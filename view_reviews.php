<?php
require 'connection.php';
session_start();

// Get all approved reviews
$stmt = $pdo->prepare("SELECT * FROM reviews WHERE is_approved = 1 ORDER BY created_at DESC");
$stmt->execute();
$reviews = $stmt->fetchAll();

// Calculate average rating
$avgStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE is_approved = 1");
$avgStmt->execute();
$stats = $avgStmt->fetch();
$avgRating = round($stats['avg_rating'], 1);
$totalReviews = $stats['total_reviews'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Reviews - E Care Hub</title><link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --light: #f8f9fa;
            --dark: #212529;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .reviews-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
            border-radius: 0 0 20px 20px;
        }
        
        .review-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            padding: 25px;
            transition: all 0.3s;
        }
        
        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .review-rating {
            color: #ffc107;
            font-size: 1.2rem;
        }
        
        .review-author {
            font-weight: 600;
            color: var(--primary);
        }
        
        .review-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .average-rating {
            font-size: 3rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .home-button {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }
        
        .navigation-buttons {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    
    <div class="home-button">
        <a href="index.php" class="btn btn-outline-light">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </div>
    
    <div class="reviews-header text-center">
        <div class="container">
            <h1>User Reviews</h1>
            <p class="lead">See what our users say about our services</p>
        </div>
    </div>
    
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center py-4">
                        <div class="average-rating mb-2"><?= $avgRating ?></div>
                        <div class="review-rating mb-3">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?= $i <= floor($avgRating) ? '' : '-half-alt' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="mb-0">Based on <?= $totalReviews ?> reviews</p>
                        <?php if (isset($_SESSION['donor_id']) || isset($_SESSION['ambulance_driver']) || isset($_SESSION['user_id'])): ?>
                            <a href="submit_review.php" class="btn btn-primary mt-3">Write a Review</a>
                        <?php else: ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="d-flex justify-content-between mb-3">
                                <div class="review-author"><?= htmlspecialchars($review['name']) ?></div>
                                <div class="review-date">
                                    <?= date('F j, Y', strtotime($review['created_at'])) ?>
                                </div>
                            </div>
                            
                            <div class="review-rating mb-3">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star<?= $i <= $review['rating'] ? '' : '-o' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            
                            <div class="review-text">
                                <?= nl2br(htmlspecialchars($review['review_text'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No reviews yet. Be the first to review!
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="navigation-buttons">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>