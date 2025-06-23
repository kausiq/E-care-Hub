<?php
require 'connection.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $dob      = $_POST['dob'] ?? null;
    $gender   = $_POST['gender'] ?? null;
    $phone    = $_POST['phone'] ?? null;
    $address  = trim($_POST['address'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $message = "Please fill in all required fields (Name, Email, Password).";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $otp = rand(100000, 999999);

        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                $message = "Email is already registered.";
            } else {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, otp_code) VALUES (?, ?, ?, 'patient', ?)");
                $stmt->execute([$name, $email, $hashedPassword, $otp]);

                $userId = $pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO patients (user_id, date_of_birth, gender, phone, address) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $dob, $gender, $phone, $address]);

                $pdo->commit();

                if (sendVerificationEmail($email, $otp)) {
                    $message = "Registration successful! OTP sent to your email.";
                    header('Location: verifyep.php?email=' . urlencode($email));
                    exit(); // Redirect to the OTP verification page
                } else {
                    $message = "Registered, but failed to send OTP email.";
                }
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient Registration - E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .registration-form {
            background: #ffffff;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        .form-group i {
            margin-right: 8px;
            color: #007bff;
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

        h2 {
            color: #333;
            font-weight: 700;
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

<div class="container mt-5">
    <div class="col-md-8 offset-md-2 registration-form">
        <h2 class="text-center mb-4"><i class="fas fa-user-plus"></i> Patient Registration</h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-info text-center"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Full Name*</label>
                <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email Address*</label>
                <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? '') ?>">
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
            <div class="form-group">
                <label><i class="fas fa-calendar-alt"></i> Date of Birth</label>
                <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($_POST['dob'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label><i class="fas fa-venus-mars"></i> Gender</label>
                <select name="gender" class="form-control">
                    <option value="">Select</option>
                    <option value="male" <?php if (($_POST['gender'] ?? '') === 'male') echo 'selected'; ?>>Male</option>
                    <option value="female" <?php if (($_POST['gender'] ?? '') === 'female') echo 'selected'; ?>>Female</option>
                    <option value="other" <?php if (($_POST['gender'] ?? '') === 'other') echo 'selected'; ?>>Other</option>
                </select>
            </div>
            <div class="form-group">
                <label><i class="fas fa-phone"></i> Phone</label>
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label><i class="fas fa-map-marker-alt"></i> Address</label>
                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? '') ?></textarea>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary"><i class="fas fa-user-check"></i> Register</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const passwordInput = document.getElementById('passwordInput');
        const eyeIcon = document.getElementById('eyeIcon');
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        eyeIcon.classList.toggle('fa-eye');
        eyeIcon.classList.toggle('fa-eye-slash');
    });
</script>

</body>
</html>
