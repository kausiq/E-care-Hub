<?php
require 'connection.php';

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$error = '';
$email = isset($_GET['email']) ? trim($_GET['email']) : '';

// Check if coming from forgot password flow
if (empty($email) && isset($_SESSION['password_reset']['email'])) {
    $email = $_SESSION['password_reset']['email'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $otp = trim($_POST['otp']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Debug: Log input values
    error_log("Reset attempt - Email: $email, OTP: $otp");
    
    // Validate inputs
    if (empty($otp)) {
        $error = "Please enter the verification code.";
    } elseif (!preg_match('/^\d{6}$/', $otp)) {
        $error = "Verification code must be 6 digits.";
    } elseif (empty($password)) {
        $error = "Please enter a new password.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        try {
            // Debug: Check database time
            $timeCheck = $pdo->query("SELECT NOW() as db_time")->fetch();
            error_log("Database time: " . $timeCheck['db_time']);
            error_log("PHP time: " . date('Y-m-d H:i:s'));
            
            // Verify OTP from verification_codes table
            $stmt = $pdo->prepare("SELECT v.* FROM verification_codes v
                                  JOIN ambulancedrivers a ON v.email = a.email
                                  WHERE v.email = ? 
                                  AND v.code = ?
                                  AND v.expiry > NOW()
                                  AND v.is_used = 0");
            $stmt->execute([$email, $otp]);
            $verification = $stmt->fetch();
            
            if (!$verification) {
                // Additional debug info
                $checkStmt = $pdo->prepare("SELECT code, expiry, is_used FROM verification_codes 
                                           WHERE email = ? ORDER BY created_at DESC LIMIT 1");
                $checkStmt->execute([$email]);
                $codeInfo = $checkStmt->fetch();
                
                error_log("Stored code: " . ($codeInfo['code'] ?? 'NULL') . 
                         ", Expiry: " . ($codeInfo['expiry'] ?? 'NULL') . 
                         ", Used: " . ($codeInfo['is_used'] ?? 'NULL'));
                
                $error = "Invalid or expired verification code. Please check and try again.";
            } else {
                // Debug: Log successful verification
                error_log("OTP verified successfully for email: $email");
                
                // Update password in ambulancedrivers table
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $pdo->beginTransaction();
                
                $updateStmt = $pdo->prepare("UPDATE ambulancedrivers 
                                           SET password = ?, updatedat = NOW() 
                                           WHERE email = ?");
                $updateStmt->execute([$hashed_password, $email]);
                
                // Mark OTP as used
                $updateOtpStmt = $pdo->prepare("UPDATE verification_codes 
                                              SET is_used = 1 
                                              WHERE id = ?");
                $updateOtpStmt->execute([$verification['id']]);
                
                $pdo->commit();
                
                // Send confirmation email
                $name = "Ambulance Driver"; // Replace with the actual name if available
                sendPasswordChangedEmail($email, $name);
                
                // Clear session and redirect
                unset($_SESSION['password_reset']);
                $_SESSION['reset_success'] = "Password reset successfully. You can now login with your new password.";
                header("Location: ambulancelogin.php?reset=success");
                exit();
            }
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Password reset error: " . $e->getMessage());
            $error = "A system error occurred. Please try again later.";
        }
    }
}

function sendPasswordChangedEmail($to, $name) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'afiquehossain84@gmail.com';
        $mail->Password = 'jhcb qxfc tyhe tocq';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('afiquehossain84@gmail.com', 'E Care Hub');
        $mail->addAddress($to, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Ambulance Driver Password Has Been Reset';
        $mail->Body    = "
            <h2>Password Reset Confirmation</h2>
            <p>Dear $name,</p>
            <p>The password for your E Care Hub ambulance driver account has been successfully reset.</p>
            <p>If you did not initiate this password reset, please contact our support team immediately.</p>
            <p><strong>Security Tip:</strong> Never share your password and change it regularly.</p>
            <p>Thank you,<br>E Care Hub Team</p>
        ";
        $mail->AltBody = "Your E Care Hub ambulance driver password has been reset. If you didn't request this, please contact support immediately.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Password change email error: " . $mail->ErrorInfo);
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Ambulance Driver Portal</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .reset-container {
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .reset-header {
            background-color: var(--primary-color);
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .reset-header h2 {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .reset-body {
            padding: 30px;
        }
        
        .input-group-text {
            background-color: transparent;
            border-right: none;
        }
        
        .input-group .form-control {
            border-left: none;
        }
        
        .form-control {
            height: 48px;
            border-radius: 6px;
            border: 1px solid #ddd;
            padding-left: 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border: none;
            height: 48px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
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
            color: #7f8c8d;
            z-index: 5;
        }
        
        .password-strength {
            height: 5px;
            background-color: #eee;
            margin-top: -10px;
            margin-bottom: 15px;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .strength-bar {
            height: 100%;
            width: 0%;
            background-color: #e74c3c;
            transition: all 0.3s;
        }
        
        .password-rules {
            font-size: 0.8rem;
            color: #7f8c8d;
            margin-top: -10px;
            margin-bottom: 15px;
        }
        
        .alert {
            border-radius: 6px;
            padding: 12px 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-container">
            <div class="reset-header">
                <h2>Reset Password</h2>
                <p>Ambulance Driver Log In</p>
            </div>
            
            <div class="reset-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form method="post">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                    
                    <div class="mb-3">
                        <label for="otp" class="form-label">Verification Code</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-shield-alt"></i></span>
                            <input type="text" class="form-control" id="otp" name="otp" required 
                                   placeholder="Enter 6-digit code" maxlength="6" pattern="\d{6}"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                        <small class="text-muted">Enter the code sent to <?= htmlspecialchars($email) ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <div class="password-input-group">
                            <input type="password" class="form-control" id="password" name="password" required 
                                   placeholder="Enter new password" minlength="8"
                                   oninput="updatePasswordStrength()">
                            <span class="password-toggle" onclick="togglePassword('password')">
                                <i class="far fa-eye"></i>
                            </span>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="password-rules">
                            Password must be at least 8 characters long
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="password-input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                                   placeholder="Confirm new password" minlength="8">
                            <span class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="far fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-save me-2"></i>Reset Password
                    </button>
                    
                    <div class="text-center">
                        <a href="ambulancelogin.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>Back to Login
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
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

        // Password strength meter
        function updatePasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('strengthBar');
            const strength = calculatePasswordStrength(password);
            
            strengthBar.style.width = strength + '%';
            strengthBar.style.backgroundColor = 
                strength < 30 ? '#e74c3c' : 
                strength < 70 ? '#f39c12' : '#27ae60';
        }

        function calculatePasswordStrength(password) {
            let strength = 0;
            
            // Length contributes up to 40%
            strength += Math.min(password.length / 12 * 40, 40);
            
            // Character variety contributes up to 60%
            const hasLower = /[a-z]/.test(password);
            const hasUpper = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[^a-zA-Z0-9]/.test(password);
            
            const varietyCount = [hasLower, hasUpper, hasNumber, hasSpecial].filter(Boolean).length;
            strength += (varietyCount / 4) * 60;
            
            return Math.min(Math.round(strength), 100);
        }
    </script>
</body>
</html>