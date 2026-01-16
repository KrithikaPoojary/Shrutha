<?php
session_start();
require_once 'config.php';

// Redirect if not admin
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php'; // Or adjust path if using manual installation

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id']) && isset($_POST['action'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];
    
    // Get user details before processing
    $user_query = mysqli_query($conn, "SELECT name, email FROM users WHERE id = $user_id");
    $user_data = mysqli_fetch_assoc($user_query);
    
    if ($action == 'approve') {
        $sql = "UPDATE users SET is_active=1 WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Send approval email
            sendApprovalEmail($user_data['email'], $user_data['name'], true);
            $_SESSION['approval_success'] = "User approved successfully!";
        } else {
            $_SESSION['approval_error'] = "Error approving user: " . mysqli_error($conn);
        }
    } 
    elseif ($action == 'reject') {
        // Get user details before deletion
        $user_email = $user_data['email'];
        $user_name = $user_data['name'];
        
        $sql = "DELETE FROM users WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Send rejection email
            sendApprovalEmail($user_email, $user_name, false);
            $_SESSION['approval_success'] = "User rejected successfully!";
        } else {
            $_SESSION['approval_error'] = "Error rejecting user: " . mysqli_error($conn);
        }
    }
    
    mysqli_stmt_close($stmt);
}

header("Location: admin_dashboard.php");
exit;

/**
 * Send approval/rejection email to user
 */
function sendApprovalEmail($email, $name, $approved = true) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Replace with your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'shruthaportal@gmail.com'; // Replace with your email
        $mail->Password   = 'sttt vkri eeug bxxq'; // Replace with your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('shruthaportal@gmail.com', 'Shrutha Portal');
        $mail->addAddress($email, $name);
        
        // Content
        $mail->isHTML(true);
        
        if ($approved) {
            $mail->Subject = 'Account Approved - Shrutha Portal';
            $mail->Body    = "
                <h2>Account Approved</h2>
                <p>Dear $name,</p>
                <p>Your account has been approved by the administrator. You can now login to the Shrutha Portal.</p>
                <p><strong>Login URL:</strong> <a href=\"http://localhost/employee_portal/login.php\">http://localhost/employee_portal/login.php</a></p>
                <p>Thank you for registering with us!</p>
                <br>
                <p>Best regards,<br>Shrutha Portal Team</p>
            ";
        } else {
            $mail->Subject = 'Account Registration Rejected - Shrutha Portal';
            $mail->Body    = "
                <h2>Registration Rejected</h2>
                <p>Dear $name,</p>
                <p>We regret to inform you that your account registration has been rejected by the administrator.</p>
                <p>If you believe this is a mistake, please contact the administrator or try registering again with complete information.</p>
                <br>
                <p>Best regards,<br>Shrutha Portal Team</p>
            ";
        }
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>