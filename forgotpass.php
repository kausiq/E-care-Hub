<?php
require 'connection.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $message = "Please enter your email.";
    } else {
        // Check if the email exists
        $stmt = $pdo->prepare("SELECT id, is_verified FROM users WHERE email = ? AND role = 'patient'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $message = "No account found with this email.";
        } elseif ($user['is_verified'] == 0) {
            $message = "Please verify your email before requesting a password reset.";
        } else {
            // Generate OTP
            $otp = rand(100000, 999999);

            // Update OTP in the database (Optional: store OTP expiration time)
            $stmt = $pdo->prepare("UPDATE users SET otp_code = ? WHERE email = ?");
            $stmt->execute([$otp, $email]);

            // Send OTP via email
            if (sendVerificationEmail($email, $otp)) {
                $message = "OTP sent to your email. Please enter it below to reset your password.";
                header('Location: resetpass.php?email=' . urlencode($email));
                exit();
            } else {
                $message = "Failed to send OTP. Please try again later.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background-color: #eef1f5;
        }

        .forgot-password-container {
            background: white;
            padding: 40px;
            margin-top: 80px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h2 {
            color: #333;
            font-weight: 600;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: none;
        }

        .alert {
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="container col-md-6 offset-md-3 forgot-password-container">
    <h2 class="text-center mb-4"><i class="fas fa-key"></i> Forgot Password</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info text-center"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label><i class="fas fa-envelope"></i> Email Address</label>
            <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="text-center">
            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send OTP</button>
        </div>
    </form>
</div>

</body>
</html>
