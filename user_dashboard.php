<?php
require_once 'config.php';

// Redirect if not logged in
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user'];
$page_title = "User Dashboard";
include 'header.php';

// Fetch user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Employment Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4e73df;
            --primary-light: #6f8de8;
            --secondary: #1cc88a;
            --info: #36b9cc;
            --warning: #f6c23e;
            --dark: #2e3a59;
            --light: #f8f9fc;
            --gray: #858796;
            --border: #e3e6f0;
        }
        
        body {
            background-color: #f5f7fb;
            color: #4a4a4a;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary) 10%, #224abe 100%);
            min-height: 100vh;
            position: fixed;
            width: 250px;
            height: 400px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.2);
            z-index: 1000;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 3px solid #fff;
        }
        
        .sidebar .nav-link i {
            margin-right: 0.8rem;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .sidebar-logo {
            padding: 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }

        /* Added for section headers */
        .sidebar-section {
            padding: 1rem 1.5rem 0.5rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            margin-top: 1rem;
        }
        
        .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 20px;
        }
        
        .top-bar {
            background: white;
            padding: 1rem 1.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            border: 2px solid white;
            box-shadow: 0 4px 10px rgba(78, 115, 223, 0.25);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #e74a3b;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .notification-icon {
            position: relative;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 0.5rem 2rem rgba(58, 59, 69, 0.2);
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: var(--dark);
            font-size: 1.1rem;
        }
        
        .stat-card {
            border-left: 0.35rem solid;
            padding: 1.5rem;
        }
        
        .stat-card.primary {
            border-left-color: var(--primary);
        }
        
        .stat-card.success {
            border-left-color: var(--secondary);
        }
        
        .stat-card.info {
            border-left-color: #36b9cc;
        }
        
        .stat-card .stat-icon {
            position: absolute;
            right: 1.25rem;
            top: 1.25rem;
            opacity: 0.2;
            font-size: 2.8rem;
        }
        
        .stat-card .stat-title {
            font-size: 0.95rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }
        
        .stat-card .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .quick-action-btn {
            display: flex;
            align-items: center;
            padding: 1.2rem 1.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            color: white;
            border: none;
            border-radius: 12px;
            text-align: left;
            transition: all 0.3s;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.25);
        }
        
        .quick-action-btn:hover {
            background: linear-gradient(to right, #3a55c8, #5a75e0);
            color: white;
            text-decoration: none;
        }
        
        .quick-action-btn i {
            font-size: 1.8rem;
            margin-right: 1rem;
            background: rgba(255,255,255,0.2);
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quick-action-btn .btn-content {
            flex: 1;
        }
        
        .quick-action-btn .btn-content h5 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .welcome-card {
            background: linear-gradient(120deg, var(--primary), var(--primary-light));
            color: white;
            border-radius: 12px;
            padding: 2.5rem;
        }
        
        .welcome-card h1 {
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }
        
        .welcome-card p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 600px;
        }
        
        .recent-activities {
            list-style: none;
            padding-left: 0;
        }
        
        .recent-activities li {
            position: relative;
            padding-left: 30px;
            padding-bottom: 1.5rem;
            border-left: 2px solid var(--border);
            margin-left: 10px;
        }
        
        .recent-activities li:last-child {
            padding-bottom: 0;
            border-left: 2px solid transparent;
        }
        
        .recent-activities li::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--primary);
            border: 3px solid white;
            box-shadow: 0 0 0 2px var(--primary);
        }
        
        .recent-activities .activity-time {
            font-size: 0.85rem;
            color: var(--gray);
            margin-bottom: 0.25rem;
        }
        
        .recent-activities .activity-content {
            background: #f8f9ff;
            border-radius: 8px;
            padding: 1rem;
        }
        
        .recent-activities .activity-content h6 {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--dark);
        }
        
        .profile-preview {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            object-fit: cover;
            margin-bottom: 1.5rem;
            background: #eef2f7;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 3rem;
            color: var(--primary);
        }
        
        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .profile-stat {
            text-align: center;
        }
        
        .profile-stat .stat-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .profile-stat .stat-label {
            font-size: 0.85rem;
            color: var(--gray);
        }
        
        .btn-view-profile {
            background: linear-gradient(135deg, var(--primary), #3a56c9);
            border: none;
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
            color: white;
            text-decoration: none;
            display: inline-block;
            margin-top: 1rem;
            box-shadow: 0 4px 12px rgba(78, 115, 223, 0.3);
        }
        
        .btn-view-profile:hover {
            box-shadow: 0 8px 20px rgba(78, 115, 223, 0.4);
            color: white;
        }
        
        .progress-bar {
            transition: width 1s ease-in-out;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar .nav-link span {
                display: none;
            }
            
            .sidebar .nav-link i {
                margin-right: 0;
                font-size: 1.4rem;
            }
            
            .sidebar-logo h4 {
                display: none;
            }
            
            .sidebar-logo {
                text-align: center;
                padding: 1rem 0;
            }
            
            .main-content {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                min-height: auto;
                position: relative;
            }
            
            .sidebar .nav {
                flex-direction: row;
                overflow-x: auto;
            }
            
            .sidebar .nav-link {
                padding: 1rem;
                border-left: none;
                border-bottom: 3px solid transparent;
            }
            
            .sidebar .nav-link:hover, .sidebar .nav-link.active {
                border-left: none;
                border-bottom: 3px solid #fff;
            }
            
            .sidebar-logo {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="sidebar-logo">
                    <h4 class="text-white"><i class="fas fa-briefcase me-2"></i>EmployeePortal</h4>
                </div>
                <ul class="nav flex-column">
                    <!-- Employee Portal Section -->
                    <li class="sidebar-section">Employee Portal</li>
                    <li class="nav-item">
                        <a class="nav-link" href="user_dashboard.php">
                            <i class="fas fa-fw fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="company_registration.php">
                            <i class="fas fa-fw fa-building"></i>
                            <span>Company Registration</span>
                        </a>
                    </li>

                    <!-- Employee Registration Section --> <li class="nav-item">
                        <a class="nav-link active" href="employee_registration.php">
                            <i class="fas fa-fw fa-building"></i>
                            <span>Employee Registration</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="resume_builder.php">
                            <i class="fas fa-fw fa-file-alt"></i>
                            <span>Resume Builder</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-fw fa-user-circle"></i>
                            <span>Profile</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="contact.php">
                            <i class="fas fa-fw fa-envelope"></i>
                            <span>Contact</span>
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-fw fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <!-- Top Bar -->
                <div class="top-bar d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-0">User Dashboard</h1>
                    <div class="d-flex align-items-center">
                        <div class="me-4 notification-icon">
                            <i class="fas fa-bell text-muted fs-5"></i>
                            <span class="notification-badge">3</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-2">
                                <?= substr($user['name'], 0, 1) ?>
                            </div>
                            <div>
                                <div class="fw-bold"><?= $user['name'] ?></div>
                                <small class="text-muted">Member</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Welcome Card -->
                <div class="card welcome-card mb-4">
                    <div class="card-body">
                        <h1>Welcome back, <?= $user['name'] ?>!</h1>
                        <p>You're doing great! Complete your profile to increase your visibility to employers and explore new opportunities.</p>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card primary">
                            <div class="position-relative">
                                <div class="stat-title">Profile Completeness</div>
                                <div class="stat-value">85%</div>
                                <div class="stat-icon">
                                    <i class="fas fa-user-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card success">
                            <div class="position-relative">
                                <div class="stat-title">Job Applications</div>
                                <div class="stat-value">8</div>
                                <div class="stat-icon">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card info">
                            <div class="position-relative">
                                <div class="stat-title">Companies Registered</div>
                                <div class="stat-value">2</div>
                                <div class="stat-icon">
                                    <i class="fas fa-building"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card primary">
                            <div class="position-relative">
                                <div class="stat-title">Resumes Created</div>
                                <div class="stat-value">3</div>
                                <div class="stat-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Left Column -->
                    <div class="col-lg-8">
                        <!-- Recent Activities -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Activities</h5>
                                <a href="#" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <ul class="recent-activities">
                                    <li>
                                        <div class="activity-time">Today, 10:30 AM</div>
                                        <div class="activity-content">
                                            <h6>Updated Profile</h6>
                                            <p class="mb-0">Added new work experience to your profile</p>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="activity-time">Yesterday, 2:15 PM</div>
                                        <div class="activity-content">
                                            <h6>Company Registration</h6>
                                            <p class="mb-0">Registered "Tech Solutions Inc." as a company</p>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="activity-time">July 15, 2025</div>
                                        <div class="activity-content">
                                            <h6>Resume Created</h6>
                                            <p class="mb-0">Created a new resume for web developer positions</p>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="activity-time">July 12, 2025</div>
                                        <div class="activity-content">
                                            <h6>Job Application</h6>
                                            <p class="mb-0">Applied for Senior Developer position at Google</p>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profile Preview -->
                    <div class="col-lg-4">
                        <div class="profile-preview">
                            <div class="profile-img">
                                <?= substr($user['name'], 0, 1) ?>
                            </div>
                            <h4><?= $user['name'] ?></h4>
                            <p class="text-muted">Web Developer</p>
                            
                            <div class="profile-stats">
                                <div class="profile-stat">
                                    <div class="stat-value">85%</div>
                                    <div class="stat-label">Profile</div>
                                </div>
                                <div class="profile-stat">
                                    <div class="stat-value">4.8</div>
                                    <div class="stat-label">Rating</div>
                                </div>
                                <div class="profile-stat">
                                    <div class="stat-value">12</div>
                                    <div class="stat-label">Skills</div>
                                </div>
                            </div>
                            
                            <a href="profile.php" class="btn-view-profile">
                                <i class="fas fa-edit me-2"></i> Edit Profile
                            </a>
                        </div>
                        
                        <!-- Contact Information -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">Contact Information</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-envelope text-primary me-2"></i>
                                        <?= $user['email'] ?>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-phone text-primary me-2"></i>
                                        (123) 456-7890
                                    </li>
                                    <li class="mb-0">
                                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                        New York, NY
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- System Status -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">Your Progress</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <div>Profile Completeness</div>
                                    <div class="fw-bold">85%</div>
                                </div>
                                <div class="progress mb-4" style="height: 10px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 85%"></div>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <div>Resume Strength</div>
                                    <div class="fw-bold">70%</div>
                                </div>
                                <div class="progress mb-4" style="height: 10px;">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: 70%"></div>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <div>Job Match</div>
                                    <div class="fw-bold">92%</div>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: 92%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>