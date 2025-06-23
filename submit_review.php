<?php
require_once 'connection.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $rating = (int)$_POST['rating'];
    $review_text = trim($_POST['review_text']);
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($review_text) || $rating < 1 || $rating > 5) {
        $error = 'Please fill all fields correctly. Rating must be between 1-5.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO reviews (name, email, rating, review_text, is_approved) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$name, $email, $rating, $review_text]);
            // $success = 'Thank you for your review! It will be visible after approval.';
        } catch (PDOException $e) {
            // $error = 'Error submitting your review. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Review - E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
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
        
        .review-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .rating-stars {
            font-size: 2rem;
            color: #ffc107;
            cursor: pointer;
        }
        
        .rating-stars .star {
            transition: all 0.2s;
        }
        
        .rating-stars .star:hover,
        .rating-stars .star.active {
            transform: scale(1.2);
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
            border-color: var(--secondary);
        }
        
        .navigation-buttons {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    
    <div class="review-container">
        <div class="text-start mb-4">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
        
        <h2 class="text-center mb-4">Share Your Experience</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="name" class="form-label">Your Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Rating</label>
                <div class="rating-stars mb-2">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star star" data-rating="<?= $i ?>"></i>
                    <?php endfor; ?>
                    <input type="hidden" name="rating" id="rating" value="0" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="review_text" class="form-label">Your Review</label>
                <textarea class="form-control" id="review_text" name="review_text" rows="5" required></textarea>
            </div>
            
            <div class="navigation-buttons">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
                <button type="submit" class="btn btn-primary px-5">
                    <i class="fas fa-paper-plane"></i> Submit Review
                </button>
            </div>
        </form>
    </div>

    <script>
        // Star rating functionality
        document.querySelectorAll('.star').forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                document.getElementById('rating').value = rating;
                
                // Update star display
                document.querySelectorAll('.star').forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                        s.classList.remove('far');
                        s.classList.add('fas');
                    } else {
                        s.classList.remove('active');
                        s.classList.remove('fas');
                        s.classList.add('far');
                    }
                });
            });
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>