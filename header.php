<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Employee Portal</title>
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
            background: rgba(255,255,255,0.2);
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
            background-color: rgba(255,255,255,0.15);
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
    </style>
</head>
<body>
    <!-- Background Particles -->
    <div class="particles-bg" id="particles"></div>
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-briefcase"></i> EmployeePortal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : '' ?>" href="about.php">About</a>
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
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'visitor_diaries.php' ? 'active' : '' ?>" href="visitor_diaries.php">Diaries</a>
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
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : '' ?>" href="contact.php">Contact</a>
                    </li>
                </ul>
                <div class="d-flex ms-lg-3 mt-3 mt-lg-0">
                    <?php if(isset($_SESSION['user'])): ?>
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>