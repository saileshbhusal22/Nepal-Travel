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
            $_SESSION['message'] = "Registration successful! Please verify your email before login.";
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
                    <input type="checkbox" name="terms">
                    I agree to Terms & Privacy Policy
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

<script src="assets/register.js"></script>
<script src="https://accounts.google.com/gsi/client?hl=en"  async defer></script>
<script>
  function handleGoogleResponse(response) {
    const idToken = response.credential;

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "google_login.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onload = function () {
      if (xhr.status === 200 && xhr.responseText.trim() === "ok") {
        window.location.href = "../index.php";
      } else {
        alert("Google login failed: " + xhr.responseText);
      }
    };

    xhr.send("id_token=" + encodeURIComponent(idToken));
  }

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
      width:300,
      locale: "en"
    }
  );
};
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
        xhr.open("POST", "facebook_callback.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onload = function () {
          if (xhr.status === 200 && xhr.responseText.trim() === "ok") {
            window.location.href = "../index.php";
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