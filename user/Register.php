<?php
session_start();

require __DIR__ . '/../vendor/autoload.php';
include 'mail.php';
require_once __DIR__ . '/../config/db.php';

$message = "";
$message_type = "";

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? "error";

    unset($_SESSION['message'], $_SESSION['message_type']);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        $_SESSION['message'] = "Passwords do not match";
        $_SESSION['message_type'] = "error";
        header("Location: Register.php");
        exit;
    }
    if (!isset($_POST['terms'])) {
        $_SESSION['message'] = "You must agree to Terms & Privacy Policy";
        $_SESSION['message_type'] = "error";
        header("Location: Register.php");
        exit;
    }

    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $_SESSION['message'] = "Phone number must be exactly 10 digits and numeric only";
        $_SESSION['message_type'] = "error";
        header("Location: Register.php");
        exit;
    }

    $check = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ? OR phone = ?");
    $check->bind_param("sss", $email, $username, $phone);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $_SESSION['message'] = "Email, Username or Phone already exists";
        $_SESSION['message_type'] = "error";
        $check->close();
        $conn->close();
        header("Location: Register.php");
        exit;
    }
    $check->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $email_token = bin2hex(random_bytes(32));

    $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, phone, password, email_token) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $fullname, $username, $email, $phone, $hashed_password, $email_token);

    if ($stmt->execute()) {
        if (sendVerificationEmail($email, $fullname, $email_token)) {
            $_SESSION['message'] = "Registration successful! Please check your inbox and verify your email";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Registered successfully, but email sending failed.";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Registration failed. Please try again.";
        $_SESSION['message_type'] = "error";
    }

    $stmt->close();
    $conn->close();

    header("Location: Register.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nepal Travel - Register</title>
    <link rel="stylesheet" href="Register.css">
</head>

<body>

<div class="container">
    <div class="left-panel">
        <div class="form-box">

            <h1>Sign Up</h1>
            <p class="login-text">Already have an account? <a href="login.php">Log In</a></p>

            <div class="social-buttons">
                <div id="g_id_onload"
                    data-client_id="1045079519630-reec2mcusabp0hg13bufjrmnpvm2a0jb.apps.googleusercontent.com"
                    data-context="signin"
                    data-ux_mode="popup"
                    data-callback="handleGoogleResponse"
                    data-auto_prompt="false">
                </div>

                <div id="googleButtonContainer"></div>

                <button type="button" onclick="facebookLogin()" class="social-btn facebook">
                    Continue with Facebook
                </button>
            </div>

            <div class="divider">or</div>

            <?php if (!empty($message)) : ?>
                <p style="margin-bottom: 10px; color: <?php echo ($message_type === 'success') ? 'green' : 'red'; ?>;">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            <?php endif; ?>

            <form action="" method="POST" novalidate>
                <input type="text" name="fullname" placeholder="Full Name" required>

                <input type="text" name="username" id="username" placeholder="Username" required>
                <small id="usernameMsg"></small>

                <input type="email" name="email" id="email" placeholder="Email" required>
                <small id="emailMsg"></small>

                <input type="text" name="phone" id="phone" placeholder="Phone Number" maxlength="10" required>
                <small id="phoneMsg"></small>

                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>

                <label class="terms">
                <label class="terms" style="display:flex; align-items:center; gap:6px; flex-wrap:wrap; font-size:13px;">
    <input type="checkbox" name="terms" style="flex-shrink:0;">
    I agree to the
    <a href="terms.php" target="_blank">Terms &amp; Conditions</a>
    and
    <a href="privacy.php" target="_blank">Privacy Policy</a>
</label>

                <button type="submit" id="createBtn" class="create-btn">Create Account</button>

                <div id="spinnerBox" class="spinner-box" style="display: none;">
                    <div class="spinner"></div>
                </div>
            </form>

        </div>
    </div>

    <div class="overlay-text">
        <span class="small-text">LET'S EXPLORE</span>
        <span class="big-text">NEPAL</span>
    </div>
</div>

<!-- Set Password Modal -->
<div id="setPasswordModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; padding:2rem; border-radius:12px; width:90%; max-width:400px; box-shadow:0 8px 32px rgba(0,0,0,0.2); margin:auto;">
        <h2 style="margin:0 0 0.5rem;">Set a Password</h2>
        <p style="color:#666; margin:0 0 1.5rem; font-size:0.9rem;">
            You signed in with Google. Optionally set a password so you can also log in with your email later.
        </p>

        <input id="spPassword" type="password" placeholder="New password (min 8 chars)"
            style="width:100%; padding:0.75rem; border:1px solid #ddd; border-radius:8px; margin-bottom:0.75rem; font-size:1rem; box-sizing:border-box;" />

        <input id="spConfirm" type="password" placeholder="Confirm password"
            style="width:100%; padding:0.75rem; border:1px solid #ddd; border-radius:8px; margin-bottom:0.75rem; font-size:1rem; box-sizing:border-box;" />

        <p id="spError" style="color:red; font-size:0.85rem; margin:0 0 0.75rem; display:none;"></p>

        <button onclick="submitSetPassword()"
            style="width:100%; padding:0.75rem; background:#4285F4; color:#fff; border:none; border-radius:8px; font-size:1rem; cursor:pointer;">
            Save Password
        </button>

        <button onclick="skipSetPassword()"
            style="width:100%; padding:0.75rem; background:none; border:none; color:#888; font-size:0.85rem; cursor:pointer; margin-top:0.5rem;">
            Skip for now
        </button>
    </div>
</div>

<script src="../assets/js/register.js"></script>
<script src="https://accounts.google.com/gsi/client?hl=en" async defer></script>

<script>
    // Google response handler
    function handleGoogleResponse(response) {
        const idToken = response.credential;

        const xhr = new XMLHttpRequest();
        // ✅ absolute path so it always resolves correctly
        xhr.open("POST", "/Nepal-Travel/user/google_login.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onload = function () {
            const result = xhr.responseText.trim();

            if (xhr.status === 200 && result === "ok") {
                window.location.href = "/Nepal-Travel/Public/index.php";
            } else if (xhr.status === 200 && result === "set_password") {
                showSetPasswordModal();
            } else {
                alert("Google login failed: " + xhr.responseText);
            }
        };

        xhr.send("id_token=" + encodeURIComponent(idToken));
    }

    // Render Google button
    window.onload = function () {
        const googleContainer = document.getElementById("googleButtonContainer");

        google.accounts.id.initialize({
            client_id: "1045079519630-reec2mcusabp0hg13bufjrmnpvm2a0jb.apps.googleusercontent.com",
            callback: handleGoogleResponse
        });

        google.accounts.id.renderButton(
            googleContainer,
            {
                type: "standard",
                theme: "outline",
                size: "large",
                text: "continue_with",
                shape: "rectangular",
                logo_alignment: "left",
                width: 300,
                locale: "en"
            }
        );
    };

    // Show modal
    function showSetPasswordModal() {
        const modal = document.getElementById('setPasswordModal');
        modal.style.display = 'flex';
    }

    // Skip — go to dashboard without setting password
    function skipSetPassword() {
        document.getElementById('setPasswordModal').style.display = 'none';
        window.location.href = "/Nepal-Travel/Public/index.php";
    }

    // Submit new password
    async function submitSetPassword() {
        const password = document.getElementById('spPassword').value;
        const confirm  = document.getElementById('spConfirm').value;
        const errorEl  = document.getElementById('spError');
        errorEl.style.display = 'none';

        if (password.length < 8) {
            errorEl.textContent = 'Password must be at least 8 characters.';
            errorEl.style.display = 'block';
            return;
        }

        if (password !== confirm) {
            errorEl.textContent = 'Passwords do not match.';
            errorEl.style.display = 'block';
            return;
        }

        const res = await fetch("/Nepal-Travel/user/set-password.php", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ password, confirm })
        });

        const text = await res.text();

        if (res.ok) {
            document.getElementById('setPasswordModal').style.display = 'none';
            window.location.href = "/Nepal-Travel/Public/index.php";
        } else {
            errorEl.textContent = text;
            errorEl.style.display = 'block';
        }
    }
</script>

<!-- Facebook -->
<div id="fb-root"></div>
<script async defer crossorigin="anonymous"
    src="https://connect.facebook.net/en_US/sdk.js"></script>

<script>
    window.fbAsyncInit = function () {
        FB.init({
            appId: '863151126739328',
            cookie: true,
            xfbml: false,
            version: 'v23.0'
        });
    };

    function facebookLogin() {
        FB.login(function(response) {
            if (response.authResponse) {
                const accessToken = response.authResponse.accessToken;

                const xhr = new XMLHttpRequest();
                xhr.open("POST", "/Nepal-Travel/user/facebook_callback.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                xhr.onload = function () {
                    if (xhr.status === 200 && xhr.responseText.trim() === "ok") {
                        window.location.href = "/Nepal-Travel/Public/index.php";
                    } else {
                        alert("Facebook login failed: " + xhr.responseText);
                    }
                };

                xhr.send("access_token=" + encodeURIComponent(accessToken));
            } else {
                alert("Facebook login cancelled or failed");
            }
        }, {scope: 'public_profile'});
    }
</script>

</body>
</html>