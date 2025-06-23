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
                        <a class="nav-link" href="services.php">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_reviews.php">Reviews</a>
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
                    <?php elseif (isset($_SESSION['ambulance_driver'])): ?>
                        <a href="ambulancedashboard.php" class="btn btn-dashboard me-2 d-flex align-items-center">
                            <i class="fas fa-user profile-icon me-2"></i>Dashboard
                        </a>
                    <?php elseif (isset($_SESSION['user_id']) && $_SESSION['role'] !== 'patient'): ?>
                        <a href="doctordashboard.php" class="btn btn-dashboard me-2 d-flex align-items-center">
                            <i class="fas fa-user profile-icon me-2"></i>Dashboard
                        </a>
                    <?php elseif (isset($_SESSION['user_id'])): ?>
                        <a href="patientdashboard.php" class="btn btn-dashboard me-2 d-flex align-items-center">
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="container position-relative">
            <h1>Your Complete Healthcare Solution</h1>
            <p class="hero-text">
                Consult doctors, store medical records, order medicines, donate blood,
                and access ambulance services - all in one platform.
            </p>
            <div class="hero-buttons">
                 <!-- if some is login than do show Get Started otherwise make it sill show  -->
                <?php if (!isset($_SESSION['donor_id']) && !isset($_SESSION['ambulance_driver']) && !isset($_SESSION['user_id'])): ?>
                    <a href="selectregister.php" class="btn btn-get-started">Get Started</a>
                <?php else: ?>
                <?php endif; ?>
                <!-- <a href="selectlogin.php" class="btn btn-get-started">Get Started</a> -->
                <!-- <a href="#" class="btn btn-learn-more">Learn More</a> -->
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Comprehensive Healthcare Solutions</h2>
                <p class="section-subtitle">E Care Hub provides a complete ecosystem for all your healthcare needs in one place.</p>
            </div>
            
            <div class="row g-4">
                <!-- Doctor Consultation -->
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card text-center">
                        <div class="icon-container mb-4">
                            <i class="fas fa-stethoscope icon-blue"></i>
                        </div>
                        <h3 class="feature-title">Doctor Consultation</h3>
                        <p class="feature-text">Connect with qualified doctors through video or chat consultations.</p>
                    </div>
                </div>
                
                <!-- Medical Records -->
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card text-center">
                        <div class="icon-container mb-4">
                            <i class="fas fa-file-medical icon-blue"></i>
                        </div>
                        <h3 class="feature-title">Medical Records</h3>
                        <p class="feature-text">Securely store and access your medical reports and prescriptions.</p>
                    </div>
                </div>
                
                <!-- Medicine Delivery -->
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card text-center">
                        <div class="icon-container mb-4">
                            <i class="fas fa-pills icon-blue"></i>
                        </div>
                        <h3 class="feature-title">Medicine Delivery</h3>
                        <p class="feature-text">Order prescribed medicines and get them delivered to your doorstep.</p>
                    </div>
                </div>
                
                <!-- Blood Donation -->
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card text-center">
                        <div class="icon-container mb-4">
                            <i class="fas fa-tint" style="font-size: 40px;color:#DC3545;"></i>
                        </div>
                        <h3 class="feature-title">Blood Donation</h3>
                        <p class="feature-text">Register as a donor or find blood donors in emergency situations.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Add this right after your Features section -->
    <section class="ai-recommendations py-5">
        <div class="container">
            <div class="ai-container row g-4 align-items-center">
                <div class="col-lg-6">
                    <h2 class="ai-heading">AI-Powered Health Recommendations</h2>
                    <p class="ai-description mb-4">Our advanced AI system analyzes your symptoms and provides preliminary health recommendations. Get connected with the right specialists based on your health concerns.</p>
                    
                    <div class="ai-checklist">
                        <div class="ai-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Describe your symptoms to our AI chatbot</span>
                        </div>
                        <div class="ai-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Get preliminary health insights</span>
                        </div>
                        <!-- <div class="ai-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Receive specialist recommendations</span>
                        </div> -->
                        <!-- <div class="ai-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Book appointments with recommended doctors</span>
                        </div> -->
                        <div class="ai-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Continuous learning system for improved accuracy</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1579684385127-1ef15d508118?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" 
                     alt="AI Health Assistant" 
                     class="img-fluid rounded-3 shadow-sm">
                </div>
            </div>
        </div>
    </section>
    
    <!-- Add this after your AI Recommendations section -->
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
                <?php

// Get 3 most recent approved reviews
$stmt = $pdo->prepare("SELECT name, rating, review_text, created_at FROM reviews WHERE is_approved = 1 ORDER BY created_at DESC LIMIT 3");
$stmt->execute();
$reviews = $stmt->fetchAll();
?>

<section class="testimonials py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title mb-3">What Our Users Say</h2>
            <p class="section-subtitle text-muted">Hear from patients and doctors who have experienced E Care Hub.</p>
        </div>

        <div class="row g-4">
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): 
                    $reviewDate = date('F j, Y', strtotime($review['created_at']));
                ?>
                    <div class="col-lg-4">
                        <div class="testimonial-card h-100 p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-1"><?= htmlspecialchars($review['name']) ?></h5>
                                    <small class="text-muted"><?= $reviewDate ?></small>
                                </div>
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?= $i <= $review['rating'] ? '' : '-o' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <blockquote class="mb-0">
                                <p class="font-italic">"<?= nl2br(htmlspecialchars($review['review_text'])) ?>"</p>
                            </blockquote>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <div class="alert alert-info">
                        No reviews yet. Be the first to share your experience!
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (count($reviews) > 0): ?>
        <div class="text-center mt-4">
            <a href="view_reviews.php" class="btn btn-outline-primary">
                <i class="fas fa-comments me-2"></i> View All Reviews
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>


    <!-- Animated Scrolling Text Section -->
<section class="scrolling-text-section py-4 bg-primary">
    <div class="container-fluid px-0">
        <div class="scrolling-text-wrapper overflow-hidden">
            <div class="scrolling-text-content d-flex align-items-center">
                <!-- Repeat this block for continuous animation -->
                <div class="scrolling-text-item text-nowrap me-5">
                    <span class="text-white fs-4 fw-bold me-3"><i class="fas fa-heartbeat me-2"></i>24/7 Doctor Consultations</span>
                    <span class="text-white fs-4 fw-bold me-3"><i class="fas fa-pills me-2"></i>Medicine Delivery</span>
                    <span class="text-white fs-4 fw-bold me-3"><i class="fas fa-file-medical me-2"></i>Digital Health Records</span>
                    <span class="text-white fs-4 fw-bold me-3"><i class="fas fa-ambulance me-2"></i>Emergency Services</span>
                    <span class="text-white fs-4 fw-bold me-3"><i class="fas fa-tint me-2"></i>Blood Donation Network</span>
                </div>
                <!-- Duplicate for seamless looping -->
                <div class="scrolling-text-item text-nowrap me-5" aria-hidden="true">
                    <span class="text-white fs-4 fw-bold me-3"><i class="fas fa-heartbeat me-2"></i>24/7 Doctor Consultations</span>
                    <span class="text-white fs-4 fw-bold me-3"><i class="fas fa-pills me-2"></i>Medicine Delivery</span>
                    <span class="text-white fs-4 fw-bold me-3"><i class="fas fa-file-medical me-2"></i>Digital Health Records</span>
                    <span class="text-white fs-4 fw-bold me-3"><i class="fas fa-ambulance me-2"></i>Emergency Services</span>
                    <span class="text-white fs-4 fw-bold me-3"><i class="fas fa-tint me-2"></i>Blood Donation Network</span>
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
                        <li class="mb-2"><a href="doctorlist.php" class="text-white-50">Doctors</a></li>
                        <li><a href="contract.php" class="text-white-50">Contact</a></li>
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