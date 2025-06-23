<?php
session_start();
include 'connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E Care Hub - Your Complete Healthcare Solution</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-heart text-primary brand-heart"></i>
                E Care Hub
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <!-- <li class="nav-item">
                        <a class="nav-link" href="#">Features</a>
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link active" href="services.php">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Reviews</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contract.php">Contact</a>
                    </li>
                </ul>
                <!--  -->
                <div class="d-flex">
                    <!-- if some is login than show dash board button otherwise make it same  -->
                    <?php if (isset($_SESSION['donor_id'])): ?>
                        <a href="donordashboard.php" class="btn btn-dashboard me-2 d-flex align-items-center">
                            <i class="fas fa-user profile-icon me-2"></i>Dashboard
                        </a>
                    <?php else: ?>
                        <a href="selectlogin.php" class="btn btn-login me-2">Login</a>
                        <a href="selectregister.php" class="btn btn-register">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <section class="our-services py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Our Services</h2>
                <p class="section-subtitle">Explore our range of healthcare services designed to provide comprehensive care.</p>
            </div>

            <div class="row g-4">
                <!-- Appointment Booking -->
                <div class="col-md-12">
                    <div class="row service-card">
                        <div class="col-md-4">
                            <img src="image/ap.gif" 
                                 alt="Appointment Booking" 
                                 class= "service-icon-img">
                        </div>
                        <div class="col-md-8 service-text">
                            <h3>Appointment Booking</h3>
                            <p>Schedule appointments with specialists based on your convenience.</p>
                            <?php if (!isset($_SESSION['donor_id']) && !isset($_SESSION['ambulance_driver']) && !isset($_SESSION['user_id'])): ?>
                                 <a href="selectlogin.php" class="btn-service">Book Now</a>
                                 <!-- if book now is clicked than show alert message -->
                                    <script>
                                        document.querySelector('.btn-service').addEventListener('click', function() {
                                            alert('Please log in to book an appointment.');
                                        });
                                    </script>
                            <?php else: ?>
                                <a href="doctorlist.php" class="btn-service">Book Now</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Emergency Services -->
                <div class="col-md-12">
                    <div class="row service-card">
                    <div class="col-md-4">
                            <img src="image/ambulence.gif" 
                                 alt="ambulence" 
                                 class= "service-icon-img">
                    </div>
                        <div class="col-md-8 service-text">
                            <h3>Emergency Services</h3>
                            <p>Quick access to ambulance services and emergency care information.</p>
                            <?php if (!isset($_SESSION['donor_id']) && !isset($_SESSION['ambulance_driver']) && !isset($_SESSION['user_id'])): ?>
                                 <a href="selectlogin.php" class="btn-service emergency">Emergency Help</a>
                                 <!-- if Emergency Help is clicked than show alert message -->
                                    <script>
                                        document.querySelector('.emergency').addEventListener('click', function() {
                                            alert('Please log in to access emergency services.');
                                        });
                                    </script>
                            <?php else: ?>
                                <a href="emergencyservices.php" class="btn-service emergency">Emergency Help</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- AI Health Assistant -->
                <div class="col-md-12">
                    <div class="row service-card">
                    <div class="col-md-4">
                            <img src="image/chatbot.gif" 
                                 alt="chatbot" 
                                 class= "service-icon-img">
                    </div>
                    <div class="col-md-8 service-text">
                    <h3>AI Health Assistant</h3>
                        <p>Get preliminary health advice based on your symptoms.</p>
                        <?php if (!isset($_SESSION['donor_id']) && !isset($_SESSION['ambulance_driver']) && !isset($_SESSION['user_id'])): ?>
                                 <a href="selectlogin.php" class="btn-service ai">Chat Now</a>
                                 <!-- if book now is clicked than show alert message -->
                                    <script>
                                        document.querySelector('.ai').addEventListener('click', function() {
                                            alert('Please log in to access the AI Health Assistant.');
                                        });
                                    </script>
                            <?php else: ?>
                                <a href="comingsoon.php" class="btn-service ai">Chat Now</a>
                            <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

      <!-- Footer Section -->
      <footer class="footer-section bg-dark text-white pt-5 pb-4">
        <div class="container">
            <div class="row g-4">
                <!-- Brand Info -->
                <div class="col-lg-4 mb-4">
                    <div class="footer-brand">
                        <h2 class="text-white mb-3"><i class="fas fa-heart text-primary me-2"></i>E-Care Hub</h2>
                        <p>Your complete healthcare solution for consultations, medical records, medicine delivery, and more.</p>
                        <div class="social-icons mt-3">
                            <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="text-white me-3"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </div>
                <!-- Quick Links -->
                <div class="col-md-4 col-lg-2 mb-4">
                    <h5 class="footer-heading mb-4">Quick Links</h5>
                    <ul class="footer-links list-unstyled">
                        <li class="mb-2"><a href="index.php" class="text-white-50">Home</a></li>
                        <li class="mb-2"><a href="aboutus.php" class="text-white-50">About Us</a></li>
                        <li class="mb-2"><a href="services.php" class="text-white-50">Services</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50">Doctors</a></li>
                        <li><a href="#" class="text-white-50">Contact</a></li>
                    </ul>
                </div>
                <!-- Services -->
                <div class="col-md-4 col-lg-2 mb-4">
                    <h5 class="footer-heading mb-4">Services</h5>
                    <ul class="footer-links list-unstyled">
                        <li class="mb-2">
                            <a href="#" class="text-white-50">Doctor Consultation</a>
                        </li>
                        <li class="mb-2"><a href="#" class="text-white-50">Medical Records</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50">Medicine Delivery</a></li>
                        <li class="mb-2">
                        <?php if (!isset($_SESSION['donor_id'])): ?>
                                <a href="selectlogin.php" class="bd text-white-50">Blood Donation</a>
                                 <!-- if book now is clicked than show alert message -->
                                    <script>
                                        document.querySelector('.bd').addEventListener('click', function() {
                                            alert('Please log in to see donor list.');
                                        });
                                    </script>
                            <?php else: ?>
                                <a href="donorlist.php" class="bd text-white-50">Blood Donation</a>
                            <?php endif; ?>
                        </li>
                        <li><a href="#" class="text-white-50">Ambulance Services</a></li>
                    </ul>
                </div>
                <!-- Newsletter -->
                <div class="col-md-4 col-lg-4 mb-4">
                    <h5 class="footer-heading mb-4">Newsletter</h5>
                    <p class="text-white-50 mb-3">Subscribe to our newsletter for health tips and updates.</p>
                    <div class="newsletter-form">
                        <div class="input-group mb-3">
                            <input type="email" class="form-control" placeholder="Your email">
                            <button class="btn btn-primary" type="button">Subscribe</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Copyright -->
            <div class="footer-bottom pt-4 mt-4 border-top border-secondary">
                <div class="row">
                        <p class="mb-0 text-white-50 text-center">&copy; 2025 E Care Hub. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>


    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="js/script.js"></script>
</body>
</html>