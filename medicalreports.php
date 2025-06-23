<?php
require 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: patientlogin.php');
    exit();
}

$patientId = $_SESSION['user_id'];

// Get patient info
$stmt = $pdo->prepare("SELECT u.name FROM users u JOIN patients p ON u.id = p.user_id WHERE u.id = ?");
$stmt->execute([$patientId]);
$patient = $stmt->fetch();

// Get medical reports
$stmt = $pdo->prepare("SELECT mr.id, mr.report_name, mr.uploaded_at, mr.file_path, 
                      u.name as doctor_name, d.specialty
                      FROM medical_reports mr
                      LEFT JOIN doctors d ON mr.doctor_id = d.user_id
                      LEFT JOIN users u ON d.user_id = u.id
                      WHERE mr.patient_id = ?
                      ORDER BY mr.uploaded_at DESC");
$stmt->execute([$patientId]);
$reports = $stmt->fetchAll();

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

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['report_file'])) {
    $reportName = $_POST['report_name'] ?? 'Untitled Report';
    $doctorId = !empty($_POST['doctor_id']) ? $_POST['doctor_id'] : null;
    
    $targetDir = "uploads/medical_reports/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = basename($_FILES["report_file"]["name"]);
    $targetFile = $targetDir . uniqid() . '_' . $fileName;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    // Check if file is a actual document
    $allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    if (in_array($fileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES["report_file"]["tmp_name"], $targetFile)) {
            $stmt = $pdo->prepare("INSERT INTO medical_reports (patient_id, doctor_id, report_name, file_path) 
                                  VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$patientId, $doctorId, $reportName, $targetFile])) {
                // Add notification
                $message = "New medical report uploaded: " . $reportName;
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'report')");
                $stmt->execute([$patientId, $message]);
                
                header("Location: medicalreports.php");
                exit();
            }
        }
    }
}
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
        
        /* Doctor Card Styles */
        .doctor-card {
            border-left: 4px solid var(--primary);
            transition: all 0.3s;
        }
        
        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .doctor-rating {
            color: var(--warning);
        }
        
        .doctor-specialty {
            color: var(--primary);
            font-weight: 500;
        }
        
        .doctor-fee {
            font-weight: 600;
            color: var(--dark);
        }
        
        /* Badge Styles */
        .badge-language {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
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
        .report-card {
            border-left: 4px solid var(--success);
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
            color: #f40f02;
        }
        
        .image-icon {
            color: #2d8cff;
        }
        
        .doc-icon {
            color: #2b579a;
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
            
            <li class="nav-item active">
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
                <input type="text" class="form-control" placeholder="Search for doctors...">
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
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Medical Reports</h1>
            <button class="btn btn-primary" data-toggle="modal" data-target="#uploadReportModal">
                <i class="fas fa-upload mr-2"></i> Upload Report
            </button>
        </div>
        
        <?php if (empty($reports)): ?>
            <div class="card shadow">
                <div class="empty-state">
                    <i class="fas fa-file-medical"></i>
                    <h4 class="text-muted">No medical reports</h4>
                    <p class="text-muted">Upload your medical reports to keep them organized in one place</p>
                    <button class="btn btn-primary mt-3" data-toggle="modal" data-target="#uploadReportModal">
                        <i class="fas fa-upload mr-2"></i> Upload Your First Report
                    </button>
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
                                    $iconClass = 'file-icon';
                                    if ($fileExt === 'pdf') {
                                        $iconClass .= ' pdf-icon';
                                    } elseif (in_array($fileExt, ['jpg', 'jpeg', 'png'])) {
                                        $iconClass .= ' image-icon';
                                    } elseif (in_array($fileExt, ['doc', 'docx'])) {
                                        $iconClass .= ' doc-icon';
                                    }
                                    ?>
                                    <i class="fas fa-file-<?= $fileExt === 'pdf' ? 'pdf' : ($fileExt === 'doc' || $fileExt === 'docx' ? 'word' : 'image') ?> <?= $iconClass ?>"></i>
                                </div>
                                
                                <h5 class="text-center mb-3"><?= htmlspecialchars($report['report_name']) ?></h5>
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <small class="text-muted">Uploaded:</small>
                                    <small><?= date('M j, Y', strtotime($report['uploaded_at'])) ?></small>
                                </div>
                                
                                <?php if (!empty($report['doctor_name'])): ?>
                                    <div class="d-flex justify-content-between mb-3">
                                        <small class="text-muted">Doctor:</small>
                                        <small>Dr. <?= htmlspecialchars($report['doctor_name']) ?></small>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="<?= htmlspecialchars($report['file_path']) ?>" 
                                       class="btn btn-sm btn-outline-primary" 
                                       target="_blank" 
                                       download="<?= htmlspecialchars($report['report_name']) ?>">
                                        <i class="fas fa-download mr-1"></i> Download
                                    </a>
                                    <a href="reportviewer.php?id=<?= $report['id'] ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye mr-1"></i> View
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Upload Report Modal -->
    <div class="modal fade" id="uploadReportModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Medical Report</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Report Name</label>
                            <input type="text" name="report_name" class="form-control" placeholder="E.g. Blood Test Results">
                        </div>
                        
                        <div class="form-group">
                            <label>Associated Doctor (Optional)</label>
                            <select name="doctor_id" class="form-control">
                                <option value="">Select Doctor</option>
                                <?php
                                $stmt = $pdo->prepare("SELECT d.user_id, u.name, d.specialty 
                                                      FROM doctors d
                                                      JOIN users u ON d.user_id = u.id
                                                      ORDER BY u.name");
                                $stmt->execute();
                                $doctors = $stmt->fetchAll();
                                
                                foreach ($doctors as $doctor): ?>
                                    <option value="<?= $doctor['user_id'] ?>">
                                        Dr. <?= htmlspecialchars($doctor['name']) ?> - <?= htmlspecialchars($doctor['specialty']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Report File</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="reportFile" name="report_file" required>
                                <label class="custom-file-label" for="reportFile">Choose file</label>
                            </div>
                            <small class="text-muted">Allowed formats: PDF, DOC, DOCX, JPG, PNG</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload Report</button>
                    </div>
                </form>
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
        // Update file input label
        $('#reportFile').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName);
        });
        // Update file input label
        $('#reportFile').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName);
        });
    </script>
</body>
</html>