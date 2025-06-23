<?php
session_start();
require 'connection.php';

// Initialize variables
$drivers = [];
$filter_availability = isset($_GET['availability']) ? $_GET['availability'] : '';
$filter_radius = isset($_GET['radius']) ? (int)$_GET['radius'] : 10; // Default 10 km radius
$user_lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$user_lng = isset($_GET['lng']) ? (float)$_GET['lng'] : null;
$address = isset($_GET['address']) ? $_GET['address'] : '';

// Get drivers based on filters
if ($user_lat && $user_lng) {
    // Calculate distance using Haversine formula (in km)
    $query = "SELECT 
                id, fullname, email, phonenumber, address, latitude, longitude, availability,
                profilepicture, profilepicturetype,
                ambulancenumber, organization, firstaidcertified, cprcertified, emtcertified,
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
                sin(radians(latitude)))) AS distance
              FROM ambulancedrivers
              WHERE isverified = 1";
    
    // Add availability filter if selected
    if (!empty($filter_availability)) {
        $query .= " AND availability = ?";
    }
    
    $query .= " HAVING distance <= ? ORDER BY distance ASC";
    
    $params = [$user_lat, $user_lng, $user_lat, $filter_radius];
    if (!empty($filter_availability)) {
        array_unshift($params, $filter_availability);
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $drivers = $stmt->fetchAll();
} elseif (!empty($filter_availability)) {
    // Filter only by availability if no location provided
    $stmt = $pdo->prepare("SELECT * FROM ambulancedrivers WHERE isverified = 1 AND availability = ?");
    $stmt->execute([$filter_availability]);
    $drivers = $stmt->fetchAll();
} else {
    // Show all verified drivers if no filters
    $stmt = $pdo->prepare("SELECT * FROM ambulancedrivers WHERE isverified = 1");
    $stmt->execute();
    $drivers = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Ambulance Drivers | E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="css/ambulancelist.css">
</head>
<body>
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1><i class="fas fa-ambulance me-2"></i>Find Ambulance Drivers</h1>
                <p class="text-muted">Search for available ambulance drivers near you</p>
                <!-- Add Back to Home button at top -->
                <div class="mb-4">
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
        
        <div class="search-container">
            <form method="GET" action="" id="searchForm">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label for="address" class="form-label">Search Location</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="address" name="address" placeholder="Enter your location" value="<?php echo htmlspecialchars($address); ?>">
                            <button class="btn btn-outline-secondary" type="button" id="detectLocationBtn">
                                <i class="fas fa-location-arrow"></i>
                            </button>
                        </div>
                        <input type="hidden" id="lat" name="lat" value="<?php echo $user_lat; ?>">
                        <input type="hidden" id="lng" name="lng" value="<?php echo $user_lng; ?>">
                        <div id="locationStatus"></div>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="availability" class="form-label">Availability</label>
                        <select class="form-select" id="availability" name="availability">
                            <option value="">All Availability</option>
                            <option value="Full-time" <?php echo ($filter_availability === 'Full-time') ? 'selected' : ''; ?>>Full-time</option>
                            <option value="Part-time" <?php echo ($filter_availability === 'Part-time') ? 'selected' : ''; ?>>Part-time</option>
                            <option value="On-call" <?php echo ($filter_availability === 'On-call') ? 'selected' : ''; ?>>On-call</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="radius" class="form-label">Radius (km)</label>
                        <select class="form-select" id="radius" name="radius">
                            <option value="5" <?php echo ($filter_radius == 5) ? 'selected' : ''; ?>>5 km</option>
                            <option value="10" <?php echo ($filter_radius == 10) ? 'selected' : ''; ?>>10 km</option>
                            <option value="20" <?php echo ($filter_radius == 20) ? 'selected' : ''; ?>>20 km</option>
                            <option value="50" <?php echo ($filter_radius == 50) ? 'selected' : ''; ?>>50 km</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                </div>
            </form>
            
            <?php if ($user_lat && $user_lng): ?>
                <div id="map"></div>
            <?php endif; ?>
        </div>
        
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner-border text-danger" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Finding nearby ambulance drivers...</p>
        </div>
        
        <div class="row" id="driversContainer">
            <?php foreach ($drivers as $driver): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card driver-card">
                        <div class="card-header driver-header py-3">
                            <h5 class="card-title mb-0 text-center">Ambulance #<?php echo htmlspecialchars($driver['ambulancenumber']); ?></h5>
                        </div>
                        <div class="card-body text-center pt-5 pb-3">
                            <?php if ($driver['profilepicture']): ?>
                                <img src="data:<?php echo htmlspecialchars($driver['profilepicturetype']); ?>;base64,<?php echo base64_encode($driver['profilepicture']); ?>" 
                                     class="driver-avatar mb-3" alt="Driver Photo">
                            <?php else: ?>
                                <div class="driver-avatar mb-3 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-user-circle fa-3x text-secondary"></i>
                                </div>
                            <?php endif; ?>
                            
                            <h4 class="card-title"><?php echo htmlspecialchars($driver['fullname']); ?></h4>
                            
                            <div class="mb-3">
                                <span class="badge badge-availability 
                                    <?php echo $driver['availability'] === 'Full-time' ? 'badge-fulltime' : 
                                          ($driver['availability'] === 'Part-time' ? 'badge-parttime' : 'badge-oncall'); ?>">
                                    <?php echo htmlspecialchars($driver['availability']); ?>
                                </span>
                                
                                <?php if (isset($driver['distance'])): ?>
                                    <span class="badge distance-badge">
                                        <?php echo number_format($driver['distance'], 1); ?> km away
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <?php if ($driver['firstaidcertified']): ?>
                                    <span class="badge bg-info text-dark certification-badge">First Aid</span>
                                <?php endif; ?>
                                <?php if ($driver['cprcertified']): ?>
                                    <span class="badge bg-warning text-dark certification-badge">CPR</span>
                                <?php endif; ?>
                                <?php if ($driver['emtcertified']): ?>
                                    <span class="badge bg-danger certification-badge">EMT</span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="card-text text-muted">
                                <i class="fas fa-hospital me-2"></i>
                                <?php echo $driver['organization'] ? htmlspecialchars($driver['organization']) : 'Independent'; ?>
                            </p>
                            
                            <p class="card-text">
                                <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                <?php echo htmlspecialchars($driver['address']); ?>
                            </p>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <div class="d-flex justify-content-between">
                                <a href="tel:<?php echo htmlspecialchars($driver['phonenumber']); ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-phone me-1"></i> Call
                                </a>
                                <a href="mailto:<?php echo htmlspecialchars($driver['email']); ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-envelope me-1"></i> Email
                                </a>
                                <button class="btn btn-sm btn-outline-danger view-on-map" 
                                        data-lat="<?php echo $driver['latitude']; ?>" 
                                        data-lng="<?php echo $driver['longitude']; ?>"
                                        data-name="<?php echo htmlspecialchars($driver['fullname']); ?>">
                                    <i class="fas fa-map me-1"></i> Map
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Map Modal -->
    <div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mapModalLabel">Driver Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="detailMap" style="height: 400px; width: 100%;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
     <div class="text-center mt-5 mb-4">
            <div class="d-flex justify-content-center gap-3">
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="bi bi-house-door-fill"></i> Back to Home
                </a>
                <a href="emergencyservices.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Emergency Services
                </a>
            </div>
        </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Global variables
        let map, detailMap;
        let isFirstLoad = <?php echo empty($drivers) ? 'true' : 'false'; ?>;
        
        // Initialize main map if we have coordinates
        function initializeMap() {
            <?php if ($user_lat && $user_lng): ?>
                map = L.map('map').setView([<?php echo $user_lat; ?>, <?php echo $user_lng; ?>], 12);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);
                
                // Add marker for search location
                L.marker([<?php echo $user_lat; ?>, <?php echo $user_lng; ?>]).addTo(map)
                    .bindPopup("Your Location")
                    .openPopup();
                
                // Add markers for each driver
                <?php foreach ($drivers as $driver): ?>
                    L.marker([<?php echo $driver['latitude']; ?>, <?php echo $driver['longitude']; ?>]).addTo(map)
                        .bindPopup("<b><?php echo addslashes($driver['fullname']); ?></b><br><?php echo addslashes($driver['ambulancenumber']); ?>");
                <?php endforeach; ?>
            <?php endif; ?>
        }
        
        // Location detection
        function detectLocation() {
            const locationStatus = document.getElementById('locationStatus');
            locationStatus.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Detecting your location...';
            locationStatus.className = 'location-status';
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        document.getElementById('lat').value = position.coords.latitude;
                        document.getElementById('lng').value = position.coords.longitude;
                        
                        // Show loading spinner
                        document.getElementById('loadingSpinner').style.display = 'block';
                        document.getElementById('driversContainer').style.display = 'none';
                        
                        // Reverse geocode to get address
                        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${position.coords.latitude}&lon=${position.coords.longitude}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.display_name) {
                                    document.getElementById('address').value = data.display_name;
                                }
                                locationStatus.innerHTML = '<i class="fas fa-check-circle me-2 location-success"></i> Location detected!';
                                locationStatus.className = 'location-status location-success';
                                
                                // Submit the form
                                document.getElementById('searchForm').submit();
                            })
                            .catch(error => {
                                locationStatus.innerHTML = '<i class="fas fa-exclamation-circle me-2 location-error"></i> Could not get address, but using coordinates';
                                locationStatus.className = 'location-status location-error';
                                document.getElementById('searchForm').submit();
                            });
                    },
                    function(error) {
                        let errorMessage;
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage = "Location access was denied. Please enable it or enter location manually.";
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage = "Location information is unavailable. Please enter location manually.";
                                break;
                            case error.TIMEOUT:
                                errorMessage = "The request to get location timed out. Please try again or enter location manually.";
                                break;
                            default:
                                errorMessage = "An unknown error occurred. Please enter location manually.";
                        }
                        
                        locationStatus.innerHTML = `<i class="fas fa-exclamation-circle me-2 location-error"></i> ${errorMessage}`;
                        locationStatus.className = 'location-status location-error';
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                locationStatus.innerHTML = '<i class="fas fa-exclamation-circle me-2 location-error"></i> Geolocation is not supported by your browser. Please enter your location manually.';
                locationStatus.className = 'location-status location-error';
            }
        }
        
        // Initialize modal map when view buttons are clicked
        function setupMapModalButtons() {
            const viewButtons = document.querySelectorAll('.view-on-map');
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const lat = parseFloat(this.getAttribute('data-lat'));
                    const lng = parseFloat(this.getAttribute('data-lng'));
                    const name = this.getAttribute('data-name');
                    
                    const modal = new bootstrap.Modal(document.getElementById('mapModal'));
                    const modalTitle = document.getElementById('mapModalLabel');
                    modalTitle.textContent = name + "'s Location";
                    
                    // Initialize or update the modal map
                    if (!detailMap) {
                        detailMap = L.map('detailMap').setView([lat, lng], 15);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                        }).addTo(detailMap);
                    } else {
                        detailMap.setView([lat, lng], 15);
                    }
                    
                    // Clear existing markers and add new one
                    detailMap.eachLayer(layer => {
                        if (layer instanceof L.Marker) {
                            detailMap.removeLayer(layer);
                        }
                    });
                    
                    L.marker([lat, lng]).addTo(detailMap)
                        .bindPopup("<b>" + name + "</b>")
                        .openPopup();
                    
                    modal.show();
                });
            });
        }
        
        // Auto-detect location on first load if no filters are set
        document.addEventListener('DOMContentLoaded', function() {
            initializeMap();
            setupMapModalButtons();
            
            if (isFirstLoad && !<?php echo $user_lat ? 'true' : 'false'; ?> && !<?php echo $filter_availability ? 'true' : 'false'; ?>) {
                detectLocation();
            }
            
            // Set up location detection button
            document.getElementById('detectLocationBtn').addEventListener('click', detectLocation);
        });
    </script>
</body>
</html>