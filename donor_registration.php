<?php
require 'vendor/autoload.php';
require 'Blockchain.php'; // Include the blockchain class

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Twilio\Rest\Client;

// Replace these with your actual credentials


session_start();

// Connect to database
require 'connect.php'; // Ensure you have a connect.php to connect to your database

// Initialize the blockchain
$blockchain = new Blockchain($db); // Pass the database connection

// Check if form is submitted
if (isset($_POST['sub'])) {
    $donor_id = isset($_POST['donor_id']) ? htmlspecialchars(trim($_POST['donor_id'])) : '';
    $email = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email'])) : '';
    $phone = isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : '';
    
    if (!preg_match('/^\+/', $phone)) {
        $phone = '+91' . ltrim($phone, '0'); // Add country code if not present
    }
    
    $city = isset($_POST['city']) ? htmlspecialchars(trim($_POST['city'])) : '';
    $blood_type = isset($_POST['blood_type']) ? htmlspecialchars(trim($_POST['blood_type'])) : '';

    // Check for existing email
    $check_email = $db->prepare("SELECT * FROM pending_donors WHERE email = :email");
    $check_email->bindValue(':email', $email);
    $check_email->execute();

    if ($check_email->rowCount() > 0) {
        echo "<script>alert('Email already exists. Please try a different email ID.');</script>";
    } else {
        // Insert new donor registration into a pending table
        $q = $db->prepare("INSERT INTO pending_donors (donor_id, email, phone, city, blood_type) VALUES (:donor_id, :email, :phone, :city, :blood_type)");
        $q->bindValue(':donor_id', $donor_id);
        $q->bindValue(':email', $email);
        $q->bindValue(':phone', $phone);
        $q->bindValue(':city', $city);
        $q->bindValue(':blood_type', $blood_type);

        if ($q->execute()) {
            echo "<script>alert('Donor registration successful! Waiting for admin approval.');</script>";

            // Prepare donor data for the blockchain
            $donorData = [
                'donor_id' => $donor_id,
                'email' => $email,
                'phone' => $phone,
                'city' => $city,
                'blood_type' => $blood_type
            ];

            // Add the donor data as a new block in the blockchain
            $blockchain->addBlock($donorData);

            // Send confirmation email using PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $mail_username;
                $mail->Password = $mail_password;
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom($mail_username, 'KARE Blood Bank Team');
                $mail->addAddress($email, $donor_id);

                $mail->isHTML(false);
                $mail->Subject = 'Donor Registration Confirmation';
                $mail->Body = "Dear $donor_id,\n\nThank you for registering as a blood donor. Your details have been successfully saved.\n\nBest regards,\nBlood Donation Team";

                $mail->send();
            } catch (Exception $e) {
                echo "<script>alert('Failed to send confirmation email. Mailer Error: {$mail->ErrorInfo}');</script>";
            }

            // Send SMS using Twilio
            $client = new Client($twilio_sid, $twilio_token);
            try {
                $message = $client->messages->create(
                    $phone,
                    [
                        'from' => $twilio_phone_number,
                        'body' => "Dear $donor_id, thank you for registering as a blood donor. Your details have been saved. This is a confirmation message from KARE Blood Bank Team."
                    ]
                );
            } catch (Twilio\Exceptions\RestException $e) {
                if ($e->getCode() == 400) {
                    echo "<script>alert('Failed to send confirmation SMS. Please enable international SMS capabilities or add the recipient\'s country to your allowed list.');</script>";
                } else {
                    echo "<script>alert('Failed to send confirmation SMS. Twilio Error: {$e->getMessage()}');</script>";
                }
            }
        } else {
            echo "<script>alert('Donor registration failed. Please try again.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Donor Registration</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            position: relative;
            background-image: url('images/Donor11.jpg');
            background-size: cover;
            background-position: left;
            background-repeat: no-repeat;
            color: white;
        }
        .back-button {
            display: inline-block;
            padding: 10px;
            margin: 10px 0;
            background-color: #ff0000;
            color: white;
            text-decoration: none;
            border-radius: 100px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: #0056b3;
        }
        .top-left-buttons {
            position: absolute;
            top: 10px;
            left: 10px;
            display: none;
            z-index: 1000;
        }
        .top-left-buttons a {
            display: block;
            margin: 5px 0;
            padding: 10px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .top-left-buttons a:hover {
            background-color: #0056b3;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#blood_type').change(function() {
                var bloodType = $(this).val();
                $.ajax({
                    type: 'POST',
                    url: 'fetch_cities.php',
                    data: { blood_type: bloodType },
                    success: function(data) {
                        $('#city').html(data);
                    }
                });
            });

            $(document).mousemove(function(e) {
                if (e.pageX < 50 && e.pageY < 50) {
                    $('.top-left-buttons').stop(true, true).slideDown(300);
                } else {
                    setTimeout(function() {
                        if (!$('.top-left-buttons:hover').length) {
                            $('.top-left-buttons').stop(true, true).slideUp(300);
                        }
                    }, 100);
                }
            });
        });
    </script>
</head>
<body>
    <div class="top-left-buttons">
        <a href="javascript:history.back()" class="back-button">← Back</a>
        
         <a href="index1.php" class="home-button">🏠 Home</a>
         <a href="index1.php" class="home-button">LogOut</a>
    </div>
    <h1>Blood Donor Registration</h1>
    <form method="post">
        <label for="name">Name:</label>
        <input type="text" id="name" name="donor_id" required><br><br>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br><br>
        <label for="phone">Phone Number:</label>
        <input type="tel" id="phone" name="phone" required placeholder="Enter your number without country code"><br><br>
        <label for="city">City:</label>
        <input type="text" id="city" name="city" required><br><br>
        <label for="blood_type">Blood Type:</label>
        <select id="blood_type" name="blood_type" required>
            <option value="">Select Blood Type</option>
            <option value="A+">A +</option>
            <option value="A-">A-</option>
            <option value="B+">B+</option>
            <option value="B-">B-</option>
            <option value="AB+">AB+</option>
            <option value="AB-">AB-</option>
            <option value="O+">O+</option>
            <option value="O-">O-</option>
        </select><br><br>
        <input type="submit" name="sub" value="Register">
    </form>
</body>
</html>
