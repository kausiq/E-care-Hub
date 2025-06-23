<?php
require 'connection.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $specialty = trim($_POST['specialty'] ?? '');
    $languages = trim($_POST['languages'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $consultation_fee = $_POST['consultation_fee'] ?? 0;

    if (empty($name) || empty($email) || empty($password) || empty($specialty)) {
        $message = "Please fill in all required fields (Name, Email, Password, Specialty).";
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

                // Insert into users table
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, otp_code) VALUES (?, ?, ?, 'doctor', ?)");
                $stmt->execute([$name, $email, $hashedPassword, $otp]);

                $userId = $pdo->lastInsertId();

                // Insert into doctors table
                $stmt = $pdo->prepare("INSERT INTO doctors (user_id, specialty, languages, location, bio, consultation_fee) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $specialty, $languages, $location, $bio, $consultation_fee]);

                $pdo->commit();

                if (sendVerificationEmail($email, $otp)) {
                    $message = "Registration successful! OTP sent to your email.";
                    header('Location: verifyed.php?email=' . urlencode($email));
                    exit();
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

// Function to send verification email (should be in a separate functions.php file)
?>

<!DOCTYPE html>
<html>
<head>
    <title>Doctor Registration - E Care Hub</title>
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
        
        .specialty-select {
            width: 100%;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="col-md-8 offset-md-2 registration-form">
        <h2 class="text-center mb-4"><i class="fas fa-user-md"></i> Doctor Registration</h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-info text-center"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
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
                <small class="text-muted">Password must be at least 8 characters</small>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-stethoscope"></i> Specialty*</label>
                <select name="specialty" class="form-control specialty-select" required>
                    <option value="">Select Specialty</option>
                    <option value="Cardiology" <?= ($_POST['specialty'] ?? '') === 'Cardiology' ? 'selected' : '' ?>>Cardiology</option>
                    <option value="Dermatology" <?= ($_POST['specialty'] ?? '') === 'Dermatology' ? 'selected' : '' ?>>Dermatology</option>
                    <option value="Endocrinology" <?= ($_POST['specialty'] ?? '') === 'Endocrinology' ? 'selected' : '' ?>>Endocrinology</option>
                    <option value="Gastroenterology" <?= ($_POST['specialty'] ?? '') === 'Gastroenterology' ? 'selected' : '' ?>>Gastroenterology</option>
                    <option value="Neurology" <?= ($_POST['specialty'] ?? '') === 'Neurology' ? 'selected' : '' ?>>Neurology</option>
                    <option value="Pediatrics" <?= ($_POST['specialty'] ?? '') === 'Pediatrics' ? 'selected' : '' ?>>Pediatrics</option>
                    <option value="Psychiatry" <?= ($_POST['specialty'] ?? '') === 'Psychiatry' ? 'selected' : '' ?>>Psychiatry</option>
                    <option value="Other" <?= ($_POST['specialty'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-language"></i> Languages Spoken (comma separated)</label>
                <input type="text" name="languages" class="form-control" value="<?php echo htmlspecialchars($_POST['languages'] ?? '') ?>" placeholder="English, Bengali, Hindi">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-map-marker-alt"></i> Location/City*</label>
                <input type="text" name="location" class="form-control" required value="<?php echo htmlspecialchars($_POST['location'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-file-alt"></i> Professional Bio</label>
                <textarea name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($_POST['bio'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-money-bill-wave"></i> Consultation Fee (BDT)*</label>
                <input type="number" name="consultation_fee" class="form-control" required value="<?php echo htmlspecialchars($_POST['consultation_fee'] ?? '500') ?>" min="0">
            </div>
            
            <div class="form-group">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="termsCheck" required>
                    <label class="form-check-label" for="termsCheck">
                        I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                    </label>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Register</button>
            </div>
            
            <div class="text-center mt-3">
                <p>Already have an account? <a href="doctorlogin.php">Login here</a></p>
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