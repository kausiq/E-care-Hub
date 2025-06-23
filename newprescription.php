<?php
require 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
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

// Get appointment details if coming from an appointment
$appointmentId = $_GET['appointment_id'] ?? null;
$patientId = null;
$appointmentDetails = null;

if ($appointmentId) {
    $stmt = $pdo->prepare("SELECT a.*, u.name as patient_name 
                          FROM appointments a
                          JOIN users u ON a.patient_id = u.id
                          WHERE a.id = ? AND a.doctor_id = ?");
    $stmt->execute([$appointmentId, $doctorId]);
    $appointmentDetails = $stmt->fetch();
    
    if ($appointmentDetails) {
        $patientId = $appointmentDetails['patient_id'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientId = $_POST['patient_id'];
    $appointmentId = $_POST['appointment_id'] ?? null;
    $notes = $_POST['notes'] ?? '';
    $advice = $_POST['advice'] ?? '';
    
    $medicines = $_POST['medicines'] ?? [];
    $dosages = $_POST['dosages'] ?? [];
    $frequencies = $_POST['frequencies'] ?? [];
    $durations = $_POST['durations'] ?? [];
    
    try {
        $pdo->beginTransaction();
        
        // Create prescription
        $stmt = $pdo->prepare("INSERT INTO prescriptions (appointment_id, doctor_id, patient_id, notes, advice) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$appointmentId, $doctorId, $patientId, $notes, $advice]);
        $prescriptionId = $pdo->lastInsertId();
        
        // Add medicines
        $stmt = $pdo->prepare("INSERT INTO prescription_medicines (prescription_id, medicine_name, dosage, frequency, duration) 
                              VALUES (?, ?, ?, ?, ?)");
        
        foreach ($medicines as $index => $medicine) {
            if (!empty($medicine)) {
                $stmt->execute([
                    $prescriptionId,
                    $medicine,
                    $dosages[$index] ?? '',
                    $frequencies[$index] ?? '',
                    $durations[$index] ?? ''
                ]);
            }
        }
        
        // Update appointment status if coming from appointment
        if ($appointmentId) {
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?");
            $stmt->execute([$appointmentId]);
        }
        
        // Add notification
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$patientId]);
        $patient = $stmt->fetch();
        
        $message = "New prescription created by Dr. " . $_SESSION['user_name'] . " for " . $patient['name'];
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'prescription')");
        $stmt->execute([$patientId, $message]);
        
        $pdo->commit();
        
        header("Location: prescriptiondetails.php?id=$prescriptionId");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to create prescription: " . $e->getMessage();
    }
}

// Get patient info if set
$patientInfo = null;
if ($patientId) {
    $stmt = $pdo->prepare("SELECT u.name, p.date_of_birth, p.gender 
                          FROM users u 
                          JOIN patients p ON u.id = p.user_id 
                          WHERE u.id = ?");
    $stmt->execute([$patientId]);
    $patientInfo = $stmt->fetch();
}

// Get recent patients for dropdown
$stmt = $pdo->prepare("SELECT DISTINCT p.patient_id, u.name 
                      FROM prescriptions p
                      JOIN users u ON p.patient_id = u.id
                      WHERE p.doctor_id = ?
                      ORDER BY p.created_at DESC
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
    <title>New Prescription - E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/doctordashboard.css">
    <style>
        .medicine-row {
            margin-bottom: 15px;
            padding: 15px;
            background-color: rgba(67, 97, 238, 0.05);
            border-radius: 8px;
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
                <h1 class="h3 mb-0 text-gray-800 font-weight-bold">New Prescription</h1>
                <a href="prescriptions.php" class="d-none d-sm-inline-block btn btn-primary shadow-sm">
                    <i class="fas fa-list mr-2"></i> View All Prescriptions
                </a>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card shadow patient-info-card">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Patient Information</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($patientInfo): ?>
                                <div class="form-group">
                                    <label>Patient Name</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($patientInfo['name']) ?>" readonly>
                                    <input type="hidden" name="patient_id" value="<?= $patientId ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Date of Birth</label>
                                    <input type="text" class="form-control" value="<?= $patientInfo['date_of_birth'] ? htmlspecialchars($patientInfo['date_of_birth']) : 'Not specified' ?>" readonly>
                                </div>
                                
                                <div class="form-group">
                                    <label>Gender</label>
                                    <input type="text" class="form-control" value="<?= $patientInfo['gender'] ? ucfirst($patientInfo['gender']) : 'Not specified' ?>" readonly>
                                </div>
                            <?php elseif ($recentPatients): ?>
                                <div class="form-group">
                                    <label>Select Recent Patient</label>
                                    <select class="form-control" id="recentPatientSelect">
                                        <option value="">Select Patient</option>
                                        <?php foreach ($recentPatients as $patient): ?>
                                            <option value="<?= $patient['patient_id'] ?>"><?= htmlspecialchars($patient['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No recent patients found</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($appointmentDetails): ?>
                        <div class="card shadow mt-4">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold">Appointment Details</h6>
                            </div>
                            <div class="card-body">
                                <p>
                                    <strong>Date:</strong> <?= date('M j, Y', strtotime($appointmentDetails['appointment_date'])) ?>
                                </p>
                                <p>
                                    <strong>Time:</strong> <?= date('h:i A', strtotime($appointmentDetails['appointment_time'])) ?>
                                </p>
                                <input type="hidden" name="appointment_id" value="<?= $appointmentId ?>">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-lg-8 mb-4">
                    <form method="POST" action="">
                        <input type="hidden" name="patient_id" value="<?= $patientId ?>">
                        <?php if ($appointmentId): ?>
                            <input type="hidden" name="appointment_id" value="<?= $appointmentId ?>">
                        <?php endif; ?>
                        
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold">Prescription Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Clinical Notes</label>
                                    <textarea name="notes" class="form-control" rows="4" placeholder="Enter clinical notes..."></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Medical Advice</label>
                                    <textarea name="advice" class="form-control" rows="4" placeholder="Enter medical advice..."></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card shadow mt-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold">Medicines</h6>
                                <button type="button" class="btn btn-sm btn-primary" id="addMedicine">
                                    <i class="fas fa-plus mr-1"></i> Add Medicine
                                </button>
                            </div>
                            <div class="card-body" id="medicinesContainer">
                                <div class="medicine-row">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Medicine Name</label>
                                                <input type="text" name="medicines[]" class="form-control" placeholder="e.g. Paracetamol">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Dosage</label>
                                                <input type="text" name="dosages[]" class="form-control" placeholder="e.g. 500mg">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Frequency</label>
                                                <input type="text" name="frequencies[]" class="form-control" placeholder="e.g. Twice daily">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Duration</label>
                                                <input type="text" name="durations[]" class="form-control" placeholder="e.g. 7 days">
                                            </div>
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end">
                                            <button type="button" class="btn btn-sm btn-danger remove-medicine" disabled>
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-right mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i> Save Prescription
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
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
        $(document).ready(function() {
            // Add new medicine row
            $('#addMedicine').click(function() {
                const newRow = `
                    <div class="medicine-row">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <input type="text" name="medicines[]" class="form-control" placeholder="e.g. Paracetamol">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <input type="text" name="dosages[]" class="form-control" placeholder="e.g. 500mg">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <input type="text" name="frequencies[]" class="form-control" placeholder="e.g. Twice daily">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <input type="text" name="durations[]" class="form-control" placeholder="e.g. 7 days">
                                </div>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-sm btn-danger remove-medicine">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                $('#medicinesContainer').append(newRow);
                
                // Enable remove button on first row if we have multiple rows now
                if ($('.medicine-row').length > 1) {
                    $('.medicine-row').first().find('.remove-medicine').prop('disabled', false);
                }
            });
            
            // Remove medicine row
            $(document).on('click', '.remove-medicine', function() {
                $(this).closest('.medicine-row').remove();
                
                // Disable remove button on first row if only one row left
                if ($('.medicine-row').length === 1) {
                    $('.medicine-row').first().find('.remove-medicine').prop('disabled', true);
                }
            });
            
            // Handle recent patient selection
            $('#recentPatientSelect').change(function() {
                $('input[name="patient_id"]').val($(this).val());
            });
        });
    </script>
</body>
</html>