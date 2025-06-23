<?php
require 'vendor/autoload.php';

$host = 'localhost';
$db   = 'ecarehub';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

function sendVerificationEmail($to, $otp) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'afiquehossain84@gmail.com'; // Your Gmail
        $mail->Password = 'jhcb qxfc tyhe tocq '; // Use App Password, not your Gmail password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        // Enable debugging (remove in production)
        // $mail->SMTPDebug = 2; // 0 for production, 2 for debugging
        // $mail->Debugoutput = function($str, $level) {
        //     error_log("PHPMailer: $str");
        // };
        
        // Recipients
        $mail->setFrom('afiquehossain84@gmail.com', 'E Care Hub');
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email Address';
        $mail->Body    = "
            <h2>E Care Hub Email Verification</h2>
            <p>Your OTP code is: <strong>$otp</strong></p>
            <p>This code will expire in 10 minutes.</p>
            <p>If you didn't request this, please ignore this email.</p>
        ";
        $mail->AltBody = "Your OTP code is: $otp\n\nThis code will expire in 10 minutes.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>