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
    <style>
        /* CSS Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f8fafc;
            color: #333;
            line-height: 1.6;
        }

        .contact-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 20px;
        }

        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: #0ea5e9;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        .section-title h2:after {
            content: '';
            position: absolute;
            width: 70px;
            height: 3px;
            background: #0ea5e9;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
        }

        .section-title p {
            color: #64748b;
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto;
        }

        .contact-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .contact-info {
            flex: 1;
            min-width: 300px;
            padding: 40px;
            background: linear-gradient(135deg, #0ea5e9 0%, #38bdf8 100%);
            color: white;
        }

        .contact-info h3 {
            font-size: 1.8rem;
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 15px;
        }

        .contact-info h3:after {
            content: '';
            position: absolute;
            width: 50px;
            height: 2px;
            background: white;
            bottom: 0;
            left: 0;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
        }

        .info-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .info-icon i {
            font-size: 1.2rem;
        }

        .info-content h4 {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .info-content p, .info-content a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.3s;
        }

        .info-content a:hover {
            color: white;
            text-decoration: underline;
        }

        .contact-form {
            flex: 1;
            min-width: 300px;
            padding: 40px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #334155;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.2);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .submit-btn {
            background: #0ea5e9;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-block;
        }

        .submit-btn:hover {
            background: #0284c7;
            transform: translateY(-2px);
        }

        .brand-logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .brand-logo h2 {
            font-size: 2rem;
            color: white;
            font-weight: 700;
        }

        .brand-logo h2 span {
            color: #e0f2fe;
            font-weight: 300;
        }

        @media (max-width: 768px) {
            .contact-wrapper {
                flex-direction: column;
            }
            
            .contact-info, .contact-form {
                min-width: 100%;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
        }

        /* Font Awesome icons (you can link to actual FA in real implementation) */
        .info-icon i {
            font-style: normal;
            font-weight: bold;
        }
        .phone-icon:after { content: "📞"; }
        .email-icon:after { content: "✉️"; }
        .location-icon:after { content: "📍"; }
        .hours-icon:after { content: "🕒"; }
    </style>
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
                        <a class="nav-link" href="#">Reviews</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="contract.php">Contact</a>
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

    <div class="contact-container">
        <div class="section-title">
            <h2>Get In Touch With Us</h2>
            <p>Have questions or need assistance? Reach out to our team and we'll get back to you as soon as possible.</p>
        </div>
        
        <div class="contact-wrapper">
            <div class="contact-info">
                <div class="brand-logo">
                    <h2>E <span>Care Hub</span></h2>
                </div>
                
                <h3>Contact Information</h3>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="phone-icon"></i>
                    </div>
                    <div class="info-content">
                        <h4>Phone Number</h4>
                        <p><a href="tel:+918000436640">+88 0170 3560778</a></p>
                        <p><a href="tel:+918000436640">+88 0163 2572282</a></p>
                        <p><a href="tel:+918000436640">+88 0179 6808105</a></p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="email-icon"></i>
                    </div>
                    <div class="info-content">
                        <h4>Email</h4>
                        <p><a href="mailto:afiquehossain84@gmail.com">afiquehossain84@gmail.com</a></p>
                        <p><a href="mailto:mahfuzkhan726@gmail.com">mahfuzkhan726@gmail.com</a></p>
                        <p><a href="mailto:kausiqmondol@gmail.com">kausiqmondol@gmail.com</a></p>
                    </div>
                </div>
                
                <!-- <div class="info-item">
                    <div class="info-icon">
                        <i class="location-icon"></i>
                    </div>
                    <div class="info-content">
                        <h4>Location</h4>
                        <p>518, Rhythm Plaza, Amar Javan Circle,<br>Nikol, Ahmedabad, Gujarat - 382350</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="hours-icon"></i>
                    </div>
                    <div class="info-content">
                        <h4>Working Hours</h4>
                        <p>Monday To Saturday<br>09:00 AM To 06:00 PM</p>
                    </div>
                </div> -->
            </div>
            
            <div class="contact-form">
                <h3>Send Us a Message</h3>
                <form>
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Your Email</label>
                        <input type="email" id="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Your Message</label>
                        <textarea id="message" class="form-control" required></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">Send Message</button>
                </form>
            </div>
        </div>
    </div>

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
                        <li class="mb-2"><a href="#" class="text-white-50">About Us</a></li>
                        <li class="mb-2"><a href="services.php" class="text-white-50">Services</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50">Doctors</a></li>
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