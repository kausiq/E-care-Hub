<?php
require 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$appointmentId = $_GET['id'] ?? null;

if (!$appointmentId) {
    header('Location: appointments.php');
    exit();
}

// Get appointment details
$stmt = $pdo->prepare("SELECT a.*, 
                      u.name as patient_name, p.phone as patient_phone,
                      doc.name as doctor_name, d.specialty as doctor_specialty
                      FROM appointments a
                      JOIN users u ON a.patient_id = u.id
                      JOIN patients p ON a.patient_id = p.user_id
                      JOIN doctors d ON a.doctor_id = d.user_id
                      JOIN users doc ON d.user_id = doc.id
                      WHERE a.id = ?");
$stmt->execute([$appointmentId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    header('Location: appointments.php');
    exit();
}

// Check if current user is either the doctor or patient of this appointment
if ($appointment['doctor_id'] != $userId && $appointment['patient_id'] != $userId) {
    header('Location: appointments.php');
    exit();
}

$isDoctor = ($_SESSION['user_role'] == 'doctor' && $appointment['doctor_id'] == $userId);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? $appointment['status'];
    $meetingLink = $_POST['meeting_link'] ?? $appointment['meeting_link'];
    $appointmentDate = $_POST['appointment_date'] ?? $appointment['appointment_date'];
    $appointmentTime = $_POST['appointment_time'] ?? $appointment['appointment_time'];
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE appointments 
                              SET status = ?, meeting_link = ?, appointment_date = ?, appointment_time = ?
                              WHERE id = ?");
        $stmt->execute([$status, $meetingLink, $appointmentDate, $appointmentTime, $appointmentId]);
        
        // Add notification
        $message = "Appointment updated: " . date('M j, Y', strtotime($appointmentDate)) . " at " . date('h:i A', strtotime($appointmentTime));
        
        // Notify both parties
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'appointment')");
        $stmt->execute([$appointment['patient_id'], $message]);
        
        if ($appointment['doctor_id'] != $userId) { // If patient is updating, notify doctor
            $stmt->execute([$appointment['doctor_id'], $message]);
        }
        
        $pdo->commit();
        
        header("Location: appointmentdetails.php?id=$appointmentId");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to update appointment: " . $e->getMessage();
    }
}

// Get unread notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$unreadNotifications = $stmt->fetchAll();
$unreadCount = count($unreadNotifications);

// Get recent notifications (both read and unread)
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$recentNotifications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Appointment - E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/doctordashboard.css">
    <style>
        .appointment-card {
            border-left: 4px solid var(--primary);
        }
        
        .patient-info-card {
            border-left: 4px solid var(--info);
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
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Update Appointment</h1>
                <a href="appointmentdetails.php?id=<?= $appointmentId ?>" class="d-none d-sm-inline-block btn btn-primary shadow-sm">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Details
                </a>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card shadow patient-info-card">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Appointment Information</h6>
                        </div>
                        <div class="card-body">
                            <p><strong>Current Status:</strong> 
                                <span class="badge badge-<?= strtolower($appointment['status']) ?>">
                                    <?= ucfirst($appointment['status']) ?>
                                </span>
                            </p>
                            <p><strong>Date:</strong> <?= date('M j, Y', strtotime($appointment['appointment_date'])) ?></p>
                            <p><strong>Time:</strong> <?= date('h:i A', strtotime($appointment['appointment_time'])) ?></p>
                            <?php if ($appointment['meeting_link']): ?>
                                <p><strong>Meeting Link:</strong> 
                                    <a href="<?= htmlspecialchars($appointment['meeting_link']) ?>" target="_blank">Join Meeting</a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card shadow mt-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold"><?= $isDoctor ? 'Patient' : 'Doctor' ?> Information</h6>
                        </div>
                        <div class="card-body">
                            <p><strong>Name:</strong> <?= $isDoctor ? htmlspecialchars($appointment['patient_name']) : 'Dr. ' . htmlspecialchars($appointment['doctor_name']) ?></p>
                            <p><strong><?= $isDoctor ? 'Phone' : 'Specialty' ?>:</strong> 
                                <?= $isDoctor ? htmlspecialchars($appointment['patient_phone']) : htmlspecialchars($appointment['doctor_specialty']) ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-8 mb-4">
                    <form method="POST" action="">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold">Update Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Appointment Date</label>
                                            <input type="date" name="appointment_date" class="form-control" 
                                                   value="<?= htmlspecialchars($appointment['appointment_date']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Appointment Time</label>
                                            <input type="time" name="appointment_time" class="form-control" 
                                                   value="<?= htmlspecialchars(substr($appointment['appointment_time'], 0, 5)) ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Meeting Link (for virtual appointments)</label>
                                    <input type="url" name="meeting_link" class="form-control" 
                                           value="<?= htmlspecialchars($appointment['meeting_link'] ?? '') ?>" 
                                           placeholder="https://meet.example.com/your-meeting-id">
                                </div>
                                
                                <?php if ($isDoctor): ?>
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="status" class="form-control" required>
                                            <option value="pending" <?= $appointment['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="confirmed" <?= $appointment['status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                            <option value="completed" <?= $appointment['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="cancelled" <?= $appointment['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                    </div>
                                <?php else: ?>
                                    <input type="hidden" name="status" value="<?= $appointment['status'] ?>">
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label>Notes (optional)</label>
                                    <textarea name="notes" class="form-control" rows="3" placeholder="Any additional notes..."></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-right mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i> Update Appointment
                            </button>
                        </div>
                    </form>
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
</body>
</html>