<?php
include('connect.php');
require 'vendor/autoload.php'; // Ensure PHPMailer is loaded

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

if (isset($_POST['send_otp'])) {
    $email = $_POST['email'];

    // Check if the email exists in the database
    $q = $db->prepare("SELECT * FROM user WHERE email = :email");
    $q->bindValue(':email', $email);
    $q->execute();

    if ($q->rowCount() > 0) {
        $user = $q->fetch(PDO::FETCH_ASSOC);
        $otp = rand(100000, 999999); // Generate a random 6-digit OTP

        // Store OTP in session for verification
        $_SESSION['otp'] = $otp;
        $_SESSION['email'] = $email;

        // Send OTP via email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'guruda7777@gmail.com'; // Your Gmail address
           
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('guruda7777@gmail.com', 'KARE Blood Bank');
            $mail->addAddress($email);

            $mail->Subject = "Your OTP Code";
            $mail->Body = "Your OTP code is: $otp";

            $mail->send();

            // Redirect to OTP verification page
            header('Location: verify_otp.php');
            exit();
        } catch (Exception $e) {
            echo "<script>alert('Email could not be sent. Mailer Error: {$mail->ErrorInfo}')</script>";
        }
    } else {
        echo "<script>alert('Email not found. Please try again.')</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <h2>Forgot Password</h2>
    <form method="post" action="">
        <input type="email" name="email" placeholder="Enter your email" required>
        <input type="submit" name="send_otp" value="Send OTP">
    </form>
</body>
</html>
