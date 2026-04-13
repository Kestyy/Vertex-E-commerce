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
        'auth_url'     => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url'    => 'https://oauth2.googleapis.com/token',
        'userinfo_url' => 'https://www.googleapis.com/oauth2/v3/userinfo',
        'scope'        => 'openid email profile',
    ],
];

$provider = $_GET['provider'] ?? '';

if (!isset($providers[$provider])) {
    $_SESSION['auth_error'] = 'Invalid authentication provider';
    $_SESSION['auth_tab']   = 'login';
    header('Location: ' . ROOT_URL . '/auth/login.php');
    exit;
}

$config = $providers[$provider];

$state                   = bin2hex(random_bytes(16));
$_SESSION['oauth_state']    = $state;
$_SESSION['oauth_provider'] = $provider;
$_SESSION['oauth_remember'] = $_GET['remember'] ?? false;

$params = [
    'client_id'     => $config['client_id'],
    'redirect_uri'  => $config['redirect_uri'],
    'response_type' => 'code',
    'scope'         => $config['scope'],
    'state'         => $state,
];

$auth_url = $config['auth_url'] . '?' . http_build_query($params);

header('Location: ' . $auth_url);
exit;