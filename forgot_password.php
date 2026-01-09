<?php
session_start();
require_once 'config.php';

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Adjust path as needed

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset'])) {
    $email = trim($_POST['email']);
    
    // Check if email exists
    $sql = "SELECT id, name FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            mysqli_stmt_bind_result($stmt, $user_id, $user_name);
            mysqli_stmt_fetch($stmt);
            
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date("Y-m-d H:i:s", time() + 3600); // 1 hour expiry
            
            // Store token in database
            $update_sql = "UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            
            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, "sss", $token, $expiry, $email);
                if (mysqli_stmt_execute($update_stmt)) {
                    // Create reset link
                    $reset_link = "https://shrutha.com/reset_password.php?token=$token";
                    
                    // Send email using PHPMailer
                    $mail = new PHPMailer(true);
                    
                    try {
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com'; // Set your SMTP server
                        $mail->SMTPAuth = true;
                        $mail->Username = 'shruthaportal@gmail.com'; // SMTP username
                        $mail->Password = 'sttt vkri eeug bxxq'; // SMTP password (use App Password for Gmail)
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;
                        
                        // Recipients
                        $mail->setFrom('shruthaportal@gmail.com', 'Shrutha Portal');
                        $mail->addAddress($email, $user_name);
                        
                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Password Reset Request - Shrutha Portal';
                        
                        $mail->Body = "
                        <html>
                        <head>
                            <title>Password Reset Request</title>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                .header { background: linear-gradient(to right, #4e73df, #6f8de8); color: white; padding: 20px; text-align: center; }
                                .content { background: #f9f9f9; padding: 20px; border-radius: 5px; }
                                .button { display: inline-block; padding: 12px 24px; background: linear-gradient(to right, #4e73df, #6f8de8); color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
                                .footer { margin-top: 20px; padding: 10px; text-align: center; color: #666; font-size: 12px; }
                            </style>
                        </head>
                        <body>
                            <div class='container'>
                                <div class='header'>
                                    <h2>Shrutha Portal</h2>
                                </div>
                                <div class='content'>
                                    <h3>Hello " . htmlspecialchars($user_name) . ",</h3>
                                    <p>You have requested to reset your password for your Shrutha Portal account.</p>
                                    <p>Click the button below to reset your password:</p>
                                    <p style='text-align: center;'>
                                        <a href='$reset_link' class='button'>Reset Password</a>
                                    </p>
                                    <p>Or copy and paste this link in your browser:</p>
                                    <p style='word-break: break-all; background: #eee; padding: 10px; border-radius: 3px;'>$reset_link</p>
                                    <p><strong>This link will expire in 1 hour.</strong></p>
                                    <p>If you didn't request this reset, please ignore this email. Your password will remain unchanged.</p>
                                </div>
                                <div class='footer'>
                                    <p>This is an automated message. Please do not reply to this email.</p>
                                    <p>&copy; " . date('Y') . " Shrutha Portal. All rights reserved.</p>
                                </div>
                            </div>
                        </body>
                        </html>
                        ";
                        
                        $mail->send();
                        $success = "Password reset instructions have been sent to your email. Please check your inbox (and spam folder).";
                        
                    } catch (Exception $e) {
                        $error = "Failed to send email. Error: " . $mail->ErrorInfo;
                        // Fallback: show reset link for demo
                        $success = "Email sending failed. For demo purposes, use this reset link: <a href='$reset_link' target='_blank'>Reset Password</a>";
                    }
                } else {
                    $error = "Failed to process reset request: " . mysqli_error($conn);
                }
                mysqli_stmt_close($update_stmt);
            } else {
                $error = "Database error. Please try again later: " . mysqli_error($conn);
            }
        } else {
            $error = "Email not found.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "Database error. Please try again later: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Employment Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        .forgot-card {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(to right, #4e73df, #6f8de8);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .card-body {
            padding: 30px;
            background: white;
        }
        .btn-primary {
            background: linear-gradient(to right, #4e73df, #6f8de8);
            border: none;
        }
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 500;
            color: #333;
        }
        .input-with-icon {
            position: relative;
        }
        .input-with-icon i:first-child {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 2;
        }
        .form-control {
            width: 100%;
            padding: 14px 46px 14px 46px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            position: relative;
        }
        .form-control:focus {
            outline: none;
            border-color: #4e73df;
            box-shadow: 0 0 0 4px rgba(78, 115, 223, 0.1);
        }
        .demo-link {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="forgot-card">
        <div class="card-header">
            <h3><i class="fas fa-lock me-2"></i>Forgot Password</h3>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php 
                    // Split the success message to handle the demo link properly
                    $parts = explode("<br><br><strong>Demo Reset Link:</strong>", $success);
                    echo $parts[0];
                    if (isset($parts[1])) {
                        echo "<br><br><strong>Demo Reset Link:</strong>" . $parts[1];
                    }
                    ?>
                </div>
                <div class="text-center mt-3">
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Login
                    </a>
                </div>
            <?php else: ?>
                <p class="text-muted mb-4">Enter your email address and we'll send you instructions to reset your password.</p>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" name="reset" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Send Reset Instructions
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <a href="login.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>Back to Login
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>