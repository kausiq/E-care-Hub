<?php
require 'connection.php';

session_start();

// Redirect if already logged in
if (isset($_SESSION['ambulance_driver'])) {
    header("Location: ambulancedashboard.php");
    exit();
}

$error = '';
$success = isset($_GET['reset']) && $_GET['reset'] === 'success' ? 'Password reset successfully. You can now login with your new password.' : '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM ambulancedrivers WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            if (password_verify($password, $user['password'])) {
                if ($user['isverified']) {
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    $_SESSION['ambulance_driver'] = [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'name' => $user['fullname'],
                        'last_login' => time(),
                        'profile_picture' => $user['profilepicture'] ? 'data:' . $user['profilepicturetype'] . ';base64,' . base64_encode($user['profilepicture']) : null
                    ];
                    
                    // Update last login time
                    $updateStmt = $pdo->prepare("UPDATE ambulancedrivers SET updatedat = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);
                    
                    header("Location: ambulancedashboard.php");
                    exit();
                } else {
                    $error = "Please verify your email address before logging in.";
                    sleep(1);
                }
            } else {
                $error = "Invalid email or password.";
                sleep(1);
            }
        } else {
            $error = "Invalid email or password.";
            sleep(1);
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "A system error occurred. Please try again later.";
    }
}

// Handle forgot password request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
    $email = trim($_POST['email']);
    
    try {
        // Check if email exists in ambulancedrivers table
        $stmt = $pdo->prepare("SELECT id FROM ambulancedrivers WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate secure OTP
            $otp = rand(100000, 999999);
            // $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Store OTP in verification_codes table
            $insertStmt = $pdo->prepare("INSERT INTO verification_codes (email, code, expiry) VALUES (?, ?, CURRENT_TIMESTAMP + INTERVAL 10 MINUTE)");
            $insertStmt->execute([$email, $otp]);
            
            // Send email
            if (sendVerificationEmail($email, $otp)) {
                $_SESSION['password_reset'] = [
                    'email' => $email,
                    'attempts' => 0,
                    'last_attempt' => time()
                ];
                
                header("Location: ambulanceresetpassword.php");
                exit();
            } else {
                $error = "Failed to send OTP. Please try again later.";
            }
        } else {
            // Don't reveal whether email exists
            $success = "If your email exists in our system, you'll receive a password reset OTP.";
        }
    } catch (PDOException $e) {
        error_log("Forgot password error: " . $e->getMessage());
        $error = "A system error occurred. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ambulance Driver Login | E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
         :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .auth-container {
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .auth-header {
            background-color: #FD7E14;
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
        }
        
        .auth-header h2 {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .auth-header p {
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .auth-logo {
            width: 70px;
            height: 70px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .auth-logo i {
            color: black;
            font-size: 32px;
        }
        
        .auth-body {
            padding: 30px;
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
        
        .input-group-text {
            background-color: transparent;
            border-right: none;
        }
        
        .input-group .form-control {
            border-left: none;
        }
        
        .btn-primary {
            background-color: #FD7E14;
            border: none;
            height: 48px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #FD7E14;
            transform: translateY(-2px);
        }
        
        .btn-block {
            width: 100%;
        }
        
        .auth-footer {
            text-align: center;
            padding: 20px;
            background-color: var(--light-color);
            font-size: 0.85rem;
        }
        
        .alert {
            border-radius: 6px;
            padding: 12px 15px;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #7f8c8d;
        }
        
        .password-container {
            position: relative;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        
        .divider::before, .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #ddd;
        }
        
        .divider-text {
            padding: 0 10px;
            color: #7f8c8d;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-ambulance"></i>
                </div>
                <h2>Ambulance Driver Log In</h2>
            </div>
            
            <div class="auth-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form id="loginForm" method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="your@email.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-container">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                            </div>
                            <span class="password-toggle" id="togglePassword">
                                <i class="far fa-eye"></i>
                            </span>
                        </div>
                        <div class="text-end mt-2">
                            <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" class="text-decoration-none" style="font-size: 0.85rem;">Forgot Password?</a>
                        </div>
                    </div>
                    
                    <button type="submit" name="login" class="btn btn-primary btn-block mb-3">Login</button>
                    
                    <div class="divider">
                        <span class="divider-text">OR</span>
                    </div>
                    
                    <div class="text-center mt-3">
                        <p style="font-size: 0.9rem;">Don't have an account? <a href="ambulanceregister.php" class="text-decoration-none">Register</a></p>
                    </div>
                </form>
            </div>
            
            <div class="auth-footer">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> E Care Hub. All rights reserved.</p>
            </div>
        </div>
    </div>
    
    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordModalLabel">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Enter your registered email address to receive a password reset OTP.</p>
                    <form id="forgotPasswordForm" method="POST" action="">
                        <div class="mb-3">
                            <label for="forgot_email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="forgot_email" name="email" placeholder="your@email.com" required>
                            </div>
                        </div>
                        <button type="submit" name="forgot_password" class="btn btn-primary btn-block">Send OTP</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Focus email field when forgot password modal opens
        document.getElementById('forgotPasswordModal').addEventListener('shown.bs.modal', function() {
            document.getElementById('forgot_email').focus();
        });
        
        // Populate forgot email with login email if available
        document.getElementById('forgotPasswordModal').addEventListener('show.bs.modal', function() {
            const loginEmail = document.getElementById('email').value;
            if (loginEmail) {
                document.getElementById('forgot_email').value = loginEmail;
            }
        });
    </script>
</body>
</html>