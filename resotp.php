<?php
require 'connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit();
    }
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = 'doctor'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'No account found with this email']);
        exit();
    }
    
    // Generate new OTP
    $otp = rand(100000, 999999);
    
    // Update OTP in database
    $stmt = $pdo->prepare("UPDATE users SET otp_code = ? WHERE id = ?");
    $stmt->execute([$otp, $user['id']]);
    
    // Send email
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'afiquehossain84@gmail.com';
        $mail->Password = 'jhcb qxfc tyhe tocq';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        $mail->setFrom('afiquehossain84@gmail.com', 'E Care Hub');
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        $mail->Subject = 'New Verification Code';
        $mail->Body = "
            <h2>New Verification Code</h2>
            <p>Your new OTP code is: <strong>$otp</strong></p>
            <p>This code will expire in 10 minutes.</p>
        ";
        $mail->AltBody = "Your new OTP code is: $otp\n\nThis code will expire in 10 minutes.";
        
        $mail->send();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        echo json_encode(['success' => false, 'message' => 'Failed to send email']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>