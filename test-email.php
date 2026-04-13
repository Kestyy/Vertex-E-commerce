<?php
/**
 * Email Configuration Tester
 * 
 * Open in browser: http://localhost/vertex/test-email.php
 * 
 * This script tests your SMTP configuration without creating a user account.
 */

// Load configuration
if (file_exists(dirname(__FILE__) . '/.env.php')) {
    require_once dirname(__FILE__) . '/.env.php';
} else {
    die('Error: .env.php not found. Please create it first.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Configuration Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .config-box {
            background: #f9f9f9;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 12px;
        }
        .form-group {
            margin: 20px 0;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover { background: #0056b3; }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .steps {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .steps h3 { margin-top: 0; }
        .steps ol { margin: 10px 0; }
        .steps li { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Email Configuration Test</h1>
        
        <?php
        $host = getenv('SMTP_HOST');
        $username = getenv('SMTP_USERNAME');
        $password = getenv('SMTP_PASSWORD');
        $debug = getenv('SMTP_DEBUG') === 'true';
        
        $hasConfig = $host && $username && $password;
        ?>
        
        <div class="config-box">
            <strong>Current Configuration:</strong><br>
            SMTP_HOST: <?php echo $host ?: '(not set)'; ?><br>
            SMTP_USERNAME: <?php echo $username ? substr($username, 0, 3) . '***' : '(not set)'; ?><br>
            SMTP_PASSWORD: <?php echo $password ? '***' : '(not set)'; ?><br>
            Debug Mode: <?php echo $debug ? '✓ ON' : '✗ OFF'; ?>
        </div>
        
        <?php if (!$hasConfig): ?>
            <div class="error">
                <strong>⚠️ Configuration incomplete!</strong> Please edit <code>.env.php</code> and fill in your SMTP credentials.
            </div>
            
            <div class="steps">
                <h3>Quick Setup:</h3>
                <ol>
                    <li><strong>With Gmail:</strong>
                        <ul>
                            <li>Go to <a href="https://myaccount.google.com/apppasswords" target="_blank">myaccount.google.com/apppasswords</a></li>
                            <li>Select "Mail" and "Windows"</li>
                            <li>Copy the 16-character password</li>
                            <li>Edit <code>.env.php</code> and paste it in SMTP_PASSWORD</li>
                            <li>Set SMTP_USERNAME to your Gmail address</li>
                        </ul>
                    </li>
                    <li><strong>With Mailtrap:</strong>
                        <ul>
                            <li>Sign up free at <a href="https://mailtrap.io" target="_blank">mailtrap.io</a></li>
                            <li>Go to Email Sending → Gmail</li>
                            <li>Copy the SMTP settings to <code>.env.php</code></li>
                        </ul>
                    </li>
                </ol>
            </div>
        <?php else: ?>
            <div class="info">
                ✓ Configuration found! Testing your email setup...
            </div>
            
            <form method="post">
                <div class="form-group">
                    <label for="test_email">Test Email Address:</label>
                    <input type="email" name="test_email" id="test_email" value="<?php echo htmlspecialchars($_POST['test_email'] ?? ''); ?>" placeholder="your.email@example.com" required>
                </div>
                
                <div class="form-group">
                    <label for="test_name">Your Name:</label>
                    <input type="text" name="test_name" id="test_name" value="<?php echo htmlspecialchars($_POST['test_name'] ?? 'Test User'); ?>" required>
                </div>
                
                <button type="submit" name="test_smtp">Send Test Email</button>
            </form>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_smtp'])) {
                $test_email = trim($_POST['test_email'] ?? '');
                $test_name = trim($_POST['test_name'] ?? '');
                
                if (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
                    echo '<div class="error">Invalid email address.</div>';
                } else {
                    // Load PHPMailer and send test
                    require_once dirname(__FILE__) . '/assets/php/email.php';
                    
                    // Generate a test code
                    $testCode = '123456';
                    
                    echo '<div class="info">Sending test email to ' . htmlspecialchars($test_email) . '...</div>';
                    
                    $result = sendVerificationEmail($test_email, $test_name, $testCode);
                    
                    if ($result) {
                        echo '<div class="success">
                            <strong>✓ Success!</strong> Test email sent successfully.<br>
                            Check your inbox (or spam folder) for the verification email.
                        </div>';
                    } else {
                        echo '<div class="error">
                            <strong>✗ Failed!</strong> Email could not be sent.<br>
                            Check your SMTP credentials and PHP error logs.
                        </div>';
                    }
                }
            }
            ?>
        <?php endif; ?>
        
        <div class="info" style="margin-top: 40px;">
            <strong>Troubleshooting:</strong>
            <ul>
                <li>Check <code>php.ini</code> error logs: <code><?php echo ini_get('error_log') ?: 'Not configured'; ?></code></li>
                <li>Enable debug mode in <code>.env.php</code> to see detailed SMTP errors</li>
                <li>Test with <a href="https://mailtrap.io" target="_blank">Mailtrap</a> if Gmail app passwords don't work</li>
                <li>Verify PHPMailer is installed: composer directories exist</li>
            </ul>
        </div>
    </div>
</body>
</html>
