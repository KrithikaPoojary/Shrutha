<?php
session_start();
require_once 'config.php';
$page_title = "Sign Up";
include 'header.php';
$errorMsg = '';
$successMsg = '';

$fixed_role = false;
$selected_role = 'employee'; // default

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

if (isset($_GET['fixed']) && $_GET['fixed'] == '1' && isset($_GET['role'])) {
    $fixed_role = true;
    $selected_role = $_GET['role'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
     // If role is fixed, use the fixed role, otherwise use form input
    if ($fixed_role && isset($_GET['role'])) {
        $role = $_GET['role'];
    } else {
        $role = trim($_POST['role']);
    }
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $payment_ref = trim($_POST['payment_ref']);
    
    // Validation
    $valid = true;
    if (empty($name)) {
        $errorMsg .= "Full name is required.<br>";
        $valid = false;
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg .= "Valid email is required.<br>";
        $valid = false;
    }
    
    if (empty($password) || strlen($password) < 8) {
        $errorMsg .= "Password must be at least 8 characters.<br>";
        $valid = false;
    }
    
    if ($password !== $confirm_password) {
        $errorMsg .= "Passwords do not match.<br>";
        $valid = false;
    }
    
    // Only require payment reference for employers
    if ($role === 'employer' && empty($payment_ref)) {
        $errorMsg .= "Payment reference is required for employer accounts.<br>";
        $valid = false;
    }
    
    if (!isset($_POST['terms'])) {
        $errorMsg .= "You must agree to the Terms and Conditions.<br>";
        $valid = false;
    }
    
    if ($valid) {
        // Check if email exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errorMsg = "Email already exists.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Set is_active based on role
            // Employees: active immediately (is_active = 1)
            // Employers: inactive until admin approval (is_active = 0)
            $is_active = ($role === 'employee') ? 1 : 0;
            
            // For employees, set payment_ref to NULL
            if ($role === 'employee') {
                $payment_ref = NULL;
            }
            
            // Insert user with role
            $sql = "INSERT INTO users (name, email, password, role, payment_ref, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssssi", $name, $email, $hashed_password, $role, $payment_ref, $is_active);
            
            if (mysqli_stmt_execute($stmt)) {
                // Set success message based on role
                if ($role === 'employee') {
                    $successMsg = "Account created successfully! You can now login.";
                } else {
                    $successMsg = "Account created successfully! Your account is pending admin approval. You will be able to login once approved.";
                }
                // Clear form
                $name = $email = $password = $confirm_password = $payment_ref = '';
            } else {
                $errorMsg = "Error: " . mysqli_error($conn);
            }
        }
        mysqli_stmt_close($stmt);
    }
}
?>

    <style>
                * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #8b5cf6;
            --accent: #f59e0b;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --light-gray: #e2e8f0;
            --success: #10b981;
            --error: #ef4444;
            --card-radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        body {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        header {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 1.2rem 0;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .logo i {
            color: var(--accent);
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 1.8rem;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 1.1rem;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        nav a:hover, nav a.active {
            background: rgba(255, 255, 255, 0.15);
        }

        /* Hero Section */
        .hero {
            padding: 4rem 0;
            text-align: center;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%232563eb" fill-opacity="0.1" d="M0,224L48,213.3C96,203,192,181,288,170.7C384,160,480,160,576,176C672,192,768,224,864,229.3C960,235,1056,213,1152,197.3C1248,181,1344,171,1392,165.3L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg') no-repeat bottom;
            background-size: contain;
        }

        .hero h1 {
            font-size: 3.2rem;
            margin-bottom: 1rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .hero p {
            font-size: 1.4rem;
            max-width: 700px;
            margin: 0 auto 2rem;
            color: var(--gray);
        }

        .highlight {
            background: linear-gradient(120deg, rgba(251, 211, 141, 0.3), rgba(251, 211, 141, 0));
            padding: 0 5px;
            border-radius: 4px;
        }

        /* Main Content */
        .main-content {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            padding: 2rem 0 4rem;
        }

        /* Signup Form */
        .signup-card {
            background: white;
            border-radius: var(--card-radius);
            box-shadow: var(--shadow);
            padding: 2.2rem;
            margin-bottom: 2rem;
            max-width: 700px;
            margin: 0 auto;
        }

        .signup-card h2 {
            font-size: 1.8rem;
            margin-bottom: 1.8rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .signup-card h2 i {
            color: var(--accent);
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative; /* Add this */
        }

        .form-group label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 500;
            color: var(--dark);
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i:first-child {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            z-index: 2;
        }

        /* #user {
            transform: translateY(-120%);
        } */

        .password-toggle {
            position: absolute;
            right: 50px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--gray);
            z-index: 2;
            background: none;
            border: none;
            font-size: 1rem;
        }

        .form-control {
            width: 100%;
            padding: 14px 46px 14px 46px; /* Equal padding on both sides for icons */
            border: 2px solid var(--light-gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .payment-section {
            background: var(--light);
            border-radius: 10px;
            padding: 1.5rem;
            margin: 2rem 0;
            border-left: 4px solid var(--accent);
        }

        .payment-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .payment-title i {
            color: var(--success);
        }

        .qr-container {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-top: 15px;
        }

        .qr-placeholder {
            width: 150px;
            height: 150px;
            background: linear-gradient(45deg, #e0e7ff, #c7d2fe);
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .qr-placeholder img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .qr-placeholder i {
            font-size: 50px;
            color: var(--primary);
        }

        .btn {
            display: inline-block;
            width: 100%;
            padding: 16px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            margin-top: 10px;
        }

        .btn:hover {
            background: linear-gradient(to right, var(--primary-dark), #7c3aed);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }

        /* Login Link */
        .login-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--light-gray);
            font-size: 1.1rem;
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .login-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Footer */
        footer {
            background: var(--dark);
            color: white;
            padding: 3rem 0 2rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
        }

        .footer-column h4 {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-column h4::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--accent);
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.8rem;
        }

        .footer-links a {
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: white;
            padding-left: 5px;
        }

        .copyright {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid #334155;
            color: #94a3b8;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 900px) {
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            nav ul {
                gap: 0.8rem;
            }
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1.2rem;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
            }
            
            .qr-container {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .signup-card {
                padding: 1.5rem;
            }
        }
        .payment-section.hidden {
            display: none;
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
        
        .google-section.hidden {
            display: none;
        }
    </style>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Employee Portal</h1>
            <p>Find your next career opportunity and access <span class="highlight">exclusive benefits</span> as part of our community</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container main-content">
        <!-- Signup Form -->
        <div class="content">
            <div class="signup-card">
                <h2><i class="fas fa-user-plus"></i> Create Your Account</h2>
                
                <?php if ($errorMsg): ?>
                    <div class="alert alert-danger" style="color: #ef4444; background-color: #fee2e2; padding: 10px; border-radius: 6px; margin-bottom: 20px;">
                        <?php echo $errorMsg; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($successMsg): ?>
                    <div class="alert alert-success" style="color: #10b981; background-color: #d1fae5; padding: 10px; border-radius: 6px; margin-bottom: 20px;">
                        <?php echo $successMsg; ?>
                    </div>
                <?php endif; ?>

                <?php
                    // Check if we have Google user data
                    $google_name = '';
                    $google_email = '';
                    if (isset($_SESSION['google_user'])) {
                        $google_name = $_SESSION['google_user']['name'];
                        $google_email = $_SESSION['google_user']['email'];
                        // Clear the session data after using it
                        unset($_SESSION['google_user']);
                    }
                ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="fullname">Full Name</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="fullname" name="name" class="form-control" placeholder="Enter your full name" 
                                value="<?= htmlspecialchars($google_name ?: (isset($_POST['name']) ? $_POST['name'] : '')) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" 
                                value="<?= htmlspecialchars($google_email ?: (isset($_POST['email']) ? $_POST['email'] : '')) ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">I am registering as:</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user-tag" id="user"></i>
                            <?php if ($fixed_role): ?>
                                <!-- Display disabled field for fixed role -->
                                <input type="text" class="form-control" value="<?php echo ucfirst($selected_role); ?>" disabled>
                                <input type="hidden" name="role" value="<?php echo $selected_role; ?>">
                                <!-- <small class="text-muted" style="display: block; margin-top: 5px;">
                                    Role selection is fixed for this registration type
                                </small> -->
                            <?php else: ?>
                                <!-- Regular dropdown for free selection -->
                                <select class="form-control" id="role" name="role" required onchange="togglePaymentSection()">
                                    <option value="employee" <?= (isset($_POST['role']) && $_POST['role'] === 'employee') ? 'selected' : '' ?>>Employee</option>
                                    <option value="employer" <?= (isset($_POST['role']) && $_POST['role'] === 'employer') ? 'selected' : '' ?>>Employer</option>
                                </select>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Create a password" required>
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm-password">Confirm Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm-password" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
                            <button type="button" class="password-toggle" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Payment Section - Only shown for employers -->
                    <div class="payment-section <?php echo (isset($_POST['role']) && $_POST['role'] === 'employee') || !isset($_POST['role']) ? 'hidden' : ''; ?>" id="paymentSection">
                        <h3 class="payment-title">
                            <i class="fas fa-rupee-sign"></i>
                            One-Time Registration Fee: ₹1
                        </h3>
                        <p>Complete your registration by making a nominal payment</p>
                        
                        <div class="qr-container">
                            <div class="qr-placeholder">
                                 <img src="frame.png" alt="Pay ₹1 QR Code">
                            </div>
                            <div>
                                <p>Scan QR code to pay with UPI</p>
                                <p><small>Payment reference required for verification</small></p>
                                <div class="form-group" style="margin-top: 15px;">
                                    <div class="input-with-icon">
                                        <i class="fas fa-receipt"></i>
                                        <input type="text" name="payment_ref" class="form-control" placeholder="Enter payment reference" 
                                               value="<?php echo isset($_POST['payment_ref']) ? htmlspecialchars($_POST['payment_ref']) : ''; ?>" 
                                               id="paymentRef">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group terms" style="display: flex; align-items: flex-start; margin-top: 20px;">
                        <input type="checkbox" id="terms" name="terms" style="margin-right: 10px; margin-top: 5px;" 
                               <?php echo isset($_POST['terms']) ? 'checked' : ''; ?> required>
                        <label for="terms" style="margin-bottom: 0;">I agree to the <a href="#" style="color: var(--primary);">Terms and Conditions</a></label>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-user-check"></i> Create Account
                    </button>
                </form>
                
                <!-- Google Signup Section -->
                <div class="google-section <?php echo ($fixed_role && $selected_role === 'employer') || (isset($_POST['role']) && $_POST['role'] === 'employer') ? 'hidden' : ''; ?>" id="googleSection">
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
                
                <!-- Login Link -->
                <div class="login-link">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Function to toggle payment section visibility
        function togglePaymentSection() {
            <?php if (!$fixed_role): ?>
                // Only allow changing if role is not fixed
                var role = document.getElementById('role').value;
            <?php else: ?>
                // Use fixed role
                var role = '<?php echo $selected_role; ?>';
            <?php endif; ?>
            
            var paymentSection = document.getElementById('paymentSection');
            var paymentRefInput = document.getElementById('paymentRef');
            var googleSection = document.getElementById('googleSection');
            
            if (role === 'employer') {
                paymentSection.classList.remove('hidden');
                if (paymentRefInput) {
                    paymentRefInput.setAttribute('required', 'required');
                }
                // Hide Google section for employers
                if (googleSection) {
                    googleSection.classList.add('hidden');
                }
            } else {
                paymentSection.classList.add('hidden');
                if (paymentRefInput) {
                    paymentRefInput.removeAttribute('required');
                }
                // Show Google section for employees
                if (googleSection) {
                    googleSection.classList.remove('hidden');
                }
            }
        }

        // Function to set role based on URL parameter
        function setRoleFromURL() {
            const urlParams = new URLSearchParams(window.location.search);
            const role = urlParams.get('role');
            const fixed = urlParams.get('fixed');
            
            // Only set role if it's not fixed (free selection)
            if (!fixed && (role === 'employer' || role === 'employee')) {
                document.getElementById('role').value = role;
                togglePaymentSection();
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            togglePaymentSection();
            <?php if ($fixed_role): ?>
                // Hide the role dropdown and show appropriate message
                console.log('Role is fixed to: <?php echo $selected_role; ?>');
            <?php endif; ?>
            setRoleFromURL(); // Add this line
        });
        
        
       
        
        // Client-side validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const role = document.getElementById('role').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return;
            }
            
            // Only validate payment reference for employers
            if (role === 'employer') {
                const paymentRef = document.getElementById('paymentRef');
                if (!paymentRef || !paymentRef.value.trim()) {
                    e.preventDefault();
                    alert('Please enter your payment reference number!');
                    return;
                }
            }
            
            const terms = document.getElementById('terms');
            if (!terms.checked) {
                e.preventDefault();
                alert('You must agree to the Terms and Conditions!');
                return;
            }
        });
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });

        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPasswordInput = document.getElementById('confirm-password');

        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    </script>
<?php include 'footer.php';?>