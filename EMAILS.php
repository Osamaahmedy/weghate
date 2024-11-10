<?php
// EMAILS.php
require 'vendor/autoload.php';
require 'DB.php'; // Make sure the database connection is correct

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['email'])) {
    // Get the email address from POST
    $email = mysqli_real_escape_string($conn, $_POST['email']);
  
    // Check if the email already exists in the database
    $checkQuery = "SELECT * FROM `emails` WHERE emails = '$email'";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if (mysqli_num_rows($checkResult) > 0) {
        // If email already exists
        echo "Account already exists.";
    } else {
        // If email is not found, insert it into the database
        $insertQuery = "INSERT INTO `emails` (emails) VALUES ('$email')";
        $insert = mysqli_query($conn, $insertQuery);

        if ($insert) {
            // Set up PHPMailer to send the email
            $mail = new PHPMailer(true);

            try {
                // SMTP configuration
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'your_email@gmail.com'; // Your email
                $mail->Password = 'your_password'; // Your email password (prefer using environment variables for production)
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                // Send email to the new user
                $mail->setFrom('your_email@gmail.com', 'Osama Ahmed');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Welcome to Our Site';
                $mail->Body = "Hi $email,<br>Welcome to our website!<br>";

                $mail->send();

                // Send notification to the site owner
                $mail->clearAddresses();
                $mail->addAddress('your_email@gmail.com');
                $mail->Subject = 'New Email Registered';
                $mail->Body = "A new email has registered: $email<br>";

                $mail->send();

                // Respond with success message
                echo 'Registration successful and emails sent!';
            } catch (Exception $e) {
                echo "Registration successful, but email sending failed. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            echo 'Registration failed.';
        }
    }
}

mysqli_close($conn);
?>
