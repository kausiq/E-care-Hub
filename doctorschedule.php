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

// Get current availability
$stmt = $pdo->prepare("SELECT availability FROM doctors WHERE user_id = ?");
$stmt->execute([$doctorId]);
$availability = json_decode($stmt->fetch()['availability'], true);

// Get upcoming appointments
$stmt = $pdo->prepare("SELECT a.id, a.appointment_date, a.appointment_time, a.status, 
                      u.name as patient_name, p.gender, p.date_of_birth
                      FROM appointments a
                      JOIN patients p ON a.patient_id = p.user_id
                      JOIN users u ON p.user_id = u.id
                      WHERE a.doctor_id = ? AND a.appointment_date >= CURDATE()
                      ORDER BY a.appointment_date, a.appointment_time");
$stmt->execute([$doctorId]);
$appointments = $stmt->fetchAll();

// Handle availability update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_availability'])) {
    $newAvailability = [
        'monday' => $_POST['monday'] ?? [],
        'tuesday' => $_POST['tuesday'] ?? [],
        'wednesday' => $_POST['wednesday'] ?? [],
        'thursday' => $_POST['thursday'] ?? [],
        'friday' => $_POST['friday'] ?? [],
        'saturday' => $_POST['saturday'] ?? [],
        'sunday' => $_POST['sunday'] ?? []
    ];
    
    $stmt = $pdo->prepare("UPDATE doctors SET availability = ? WHERE user_id = ?");
    if ($stmt->execute([json_encode($newAvailability), $doctorId])) {
        $success = "Availability updated successfully!";
        $availability = $newAvailability;
    } else {
        $error = "Failed to update availability.";
    }
}

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
    <title>My Schedule - E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/doctordashboard.css">
    <style>
        .time-slot {
            padding: 5px 10px;
            margin: 2px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
        }
        
        .time-slot.available {
            background-color: rgba(67, 170, 139, 0.2);
            color: var(--info);
        }
        
        .time-slot.booked {
            background-color: rgba(247, 37, 133, 0.2);
            color: var(--danger);
            cursor: not-allowed;
        }
        
        .time-slot.selected {
            background-color: var(--info);
            color: white;
        }
        
        .day-column {
            min-height: 300px;
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
            
            <li class="nav-item active">
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
                <h1 class="h3 mb-0 text-gray-800 font-weight-bold">My Schedule</h1>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $success ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Availability Settings -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Set Your Weekly Availability</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row">
                                    <?php 
                                    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                    $timeSlots = [
                                        '08:00 AM - 09:00 AM', '10:00 AM - 11:00 AM', '11:00 AM - 12:00 PM', '12:00 AM - 01:00 AM',  
                                        '01:00 PM - 02:00 PM', '02:00 PM - 03:00 PM', '03:00 PM - 04:00 PM', '04:00 PM - 05:00 PM', 
                                        '05:00 PM - 06:00 PM', '06:00 PM - 07:00 PM', '07:00 PM - 08:00 PM', '08:00 PM - 09:00 PM'
                                    ];
                                    
                                    foreach ($days as $day): 
                                        $daySlots = $availability[$day] ?? [];
                                    ?>
                                    <div class="col-md-4 mb-4">
                                        <div class="card day-column">
                                            <div class="card-header bg-light">
                                                <h6 class="m-0 font-weight-bold text-capitalize"><?= ucfirst($day) ?></h6>
                                            </div>
                                            <div class="card-body">
                                                <?php foreach ($timeSlots as $slot): 
                                                    $isAvailable = in_array($slot, $daySlots);
                                                    $isBooked = false;
                                                    
                                                    // Check if slot is booked
                                                    foreach ($appointments as $appt) {
                                                        if (strtolower(date('l', strtotime($appt['appointment_date']))) == $day && 
                                                            date('h:i A', strtotime($appt['appointment_time'])) == $slot &&
                                                            $appt['status'] != 'cancelled') {
                                                            $isBooked = true;
                                                            break;
                                                        }
                                                    }
                                                ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                        name="<?= $day ?>[]" value="<?= $slot ?>"
                                                        id="<?= $day ?>_<?= str_replace(' ', '', $slot) ?>"
                                                        <?= $isAvailable && !$isBooked ? 'checked' : '' ?>
                                                        <?= $isBooked ? 'disabled' : '' ?>>
                                                    <label class="form-check-label" for="<?= $day ?>_<?= str_replace(' ', '', $slot) ?>">
                                                        <span class="time-slot <?= $isBooked ? 'booked' : ($isAvailable ? 'available' : '') ?>">
                                                            <?= $slot ?>
                                                            <?php if ($isBooked): ?>
                                                                <i class="fas fa-user ml-1"></i>
                                                            <?php endif; ?>
                                                        </span>
                                                    </label>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="text-right mt-3">
                                    <button type="submit" name="update_availability" class="btn btn-primary">
                                        <i class="fas fa-save mr-2"></i> Save Availability
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Upcoming Appointments -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Upcoming Appointments</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($appointments)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-calendar-times fa-2x text-muted mb-3"></i>
                                    <p class="text-muted">No upcoming appointments</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($appointments as $appointment): 
                                        $patientAge = '';
                                        if (!empty($appointment['date_of_birth'])) {
                                            $dob = new DateTime($appointment['date_of_birth']);
                                            $now = new DateTime();
                                            $patientAge = $now->diff($dob)->y;
                                        }
                                    ?>
                                    <a href="appointmentdetails.php?id=<?= $appointment['id'] ?>" 
                                       class="list-group-item list-group-item-action">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="mb-1"><?= htmlspecialchars($appointment['patient_name']) ?></h6>
                                                <small class="text-muted">
                                                    <?= $patientAge ? $patientAge.'y' : '' ?> 
                                                    <?= $appointment['gender'] ? ' | '.ucfirst($appointment['gender']) : '' ?>
                                                </small>
                                            </div>
                                            <div class="text-right">
                                                <small class="text-muted">
                                                    <?= date('M j', strtotime($appointment['appointment_date'])) ?>
                                                </small>
                                                <div><?= date('h:i A', strtotime($appointment['appointment_time'])) ?></div>
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
        
        // Time slot selection
        $('.form-check-input').change(function() {
            const label = $(this).next('.form-check-label');
            const slot = label.find('.time-slot');
            
            if (this.checked) {
                slot.addClass('available').removeClass('selected');
            } else {
                slot.removeClass('available selected');
            }
        });
    </script>
</body>
</html>