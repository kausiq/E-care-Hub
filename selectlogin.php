<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In | E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style1.css">
</head>
<body>
    <div class="registration-container">
        <div class="registration-header">
            <h2><i class="fas fa-heartbeat me-2"></i>E Care Hub Log In</h2>
            <p>Join our healthcare community</p>
        </div>
        
        <div class="role-selection">
            <h3 class="text-center mb-4">Select Your Role</h3>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <a href="patientlogin.php" class="role-card">
                        <div class="role-icon">
                            <i class="fas fa-user-injured"></i>
                        </div>
                        <h4>Patient</h4>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="doctorlogin.php" class="role-card">
                        <div class="role-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <h4>Doctor</h4>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="donorlogin.php" class="role-card">
                        <div class="role-icon">
                            <i class="fas fa-tint" style="font-size: 40px;color:#DC3545;"></i>
                        </div>
                        <h4>Blood Donor</h4>
                    </a>
                </div>
                <div class="col-md-6 col-lg-3">
                    <a href="ambulancelogin.php" class="role-card">
                        <div class="role-icon">
                            <i class="fas fa-ambulance"></i>
                        </div>
                        <h4>Ambulance Driver</h4>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>