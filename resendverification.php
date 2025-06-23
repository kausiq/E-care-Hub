<?php
session_start();
require 'connection.php';

if (!isset($_SESSION['verifyemail'])) {
    header("Location: ambulanceregister.php");
    exit();
}

$email = $_SESSION['verifyemail'];

// Generate new OTP
$otp = rand(100000, 999999);
$expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Update database with new OTP
$stmt = $pdo->prepare("UPDATE ambulancedrivers SET verificationcode = ?, codeexpiry = ? WHERE email = ?");
if ($stmt->execute([$otp, $expiry, $email])) {
    // Resend email
    if (sendVerificationEmail($email, $otp)) {
        $_SESSION['resendsuccess'] = "Verification code resent successfully!";
    } else {
        $_SESSION['resenderror'] = "Failed to resend verification email. Please try again.";
    }
} else {
    $_SESSION['resenderror'] = "Failed to generate new verification code. Please try again.";
}

header("Location: verifyambulance.php");
exit();
?>