<?php
require 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: doctorlogin.php');
    exit();
}

$doctorId = $_SESSION['user_id'];

// Get doctor info
$stmt = $pdo->prepare("SELECT u.name, d.specialty FROM users u JOIN doctors d ON u.id = d.user_id WHERE u.id = ?");
$stmt->execute([$doctorId]);
$doctor = $stmt->fetch();

// Get patients who have appointments with this doctor
$stmt = $pdo->prepare("SELECT DISTINCT p.user_id, u.name, p.date_of_birth, p.gender, p.phone
                      FROM patients p
                      JOIN users u ON p.user_id = u.id
                      JOIN appointments a ON p.user_id = a.patient_id
                      WHERE a.doctor_id = ?
                      ORDER BY u.name");
$stmt->execute([$doctorId]);
$patients = $stmt->fetchAll();

// Get unread notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$doctorId]);
$unreadNotifications = $stmt->fetchAll();
$unreadCount = count($unreadNotifications);

// Get recent notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$doctorId]);
$recentNotifications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Patients - E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/doctordashboard.css">
    <style>        
        .patient-card {
            border-left: 4px solid var(--info);
            transition: all 0.3s;
        }
        
        .patient-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-heart text-white brand-heart p-2"></i>
            <a class="sidebar-brand-text ms-2" href="index.php">E Care Hub</a>
        </div>
        
        <ul class="nav flex-column mt-4">
            <li class="nav-item">
                <a class="nav-link" href="doctordashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="doctorappointments.php">
                    <i class="fas fa-calendar-check"></i>
                    <span>Appointments</span>
                </a>
            </li>
            
            <li class="nav-item active">
                <a class="nav-link" href="doctorpatients.php">
                    <i class="fas fa-user-injured"></i>
                    <span>Patients</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="doctorprescriptions.php">
                    <i class="fas fa-prescription-bottle-alt"></i>
                    <span>Prescriptions</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="doctorreports.php">
                    <i class="fas fa-file-medical"></i>
                    <span>Medical Reports</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="doctorschedule.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Schedule</span>
                </a>
            </li>
            
            <li class="nav-item mt-4">
                <a class="nav-link" href="doctorprof.php">
                    <i class="fas fa-user"></i>
                    <span>Profile Settings</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <nav class="topbar">
            <button class="btn d-lg-none" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="search-bar d-none d-md-block">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control" placeholder="Search for patients...">
            </div>
            
            <div class="ml-auto d-flex align-items-center">
                <div class="dropdown mr-3">
                    <a href="#" class="position-relative" data-toggle="dropdown">
                        <i class="fas fa-bell fa-lg text-muted"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge badge-danger notification-badge"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow" style="width: 350px;">
                        <h6 class="dropdown-header">Notifications</h6>
                        <?php if (empty($recentNotifications)): ?>
                            <div class="px-3 py-2 text-center text-muted">
                                No notifications
                            </div>
                        <?php else: ?>
                            <div style="max-height: 300px; overflow-y: auto;">
                                <?php foreach ($recentNotifications as $notification): ?>
                                    <a href="#" class="dropdown-item notification-item">
                                        <div class="d-flex align-items-center">
                                            <div class="mr-3">
                                                <i class="fas fa-bell text-warning"></i>
                                            </div>
                                            <div>
                                                <p class="mb-1"><?= htmlspecialchars($notification['message']) ?></p>
                                                <small class="text-muted">
                                                    <?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-center" href="doctornotifications.php">
                            View All Notifications
                        </a>
                    </div>
                </div>
                
                <div class="topbar-divider d-none d-sm-block"></div>
                
                <div class="dropdown">
                    <div class="user-dropdown" data-toggle="dropdown">
                        <div class="user-avatar"><?= strtoupper(substr($doctor['name'], 0, 1)) ?></div>
                        <div class="d-none d-lg-block">
                            <div class="user-name">Dr. <?= htmlspecialchars($doctor['name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($doctor['specialty']) ?></small>
                        </div>
                    </div>
                    <div class="dropdown-menu dropdown-menu-right shadow">
                        <h6 class="dropdown-header">Welcome!</h6>
                        <a class="dropdown-item" href="doctorprof.php">
                            <i class="fas fa-user mr-2"></i> Profile
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="logout.php">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Page Content -->
        <div class="container-fluid py-4">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800 font-weight-bold">My Patients</h1>
            </div>
            
            <?php if (empty($patients)): ?>
                <div class="card shadow">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-user-injured fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No patients yet</h4>
                        <p class="text-muted">Your patients will appear here after they book appointments with you</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($patients as $patient): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card patient-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 class="mb-1"><?= htmlspecialchars($patient['name']) ?></h5>
                                            <?php 
                                            $dob = new DateTime($patient['date_of_birth']);
                                            $now = new DateTime();
                                            $age = $now->diff($dob)->y;
                                            ?>
                                            <p class="text-muted mb-2">
                                                <?= $age ?> years, <?= ucfirst($patient['gender']) ?>
                                            </p>
                                        </div>
                                        <div class="user-avatar" style="width: 40px; height: 40px; font-size: 1rem;">
                                            <?= strtoupper(substr($patient['name'], 0, 1)) ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <p class="mb-1">
                                            <i class="fas fa-phone mr-2"></i>
                                            <?= $patient['phone'] ? htmlspecialchars($patient['phone']) : 'Phone not provided' ?>
                                        </p>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <a href="patientmedicalhistory.php?patient_id=<?= $patient['user_id'] ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-history mr-2"></i> History
                                        </a>
                                        <a href="newprescription.php?patient_id=<?= $patient['user_id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-prescription mr-2"></i> New Prescription
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-white py-4 mt-auto">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between small">
                <div class="text-muted">Copyright &copy; E Care Hub <?= date('Y') ?></div>
            </div>
        </div>
    </footer>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        $('#sidebarToggle').click(function() {
            $('.sidebar').toggleClass('active');
        });
        
        // Close sidebar when clicking outside on mobile
        $(document).click(function(e) {
            if ($(window).width() < 992) {
                if (!$(e.target).closest('.sidebar').length && !$(e.target).is('#sidebarToggle')) {
                    $('.sidebar').removeClass('active');
                }
            }
        });
        
        // Prevent closing when clicking inside sidebar
        $('.sidebar').click(function(e) {
            e.stopPropagation();
        });
    </script>
</body>
</html>