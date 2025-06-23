<?php
session_start();
require 'connection.php';
if (!isset($_SESSION['donor_id'])) {
    header('Location: donorlogin.php');
    exit();
}
$donor_id = $_SESSION['donor_id'];

// Check if this is a password change request
if (isset($_POST['action']) && $_POST['action'] === 'password_change') {
    // Password change flow
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate password input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error_message'] = "All password fields are required.";
        header("Location: donordashboard.php");
        exit();
    }
    
    if ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = "New passwords do not match.";
        header("Location: donordashboard.php");
        exit();
    }
    
    // Verify current password
    try {
        $stmt = $pdo->prepare("SELECT password FROM donors WHERE id = ?");
        $stmt->execute([$donor_id]);
        $stored_hash = $stmt->fetchColumn();
        
        if (!password_verify($current_password, $stored_hash)) {
            $_SESSION['error_message'] = "Current password is incorrect.";
            header("Location: donordashboard.php");
            exit();
        }
        
        // Hash new password and update
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $pdo->prepare("UPDATE donors SET password = ? WHERE id = ?");
        $update_stmt->execute([$new_hash, $donor_id]);
        
        $_SESSION['success_message'] = "Your password has been updated successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Password update failed: " . $e->getMessage();
    }
    
    header("Location: donordashboard.php");
    exit();
}

// Regular profile update flow
$phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
$address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
$last_donation_date = $_POST['last_donation_date'] ?? null;

// Determine if the donor can donate
$can_donate = 0;
if (!empty($last_donation_date)) {
    $last_donation = new DateTime($last_donation_date);
    $today = new DateTime();
    $interval = $today->diff($last_donation);
    if ($interval->days >= 120) {
        $can_donate = 1;
    }
}

try {
    $stmt = $pdo->prepare("UPDATE donors SET phone = ?, address = ?, last_donation_date = ?, can_donate = ? WHERE id = ?");
    $stmt->execute([
        $phone,
        $address,
        $last_donation_date,
        $can_donate,
        $donor_id
    ]);
    $_SESSION['success_message'] = "Your information has been updated successfully.";
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Update failed: " . $e->getMessage();
}

header("Location: donordashboard.php");
exit();
?>