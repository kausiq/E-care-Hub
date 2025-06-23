<?php
require 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: patientlogin.php');
    exit();
}

$patientId = $_SESSION['user_id'];
$doctorId = $_GET['id'] ?? null;

if (!$doctorId) {
    header('Location: finddoctor.php');
    exit();
}

// Get patient info
$stmt = $pdo->prepare("SELECT u.name FROM users u JOIN patients p ON u.id = p.user_id WHERE u.id = ?");
$stmt->execute([$patientId]);
$patient = $stmt->fetch();

// Get doctor details
$stmt = $pdo->prepare("SELECT d.user_id, u.name, d.specialty, d.languages, d.location, 
                      d.consultation_fee, d.rating, d.bio, d.availability
                      FROM doctors d
                      JOIN users u ON d.user_id = u.id
                      WHERE d.user_id = ?");
$stmt->execute([$doctorId]);
$doctor = $stmt->fetch();

if (!$doctor) {
    header('Location: finddoctor.php');
    exit();
}

// Parse availability JSON
$availability = json_decode($doctor['availability'], true) ?? [];

// Get doctor reviews
$stmt = $pdo->prepare("SELECT r.id, r.rating, r.comment, r.created_at, 
                      u.name as patient_name
                      FROM reviewss r
                      JOIN patients p ON r.patient_id = p.user_id
                      JOIN users u ON p.user_id = u.id
                      WHERE r.doctor_id = ?
                      ORDER BY r.created_at DESC");
$stmt->execute([$doctorId]);
$reviews = $stmt->fetchAll();

// Calculate average rating
$avgRating = $doctor['rating'];
$ratingCount = count($reviews);

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
    <title>Doctor Profile - E Care Hub</title>
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
        
        /* Doctor Profile Styles */
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            margin-right: 30px;
        }
        
        .profile-info h2 {
            margin-bottom: 5px;
        }
        
        .profile-specialty {
            color: var(--primary);
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .profile-rating {
            color: var(--warning);
            font-weight: 500;
        }
        
        .profile-meta {
            display: flex;
            margin-bottom: 15px;
        }
        
        .profile-meta-item {
            margin-right: 20px;
            display: flex;
            align-items: center;
        }
        
        .profile-meta-item i {
            margin-right: 8px;
            color: var(--primary);
        }
        
        /* Badge Styles */
        .badge-language {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-right: 5px;
            margin-bottom: 5px;
            display: inline-block;
        }
        
        /* Review Styles */
        .review-item {
            padding: 20px 0;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .review-item:last-child {
            border-bottom: none;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .review-patient {
            font-weight: 500;
        }
        
        .review-date {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .review-rating {
            color: var(--warning);
            margin-bottom: 10px;
        }
        
        /* Availability Styles */
        .availability-day {
            margin-bottom: 15px;
        }
        
        .day-header {
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .time-slots {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .time-slot {
            padding: 8px 15px;
            background-color: var(--light);
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .time-slot:hover {
            background-color: var(--primary);
            color: white;
        }
        
        .time-slot.booked {
            background-color: var(--light-gray);
            color: var(--gray);
            cursor: not-allowed;
            text-decoration: line-through;
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
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-avatar {
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .profile-meta {
                justify-content: center;
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
                <input type="text" class="form-control" placeholder="Search...">
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
                        <a class="dropdown-item" href="settings.php">
                            <i class="fas fa-cog mr-2"></i> Settings
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
            <!-- Back Button -->
            <a href="finddoctor.php" class="btn btn-outline-secondary mb-4">
                <i class="fas fa-arrow-left mr-2"></i> Back to Doctors
            </a>
            
            <!-- Doctor Profile -->
            <div class="card shadow">
                <div class="card-body">
                    <div class="profile-header">
                        <div class="profile-avatar"><?= strtoupper(substr($doctor['name'], 0, 1)) ?></div>
                        <div class="profile-info">
                            <h2>Dr. <?= htmlspecialchars($doctor['name']) ?></h2>
                            <p class="profile-specialty"><?= htmlspecialchars($doctor['specialty']) ?></p>
                            <div class="profile-rating">
                                <i class="fas fa-star"></i> <?= number_format($avgRating, 1) ?> (<?= $ratingCount ?> reviews)
                            </div>
                            <div class="profile-meta">
                                <div class="profile-meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?= htmlspecialchars($doctor['location']) ?></span>
                                </div>
                                <div class="profile-meta-item">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>$<?= number_format($doctor['consultation_fee'], 2) ?> consultation</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Languages -->
                    <?php if (!empty($doctor['languages'])): ?>
                        <div class="mb-4">
                            <h5 class="mb-3">Languages Spoken</h5>
                            <?php $languages = explode(',', $doctor['languages']); ?>
                            <?php foreach ($languages as $language): ?>
                                <span class="badge-language"><?= trim($language) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- About Doctor -->
                    <div class="mb-4">
                        <h5 class="mb-3">About Dr. <?= htmlspecialchars($doctor['name']) ?></h5>
                        <p><?= !empty($doctor['bio']) ? nl2br(htmlspecialchars($doctor['bio'])) : 'No biography available.' ?></p>
                    </div>
                    
                    <!-- Availability -->
                    <div class="mb-4">
                        <h5 class="mb-3">Availability</h5>
                        <?php if (!empty($availability)): ?>
                            <?php foreach ($availability as $day => $slots): ?>
                                <div class="availability-day">
                                    <div class="day-header"><?= ucfirst($day) ?></div>
                                    <div class="time-slots">
                                        <?php foreach ($slots as $slot): ?>
                                            <div class="time-slot" 
                                                 data-day="<?= $day ?>" 
                                                 data-time="<?= $slot ?>"
                                                 onclick="prepareBooking('<?= $day ?>', '<?= $slot ?>')">
                                                <?= $slot ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No availability information available.</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Book Appointment Button -->
                    <div class="text-center mt-5">
                        <a href="bookappointment.php?doctor_id=<?= $doctor['user_id'] ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-calendar-plus mr-2"></i> Book Appointment
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Reviews Section -->
            <div class="card shadow mt-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold">Patient Reviews</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($reviews)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No reviews yet</h5>
                            <p class="text-muted">Be the first to review Dr. <?= htmlspecialchars($doctor['name']) ?></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="review-patient"><?= htmlspecialchars($review['patient_name']) ?></div>
                                    <div class="review-date"><?= date('M j, Y', strtotime($review['created_at'])) ?></div>
                                </div>
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?= $i > $review['rating'] ? '-half-alt' : '' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="review-comment">
                                    <?= nl2br(htmlspecialchars($review['comment'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
        
        // Prepare booking by selecting day and time
        function prepareBooking(day, time) {
            // You can implement this to pre-fill the booking form
            console.log('Selected:', day, time);
            // Alternatively, redirect to booking page with parameters
            // window.location.href = `bookappointment.php?doctor_id=<?= $doctor['user_id'] ?>&day=${day}&time=${time}`;
        }
    </script>
</body>
</html>