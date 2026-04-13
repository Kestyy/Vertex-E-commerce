<?php
// assets/php/email.php
// Email helper using PHPMailer for reliable SMTP delivery

// Load environment configuration
if (file_exists(dirname(__DIR__, 2) . '/.env.php')) {
    require_once dirname(__DIR__, 2) . '/.env.php';
}

// Load PHPMailer
if (file_exists(dirname(__DIR__, 2) . '/vendor/PHPMailer/PHPMailer/PHPMailer.php')) {
    require_once dirname(__DIR__, 2) . '/vendor/PHPMailer/PHPMailer/PHPMailer.php';
    require_once dirname(__DIR__, 2) . '/vendor/PHPMailer/PHPMailer/SMTP.php';
    require_once dirname(__DIR__, 2) . '/vendor/PHPMailer/PHPMailer/Exception.php';
}

// TEMPORARY DEBUG — remove after testing
error_log('PHPMailer path: ' . dirname(__DIR__, 2) . '/vendor/PHPMailer/PHPMailer/PHPMailer.php');
error_log('PHPMailer file exists: ' . (file_exists(dirname(__DIR__, 2) . '/vendor/PHPMailer/PHPMailer/PHPMailer.php') ? 'YES' : 'NO'));
error_log('PHPMailer class exists: ' . (class_exists('PHPMailer\PHPMailer\PHPMailer') ? 'YES' : 'NO'));

function sendVerificationEmail($toEmail, $toName, $code) {
    $fromEmail = getenv('SMTP_FROM_EMAIL') ?: 'noreply@vertex.local';
    $fromName  = getenv('SMTP_FROM_NAME')  ?: 'Vertex';
    $debugMode = getenv('SMTP_DEBUG') === 'true';

    $displayName = $toName ? htmlspecialchars($toName) : 'there';

    $subject = 'Vertex Account Verification';
    $body =
        "<html><body style=\"font-family: Arial, sans-serif;\">" .
        "<p>Hi {$displayName},</p>" .
        "<p>Thank you for registering at Vertex. Use the code below to verify your email address:</p>" .
        "<div style=\"background:#f5f5f5;padding:20px;border-radius:8px;text-align:center;margin:20px 0;\">" .
        "<h2 style=\"letter-spacing:4px;margin:0;font-size:32px;color:#333;\">{$code}</h2>" .
        "</div>" .
        "<p><strong>This code expires in 10 minutes.</strong></p>" .
        "<p>If you did not register, you can safely ignore this message.</p>" .
        "<p style=\"color:#999;font-size:12px;margin-top:30px;\">Vertex Shop &mdash; Account Verification</p>" .
        "</body></html>";

    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log('PHPMailer not installed. Cannot send email.');
        return false;
    }

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
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>'], "\n", $body));

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('PHPMailer Exception: ' . $e->getMessage());
        return false;
    }
}