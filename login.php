<?php
session_start();
require_once 'config.php';

$page_title = "Login";
$errorMsg = "";

function generateCaptcha() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $captcha = substr(str_shuffle($chars), 0, 5);
    $_SESSION['captcha'] = $captcha;
    return $captcha;
}

function getGoogleAuthUrl() {
            $params = [
                'client_id' => GOOGLE_CLIENT_ID,
                'redirect_uri' => GOOGLE_REDIRECT_URI,
                'response_type' => 'code',
                'scope' => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
                'access_type' => 'online',
                'prompt' => 'select_account'
            ];
            
            return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
        }

if (isset($_GET['refresh_captcha'])) {
    echo generateCaptcha();
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $captchaInput = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';

    if (empty($captchaInput)) {
        $errorMsg = "Please complete the CAPTCHA.";
    } elseif (!isset($_SESSION['captcha']) || $captchaInput !== $_SESSION['captcha']) {
        $errorMsg = "CAPTCHA verification failed.";
    } else {
        $sql = "SELECT id, name, email, password, role, is_active FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $id, $name, $email, $hashed_password, $role, $is_active);
                if (mysqli_stmt_fetch($stmt)) {
                    if (password_verify($password, $hashed_password)) {
                        // Check if account is active
                        // Employees are always active, employers need admin approval
                        if ($is_active || $role === 'employee') {
                            $_SESSION['user'] = $id;
                            $_SESSION['name'] = $name;
                            $_SESSION['role'] = $role;
                            
                            // Redirect based on role
                            if ($role == 'admin') {
                                header("Location: admin_dashboard.php");
                            } elseif ($role == 'employee') {
                                header("Location: employee_dashboard.php");
                            } elseif ($role == 'employer') {
                                header("Location: employer_dashboard.php");
                            }
                            exit;
                        } else {
                            $errorMsg = "Your employer account is pending admin approval. Please try again later.";
                        }
                    } else {
                        $errorMsg = "Invalid password.";
                    }
                }
            } else {
                $errorMsg = "Email not found.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $errorMsg = "Database error: " . mysqli_error($conn);
        }
    }
    generateCaptcha();
} else {
    generateCaptcha();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Employment Portal</title>
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
        .login-card {
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
        .captcha-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .captcha-display {
            flex: 1;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 3px;
            font-size: 1.2rem;
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
        .google-btn {
            background: white;
            color: #757575;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .google-btn:hover {
            background: #f8f9fa;
            border-color: #dadce0;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: #6c757d;
        }

        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #dee2e6;
        }

        .divider-text {
            padding: 0 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <a href="index.php" class="btn btn-outline-secondary" style="text-decoration: none; position: absolute; left: 40px; box-shadow: none; margin-bottom: 680px;">
                <i class="fas fa-arrow-left me-2"></i>Back to Home
            </a>
    <div class="login-card">
        <div class="card-header">
            <h3><i class="fas fa-sign-in-alt me-2"></i>Account Login</h3>
        </div>
        <div class="card-body">
            <?php if ($errorMsg): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($errorMsg); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter the Email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="mb-3 form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter the password" required>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">CAPTCHA Verification</label>
                    <div class="captcha-container">
                        <div class="captcha-display" id="captcha-container">
                            <?php echo isset($_SESSION['captcha']) ? $_SESSION['captcha'] : ''; ?>
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="refreshCaptcha()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <input type="text" class="form-control" name="captcha" placeholder="Enter CAPTCHA" required>
                </div>
                
                <div class="d-grid mb-3">
                    <button type="submit" name="login" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </div>
                
                <div class="text-center">
                    <a href="forgot_password.php" class="text-decoration-none">
                        <i class="fas fa-lock me-1"></i>Forgot Password?
                    </a>
                </div>
            </form>
        </div>
        <br>
        <div class="card-footer text-center">
            Don't have an account?  <a href="signup.php" style="text-decoration:none">Sign Up</a>
        </div>
        <div class="divider">
            <span class="divider-text">or</span>
        </div>

        <div class="d-grid mb-3">
            <a href="<?= getGoogleAuthUrl() ?>" class="google-btn" style="text-decoration: none;">
                <img src="https://www.svgrepo.com/show/355037/google.svg" 
                    alt="Google" width="18" height="18">
                Continue with Google
            </a>
        </div>
        
    </div>

    <script>
        function refreshCaptcha() {
            fetch('login.php?refresh_captcha=1')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(data => {
                    document.getElementById('captcha-container').textContent = data.trim();
                })
                .catch(error => {
                    console.error('Error refreshing CAPTCHA:', error);
                    // Fallback: reload the page if fetch fails
                    location.reload();
                });
        }

        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
    </script>
</body>
</html>