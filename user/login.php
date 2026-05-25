<?php
/**
 * Author: Ishwor Dhital
 * Student ID: 2462304
 * Group: L5CG6
 */
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_redirect.php';

auth_capture_redirect();

$message = "";
$redirect_after = auth_get_redirect_url();
$login_url_self = auth_build_login_url($redirect_after);
$register_url = auth_build_register_url($redirect_after);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login_input = trim($_POST['login_input'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login_input) || empty($password)) {
        $message = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, password, email_verified, role FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $login_input, $login_input);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user['password'])) {
            $message = "Invalid email/username or password.";
        } elseif ($user['email_verified'] != 1) {
            $message = "Please verify your email before logging in. Check your inbox.";
        } else {
            if ($user['role'] === 'admin') {
                // Close the public session first, preserving its data
                session_write_close();
                
                // Start a brand new admin session
                session_name('nepal_admin_session');
                if (isset($_COOKIE['nepal_admin_session'])) {
                    session_id($_COOKIE['nepal_admin_session']);
                } else {
                    session_id(session_create_id());
                }
                session_start();
            }

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];

            auth_redirect_after_login($user['role']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nepal Travel - Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            height: 100vh;
            display: flex;
        }

        .login-left {
            width: 530px;
            min-width: 530px;
            background-image: url('../images/pokhara_lake.png');
            background-size: cover;
            background-position: center;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 3.5rem;
        }

        .login-left::before {
            content: '';
            position: absolute;
            inset: 0;
            backdrop-filter: blur(6px);
            background: rgba(30, 80, 120, 0.45);
        }

        .form-content {
            position: relative;
            z-index: 1;
        }

        .form-content h2 {
            font-size: 36px;
            font-weight: 800;
            color: #fbbf24;
            margin-bottom: 0.4rem;
            font-family: 'Montserrat', sans-serif;
        }

        .form-content .subtitle {
            font-size: 14px;
            color: #ddd;
            margin-bottom: 2rem;
        }

        .form-content .subtitle a {
            color: #fbbf24;
            font-weight: 700;
            text-decoration: none;
        }

        .form-group {
            margin-bottom: 1rem;
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 13px 16px;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 6px;
            font-size: 14px;
            color: #fff;
            outline: none;
        }

        .form-group input::placeholder {
            color: rgba(255,255,255,0.7);
        }

        .form-group input:focus {
            background: rgba(255,255,255,0.22);
            border-color: rgba(255,255,255,0.5);
        }

        .pass-wrap { position: relative; }
        .pass-wrap input { padding-right: 44px; }
        .eye-btn {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: rgba(255,255,255,0.6);
            padding: 0;
            display: flex;
            align-items: center;
        }
        .eye-btn:hover { color: #fff; }
        .eye-btn svg { width: 18px; height: 18px; }

        .forgot-password {
            display: block;
            text-align: right;
            font-size: 13px;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            margin-bottom: 1.5rem;
            margin-top: -0.3rem;
        }
        .forgot-password:hover { color: #fff; }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: #fbbf24;
            color: #0f172a;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            font-family: 'Montserrat', sans-serif;
        }
        .btn-login:hover { 
            background: #eab308;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(251,191,36,0.4);
        }

        .message {
            font-size: 13px;
            margin-bottom: 1rem;
            padding: 10px 14px;
            border-radius: 6px;
            background: rgba(255,0,0,0.12);
            border: 1px solid rgba(255,80,80,0.25);
            color: #ff6b6b;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .message::before { content: '✕'; font-weight: 700; font-size: 12px; }

        .login-right {
            flex: 1;
            background-image: url('../images/pokhara_lake.png');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .login-right .tagline {
            font-size: 13px;
            letter-spacing: 4px;
            color: rgba(255,255,255,0.85);
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .login-right .big-title {
            font-size: 80px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 6px;
            text-shadow: 0 2px 20px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>

<div class="login-left">
    <div class="form-content">
        <h2>Login</h2>
        <p class="subtitle">Don't have an account? <a href="<?= htmlspecialchars($register_url) ?>">Sign Up</a></p>

        <?php if (!empty($message)): ?>
            <p class="message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <input type="text" name="login_input" placeholder="Email or Username"
                       value="<?php echo htmlspecialchars($_POST['login_input'] ?? '') ?>" required>
            </div>

            <div class="form-group pass-wrap">
                <input type="password" name="password" id="passwordField" placeholder="Password" required>
                <button type="button" class="eye-btn" onclick="togglePassword()" title="Show/hide password">
                    <svg id="eyeOpen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg id="eyeClosed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none">
                        <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/>
                        <path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/>
                        <line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                </button>
            </div>

            <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
            <button type="submit" class="btn-login">Login</button>
        </form>
    </div>
</div>

<div class="login-right">
    <p class="tagline">Experience the ultimate</p>
    <p class="big-title">NEPAL</p>
</div>

<script>
function togglePassword() {
    const field     = document.getElementById('passwordField');
    const eyeOpen   = document.getElementById('eyeOpen');
    const eyeClosed = document.getElementById('eyeClosed');

    if (field.type === 'password') {
        field.type      = 'text';
        eyeOpen.style.display   = 'none';
        eyeClosed.style.display = 'block';
    } else {
        field.type      = 'password';
        eyeOpen.style.display   = 'block';
        eyeClosed.style.display = 'none';
    }
}
</script>

</body>
</html>