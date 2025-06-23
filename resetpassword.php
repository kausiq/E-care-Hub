<?php
require 'connection.php';

$error = '';
$email = isset($_GET['email']) ? $_GET['email'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $otp = $_POST['otp'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate OTP
    $stmt = $pdo->prepare("SELECT * FROM otp_verification 
                          WHERE email = ? AND otp = ? AND expires_at > NOW()");
    $stmt->execute([$email, $otp]);
    $verification = $stmt->fetch();
    
    if (!$verification) {
        $error = "Invalid or expired OTP.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Update password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE donors SET password = ? WHERE email = ?");
        $stmt->execute([$hashed_password, $email]);
        
        // Delete OTP
        $stmt = $pdo->prepare("DELETE FROM otp_verification WHERE email = ?");
        $stmt->execute([$email]);
        
        // Redirect to login with success message
        header("Location: donorlogin.php?reset=success");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .reset-container {
            max-width: 400px;
            margin: 80px auto;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            background: #fff;
        }
        .password-input-group {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 5;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="reset-container">
        <div class="logo">
            <i class="fas fa-lock fa-3x text-primary"></i>
        </div>
        <h4 class="text-center mb-4">Reset Password</h4>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
            
            <div class="mb-3">
                <label for="otp" class="form-label">Verification Code</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-shield-alt"></i></span>
                    <input type="text" class="form-control" id="otp" name="otp" required 
                           placeholder="Enter the 6-digit code" maxlength="6" pattern="\d{6}">
                </div>
                <small class="text-muted">Check your email for the verification code</small>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <div class="password-input-group">
                    <input type="password" class="form-control" id="password" name="password" required 
                           placeholder="Enter new password (min 8 characters)" minlength="8">
                    <span class="password-toggle" onclick="togglePassword('password')">
                        <i class="far fa-eye"></i>
                    </span>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="password-input-group">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                           placeholder="Confirm new password" minlength="8">
                    <span class="password-toggle" onclick="togglePassword('confirm_password')">
                        <i class="far fa-eye"></i>
                    </span>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save me-2"></i>Reset Password
            </button>
            
            <div class="mt-3 text-center">
                <a href="donorlogin.php" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i>Back to Login
                </a>
            </div>
        </form>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Auto-focus on OTP field
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('otp').focus();
        });
    </script>
</body>
</html>