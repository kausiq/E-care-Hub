<?php
require 'connection.php';
session_start();

// Check if user is logged in as doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: doctorlogin.php');
    exit();
}

$doctorId = $_SESSION['user_id'];

// Get doctor information
$stmt = $pdo->prepare("SELECT u.name, u.email, d.specialty, d.languages, d.location, d.bio, d.consultation_fee 
                       FROM users u JOIN doctors d ON u.id = d.user_id 
                       WHERE u.id = ?");
$stmt->execute([$doctorId]);
$doctor = $stmt->fetch();

// Get upcoming appointments
$stmt = $pdo->prepare("SELECT a.id, p.user_id as patient_id, u.name as patient_name, 
                      a.appointment_date, a.appointment_time, a.status, a.meeting_link
                      FROM appointments a
                      JOIN patients p ON a.patient_id = p.user_id
                      JOIN users u ON p.user_id = u.id
                      WHERE a.doctor_id = ? AND a.appointment_date >= CURDATE()
                      ORDER BY a.appointment_date, a.appointment_time");
$stmt->execute([$doctorId]);
$upcomingAppointments = $stmt->fetchAll();

// Get today's appointments
$stmt = $pdo->prepare("SELECT a.id, p.user_id as patient_id, u.name as patient_name, 
                      a.appointment_time, a.status, a.meeting_link
                      FROM appointments a
                      JOIN patients p ON a.patient_id = p.user_id
                      JOIN users u ON p.user_id = u.id
                      WHERE a.doctor_id = ? AND a.appointment_date = CURDATE()
                      ORDER BY a.appointment_time");
$stmt->execute([$doctorId]);
$todaysAppointments = $stmt->fetchAll();

// Get recent patients
$stmt = $pdo->prepare("SELECT DISTINCT p.user_id as patient_id, u.name as patient_name
                      FROM appointments a
                      JOIN patients p ON a.patient_id = p.user_id
                      JOIN users u ON p.user_id = u.id
                      WHERE a.doctor_id = ?
                      ORDER BY a.appointment_date DESC
                      LIMIT 5");
$stmt->execute([$doctorId]);
$recentPatients = $stmt->fetchAll();

// Get unread notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$doctorId]);
$unreadNotifications = $stmt->fetchAll();
$unreadCount = count($unreadNotifications);

// Get recent notifications (both read and unread)
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$doctorId]);
$recentNotifications = $stmt->fetchAll();

// Mark notifications as read when viewing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_as_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$doctorId]);
    header("Location: doctordashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/doctordashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-heart text-white brand-heart p-2"></i>
            <a class="sidebar-brand-text ms-2" href="index.php">E Care Hub</a>
        </div>
        
        <ul class="nav flex-column mt-4">
            <li class="nav-item active">
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
            
            <li class="nav-item">
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
                <input type="text" class="form-control" placeholder="Search for patients, appointments...">
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
                                    <a href="#" class="dropdown-item notification-item <?= $notification['is_read'] ? '' : 'unread' ?>">
                                        <div class="d-flex align-items-center">
                                            <div class="mr-3">
                                                <?php if ($notification['type'] === 'appointment'): ?>
                                                    <i class="fas fa-calendar-check text-primary"></i>
                                                <?php elseif ($notification['type'] === 'prescription'): ?>
                                                    <i class="fas fa-prescription-bottle-alt text-success"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-bell text-warning"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <p class="mb-1"><?= htmlspecialchars($notification['message']) ?></p>
                                                <small class="notification-time">
                                                    <?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="">
                            <button type="submit" name="mark_as_read" class="dropdown-item text-center">
                                Mark all as read
                            </button>
                        </form>
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
            <!-- Page Header -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Doctor Dashboard</h1>
                <a href="doctorschedule.php" class="d-none d-sm-inline-block btn btn-primary shadow-sm">
                    <i class="fas fa-calendar-plus fa-sm mr-2"></i> Update Schedule
                </a>
            </div>
            
            <!-- Stats Cards Row -->
            <div class="row">
                <!-- Today's Appointments Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card bg-white p-4 rounded-lg shadow-sm border-left border-primary border-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="stats-title text-muted mb-1">Today's Appointments</p>
                                <h3 class="stats-value mb-0"><?= count($todaysAppointments) ?></h3>
                            </div>
                            <i class="fas fa-calendar-day text-primary"></i>
                        </div>
                        <a href="doctorappointments.php" class="small text-primary font-weight-bold mt-2 d-block">View all <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
                
                <!-- Upcoming Appointments Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card bg-white p-4 rounded-lg shadow-sm border-left border-success border-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="stats-title text-muted mb-1">Upcoming Appointments</p>
                                <h3 class="stats-value mb-0"><?= count($upcomingAppointments) ?></h3>
                            </div>
                            <i class="fas fa-calendar-check text-success"></i>
                        </div>
                        <a href="doctorappointments.php" class="small text-success font-weight-bold mt-2 d-block">View all <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
                
                <!-- Recent Patients Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card bg-white p-4 rounded-lg shadow-sm border-left border-info border-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="stats-title text-muted mb-1">Recent Patients</p>
                                <h3 class="stats-value mb-0"><?= count($recentPatients) ?></h3>
                            </div>
                            <i class="fas fa-user-injured text-info"></i>
                        </div>
                        <a href="doctorpatients.php" class="small text-info font-weight-bold mt-2 d-block">View all <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
                
                <!-- Consultation Fee Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card bg-white p-4 rounded-lg shadow-sm border-left border-warning border-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="stats-title text-muted mb-1">Consultation Fee</p>
                                <h3 class="stats-value mb-0">$<?= number_format($doctor['consultation_fee'], 2) ?></h3>
                            </div>
                            <i class="fas fa-money-bill-wave text-warning"></i>
                        </div>
                        <a href="doctorprof.php" class="small text-warning font-weight-bold mt-2 d-block">Update fee <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Row -->
            <div class="row">
                <!-- Today's Appointments Column -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Today's Appointments</h6>
                            <a href="doctorappointments.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($todaysAppointments)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <p>No appointments scheduled for today</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($todaysAppointments as $appointment): ?>
                                        <div class="list-group-item appointment-card px-0 py-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h5 class="patient-name mb-1"><?= htmlspecialchars($appointment['patient_name']) ?></h5>
                                                    <p class="appointment-time mb-0">
                                                        <i class="far fa-clock mr-1"></i> 
                                                        <?= date('h:i A', strtotime($appointment['appointment_time'])) ?>
                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    <span class="badge-status badge-<?= strtolower($appointment['status']) ?>">
                                                        <?= ucfirst($appointment['status']) ?>
                                                    </span>
                                                    <?php if ($appointment['status'] == 'confirmed' && !empty($appointment['meeting_link'])): ?>
                                                        <a href="<?= htmlspecialchars($appointment['meeting_link']) ?>" 
                                                           class="btn btn-sm btn-success mt-2" target="_blank">
                                                            <i class="fas fa-video mr-1"></i> Start
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Doctor Profile Summary -->
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Your Profile Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="bg-light rounded p-3 mr-3">
                                            <i class="fas fa-user-md text-primary"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 text-muted small">Name</p>
                                            <p class="mb-0 font-weight-bold">Dr. <?= htmlspecialchars($doctor['name']) ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="bg-light rounded p-3 mr-3">
                                            <i class="fas fa-stethoscope text-primary"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 text-muted small">Specialty</p>
                                            <p class="mb-0 font-weight-bold"><?= htmlspecialchars($doctor['specialty']) ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="bg-light rounded p-3 mr-3">
                                            <i class="fas fa-map-marker-alt text-primary"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 text-muted small">Location</p>
                                            <p class="mb-0 font-weight-bold"><?= $doctor['location'] ? htmlspecialchars($doctor['location']) : 'Not specified' ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded p-3 mr-3">
                                            <i class="fas fa-language text-primary"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 text-muted small">Languages</p>
                                            <p class="mb-0 font-weight-bold"><?= $doctor['languages'] ? htmlspecialchars($doctor['languages']) : 'Not specified' ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Upcoming Appointments Column -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Upcoming Appointments</h6>
                            <a href="doctorappointments.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($upcomingAppointments)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <p>No upcoming appointments</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Patient</th>
                                                <th>Time</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($upcomingAppointments as $appointment): ?>
                                                <tr>
                                                    <td><?= date('M j, Y', strtotime($appointment['appointment_date'])) ?></td>
                                                    <td><?= htmlspecialchars($appointment['patient_name']) ?></td>
                                                    <td><?= date('h:i A', strtotime($appointment['appointment_time'])) ?></td>
                                                    <td>
                                                        <span class="badge-status badge-<?= strtolower($appointment['status']) ?>">
                                                            <?= ucfirst($appointment['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="doctorappointmentdetails.php?id=<?= $appointment['id'] ?>" 
                                                           class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye mr-1"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Patients -->
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Recent Patients</h6>
                            <a href="doctorpatients.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentPatients)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-user-injured"></i>
                                    <p>No recent patients</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentPatients as $patient): ?>
                                        <a href="doctorpatientdetails.php?id=<?= $patient['patient_id'] ?>" 
                                           class="list-group-item list-group-item-action px-0 py-3">
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar mr-3"><?= strtoupper(substr($patient['patient_name'], 0, 1)) ?></div>
                                                <div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($patient['patient_name']) ?></h6>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- <div class="col-md-6 mb-3">
                                    <a href="doctorprescriptionnew.php" class="btn btn-primary btn-block">
                                        <i class="fas fa-prescription mr-2"></i> New Prescription
                                    </a>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <a href="doctorreportnew.php" class="btn btn-success btn-block">
                                        <i class="fas fa-file-medical mr-2"></i> Add Report
                                    </a>
                                </div> -->
                                <div class="col-md-6 mb-3">
                                    <a href="doctorschedule.php" class="btn btn-info btn-block">
                                        <i class="fas fa-calendar-alt mr-2"></i> Update Schedule
                                    </a>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <a href="doctorprof.php" class="btn btn-warning btn-block">
                                        <i class="fas fa-user-edit mr-2"></i> Edit Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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