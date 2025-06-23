<?php
require 'connection.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'doctor') {
    header('Location: doctorlogin.php');
    exit();
}

$doctorId = $_SESSION['user_id'];
$patientId = $_GET['patient_id'] ?? 0;

// Get doctor info
$stmt = $pdo->prepare("SELECT u.name, d.specialty FROM users u JOIN doctors d ON u.id = d.user_id WHERE u.id = ?");
$stmt->execute([$doctorId]);
$doctor = $stmt->fetch();

// Get patient info
$stmt = $pdo->prepare("SELECT u.name, p.date_of_birth, p.gender FROM users u JOIN patients p ON u.id = p.user_id WHERE u.id = ?");
$stmt->execute([$patientId]);
$patient = $stmt->fetch();

if (!$patient) {
    header('Location: doctorappointments.php');
    exit();
}

// Calculate patient age
$patientAge = '';
if (!empty($patient['date_of_birth'])) {
    $dob = new DateTime($patient['date_of_birth']);
    $now = new DateTime();
    $patientAge = $now->diff($dob)->y;
}

// Get patient's medical history
$stmt = $pdo->prepare("SELECT a.id, a.appointment_date, a.status, 
                      u.name as doctor_name, d.specialty
                      FROM appointments a
                      JOIN doctors d ON a.doctor_id = d.user_id
                      JOIN users u ON d.user_id = u.id
                      WHERE a.patient_id = ? AND a.status = 'completed'
                      ORDER BY a.appointment_date DESC");
$stmt->execute([$patientId]);
$appointments = $stmt->fetchAll();

// Get patient's prescriptions
$stmt = $pdo->prepare("SELECT p.id, p.created_at, 
                      u.name as doctor_name, d.specialty
                      FROM prescriptions p
                      JOIN doctors d ON p.doctor_id = d.user_id
                      JOIN users u ON d.user_id = u.id
                      WHERE p.patient_id = ?
                      ORDER BY p.created_at DESC");
$stmt->execute([$patientId]);
$prescriptions = $stmt->fetchAll();

// Get patient's medical reports
$stmt = $pdo->prepare("SELECT mr.id, mr.report_name, mr.uploaded_at,
                      u.name as doctor_name
                      FROM medical_reports mr
                      LEFT JOIN doctors d ON mr.doctor_id = d.user_id
                      LEFT JOIN users u ON d.user_id = u.id
                      WHERE mr.patient_id = ?
                      ORDER BY mr.uploaded_at DESC");
$stmt->execute([$patientId]);
$reports = $stmt->fetchAll();

// Get unread notifications count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$doctorId]);
$unreadCount = $stmt->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Medical History - E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/doctordashboard.css">
    <style>
        .history-card {
            border-left: 4px solid var(--info);
        }
        
        .nav-pills .nav-link.active {
            background-color: var(--info);
        }
        
        .nav-pills .nav-link {
            color: var(--dark);
            border-radius: 8px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--light-gray);
            margin-bottom: 20px;
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
            
            <li class="nav-item active">
                <a class="nav-link" href="patientmedicalhistory.php">
                    <i class="fas fa-history"></i>
                    <span>Patient History</span>
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
            
            <div class="ml-auto d-flex align-items-center">
                <div class="dropdown mr-3">
                    <a href="doctornotifications.php" class="position-relative">
                        <i class="fas fa-bell fa-lg text-muted"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge badge-danger notification-badge"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
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
                <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Patient Medical History</h1>
                <a href="doctorappointments.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Appointments
                </a>
            </div>
            
            <!-- Patient Info Card -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h4><?= htmlspecialchars($patient['name']) ?></h4>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <p class="mb-1"><i class="fas fa-birthday-cake mr-2"></i> <?= $patientAge ? $patientAge.' years' : 'Age not specified' ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1"><i class="fas fa-venus-mars mr-2"></i> <?= $patient['gender'] ? ucfirst($patient['gender']) : 'Gender not specified' ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- History Tabs -->
            <ul class="nav nav-pills mb-4" id="historyTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="appointments-tab" data-toggle="pill" href="#appointments" role="tab">
                        <i class="fas fa-calendar-check mr-1"></i> Appointments
                        <span class="badge badge-primary ml-1"><?= count($appointments) ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="prescriptions-tab" data-toggle="pill" href="#prescriptions" role="tab">
                        <i class="fas fa-prescription-bottle-alt mr-1"></i> Prescriptions
                        <span class="badge badge-primary ml-1"><?= count($prescriptions) ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="reports-tab" data-toggle="pill" href="#reports" role="tab">
                        <i class="fas fa-file-medical mr-1"></i> Medical Reports
                        <span class="badge badge-primary ml-1"><?= count($reports) ?></span>
                    </a>
                </li>
            </ul>
            
            <div class="tab-content" id="historyTabsContent">
                <!-- Appointments Tab -->
                <div class="tab-pane fade show active" id="appointments" role="tabpanel">
                    <?php if (empty($appointments)): ?>
                        <div class="card shadow">
                            <div class="empty-state">
                                <i class="fas fa-calendar-times"></i>
                                <h4 class="text-muted">No completed appointments</h4>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card shadow">
                            <div class="card-body">
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
                                            <?php foreach ($appointments as $appt): ?>
                                                <tr>
                                                    <td><?= date('M j, Y', strtotime($appt['appointment_date'])) ?></td>
                                                    <td>Dr. <?= htmlspecialchars($appt['doctor_name']) ?></td>
                                                    <td><?= htmlspecialchars($appt['specialty']) ?></td>
                                                    <td>
                                                        <a href="appointmentdetails.php?id=<?= $appt['id'] ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye mr-1"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Prescriptions Tab -->
                <div class="tab-pane fade" id="prescriptions" role="tabpanel">
                    <?php if (empty($prescriptions)): ?>
                        <div class="card shadow">
                            <div class="empty-state">
                                <i class="fas fa-prescription-bottle-alt"></i>
                                <h4 class="text-muted">No prescriptions</h4>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card shadow">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Prescribed By</th>
                                                <th>Specialty</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($prescriptions as $pres): ?>
                                                <tr>
                                                    <td><?= date('M j, Y', strtotime($pres['created_at'])) ?></td>
                                                    <td>Dr. <?= htmlspecialchars($pres['doctor_name']) ?></td>
                                                    <td><?= htmlspecialchars($pres['specialty']) ?></td>
                                                    <td>
                                                        <a href="prescriptiondetails.php?id=<?= $pres['id'] ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye mr-1"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Reports Tab -->
                <div class="tab-pane fade" id="reports" role="tabpanel">
                    <?php if (empty($reports)): ?>
                        <div class="card shadow">
                            <div class="empty-state">
                                <i class="fas fa-file-medical"></i>
                                <h4 class="text-muted">No medical reports</h4>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card shadow">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Report Name</th>
                                                <th>Uploaded On</th>
                                                <th>Uploaded By</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($reports as $report): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($report['report_name']) ?></td>
                                                    <td><?= date('M j, Y', strtotime($report['uploaded_at'])) ?></td>
                                                    <td><?= $report['doctor_name'] ? 'Dr. '.htmlspecialchars($report['doctor_name']) : 'Patient' ?></td>
                                                    <td>
                                                        <a href="<?= htmlspecialchars($report['file_path']) ?>" class="btn btn-sm btn-primary" target="_blank">
                                                            <i class="fas fa-download mr-1"></i> Download
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
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