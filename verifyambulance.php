<?php
session_start();
require 'connection.php';

if (!isset($_SESSION['verifyemail'])) {
    header("Location: ambulanceregister.php");
    exit();
}

$email = $_SESSION['verifyemail'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'];
    
    // Check if OTP is valid
    $stmt = $pdo->prepare("SELECT id, codeexpiry FROM ambulancedrivers WHERE email = ? AND verificationcode = ?");
    $stmt->execute([$email, $otp]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Check if OTP is expired
        if (strtotime($user['codeexpiry']) < time()) {
            $error = "Verification code has expired. Please register again.";
        } else {
            // Mark user as verified
            $stmt = $pdo->prepare("UPDATE ambulancedrivers SET isverified = TRUE, verificationcode = NULL, codeexpiry = NULL WHERE id = ?");
            if ($stmt->execute([$user['id']])) {
                $success = "Email verified successfully! You can now login.";
                unset($_SESSION['verifyemail']);
            } else {
                $error = "Verification failed. Please try again.";
            }
        }
    } else {
        $error = "Invalid verification code";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification | E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style1.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-envelope me-2"></i>Email Verification</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                            <div class="text-center mt-3">
                                <a href="index.php" class="btn btn-primary">Back to Home</a>
                            </div>
                        <?php else: ?>
                            <p>We've sent a verification code to <strong><?php echo $email; ?></strong>. Please check your email and enter the code below.</p>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="otp" class="form-label">Verification Code</label>
                                    <input type="text" class="form-control" id="otp" name="otp" required maxlength="6">
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Verify Email</button>
                                </div>
                            </form>
                            <div class="mt-3 text-center">
                                <p>Didn't receive the code? <a href="resendverification.php">Resend</a></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>