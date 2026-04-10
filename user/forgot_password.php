<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set timezone (adjust to your location, e.g., 'Asia/Kathmandu')
date_default_timezone_set('Asia/Kathmandu');

$message = "";
$error = "";
$debug = "";

// Function to send email (no undefined variable now)
function sendPasswordResetEmail($to_email, $to_name, $reset_link) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        $mail->setFrom(SMTP_USER, APP_NAME);
        $mail->addAddress($to_email, $to_name);
        
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request - ' . APP_NAME;
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto;'>
                <div style='background: #1a5c2a; padding: 20px; text-align: center;'>
                    <h2 style='color: white; margin: 0;'>" . APP_NAME . "</h2>
                </div>
                <div style='padding: 20px; border: 1px solid #ddd;'>
                    <h3>Hello " . htmlspecialchars($to_name) . ",</h3>
                    <p>We received a request to reset your password.</p>
                    <p>Click the link below (valid for 24 hours):</p>
                    <p style='text-align: center;'>
                        <a href='" . $reset_link . "' style='display: inline-block; padding: 12px 24px; background: #2e7d32; color: white; text-decoration: none; border-radius: 5px;'>Reset Password</a>
                    </p>
                    <p>If you didn't request this, please ignore this email.</p>
                </div>
                <div style='text-align: center; padding: 20px; font-size: 12px; color: #666;'>
                    <p>Thank you,<br>" . APP_NAME . " Team</p>
                </div>
            </div>
        ";
        
        $mail->AltBody = "Hello " . $to_name . ",\n\nReset your password (valid 24 hours):\n" . $reset_link . "\n\nIgnore if not requested.\n\n" . APP_NAME . " Team";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Handle password reset request
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['request_reset'])) {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = "Please enter your email address.";
    } else {
        $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if (!$user) {
            $error = "No account found with that email address.";
        } else {
            // Generate token with 24 hours expiry
            $reset_token = bin2hex(random_bytes(32));
            $reset_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?");
            $stmt->bind_param("sss", $reset_token, $reset_expiry, $email);
            
            if ($stmt->execute()) {
                $reset_link = BASE_URL . "/forgot_password.php?token=" . urlencode($reset_token) . "&email=" . urlencode($email);
                
                if (sendPasswordResetEmail($email, $user['full_name'], $reset_link)) {
                    $message = "Password reset link has been sent to your email address. It will expire in 24 hours.";
                } else {
                    $error = "Failed to send email. Please try again later.";
                }
            } else {
                $error = "Database error. Please try again.";
            }
            $stmt->close();
        }
    }
}

// Handle password reset submission (when user submits new password)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['reset_password'])) {
    $token = trim($_POST['token'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in both password fields.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_expiry > NOW()");
        $stmt->bind_param("ss", $email, $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "Invalid or expired reset link.";
        } else {
            $user = $result->fetch_assoc();
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
            $updateStmt->bind_param("si", $hashed_password, $user['id']);
            
            if ($updateStmt->execute()) {
                $_SESSION['reset_success'] = "Password successfully reset! Please login.";
                header("Location: login.php");
                exit;
            } else {
                $error = "Failed to reset password.";
            }
            $updateStmt->close();
        }
        $stmt->close();
    }
}

// Check reset link from email (display reset form if valid)
$show_reset_form = false;
$reset_email = "";
$reset_token = "";

if (isset($_GET['token']) && isset($_GET['email'])) {
    $reset_token = $_GET['token'];
    $reset_email = urldecode($_GET['email']);
    
    $debug .= "Server current time: " . date('Y-m-d H:i:s') . "<br>";
    $debug .= "Email from URL: " . htmlspecialchars($reset_email) . "<br>";
    $debug .= "Token from URL: " . htmlspecialchars($reset_token) . "<br>";
    
    $checkStmt = $conn->prepare("SELECT reset_token, reset_expiry, reset_expiry > NOW() as is_valid FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $reset_email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $dbData = $checkResult->fetch_assoc();
        $debug .= "DB Token: " . ($dbData['reset_token'] ?? 'NULL') . "<br>";
        $debug .= "DB Expiry: " . ($dbData['reset_expiry'] ?? 'NULL') . "<br>";
        $debug .= "Is Valid (DB check): " . ($dbData['is_valid'] ? "Yes" : "No") . "<br>";
        $debug .= "Token Match: " . (($dbData['reset_token'] === $reset_token) ? "YES" : "NO") . "<br>";
    } else {
        $debug .= "No user found with email: " . htmlspecialchars($reset_email) . "<br>";
    }
    $checkStmt->close();
    
    // Verify token
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_expiry > NOW()");
    $stmt->bind_param("ss", $reset_email, $reset_token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $show_reset_form = true;
    } else {
        $error = "Invalid or expired reset link. Please request a new one.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Nepal Travel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            height: 100vh;
            display: flex;
            background: linear-gradient(135deg, #1a5c2a 0%, #0d3b1a 100%);
        }
        .container {
            width: 100%;
            max-width: 500px;
            margin: auto;
            padding: 2rem;
        }
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            padding: 2rem;
        }
        .card h2 {
            font-size: 28px;
            font-weight: 700;
            color: #1a5c2a;
            margin-bottom: 0.5rem;
        }
        .card .subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.2rem;
        }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
        }
        .form-group input:focus {
            border-color: #2e7d32;
        }
        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #2e7d32;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 0.5rem;
        }
        .btn-submit:hover {
            background: #1b5e20;
        }
        .btn-back {
            width: 100%;
            padding: 12px;
            background: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
            display: inline-block;
            text-decoration: none;
            margin-top: 0.8rem;
        }
        .btn-back:hover {
            background: #e0e0e0;
        }
        .message {
            font-size: 14px;
            margin-bottom: 1.2rem;
            padding: 10px 14px;
            border-radius: 8px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .debug-box {
            background: #f0f0f0;
            padding: 10px;
            margin-top: 15px;
            font-size: 12px;
            font-family: monospace;
            border-radius: 5px;
            word-wrap: break-word;
        }
        hr {
            margin: 1.5rem 0;
            border: none;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        
        <?php if ($show_reset_form): ?>
            <!-- Show Reset Password Form -->
            <h2>Reset Password</h2>
            <div class="subtitle">Create a new password for your account</div>
            
            <?php if (!empty($error)): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($reset_token); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($reset_email); ?>">
                
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" placeholder="Enter new password (min. 6 characters)" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm your new password" required>
                </div>
                <button type="submit" name="reset_password" class="btn-submit">Reset Password</button>
            </form>
            <a href="login.php" class="btn-back">← Back to Login</a>
            
            <?php if (!empty($debug)): ?>
                <div class="debug-box">
                    <strong>Debug Info:</strong><br>
                    <?php echo $debug; ?>
                </div>
            <?php endif; ?>
            
        <?php elseif (isset($_GET['token']) || isset($_GET['email'])): ?>
            <!-- Invalid Reset Link -->
            <h2>Invalid Reset Link</h2>
            <div class="subtitle">The password reset link is invalid or expired</div>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            
            <?php if (!empty($debug)): ?>
                <div class="debug-box">
                    <strong>Debug Info:</strong><br>
                    <?php echo $debug; ?>
                </div>
            <?php endif; ?>
            
            <a href="forgot_password.php" class="btn-submit" style="display: block; text-align: center; text-decoration: none;">Request New Reset Link</a>
            <a href="login.php" class="btn-back">← Back to Login</a>
            
        <?php else: ?>
            <!-- Show Request Reset Form -->
            <h2>Forgot Password?</h2>
            <div class="subtitle">Enter your email address and we'll send you a link to reset your password.</div>
            
            <?php if (!empty($message)): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="your@email.com" required>
                </div>
                <button type="submit" name="request_reset" class="btn-submit">Send Reset Link</button>
            </form>
            
            <hr>
            
            <a href="login.php" class="btn-back">← Back to Login</a>
            <a href="register.php" class="btn-back" style="margin-top: 8px;">Create New Account →</a>
        <?php endif; ?>
        
    </div>
</div>
</body>
</html>