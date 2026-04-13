<?php
session_start();

define('ROOT_PATH', dirname(__DIR__));
define('ROOT_URL', rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));

require_once ROOT_PATH . '/assets/php/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . ROOT_URL . '/index.php');
    exit;
}

$error   = '';
$success = '';
$valid   = false;

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

if ($token && $email) {
    $stmt = mysqli_prepare($conn, "
        SELECT id, reset_token, reset_token_expires 
        FROM users 
        WHERE email = ? AND reset_token = ? 
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "ss", $email, $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($user && strtotime($user['reset_token_expires']) > time()) {
        $valid = true;
    } else {
        $error = 'Invalid or expired reset link. Please request a new one.';
    }
} else {
    $error = 'Missing reset token. Please request a new password reset link.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        $upd = mysqli_prepare($conn, "
            UPDATE users 
            SET password = ?, reset_token = NULL, reset_token_expires = NULL 
            WHERE id = ?
        ");
        mysqli_stmt_bind_param($upd, "si", $hashed, $user['id']);
        
        if (mysqli_stmt_execute($upd)) {
            $success = 'Your password has been reset! You can now log in.';
            $valid = false;
        } else {
            $error = 'Failed to update password. Please try again.';
        }
        mysqli_stmt_close($upd);
    }
}

function asset(string $path): string {
    return ROOT_URL . '/' . ltrim($path, '/');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Password — Vertex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>"/>
    <style>
        .auth-page-wrap {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 10px 20px 120px;
            background: radial-gradient(ellipse 120% 80% at 0% 0%, rgba(22, 112, 255, 0.8) 0%, transparent 60%),
                        radial-gradient(ellipse 120% 80% at 100% 0%, rgba(137, 174, 255, 0.65) 0%, transparent 55%),
                        radial-gradient(ellipse 150% 80% at 50% 130%, rgba(251, 249, 255, 0.6) 0%, transparent 60%),
                        #bfdbfe;
        }
        .auth-card {
            background: #fff; border-radius: 2.4rem; padding: 4.2rem; width: 100%; max-width: 480px;
            box-shadow: 0 8px 40px rgba(15,23,42,0.10), 0 2px 8px rgba(15,23,42,0.04);
            border: 1px solid rgba(226,232,240,0.8); animation: card-in 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        @keyframes card-in { from { opacity: 0; transform: translateY(24px) scale(0.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .auth-header { margin-bottom: 2.4rem; text-align: center; }
        .auth-header h2 { font-size: 2.4rem; font-weight: 700; color: #0f172a; margin: 0 0 8px; }
        .auth-header p  { font-size: 1.4rem; color: #64748b; margin: 0; }
        .auth-field { margin-bottom: 1.6rem; }
        .auth-field label { display: block; font-size: 1.3rem; font-weight: 500; color: #64748b; margin-bottom: 6px; }
        .auth-input-wrap {
            display: flex; align-items: center; gap: 10px; border: 1.5px solid #e2e8f0; border-radius: 1rem;
            padding: 13px 16px; background: #fff; transition: border-color 0.2s, box-shadow 0.2s;
        }
        .auth-input-wrap:focus-within { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.10); }
        .auth-input-wrap.error-field { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,0.08); }
        .auth-input-wrap.success-field { border-color: #22c55e; }
        .auth-input-wrap i { color: #94a3b8; flex-shrink: 0; font-size: 1.5rem; width: 18px; text-align: center; }
        .auth-input-wrap input {
            flex: 1; border: none; outline: none; font-family: 'Poppins', sans-serif; font-size: 1.4rem;
            color: #0f172a; background: transparent; min-width: 0;
        }
        .auth-input-wrap input::placeholder { color: #cbd5e1; }
        .eye-btn { background: none; border: none; padding: 0; cursor: pointer; color: #94a3b8; display: flex; align-items: center; }
        .eye-btn:hover { color: #475569; }
        .field-error {
            font-size: 1.1rem;
            color: #ef4444;
            margin-top: 6px;
            display: none;
            align-items: center;
            gap: 6px;
        }
        .field-error.show { display: flex; }
        .auth-btn {
            width: 100%; padding: 1.5rem; background: #3b82f6; color: #fff; border: none; border-radius: 1rem;
            font-family: 'Poppins', sans-serif; font-size: 1.5rem; font-weight: 600; cursor: pointer;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s, opacity 0.2s;
            box-shadow: 0 4px 14px rgba(59,130,246,0.35); margin-bottom: 1.8rem;
        }
        .auth-btn:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(59,130,246,0.42); }
        .auth-btn:active { transform: translateY(0); }
        .auth-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
        .auth-alert {
            padding: 14px 16px; border-radius: 10px; font-size: 1.3rem; margin-bottom: 2rem;
            display: flex; align-items: flex-start; gap: 10px;
        }
        .auth-alert.success { background: #f0fdf4; border: 1.5px solid #bbf7d0; color: #166534; }
        .auth-alert.error   { background: #fef2f2; border: 1.5px solid #fecaca; color: #b91c1c; }
        .auth-link { display: block; text-align: center; font-size: 1.3rem; color: #64748b; text-decoration: none; }
        .auth-link:hover { color: #3b82f6; text-decoration: underline; }
        @media (max-width: 520px) {
            .auth-card { padding: 3rem 2.4rem; border-radius: 2rem; }
            .auth-header h2 { font-size: 2rem; }
        }
    </style>
</head>
<body>

<?php include ROOT_PATH . '/navbar.php'; ?>

<div class="auth-page-wrap">
    <div class="auth-card">
        
        <?php if ($success): ?>
            <div class="auth-alert success" role="alert">
                <i class="fas fa-circle-check" aria-hidden="true"></i>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
            <a href="<?= ROOT_URL ?>/auth/login.php" class="auth-link">Go to Log In →</a>
            
        <?php elseif ($error): ?>
            <div class="auth-alert error" role="alert">
                <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <a href="<?= ROOT_URL ?>/auth/forgot-password.php" class="auth-link">← Request New Link</a>
            
        <?php elseif ($valid): ?>
            <div class="auth-header">
                <h2>Reset Password</h2>
                <p>Enter your new password for <strong><?= htmlspecialchars($email) ?></strong></p>
            </div>
            
            <form method="POST" id="resetForm" novalidate>
                <div class="auth-field">
                    <label for="password">New Password <span style="color:#ef4444">*</span></label>
                    <div class="auth-input-wrap" id="wrapPassword">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <input type="password" id="password" name="password" placeholder="At least 8 characters" 
                               required autocomplete="new-password" minlength="8"/>
                        <button type="button" class="eye-btn" onclick="togglePw('password', this)" aria-label="Toggle password visibility">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <p class="field-error" id="errPassword" role="alert">
                        <i class="fas fa-circle-exclamation" style="font-size:1rem;"></i>
                        <span>Password must be at least 8 characters.</span>
                    </p>
                </div>
                
                <div class="auth-field">
                    <label for="confirm_password">Confirm Password <span style="color:#ef4444">*</span></label>
                    <div class="auth-input-wrap" id="wrapConfirmPassword">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="Re-enter your password" required autocomplete="new-password"/>
                        <button type="button" class="eye-btn" onclick="togglePw('confirm_password', this)" aria-label="Toggle password visibility">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <p class="field-error" id="errConfirmPassword" role="alert">
                        <i class="fas fa-circle-exclamation" style="font-size:1rem;"></i>
                        <span>Passwords do not match.</span>
                    </p>
                </div>
                
                <button type="submit" class="auth-btn" id="btnSubmit" disabled>
                    <span class="btn-text">Reset Password</span>
                </button>
            </form>
            
            <a href="<?= ROOT_URL ?>/auth/forgot-password.php" class="auth-link">← Back to Log In</a>
            
        <?php else: ?>
            <div class="auth-header">
                <h2>Invalid Link</h2>
                <p>This password reset link is invalid or has expired.</p>
            </div>
            <a href="<?= ROOT_URL ?>/auth/forgot-password.php" class="auth-link">Request a New Link →</a>
        <?php endif; ?>
        
    </div>
</div>

<?php include ROOT_PATH . '/footer.php'; ?>

<script>
function togglePw(id, btn) {
    const inp = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('resetForm');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const wrapPassword = document.getElementById('wrapPassword');
    const wrapConfirm = document.getElementById('wrapConfirmPassword');
    const errPassword = document.getElementById('errPassword');
    const errConfirm = document.getElementById('errConfirmPassword');
    const submitBtn = document.getElementById('btnSubmit');
    
    function validatePassword() {
        const val = passwordInput.value;
        if (val.length === 0) {
            wrapPassword.classList.remove('error-field', 'success-field');
            errPassword.classList.remove('show');
            return false;
        }
        if (val.length < 8) {
            wrapPassword.classList.add('error-field');
            wrapPassword.classList.remove('success-field');
            errPassword.classList.add('show');
            return false;
        }
        wrapPassword.classList.remove('error-field');
        wrapPassword.classList.add('success-field');
        errPassword.classList.remove('show');
        return true;
    }
    
    function validateConfirm() {
        const passVal = passwordInput.value;
        const confirmVal = confirmInput.value;
        if (confirmVal.length === 0) {
            wrapConfirm.classList.remove('error-field', 'success-field');
            errConfirm.classList.remove('show');
            return false;
        }
        if (passVal !== confirmVal) {
            wrapConfirm.classList.add('error-field');
            wrapConfirm.classList.remove('success-field');
            errConfirm.classList.add('show');
            return false;
        }
        wrapConfirm.classList.remove('error-field');
        wrapConfirm.classList.add('success-field');
        errConfirm.classList.remove('show');
        return true;
    }
    
    function updateSubmitButton() {
        const isPasswordValid = validatePassword();
        const isConfirmValid = validateConfirm();
        submitBtn.disabled = !(isPasswordValid && isConfirmValid);
    }
    
    passwordInput.addEventListener('input', function() {
        validatePassword();
        if (confirmInput.value.length > 0) validateConfirm();
        updateSubmitButton();
    });
    
    confirmInput.addEventListener('input', function() {
        validateConfirm();
        updateSubmitButton();
    });
    
    passwordInput.addEventListener('blur', validatePassword);
    confirmInput.addEventListener('blur', validateConfirm);
    
    form.addEventListener('submit', function(e) {
        if (!validatePassword() || !validateConfirm()) {
            e.preventDefault();
            updateSubmitButton();
            return;
        }
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i> Resetting...';
    });
});
</script>
</body>
</html>