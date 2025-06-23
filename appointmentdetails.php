<?php
require 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$appointmentId = $_GET['id'] ?? 0;

// Get appointment details
$stmt = $pdo->prepare("SELECT a.*, 
                      u1.name as doctor_name, d.specialty, d.consultation_fee,
                      u2.name as patient_name, p.date_of_birth, p.gender, p.phone
                      FROM appointments a
                      LEFT JOIN doctors d ON a.doctor_id = d.user_id
                      LEFT JOIN users u1 ON d.user_id = u1.id
                      LEFT JOIN patients p ON a.patient_id = p.user_id
                      LEFT JOIN users u2 ON p.user_id = u2.id
                      WHERE a.id = ?");
$stmt->execute([$appointmentId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    header('Location: '.($_SESSION['role'] == 'doctor' ? 'doctorappointments.php' : 'appointments.php'));
    exit();
}

// Check if user has access to this appointment
if ($_SESSION['role'] == 'doctor' && $appointment['doctor_id'] != $userId) {
    header('Location: doctorappointments.php');
    exit();
} elseif ($_SESSION['role'] == 'patient' && $appointment['patient_id'] != $userId) {
    header('Location: appointments.php');
    exit();
}

// Calculate patient age
$patientAge = '';
if (!empty($appointment['date_of_birth'])) {
    $dob = new DateTime($appointment['date_of_birth']);
    $now = new DateTime();
    $patientAge = $now->diff($dob)->y;
}

// Get prescription if exists
$prescription = null;
if ($appointment['status'] == 'completed') {
    $stmt = $pdo->prepare("SELECT * FROM prescriptions WHERE appointment_id = ?");
    $stmt->execute([$appointmentId]);
    $prescription = $stmt->fetch();
}

// Get user info for header
$table = $_SESSION['role'] == 'doctor' ? 'doctors' : 'patients';
$stmt = $pdo->prepare("SELECT u.name, u.email FROM users u JOIN $table t ON u.id = t.user_id WHERE u.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Get unread notifications count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$userId]);
$unreadCount = $stmt->fetch()['count'];
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
    <link rel="stylesheet" href="css/doctordashboard.css">
    <style>
        .detail-card {
            border-left: 4px solid var(--primary);
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
        
        .prescription-item {
            border-left: 3px solid var(--info);
            padding-left: 10px;
            margin-bottom: 15px;
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
                <a class="nav-link" href="<?= $_SESSION['role'] == 'doctor' ? 'doctordashboard.php' : 'patientdashboard.php' ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <?php if ($_SESSION['role'] == 'doctor'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="doctorschedule.php">
                        <i class="fas fa-calendar-alt"></i>
                        <span>My Schedule</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="doctorappointments.php">
                        <i class="fas fa-calendar-check"></i>
                        <span>Appointments</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="newprescription.php">
                        <i class="fas fa-prescription-bottle-alt"></i>
                        <span>New Prescription</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="patientmedicalhistory.php">
                        <i class="fas fa-history"></i>
                        <span>Patient History</span>
                    </a>
                </li>
            <?php else: ?>
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
            <?php endif; ?>
            
            <li class="nav-item mt-4">
                <a class="nav-link" href="<?= $_SESSION['role'] == 'doctor' ? 'doctorprof.php' : 'profile.php' ?>">
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
            
            <div class="ml-auto d-flex align-items-center">
                <div class="dropdown mr-3">
                    <a href="<?= $_SESSION['role'] == 'doctor' ? 'doctornotifications.php' : 'notifications.php' ?>" class="position-relative">
                        <i class="fas fa-bell fa-lg text-muted"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge badge-danger notification-badge"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <div class="topbar-divider d-none d-sm-block"></div>
                
                <div class="dropdown">
                    <div class="user-dropdown" data-toggle="dropdown">
                        <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                        <div class="d-none d-lg-block">
                            <div class="user-name"><?= $_SESSION['role'] == 'doctor' ? 'Dr. ' : '' ?><?= htmlspecialchars($user['name']) ?></div>
                            <small class="text-muted"><?= ucfirst($_SESSION['role']) ?></small>
                        </div>
                    </div>
                    <div class="dropdown-menu dropdown-menu-right shadow">
                        <h6 class="dropdown-header">Welcome!</h6>
                        <a class="dropdown-item" href="<?= $_SESSION['role'] == 'doctor' ? 'doctorprof.php' : 'profile.php' ?>">
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
                <a href="<?= $_SESSION['role'] == 'doctor' ? 'doctorappointments.php' : 'appointments.php' ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Appointments
                </a>
            </div>
            
            <div class="row">
                <!-- Appointment Details -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow detail-card">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Appointment Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5 class="mb-3">
                                        <?php if ($_SESSION['role'] == 'doctor'): ?>
                                            <i class="fas fa-user mr-2"></i> Patient: <?= htmlspecialchars($appointment['patient_name']) ?>
                                        <?php else: ?>
                                            <i class="fas fa-user-md mr-2"></i> Doctor: Dr. <?= htmlspecialchars($appointment['doctor_name']) ?>
                                        <?php endif; ?>
                                    </h5>
                                    
                                    <p class="mb-2">
                                        <i class="fas fa-calendar-alt mr-2"></i> 
                                        <?= date('l, F j, Y', strtotime($appointment['appointment_date'])) ?>
                                    </p>
                                    <p class="mb-2">
                                        <i class="fas fa-clock mr-2"></i> 
                                        <?= date('h:i A', strtotime($appointment['appointment_time'])) ?>
                                    </p>
                                    <p class="mb-0">
                                        <span class="badge badge-<?= strtolower($appointment['status']) ?>">
                                            <?= ucfirst($appointment['status']) ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <?php if ($_SESSION['role'] == 'doctor'): ?>
                                        <p class="mb-2">
                                            <i class="fas fa-birthday-cake mr-2"></i> 
                                            <?= $patientAge ? $patientAge.' years' : 'Age not specified' ?>
                                        </p>
                                        <p class="mb-2">
                                            <i class="fas fa-venus-mars mr-2"></i> 
                                            <?= $appointment['gender'] ? ucfirst($appointment['gender']) : 'Gender not specified' ?>
                                        </p>
                                        <p class="mb-2">
                                            <i class="fas fa-phone mr-2"></i> 
                                            <?= $appointment['phone'] ? htmlspecialchars($appointment['phone']) : 'Phone not specified' ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="mb-2">
                                            <i class="fas fa-stethoscope mr-2"></i> 
                                            <?= htmlspecialchars($appointment['specialty']) ?>
                                        </p>
                                        <p class="mb-2">
                                            <i class="fas fa-money-bill-wave mr-2"></i> 
                                            $<?= number_format($appointment['consultation_fee'], 2) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($appointment['status'] == 'confirmed' && !empty($appointment['meeting_link'])): ?>
                                <div class="alert alert-info">
                                    <h5><i class="fas fa-video mr-2"></i> Virtual Consultation</h5>
                                    <p>Join your appointment using the link below:</p>
                                    <a href="<?= htmlspecialchars($appointment['meeting_link']) ?>" class="btn btn-success" target="_blank">
                                        <i class="fas fa-video mr-2"></i> Join Meeting
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($_SESSION['role'] == 'doctor' && $appointment['status'] == 'pending'): ?>
                                <div class="text-right">
                                    <form method="POST" action="updateappointment.php" class="d-inline">
                                        <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                        <input type="hidden" name="status" value="confirmed">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-check mr-2"></i> Confirm Appointment
                                        </button>
                                    </form>
                                    <form method="POST" action="updateappointment.php" class="d-inline">
                                        <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" class="btn btn-outline-danger">
                                            <i class="fas fa-times mr-2"></i> Cancel Appointment
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Prescription Section -->
                    <?php if ($prescription): ?>
                        <div class="card shadow mt-4">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold">Prescription</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <h5>Doctor's Notes</h5>
                                    <p><?= nl2br(htmlspecialchars($prescription['notes'])) ?></p>
                                </div>
                                
                                <div class="mb-4">
                                    <h5>Advice</h5>
                                    <p><?= nl2br(htmlspecialchars($prescription['advice'])) ?></p>
                                </div>
                                
                                <div>
                                    <h5>Medicines</h5>
                                    <?php 
                                    $stmt = $pdo->prepare("SELECT * FROM prescription_medicines WHERE prescription_id = ?");
                                    $stmt->execute([$prescription['id']]);
                                    $medicines = $stmt->fetchAll();
                                    
                                    if ($medicines): ?>
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Medicine</th>
                                                        <th>Dosage</th>
                                                        <th>Frequency</th>
                                                        <th>Duration</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($medicines as $medicine): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($medicine['medicine_name']) ?></td>
                                                            <td><?= htmlspecialchars($medicine['dosage']) ?></td>
                                                            <td><?= htmlspecialchars($medicine['frequency']) ?></td>
                                                            <td><?= htmlspecialchars($medicine['duration']) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p>No medicines prescribed.</p>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($_SESSION['role'] == 'patient'): ?>
                                    <div class="text-right mt-4">
                                        <a href="prescriptionpdf.php?id=<?= $prescription['id'] ?>" class="btn btn-primary" target="_blank">
                                            <i class="fas fa-file-pdf mr-2"></i> Download Prescription
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php elseif ($_SESSION['role'] == 'doctor' && $appointment['status'] == 'completed' && !$prescription): ?>
                        <div class="card shadow mt-4">
                            <div class="card-body text-center">
                                <i class="fas fa-prescription-bottle-alt fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Prescription Created</h5>
                                <a href="newprescription.php?appointment_id=<?= $appointment['id'] ?>" class="btn btn-primary mt-3">
                                    <i class="fas fa-plus mr-2"></i> Create Prescription
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Actions & Notes -->
                <div class="col-lg-4 mb-4">
                    <?php if ($_SESSION['role'] == 'doctor' && $appointment['status'] != 'cancelled'): ?>
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold">Appointment Actions</h6>
                            </div>
                            <div class="card-body">
                                <?php if ($appointment['status'] == 'completed'): ?>
                                    <a href="newprescription.php?appointment_id=<?= $appointment['id'] ?>" class="btn btn-primary btn-block mb-3">
                                        <i class="fas fa-prescription-bottle-alt mr-2"></i> Create Prescription
                                    </a>
                                <?php else: ?>
                                    <form method="POST" action="updateappointment.php" class="mb-3">
                                        <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                        <div class="form-group">
                                            <label>Meeting Link</label>
                                            <input type="text" name="meeting_link" class="form-control" 
                                                   value="<?= htmlspecialchars($appointment['meeting_link'] ?? '') ?>" 
                                                   placeholder="https://meet.example.com/your-room">
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-save mr-2"></i> Save Meeting Link
                                        </button>
                                    </form>
                                    
                                    <?php if ($appointment['status'] != 'completed'): ?>
                                        <form method="POST" action="updateappointment.php">
                                            <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="btn btn-success btn-block">
                                                <i class="fas fa-check-circle mr-2"></i> Mark as Completed
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Patient Medical History (Doctor View) -->
                    <?php if ($_SESSION['role'] == 'doctor'): ?>
                        <div class="card shadow mt-4">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold">Patient Medical History</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                $stmt = $pdo->prepare("SELECT a.id, a.appointment_date, a.status 
                                                      FROM appointments a
                                                      WHERE a.patient_id = ? AND a.id != ? AND a.status = 'completed'
                                                      ORDER BY a.appointment_date DESC
                                                      LIMIT 5");
                                $stmt->execute([$appointment['patient_id'], $appointment['id']]);
                                $history = $stmt->fetchAll();
                                ?>
                                
                                <?php if ($history): ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($history as $item): ?>
                                            <a href="appointmentdetails.php?id=<?= $item['id'] ?>" class="list-group-item list-group-item-action">
                                                <?= date('M j, Y', strtotime($item['appointment_date'])) ?>
                                                <span class="badge badge-completed float-right">Completed</span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="patientmedicalhistory.php?patient_id=<?= $appointment['patient_id'] ?>" class="btn btn-sm btn-outline-primary">
                                            View Full History
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No previous appointments found</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
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