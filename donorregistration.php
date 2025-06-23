<?php
require 'connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize input
    $data = [
        'full_name' => filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING),
        'email' => filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL),
        'password' => $_POST['password'], // Will be hashed before storage
        'phone' => filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING),
        'blood_type' => filter_input(INPUT_POST, 'blood_type', FILTER_SANITIZE_STRING),
        'last_donation_date' => $_POST['last_donation_date'] ?? null,
        'address' => filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING),
        'dob' => $_POST['bDob'] ?? null,
        'weight' => isset($_POST['bWeight']) ? (int)$_POST['bWeight'] : null,
        'allergies' => filter_input(INPUT_POST, 'bAllergies', FILTER_SANITIZE_STRING),
        'medications' => filter_input(INPUT_POST, 'bMedications', FILTER_SANITIZE_STRING),
        'health_conditions' => json_encode([
            'healthy' => isset($_POST['bHealthy']),
            'no_tattoo' => isset($_POST['bNoTattoo']),
            'no_risk' => isset($_POST['bNoRisk'])
        ]),
        'verification_code' => rand(100000, 999999),
        'user_type' => 'donor',
        'is_verified' => 0,
        'can_donate' => 0
    ];
    // can donate logic update if he is last donation date is more than 120 days
    $last_donation_date = DateTime::createFromFormat('Y-m-d', $data['last_donation_date']); 
    if ($last_donation_date) {
        $today = new DateTime();
        $interval = $today->diff($last_donation_date);
        if ($interval->days >= 120) {
            $data['can_donate'] = 1;
        }
    }

    // validate if dob is valid date
    if (!empty($data['dob'])) {
        $dob = DateTime::createFromFormat('Y-m-d', $data['dob']);
        if (!$dob) {
            echo "<script>alert('Invalid date of birth format. Please use YYYY-MM-DD.');</script>";
            echo "<script>window.location.href = 'donorregistration.php';</script>";
            exit;
        }
    }

    // Validate required fields
    if (empty($data['last_donation_date'])) {
        die("Last donation date is required");
    }
    
    // Validate password
    if (empty($_POST['password']) || strlen($_POST['password']) < 8) {
        die("Password must be at least 8 characters long");
    }
    // check if last donation date is valid
    $last_donation_date = DateTime::createFromFormat('Y-m-d', $data['last_donation_date']);
    if (!$last_donation_date || $last_donation_date > new DateTime()) {
        echo "<script>alert('Invalid last donation date. Please select a valid date.');</script>";
        // Redirect to the registration page after showing the alert
        echo "<script>window.location.href = 'donorregistration.php';</script>";
        exit;
    }
    
    // Hash the password
    $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM donors WHERE email = ?");
        $stmt->execute([$data['email']]);
        
        // Check if email already exists in otp_verification table
        $stmt2 = $pdo->prepare("SELECT id FROM otp_verification WHERE email = ?");
        $stmt2->execute([$data['email']]);
        
        if ($stmt->rowCount() > 0 && $stmt2->rowCount() == 0) {
            echo "<script>alert('Email already registered. Please login or use another email.');</script>";
            echo "<script>window.location.href = 'donorregistration.php';</script>";
            exit;
        }

        // Save to database (unverified)
        $stmt = $pdo->prepare("INSERT INTO donors 
            (full_name, email, password, phone, blood_type, last_donation_date, address, 
             dob, weight, allergies, medications, health_conditions, 
             verification_code, is_verified, can_donate, user_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"); 
            
        $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['password'],
            $data['phone'],
            $data['blood_type'],
            $data['last_donation_date'],
            $data['address'],
            $data['dob'],
            $data['weight'],
            $data['allergies'],
            $data['medications'],
            $data['health_conditions'],
            $data['verification_code'],
            $data['is_verified'],
            $data['can_donate'],
            $data['user_type']
        ]);

        // Save OTP to verification table
        $stmt = $pdo->prepare("INSERT INTO otp_verification (email, otp) VALUES (?, ?)");
        $stmt->execute([$data['email'], $data['verification_code']]);

        // Send email with OTP using PHPMailer
        if (sendVerificationEmail($data['email'], $data['verification_code'])) {
            header("Location: verifyemail.php?email=" . urlencode($data['email']));
            exit();
        } else {
            echo '<div class="alert alert-danger">Failed to send verification email. Please check your email address and try again. If the problem persists, contact support.</div>';
            error_log("Failed to send verification email to: " . $data['email']);
        }
    } catch (PDOException $e) {
        die("Registration failed: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Donor Registration | E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style1.css">
    <link rel="stylesheet" href="css/donor.css">
</head>
<body>
    <div class="registration-container">
        <div class="registration-header donor-header">
            <h2><i class="fas fa-tint me-2"></i>Blood Donor Registration</h2>
            <p>Join our life-saving network</p>
            <!-- <p class="required-note"><small>* indicates required field</small></p> -->
        </div>
        
        <div class="registration-form">
            <form method="post" id="registrationForm">
                <div class="row g-3">
                    <!-- Mandatory Fields -->
                    <div class="col-md-6">
                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <div class="password-toggle">
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            <i class="fas fa-eye password-toggle-icon" id="togglePassword"></i>
                        </div>
                        <div class="form-text">Minimum 8 characters</div>
                    </div>
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <div class="password-toggle">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <i class="fas fa-eye password-toggle-icon" id="toggleConfirmPassword"></i>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>
                    <div class="col-md-6">
                        <label for="blood_type" class="form-label">Blood Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="blood_type" name="blood_type" required>
                            <option value="">Select blood type</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="last_donation_date" class="form-label">Last Donation Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="last_donation_date" name="last_donation_date" required>
                    </div>
                    <div class="col-12">
                        <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                    </div>
                    
                    <!-- Rest of your form fields remain the same -->
                    <!-- Health Conditions, Optional Fields, Terms checkbox, etc. -->
                    <div class="col-12">
                        <label class="form-label">Health Conditions <span class="text-danger">*</span></label>
                        <div class="health-conditions">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="bHealthy" name="bHealthy" required>
                                <label class="form-check-label" for="bHealthy">I confirm I'm in good health</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="bNoTattoo" name="bNoTattoo" required>
                                <label class="form-check-label" for="bNoTattoo">I haven't had tattoos or piercings in the last 6 months</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="bNoRisk" name="bNoRisk" required>
                                <label class="form-check-label" for="bNoRisk">I haven't engaged in any high-risk activities</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Optional Fields -->
                    <div class="col-md-6">
                        <label for="bDob" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="bDob" name="bDob">
                    </div>
                    <div class="col-md-6">
                        <label for="bWeight" class="form-label">Weight (kg)</label>
                        <input type="number" class="form-control" id="bWeight" name="bWeight" min="40">
                    </div>
                    <div class="col-12">
                        <label for="bAllergies" class="form-label">Known Allergies</label>
                        <input type="text" class="form-control" id="bAllergies" name="bAllergies" placeholder="List any known allergies">
                    </div>
                    <div class="col-12">
                        <label for="bMedications" class="form-label">Current Medications</label>
                        <input type="text" class="form-control" id="bMedications" name="bMedications" placeholder="List any current medications">
                    </div>
                    
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="bTerms" name="bTerms" required>
                            <label class="form-check-label" for="bTerms">
                                I agree to the terms and conditions <span class="text-danger">*</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-4">
                    <a href="index.html" class="btn btn-back">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                    <button type="submit" class="btn btn-danger">
                        Register <i class="fas fa-heartbeat ms-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setupPasswordToggle(toggleId, inputId) {
            const toggle = document.getElementById(toggleId);
            const input = document.getElementById(inputId);

            toggle.addEventListener('click', function () {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('fa-eye-slash');
            });
        }

        setupPasswordToggle('togglePassword', 'password');
        setupPasswordToggle('toggleConfirmPassword', 'confirm_password');

        // Password match validation
        document.getElementById('registrationForm').addEventListener('submit', function (e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }

            return true;
        });
    </script>

</body>
</html>