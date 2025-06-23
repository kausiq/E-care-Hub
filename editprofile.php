<?php
require 'connection.php';

session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['ambulance_driver'])) {
    header("Location: ambulancelogin.php");
    exit();
}

$driver = $_SESSION['ambulance_driver'];
$error = '';
$success = '';

// Get driver details
try {
    $stmt = $pdo->prepare("SELECT * FROM ambulancedrivers WHERE id = ?");
    $stmt->execute([$driver['id']]);
    $driverDetails = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Profile error: " . $e->getMessage());
    $error = "Error loading profile data.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $latitude = trim($_POST['latitude']);
    $longitude = trim($_POST['longitude']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    try {
        $pdo->beginTransaction();
        
        // Update profile data
        $updateStmt = $pdo->prepare("UPDATE ambulancedrivers 
                                   SET phonenumber = ?, address = ?, latitude = ?, longitude = ?, updatedat = NOW()
                                   WHERE id = ?");
        $updateStmt->execute([$phone, $address, $latitude, $longitude, $driver['id']]);
        
        // Update password if provided
        if (!empty($new_password)) {
            if (!password_verify($current_password, $driverDetails['password'])) {
                throw new Exception("Current password is incorrect");
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match");
            }
            
            if (strlen($new_password) < 8) {
                throw new Exception("Password must be at least 8 characters");
            }
            
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $passwordStmt = $pdo->prepare("UPDATE ambulancedrivers 
                                         SET password = ?
                                         WHERE id = ?");
            $passwordStmt->execute([$hashed_password, $driver['id']]);
        }
        
        $pdo->commit();
        $success = "Profile updated successfully!";
        
        // Refresh driver details
        $stmt->execute([$driver['id']]);
        $driverDetails = $stmt->fetch();
        
        // Update session with new data
        $_SESSION['ambulance_driver']['name'] = $driverDetails['fullname'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root {
            --primary-color: #FD7E14;
            --primary-light: rgba(253, 126, 20, 0.1);
            --secondary-color: #343a40;
            --light-color: #f8f9fa;
            --sidebar-width: 280px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }
        
        .sidebar {
            background-color: var(--secondary-color);
            color: white;
            height: 100vh;
            position: fixed;
            width: var(--sidebar-width);
            transition: all 0.3s;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            transition: all 0.3s;
            background-color: white;
            min-height: 100vh;
        }
        
        .profile-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: none;
        }
        
        .profile-img {
            width: 110px;
            height: 110px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #e67312;
            border-color: #e67312;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .nav-link.active {
            background-color: var(--primary-color);
            color: white !important;
        }
        
        .nav-link:hover:not(.active) {
            background-color: var(--primary-light);
            color: white !important;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.85);
            border-radius: 8px;
            margin-bottom: 5px;
            padding: 10px 15px;
            transition: all 0.2s;
        }
        
        .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        
        .sidebar-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px;
            text-align: center;
        }
        
        .sidebar-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px;
        }
        
        .location-container {
            margin-bottom: 20px;
            border: 1px solid #eee;
            padding: 15px;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        
        .location-status {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .location-success {
            color: #28a745;
        }
        
        .location-error {
            color: #dc3545;
        }
        
        #mapPreview {
            height: 250px;
            width: 100%;
            background-color: #f1f1f1;
            margin-top: 10px;
            border-radius: 8px;
        }
        
        .password-toggle {
            cursor: pointer;
            color: var(--primary-color);
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .password-input-group {
            position: relative;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .main-content.active {
                margin-left: var(--sidebar-width);
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="d-flex flex-column h-100">
            <div class="sidebar-header">
                <img src="<?= $driver['profile_picture'] ?? 'https://via.placeholder.com/150' ?>" 
                     alt="Profile" class="profile-img mb-3">
                <h5 class="mb-2"><?= htmlspecialchars($driver['name']) ?></h5>
                <span class="badge bg-primary">
                    <?= htmlspecialchars($driverDetails['availability']) ?>
                </span>
            </div>
            
            <nav class="flex-grow-1 p-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="ambulancedashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="editprofile.php">
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
            <h4 class="mb-0">Edit Profile</h4>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card profile-card">
                    <div class="card-body">
                        <form method="post" id="profileForm">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($driverDetails['fullname']) ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($driverDetails['email']) ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($driverDetails['phonenumber']) ?>" required>
                            </div>
                            
                            <!-- Location Section -->
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <div class="location-container">
                                    <div class="location-status" id="locationStatus">
                                        <i class="fas fa-spinner fa-spin me-2"></i> Loading location...
                                    </div>
                                    
                                    <div id="mapPreview"></div>
                                    
                                    <textarea id="address" name="address" class="form-control mt-3" rows="2" required><?= 
                                        htmlspecialchars($driverDetails['address']) ?></textarea>
                                    <input type="hidden" id="latitude" name="latitude" value="<?= $driverDetails['latitude'] ?>">
                                    <input type="hidden" id="longitude" name="longitude" value="<?= $driverDetails['longitude'] ?>">
                                    
                                    <button type="button" id="getLocationBtn" class="btn btn-sm btn-primary mt-2">
                                        <i class="fas fa-sync-alt me-1"></i> Detect Location Again
                                    </button>
                                    
                                    <button type="button" id="manualLocationBtn" class="btn btn-sm btn-outline-secondary mt-2 ms-2">
                                        <i class="fas fa-map-pin me-1"></i> Set Location Manually
                                    </button>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <h5 class="mb-3">Change Password</h5>
                            
                            <div class="mb-3 password-input-group">
                                <label for="current_password" class="form-label">Current Password</label>
                                <div class="password-input-group">
                                    <input type="password" class="form-control" id="current_password" name="current_password">
                                    <i class="fas fa-eye password-toggle" onclick="togglePassword('current_password')"></i>
                                </div>
                            </div>
                            
                            <div class="mb-3 password-input-group">
                                <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                    <i class="fas fa-eye password-toggle" onclick="togglePassword('new_password')"></i>
                                    <small class="text-muted">Minimum 8 characters</small>
                            </div>
                            
                            <div class="mb-3 password-input-group">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <div class="password-input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="ambulancedashboard.php" class="btn btn-outline-secondary me-md-2">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/esri-leaflet@3.0.10/dist/esri-leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('mainContent').classList.toggle('active');
        });

        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling;
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Location Services
        const locationStatus = document.getElementById('locationStatus');
        const addressInput = document.getElementById('address');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        const mapPreview = document.getElementById('mapPreview');
        const getLocationBtn = document.getElementById('getLocationBtn');
        const manualLocationBtn = document.getElementById('manualLocationBtn');
        let map;
        let marker;
        let geocoder;

        // Initialize map with current location
        function initializeMap() {
            const currentLat = parseFloat(latitudeInput.value) || 0;
            const currentLng = parseFloat(longitudeInput.value) || 0;
            const zoom = currentLat && currentLng ? 15 : 2;
            
            map = L.map('mapPreview').setView([currentLat, currentLng], zoom);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            // Initialize geocoder
            geocoder = L.Control.Geocoder.nominatim();
            
            // Add marker if we have coordinates
            if (currentLat && currentLng) {
                marker = L.marker([currentLat, currentLng]).addTo(map)
                    .bindPopup("Your current location")
                    .openPopup();
                
                locationStatus.innerHTML = '<i class="fas fa-check-circle me-2"></i> Current location loaded';
                locationStatus.className = 'location-status location-success';
            } else {
                locationStatus.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> No location set';
                locationStatus.className = 'location-status location-error';
            }
            
            return map;
        }

        // Update map with specific coordinates
        function updateMapLocation(lat, lng) {
            if (!map) {
                map = initializeMap();
            }
            
            // Set view to new coordinates with appropriate zoom
            map.setView([lat, lng], 15);
            
            // Remove existing marker if any
            if (marker) {
                map.removeLayer(marker);
            }
            
            // Add new marker
            marker = L.marker([lat, lng]).addTo(map)
                .bindPopup("Your location")
                .openPopup();
            
            // Update form fields
            latitudeInput.value = lat;
            longitudeInput.value = lng;
            
            // Get address from coordinates
            geocoder.reverse(
                { lat: lat, lng: lng },
                map.options.crs.scale(map.getZoom()),
                (results) => {
                    if (results && results.length > 0) {
                        const address = results[0].name || results[0].html || 'Address not available';
                        addressInput.value = address;
                        
                        locationStatus.innerHTML = '<i class="fas fa-check-circle me-2"></i> Location detected!';
                        locationStatus.className = 'location-status location-success';
                    } else {
                        addressInput.value = `Coordinates: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    }
                }
            );
        }

        // Get user's current location
        function detectUserLocation() {
            locationStatus.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Detecting your location...';
            locationStatus.className = 'location-status';
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    // Success callback
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        updateMapLocation(lat, lng);
                    },
                    // Error callback
                    (error) => {
                        let errorMessage;
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage = "Location access was denied. Please enable it or set location manually.";
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage = "Location information is unavailable. Please set location manually.";
                                break;
                            case error.TIMEOUT:
                                errorMessage = "The request to get location timed out. Please try again or set location manually.";
                                break;
                            default:
                                errorMessage = "An unknown error occurred. Please set location manually.";
                        }
                        
                        locationStatus.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i> ${errorMessage}`;
                        locationStatus.className = 'location-status location-error';
                    }
                );
            } else {
                locationStatus.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> Geolocation is not supported by this browser. Please set location manually.';
                locationStatus.className = 'location-status location-error';
            }
        }

        // Set up manual location selection
        function setupManualLocationSelection() {
            if (!map) {
                map = initializeMap();
            }
            
            locationStatus.innerHTML = '<i class="fas fa-map-pin me-2"></i> Click on the map to set your location';
            locationStatus.className = 'location-status';
            
            // Change cursor style
            map.getContainer().style.cursor = 'crosshair';
            
            // Remove any existing click handlers
            map.off('click');
            
            // Set up click handler
            map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;
                updateMapLocation(lat, lng);
                
                // Reset cursor
                map.getContainer().style.cursor = '';
                map.off('click');
            });
        }

        // Initialize on page load
        window.addEventListener('DOMContentLoaded', function() {
            // Initialize map with current location
            initializeMap();
            
            // Set up event listeners
            getLocationBtn.addEventListener('click', detectUserLocation);
            manualLocationBtn.addEventListener('click', setupManualLocationSelection);
        });
    </script>
</body>
</html>