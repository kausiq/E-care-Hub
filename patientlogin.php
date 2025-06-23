<?php
require 'connection.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $message = "Please enter both email and password.";
    } else {
        $stmt = $pdo->prepare("SELECT id, password, is_verified FROM users WHERE email = ? AND role = 'patient'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $message = "No account found with this email.";
        } elseif ($user['is_verified'] == 0) {
            $message = "Please verify your email before logging in.";
        } elseif (password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'patient';
            header('Location: patientdashboard.php');
            exit();
        } else {
            $message = "Incorrect password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Login - E Care Hub</title>
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
        }
        .btn-primary {
            border-radius: 25px;
        }
        .forgot-password {
            color: #007bff;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="container col-md-6 offset-md-3 login-container">
    <h2 class="text-center"><i class="fas fa-sign-in-alt"></i> Patient Login</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info mt-3"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label><i class="fas fa-envelope"></i> Email Address*</label>
            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label><i class="fas fa-lock"></i> Password*</label>
            <div class="input-group">
                <input type="password" name="password" class="form-control" id="passwordInput" required>
                <div class="input-group-append">
                    <span class="input-group-text" id="togglePassword" style="cursor: pointer;">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary px-5"><i class="fas fa-sign-in-alt"></i> Login</button>
        </div>

        <div class="mt-3 text-center">
            <a href="forgotpass.php" class="forgot-password"><i class="fas fa-key"></i> Forgot Password?</a>
        </div>
    </form>
</div>

<script>
    const togglePassword = document.getElementById("togglePassword");
    const passwordInput = document.getElementById("passwordInput");
    const eyeIcon = document.getElementById("eyeIcon");

    togglePassword.addEventListener("click", () => {
        const type = passwordInput.type === "password" ? "text" : "password";
        passwordInput.type = type;
        eyeIcon.classList.toggle("fa-eye");
        eyeIcon.classList.toggle("fa-eye-slash");
    });
</script>

</body>
</html>
