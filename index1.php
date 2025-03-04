<?php
include('connect.php');
session_start();
require 'vendor/autoload.php'; // Ensure PHPMailer is loaded

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Retrieve user data from the database
    $q = $db->prepare("SELECT * FROM user WHERE username = :username");
    $q->bindValue(':username', $username);
    $q->execute();
    
    if ($q->rowCount() > 0) {
        $user = $q->fetch(PDO::FETCH_ASSOC);
        // Verify the password (you should ideally use password_hash for storing and verifying passwords)
        if ($password === $user['password']) {
            // Set session variables
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email']; // Assuming you have an email column in your database
            
            // Check if the user has any blood requests
            $requestQuery = $db->prepare("SELECT * FROM blood_request WHERE donor_id = :username");
            $requestQuery->bindValue(':username', $user['username']);
            $requestQuery->execute();

            // Send alert email
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'guruda7777@gmail.com'; // Your Gmail address
                
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('guruda7777@gmail.com', 'KARE Blood Bank Team');
                $mail->addAddress($user['email']); // Send to the user's email

                // Content
                $mail->isHTML(false);
                $mail->Subject = "Login Alert";
                $mail->Body = "Dear " . $user['username'] . ",\n\nYou have successfully logged into the KARE Blood Bank Management System.\n\nThank you for using our service!";

                $mail->send();
                
                // Redirect based on blood request presence
                if ($requestQuery->rowCount() > 0) {
                    header('Location: index.php'); // User has blood requests
                } else {
                    header('Location: indexR.php'); // User does not have blood requests
                }
                exit();
            } catch (Exception $e) {
                echo "<script>alert('Email could not be sent. Mailer Error: {$mail->ErrorInfo}')</script>";
            }
        } else {
            echo "<script>alert('Incorrect password. Please try again.')</script>";
        }
    } else {
        echo "<script>alert('Username does not exist. Please sign up.')</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="animations.css">
</head>
<body>
    <div id ="blood-drop" style="left: 40%; animation-delay: 0.5s;">🩸</div>
    <div id="blood-drop" style="left: 53%; animation-delay: 1s;">🩸</div>
    <div id ="blood-drop" style="left: 5%; animation-delay: 0.5s;">🩸</div>
    <div id="blood-drop" style="left: 90%; animation-delay: 1s;">🩸</div>
    <div id="blood-drop" style="left: 99%; animation-delay: 1.5s;">🩸</div>

    <!-- Blood drop animation -->
    <div id="full">
        <div id="inner_full">
            <div id="header"><h1>KARE Blood Bank Management System</h1></div>
            <div id="body">
                <br>
                <form id="loginForm" action="" method="post" onsubmit="return validateForm()">
                    <table align="left">
                        <tr>
                            <td width=" 150px" height="70px"><h3>Enter Username</h3></td>
                            <td width="100px" height="70px"><input type="text" name="username" placeholder="Enter Username" required></td>
                        </tr>
                        <tr>
                            <td width="150px" height="70px"><h3>Enter Password</h3></td>
                            <td width="200px" height="70px"><input type="password" name="password" placeholder="Enter Password" required></td>
                        </tr>
                        <tr>
                            <td><input type="submit" name="login" value="Login"></td>
                        </tr>
                    </table>
                </form>
                <br><br>
                <h3><a href="forgot_password.php">Forgot Password?</a></h3> <!-- Added link here -->
                <h3>Don't have an account?</h3>
                <a href="signup.php"><button>Sign Up</button></a>
            </div>
        </div>
    </div>

    <script>
        function validateForm() {
            const username = document.querySelector('input[name="username"]').value;
            const password = document.querySelector('input[name="password"]').value;

            if (username.trim() === '' || password.trim() === '') {
                alert('Please fill out all fields.');
                return false; // Prevent form submission
            }

            return true; // Allow form submission
        }
    </script>
</body>
</html>
