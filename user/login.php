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

        /* LEFT SIDE */
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
            font-weight: 700;
            color: #1a5c2a;
            margin-bottom: 0.4rem;
        }

        .form-content .subtitle {
            font-size: 14px;
            color: #ddd;
            margin-bottom: 2rem;
        }

        .form-content .subtitle a {
            color: #3db85a;
            font-weight: 600;
            text-decoration: none;
        }

        .form-group {
            margin-bottom: 1rem;
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

        .forgot-password {
            display: block;
            text-align: right;
            font-size: 13px;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            margin-bottom: 1.5rem;
            margin-top: -0.5rem;
        }

        .forgot-password:hover {
            color: #fff;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: #2e7d32;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: 0.5px;
        }

        .btn-login:hover {
            background: #245c27;
        }

        .message {
            color: #ff6b6b;
            font-size: 13px;
            margin-bottom: 1rem;
            background: rgba(255,0,0,0.1);
            padding: 8px 12px;
            border-radius: 4px;
        }

        /* RIGHT SIDE */
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

<!-- LEFT: Form -->
<div class="login-left">
    <div class="form-content">
        <h2>Login</h2>
        <p class="subtitle">Don't have an account? <a href="/Nepal-Travel/user/register.php">Sign Up</a></p>

        <?php if (isset($_GET['error'])): ?>
            <p class="message">Invalid email or password</p>
        <?php endif; ?>

        <form action="login_process.php" method="POST">
            <div class="form-group">
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
            <button type="submit" class="btn-login">Login</button>
        </form>
    </div>
</div>

<!-- RIGHT: Image with text -->
<div class="login-right">
   
</div>

</body>
</html>