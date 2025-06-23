<?php
require 'connection.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $otp   = trim($_POST['otp'] ?? '');

    if (empty($email) || empty($otp)) {
        $message = "Please enter both email and OTP.";
    } else {
        $stmt = $pdo->prepare("SELECT id, otp_code, is_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $message = "No account found with this email.";
        } elseif ($user['is_verified']) {
            $message = "Your email is already verified.";
        } elseif ($user['otp_code'] == $otp) {
            $update = $pdo->prepare("UPDATE users SET is_verified = 1, otp_code = NULL WHERE id = ?");
            $update->execute([$user['id']]);
            $message = "Email verified successfully! You can now log in.";
            header('Location: index.php');  // Redirect to home page
            exit();
        } else {
            $message = "Invalid OTP. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Email Verification - E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background-color: #eef1f5;
        }
        .verify-container {
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

<div class="container col-md-6 offset-md-3 verify-container">
    <h2 class="text-center mb-4"><i class="fas fa-envelope-open-text"></i> Email Verification</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info text-center"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label><i class="fas fa-envelope"></i> Email Address</label>
            <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_GET['email'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label><i class="fas fa-key"></i> Enter OTP Code</label>
            <input type="text" name="otp" class="form-control" required maxlength="6">
        </div>
        <div class="text-center">
            <button type="submit" class="btn btn-primary"><i class="fas fa-check-circle"></i> Verify Email</button>
        </div>
    </form>
</div>

</body>
</html>
