<?php
require 'connection.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $otp      = trim($_POST['otp'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($otp) || empty($password)) {
        $message = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT id, otp_code FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $message = "No account found with this email.";
        } elseif ($user['otp_code'] == $otp) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, otp_code = NULL WHERE email = ?");
            $stmt->execute([$hashedPassword, $email]);
            header('Location: patientlogin.php');
            exit();
        } else {
            $message = "Invalid OTP.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background-color: #eef1f5;
        }

        .reset-password-container {
            background: white;
            padding: 40px;
            margin-top: 80px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background-color: #007bff;
            border-radius: 25px;
        }
    </style>
</head>
<body>

<div class="container col-md-6 offset-md-3 reset-password-container">
    <h2 class="text-center mb-4"><i class="fas fa-lock"></i> Reset Password</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info text-center"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label><i class="fas fa-envelope"></i> Email Address*</label>
            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label><i class="fas fa-key"></i> OTP*</label>
            <input type="text" name="otp" class="form-control" required>
        </div>

        <div class="form-group">
            <label><i class="fas fa-lock"></i> New Password*</label>
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
            <button type="submit" class="btn btn-primary px-4"><i class="fas fa-check"></i> Reset Password</button>
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
