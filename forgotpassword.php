<?php
require 'connection.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT * FROM donors WHERE email = ?");
        $stmt->execute([$email]);
        $donor = $stmt->fetch();
        
        if ($donor) {
            // Generate OTP
            $otp = rand(100000, 999999);
            
            // Store OTP in database
            $stmt = $pdo->prepare("INSERT INTO otp_verification (email, otp) 
                                  VALUES (?, ?)
                                  ON DUPLICATE KEY UPDATE otp = ?, expires_at = CURRENT_TIMESTAMP + INTERVAL 10 MINUTE");
            $stmt->execute([$email, $otp, $otp]);
            
            // Send OTP email
            if (sendVerificationEmail($email, $otp)) {
                header("Location: resetpassword.php?email=" . urlencode($email));
                exit();
            } else {
                $error = "Failed to send verification email. Please try again.";
            }
        } else {
            $error = "Email not found in our system.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .forgot-container {
            max-width: 400px;
            margin: 80px auto;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            background: #fff;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="forgot-container">
        <div class="logo">
            <i class="fas fa-key fa-3x text-primary"></i>
        </div>
        <h4 class="text-center mb-4">Forgot Password</h4>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" required 
                           placeholder="Enter your registered email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-paper-plane me-2"></i>Send Reset Code
            </button>
            
            <div class="mt-3 text-center">
                <a href="donorlogin.php" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i>Back to Login
                </a>
            </div>
        </form>
    </div>
</body>
</html>