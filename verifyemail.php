<?php
require 'connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $otp = $_POST['otp'];
    
    // Check if OTP is valid
    $stmt = $pdo->prepare("SELECT * FROM otp_verification 
                          WHERE email = ? AND otp = ? AND expires_at > NOW()");
    $stmt->execute([$email, $otp]);
    $verification = $stmt->fetch();
    
    if ($verification) {
        // Mark user as verified
        $stmt = $pdo->prepare("UPDATE donors SET is_verified = 1 WHERE email = ?");
        $stmt->execute([$email]);
        
        // Delete used OTP
        $stmt = $pdo->prepare("DELETE FROM otp_verification WHERE email = ?");
        $stmt->execute([$email]);
        
        // session_start();
        // $_SESSION['user_email'] = $email;
        // $_SESSION['verified'] = true;
        
        // Send welcome email
        sendWelcomeEmail($email);
        
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid or expired OTP. Please try again.";
    }
}

function sendWelcomeEmail($to) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'afiquehossain84@gmail.com';
        $mail->Password = 'jhcb qxfc tyhe tocq'; 
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('welcome@ecarehub.com', 'E Care Hub');
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to E Care Hub';
        $mail->Body    = "
            <h2>Welcome to E Care Hub!</h2>
            <p>Your account has been successfully verified.</p>
            <p>You can now login to your dashboard and update your profile.</p>
            <p>Thank you for joining our life-saving community!</p>
        ";
        $mail->AltBody = "Welcome to E Care Hub!\n\nYour account has been verified.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Welcome email could not be sent: {$mail->ErrorInfo}");
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification | E Care Hub</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/verifyemail.css">
</head>
<body>
    <div class="verification-container">
        <div class="verification-header">
            <i class="fas fa-envelope-circle-check"></i>
            <h2>Verify Your Email</h2>
            <p class="text-muted">Enter the 6-digit code sent to your email</p>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="verification-form">
            <form method="POST">
                <input type="hidden" name="email" value="<?= htmlspecialchars($_GET['email']) ?>">
                
                <div class="mb-4">
                    <label for="otp" class="form-label mb-3">Verification Code</label>
                    <input type="text" 
                           class="form-control form-control-lg otp-input" 
                           name="otp" 
                           placeholder="••••••" 
                           required
                           maxlength="6"
                           pattern="\d{6}"
                           title="Please enter exactly 6 digits">
                    <div class="form-text">Check your email for the 6-digit code</div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-verify">
                        <i class="fas fa-check-circle me-2"></i> Verify Account
                    </button>
                </div>
                
                <div class="resend-link mt-4">
                    <p class="text-muted">Didn't receive the code? 
                        <a href="#" id="resendOtp">Resend OTP</a>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Resend OTP functionality
        document.getElementById('resendOtp').addEventListener('click', function(e) {
            e.preventDefault();
            const email = "<?= htmlspecialchars($_GET['email']) ?>";
            
            fetch('resendotp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('A new OTP has been sent to your email!');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while resending OTP');
            });
        });
        
        // Auto-focus on OTP input
        document.querySelector('.otp-input').focus();
    </script>
</body>
</html>