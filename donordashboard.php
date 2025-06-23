<?php
session_start();
require 'connection.php';
if (!isset($_SESSION['donor_id'])) {
    header('Location: login.php');
    exit();
}
$donor_id = $_SESSION['donor_id'];
$stmt = $pdo->prepare("SELECT * FROM donors WHERE id = ?");
$stmt->execute([$donor_id]);
$donor = $stmt->fetch();
function getEligibilityStatus($lastDonationDate) {
    if (!$lastDonationDate) return "Not available";
    $last = new DateTime($lastDonationDate);
    $now = new DateTime();
    $days = $now->diff($last)->days;
    $waitDays = 120 - $days;
    return ($days >= 120) ? "Eligible to Donate" : "Not Eligible (Wait {$waitDays} days)";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard | E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="css/donordashboard.css" rel="stylesheet">
    <style>
        /* Add this new style for password toggle */
        .password-toggle-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 5;
            background: transparent;
            border: none;
        }
        .password-input-group {
            position: relative;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-heartbeat me-2"></i>E Care Hub</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <!-- <a class="nav-link active" href="#"><i class="fas fa-home me-1"></i> Dashboard</a> -->
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($donor['full_name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-container">
            <!-- Header Section -->
            <div class="dashboard-header text-center">
                <div class="profile-circle">
                    <i class="fas fa-user profile-icon"></i>
                </div>
                <h2><?= htmlspecialchars($donor['full_name']) ?></h2>
                <p class="mb-0">Blood Donor Profile</p>
            </div>
            
            <div class="dashboard-content">
                <?php if (!empty($_SESSION['success_message'])): ?>
                    <div class="alert alert-success"> 
                        <i class="fas fa-check-circle me-2"></i>
                        <?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?> 
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger"> 
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?> 
                    </div>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-body blood-info">
                                <div class="blood-type"><?= htmlspecialchars($donor['blood_type'] ?? 'A+') ?></div>
                                <div class="text-muted">Blood Type</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body blood-info">
                                <?php 
                                $status = getEligibilityStatus($donor['last_donation_date']);
                                $statusClass = strpos($status, 'Eligible') !== false ? 'status-eligible' : 
                                              (strpos($status, 'Not available') !== false ? 'status-na' : 'status-not-eligible');
                                ?>
                                <div class="status-badge <?= $statusClass ?>">
                                    <?php if(strpos($status, 'Eligible') !== false): ?>
                                        <i class="fas fa-check-circle me-1"></i>
                                    <?php elseif(strpos($status, 'Not available') !== false): ?>
                                        <i class="fas fa-question-circle me-1"></i>
                                    <?php else: ?>
                                        <i class="fas fa-clock me-1"></i>
                                    <?php endif; ?>
                                    <?= $status ?>
                                </div>
                                <div class="text-muted mt-2">Donation Status</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Profile View Section -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-id-card"></i> Donor Information</h3>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($donor['full_name']) ?>" disabled>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($donor['email']) ?>" disabled>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-birthday-cake"></i></span>
                                <input type="text" class="form-control" value="<?= $donor['dob'] ? date('M d, Y', strtotime($donor['dob'])) : 'Not provided' ?>" disabled>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Weight</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-weight"></i></span>
                                <input type="text" class="form-control" value="<?= $donor['weight'] ? $donor['weight'].' kg' : 'Not provided' ?>" disabled>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Edit Information Form -->
                <form method="post" action="updatedonorinfo.php">
                    <div class="form-section">
                        <h3 class="section-title"><i class="fas fa-edit"></i> Edit Contact Information</h3>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($donor['phone']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_donation_date" class="form-label">Last Donation Date</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                    <input type="date" class="form-control" id="last_donation_date" name="last_donation_date" value="<?= htmlspecialchars($donor['last_donation_date']) ?>">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Current Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                <textarea class="form-control" id="address" name="address" rows="2" required><?= htmlspecialchars($donor['address']) ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                    </div>
                </form>
                
                <!-- Password Change Form -->
                <form method="post" action="updatedonorinfo.php" id="passwordForm">
                    <input type="hidden" name="action" value="password_change">
                    <div class="form-section">
                        <h3 class="section-title"><i class="fas fa-lock"></i> Change Password</h3>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <div class="input-group password-input-group">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    <button type="button" class="password-toggle-icon" onclick="togglePassword('current_password', 'current_password_toggle')">
                                        <i class="far fa-eye" id="current_password_toggle"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <div class="input-group password-input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <button type="button" class="password-toggle-icon" onclick="togglePassword('new_password', 'new_password_toggle')">
                                        <i class="far fa-eye" id="new_password_toggle"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <div class="input-group password-input-group">
                                    <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <button type="button" class="password-toggle-icon" onclick="togglePassword('confirm_password', 'confirm_password_toggle')">
                                        <i class="far fa-eye" id="confirm_password_toggle"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="logout.php" class="btn btn-secondary">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-key me-2"></i>Change Password
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="footer">
                <p class="mb-0">© 2025 E Care Hub Blood Donation System. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
        
        // Add animation to alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.classList.add('fade');
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 500);
                }, 3000);
            });
        });

        // Password toggle function
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>