<?php
require 'connection.php';

$message = "";
$email = $_GET['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($token) || empty($password) || empty($confirm_password)) {
        $message = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
    } else {
        // Check if token is valid
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND otp_code = ?  AND role = 'doctor'");
        $stmt->execute([$email, $token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $message = "Invalid or expired reset code. Please request a new one.";
        } else {
            // Update password and clear reset token
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, otp_code = NULL WHERE id = ?");
            $stmt->execute([$hashedPassword, $user['id']]);
            
            $message = "Password reset successfully! You can now login.";
            header('Location: doctorlogin.php?reset=1');
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .reset-box {
            background: #ffffff;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin: 50px auto;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 25px;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .alert {
            border-radius: 8px;
        }
        .token-input {
            letter-spacing: 2px;
            font-size: 1.2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-box">
            <h2 class="text-center mb-4"><i class="fas fa-key"></i> Reset Password</h2>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <p class="text-center mb-4">Enter the 6-digit code sent to <strong><?php echo htmlspecialchars($email); ?></strong> and your new password.</p>
            
            <form method="POST" action="">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div class="form-group">
                    <label>Verification Code</label>
                    <input type="text" name="token" class="form-control token-input" maxlength="6" required 
                           pattern="\d{6}" title="Please enter exactly 6 digits">
                </div>
                
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="8">
                </div>
                
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
                </div>
            </form>
            
            <div class="text-center mt-4">
                <p>Didn't receive the code? <a href="doctorlogin.php">Try again</a></p>
            </div>
        </div>
    </div>
</body>
</html>