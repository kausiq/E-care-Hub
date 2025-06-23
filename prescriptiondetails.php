<?php
require 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$prescriptionId = $_GET['id'] ?? null;
// Get doctor information
$stmt = $pdo->prepare("SELECT u.name, u.email, d.specialty, d.languages, d.location, d.bio, d.consultation_fee 
                       FROM users u JOIN doctors d ON u.id = d.user_id 
                       WHERE u.id = ?");
$stmt->execute([$userId]);
$doctor = $stmt->fetch();

if (!$prescriptionId) {
    header('Location: dashboard.php');
    exit();
}

// Get prescription details
$stmt = $pdo->prepare("SELECT p.*, 
                      u.name as doctor_name, d.specialty as doctor_specialty,
                      pat.name as patient_name
                      FROM prescriptions p
                      JOIN users u ON p.doctor_id = u.id
                      JOIN doctors d ON p.doctor_id = d.user_id
                      JOIN users pat ON p.patient_id = pat.id
                      WHERE p.id = ?");
$stmt->execute([$prescriptionId]);
$prescription = $stmt->fetch();

if (!$prescription) {
    header('Location: dashboard.php');
    exit();
}

// Check if current user is either the doctor who created it or the patient it's for
if ($prescription['doctor_id'] != $userId && $prescription['patient_id'] != $userId) {
    header('Location: dashboard.php');
    exit();
}

// Get medicines
$stmt = $pdo->prepare("SELECT * FROM prescription_medicines WHERE prescription_id = ?");
$stmt->execute([$prescriptionId]);
$medicines = $stmt->fetchAll();

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
    <title>Prescription Details - E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/doctordashboard.css">
    <style>
        .prescription-header {
            border-bottom: 2px solid var(--primary);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .prescription-body {
            margin-bottom: 30px;
        }
        
        .medicine-card {
            border-left: 3px solid var(--info);
            margin-bottom: 15px;
        }
        
        .prescription-footer {
            border-top: 2px solid var(--primary);
            padding-top: 20px;
        }
        
        .signature-box {
            border-top: 1px solid var(--dark);
            width: 250px;
            margin-top: 50px;
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
        <!-- <nav class="topbar">
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
        </nav> -->
        
        <!-- Page Content -->
        <div class="container-fluid py-4">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Prescription Details</h1>
                <div>
                    <a href="prescriptionpdf.php?id=<?= $prescriptionId ?>" class="btn btn-primary mr-2" target="_blank">
                        <i class="fas fa-file-pdf mr-2"></i> Download PDF
                    </a>
                    <?php if ($prescription['doctor_id'] == $userId): ?>
                        <a href="newprescription.php?appointment_id=<?= $prescription['appointment_id'] ?>" class="btn btn-success">
                            <i class="fas fa-edit mr-2"></i> Create Similar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card shadow">
                <div class="card-body">
                    <div class="prescription-header">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="text-primary">E Care Hub</h4>
                                <p class="mb-0">123 Medical Street</p>
                                <p class="mb-0">Healthcare City, HC 12345</p>
                                <p class="mb-0">Phone: (123) 456-7890</p>
                            </div>
                            <div class="col-md-6 text-right">
                                <h4 class="text-primary">PRESCRIPTION</h4>
                                <p class="mb-0"><strong>Date:</strong> <?= date('M j, Y', strtotime($prescription['created_at'])) ?></p>
                                <p class="mb-0"><strong>Prescription ID:</strong> <?= $prescription['id'] ?></p>
                                <?php if ($prescription['appointment_id']): ?>
                                    <p class="mb-0"><strong>Appointment ID:</strong> <?= $prescription['appointment_id'] ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row prescription-body">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="m-0 font-weight-bold">Patient Information</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Name:</strong> <?= htmlspecialchars($prescription['patient_name']) ?></p>
                                    <p><strong>Prescribed On:</strong> <?= date('M j, Y', strtotime($prescription['created_at'])) ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="m-0 font-weight-bold">Doctor Information</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Name:</strong> Dr. <?= htmlspecialchars($prescription['doctor_name']) ?></p>
                                    <p><strong>Specialty:</strong> <?= htmlspecialchars($prescription['doctor_specialty']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="text-primary mb-3">Medicines Prescribed</h5>
                        
                        <?php if (empty($medicines)): ?>
                            <div class="alert alert-info">No medicines prescribed</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Medicine Name</th>
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
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($prescription['notes'])): ?>
                        <div class="mb-4">
                            <h5 class="text-primary mb-3">Clinical Notes</h5>
                            <div class="card">
                                <div class="card-body">
                                    <?= nl2br(htmlspecialchars($prescription['notes'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($prescription['advice'])): ?>
                        <div class="mb-4">
                            <h5 class="text-primary mb-3">Medical Advice</h5>
                            <div class="card">
                                <div class="card-body">
                                    <?= nl2br(htmlspecialchars($prescription['advice'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="prescription-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Pharmacy Instructions</h5>
                                <p>Please dispense as written. No substitutions allowed.</p>
                            </div>
                            <div class="col-md-6 text-right">
                                <div class="signature-box">
                                    <p class="text-center mb-0">Dr. <?= htmlspecialchars($prescription['doctor_name']) ?></p>
                                    <p class="text-center mb-0">License: MH123456</p>
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
</body>
</html>