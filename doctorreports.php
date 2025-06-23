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

// Get medical reports
$stmt = $pdo->prepare("SELECT mr.id, mr.report_name, mr.uploaded_at, mr.file_path, 
                      u.name as patient_name
                      FROM medical_reports mr
                      JOIN patients p ON mr.patient_id = p.user_id
                      JOIN users u ON p.user_id = u.id
                      WHERE mr.doctor_id = ?
                      ORDER BY mr.uploaded_at DESC");
$stmt->execute([$doctorId]);
$reports = $stmt->fetchAll();

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
    <title>Medical Reports - E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/doctordashboard.css">
    <style>
        .report-card {
            border-left: 4px solid var(--warning);
            transition: all 0.3s;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .file-icon {
            font-size: 2rem;
            color: var(--gray);
        }
        
        .pdf-icon {
            color: #d23333;
        }
        
        .doc-icon {
            color: #2b579a;
        }
        
        .img-icon {
            color: #d35400;
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
            
            <li class="nav-item">
                <a class="nav-link" href="doctorprescriptions.php">
                    <i class="fas fa-prescription-bottle-alt"></i>
                    <span>Prescriptions</span>
                </a>
            </li>
            
            <li class="nav-item active">
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
                <input type="text" class="form-control" placeholder="Search for reports...">
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
                <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Medical Reports</h1>
            </div>
            
            <?php if (empty($reports)): ?>
                <div class="card shadow">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-file-medical fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No medical reports yet</h4>
                        <p class="text-muted">Upload or generate reports for your patients</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($reports as $report): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card report-card h-100">
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <?php
                                        $fileExt = pathinfo($report['file_path'], PATHINFO_EXTENSION);
                                        if ($fileExt === 'pdf') {
                                            echo '<i class="fas fa-file-pdf file-icon pdf-icon"></i>';
                                        } elseif (in_array($fileExt, ['doc', 'docx'])) {
                                            echo '<i class="fas fa-file-word file-icon doc-icon"></i>';
                                        } elseif (in_array($fileExt, ['jpg', 'jpeg', 'png'])) {
                                            echo '<i class="fas fa-file-image file-icon img-icon"></i>';
                                        } else {
                                            echo '<i class="fas fa-file file-icon"></i>';
                                        }
                                        ?>
                                    </div>
                                    
                                    <h5 class="text-center mb-3"><?= htmlspecialchars($report['report_name']) ?></h5>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <p class="mb-0 text-muted small">
                                                <i class="fas fa-user-injured mr-2"></i>
                                                <?= htmlspecialchars($report['patient_name']) ?>
                                            </p>
                                        </div>
                                        <div>
                                            <p class="mb-0 text-muted small">
                                                <?= date('M j, Y', strtotime($report['uploaded_at'])) ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="text-center">
                                        <a href="<?= htmlspecialchars($report['file_path']) ?>" class="btn btn-primary btn-sm" download>
                                            <i class="fas fa-download mr-2"></i> Download
                                        </a>
                                        <a href="<?= htmlspecialchars($report['file_path']) ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                                            <i class="fas fa-eye mr-2"></i> View
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