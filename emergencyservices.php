<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E Care Hub - Emergency Services</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/emergencyservices.css">
</head>
<body>
    <div class="hero-section">
        <div class="container emergency-container">
            <div class="row">
                <div class="col-12 text-center">
                    <i class="bi bi-hospital text-danger logo-img" style="font-size: 4rem;"></i>
                    <h1 class="display-5">Emergency Services</h1>
                    <p class="lead">Immediate access to critical care services when every minute counts.</p>
                    <!-- <a href="#services" class="btn btn-danger btn-lg emergency-btn pulse-animation">
                        <i class="bi bi-lightning-fill me-2"></i>View Services
                    </a> -->
                </div>
            </div>
        </div>
    </div>
    
    <div class="container mb-5" id="services">
        <div class="services-container">
            <div class="row g-4">
                <!-- Ambulance Service -->
                <div class="col-md-4">
                    <div class="card service-card ambulance-card">
                        <div class="card-body text-center p-4">
                            <div class="service-icon text-danger">
                                <i class="bi bi-truck"></i>
                            </div>
                            <h3 class="card-title">Ambulance Service</h3>
                            <p class="card-text">Request immediate emergency medical transport with trained paramedics to the nearest hospital.</p>
                            <a href="ambulancelist.php" class="btn btn-outline-danger mt-3">
                                <i class="bi bi-telephone-fill me-2"></i>Request Ambulance
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Medicine Delivery -->
                <div class="col-md-4">
                    <div class="card service-card medicine-card">
                        <div class="card-body text-center p-4">
                            <div class="service-icon text-primary">
                                <i class="bi bi-capsule"></i>
                            </div>
                            <h3 class="card-title">Medicine Delivery</h3>
                            <p class="card-text">Get essential medications delivered to your doorstep within hours during emergencies.</p>
                            <a href="comingsoon.php" class="btn btn-outline-primary mt-3">
                                <i class="bi bi-box me-2"></i>Order Medicine
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Donor List -->
                <div class="col-md-4">
                    <div class="card service-card donor-card">
                        <div class="card-body text-center p-4">
                            <div class="service-icon text-success">
                                <i class="bi bi-droplet"></i>
                            </div>
                            <h3 class="card-title">Donor List</h3>
                            <p class="card-text">Find available blood donors near your location for emergency blood transfusions.</p>
                            <a href="donorlist.php" class="btn btn-outline-success mt-3">
                                <i class="bi bi-search me-2"></i>Find Donors
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-5 mb-4">
            <div class="d-flex justify-content-center gap-3">
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="bi bi-house-door-fill"></i> Back to Home
                </a>
            </div>
        </div>
    
    <footer class="py-4 mt-auto">
        <div class="container">
            <div class="text-center">
                <div class="text-muted small">Copyright &copy; E Care Hub 2025</div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>