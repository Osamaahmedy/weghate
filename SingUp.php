<?php
//SingUp.php
require 'vendor/autoload.php';
require 'DB.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// تحميل متغيرات البيئة
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// استخدم مفتاح التشفير من ملف .env
$encryption_key = $_ENV['ENCRYPTION_KEY'];

function encryptData($data, $key) {
    $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

if (isset($_POST['firstName'], $_POST['LastName'], $_POST['PassWord'], $_POST['PhonNumber'], $_POST['email'], $_POST['Age'])) {
    $firstName = mysqli_real_escape_string($conn, $_POST['firstName']);
    $lastName = mysqli_real_escape_string($conn, $_POST['LastName']);
    $password = mysqli_real_escape_string($conn, $_POST['PassWord']);
    $phoneNumber = mysqli_real_escape_string($conn, $_POST['PhonNumber']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $age = (int) $_POST['Age'];

    // Validate age
    if ($age < 18) {
        echo 'Age is below the required minimum.';
        exit;
    }

    $checkQuery = "SELECT * FROM `users` WHERE email = '$email'";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if (mysqli_num_rows($checkResult) > 0) {
        echo "Account already exists.";
    } else {
        // Generate unique identifier
        $uniqueId = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)) . random_int(1000000, 9999999);

        // تشفير كلمة المرور و uniqueId
        $encryptedPassword = encryptData($password, $encryption_key);
        $encryptedUniqueId = encryptData($uniqueId, $encryption_key);

        // Insert data into the database
        $insertQuery = "INSERT INTO `users` (firstName, lastName, password, phoneNumber, email, age, uniqueId) 
                        VALUES ('$firstName', '$lastName', '$encryptedPassword', '$phoneNumber', '$email', '$age', '$encryptedUniqueId')";
        $insert = mysqli_query($conn, $insertQuery);

        if ($insert) {
            $mail = new PHPMailer(true);

            try {
                // SMTP Configuration
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'your_email@gmail.com'; // Your email
                $mail->Password = 'your_password'; // Your email password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                // Send email to the new user with original data
                $mail->setFrom('your_email@gmail.com', 'Osama Ahmed');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Welcome to Our Site';
                $mail->Body = "Hi $firstName,<br>Welcome to our website!<br>Your unique ID is $uniqueId.<br>Password: $password<br>Phone: $phoneNumber<br>Age: $age";

                $mail->send();

                // Send notification to the site owner
                $mail->clearAddresses();
                $mail->addAddress('your_email@gmail.com');
                $mail->Subject = 'New User Registered';
                $mail->Body = "A new user has registered:<br>Name: $firstName $lastName<br>Email: $email<br>Unique ID: $uniqueId";

                $mail->send();

                echo 'Registration successful and emails sent!';
            } catch (Exception $e) {
                echo "Registration successful, but email sending failed. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            echo 'Registration failed';
        }
    }
}

mysqli_close($conn);
?>