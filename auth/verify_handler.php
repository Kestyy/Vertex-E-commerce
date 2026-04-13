<?php
session_start();
header('Content-Type: application/json');

define('ROOT_PATH', dirname(__DIR__));
define('ROOT_URL', rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));

require_once ROOT_PATH . '/assets/php/db.php';

if (!isset($_SESSION['verify_user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

$user_id  = $_SESSION['verify_user_id'];
$otp_code = $_POST['otp_code'] ?? '';

if (strlen($otp_code) !== 6 || !ctype_digit($otp_code)) {
    echo json_encode(['success' => false, 'message' => 'Invalid code format']);
    exit;
}

// ✅ Correct column name: verification_expires_at
$stmt = mysqli_prepare($conn,
    'SELECT id FROM users WHERE id = ? AND verification_code = ? AND verification_expires_at > NOW()'
);
mysqli_stmt_bind_param($stmt, 'is', $user_id, $otp_code);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) === 0) {
    mysqli_stmt_close($stmt);

    // Check if it was simply expired
    $chk = mysqli_prepare($conn, 'SELECT verification_expires_at FROM users WHERE id = ?');
    mysqli_stmt_bind_param($chk, 'i', $user_id);
    mysqli_stmt_execute($chk);
    $res = mysqli_stmt_get_result($chk);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($chk);

    if ($row && $row['verification_expires_at'] < date('Y-m-d H:i:s')) {
        echo json_encode(['success' => false, 'message' => 'Code expired. Request a new one.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid code. Try again.']);
    }
    exit;
}
mysqli_stmt_close($stmt);

// Mark user as verified
$upd = mysqli_prepare($conn,
    'UPDATE users SET is_verified = 1, verification_code = NULL, verification_expires_at = NULL, verified_at = NOW() WHERE id = ?'
);
mysqli_stmt_bind_param($upd, 'i', $user_id);
mysqli_stmt_execute($upd);
mysqli_stmt_close($upd);

// Fetch full_name for session
$s = mysqli_prepare($conn, 'SELECT full_name FROM users WHERE id = ?');
mysqli_stmt_bind_param($s, 'i', $user_id);
mysqli_stmt_execute($s);
$res  = mysqli_stmt_get_result($s);
$row  = mysqli_fetch_assoc($res);
mysqli_stmt_close($s);

$_SESSION['user_id']   = $user_id;
$_SESSION['user_name'] = $row['full_name'] ?? '';
unset($_SESSION['verify_user_id'], $_SESSION['verify_email'], $_SESSION['verify_name']);

echo json_encode([
    'success'  => true,
    'message'  => 'Email verified successfully',
    'redirect' => ROOT_URL . '/index.php',
]);