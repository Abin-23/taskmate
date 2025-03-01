<?php
session_start(); 
$error_message = '';
$conn = new mysqli('localhost', 'root', '', 'telecare+');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

function generateVerificationCode($length = 6) {
    return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

function sendVerificationEmail($recipientEmail, $verificationCode) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'telecareplus@gmail.com';
        $mail->Password   = 'skjq ckyi vyqp hipt';
    
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('telecareplus@gmail.com', 'telecare+');
        $mail->addAddress($recipientEmail);
        $mail->Subject = 'Your Verification Code';
        $mail->Body    = "Your verification code is: $verificationCode\n\nThis code will expire in 10 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

if (isset($_SESSION['email'])) {
    $verificationCode = generateVerificationCode();
    $_SESSION['verification_code'] = $verificationCode;

    if (sendVerificationEmail($_SESSION['email'], $verificationCode)) {
        echo "";
    } else {
        $error_message = "Failed to send verification code.";
    }
} elseif (isset($_POST['verify'])) {
    $enteredOTP = $_POST['otp1'] . $_POST['otp2'] . $_POST['otp3'] . $_POST['otp4'] . $_POST['otp5'] . $_POST['otp6'];

    if (!isset($_SESSION['verification_code'])) {
        $error_message = "Verification code not set. Please request a new code.";
    } else {
        if ($enteredOTP == $_SESSION['verification_code']) {
            $fullname = $conn->real_escape_string($_SESSION['fullname']);
            $email = $conn->real_escape_string($_SESSION['email']);
            $password = password_hash($_SESSION['password'], PASSWORD_DEFAULT); // Hash password
            $address = $conn->real_escape_string($_SESSION['address']);
            $place = $conn->real_escape_string($_SESSION['place']);
            $role = $conn->real_escape_string($_SESSION['role']);

            $sql = "INSERT INTO signup (full_name, email, password, permanent_address, place, role) 
                    VALUES ('$fullname', '$email', '$password', '$address', '$place', '$role')";

            if ($conn->query($sql) === TRUE) {
                header('Location: login.php');
                exit();
            } else {
                echo "Error: " . $conn->error;
            }
        } else {
            $error_message = "Incorrect OTP.";
        }
    }
}
?>
