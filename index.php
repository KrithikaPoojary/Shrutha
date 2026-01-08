<?php
session_start();
require_once 'config.php'; // Add this line to include database configuration

// Add login functionality similar to login.php
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
    <title>Employee Portal - Your Career Journey Starts Here</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --primary-light: #ebf5ff;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1a1f36;
            --light: #f8fafc;
            --gray: #94a3b8;
            --white: #ffffff;
            --gradient-start: #4361ee;
            --gradient-end: #3f37c9;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: var(--dark);
            line-height: 1.6;
            overflow-x: hidden;
            background-attachment: fixed;
        }

        /* Background Particles */
        .particles-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.1;
        }

        /* Navigation */
        .navbar {
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
             background: rgba(67, 97, 238, 0.95);
        }

        .navbar-brand {
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 0.5px;
            font-size: 1.8rem;
        }

        .navbar-brand i {
            color: var(--white);
            background: rgba(67, 97, 238, 0.1);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-weight: 500;
            position: relative;
            font-size: 1.1rem;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--white) !important;
            background-color:  rgba(255,255,255,0.15);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 10%;
            width: 80%;
            height: 3px;
            background: var(--white);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .nav-link:hover::after, .nav-link.active::after {
            transform: scaleX(1);
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1497215728101-856f4ea42174?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            padding: 160px 0 120px;
            text-align: center;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            min-height: 100vh;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, transparent 0%, rgba(0,0,0,0.8) 100%);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            animation: fadeInUp 1s ease-out;
        }

        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 2.5rem;
            opacity: 0.9;
            animation: fadeInUp 1s ease-out 0.2s forwards;
            opacity: 0;
        }

        .hero-btns {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            animation: fadeInUp 1s ease-out 0.4s forwards;
            opacity: 0;
        }

        /* Floating animation for hero elements */
        .floating {
            animation: float 8s ease-in-out infinite;
        }

        /* Stats Section */
        .stats-section {
            padding: 80px 0;
            background: var(--white);
            position: relative;
        }

        .stats-container {
            position: relative;
            z-index: 2;
        }

        .counter-box {
            background: linear-gradient(145deg, #ffffff, #f0f4f8);
            border-radius: 20px;
            padding: 40px 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            text-align: center;
            transition: all 0.4s ease;
            border: 1px solid rgba(67, 97, 238, 0.1);
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .counter-box:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(67, 97, 238, 0.15);
            border-color: rgba(67, 97, 238, 0.2);
        }

        .counter-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .counter-box:hover::before {
            transform: translateX(0);
        }

        .counter {
            font-size: 3.5rem;
            font-weight: 700;
            background: linear-gradient(to right, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
            line-height: 1;
        }

        .counter-label {
            font-size: 1.1rem;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        /* Features Section */
        .features-section {
            padding: 100px 0;
            position: relative;
            overflow: hidden;
            background: url('https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80') no-repeat center center;
            background-size: cover;
            background-attachment: fixed;
        }

        .features-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(248, 250, 252, 0.92);
        }

        .section-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 70px;
            position: relative;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(to right, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 5px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 5px;
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: var(--gray);
            margin-bottom: 0;
        }

        .feature-card {
            background-color: var(--white);
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.07);
            margin-bottom: 30px;
            border: none;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            height: 100%;
            position: relative;
            overflow: hidden;
            z-index: 1;
            backdrop-filter: blur(5px);
            background: rgba(255, 255, 255, 0.85);
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            z-index: -1;
            transition: height 0.4s ease;
        }

        .feature-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 50px rgba(67, 97, 238, 0.15);
        }

        .feature-card:hover::before {
            height: 100%;
        }

        .feature-card:hover * {
            color: white !important;
        }

        .feature-icon {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--accent-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            color: var(--primary);
            font-size: 2.5rem;
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: scale(1.1);
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .feature-text {
            color: var(--gray);
            margin-bottom: 0;
        }

        /* Job Listings */
        .jobs-section {
            padding: 100px 0;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--accent-light) 100%);
            position: relative;
            overflow: hidden;
        }

        .jobs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 50px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .jobs-title {
            font-size: 2.2rem;
            font-weight: 800;
            margin: 0;
            color: var(--dark);
        }

        .job-card {
            background-color: var(--white);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.07);
            margin-bottom: 30px;
            transition: all 0.3s ease;
            border-left: 5px solid var(--primary);
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .job-card:hover {
            transform: translateY(-10px) rotate(2deg);
            box-shadow: 0 15px 40px rgba(67, 97, 238, 0.2);
        }

        .job-badge {
            background: linear-gradient(to right, var(--accent), var(--secondary));
            color: white;
            padding: 6px 18px;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 20px;
        }

        .job-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .job-company {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 15px;
            display: block;
        }

        .job-location {
            display: flex;
            align-items: center;
            color: var(--gray);
            margin-bottom: 20px;
        }

        .job-location i {
            margin-right: 8px;
        }

        .job-salary {
            font-weight: 700;
            color: var(--success);
            font-size: 1.2rem;
            margin-bottom: 20px;
        }

        .job-description {
            color: var(--gray);
            margin-bottom: 25px;
        }

        /* Registration Section */
        .registration-section {
            padding: 100px 0;
            position: relative;
            background: var(--white);
            background: url('https://images.unsplash.com/photo-1497366754035-f200968a6e72?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80') no-repeat center center;
            background-size: cover;
            background-attachment: fixed;
        }

        .registration-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.92);
        }

        .registration-container {
            display: flex;
            align-items: center;
            gap: 50px;
            position: relative;
        }

        .registration-content {
            flex: 1;
        }

        .registration-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 25px;
            color: var(--dark);
        }

        .registration-text {
            font-size: 1.2rem;
            color: var(--gray);
            margin-bottom: 30px;
        }

        .feature-list {
            list-style: none;
            padding: 0;
            margin-bottom: 40px;
        }

        .feature-list li {
            margin-bottom: 15px;
            padding-left: 40px;
            position: relative;
            font-size: 1.1rem;
        }

        .feature-list li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 28px;
            height: 28px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .feature-list li i {
            position: absolute;
            left: 7px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 0.9rem;
            z-index: 1;
        }

        .login-form-container {
            flex: 1;
            background: var(--white);
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            border-top: 5px solid var(--primary);
            transition: all 0.4s ease;
            backdrop-filter: blur(5px);
            background: rgba(255, 255, 255, 0.9);
        }

        .login-form-container:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 60px rgba(67, 97, 238, 0.15);
        }

        .form-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 30px;
            color: var(--dark);
            position: relative;
            padding-bottom: 15px;
        }

        .form-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 70px;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark);
        }

        .form-control {
            padding: 15px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .form-check {
            margin-bottom: 25px;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary), var(--accent));
            border: none;
            padding: 15px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.3);
            width: 100%;
            font-size: 1.1rem;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: 0.5s;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(67, 97, 238, 0.4);
            background: linear-gradient(to right, var(--primary-dark), var(--secondary));
        }

        .forgot-password {
            text-align: center;
            margin-top: 20px;
            display: block;
            color: var(--primary);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .forgot-password:hover {
            color: var(--primary-dark);
            text-decoration: none;
        }

        /* Diaries Section */
        .diaries-section {
            padding: 100px 0;
            background: var(--light);
            position: relative;
            background: url('https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80') no-repeat center center;
            background-size: cover;
            background-attachment: fixed;
        }

        .diaries-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(248, 250, 252, 0.92);
        }

        .diary-card {
            background-color: var(--white);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.07);
            margin-bottom: 30px;
            transition: all 0.3s ease;
            border-top: 5px solid var(--primary);
            height: 100%;
            position: relative;
        }

        .diary-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(67, 97, 238, 0.15);
        }

        .diary-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--dark);
        }

        .diary-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .diary-meta-item {
            display: flex;
            align-items: center;
            color: var(--gray);
        }

        .diary-meta-item i {
            margin-right: 8px;
            color: var(--primary);
        }

        .diary-content {
            color: var(--gray);
            margin-bottom: 25px;
        }

        /* Testimonials */
        .testimonials-section {
            padding: 100px 0;
            position: relative;
        }

        .testimonial-card {
            background-color: var(--white);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.07);
            margin-bottom: 30px;
            transition: all 0.3s ease;
            border-top: 5px solid var(--primary);
            position: relative;
            height: 100%;
        }

        .testimonial-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(67, 97, 238, 0.15);
        }

        .testimonial-content {
            font-style: italic;
            color: var(--gray);
            margin-bottom: 30px;
            font-size: 1.1rem;
            line-height: 1.8;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .testimonial-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--primary);
        }

        .testimonial-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .testimonial-info h5 {
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark);
        }

        .testimonial-info p {
            color: var(--primary);
            margin-bottom: 0;
            font-weight: 500;
        }

        /* Footer */
         .footer-links a {
        position: relative;
        z-index: 10;
        pointer-events: auto;
        }
        
        .footer-links li {
            position: relative;
            z-index: 10;
        }
        
        .footer {
            background: linear-gradient(to right, var(--dark) 0%, #0f172a 100%);
            color: var(--white);
            padding-top: 100px;
            padding-bottom: 40px;
            padding-left: 100px;
            padding-right: 100px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%231e293b' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.1;
        }

        .footer-content {
            position: relative;
            z-index: 2;
        }

        .footer-logo {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-logo i {
            color: var(--white);
            background: rgba(255,255,255,0.15);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .footer-text {
            color: rgba(255,255,255,0.7);
            margin-bottom: 30px;
            max-width: 400px;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .social-links a {
            position: relative;
            z-index: 10;
            pointer-events: auto !important;
            text-decoration: none;
        }

        .social-links {
            position: relative;
            z-index: 10;
        }

        .social-links a:hover {
            
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(67, 97, 238, 0.3);
        }
        .social-links a i {
            color: var(--white);
            background: rgba(255,255,255,0.15);
            width: 40px;
            height: 40px;
            border-radius: 10%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .footer-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 25px;
            color: var(--white);
            position: relative;
            padding-bottom: 15px;
        }

        .footer-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--primary);
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 15px;
        }

        .footer-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-links a:hover {
            color: var(--white);
            transform: translateX(5px);
        }

        .footer-links a i {
            color: var(--primary);
        }

        .contact-info {
            list-style: none;
            padding: 0;
        }

        .contact-info li {
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            color: rgba(255,255,255,0.7);
        }

        .contact-info i {
            color: var(--primary);
            font-size: 1.2rem;
            margin-top: 5px;
        }

        .copyright {
            text-align: center;
            padding-top: 40px;
            color: rgba(255,255,255,0.5);
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 60px;
        }

        .captcha-container {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
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
            font-family: monospace;
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
            right: 45px;
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
            padding-left: 45px !important;
            padding-right: 45px !important;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.8s ease forwards;
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
        .delay-5 { animation-delay: 0.5s; }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        @keyframes wave {
            0% { transform: translateX(0); }
            100% { transform: translateX(100%); }
        }

        /* Particle animation */
        @keyframes particle {
            0% { transform: translate(0, 0) rotate(0deg); opacity: 1; }
            100% { transform: translate(var(--tx), var(--ty)) rotate(360deg); opacity: 0; }
        }

        /* Responsive */
        @media (max-width: 992px) {
            .hero-title {
                font-size: 2.8rem;
            }
            
            .registration-container {
                flex-direction: column;
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.3rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
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
    <!-- Background Particles -->
    <div class="particles-bg" id="particles"></div>
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#" style="display: flex; align-items: center; height: 60px; padding: 5px 0;">
                <img src="shrutha.png" alt="Shrutha Logo" style="height: 170px; width: auto; margin-right: -40px; position: relative; z-index: 1;">
                
                <span style="position: relative; z-index: 2;">EmployeePortal</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <?php if(!isset($_SESSION['user']) || (isset($_SESSION['user']) && $_SESSION['role'] != 'employer')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= 
                            in_array(basename($_SERVER['PHP_SELF']), ['signup.php', 'resume_builder.php']) ? 'active' : '' ?>" 
                            href="#" id="registerDropdown" role="button" data-bs-toggle="dropdown">
                            Register
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="signup.php?role=employer&fixed=1">Company Registration</a></li>
                            <li><a class="dropdown-item" href="signup.php?role=employee&fixed=1">Employee Registration</a></li>
                            <li>
                                <?php if(isset($_SESSION['user'])): ?>
                                    <a class="dropdown-item" href="resume_builder.php">Resume Builder</a>
                                <?php else: ?>
                                    <a class="dropdown-item" href="signup.php?role=employee&fixed=1">Resume Builder</a>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="visitor_diaries.php">Diaries</a>
                    </li>
                    <li class="nav-item">
                        <?php if(isset($_SESSION['user']) && $_SESSION['role'] == 'employer'): ?>
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'job_postings.php' ? 'active' : '' ?>" href="job_postings.php">Job Posting</a>
                        <?php else: ?>
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'jobs.php' ? 'active' : '' ?>" href="jobs.php">Jobs</a>
                        <?php endif; ?>
                    </li>

                    <li class="nav-item">
                        <?php if(isset($_SESSION['user']) && $_SESSION['role'] == 'employer'): ?>
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'candidate_search.php' ? 'active' : '' ?>" href="candidate_search.php">Candidate Search</a>
                        <?php else: ?>
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'resources.php' ? 'active' : '' ?>" href="resources.php">Resources</a>
                        <?php endif; ?>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                <div class="d-flex ms-lg-3 mt-3 mt-lg-0">
                    <?php if(isset($_SESSION['user'])): ?>
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-2"></i> <?= isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'User'; ?>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php if ($_SESSION['role'] == 'admin') {echo 'admin_dashboard.php';} elseif ($_SESSION['role'] == 'employer') {echo 'employer_dashboard.php';} elseif ($_SESSION['role'] == 'employee') {echo 'employee_dashboard.php';} else {echo 'index.php';}?>">
                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?= $_SESSION['role'] == 'employer' ? 'employer_profile.php' : 'profile.php' ?>"><i class="fas fa-user-cog me-2"></i> Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light me-2"><i class="fas fa-sign-in-alt me-2"></i> Login</a>
                        <a href="signup.php" class="btn btn-light"><i class="fas fa-user-plus me-2"></i> Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content floating">
                <h1 class="hero-title">Find Your Dream Career Path</h1>
                <p class="hero-subtitle">Connect with top employers, showcase your skills, and take the next step in your professional journey</p>
                <div class="hero-btns">
                    <a href="signup.php" class="btn btn-primary btn-lg px-5 py-3 pulse"><i class="fas fa-user-plus me-2"></i> Get Started</a>
                    <a href="jobs.php" class="btn btn-outline-light btn-lg px-5 py-3"><i class="fas fa-briefcase me-2"></i> Browse Jobs</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3 fade-in">
                    <div class="counter-box">
                        <div class="counter">1,250+</div>
                        <div class="counter-label">Jobs Available</div>
                    </div>
                </div>
                <div class="col-md-3 fade-in delay-1">
                    <div class="counter-box">
                        <div class="counter">750+</div>
                        <div class="counter-label">Companies Hiring</div>
                    </div>
                </div>
                <div class="col-md-3 fade-in delay-2">
                    <div class="counter-box">
                        <div class="counter">15,000+</div>
                        <div class="counter-label">Active Users</div>
                    </div>
                </div>
                <div class="col-md-3 fade-in delay-3">
                    <div class="counter-box">
                        <div class="counter">95%</div>
                        <div class="counter-label">Success Rate</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title">Why Choose Our Portal</h2>
                <p class="section-subtitle">We provide everything you need for a successful career journey</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-3 fade-in">
                    <div class="feature-card text-center">
                        <div class="feature-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3 class="feature-title">Job Search</h3>
                        <p class="feature-text">Find thousands of job listings from top companies in various industries.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 fade-in delay-1">
                    <div class="feature-card text-center">
                        <div class="feature-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h3 class="feature-title">Resume Builder</h3>
                        <p class="feature-text">Create professional resumes that stand out to employers with our tools.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 fade-in delay-2">
                    <div class="feature-card text-center">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3 class="feature-title">Job Alerts</h3>
                        <p class="feature-text">Get notified when new jobs matching your criteria become available.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 fade-in delay-3">
                    <div class="feature-card text-center">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">Career Insights</h3>
                        <p class="feature-text">Access valuable career resources, salary data, and industry trends.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Job Listings Section -->
    <section class="jobs-section">
        <div class="container">
            <div class="jobs-header fade-in">
                <h2 class="jobs-title">Latest Job Opportunities</h2>
                <a href="jobs.php" class="btn btn-outline-primary btn-lg">View All Jobs <i class="fas fa-arrow-right ms-2"></i></a>
            </div>
            
            <div class="row g-4">
                <?php
                // Fetch latest job openings from database
                $job_query = "SELECT jo.*, c.organization_name 
                            FROM job_openings jo 
                            LEFT JOIN companies c ON jo.company_id = c.id 
                            WHERE jo.vacancies > 0 
                            ORDER BY jo.created_at DESC 
                            LIMIT 3";
                
                $job_result = mysqli_query($conn, $job_query);
                
                if (mysqli_num_rows($job_result) > 0) {
                    while ($job = mysqli_fetch_assoc($job_result)) {
                        // Format salary
                        $salary = "₹" . number_format($job['from_ctc'], 1) . " - ₹" . number_format($job['to_ctc'], 1) . " LPA";
                        
                        // Determine job type badge (you can customize this based on your data)
                        $badge_class = "Full-time";
                        if (strpos(strtolower($job['job_designation']), 'remote') !== false) {
                            $badge_class = "Remote";
                        } elseif (strpos(strtolower($job['job_designation']), 'contract') !== false) {
                            $badge_class = "Contract";
                        }
                        
                        // Truncate description if too long
                        $description = $job['job_description'];
                        if (strlen($description) > 120) {
                            $description = substr($description, 0, 120) . '...';
                        }
                        ?>
                        <div class="col-md-6 col-lg-4 fade-in">
                            <div class="job-card">
                                <span class="job-badge"><?php echo htmlspecialchars($badge_class); ?></span>
                                <h3 class="job-title"><?php echo htmlspecialchars($job['job_designation']); ?></h3>
                                <span class="job-company"><?php echo htmlspecialchars($job['organization_name'] ?: 'Company'); ?></span>
                                <div class="job-location">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['job_location']); ?>
                                </div>
                                <div class="job-salary"><?php echo $salary; ?></div>
                                <p class="job-description"><?php echo htmlspecialchars($description); ?></p>
                                <?php if(isset($_SESSION['user']) && $_SESSION['role'] == 'employee'): ?>
                                    <a href="apply_job.php?job_id=<?php echo $job['id']; ?>" class="btn btn-primary">Apply Now</a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-primary">Login to Apply</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    // Show placeholder jobs if no jobs found
                    ?>
                    <div class="col-md-6 col-lg-4 fade-in">
                        <div class="job-card">
                            <span class="job-badge">Full-time</span>
                            <h3 class="job-title">Senior Frontend Developer</h3>
                            <span class="job-company">Tech Innovations Inc.</span>
                            <div class="job-location">
                                <i class="fas fa-map-marker-alt"></i> San Francisco, CA
                            </div>
                            <div class="job-salary">$120,000 - $150,000</div>
                            <p class="job-description">We're looking for an experienced frontend developer to join our team and help build innovative web applications.</p>
                            <a href="login.php" class="btn btn-primary">Login to Apply</a>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4 fade-in delay-1">
                        <div class="job-card">
                            <span class="job-badge">Remote</span>
                            <h3 class="job-title">UX/UI Designer</h3>
                            <span class="job-company">Creative Solutions</span>
                            <div class="job-location">
                                <i class="fas fa-map-marker-alt"></i> Remote
                            </div>
                            <div class="job-salary">$90,000 - $120,000</div>
                            <p class="job-description">Join our design team to create beautiful and intuitive user experiences for our digital products.</p>
                            <a href="login.php" class="btn btn-primary">Login to Apply</a>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4 fade-in delay-2">
                        <div class="job-card">
                            <span class="job-badge">Contract</span>
                            <h3 class="job-title">Project Manager</h3>
                            <span class="job-company">Global Enterprises</span>
                            <div class="job-location">
                                <i class="fas fa-map-marker-alt"></i> New York, NY
                            </div>
                            <div class="job-salary">$100,000 - $130,000</div>
                            <p class="job-description">Lead exciting projects for Fortune 500 clients and help drive our business forward.</p>
                            <a href="login.php" class="btn btn-primary">Login to Apply</a>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Registration Section -->
    <section class="registration-section">
        <div class="container">
            <div class="registration-container">
                <div class="registration-content fade-in">
                    <h2 class="registration-title">Ready to Advance Your Career?</h2>
                    <p class="registration-text">Join thousands of professionals who have found their dream jobs through our portal.</p>
                    
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Create a professional resume in minutes</li>
                        <li><i class="fas fa-check"></i> Get matched with relevant job opportunities</li>
                        <li><i class="fas fa-check"></i> Track your applications in one place</li>
                        <li><i class="fas fa-check"></i> Receive personalized career advice</li>
                        <li><i class="fas fa-check"></i> Connect with industry professionals</li>
                        <li><i class="fas fa-check"></i> Access exclusive training resources</li>
                    </ul>
                    
                    <a href="signup.php" class="btn btn-primary btn-lg pulse"><i class="fas fa-user-plus me-2"></i> Sign Up Now</a>
                </div>
                
                <div class="login-form-container floating fade-in delay-1">
                    <h3 class="form-title">Login to Your Account</h3>
                    
                    <?php if ($errorMsg): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMsg); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-with-icon">
                                <i class="fas fa-envelope"></i>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                                <button type="button" class="password-toggle" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">CAPTCHA Verification</label>
                            <div class="captcha-container">
                                <div class="captcha-display" id="captcha-container">
                                    <?php echo isset($_SESSION['captcha']) ? $_SESSION['captcha'] : ''; ?>
                                </div>
                                <button type="button" class="btn btn-outline-secondary" onclick="refreshCaptcha()">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                            <input type="text" class="form-control" name="captcha" placeholder="Enter CAPTCHA" required>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        
                        <button type="submit" name="login" class="btn btn-primary">Login</button>
                        <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
                    </form>
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
            </div>
        </div>
    </section>

    <!-- Diaries Section -->
    <section class="diaries-section">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title">Visitor Diaries</h2>
                <p class="section-subtitle">Insights and experiences from our Visitors</p>
            </div>
            
            <div class="row g-4">
                <?php
                // Fetch latest visitor diaries from database
                $diary_query = "SELECT * FROM visitor_diaries 
                            ORDER BY visit_date DESC 
                            LIMIT 3";
                
                $diary_result = mysqli_query($conn, $diary_query);
                
                if (mysqli_num_rows($diary_result) > 0) {
                    while ($diary = mysqli_fetch_assoc($diary_result)) {
                        // Format date
                        $visit_date = date('F j, Y', strtotime($diary['visit_date']));
                        
                        // Truncate feedback if too long
                        $feedback = $diary['feedback'];
                        if (strlen($feedback) > 120) {
                            $feedback = substr($feedback, 0, 120) . '...';
                        }
                        
                        // Generate star rating
                        $rating = $diary['rating'];
                        $stars = '';
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                $stars .= '<i class="fas fa-star text-warning"></i>';
                            } else {
                                $stars .= '<i class="far fa-star text-warning"></i>';
                            }
                        }
                        ?>
                        <div class="col-md-4 fade-in">
                            <div class="diary-card">
                                <h3 class="diary-title"><?php echo htmlspecialchars($diary['visitor_name']); ?>'s Visit</h3>
                                <div class="diary-meta">
                                    <div class="diary-meta-item">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($diary['visitor_name']); ?>
                                    </div>
                                    <div class="diary-meta-item">
                                        <i class="fas fa-calendar"></i> <?php echo $visit_date; ?>
                                    </div>
                                    <div class="diary-meta-item">
                                        <i class="fas fa-star"></i> <?php echo $stars; ?>
                                    </div>
                                </div>
                                <?php if (!empty($diary['company'])): ?>
                                <div class="diary-meta">
                                    <div class="diary-meta-item">
                                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($diary['company']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($diary['purpose'])): ?>
                                <div class="diary-meta">
                                    <div class="diary-meta-item">
                                        <i class="fas fa-bullseye"></i> Purpose: <?php echo htmlspecialchars($diary['purpose']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <p class="diary-content"><?php echo htmlspecialchars($feedback); ?></p>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    // Show placeholder diaries if no diaries found
                    ?>
                    <div class="col-md-4 fade-in">
                        <div class="diary-card">
                            <h3 class="diary-title">My Journey to Becoming a UX Lead</h3>
                            <div class="diary-meta">
                                <div class="diary-meta-item">
                                    <i class="fas fa-user"></i> Sarah Johnson
                                </div>
                                <div class="diary-meta-item">
                                    <i class="fas fa-calendar"></i> June 15, 2023
                                </div>
                                <div class="diary-meta-item">
                                    <i class="fas fa-star"></i> 
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                </div>
                            </div>
                            <p class="diary-content">Discover how I transitioned from graphic design to UX leadership at a major tech company and the lessons I learned along the way.</p>
                            <a href="visitor_diaries.php" class="btn btn-outline-primary">Read More</a>
                        </div>
                    </div>
                    <div class="col-md-4 fade-in delay-1">
                        <div class="diary-card">
                            <h3 class="diary-title">Remote Work: Challenges & Solutions</h3>
                            <div class="diary-meta">
                                <div class="diary-meta-item">
                                    <i class="fas fa-user"></i> Michael Chen
                                </div>
                                <div class="diary-meta-item">
                                    <i class="fas fa-calendar"></i> May 28, 2023
                                </div>
                                <div class="diary-meta-item">
                                    <i class="fas fa-star"></i> 
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="far fa-star text-warning"></i>
                                </div>
                            </div>
                            <p class="diary-content">After 3 years of leading remote teams, I've compiled the most effective strategies for productivity and team cohesion.</p>
                            <a href="visitor_diaries.php" class="btn btn-outline-primary">Read More</a>
                        </div>
                    </div>
                    <div class="col-md-4 fade-in delay-2">
                        <div class="diary-card">
                            <h3 class="diary-title">Negotiating Your First Tech Salary</h3>
                            <div class="diary-meta">
                                <div class="diary-meta-item">
                                    <i class="fas fa-user"></i> Emma Rodriguez
                                </div>
                                <div class="diary-meta-item">
                                    <i class="fas fa-calendar"></i> May 10, 2023
                                </div>
                                <div class="diary-meta-item">
                                    <i class="fas fa-star"></i> 
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star-half-alt text-warning"></i>
                                </div>
                            </div>
                            <p class="diary-content">Learn the strategies I used to negotiate a 30% higher starting salary for my first developer position right out of college.</p>
                            
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            
            <!-- View All Diaries Button -->
            <div class="text-center mt-5 fade-in">
                <a href="visitor_diaries.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-book me-2"></i> Write Your Feedback
                </a>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title">Success Stories</h2>
                <p class="section-subtitle">What our users say about us</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4 fade-in">
                    <div class="testimonial-card">
                        <p class="testimonial-content">"The Employee Portal helped me land my dream job at Google. The resume builder and interview prep resources were invaluable!"</p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar">
                                <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80" alt="Sarah Johnson">
                            </div>
                            <div class="testimonial-info">
                                <h5>Sarah Johnson</h5>
                                <p>Software Engineer at Google</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 fade-in delay-1">
                    <div class="testimonial-card">
                        <p class="testimonial-content">"I received 3 job offers within a month of using this portal. The job matching algorithm is incredibly accurate."</p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar">
                                <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80" alt="Michael Chen">
                            </div>
                            <div class="testimonial-info">
                                <h5>Michael Chen</h5>
                                <p>Product Manager at Microsoft</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 fade-in delay-2">
                    <div class="testimonial-card">
                        <p class="testimonial-content">"The career resources helped me negotiate a 30% higher salary than my initial offer. This portal is a game-changer!"</p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar">
                                <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80" alt="Emma Rodriguez">
                            </div>
                            <div class="testimonial-info">
                                <h5>Emma Rodriguez</h5>
                                <p>Data Scientist at Amazon</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <div class="footer-logo">
                        <i class="fas fa-briefcase"></i> EmployeePortal
                    </div>
                    <p class="footer-text">Connecting talented professionals with top employers to build successful careers and thriving organizations.</p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/share/1MiQLLNC4z/" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.linkedin.com/company/abhimo-technologies-private-limited/" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                        <a href="https://www.instagram.com/abhimo_technologies?igsh=Mm90N2N1bWY4OGcy" target="_blank"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5 class="footer-title">Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="about.php"><i class="fas fa-chevron-right"></i> About Us</a></li>
                        <li><a href="jobs.php"><i class="fas fa-chevron-right"></i> Job Listings</a></li>
                        <li><a href="resources.php"><i class="fas fa-chevron-right"></i> Resources</a></li>
                        <li><a href="contact.php"><i class="fas fa-chevron-right"></i> Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="footer-title">Resources</h5>
                    <ul class="footer-links">
                        <li><a href="resume_tips.php"><i class="fas fa-chevron-right"></i> Resume Tips</a></li>
                        <li><a href="interview_prep.php"><i class="fas fa-chevron-right"></i> Interview Preparation</a></li>
                        <li><a href="career_advice.php"><i class="fas fa-chevron-right"></i> Career Advice</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h5 class="footer-title">Contact Us</h5>
                    <ul class="contact-info">
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span>F07, D.No 2-11/26(27), "Green City", Behind Naganakatte, N.H.66, Thokottu, Mangaluru, Karnataka-575017.</span>
                        </li>
                        <li>
                            <i class="fas fa-phone-alt"></i>
                            <span>078297 38999</span>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span>naveennayak.i@abhimo.com</span>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <span>Mon-Fri: 9AM - 6PM</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; 2023 Employee Portal. All rights reserved. Designed with <i class="fas fa-heart text-danger"></i> for job seekers</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple fade-in animation on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const fadeElements = document.querySelectorAll('.fade-in');
            
            const fadeInOnScroll = function() {
                fadeElements.forEach(element => {
                    const elementTop = element.getBoundingClientRect().top;
                    const elementVisible = 150;
                    
                    if (elementTop < window.innerHeight - elementVisible) {
                        element.style.opacity = "1";
                        element.style.transform = "translateY(0)";
                    }
                });
            };
            
            // Set initial state
            fadeElements.forEach(element => {
                element.style.opacity = "0";
                element.style.transform = "translateY(20px)";
                element.style.transition = "opacity 0.6s ease, transform 0.6s ease";
            });
            
            // Check on scroll
            window.addEventListener('scroll', fadeInOnScroll);
            fadeInOnScroll(); // Initialize
            
            // Create background particles
            createParticles();
            
            // Password toggle functionality
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
            
            function createParticles() {
                const particlesContainer = document.getElementById('particles');
                const particlesCount = 30;
                
                for (let i = 0; i < particlesCount; i++) {
                    const particle = document.createElement('div');
                    particle.style.position = 'absolute';
                    particle.style.width = `${Math.random() * 5 + 2}px`;
                    particle.style.height = particle.style.width;
                    particle.style.backgroundColor = '#4361ee';
                    particle.style.borderRadius = '50%';
                    particle.style.opacity = Math.random() * 0.5 + 0.2;
                    particle.style.left = `${Math.random() * 100}%`;
                    particle.style.top = `${Math.random() * 100}%`;
                    
                    // Random animation properties
                    const tx = (Math.random() - 0.5) * 200;
                    const ty = (Math.random() - 0.5) * 200;
                    const duration = Math.random() * 20 + 10;
                    
                    particle.style.setProperty('--tx', `${tx}px`);
                    particle.style.setProperty('--ty', `${ty}px`);
                    particle.style.animation = `particle ${duration}s linear infinite`;
                    
                    particlesContainer.appendChild(particle);
                }
            }
        });

        // CAPTCHA refresh function
        function refreshCaptcha() {
            fetch('index.php?refresh_captcha=1')
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
    </script>
</body>
</html>