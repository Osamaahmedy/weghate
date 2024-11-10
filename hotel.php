<?php

require 'vendor/autoload.php'; // تحميل Composer autoload
require 'DB.php';
require('fpdf186/fpdf.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = mysqli_real_escape_string($conn, $_POST['firstName']);
    $lastName = mysqli_real_escape_string($conn, $_POST['lastName']);
    $night = mysqli_real_escape_string($conn, $_POST['night']);
    $phoneNumber = mysqli_real_escape_string($conn, $_POST['phoneNumber']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $age = (int) $_POST['age'];
    $carID = mysqli_real_escape_string($conn, $_POST['carID']);
    $PersonalID = mysqli_real_escape_string($conn, $_POST['PersonalID']);
    $hotelName = mysqli_real_escape_string($conn, $_POST['hotelName']);
    $vip = mysqli_real_escape_string($conn, $_POST['vip']);
    $room = mysqli_real_escape_string($conn, $_POST['room']);
    $apartment = mysqli_real_escape_string($conn, $_POST['apartment']);
    $suite = mysqli_real_escape_string($conn, $_POST['suite']);

    // Validate age
    if ($age < 18) {
        echo 'Age is below the required minimum.';
        exit;
    }
    
    // Generate unique identifier
    $uniqueId = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)) . random_int(100000, 999999);
    $RN =  random_int(100000, 999999);
    // Insert data into the database
    $insertQuery = "INSERT INTO `hotel` (firstName, lastName, night, phoneNumber, email, age, uniqueId, PersonalID, carID, hotelName, vip, room, apartment, suite) 
                    VALUES ('$firstName', '$lastName', '$night', '$phoneNumber', '$email', '$age', '$uniqueId', '$PersonalID', '$carID', '$hotelName', '$vip', '$room', '$apartment', '$suite')";
    $insert = mysqli_query($conn, $insertQuery);
    
    if ($insert) {
        // إنشاء كود QR
        $qrData = "Name: $firstName $lastName\nPhone: $phoneNumber\nEmail: $email\nUnique ID: $uniqueId\nAge: $age\ncarID: $carID\nPersonalID: $PersonalID\nhotelName: $hotelName\nvip: $vip\nroom :$room\napartment :$apartment\nsuite :$suite";
        $qrCode = new QrCode($qrData);
        $qrCode->setSize(150);
        $writer = new PngWriter();
        $qrFile = 'qrcode.png';
        $writer->write($qrCode)->saveToFile($qrFile);


        

        
        // إعداد بيانات الجدول
        $data = array(
            "Name" => "$firstName $lastName",
            "Confirmation ID" => $uniqueId,
            "Phone Number" => $phoneNumber,
            "Email Address" => $email,
            "Age" => $age,
            "Nights" => $night,
            "Hotel Name" => $hotelName,
            "VIP" => $vip,
            "Room Number" => $room,
            "Apartment" => $apartment,
            "Suite" => $suite
        );
        
        $pdf = new FPDF();
        $pdf->AddPage();
        
        // إضافة الشعار
        $pdf->Image('C:\xampp\htdocs\weghate\1731172903205.jpg', 86, 5, 40  );
        $pdf->Ln(20);
        // إضافة عنوان
        $pdf->SetFont('Arial', 'B', 20);
        $pdf->Cell(0, 50, 'Hotel Booking', 0, 1, 'C');
        $pdf->Ln(5);
        // اسم ورقم
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 0, "MR: $firstName $lastName", 0, 1, 'L');
        $pdf->Cell(0, 0, "Reg.NO : $RN", 0, 1, 'R');
        
        // إضافة مسافة قبل الجدول
        $pdf->Ln(20);
      
// Calculate the position to center the table
$cellWidth = 60;
$cellHeight = 10;
$totalWidth = $cellWidth * 2;
$cellPadding = 2;
$tableStartX = ($pdf->GetPageWidth() - $totalWidth) / 2;

// Set table colors
$pdf->SetFillColor(230, 230, 250); // Light purple background
$pdf->SetTextColor(0, 0, 0); // Black text color
$pdf->SetDrawColor(128, 0, 128); // Purple border color

// Display the table
foreach ($data as $key => $value) {
    $pdf->SetX($tableStartX);
    $pdf->Cell($cellWidth, $cellHeight, $key, 1, 0, 'C', true);
    $pdf->Cell($cellWidth, $cellHeight, $value, 1, 1, 'C', true);
}

        // Output the QR Code

$pdf->Cell(0, 50, 'QR Code:', 0, 1, 'C');
// الحصول على عرض وارتفاع الصفحة
$pageWidth = $pdf->GetPageWidth();
$pageHeight = $pdf->GetPageHeight();

// تحديد حجم الصورة (مثال: 30x30)
$imageWidth = 50;
$imageHeight = 50;

// حساب الإحداثيات المطلوبة (المنتصف)
$x = ($pageWidth - $imageWidth) / 2;
$y = ($pageHeight - $imageHeight) / 1;

// إدراج الصورة في المنتصف
$pdf->Image($qrFile, $x, $y, $imageWidth, $imageHeight);

        // إخراج ملف PDF
        $pdfFile = 'hotel_booking.pdf';
        $pdf->Output($pdfFile, 'F');
        // إرسال البريد الإلكتروني
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
            
            // إعدادات البريد الإلكتروني للعميل
            $mail->setFrom('your_email@gmail.com', 'Hotel Booking');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Hotel Booking Confirmation';
            $mail->Body = "Hi $firstName,<br>Your booking details are attached.";
            $mail->addAttachment($pdfFile);
            
            $mail->send();
            
            // إعدادات البريد الإلكتروني للمسؤول
            $mail->clearAddresses();
            $mail->addAddress('your_email@gmail.com');
            $mail->Subject = 'New Hotel Booking Request';
            $mail->Body = "A new booking has been made. Details are attached.";
            $mail->addAttachment($pdfFile);
            
            $mail->send();
            
            echo 'Registration successful and emails sent!';
        } catch (Exception $e) {
            echo "Registration successful, but email sending failed. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo 'Registration failed: ' . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>