<?php
session_start();
require 'db_connection.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// CSRF protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Security token mismatch. Please try again.");
}

if (isset($_SESSION['pending_verification_email'], $_SESSION['pending_verification_token'])) {
    $email = $_SESSION['pending_verification_email'];
    $verification_token = $_SESSION['pending_verification_token'];

    // Update expiration time (give another 24 hours)
    $token_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $conn->query("UPDATE users SET token_expires = '$token_expires' WHERE email = '$email'");

    // Resend email
    $verification_link = "http://unimaidresources.com.ng/verify_email.php?token=$verification_token";
    $email_subject = "Verify Your Email for UNIMAID Resources";
    $email_body = "
        <h2>Welcome to UNIMAID Resources!</h2>
        <p>Please click the link below to verify your email address:</p>
        <p><a href='$verification_link'>Verify Email</a></p>
        <p>If you didn't create an account, please ignore this email.</p>
    ";

    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.example.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'adyemsgodlove@gmail.com';
        $mail->Password   = 'doid zchb bagz arfz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('adyemsgodlove@gmail.com', 'UNIMAID Resources');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $email_subject;
        $mail->Body    = $email_body;

        if ($mail->send()) {
            header("Location: index.php?resend=success");
        } else {
            header("Location: index.php?resend=failed");
        }
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        header("Location: index.php?resend=failed");
    }
    exit();
}

header("Location: index.php");
exit();