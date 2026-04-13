<?php
session_start();
header('Content-Type: application/json');

define('ROOT_PATH', dirname(__DIR__));
define('ROOT_URL', rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));

require_once ROOT_PATH . '/assets/php/db.php';
require_once ROOT_PATH . '/assets/php/email.php';

if (!isset($_SESSION['verify_user_id']) || !isset($_SESSION['verify_email'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

$user_id    = $_SESSION['verify_user_id'];
$user_email = $_SESSION['verify_email'];
$user_name  = $_SESSION['verify_name'] ?? 'User';

$new_code = sprintf('%06d', mt_rand(0, 999999));

// ✅ Correct column name: verification_expires_at
$stmt = mysqli_prepare($conn,
    'UPDATE users SET verification_code = ?, verification_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = ?'
);
mysqli_stmt_bind_param($stmt, 'si', $new_code, $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (sendVerificationEmail($user_email, $user_name, $new_code)) {
    echo json_encode(['success' => true, 'message' => 'Code resent']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send email']);
}