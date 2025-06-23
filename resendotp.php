<?php
require 'connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        echo json_encode(['success' => false, 'message' => 'Invalid email']);
        exit();
    }
    
    // Generate new OTP
    $otp = rand(100000, 999999);
    
    try {
        // Update or insert new OTP
        $stmt = $pdo->prepare("REPLACE INTO otp_verification (email, otp) VALUES (?, ?)");
        $stmt->execute([$email, $otp]);
        
        // Send email
        if (sendVerificationEmail($email, $otp)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send email']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>