<?php
require 'connection.php';

$message = "";
$email = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $message = "Please enter both email and password.";
    } else {
        $stmt = $pdo->prepare("SELECT id, password, is_verified FROM users WHERE email = ? AND role = 'doctor'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $message = "No doctor account found with this email.";
        } elseif ($user['is_verified'] == 0) {
            $message = "Please verify your email before logging in.";
            header('Location: verifyemail.php?email=' . urlencode($email));
            exit();
        } elseif (password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'doctor';
            header('Location: doctordashboard.php');
            exit();
        } else {
            $message = "Incorrect password.";
        }
    }
}

// Handle forgot password request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $message = "Please enter your email address.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = 'doctor'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $message = "No doctor account found with this email.";
        } else {
            // Generate reset token (6-digit code)
            $resetToken = rand(100000, 999999);
            
            // Store in database
            $stmt = $pdo->prepare("UPDATE users SET otp_code = ? WHERE id = ?");
            $stmt->execute([$resetToken, $user['id']]);
            
            // Send email
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            try {
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
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "
                    <h2>Password Reset Request</h2>
                    <p>Your password reset code is: <strong>$resetToken</strong></p>
                    <p>If you didn't request this, please ignore this email.</p>
                ";
                $mail->AltBody = "Your password reset code is: $resetToken\n\nThis code will expire in 30 minutes.";
                
                $mail->send();
                
                // Redirect to reset password page
                header('Location: resetpas.php?email=' . urlencode($email));
                exit();
            } catch (Exception $e) {
                $message = "Failed to send reset email. Please try again later.";
                error_log("Mailer Error: " . $mail->ErrorInfo);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Login - E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background-color: #eef1f5;
        }
        .login-container {
            margin-top: 80px;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px #ccc;
            max-width: 500px;
        }
        .btn-primary {
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 600;
        }
        .forgot-password {
            color: #007bff;
            font-weight: 500;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo i {
            font-size: 3rem;
            color: #007bff;
        }
        .tab-content {
            padding: 20px 0;
        }
        .nav-tabs .nav-link {
            border: none;
            color: #495057;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            color: #007bff;
            border-bottom: 2px solid #007bff;
            background: transparent;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 login-container">
                <div class="login-logo">
                    <i class="fas fa-user-md"></i>
                    <h3 class="mt-2">Doctor Login</h3>
                </div>
                
                <?php if (!empty($message)): ?>
                    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_GET['verified']) && $_GET['verified'] == 1): ?>
                    <div class="alert alert-success">Your email has been verified successfully. You can now login.</div>
                <?php endif; ?>
                
                <ul class="nav nav-tabs" id="loginTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="login-tab" data-toggle="tab" href="#login" role="tab">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="forgot-tab" data-toggle="tab" href="#forgot" role="tab">Forgot Password</a>
                    </li>
                </ul>
                
                <div class="tab-content" id="loginTabsContent">
                    <!-- Login Tab -->
                    <div class="tab-pane fade show active" id="login" role="tabpanel">
                        <form method="POST" action="">
                            <input type="hidden" name="login" value="1">
                            
                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> Email Address</label>
                                <input type="email" name="email" class="form-control" required 
                                       value="<?= htmlspecialchars($email) ?>">
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-lock"></i> Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" class="form-control" id="passwordInput" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text" id="togglePassword" style="cursor: pointer;">
                                            <i class="fas fa-eye" id="eyeIcon"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group form-check">
                                <input type="checkbox" class="form-check-input" id="rememberMe">
                                <label class="form-check-label" for="rememberMe">Remember me</label>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary px-5">Login</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Forgot Password Tab -->
                    <div class="tab-pane fade" id="forgot" role="tabpanel">
                        <form method="POST" action="">
                            <input type="hidden" name="forgot_password" value="1">
                            
                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> Email Address</label>
                                <input type="email" name="email" class="form-control" required 
                                       value="<?= htmlspecialchars($email) ?>">
                            </div>
                            
                            <p class="text-muted small">
                                Enter your email address and we'll send you a code to reset your password.
                            </p>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary px-5">Send Reset Code</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p>Don't have an account? <a href="doctorregistration.php">Register as Doctor</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById("togglePassword");
        const passwordInput = document.getElementById("passwordInput");
        const eyeIcon = document.getElementById("eyeIcon");

        if (togglePassword) {
            togglePassword.addEventListener("click", () => {
                const type = passwordInput.type === "password" ? "text" : "password";
                passwordInput.type = type;
                eyeIcon.classList.toggle("fa-eye");
                eyeIcon.classList.toggle("fa-eye-slash");
            });
        }
        
        // Switch to forgot password tab if there was an error on that tab
        <?php if (isset($_POST['forgot_password'])): ?>
            $(document).ready(function() {
                $('#forgot-tab').tab('show');
            });
        <?php endif; ?>
    </script>
</body>
</html>