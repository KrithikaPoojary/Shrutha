<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

// Verify token
if (empty($token)) {
    $error = "Invalid reset token.";
} else {
    // Check if token exists and is not expired
    $sql = "SELECT id, email FROM users WHERE reset_token = ? AND reset_expiry > NOW()";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) == 0) {
            $error = "Invalid or expired reset token.";
        } else {
            mysqli_stmt_bind_result($stmt, $user_id, $user_email);
            mysqli_stmt_fetch($stmt);
            
            // Process password reset
            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
                $new_password = trim($_POST['new_password']);
                $confirm_password = trim($_POST['confirm_password']);
                
                if (empty($new_password) || empty($confirm_password)) {
                    $error = "Please fill in all password fields.";
                } elseif (strlen($new_password) < 6) {
                    $error = "Password must be at least 6 characters long.";
                } elseif ($new_password !== $confirm_password) {
                    $error = "Passwords do not match.";
                } else {
                    // Hash new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Update password and clear reset token
                    $update_sql = "UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_sql);
                    
                    if ($update_stmt) {
                        mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user_id);
                        if (mysqli_stmt_execute($update_stmt)) {
                            $success = "Password has been reset successfully. You can now login with your new password.";
                            $token = ''; // Clear token after successful reset
                        } else {
                            $error = "Failed to reset password. Please try again: " . mysqli_error($conn);
                        }
                        mysqli_stmt_close($update_stmt);
                    } else {
                        $error = "Database error. Please try again later: " . mysqli_error($conn);
                    }
                }
            }
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
    <title>Reset Password - Employment Portal</title>
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
        .reset-card {
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
        .password-toggle {
            position: absolute;
            right: 35px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 2;
            background: none;
            border: none;
            font-size: 1rem;
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
    </style>
</head>
<body>
    <div class="reset-card">
        <div class="card-header">
            <h3><i class="fas fa-key me-2"></i>Reset Password</h3>
        </div>
        <div class="card-body">
            <?php if ($error && empty($token)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <div class="text-center mt-3">
                    <a href="forgot_password.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Forgot Password
                    </a>
                </div>
            <?php elseif ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <div class="text-center mt-3">
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                    </a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <p class="text-muted mb-4">Enter your new password below.</p>
                <form method="POST" action="">
                    <div class="mb-3 form-group">
                        <label for="new_password">New Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Enter new password" required minlength="6">
                            <button type="button" class="password-toggle" id="toggleNewPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Password must be at least 6 characters long.</div>
                    </div>
                    
                    <div class="mb-3 form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm new password" required minlength="6">
                            <button type="button" class="password-toggle" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" name="reset_password" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Reset Password
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

    <script>
        // Toggle password visibility
        const toggleNewPassword = document.getElementById('toggleNewPassword');
        const newPasswordInput = document.getElementById('new_password');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPasswordInput = document.getElementById('confirm_password');

        if (toggleNewPassword && newPasswordInput) {
            toggleNewPassword.addEventListener('click', function() {
                const type = newPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                newPasswordInput.setAttribute('type', type);
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }

        if (toggleConfirmPassword && confirmPasswordInput) {
            toggleConfirmPassword.addEventListener('click', function() {
                const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPasswordInput.setAttribute('type', type);
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>