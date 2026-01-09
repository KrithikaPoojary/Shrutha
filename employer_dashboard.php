<?php
// employer_dashboard.php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'employer'){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user'];
$page_title = "Employer Dashboard";

// Create necessary tables if they don't exist
$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('employee', 'employer') NOT NULL
    )",
    
    "CREATE TABLE IF NOT EXISTS jobs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employer_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employer_id) REFERENCES users(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS application1 (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        candidate_id INT NOT NULL,
        status ENUM('pending', 'review', 'interview', 'hired', 'rejected') DEFAULT 'pending',
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (job_id) REFERENCES jobs(id),
        FOREIGN KEY (candidate_id) REFERENCES users(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS interviews1 (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application1_id INT NOT NULL,
        interview_time DATETIME NOT NULL,
        FOREIGN KEY (application1_id) REFERENCES application1(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS candidates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        title VARCHAR(255) NOT NULL,
        experience VARCHAR(50),
        skills VARCHAR(255)
    )"
];

foreach ($tables as $query) {
    if (!mysqli_query($conn, $query)) {
        die("Error creating table: " . mysqli_error($conn));
    }
}

// Insert sample data if tables are empty
$check_data = "SELECT COUNT(*) AS count FROM jobs";
$result = mysqli_query($conn, $check_data);
$row = mysqli_fetch_assoc($result);
if ($row['count'] == 0) {
    $sample_data = [
        "INSERT INTO jobs (employer_id, title, description) VALUES
            ($user_id, 'Senior Developer', 'Looking for experienced developers'),
            ($user_id, 'UX Designer', 'Creative designer needed'),
            ($user_id, 'DevOps Engineer', 'Infrastructure specialist')",
        
        "INSERT INTO candidates (name, title, experience, skills) VALUES
            ('John Doe', 'Senior Developer', '8 years', 'JavaScript,React,Node.js'),
            ('Sarah Adams', 'UX Designer', '6 years', 'Figma,UI/UX,Prototyping'),
            ('Michael Johnson', 'DevOps Engineer', '7 years', 'AWS,Docker,Kubernetes')",
        
        "INSERT INTO application1 (job_id, candidate_id, status) VALUES
            (1, 1, 'pending'),
            (2, 2, 'interview'),
            (3, 3, 'hired')",
        
        "INSERT INTO interviews1 (application1_id, interview_time) VALUES
            (1, NOW() + INTERVAL 1 DAY),
            (2, NOW() + INTERVAL 2 DAY),
            (3, NOW() - INTERVAL 1 DAY)"
    ];
    
    foreach ($sample_data as $query) {
        mysqli_query($conn, $query);
    }
}
// Fetch user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Generate initials from name
$initials = '';
$name_parts = explode(' ', $user['name']);
if(count($name_parts) >= 2) {
    $initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[count($name_parts)-1], 0, 1));
} else {
    $initials = strtoupper(substr($user['name'], 0, 2));
}

// Fetch stats from database
$stats = [
    'active_jobs' => 0,
    'new_applications' => 0,
    'interviews' => 0,
    'hired' => 0
];

// Get employer's company ID first
$company_id = 0;
$company_registered = false;
$sql_company = "SELECT id FROM companies WHERE email = ?";
$stmt_company = mysqli_prepare($conn, $sql_company);
mysqli_stmt_bind_param($stmt_company, "s", $user['email']);
mysqli_stmt_execute($stmt_company);
$result_company = mysqli_stmt_get_result($stmt_company);
if ($row_company = mysqli_fetch_assoc($result_company)) {
    $company_id = $row_company['id'];
    $company_registered = true;
}

// Get active jobs count
$sql = "SELECT COUNT(*) AS count FROM job_openings WHERE company_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $company_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$stats['active_jobs'] = $row['count'];

// Get new applications count
$sql = "SELECT COUNT(*) AS count FROM applications 
        WHERE status = 'Submitted' 
        AND job_id IN (SELECT id FROM job_openings WHERE company_id = ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $company_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$stats['new_applications'] = $row['count'];

// Get interviews count (using interviews table)
$sql = "SELECT COUNT(*) AS count FROM interviews 
        WHERE user_id IN (
            SELECT id FROM users 
            WHERE email IN (
                SELECT DISTINCT email FROM applications 
                WHERE job_id IN (SELECT id FROM job_openings WHERE company_id = ?)
            ) AND status = 'scheduled'
        )";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $company_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$stats['interviews'] = $row['count'];


// Get hired this month count
$sql = "SELECT COUNT(*) AS count FROM applications 
        WHERE status = 'Hired' 
        AND MONTH(applied_at) = MONTH(CURRENT_DATE())
        AND YEAR(applied_at) = YEAR(CURRENT_DATE())
        AND job_id IN (SELECT id FROM job_openings WHERE company_id = ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $company_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$stats['hired'] = $row['count'];

// ===================================================
// == START: RECENT APPLICATIONS LOGIC (MODIFIED)
// ===================================================
$recent_applications = [];

// Get sort status from URL
$sort_status = '';
$valid_statuses = ['Submitted', 'Under Review', 'Shortlisted', 'Hired'];
if (isset($_GET['sort_status']) && in_array($_GET['sort_status'], $valid_statuses)) {
    $sort_status = $_GET['sort_status'];
}

$sql = "SELECT a.*, j.job_designation, c.organization_name, a.resume_path 
        FROM applications a
        JOIN job_openings j ON a.job_id = j.id
        JOIN companies c ON j.company_id = c.id
        WHERE j.company_id = ?";

$params = [$company_id];
$types = "i";

if ($sort_status) {
    // If sorting by a specific status, use that status
    $sql .= " AND a.status = ?";
    $params[] = $sort_status;
    $types .= "s";
} else {
    // Default behavior: show all *except* Hired
    $sql .= " AND a.status != 'Hired'";
}

$sql .= " ORDER BY a.applied_at DESC LIMIT 4";

$stmt = mysqli_prepare($conn, $sql);

// Dynamically bind parameters
mysqli_stmt_bind_param($stmt, $types, ...$params);

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $recent_applications[] = $row;
}
// ===================================================
// == END: RECENT APPLICATIONS LOGIC
// ===================================================


$company_name = '';
$sql_company_name = "SELECT organization_name FROM companies WHERE id = ?";
$stmt_company = mysqli_prepare($conn, $sql_company_name);
mysqli_stmt_bind_param($stmt_company, "i", $company_id);
mysqli_stmt_execute($stmt_company);
$result_company = mysqli_stmt_get_result($stmt_company);
if ($row_company = mysqli_fetch_assoc($result_company)) {
    $company_name = $row_company['organization_name'];
}

// Get upcoming interviews for this company
$upcoming_interviews = [];
if ($company_id > 0) {
    $sql = "SELECT a.name AS candidate_name, a.email, j.job_designation, 
                   i.interview_time, i.position, i.status, u.name AS user_name
            FROM interviews i
            JOIN users u ON i.user_id = u.id
            JOIN applications a ON u.email = a.email
            JOIN job_openings j ON a.job_id = j.id
            WHERE j.company_id = ?
            AND i.interview_time > NOW()
            AND i.status = 'scheduled'
            ORDER BY i.interview_time ASC
            LIMIT 3";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $company_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $upcoming_interviews[] = $row;
    }
}

// Get interviews count for current employer
if (!empty($company_name)) {
    $sql = "SELECT COUNT(*) AS count FROM interviews 
            WHERE company = ? 
            AND interview_time > NOW()
            AND status = 'scheduled'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $company_name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $stats['interviews'] = $row['count'];
}

// ===================================================
// == START: APPLICANT NOTIFICATION SYSTEM LOGIC
// ===================================================
$applicant_notifications = [];
$applicant_notification_count = 0;

if ($company_id > 0) {
    $sql_notify = "SELECT a.id, a.name, a.email, a.applied_at, j.job_designation
                   FROM applications a
                   JOIN job_openings j ON a.job_id = j.id
                   WHERE j.company_id = ?
                   ORDER BY a.id DESC 
                   LIMIT 10";
    
    $stmt_notify = mysqli_prepare($conn, $sql_notify);
    
    if ($stmt_notify) {
        mysqli_stmt_bind_param($stmt_notify, "i", $company_id);
        mysqli_stmt_execute($stmt_notify);
        $result_notify = mysqli_stmt_get_result($stmt_notify);
        while ($row = mysqli_fetch_assoc($result_notify)) {
            $applicant_notifications[] = $row;
        }
        $applicant_notification_count = count($applicant_notifications);
    }
}

$top_candidates = [];
$sql = "SELECT a.name, a.email, a.experience, a.skills, a.phone, a.current_company, a.current_position, a.resume_path, 
               GROUP_CONCAT(DISTINCT j.job_designation SEPARATOR ', ') as applied_positions,
               MAX(a.applied_at) as latest_application,
               MAX(
                   CASE
                       WHEN a.status = 'Hired' THEN 3
                       WHEN a.status = 'Shortlisted' THEN 2
                       WHEN a.status = 'Under Review' THEN 1
                       ELSE 0
                   END
               ) as best_status_score
        FROM applications a
        JOIN job_openings j ON a.job_id = j.id
        LEFT JOIN users u ON a.email = u.email
        LEFT JOIN interviews i ON u.id = i.user_id AND i.company = ?
        WHERE j.company_id = ?
        GROUP BY a.email, a.name, a.experience, a.skills, a.phone, a.current_company, a.current_position, a.resume_path
        ORDER BY best_status_score DESC, latest_application DESC
        LIMIT 2";

$stmt = mysqli_prepare($conn, $sql);
// We need to bind $company_name first (for interview check), then $company_id
mysqli_stmt_bind_param($stmt, "si", $company_name, $company_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $top_candidates[] = $row;
}
// ===================================================
// == END: TOP CANDIDATES QUERY
// ===================================================
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
            --primary: #3a0ca3;
            --primary-light: #4361ee;
            --secondary: #7209b7;
            --success: #4cc9f0;
            --info: #f72585;
            --warning: #f8961e;
            --dark: #2b2d42;
            --light: #f8f9fa;
            --gray: #6c757d;
            --border: #dee2e6;
        }
        
        body {
            background-color: #f5f7fb;
            color: #343a40;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary) 10%, var(--secondary) 100%);
            min-height: 100vh;
            position: fixed;
            width: 250px;
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
            border: 2px solid white;
        }
        
        .notification-icon {
            position: relative;
            color: var(--gray);
            cursor: pointer;
        }
        
        .notification-icon:hover {
            color: var(--dark);
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
            border-left-color: var(--success);
        }
        
        .stat-card.info {
            border-left-color: var(--info);
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
        
        .welcome-card {
            background: linear-gradient(120deg, var(--primary), var(--primary-light));
            color: white;
            border-radius: 12px;
            padding: 2.5rem;
        }
        
        .candidate-card {
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.3s;
        }
        
        .candidate-card:hover {
            transform: translateY(-5px);
        }
        
        .candidate-card .card-body {
            padding: 1.5rem;
        }
        
        .candidate-card .candidate-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #f0f5ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary);
            margin: 0 auto 1rem;
        }
        
        .candidate-card .candidate-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        
        .candidate-card .candidate-title {
            color: var(--gray);
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .candidate-card .candidate-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 1.5rem;
            justify-content: center;
        }
        
        .candidate-card .meta-item {
            background: #f0f5ff;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .progress-bar {
            transition: width 1s ease-in-out;
        }
        
        /* === NEW/UPDATED NOTIFICATION STYLES === */
        .offcanvas-body .list-group-item {
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 1.5rem;
        }
        .offcanvas-body .list-group-item:last-child {
            border-bottom: none;
        }
        
        .notification-icon-wrapper {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-light);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        
        .notification-content {
            flex-grow: 1;
        }
        
        .notification-content .notification-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }
        
        .notification-content .notification-meta {
            font-size: 0.85rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
            display: block;
        }
        
        /* NEW STYLE FOR TIME */
        .notification-content .notification-time {
            font-size: 0.75rem;
            padding-left: 1rem;
        }

        #no-notifications-msg {
            display: none; /* Hide by default, JS will show it */
        }
        /* === END NOTIFICATION STYLES === */
        
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
        
        /* Modal Styles */
        .candidate-modal .candidate-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #f0f5ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--primary);
            margin: 0 auto 1rem;
        }
        
        .candidate-modal .skill-badge {
            background: var(--primary-light);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            margin: 2px;
            display: inline-block;
        }
        
        .candidate-modal .contact-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="sidebar-logo">
                    <h4 class="text-white"><i class="fas fa-building me-2"></i>EmployerPortal</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="sidebar-section">Employer Portal</li>
                    <li class="nav-item">
                        <a class="nav-link active" href="employer_dashboard.php">
                            <i class="fas fa-fw fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <?php if ($company_registered): ?>
                            <a class="nav-link" href="employer_profile.php" title="You are already registered. Go to profile to edit details." 
                            data-bs-toggle="tooltip" data-bs-placement="right">
                                <i class="fas fa-fw fa-building"></i>
                                <span>Company Registration</span>
                            </a>
                        <?php else: ?>
                            <a class="nav-link" href="company_registration.php">
                                <i class="fas fa-fw fa-building"></i>
                                <span>Company Registration</span>
                            </a>
                        <?php endif; ?>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="job_postings.php">
                            <i class="fas fa-fw fa-briefcase"></i>
                            <span>Job Postings</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="candidate_search.php">
                            <i class="fas fa-fw fa-search"></i>
                            <span>Candidate Search</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="applications.php">
                            <i class="fas fa-fw fa-tasks"></i>
                            <span>Applications</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <?php if (!$company_registered): ?>
                            <a class="nav-link" href="company_registration.php" title="You are Not registered. Go to Registration." 
                            data-bs-toggle="tooltip" data-bs-placement="right">
                                <i class="fas fa-fw fa-user-circle"></i>
                                <span>Profile</span>
                            </a>
                        <?php else: ?>
                            <a class="nav-link" href="employer_profile.php">
                                <i class="fas fa-fw fa-user-circle"></i>
                                <span>Profile</span>
                            </a>
                        <?php endif; ?>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">
                            <i class="fas fa-fw fa-envelope"></i>
                            <span>Contact</span>
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link" href="index.php">
                            <i class="fa fa-home"></i>
                            <span>Home</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?logout=1">
                            <i class="fas fa-fw fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="main-content">
                <div class="top-bar d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-0">Employer Dashboard</h1>
                    <div class="d-flex align-items-center">
                        
                        <a class="nav-link notification-icon me-3" id="notificationBell" role="button" data-bs-toggle="offcanvas" data-bs-target="#notificationPanel" aria-controls="notificationPanel">
                            <i class="fas fa-bell fs-5"></i>
                            <span class="notification-badge notification-badge-count"><?= $applicant_notification_count ?></span>
                        </a>
                        
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-2">
                                <?= $initials ?>
                            </div>
                            <div>
                                <div class="fw-bold"><?= $user['name'] ?></div>
                                <small class="text-muted">Recruitment Team</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card welcome-card mb-4">
                    <div class="card-body">
                        <?php
                        // Check if this is user's first login by comparing created_at with current time
                        $user_created = strtotime($user['created_at']);
                        $current_time = time();
                        $time_difference = $current_time - $user_created;
                        
                        // If account was created within the last 24 hours, consider it first login
                        $is_first_login = ($time_difference <= 86400); // 86400 seconds = 24 hours
                        
                        if ($is_first_login): ?>
                            <h1>Welcome, <?= htmlspecialchars($user['name']) ?>!</h1>
                            <?php if ($company_registered): ?>
                                <p>Congratulations! Your company is fully registered. You can now post jobs and start attracting top talent.</p>
                            <?php else: ?>
                                <p>We're excited to have you on board! Complete your company registration to get started and post your first job opening.</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <h1>Welcome back, <?= htmlspecialchars($user['name']) ?>!</h1>
                            <?php if ($company_registered): ?>
                                <p>You have <?= $stats['new_applications'] ?> new applications to review. Post new jobs to attract top talent in the industry.</p>
                            <?php else: ?>
                                <p>Complete your company registration to start posting jobs and attracting qualified candidates.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="d-flex gap-3 mt-3">
                            <?php if ($company_registered): ?>
                                <a href="job_postings.php" class="btn btn-light">
                                    <i class="fas fa-plus me-2"></i> Post New Job
                                </a>
                                <a href="candidate_search.php" class="btn btn-light">
                                    <i class="fas fa-search me-2"></i> Search Candidates
                                </a>
                            <?php else: ?>
                                <a href="company_registration.php" class="btn btn-light">
                                    <i class="fas fa-building me-2"></i> Complete Company Registration
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card primary">
                            <div class="position-relative">
                                <div class="stat-title">Active Job Postings</div>
                                <div class="stat-value"><?= $stats['active_jobs'] ?></div>
                                <div class="stat-icon">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card success">
                            <div class="position-relative">
                                <div class="stat-title">New Applications</div>
                                <div class="stat-value"><?= $stats['new_applications'] ?></div>
                                <div class="stat-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card info">
                            <div class="stat-title">Interviews Scheduled</div>
                            <div class="stat-value"><?= $stats['interviews'] ?></div>
                            <div class="stat-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card primary">
                            <div class="position-relative">
                                <div class="stat-title">Hired This Month</div>
                                <div class="stat-value"><?= $stats['hired'] ?></div>
                                <div class="stat-icon">
                                    <i class="fas fa-user-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Applications</h5>
                        <div class="d-flex align-items-center">
                            <div class="dropdown me-2">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="sort-status-button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Sort by: <?= htmlspecialchars($sort_status ? $sort_status : 'Recent') ?>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="sort-status-button" id="sort-menu">
                                    <li><a class="dropdown-item sort-link" href="#" data-status="">All (Recent)</a></li>
                                    <li><a class="dropdown-item sort-link" href="#" data-status="Submitted">Submitted</a></li>
                                    <li><a class="dropdown-item sort-link" href="#" data-status="Under Review">Under Review</a></li>
                                    <li><a class="dropdown-item sort-link" href="#" data-status="Shortlisted">Shortlisted</a></li>
                                    <li><a class="dropdown-item sort-link" href="#" data-status="Hired">Hired</a></li>
                                </ul>
                            </div>
                            <a href="applications.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Candidate</th>
                                        <th>Position</th>
                                        <th>Status</th>
                                        <th>Applied</th>
                                        <th>Resume</th>
                                        <th>Profile</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-applications-tbody">
                                    <?php if (!empty($recent_applications)): ?>
                                        <?php foreach ($recent_applications as $app): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm me-3">
                                                            <div class="avatar-title rounded-circle bg-light text-primary">
                                                                <?php 
                                                                $name_parts = explode(' ', $app['name']);
                                                                if (count($name_parts) >= 2) {
                                                                    echo strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[1], 0, 1));
                                                                } else {
                                                                    echo strtoupper(substr($app['name'], 0, 2));
                                                                }
                                                                ?>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0"><?= $app['name'] ?></h6>
                                                            <small class="text-muted"><?= $app['email'] ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= $app['job_designation'] ?></td>
                                                <td>
                                                    <?php 
                                                    $status_badge = [
                                                        'Submitted' => 'bg-warning',
                                                        'Under Review' => 'bg-info',
                                                        'Shortlisted' => 'bg-primary',
                                                        'Hired' => 'bg-success',
                                                        'Rejected' => 'bg-danger'
                                                    ];
                                                    ?>
                                                    <span class="badge <?= $status_badge[$app['status']] ?? 'bg-secondary' ?>">
                                                        <?= $app['status'] ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($app['applied_at'])) ?></td>
                                                <td>
                                                    <?php if (!empty($app['resume_path'])): ?>
                                                        <a href="track_resume_view.php?application_id=<?= $app['id'] ?>&resume_path=<?= urlencode($app['resume_path']) ?>" 
                                                        target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye me-1"></i>View Resume
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-outline-secondary" disabled>
                                                            <i class="fas fa-eye me-1"></i>No Resume
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="applications.php?view_application=<?= $app['id'] ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-user me-1"></i>View Profile
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No recent applications <?php if($sort_status) echo "with status '".htmlspecialchars($sort_status)."'"; ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Top Candidates</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if (!empty($top_candidates)): ?>
                                <?php foreach ($top_candidates as $candidate): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="candidate-card card h-100">
                                            <div class="card-body">
                                                <div class="candidate-avatar">
                                                    <?php 
                                                    $name_parts = explode(' ', $candidate['name']);
                                                    if (count($name_parts) >= 2) {
                                                        echo strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[1], 0, 1));
                                                    } else {
                                                        echo strtoupper(substr($candidate['name'], 0, 2));
                                                    }
                                                    ?>
                                                </div>
                                                <h5 class="candidate-name"><?= $candidate['name'] ?></h5>
                                                <div class="candidate-title"><?= isset($candidate['applied_positions']) ? $candidate['applied_positions'] : $candidate['title'] ?></div>
                                                <div class="candidate-meta">
                                                    <span class="meta-item"><?= $candidate['experience'] ?></span>
                                                    <?php 
                                                    $skills = explode(',', $candidate['skills']);
                                                    foreach (array_slice($skills, 0, 2) as $skill): ?>
                                                        <span class="meta-item"><?= trim($skill) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                                <button class="btn btn-outline-primary w-100 view-profile-btn" 
                                                        data-candidate='<?= json_encode($candidate) ?>'>
                                                    View Profile
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <p class="text-center text-muted">No candidates found</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Upcoming Interviews</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($upcoming_interviews)): ?>
                            <?php foreach ($upcoming_interviews as $interview): ?>
                                <div class="d-flex mb-3">
                                    <div class="flex-shrink-0">
                                        <div class="bg-primary text-white rounded p-2">
                                            <i class="fas fa-calendar"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0"><?= $interview['candidate_name'] ?> - <?= $interview['position'] ?></h6>
                                        <small class="text-muted">
                                            <?= date('M d, h:i A', strtotime($interview['interview_time'])) ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No upcoming interviews</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="candidateModal" tabindex="-1" aria-labelledby="candidateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content candidate-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="candidateModalLabel">Candidate Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="candidate-avatar" id="modalAvatar">
                            </div>
                        <h4 id="modalName"></h4>
                        <p class="text-muted" id="modalTitle"></p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="contact-info">
                                <h6><i class="fas fa-envelope me-2"></i>Contact Information</h6>
                                <p><strong>Email:</strong> <span id="modalEmail"></span></p>
                                <p><strong>Phone:</strong> <span id="modalPhone"></span></p>
                                <p><strong>Experience:</strong> <span id="modalExperience"></span></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="contact-info">
                                <h6><i class="fas fa-briefcase me-2"></i>Current Position</h6>
                                <p><strong>Company:</strong> <span id="modalCurrentCompany"></span></p>
                                <p><strong>Position:</strong> <span id="modalCurrentPosition"></span></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <h6><i class="fas fa-star me-2"></i>Skills</h6>
                        <div id="modalSkills">
                            </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" id="modalResumeLink" target="_blank" class="btn btn-primary">
                        <i class="fas fa-download me-1"></i>View Resume
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="offcanvas offcanvas-end" tabindex="-1" id="notificationPanel" aria-labelledby="notificationPanelLabel">
        <div class="offcanvas-header border-bottom">
            <h5 id="notificationPanelLabel"><i class="fas fa-bell me-2"></i>Applicant Notifications</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <ul class="list-group list-group-flush" id="notification-list">
                <?php if ($applicant_notification_count > 0): ?>
                    <?php foreach ($applicant_notifications as $app): ?>
                        <li class="list-group-item list-group-item-action py-3 applicant-notification-item" 
                            data-application-id="<?= $app['id'] ?>" 
                            data-timestamp="<?= htmlspecialchars($app['applied_at']) ?>">
                            <div class="notification-icon-wrapper">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="notification-content">
                                <small class="float-end text-muted notification-time">--</small>
                                
                                <div class="notification-title"><?= htmlspecialchars($app['name']) ?></div>
                                <span class="notification-meta">
                                    <i class="fas fa-briefcase me-1 opacity-75"></i> Applied for <?= htmlspecialchars($app['job_designation']) ?>
                                </span>
                                <span class="notification-meta">
                                    <i class="fas fa-envelope me-1 opacity-75"></i> <?= htmlspecialchars($app['email']) ?>
                                </span>
                                
                                <a href="applications.php?view_application=<?= $app['id'] ?>" class="btn btn-primary btn-sm mt-2">
                                    <i class="fas fa-user me-1"></i> View Application
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <li class="list-group-item text-center text-muted p-4" id="no-notifications-msg">
                    <i class="fas fa-check-circle fs-3 d-block mb-2 text-success"></i>
                    You're all caught up!
                    <br>
                    <small>No new applicants.</small>
                </li>
            </ul>
        </div>
        
        <div class="offcanvas-footer p-3 border-top notification-footer">
            <button class="btn btn-outline-secondary w-100 mark-all-read-btn" id="mark-all-read">
                <i class="fas fa-check-double me-2"></i> Mark all as read
            </button>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    
    // === NEW timeAgo function ===
    function timeAgo(dateString) {
        // Must parse the SQL datetime string correctly
        const date = new Date(dateString.replace(/-/g, '/')); // Replace '-' with '/' for cross-browser compatibility
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        let interval = seconds / 31536000;
        if (interval > 1) {
            return Math.floor(interval) + (Math.floor(interval) === 1 ? " year" : " years") + " ago";
        }
        interval = seconds / 2592000;
        if (interval > 1) {
            return Math.floor(interval) + (Math.floor(interval) === 1 ? " month" : " months") + " ago";
        }
        interval = seconds / 86400;
        if (interval > 1) {
            return Math.floor(interval) + (Math.floor(interval) === 1 ? " day" : " days") + " ago";
        }
        interval = seconds / 3600;
        if (interval > 1) {
            return Math.floor(interval) + (Math.floor(interval) === 1 ? " hour" : " hours") + " ago";
        }
        interval = seconds / 60;
        if (interval > 1) {
            return Math.floor(interval) + (Math.floor(interval) === 1 ? " minute" : " minutes") + " ago";
        }
        if (seconds < 10) return "just now";
        
        return Math.floor(seconds) + " seconds ago";
    }

    // === NEW NOTIFICATION SCRIPT (handles persistence and time) ===
    document.addEventListener('DOMContentLoaded', function() {
        const currentEmployerId = <?= $user_id ?>;
        const storageKey = 'lastReadApplicantId_' + currentEmployerId;
        
        const notificationList = document.getElementById('notification-list');
        const notificationItems = document.querySelectorAll('.applicant-notification-item');
        const notificationBadge = document.querySelector('.notification-badge-count');
        const noNotificationsMsg = document.getElementById('no-notifications-msg');
        const notificationFooter = document.querySelector('.notification-footer');
        const markAllReadBtn = document.querySelector('.mark-all-read-btn');

        let visibleCount = 0;
        const lastReadId = parseInt(localStorage.getItem(storageKey) || '0');

        if (notificationItems.length > 0) {
            
            // --- Loop to set all timestamps first ---
            notificationItems.forEach(item => {
                const timestamp = item.dataset.timestamp;
                const timeEl = item.querySelector('.notification-time');
                if (timestamp && timeEl) {
                    timeEl.textContent = timeAgo(timestamp);
                }
            });
            // --- End loop ---

            notificationItems.forEach(item => {
                const applicationId = parseInt(item.dataset.applicationId);
                if (applicationId > lastReadId) {
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            if (visibleCount === 0) {
                if (notificationBadge) notificationBadge.style.display = 'none';
                if (noNotificationsMsg) noNotificationsMsg.style.display = 'block';
                if (notificationFooter) notificationFooter.style.display = 'none';
            } else {
                if (notificationBadge) {
                    notificationBadge.style.display = 'flex';
                    notificationBadge.textContent = visibleCount;
                }
                if (noNotificationsMsg) noNotificationsMsg.style.display = 'none';
                if (notificationFooter) notificationFooter.style.display = 'block';
            }
        } else {
            // No notifications came from server
            if (notificationBadge) notificationBadge.style.display = 'none';
            if (noNotificationsMsg) noNotificationsMsg.style.display = 'block';
            if (notificationFooter) notificationFooter.style.display = 'none';
        }


        // "Mark all as read" button click listener
        markAllReadBtn?.addEventListener('click', function() {
            // Find the newest notification item
            let latestApplicationId = 0;
            const allItems = document.querySelectorAll('.applicant-notification-item');
            if(allItems.length > 0) {
                 // The first item in the list is the newest (since we query ORDER BY id DESC)
                 latestApplicationId = allItems[0].dataset.applicationId;
            }

            // Store the latest ID in localStorage
            if(latestApplicationId > 0) {
                localStorage.setItem(storageKey, latestApplicationId);
            }

            // Clear the UI
            notificationItems.forEach(item => {
                item.style.display = 'none';
            });
            
            if (notificationBadge) notificationBadge.style.display = 'none';
            if (noNotificationsMsg) noNotificationsMsg.style.display = 'block';
    
            if (notificationFooter) notificationFooter.style.display = 'none';
        });

        // ===================================================
        // == START: AJAX Sort for Recent Applications
        // ===================================================
        const sortMenu = document.getElementById('sort-menu');
        const tableBody = document.getElementById('recent-applications-tbody');
        const sortButton = document.getElementById('sort-status-button');

        if (sortMenu && tableBody && sortButton) {
            sortMenu.addEventListener('click', function(event) {
                // Check if a .sort-link was clicked
                if (event.target.classList.contains('sort-link')) {
                    event.preventDefault(); // Stop the link from navigating

                    const status = event.target.getAttribute('data-status');
                    const buttonText = event.target.textContent;

                    // Build the fetch URL
                    const fetchUrl = status ? `employer_dashboard.php?sort_status=${status}` : 'employer_dashboard.php';

                    // Show loading state
                    tableBody.innerHTML = `<tr><td colspan="6" class="text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div> Loading...</td></tr>`;

                    // Fetch the updated content
                    fetch(fetchUrl)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.text();
                        })
                        .then(html => {
                            // Parse the full HTML response
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            
                            // Find the new table body from the fetched content
                            const newTbody = doc.getElementById('recent-applications-tbody');

                            if (newTbody) {
                                // Replace the current table body with the new one
                                tableBody.innerHTML = newTbody.innerHTML;
                            } else {
                                throw new Error('Could not find updated content.');
                            }

                            // Update the button text
                            sortButton.textContent = `Sort by: ${buttonText}`;
                        })
                        .catch(error => {
                            console.error('Error fetching applications:', error);
                            tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error loading applications. Please refresh the page.</td></tr>`;
                        });
                }
            });
        }
        // ===================================================
        // == END: AJAX Sort
        // ===================================================
        
        // Candidate Profile Modal Functionality
        const viewProfileButtons = document.querySelectorAll('.view-profile-btn');
        const candidateModal = new bootstrap.Modal(document.getElementById('candidateModal'));
        
        viewProfileButtons.forEach(button => {
            button.addEventListener('click', function() {
                const candidateData = JSON.parse(this.getAttribute('data-candidate'));
                populateCandidateModal(candidateData);
                candidateModal.show();
            });
        });
        
        function populateCandidateModal(candidate) {
            // Set basic information
            document.getElementById('modalName').textContent = candidate.name;
            document.getElementById('modalTitle').textContent = candidate.applied_positions || 'Candidate'; // Use applied_positions
            document.getElementById('modalEmail').textContent = candidate.email;
            document.getElementById('modalPhone').textContent = candidate.phone || 'Not provided';
            document.getElementById('modalExperience').textContent = candidate.experience;
            document.getElementById('modalCurrentCompany').textContent = candidate.current_company || 'Not provided';
            document.getElementById('modalCurrentPosition').textContent = candidate.current_position || 'Not provided';
            
            // Set avatar
            const nameParts = candidate.name.split(' ');
            let initials = '';
            if (nameParts.length >= 2) {
                initials = nameParts[0].charAt(0) + nameParts[nameParts.length - 1].charAt(0);
            } else {
                initials = candidate.name.substring(0, 2);
            }
            document.getElementById('modalAvatar').textContent = initials.toUpperCase();
            
            // Set skills
            const skillsContainer = document.getElementById('modalSkills');
            skillsContainer.innerHTML = '';
            if (candidate.skills) {
                const skills = candidate.skills.split(',');
                skills.forEach(skill => {
                    const skillBadge = document.createElement('span');
                    skillBadge.className = 'skill-badge';
                    skillBadge.textContent = skill.trim();
                    skillsContainer.appendChild(skillBadge);
                });
            } else {
                skillsContainer.textContent = 'No skills listed';
            }
            
            // Set resume link
            const resumeLink = document.getElementById('modalResumeLink');
            if (candidate.resume_path) {
                // Note: The resume_path from the query is just one of their resumes.
                // A more robust system might link to a specific application's resume.
                // For this modal, we'll use the one provided by the query.
                resumeLink.href = candidate.resume_path; 
                resumeLink.style.display = 'inline-block';
            } else {
                resumeLink.style.display = 'none';
            }
        }
    });
    // === END NOTIFICATION SCRIPT ===
    </script>
    
</body>
</html>