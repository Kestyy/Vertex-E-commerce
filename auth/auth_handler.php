<?php
session_start();

define('ROOT_PATH', dirname(__DIR__));
define('ROOT_URL', rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));

require_once ROOT_PATH . '/assets/php/db.php';
require_once ROOT_PATH . '/assets/php/email.php';

$action   = $_POST['action']   ?? '';
$redirect = $_POST['redirect'] ?? 'index.php';

if (preg_match('#(://|^/)#', $redirect)) {
    $redirect = 'index.php';
}

function authRedirect(string $to): void {
    header('Location: ' . ROOT_URL . '/auth/' . $to);
    exit;
}

function capitalizeName(string $name): string {
    return implode(' ', array_map(function ($word) {
        return implode('-', array_map(function ($part) {
            return mb_strtoupper(mb_substr($part, 0, 1)) . mb_strtolower(mb_substr($part, 1));
        }, explode('-', $word)));
    }, explode(' ', trim($name))));
}

function isValidName(string $name): bool {
    $name = trim($name);
    if (empty($name)) return false;
    if (mb_strlen($name) > 50) return false;
    foreach (explode(' ', $name) as $word) {
        if (!preg_match('/^[a-zA-ZÀ-ÿ]+(-[a-zA-ZÀ-ÿ]+)?$/u', $word)) return false;
    }
    return true;
}

// ── LOGIN ─────────────────────────────────────────────────────────────────────
if ($action === 'login') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    if (empty($email) || empty($password)) {
        $_SESSION['auth_error'] = 'Please fill in all fields.';
        $_SESSION['auth_tab']   = 'login';
        authRedirect('login.php');
    }

    // ✅ FIXED: Added 'role' to SELECT
    $stmt = mysqli_prepare($conn, 'SELECT id, full_name, password, is_verified, role FROM users WHERE email = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($user && password_verify($password, $user['password'])) {
        if (!$user['is_verified']) {
            $code = sprintf('%06d', mt_rand(0, 999999));
            $upd  = mysqli_prepare($conn,
                'UPDATE users SET verification_code = ?, verification_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = ?'
            );
            mysqli_stmt_bind_param($upd, 'si', $code, $user['id']);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);

            $nameParts = explode(' ', $user['full_name'], 2);
            sendVerificationEmail($email, $nameParts[0], $code);

            $_SESSION['verify_user_id'] = $user['id'];
            $_SESSION['verify_email']   = $email;
            $_SESSION['verify_name']    = $nameParts[0];

            header('Location: ' . ROOT_URL . '/auth/verify.php');
            exit;
        }

        // ✅ Set session variables
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        unset($_SESSION['auth_error'], $_SESSION['auth_tab']);

        // ✅ Redirect based on role: admin → admin dashboard, customer → regular page
        if (($user['role'] ?? '') === 'admin') {
            header('Location: ' . ROOT_URL . '/admin/dashboard.php');
        } else {
            header('Location: ' . ROOT_URL . '/' . $redirect);
        }
        exit;
    } else {
        $_SESSION['auth_error'] = 'Invalid email or password.';
        $_SESSION['auth_tab']   = 'login';
        authRedirect('login.php');
    }
}

// ── REGISTER ──────────────────────────────────────────────────────────────────
if ($action === 'register') {
    $first_name_raw = trim($_POST['first_name'] ?? '');
    $last_name_raw  = trim($_POST['last_name']  ?? '');
    $email          = trim($_POST['email']       ?? '');
    $password       = $_POST['password']         ?? '';

    if (!isValidName($first_name_raw)) {
        $_SESSION['auth_error'] = 'First name may only contain letters and one hyphen per word.';
        $_SESSION['auth_tab']   = 'register';
        authRedirect('login.php');
    }
    if (!isValidName($last_name_raw)) {
        $_SESSION['auth_error'] = 'Last name may only contain letters and one hyphen per word.';
        $_SESSION['auth_tab']   = 'register';
        authRedirect('login.php');
    }

    $first_name = capitalizeName($first_name_raw);
    $last_name  = capitalizeName($last_name_raw);
    $full_name  = $first_name . ' ' . $last_name;

    if (empty($email) || empty($password)) {
        $_SESSION['auth_error'] = 'Please fill in all fields.';
        $_SESSION['auth_tab']   = 'register';
        authRedirect('login.php');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['auth_error'] = 'Please enter a valid email address.';
        $_SESSION['auth_tab']   = 'register';
        authRedirect('login.php');
    }
    if (strlen($password) < 8) {
        $_SESSION['auth_error'] = 'Password must be at least 8 characters.';
        $_SESSION['auth_tab']   = 'register';
        authRedirect('login.php');
    }

    // Duplicate email check
    $chk = mysqli_prepare($conn, 'SELECT id, is_verified FROM users WHERE email = ? LIMIT 1');
    mysqli_stmt_bind_param($chk, 's', $email);
    mysqli_stmt_execute($chk);
    $chkRes     = mysqli_stmt_get_result($chk);
    $existingUser = mysqli_fetch_assoc($chkRes);
    mysqli_stmt_close($chk);

    if ($existingUser) {
        if (!$existingUser['is_verified']) {
            // Account exists but unverified — delete it so they can re-register cleanly
            $del = mysqli_prepare($conn, 'DELETE FROM users WHERE id = ?');
            mysqli_stmt_bind_param($del, 'i', $existingUser['id']);
            mysqli_stmt_execute($del);
            mysqli_stmt_close($del);
            // Now falls through and creates a fresh account below
        } else {
            // Fully verified account already exists
            $_SESSION['auth_error'] = 'An account with that email already exists.';
            $_SESSION['auth_tab']   = 'register';
            authRedirect('login.php');
        }
    }

    $verification_code = sprintf('%06d', mt_rand(0, 999999));
    $hashed            = password_hash($password, PASSWORD_DEFAULT);

    // ✅ Correct column name: verification_expires_at
    $ins = mysqli_prepare($conn,
        'INSERT INTO users (full_name, email, password, verification_code, verification_expires_at, is_verified, created_at)
         VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0, NOW())'
    );
    mysqli_stmt_bind_param($ins, 'ssss', $full_name, $email, $hashed, $verification_code);

    if (mysqli_stmt_execute($ins)) {
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($ins);

        if (sendVerificationEmail($email, $first_name, $verification_code)) {
            $_SESSION['verify_user_id'] = $newId;
            $_SESSION['verify_email']   = $email;
            $_SESSION['verify_name']    = $first_name;

            header('Location: ' . ROOT_URL . '/auth/verify.php');
            exit;
        } else {
            $del = mysqli_prepare($conn, 'DELETE FROM users WHERE id = ?');
            mysqli_stmt_bind_param($del, 'i', $newId);
            mysqli_stmt_execute($del);
            mysqli_stmt_close($del);

            $_SESSION['auth_error'] = 'Failed to send verification email. Please try again.';
            $_SESSION['auth_tab']   = 'register';
            authRedirect('login.php');
        }
    } else {
        mysqli_stmt_close($ins);
        $_SESSION['auth_error'] = 'Something went wrong. Please try again.';
        $_SESSION['auth_tab']   = 'register';
        authRedirect('login.php');
    }
}

authRedirect('login.php');