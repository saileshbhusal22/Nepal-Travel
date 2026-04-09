<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/mail_config.php';

function sendVerificationEmail($email, $fullname, $token)
{

    $mail = new PHPMailer(true);

    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port = SMTP_PORT;

        // Sender
        $mail->setFrom(SMTP_USER, APP_NAME);

        // Receiver
        $mail->addAddress($email, $fullname);

        // Verification link
        $verify_link = BASE_URL . "/verify_email.php?token=" . $token;

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email';

        $mail->Body = "
            <h2>Welcome to " . APP_NAME . " 🌄</h2>
            <p>Hello $fullname,</p>
            <p>Please verify your email:</p>

            <a href='$verify_link' style='
                display:inline-block;
                padding:10px 20px;
                background:#0e4d1d;
                color:white;
                text-decoration:none;
                border-radius:5px;
            '>Verify Email</a>

            <p>Or copy this link:</p>
            <p>$verify_link</p>
        ";

        $mail->send();
        return true;

    }
    catch (Exception $e) {
        return false;
    }
}