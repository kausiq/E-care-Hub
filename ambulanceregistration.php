<?php
session_start();
require 'connection.php';

$error = '';
$success = '';

// File upload settings
$allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
$maxFileSize = 2 * 1024 * 1024; // 2MB

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $fullname = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $license = filter_input(INPUT_POST, 'license', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $confirmpassword = $_POST['confirmpassword'];
    $ambulancenumber = filter_input(INPUT_POST, 'ambulancenumber', FILTER_SANITIZE_STRING);
    $organization = filter_input(INPUT_POST, 'organization', FILTER_SANITIZE_STRING);
    $availability = filter_input(INPUT_POST, 'availability', FILTER_SANITIZE_STRING);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $latitude = filter_input(INPUT_POST, 'latitude', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $longitude = filter_input(INPUT_POST, 'longitude', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $firstaid = isset($_POST['firstaid']) ? 1 : 0;
    $cpr = isset($_POST['cpr']) ? 1 : 0;
    $emt = isset($_POST['emt']) ? 1 : 0;
    $terms = isset($_POST['terms']) ? 1 : 0;

    // Validate address and coordinates
    if (empty($address) || empty($latitude) || empty($longitude)) {
        $error = "Please enable location services and provide your address";
    }
    // Handle file upload
    elseif (isset($_FILES['profilepicture']) && $_FILES['profilepicture']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profilepicture']['tmp_name'];
        $fileType = $_FILES['profilepicture']['type'];
        $fileSize = $_FILES['profilepicture']['size'];
        
        // Validate file type
        if (!array_key_exists($fileType, $allowedTypes)) {
            $error = "Only JPG, PNG, and GIF images are allowed.";
        } 
        // Validate file size
        elseif ($fileSize > $maxFileSize) {
            $error = "File size must be less than 2MB.";
        } else {
            // Read file content directly without compression
            $imageData = file_get_contents($fileTmpPath);
            if ($imageData !== false) {
                $profilePicture = $imageData;
                $profilePictureType = $fileType;
            } else {
                $error = "Failed to read image file.";
            }
        }
    } else {
        $error = "Profile picture is required.";
    }

    // Continue with registration if no errors
    if (empty($error)) {
        // Validate terms agreement
        if (!$terms) {
            $error = "You must agree to the terms and conditions";
        } 
        // Validate password match
        elseif ($password !== $confirmpassword) {
            $error = "Passwords do not match";
        } 
        // Validate password strength
        elseif (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password)) {
            $error = "Password must be at least 8 characters long and contain at least one uppercase letter and one number";
        } else {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM ambulancedrivers WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email already registered";
            } else {
                // Check if license number already exists
                $stmt = $pdo->prepare("SELECT id FROM ambulancedrivers WHERE driverlicense = ?");
                $stmt->execute([$license]);
                if ($stmt->fetch()) {
                    $error = "Driver's license number already registered";
                } else {
                    // Generate OTP
                    $otp = rand(100000, 999999);
                    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                    
                    // Hash password
                    $hashedpassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Store in database (unverified)
                    $stmt = $pdo->prepare("INSERT INTO ambulancedrivers 
                        (fullname, email, phonenumber, driverlicense, password, ambulancenumber, 
                        organization, availability, address, latitude, longitude, 
                        firstaidcertified, cprcertified, emtcertified, 
                        profilepicture, profilepicturetype, verificationcode, codeexpiry) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    if ($stmt->execute([
                        $fullname, $email, $phone, $license, $hashedpassword, $ambulancenumber,
                        $organization, $availability, $address, $latitude, $longitude,
                        $firstaid, $cpr, $emt, $profilePicture, $profilePictureType, $otp, $expiry
                    ])) {
                        // Send verification email
                        if (sendVerificationEmail($email, $otp)) {
                            // Store email in session for verification page
                            $_SESSION['verifyemail'] = $email;
                            header("Location: verifyambulance.php");
                            exit();
                        } else {
                            $error = "Failed to send verification email. Please try again.";
                        }
                    } else {
                        $error = "Registration failed. Please try again.";
                    }
                }
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
    <title>Ambulance Driver Registration | E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="css/style1.css">
    <link rel="stylesheet" href="css/ambulance.css">
    <style>
        .preview-container {
            width: 150px;
            height: 150px;
            border: 2px dashed #ccc;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            background-color: #f8f9fa;
        }
        .preview-container img {
            max-width: 100%;
            max-height: 100%;
        }
        #imagePreview {
            display: none;
        }
        .location-container {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
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
            border-radius: 5px;
            display: none;
        }
        #address {
            width: 100%;
            margin-top: 10px;
        }
        .leaflet-container {
            height: 100%;
            width: 100%;
            border-radius: 5px;
        }
        .manual-location-btn {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="registration-header ambulance-header">
            <h2><i class="fas fa-ambulance me-2"></i>Ambulance Driver Registration</h2>
            <p>Join our emergency response team</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="registration-form">
            <form method="POST" action="" enctype="multipart/form-data" id="registrationForm">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="fullname" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" required
                               value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="license" class="form-label">Driver's License Number</label>
                        <input type="text" class="form-control" id="license" name="license" required
                               value="<?php echo isset($_POST['license']) ? htmlspecialchars($_POST['license']) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength mt-2">
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small class="password-strength-text text-muted">Password strength: weak</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="confirmpassword" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirmpassword" name="confirmpassword" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small id="passwordMatch" class="text-danger d-none">Passwords do not match</small>
                    </div>
                    <div class="col-md-6">
                        <label for="ambulancenumber" class="form-label">Ambulance Number</label>
                        <input type="text" class="form-control" id="ambulancenumber" name="ambulancenumber" required
                               value="<?php echo isset($_POST['ambulancenumber']) ? htmlspecialchars($_POST['ambulancenumber']) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="organization" class="form-label">Organization/Hospital</label>
                        <input type="text" class="form-control" id="organization" name="organization"
                               value="<?php echo isset($_POST['organization']) ? htmlspecialchars($_POST['organization']) : ''; ?>">
                    </div>
                    <div class="col-12">
                        <label for="availability" class="form-label">Availability</label>
                        <select class="form-select" id="availability" name="availability" required>
                            <option value="">Select availability</option>
                            <option value="Full-time" <?php echo (isset($_POST['availability']) && $_POST['availability'] === 'Full-time') ? 'selected' : ''; ?>>Full-time</option>
                            <option value="Part-time" <?php echo (isset($_POST['availability']) && $_POST['availability'] === 'Part-time') ? 'selected' : ''; ?>>Part-time</option>
                            <option value="On-call" <?php echo (isset($_POST['availability']) && $_POST['availability'] === 'On-call') ? 'selected' : ''; ?>>On-call</option>
                        </select>
                    </div>
                    
                    <!-- Location Section -->
                    <div class="col-12">
                        <div class="location-container">
                            <h5><i class="fas fa-map-marker-alt me-2"></i>Your Location</h5>
                            <p>We need your current location to provide emergency services</p>
                            
                            <div class="location-status" id="locationStatus">
                                <i class="fas fa-spinner fa-spin me-2"></i> Waiting for location access...
                            </div>
                            
                            <div id="mapPreview"></div>
                            
                            <input type="text" id="address" name="address" class="form-control" placeholder="Your address will appear here" required>
                            <input type="hidden" id="latitude" name="latitude">
                            <input type="hidden" id="longitude" name="longitude">
                            
                            <button type="button" id="getLocationBtn" class="btn btn-sm btn-primary mt-2">
                                <i class="fas fa-sync-alt me-1"></i> Detect Location Again
                            </button>
                            
                            <button type="button" id="manualLocationBtn" class="btn btn-sm btn-outline-secondary manual-location-btn">
                                <i class="fas fa-map-pin me-1"></i> Set Location Manually
                            </button>
                            
                            <small class="text-muted">Please allow location access when prompted</small>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Profile Picture</label>
                        <div class="preview-container">
                            <img id="imagePreview" src="#" alt="Preview">
                            <span id="noPreviewText"><i class="fas fa-user-circle fa-5x text-secondary"></i></span>
                        </div>
                        <input type="file" class="form-control" id="profilepicture" name="profilepicture" accept="image/*" required>
                        <small class="text-muted">Max file size: 2MB (JPG, PNG, GIF)</small>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Certifications</label>
                        <div class="certifications">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="firstaid" name="firstaid" <?php echo (isset($_POST['firstaid']) && $_POST['firstaid']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="firstaid">First Aid Certified</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="cpr" name="cpr" <?php echo (isset($_POST['cpr']) && $_POST['cpr']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cpr">CPR Certified</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="emt" name="emt" <?php echo (isset($_POST['emt']) && $_POST['emt']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="emt">EMT Certified</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" name="terms" required <?php echo (isset($_POST['terms']) && $_POST['terms']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="terms">
                                I agree to the terms and conditions
                            </label>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-4">
                    <a href="index.php" class="btn btn-back">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                    <button type="submit" class="btn btn-warning" id="submitBtn">
                        Register <i class="fas fa-siren-on ms-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/esri-leaflet@3.0.10/dist/esri-leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.js"></script>
    <script>
        // Password toggle functionality
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        // Password strength checker
        const passwordInput = document.getElementById('password');
        const progressBar = document.querySelector('.progress-bar');
        const strengthText = document.querySelector('.password-strength-text');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Check for length
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;
            
            // Check for uppercase letters
            if (/[A-Z]/.test(password)) strength += 1;
            
            // Check for numbers
            if (/[0-9]/.test(password)) strength += 1;
            
            // Check for special characters
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            // Update progress bar
            const width = (strength / 5) * 100;
            progressBar.style.width = width + '%';
            
            // Update colors and text
            if (strength <= 2) {
                progressBar.className = 'progress-bar bg-danger';
                strengthText.textContent = 'Password strength: weak';
            } else if (strength <= 3) {
                progressBar.className = 'progress-bar bg-warning';
                strengthText.textContent = 'Password strength: medium';
            } else {
                progressBar.className = 'progress-bar bg-success';
                strengthText.textContent = 'Password strength: strong';
            }
        });

        // Password match checker
        const confirmPassword = document.getElementById('confirmpassword');
        const passwordMatch = document.getElementById('passwordMatch');
        
        confirmPassword.addEventListener('input', function() {
            if (this.value !== passwordInput.value) {
                passwordMatch.classList.remove('d-none');
            } else {
                passwordMatch.classList.add('d-none');
            }
        });

        // Image preview functionality
        const profilePictureInput = document.getElementById('profilepicture');
        const imagePreview = document.getElementById('imagePreview');
        const noPreviewText = document.getElementById('noPreviewText');
        
        profilePictureInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    noPreviewText.style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });

        // Location Services
        const locationStatus = document.getElementById('locationStatus');
        const addressInput = document.getElementById('address');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        const mapPreview = document.getElementById('mapPreview');
        const getLocationBtn = document.getElementById('getLocationBtn');
        const manualLocationBtn = document.getElementById('manualLocationBtn');
        const submitBtn = document.getElementById('submitBtn');
        let map;
        let marker;
        let geocoder;

        // Initialize map with default view
        function initializeMap() {
            // Default to a central location if no coordinates provided
            const defaultLat = 0;
            const defaultLng = 0;
            const defaultZoom = 2;
            
            mapPreview.style.display = "block";
            
            if (map) {
                map.remove();
            }
            
            map = L.map('mapPreview').setView([defaultLat, defaultLng], defaultZoom);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            // Initialize geocoder
            geocoder = L.Control.Geocoder.nominatim();
            
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
                        // Enable submit button since we have a valid location
                        submitBtn.disabled = false;
                        
                        locationStatus.innerHTML = '<i class="fas fa-check-circle me-2"></i> Location detected!';
                        locationStatus.className = 'location-status location-success';
                    } else {
                        addressInput.value = `Coordinates: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                        submitBtn.disabled = false;
                    }
                }
            );
        }

        // Get user's current location
        function detectUserLocation() {
            locationStatus.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Detecting your location...';
            locationStatus.className = 'location-status';
            
            // Initialize map if not already done
            if (!map) {
                map = initializeMap();
            }
            
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
                        
                        // Show the map anyway so users can set location manually
                        mapPreview.style.display = "block";
                    }
                );
            } else {
                locationStatus.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> Geolocation is not supported by this browser. Please set location manually.';
                locationStatus.className = 'location-status location-error';
                mapPreview.style.display = "block";
            }
        }

        // Set up manual location selection
        function setupManualLocationSelection() {
            // Make sure map is initialized
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
            // Try to detect location automatically
            detectUserLocation();
            
            // Set up event listeners
            getLocationBtn.addEventListener('click', detectUserLocation);
            manualLocationBtn.addEventListener('click', setupManualLocationSelection);
        });

        // Form validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            if (!latitudeInput.value || !longitudeInput.value || !addressInput.value.trim()) {
                e.preventDefault();
                alert('Please provide your location to continue');
                return false;
            }
            
            if (passwordInput.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>