<?php
require 'connection.php';

session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['ambulance_driver'])) {
    header("Location: ambulancelogin.php");
    exit();
}

$driver = $_SESSION['ambulance_driver'];

// Get driver details from database
try {
    $stmt = $pdo->prepare("SELECT * FROM ambulancedrivers WHERE id = ?");
    $stmt->execute([$driver['id']]);
    $driverDetails = $stmt->fetch();
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error = "Error loading dashboard data. Please try again.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ambulance Dashboard | E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/ambulancedashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="d-flex flex-column h-100">
            <div class="sidebar-header">
                <img src="<?= $driver['profile_picture'] ?? 'https://via.placeholder.com/150' ?>" 
                     alt="Profile" class="profile-img mb-3">
                <h5 class="mb-2"><?= htmlspecialchars($driver['name']) ?></h5>
                <span class="status-badge <?= $driverDetails['availability'] === 'Available' ? 'availability-badge' : 'unavailable-badge' ?>">
                    <?= htmlspecialchars($driverDetails['availability']) ?>
                </span>
            </div>
            
            <nav class="flex-grow-1 p-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="ambulancedashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="editprofile.php">
                            <i class="fas fa-user-edit"></i> Edit Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Home Page
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="logout.php" class="btn btn-outline-light w-100">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <button class="btn btn-primary d-lg-none" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h4 class="mb-0">Ambulance Dashboard</h4>
            <!-- <div class="last-login">Last login: <?= date('M j, Y g:i a', strtotime($driverDetails['last_login'])) ?></div> -->
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Profile Card -->
            <div class="col-lg-8 mx-auto">
                <div class="card profile-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title mb-0">My Profile</h5>
                            <a href="editprofile.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-edit"></i> Edit Profile
                            </a>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-3"><strong><i class="fas fa-user me-2 text-primary"></i>Name:</strong> <?= htmlspecialchars($driverDetails['fullname']) ?></p>
                                <p class="mb-3"><strong><i class="fas fa-envelope me-2 text-primary"></i>Email:</strong> <?= htmlspecialchars($driverDetails['email']) ?></p>
                                <p class="mb-3"><strong><i class="fas fa-phone me-2 text-primary"></i>Phone:</strong> <?= htmlspecialchars($driverDetails['phonenumber']) ?></p>
                                <p class="mb-3"><strong><i class="fas fa-id-card me-2 text-primary"></i>License:</strong> <?= htmlspecialchars($driverDetails['driverlicense']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-3"><strong><i class="fas fa-ambulance me-2 text-primary"></i>Ambulance:</strong> <?= htmlspecialchars($driverDetails['ambulancenumber']) ?></p>
                                <p class="mb-3"><strong><i class="fas fa-map-marker-alt me-2 text-primary"></i>Address:</strong> <?= htmlspecialchars($driverDetails['address']) ?></p>
                                <p class="mb-3"><strong><i class="fas fa-building me-2 text-primary"></i>Organization:</strong> <?= htmlspecialchars($driverDetails['organization'] ?? 'N/A') ?></p>
                                <p class="mb-3"><strong><i class="fas fa-clock me-2 text-primary"></i>Availability:</strong> 
                                    <span class="badge bg-primary">
                                        <?= htmlspecialchars($driverDetails['availability']) ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('mainContent').classList.toggle('active');
        });
    </script>
</body>
</html>