<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        .page-header {
            text-align: center;
            padding: 60px 0 40px;
            background: linear-gradient(135deg, #0ea5e9 0%, #38bdf8 100%);
            color: white;
            margin-bottom: 50px;
            position: relative;
        }

        .home-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .home-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .page-header h1 {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .page-header p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
            opacity: 0.9;
        }

        /* About Company Section */
        .about-company {
            display: flex;
            align-items: center;
            gap: 50px;
            margin-bottom: 80px;
            flex-wrap: wrap;
        }

        .about-text {
            flex: 1;
            min-width: 300px;
        }

        .about-text h2 {
            font-size: 2.2rem;
            color: #0ea5e9;
            margin-bottom: 20px;
        }

        .about-text p {
            margin-bottom: 20px;
            color: #64748b;
            font-size: 1.1rem;
        }

        .about-image {
            flex: 1;
            min-width: 300px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .about-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* Team Section */
        .team-section {
            margin-bottom: 80px;
            text-align: center;
        }

        .section-title {
            margin-bottom: 50px;
        }

        .section-title h2 {
            font-size: 2.2rem;
            color: #0ea5e9;
            margin-bottom: 15px;
        }

        .section-title p {
            color: #64748b;
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto;
        }

        .team-members {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 30px;
        }

        .team-member {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            width: 350px;
        }

        .member-image {
            height: 300px;
            overflow: hidden;
        }

        .member-image img {
            width: 100%;
            height: fit-content;
            object-fit: cover;
        }

        .member-info {
            padding: 25px;
            text-align: center;
        }

        .member-info h3 {
            font-size: 1.5rem;
            margin-bottom: 5px;
            color: #0f172a;
        }

        .member-contact {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .contact-link {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0f2fe;
            color: #0ea5e9;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .page-header {
                padding: 60px 0 30px;
            }
            
            .page-header h1 {
                font-size: 2.2rem;
            }
            
            .about-company {
                flex-direction: column;
            }
            
            .about-text, .about-image {
                min-width: 100%;
            }
            
            .team-member {
                width: 100%;
                max-width: 350px;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <a href="index.php" class="home-btn">
            <i class="fas fa-home"></i> Home
        </a>
        <div class="container">
            <h1>About E Care Hub</h1>
            <p>Compassionate care meets innovative technology to transform healthcare experiences</p>
        </div>
    </div>

    <div class="container">
        <section class="about-company">
            <div class="about-text">
                <h2>Our Story</h2>
                <p>At E Care Hub, we believe that everyone deserves access to quality healthcare, regardless of their location or circumstances. Our team is committed to making this vision a reality through innovation, compassion, and dedication.</p>
            </div>
            <div class="about-image">
                <img src="image/WhatsApp Image 2025-05-09 at 6.37.17 PM.jpeg" alt="Healthcare team discussing">
            </div>
        </section>

        <section class="team-section">
            <div class="section-title">
                <h2>Meet Our Founders</h2>
                <p>The passionate individuals behind E Care Hub's vision and success</p>
            </div>

            <div class="team-members">
                <div class="team-member">
                    <div class="member-image">
                        <img src="image/afique.jpeg" alt="Md. Afique Hossain">
                    </div>
                    <div class="member-info">
                        <h3>Md. Afique Hossain</h3>
                        <div class="member-contact">
                            <a href="tel:+8801703560778" class="contact-link" title="Call">
                                <i class="fas fa-phone"></i>
                            </a>
                            <a href="mailto:afiquehossain84@gmail.com" class="contact-link" title="Email">
                                <i class="fas fa-envelope"></i>
                            </a>
                            <a href="https://www.linkedin.com/in/md-afique-hossain-2b79262b1/" class="contact-link" title="LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="team-member">
                    <div class="member-image">
                        <img src="image/mahfuz.jpg" alt="Md. Montajul Islam Mahfuz">
                    </div>
                    <div class="member-info">
                        <h3>Md. Montajul Islam Mahfuz</h3>
                        <div class="member-contact">
                            <a href="tel:+8801632572282" class="contact-link" title="Call">
                                <i class="fas fa-phone"></i>
                            </a>
                            <a href="mailto:mahfuzkhan726@gmail.com" class="contact-link" title="Email">
                                <i class="fas fa-envelope"></i>
                            </a>
                            <a href="https://www.linkedin.com/in/md-mahfuz-190591199/" class="contact-link" title="LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="team-member">
                    <div class="member-image">
                        <img src="image/kausiq.jpg" alt="Kausiq Mondol">
                    </div>
                    <div class="member-info">
                        <h3>Kausiq Mondol</h3>
                        <div class="member-contact">
                            <a href="tel:+8801796808105" class="contact-link" title="Call">
                                <i class="fas fa-phone"></i>
                            </a>
                            <a href="mailto:kausiqmondol@gmail.com" class="contact-link" title="Email">
                                <i class="fas fa-envelope"></i>
                            </a>
                            <a href="https://www.linkedin.com/in/kausiq-mondol-4373bb195/" class="contact-link" title="LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</body>
</html>