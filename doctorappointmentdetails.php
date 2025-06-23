<?php
require 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: doctorlogin.php');
    exit();
}

$doctorId = $_SESSION['user_id'];

// Check if appointment ID is provided
if (!isset($_GET['id'])) {
    header('Location: doctorappointments.php');
    exit();
}

$appointmentId = $_GET['id'];

// Get appointment details
$stmt = $pdo->prepare("SELECT a.*, u.name as patient_name, p.date_of_birth, p.gender, p.phone, p.address
                      FROM appointments a
                      JOIN users u ON a.patient_id = u.id
                      JOIN patients p ON u.id = p.user_id
                      WHERE a.id = ? AND a.doctor_id = ?");
$stmt->execute([$appointmentId, $doctorId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    header('Location: doctorappointments.php');
    exit();
}

// Get prescription if exists
$stmt = $pdo->prepare("SELECT p.* FROM prescriptions p 
                      WHERE p.appointment_id = ?");
$stmt->execute([$appointmentId]);
$prescription = $stmt->fetch();

// Get prescription medicines if prescription exists
$prescriptionMedicines = [];
if ($prescription) {
    $stmt = $pdo->prepare("SELECT * FROM prescription_medicines 
                          WHERE prescription_id = ?");
    $stmt->execute([$prescription['id']]);
    $prescriptionMedicines = $stmt->fetchAll();
}

// Get doctor info
$stmt = $pdo->prepare("SELECT u.name, d.specialty FROM users u 
                      JOIN doctors d ON u.id = d.user_id 
                      WHERE u.id = ?");
$stmt->execute([$doctorId]);
$doctor = $stmt->fetch();

// Get unread notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$doctorId]);
$unreadNotifications = $stmt->fetchAll();
$unreadCount = count($unreadNotifications);

// Get recent notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$doctorId]);
$recentNotifications = $stmt->fetchAll();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $newStatus = $_POST['status'];
        $meetingLink = $_POST['meeting_link'] ?? null;
        
        $stmt = $pdo->prepare("UPDATE appointments SET status = ?, meeting_link = ? WHERE id = ? AND doctor_id = ?");
        if ($stmt->execute([$newStatus, $meetingLink, $appointmentId, $doctorId])) {
            // Add notification
            $message = "Appointment status updated to " . $newStatus;
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'appointment')");
            $stmt->execute([$doctorId, $message]);
            
            header("Location: doctorappointmentdetails.php?id=" . $appointmentId);
            exit();
        }
    }
    
    if (isset($_POST['add_prescription'])) {
        $notes = $_POST['notes'] ?? '';
        $advice = $_POST['advice'] ?? '';
        
        $pdo->beginTransaction();
        
        try {
            // Create prescription
            $stmt = $pdo->prepare("INSERT INTO prescriptions (appointment_id, doctor_id, patient_id, notes, advice) 
                                 VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$appointmentId, $doctorId, $appointment['patient_id'], $notes, $advice]);
            $prescriptionId = $pdo->lastInsertId();
            
            // Add medicines
            if (isset($_POST['medicines'])) {
                foreach ($_POST['medicines'] as $medicine) {
                    if (!empty($medicine['name'])) {
                        $stmt = $pdo->prepare("INSERT INTO prescription_medicines 
                                              (prescription_id, medicine_name, dosage, frequency, duration) 
                                              VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $prescriptionId,
                            $medicine['name'],
                            $medicine['dosage'] ?? '',
                            $medicine['frequency'] ?? '',
                            $medicine['duration'] ?? ''
                        ]);
                    }
                }
            }
            
            $pdo->commit();
            
            // Add notification
            $message = "New prescription added for " . $appointment['patient_name'];
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'prescription')");
            $stmt->execute([$doctorId, $message]);
            
            header("Location: doctorappointmentdetails.php?id=" . $appointmentId);
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to add prescription: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['mark_as_read'])) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$doctorId]);
        header("Location: doctorappointmentdetails.php?id=" . $appointmentId);
        exit();
    }
}

// Calculate patient age
$age = '';
if (!empty($appointment['date_of_birth'])) {
    $dob = new DateTime($appointment['date_of_birth']);
    $now = new DateTime();
    $age = $now->diff($dob)->y;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details - E Care Hub</title>
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
        
        /* Status Badges */
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
        
        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        /* Patient Info Styles */
        .patient-info-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .patient-info-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        /* Medicine List Styles */
        .medicine-item {
            border-left: 3px solid var(--info);
            padding-left: 15px;
            margin-bottom: 15px;
        }
        
        .medicine-name {
            font-weight: 600;
            color: var(--dark);
        }
        
        .medicine-details {
            font-size: 0.9rem;
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
        
        /* Form Styles */
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid var(--light-gray);
        }
        
        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-heart text-white brand-heart p-2"></i>
            <span class="sidebar-brand-text ms-2">E Care Hub</span>
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
            
            <li class="nav-item">
                <a class="nav-link" href="doctorpatients.php">
                    <i class="fas fa-user-injured"></i>
                    <span>Patients</span>
                </a>
            </li>
            
            <li class="nav-item active">
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
                        <h6 class="dropdown-header">Welcome, Doctor!</h6>
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
                <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Appointment Details</h1>
                <a href="doctorappointments.php" class="d-none d-sm-inline-block btn btn-primary shadow-sm">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Appointments
                </a>
            </div>
            
            <div class="row">
                <!-- Appointment Details Column -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Appointment Information</h6>
                            <span class="badge badge-<?= strtolower($appointment['status']) ?>">
                                <?= ucfirst($appointment['status']) ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5 class="mb-3">Appointment Time</h5>
                                    <p>
                                        <i class="far fa-calendar-alt mr-2 text-primary"></i>
                                        <?= date('F j, Y', strtotime($appointment['appointment_date'])) ?>
                                    </p>
                                    <p>
                                        <i class="far fa-clock mr-2 text-primary"></i>
                                        <?= date('h:i A', strtotime($appointment['appointment_time'])) ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-3">Meeting Information</h5>
                                    <?php if (!empty($appointment['meeting_link'])): ?>
                                        <p>
                                            <i class="fas fa-video mr-2 text-primary"></i>
                                            <a href="<?= htmlspecialchars($appointment['meeting_link']) ?>" target="_blank">
                                                Join Consultation
                                            </a>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-muted">
                                            <i class="fas fa-video mr-2"></i>
                                            No meeting link provided
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label>Update Status</label>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <select name="status" class="form-control p-0" required>
                                                <option value="pending" <?= $appointment['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="confirmed" <?= $appointment['status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                <option value="completed" <?= $appointment['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                                <option value="cancelled" <?= $appointment['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <input type="text" name="meeting_link" class="form-control" 
                                                   placeholder="Meeting Link (if applicable)" 
                                                   value="<?= htmlspecialchars($appointment['meeting_link'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <button type="submit" name="update_status" class="btn btn-primary">
                                        <i class="fas fa-save mr-2"></i> Update Status
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Prescription Section -->
                    <div class="card shadow mt-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Prescription</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($prescription): ?>
                                <div class="mb-4">
                                    <h5 class="mb-3">Doctor's Notes</h5>
                                    <p><?= !empty($prescription['notes']) ? nl2br(htmlspecialchars($prescription['notes'])) : 'No notes provided' ?></p>
                                    
                                    <h5 class="mb-3">Medical Advice</h5>
                                    <p><?= !empty($prescription['advice']) ? nl2br(htmlspecialchars($prescription['advice'])) : 'No advice provided' ?></p>
                                </div>
                                
                                <h5 class="mb-3">Prescribed Medicines</h5>
                                <?php if (!empty($prescriptionMedicines)): ?>
                                    <div class="mb-4">
                                        <?php foreach ($prescriptionMedicines as $medicine): ?>
                                            <div class="medicine-item">
                                                <h6 class="medicine-name"><?= htmlspecialchars($medicine['medicine_name']) ?></h6>
                                                <div class="medicine-details">
                                                    <span class="mr-3"><strong>Dosage:</strong> <?= htmlspecialchars($medicine['dosage']) ?></span>
                                                    <span class="mr-3"><strong>Frequency:</strong> <?= htmlspecialchars($medicine['frequency']) ?></span>
                                                    <span><strong>Duration:</strong> <?= htmlspecialchars($medicine['duration']) ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No medicines prescribed</p>
                                <?php endif; ?>
                                
                                <div class="text-right">
                                    <a href="prescriptionpdf.php?id=<?= $prescription['id'] ?>" class="btn btn-primary" target="_blank">
                                        <i class="fas fa-file-pdf mr-2"></i> Generate PDF
                                    </a>
                                </div>
                            <?php else: ?>
                                <form method="POST" action="">
                                    <div class="form-group">
                                        <label>Doctor's Notes</label>
                                        <textarea name="notes" class="form-control" rows="3"></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Medical Advice</label>
                                        <textarea name="advice" class="form-control" rows="3"></textarea>
                                    </div>
                                    
                                    <h5 class="mb-3">Prescribed Medicines</h5>
                                    <div id="medicines-container">
                                        <div class="medicine-form-group mb-3">
                                            <div class="row">
                                                <div class="col-md-4 mb-2">
                                                    <input type="text" name="medicines[0][name]" class="form-control" placeholder="Medicine Name">
                                                </div>
                                                <div class="col-md-2 mb-2">
                                                    <input type="text" name="medicines[0][dosage]" class="form-control" placeholder="Dosage">
                                                </div>
                                                <div class="col-md-3 mb-2">
                                                    <input type="text" name="medicines[0][frequency]" class="form-control" placeholder="Frequency">
                                                </div>
                                                <div class="col-md-3 mb-2">
                                                    <input type="text" name="medicines[0][duration]" class="form-control" placeholder="Duration">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <button type="button" id="add-medicine" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-plus mr-1"></i> Add Another Medicine
                                        </button>
                                    </div>
                                    
                                    <div class="text-right">
                                        <button type="submit" name="add_prescription" class="btn btn-primary">
                                            <i class="fas fa-prescription-bottle-alt mr-2"></i> Create Prescription
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Patient Information Column -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Patient Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <div class="user-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                                    <?= strtoupper(substr($appointment['patient_name'], 0, 1)) ?>
                                </div>
                                <h4><?= htmlspecialchars($appointment['patient_name']) ?></h4>
                            </div>
                            
                            <div class="patient-info-item">
                                <div class="patient-info-icon">
                                    <i class="fas fa-birthday-cake"></i>
                                </div>
                                <div>
                                    <p class="mb-0 text-muted small">Age</p>
                                    <p class="mb-0 font-weight-bold"><?= $age ? $age . ' years' : 'Not specified' ?></p>
                                </div>
                            </div>
                            
                            <div class="patient-info-item">
                                <div class="patient-info-icon">
                                    <i class="fas fa-venus-mars"></i>
                                </div>
                                <div>
                                    <p class="mb-0 text-muted small">Gender</p>
                                    <p class="mb-0 font-weight-bold"><?= $appointment['gender'] ? ucfirst($appointment['gender']) : 'Not specified' ?></p>
                                </div>
                            </div>
                            
                            <div class="patient-info-item">
                                <div class="patient-info-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div>
                                    <p class="mb-0 text-muted small">Phone</p>
                                    <p class="mb-0 font-weight-bold"><?= $appointment['phone'] ? htmlspecialchars($appointment['phone']) : 'Not specified' ?></p>
                                </div>
                            </div>
                            
                            <div class="patient-info-item">
                                <div class="patient-info-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <p class="mb-0 text-muted small">Address</p>
                                    <p class="mb-0 font-weight-bold"><?= $appointment['address'] ? htmlspecialchars($appointment['address']) : 'Not specified' ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="card shadow mt-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <a href="patientmedicalhistory.php?id=<?= $appointment['patient_id'] ?>" class="btn btn-outline-primary btn-block mb-2">
                                <i class="fas fa-history mr-2"></i> View Medical History
                            </a>
                            <a href="patientreports.php?id=<?= $appointment['patient_id'] ?>" class="btn btn-outline-info btn-block mb-2">
                                <i class="fas fa-file-medical mr-2"></i> View Medical Reports
                            </a>
                            <a href="patientprescriptions.php?id=<?= $appointment['patient_id'] ?>" class="btn btn-outline-success btn-block">
                                <i class="fas fa-prescription-bottle-alt mr-2"></i> View Past Prescriptions
                            </a>
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
        
        // Add medicine form fields
        let medicineCount = 1;
        $('#add-medicine').click(function() {
            const html = `
                <div class="medicine-form-group mb-3">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <input type="text" name="medicines[${medicineCount}][name]" class="form-control" placeholder="Medicine Name">
                        </div>
                        <div class="col-md-2 mb-2">
                            <input type="text" name="medicines[${medicineCount}][dosage]" class="form-control" placeholder="Dosage">
                        </div>
                        <div class="col-md-3 mb-2">
                            <input type="text" name="medicines[${medicineCount}][frequency]" class="form-control" placeholder="Frequency">
                        </div>
                        <div class="col-md-3 mb-2">
                            <input type="text" name="medicines[${medicineCount}][duration]" class="form-control" placeholder="Duration">
                        </div>
                    </div>
                </div>
            `;
            $('#medicines-container').append(html);
            medicineCount++;
        });
    </script>
</body>
</html>