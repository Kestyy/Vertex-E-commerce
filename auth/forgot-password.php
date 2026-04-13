<?php
session_start();

// ✅ Correct paths for auth/ subfolder
define('ROOT_PATH', dirname(__DIR__)); // One level up = vertex/
define('ROOT_URL', rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/')); // = /vertex

require_once ROOT_PATH . '/assets/php/db.php';
require_once ROOT_PATH . '/assets/php/email.php'; // ✅ Load email helper

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ROOT_URL . '/index.php');
    exit;
}

$message = '';
$error   = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Fetch user
        $stmt = mysqli_prepare($conn, "SELECT id, email, full_name FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        
        // If user exists: generate token + send email
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $upd = mysqli_prepare($conn, "UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
            mysqli_stmt_bind_param($upd, "ssi", $token, $expires, $user['id']);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
            
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $resetLink = "{$protocol}://{$host}" . ROOT_URL . "/auth/reset-password.php?token=$token&email=" . urlencode($email);
            
            sendPasswordResetEmail($email, $user['full_name'] ?? 'there', $resetLink);
        }
        
        $message = "Reset link sent to <strong>" . htmlspecialchars($email) . "</strong>. Check your inbox.";
    }
}

// ✅ Add this function to send reset emails (add to email.php or keep here)
function sendPasswordResetEmail($toEmail, $toName, $resetLink) {
    // Load PHPMailer if not already loaded
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $basePath = dirname(__DIR__, 2); // vertex/
        if (file_exists($basePath . '/vendor/PHPMailer/PHPMailer/PHPMailer.php')) {
            require_once $basePath . '/vendor/PHPMailer/PHPMailer/PHPMailer.php';
            require_once $basePath . '/vendor/PHPMailer/PHPMailer/SMTP.php';
            require_once $basePath . '/vendor/PHPMailer/PHPMailer/Exception.php';
        } else {
            error_log('PHPMailer not found. Cannot send reset email.');
            return false;
        }
    }

    $fromEmail = getenv('SMTP_FROM_EMAIL') ?: 'noreply@vertex.local';
    $fromName  = getenv('SMTP_FROM_NAME')  ?: 'Vertex';
    $debugMode = getenv('SMTP_DEBUG') === 'true';

    $displayName = $toName ? htmlspecialchars($toName) : 'there';
    $subject = 'Reset Your Vertex Password';
    
    $body =
        "<html><body style=\"font-family: Arial, sans-serif; color: #333;\">" .
        "<div style=\"max-width: 600px; margin: 0 auto; padding: 20px;\">" .
        "<h2 style=\"color: #3b82f6; margin-bottom: 20px;\">Reset Your Password</h2>" .
        "<p>Hi {$displayName},</p>" .
        "<p>We received a request to reset your password for your Vertex account.</p>" .
        "<div style=\"text-align: center; margin: 30px 0;\">" .
        "<a href=\"{$resetLink}\" style=\"background: #3b82f6; color: #fff; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-weight: 600; display: inline-block;\">Reset Password</a>" .
        "</div>" .
        "<p><strong>This link expires in 1 hour.</strong></p>" .
        "<p>If you didn't request this, you can safely ignore this email. Your password will remain unchanged.</p>" .
        "<hr style=\"border: none; border-top: 1px solid #eee; margin: 30px 0;\" />" .
        "<p style=\"color: #999; font-size: 12px;\">Vertex Shop &mdash; Secure Account Management</p>" .
        "</div></body></html>";

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host      = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth  = true;
        $mail->Username  = getenv('SMTP_USERNAME') ?: '';
        $mail->Password  = getenv('SMTP_PASSWORD') ?: '';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port      = (int)(getenv('SMTP_PORT') ?: 587);

        if ($debugMode) {
            $mail->SMTPDebug  = 2;
            $mail->Debugoutput = function ($str, $level) {
                error_log("PHPMailer Debug [{$level}]: {$str}");
            };
        }

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName ?: '');
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = "Reset your Vertex password: {$resetLink}\n\nThis link expires in 1 hour.\n\nIf you didn't request this, ignore this email.";

        $mail->send();
        error_log("Reset email sent to {$toEmail}");
        return true;

    } catch (Exception $e) {
        error_log('PHPMailer Exception in sendPasswordResetEmail: ' . $e->getMessage());
        return false;
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
    <title>Forgot Password — Vertex</title>
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
        .auth-input-wrap i { color: #94a3b8; flex-shrink: 0; font-size: 1.5rem; width: 18px; text-align: center; }
        .auth-input-wrap input {
            flex: 1; border: none; outline: none; font-family: 'Poppins', sans-serif; font-size: 1.4rem;
            color: #0f172a; background: transparent; min-width: 0;
        }
        .auth-input-wrap input::placeholder { color: #cbd5e1; }
        .auth-btn {
            width: 100%; padding: 1.5rem; background: #3b82f6; color: #fff; border: none; border-radius: 1rem;
            font-family: 'Poppins', sans-serif; font-size: 1.5rem; font-weight: 600; cursor: pointer;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 14px rgba(59,130,246,0.35); margin-bottom: 1.8rem;
        }
        .auth-btn:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(59,130,246,0.42); }
        .auth-btn:active { transform: translateY(0); }
        .auth-alert {
            padding: 14px 16px; border-radius: 10px; font-size: 1.3rem; margin-bottom: 2rem;
            display: flex; align-items: flex-start; gap: 10px;
        }
        .auth-alert.success { background: #f0fdf4; border: 1.5px solid #bbf7d0; color: #166534; }
        .auth-alert.error   { background: #fef2f2; border: 1.5px solid #fecaca; color: #b91c1c; }
        .auth-alert.info    { background: #eff6ff; border: 1.5px solid #bfdbfe; color: #1e40af; }
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
        
        <?php if ($message): ?>
            <div class="auth-alert info" role="alert">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
                <span><?= $message ?></span>
            </div>
            <a href="<?= ROOT_URL ?>/auth/login.php" class="auth-link">← Back to Log In</a>
        <?php elseif ($error): ?>
            <div class="auth-alert error" role="alert">
                <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <form method="POST">
                <div class="auth-field">
                    <label for="email">Email Address</label>
                    <div class="auth-input-wrap">
                        <i class="fas fa-envelope" aria-hidden="true"></i>
                        <input type="email" id="email" name="email" placeholder="your@email.com" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                               required autocomplete="email"/>
                    </div>
                </div>
                <button type="submit" class="auth-btn">Send Reset Link</button>
            </form>
            <a href="<?= ROOT_URL ?>/auth/login.php" class="auth-link">← Back to Log In</a>
        <?php else: ?>
            <div class="auth-header">
                <h2>Forgot Password?</h2>
                <p>Enter your email and we'll send you a link to reset your password.</p>
            </div>
            
            <form method="POST">
                <div class="auth-field">
                    <label for="email">Email Address</label>
                    <div class="auth-input-wrap">
                        <i class="fas fa-envelope" aria-hidden="true"></i>
                        <input type="email" id="email" name="email" placeholder="your@email.com" 
                               required autocomplete="email"/>
                    </div>
                </div>
                <button type="submit" class="auth-btn">Send Reset Link</button>
            </form>
            
            <a href="<?= ROOT_URL ?>/auth/login.php" class="auth-link">← Back to Log In</a>
        <?php endif; ?>
        
    </div>
</div>

<?php include ROOT_PATH . '/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[method="POST"]');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    if (form && submitBtn) {
        form.addEventListener('submit', function(e) {
            if (form.checkValidity()) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Sending...';
                submitBtn.style.opacity = '0.7';
                submitBtn.style.cursor = 'not-allowed';
            }
        });
    }
});
</script>
</body>
</html>