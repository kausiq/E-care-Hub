<?php
session_start();
require 'connection.php';

// Check for password reset success message
$reset_success = isset($_GET['reset']) && $_GET['reset'] === 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if (!$email || empty($password)) {
        $error = "Please provide valid credentials.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM donors WHERE email = ?");
        $stmt->execute([$email]);
        $donor = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($donor && password_verify($password, $donor['password'])) {
            if (!$donor['is_verified']) {
                $error = "Your email is not verified. Please check your inbox.";
            } else {
                $_SESSION['donor_id'] = $donor['id'];
                $_SESSION['donor_name'] = $donor['full_name'];
                header('Location: donordashboard.php');
                exit();
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Donor Login | E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 80px auto;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            background: #fff;
        }
        .form-title {
            margin-bottom: 25px;
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
        .links-container {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="login-container">
        <h4 class="text-center form-title"><i class="fas fa-user-lock me-2"></i>Donor Login</h4>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($reset_success): ?>
            <div class="alert alert-success">
                Your password has been reset successfully. Please login with your new password.
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" required class="form-control" id="email" name="email" 
                       placeholder="Enter your email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="password-input-group">
                    <input type="password" required class="form-control" id="password" name="password" 
                           placeholder="Enter your password">
                    <span class="password-toggle" id="togglePassword">
                        <i class="far fa-eye"></i>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn btn-danger w-100">Login</button>

            <div class="links-container">
                <a href="forgotpassword.php">Forgot Password?</a>
                <a href="selectregister.php">Create Account</a>
            </div>
        </form>
    </div>

    <script>
        // Enhanced password visibility toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password');
        
        togglePassword.addEventListener('click', () => {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
            // Toggle eye icon
            const eyeIcon = togglePassword.querySelector('i');
            if (type === 'password') {
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            } else {
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            }
        });

        // Auto-focus on email field if empty, otherwise on password field
        document.addEventListener('DOMContentLoaded', () => {
            const emailField = document.getElementById('email');
            const passwordField = document.getElementById('password');
            
            if (emailField.value === '') {
                emailField.focus();
            } else {
                passwordField.focus();
            }
        });
    </script>
</body>
</html>