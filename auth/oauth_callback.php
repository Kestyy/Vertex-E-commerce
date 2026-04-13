<?php
session_start();

define('ROOT_PATH', dirname(__DIR__));
define('ROOT_URL', rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));

require_once ROOT_PATH . '/assets/php/db.php';

$providers = [
    'google' => [
        'client_id'    => '738682054341-n1naghl43licafl141tfuvhjbgptmp8n.apps.googleusercontent.com',
        'client_secret'=> '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',
        'redirect_uri' => 'http://localhost/vertex/auth/oauth_callback.php',
        'token_url'    => 'https://oauth2.googleapis.com/token',
        'userinfo_url' => 'https://www.googleapis.com/oauth2/v3/userinfo',
    ],
];

$provider = $_SESSION['oauth_provider'] ?? '';
$code     = $_GET['code']  ?? '';
$state    = $_GET['state'] ?? '';

if (!isset($_SESSION['oauth_state']) || $state !== $_SESSION['oauth_state']) {
    $_SESSION['auth_error'] = 'Invalid authentication request';
    $_SESSION['auth_tab']   = 'login';
    header('Location: ' . ROOT_URL . '/auth/login.php'); exit;
}
if (!isset($providers[$provider]) || empty($code)) {
    $_SESSION['auth_error'] = 'Authentication failed';
    $_SESSION['auth_tab']   = 'login';
    header('Location: ' . ROOT_URL . '/auth/login.php'); exit;
}

$config         = $providers[$provider];
$token_response = exchange_code_for_token($config, $code);

if (!$token_response || isset($token_response['error'])) {
    error_log('OAuth Token Error: ' . print_r($token_response, true));
    $_SESSION['auth_error'] = 'Failed to get access token';
    $_SESSION['auth_tab']   = 'login';
    header('Location: ' . ROOT_URL . '/auth/login.php'); exit;
}

$user_info = get_user_info($config, $token_response['access_token']);
if (!$user_info) {
    $_SESSION['auth_error'] = 'Failed to get user information';
    $_SESSION['auth_tab']   = 'login';
    header('Location: ' . ROOT_URL . '/auth/login.php'); exit;
}

$user_id = find_or_create_user($user_info, $provider);
if ($user_id) {
    $_SESSION['user_id']   = $user_id;
    $full_name             = resolve_display_name($user_info, $provider);
    $_SESSION['user_name'] = $full_name;

    if (isset($_SESSION['oauth_remember']) && $_SESSION['oauth_remember']) {
        setcookie('remember_token', bin2hex(random_bytes(32)), time() + (30 * 24 * 60 * 60), '/');
    }

    header('Location: ' . ROOT_URL . '/index.php'); exit;
} else {
    $_SESSION['auth_error'] = 'Failed to create or login user';
    $_SESSION['auth_tab']   = 'login';
    header('Location: ' . ROOT_URL . '/auth/login.php'); exit;
}

// ─────────────────────────────────────────────────────────────────────────────

function resolve_display_name(array $user_info, string $provider): string {
    $given  = trim($user_info['given_name']  ?? '');
    $family = trim($user_info['family_name'] ?? '');

    if ($given === '' && $family === '') {
        $full  = trim($user_info['name'] ?? '');
        $parts = explode(' ', $full, 2);
        $given  = $parts[0] ?? '';
        $family = $parts[1] ?? '';
    }

    $full = trim("$given $family");
    return capitalize_name($full ?: 'User');
}

function capitalize_name(string $name): string {
    return implode(' ', array_map(function ($word) {
        return implode('-', array_map(function ($part) {
            return mb_strtoupper(mb_substr($part, 0, 1)) . mb_strtolower(mb_substr($part, 1));
        }, explode('-', $word)));
    }, explode(' ', $name)));
}

function exchange_code_for_token(array $config, string $code): array|false {
    $params = [
        'client_id'     => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri'  => $config['redirect_uri'],
        'code'          => $code,
        'grant_type'    => 'authorization_code',
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $config['token_url'],
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'],
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err       = curl_errno($ch);
    curl_close($ch);

    if ($err) return false;
    $result = json_decode($response, true);
    return ($http_code === 200 && !isset($result['error'])) ? $result : false;
}

function get_user_info(array $config, string $access_token): array|false {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $config['userinfo_url'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $access_token", 'Accept: application/json'],
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($http_code === 200) ? json_decode($response, true) : false;
}

function find_or_create_user(array $user_info, string $provider): int|false {
    global $conn;

    $email = $user_info['email'] ?? '';
    if (empty($email)) return false;

    $provider_id = $user_info['sub'] ?? $user_info['id'] ?? '';
    $full_name   = resolve_display_name($user_info, $provider);

    // Check if user already exists
    $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($user) {
        $upd = mysqli_prepare($conn,
            'UPDATE users SET oauth_provider = ?, oauth_provider_id = ?, full_name = ?, is_verified = 1 WHERE id = ?'
        );
        mysqli_stmt_bind_param($upd, 'sssi', $provider, $provider_id, $full_name, $user['id']);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
        return $user['id'];
    }

    // Create new Google user (always verified)
    $password    = bin2hex(random_bytes(32));
    $hashed      = password_hash($password, PASSWORD_DEFAULT);
    $is_verified = 1;
    $verified_at = date('Y-m-d H:i:s');

    $ins = mysqli_prepare($conn, '
        INSERT INTO users (full_name, email, password, oauth_provider, oauth_provider_id, is_verified, verified_at, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ');

    mysqli_stmt_bind_param($ins, 'sssssis',
        $full_name,
        $email,
        $hashed,
        $provider,
        $provider_id,
        $is_verified,
        $verified_at
    );

    if (mysqli_stmt_execute($ins)) {
        $id = mysqli_insert_id($conn);
        mysqli_stmt_close($ins);
        return $id;
    }

    mysqli_stmt_close($ins);
    return false;
}