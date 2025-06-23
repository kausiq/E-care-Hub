<?php
require 'connection.php';

$message = "";
$email = $_GET['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'] ?? '';
    $email = $_POST['email'] ?? '';
    
    if (empty($otp) || empty($email)) {
        $message = "Please enter the OTP code.";
    } else {
        $stmt = $pdo->prepare("SELECT id, otp_code FROM users WHERE email = ? AND role = 'doctor'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $message = "Invalid email address.";
        } elseif ($user['otp_code'] != $otp) {
            $message = "Invalid OTP code. Please try again.";
        } else {
            // Update user as verified
            $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, otp_code = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            $message = "Email verified successfully! You can now login.";
            header('Location: index.php?verified=1');
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify Email - E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .verification-box {
            background: #ffffff;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin: 0 auto;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 25px;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .alert {
            border-radius: 8px;
        }
        .otp-input {
            letter-spacing: 2px;
            font-size: 1.5rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="verification-box">
            <h2 class="text-center mb-4"><i class="fas fa-envelope"></i> Verify Your Email</h2>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <p class="text-center mb-4">We've sent a 6-digit verification code to <strong><?php echo htmlspecialchars($email); ?></strong></p>
            
            <form method="POST" action="">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div class="form-group">
                    <label>Enter Verification Code</label>
                    <input type="text" name="otp" class="form-control otp-input" maxlength="6" required 
                           pattern="\d{6}" title="Please enter exactly 6 digits">
                </div>
                
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-block">Verify Email</button>
                </div>
            </form>
            
            <div class="text-center mt-4">
                <p>Didn't receive the code? <a href="#" id="resendLink">Resend OTP</a></p>
                <p id="resendMessage" class="text-success small" style="display:none;"></p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('resendLink').addEventListener('click', function(e) {
            e.preventDefault();
            
            // Disable the link temporarily
            this.style.pointerEvents = 'none';
            
            // Show loading message
            const resendMessage = document.getElementById('resendMessage');
            resendMessage.textContent = 'Sending new OTP...';
            resendMessage.style.display = 'block';
            
            // Send AJAX request to resend OTP
            fetch('resdotp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `email=<?php echo urlencode($email); ?>`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resendMessage.textContent = 'New OTP sent successfully!';
                    resendMessage.className = 'text-success small';
                } else {
                    resendMessage.textContent = 'Error: ' + (data.message || 'Failed to resend OTP');
                    resendMessage.className = 'text-danger small';
                }
                
                // Re-enable the link after 30 seconds
                setTimeout(() => {
                    document.getElementById('resendLink').style.pointerEvents = 'auto';
                    resendMessage.style.display = 'none';
                }, 30000);
            })
            .catch(error => {
                resendMessage.textContent = 'Error: ' + error;
                resendMessage.className = 'text-danger small';
            });
        });
    </script>
</body>
</html>