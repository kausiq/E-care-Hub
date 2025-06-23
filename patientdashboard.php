<?php
require 'connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: patientlogin.php');
    exit();
}

// Get patient information
$patientId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT u.name, u.email, p.date_of_birth, p.gender, p.phone, p.address 
                       FROM users u JOIN patients p ON u.id = p.user_id 
                       WHERE u.id = ?");
$stmt->execute([$patientId]);
$patient = $stmt->fetch();

// Calculate age from date of birth
$age = '';
if (!empty($patient['date_of_birth'])) {
    $dob = new DateTime($patient['date_of_birth']);
    $now = new DateTime();
    $age = $now->diff($dob)->y;
}

// Get upcoming appointments
$stmt = $pdo->prepare("SELECT a.id, d.user_id as doctor_id, u.name as doctor_name, d.specialty, 
                       a.appointment_date, a.appointment_time, a.status, a.meeting_link
                       FROM appointments a
                       JOIN doctors d ON a.doctor_id = d.user_id
                       JOIN users u ON d.user_id = u.id
                       WHERE a.patient_id = ? AND a.appointment_date >= CURDATE()
                       ORDER BY a.appointment_date, a.appointment_time");
$stmt->execute([$patientId]);
$upcomingAppointments = $stmt->fetchAll();

// Get past appointments for medical history
$stmt = $pdo->prepare("SELECT a.id, u.name as doctor_name, d.specialty, 
                       a.appointment_date, a.appointment_time
                       FROM appointments a
                       JOIN doctors d ON a.doctor_id = d.user_id
                       JOIN users u ON d.user_id = u.id
                       WHERE a.patient_id = ? AND a.appointment_date < CURDATE()
                       ORDER BY a.appointment_date DESC, a.appointment_time DESC
                       LIMIT 5");
$stmt->execute([$patientId]);
$pastAppointments = $stmt->fetchAll();

// Get recent prescriptions
$stmt = $pdo->prepare("SELECT p.id, p.created_at, u.name as doctor_name, d.specialty
                       FROM prescriptions p
                       JOIN doctors d ON p.doctor_id = d.user_id
                       JOIN users u ON d.user_id = u.id
                       WHERE p.patient_id = ?
                       ORDER BY p.created_at DESC
                       LIMIT 3");
$stmt->execute([$patientId]);
$recentPrescriptions = $stmt->fetchAll();

// Get medical reports count
$stmt = $pdo->prepare("SELECT COUNT(*) as report_count FROM medical_reports WHERE patient_id = ?");
$stmt->execute([$patientId]);
$reportCount = $stmt->fetch()['report_count'];

// Get unread notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$patientId]);
$unreadNotifications = $stmt->fetchAll();
$unreadCount = count($unreadNotifications);

// Get recent notifications (both read and unread)
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$patientId]);
$recentNotifications = $stmt->fetchAll();
// Mark notifications as read when viewing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_as_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$patientId]);
    header("Location: profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #43aa8b;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: #333;
        }
        
        /* Sidebar Styles */
        .sidebar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
            width: 280px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }
        
        .sidebar-brand {
            padding: 1.5rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand img {
            height: 40px;
            margin-right: 10px;
        }
        
        .sidebar-brand-text {
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
            letter-spacing: 1px;
        }
        
        .nav-item {
            position: relative;
            margin: 5px 15px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            font-weight: 500;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .nav-link i {
            margin-right: 12px;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }
        
        .active {
            color: white;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .active:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: white;
        }
        
        /* Main Content Styles */
        .main-content {
            margin-left: 280px;
            width: calc(100% - 280px);
            min-height: 100vh;
            transition: all 0.3s;
        }
        
        /* Topbar Styles */
        .topbar {
            height: 70px;
            background: white;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            padding: 0 25px;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        
        .search-bar {
            position: relative;
            flex-grow: 1;
            max-width: 500px;
        }
        
        .search-bar input {
            padding-left: 40px;
            border-radius: 30px;
            border: 1px solid var(--light-gray);
            background-color: var(--light-gray);
            transition: all 0.3s;
        }
        
        .search-bar input:focus {
            background-color: white;
            box-shadow: none;
            border-color: var(--primary-light);
        }
        
        .search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        .topbar-divider {
            width: 1px;
            height: 40px;
            background-color: var(--light-gray);
            margin: 0 20px;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.6rem;
            padding: 3px 6px;
        }
        
        .user-dropdown {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .user-name {
            font-weight: 500;
            color: var(--dark);
        }
        
        /* Card Styles */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            transition: all 0.3s;
            margin-bottom: 25px;
        }
        
        .card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 20px 25px;
            border-radius: 12px 12px 0 0 !important;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-header h6 {
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }
        
        .card-body {
            padding: 25px;
        }
        
        /* Stats Card Styles */
        .stats-card {
            padding: 20px;
            border-radius: 12px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .stats-card:after {
            content: '';
            position: absolute;
            top: -20px;
            right: -20px;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .stats-card i {
            font-size: 2.5rem;
            opacity: 0.3;
            position: absolute;
            top: 20px;
            right: 20px;
        }
        
        .stats-card .stats-title {
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 5px;
            opacity: 0.9;
        }
        
        .stats-card .stats-value {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0;
        }
        
        /* Appointment Card Styles */
        .appointment-card {
            border-left: 4px solid var(--primary);
            border-radius: 8px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .appointment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .appointment-card .doctor-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .appointment-card .specialty {
            font-size: 0.85rem;
            color: var(--gray);
        }
        
        .appointment-card .appointment-time {
            font-size: 0.9rem;
            color: var(--dark);
        }
        
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-pending {
            background-color: rgba(248, 150, 30, 0.1);
            color: var(--warning);
        }
        
        .badge-confirmed {
            background-color: rgba(67, 170, 139, 0.1);
            color: var(--info);
        }
        
        .badge-completed {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success);
        }
        
        .badge-cancelled {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--danger);
        }
        
        /* Button Styles */
        .btn {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
            border-color: var(--secondary);
        }
        
        .btn-success {
            background-color: var(--info);
            border-color: var(--info);
        }
        
        .btn-success:hover {
            background-color: #3a9278;
            border-color: #3a9278;
        }
        
        /* Table Styles */
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table thead th {
            border: none;
            background-color: var(--light);
            color: var(--gray);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        
        .table td {
            vertical-align: middle;
            border-top: 1px solid var(--light-gray);
        }
        
        .table tr:first-child td {
            border-top: none;
        }
        
        /* Empty State Styles */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--light-gray);
            margin-bottom: 20px;
        }
        
        .empty-state p {
            color: var(--gray);
            margin-bottom: 20px;
        }

        /* Notification Styles */
        .notification-item {
            padding: 10px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-item.unread {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .notification-time {
            font-size: 0.75rem;
            color: var(--gray);
        }
        
        /* Responsive Styles */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .topbar {
                padding: 0 15px;
            }
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
            <li class="nav-item active">
                <a class="nav-link" href="patientdashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="finddoctor.php">
                    <i class="fas fa-search"></i>
                    <span>Find Doctor</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="appointments.php">
                    <i class="fas fa-calendar-check"></i>
                    <span>Appointments</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="prescriptions.php">
                    <i class="fas fa-prescription-bottle-alt"></i>
                    <span>Prescriptions</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="medicalreports.php">
                    <i class="fas fa-file-medical"></i>
                    <span>Medical Reports</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="medicalhistory.php">
                    <i class="fas fa-history"></i>
                    <span>Medical History</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="payments.php">
                    <i class="fas fa-credit-card"></i>
                    <span>Payments</span>
                </a>
            </li>
            
            <li class="nav-item mt-4">
                <a class="nav-link" href="profile.php">
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
                <input type="text" class="form-control" placeholder="Search for doctors, appointments...">
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
                                                <?php elseif ($notification['type'] === 'payment'): ?>
                                                    <i class="fas fa-credit-card text-info"></i>
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
                        <a class="dropdown-item text-center" href="notifications.php">
                            View All Notifications
                        </a>
                    </div>
                </div>
                
                <div class="topbar-divider d-none d-sm-block"></div>
                
                <div class="dropdown">
                    <div class="user-dropdown" data-toggle="dropdown">
                        <div class="user-avatar"><?= strtoupper(substr($patient['name'], 0, 1)) ?></div>
                        <div class="d-none d-lg-block">
                            <div class="user-name"><?= htmlspecialchars($patient['name']) ?></div>
                            <small class="text-muted">Patient</small>
                        </div>
                    </div>
                    <div class="dropdown-menu dropdown-menu-right shadow">
                        <h6 class="dropdown-header">Welcome!</h6>
                        <a class="dropdown-item" href="profile.php">
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
                <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Dashboard</h1>
                <a href="finddoctor.php" class="d-none d-sm-inline-block btn btn-primary shadow-sm">
                    <i class="fas fa-plus fa-sm mr-2"></i> Book Appointment
                </a>
            </div>
            
            <!-- Stats Cards Row -->
            <div class="row">
                <!-- Upcoming Appointments Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card bg-white p-4 rounded-lg shadow-sm border-left border-primary border-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="stats-title text-muted mb-1">Upcoming Appointments</p>
                                <h3 class="stats-value mb-0"><?= count($upcomingAppointments) ?></h3>
                            </div>
                            <i class="fas fa-calendar-alt text-primary"></i>
                        </div>
                        <a href="appointments.php" class="small text-primary font-weight-bold mt-2 d-block">View all <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
                
                <!-- Prescriptions Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card bg-white p-4 rounded-lg shadow-sm border-left border-success border-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="stats-title text-muted mb-1">Prescriptions</p>
                                <h3 class="stats-value mb-0"><?= count($recentPrescriptions) ?></h3>
                            </div>
                            <i class="fas fa-prescription-bottle-alt text-success"></i>
                        </div>
                        <a href="prescriptions.php" class="small text-success font-weight-bold mt-2 d-block">View all <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
                
                <!-- Medical Reports Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card bg-white p-4 rounded-lg shadow-sm border-left border-info border-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="stats-title text-muted mb-1">Medical Reports</p>
                                <h3 class="stats-value mb-0"><?= $reportCount ?></h3>
                            </div>
                            <i class="fas fa-file-medical text-info"></i>
                        </div>
                        <a href="medicalreports.php" class="small text-info font-weight-bold mt-2 d-block">View all <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
                
                <!-- Doctors Consulted Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card bg-white p-4 rounded-lg shadow-sm border-left border-warning border-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="stats-title text-muted mb-1">Doctors Consulted</p>
                                <h3 class="stats-value mb-0"><?= count($pastAppointments) ?></h3>
                            </div>
                            <i class="fas fa-user-md text-warning"></i>
                        </div>
                        <a href="medicalhistory.php" class="small text-warning font-weight-bold mt-2 d-block">View all <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Row -->
            <div class="row">
                <!-- Upcoming Appointments Column -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Upcoming Appointments</h6>
                            <a href="appointments.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($upcomingAppointments)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <p>No upcoming appointments scheduled</p>
                                    <a href="finddoctor.php" class="btn btn-primary">
                                        <i class="fas fa-plus mr-2"></i> Book Appointment
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($upcomingAppointments as $appointment): ?>
                                        <div class="list-group-item appointment-card px-0 py-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h5 class="doctor-name mb-1">Dr. <?= htmlspecialchars($appointment['doctor_name']) ?></h5>
                                                    <p class="specialty mb-2">
                                                        <i class="fas fa-stethoscope mr-1"></i> <?= htmlspecialchars($appointment['specialty']) ?>
                                                    </p>
                                                    <p class="appointment-time mb-0">
                                                        <i class="far fa-calendar-alt mr-1"></i> 
                                                        <?= date('F j, Y', strtotime($appointment['appointment_date'])) ?>
                                                        <i class="far fa-clock ml-3 mr-1"></i> 
                                                        <?= date('h:i A', strtotime($appointment['appointment_time'])) ?>
                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    <span class="badge badge-status badge-<?= strtolower($appointment['status']) ?>">
                                                        <?= ucfirst($appointment['status']) ?>
                                                    </span>
                                                    <?php if ($appointment['status'] == 'confirmed' && !empty($appointment['meeting_link'])): ?>
                                                        <a href="<?= htmlspecialchars($appointment['meeting_link']) ?>" 
                                                           class="btn btn-sm btn-success mt-2" target="_blank">
                                                            <i class="fas fa-video mr-1"></i> Join
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
                    
                    <!-- Health Summary Card -->
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Health Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="bg-light rounded p-3 mr-3">
                                            <i class="fas fa-user text-primary"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 text-muted small">Name</p>
                                            <p class="mb-0 font-weight-bold"><?= htmlspecialchars($patient['name']) ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="bg-light rounded p-3 mr-3">
                                            <i class="fas fa-birthday-cake text-primary"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 text-muted small">Age</p>
                                            <p class="mb-0 font-weight-bold"><?= $age ? $age . ' years' : 'Not specified' ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="bg-light rounded p-3 mr-3">
                                            <i class="fas fa-venus-mars text-primary"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 text-muted small">Gender</p>
                                            <p class="mb-0 font-weight-bold"><?= $patient['gender'] ? ucfirst($patient['gender']) : 'Not specified' ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded p-3 mr-3">
                                            <i class="fas fa-phone text-primary"></i>
                                        </div>
                                        <div>
                                            <p class="mb-0 text-muted small">Phone</p>
                                            <p class="mb-0 font-weight-bold"><?= $patient['phone'] ? htmlspecialchars($patient['phone']) : 'Not specified' ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Prescriptions Column -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Recent Prescriptions</h6>
                            <a href="prescriptions.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentPrescriptions)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-prescription-bottle-alt"></i>
                                    <p>No prescriptions available</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Doctor</th>
                                                <th>Specialty</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentPrescriptions as $prescription): ?>
                                                <tr>
                                                    <td><?= date('M j, Y', strtotime($prescription['created_at'])) ?></td>
                                                    <td>Dr. <?= htmlspecialchars($prescription['doctor_name']) ?></td>
                                                    <td><?= htmlspecialchars($prescription['specialty']) ?></td>
                                                    <td>
                                                        <a href="prescriptiondetails.php?id=<?= $prescription['id'] ?>" 
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
                    
                    <!-- Quick Actions Card -->
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <a href="finddoctor.php" class="btn btn-primary btn-block">
                                        <i class="fas fa-search mr-2"></i> Find Doctor
                                    </a>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <a href="uploadreport.php" class="btn btn-success btn-block">
                                        <i class="fas fa-upload mr-2"></i> Upload Report
                                    </a>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <a href="medicalhistory.php" class="btn btn-info btn-block">
                                        <i class="fas fa-history mr-2"></i> Medical History
                                    </a>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <a href="profile.php" class="btn btn-warning btn-block">
                                        <i class="fas fa-user-edit mr-2"></i> Update Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Medical History Card -->
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Recent Medical History</h6>
                            <a href="medicalhistory.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($pastAppointments)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-history"></i>
                                    <p>No medical history available</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($pastAppointments as $appointment): ?>
                                        <a href="appointmentdetails.php?id=<?= $appointment['id'] ?>" 
                                           class="list-group-item list-group-item-action px-0 py-3">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="mb-1">Dr. <?= htmlspecialchars($appointment['doctor_name']) ?></h6>
                                                    <small class="text-muted">
                                                        <i class="fas fa-stethoscope mr-1"></i> <?= htmlspecialchars($appointment['specialty']) ?>
                                                    </small>
                                                </div>
                                                <div class="text-right">
                                                    <small class="text-muted">
                                                        <?= date('M j, Y', strtotime($appointment['appointment_date'])) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
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
                <div>
                    <a href="#">Privacy Policy</a>
                    &middot;
                    <a href="#">Terms &amp; Conditions</a>
                </div>
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