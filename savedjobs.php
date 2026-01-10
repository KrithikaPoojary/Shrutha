<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'employee'){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user'];
$page_title = "Saved Jobs";

// Fetch user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Handle unsave job action
if(isset($_POST['unsave_job'])) {
    $job_id = intval($_POST['job_id']);
    $unsave_sql = "DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?";
    $stmt = mysqli_prepare($conn, $unsave_sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $job_id);
    mysqli_stmt_execute($stmt);
    
    $_SESSION['message'] = "Job removed from saved jobs";
    header("Location: savedjobs.php");
    exit;
}

// Handle apply and unsave action
if(isset($_POST['apply_unsave'])) {
    $job_id = intval($_POST['job_id']);
    
    // First remove from saved jobs
    $unsave_sql = "DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?";
    $stmt = mysqli_prepare($conn, $unsave_sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $job_id);
    mysqli_stmt_execute($stmt);
    
    // Redirect to apply page
    header("Location: apply.php?job_id=" . $job_id);
    exit;
}

// Get user's saved jobs
$saved_jobs = [];
$sql = "SELECT sj.*, jo.*, c.organization_name 
        FROM saved_jobs sj 
        JOIN job_openings jo ON sj.job_id = jo.id 
        JOIN companies c ON jo.company_id = c.id 
        WHERE sj.user_id = ? 
        ORDER BY sj.saved_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $saved_jobs[] = $row;
}

// Get all job IDs that the user has applied for
$applied_jobs = [];
$applied_sql = "SELECT job_id FROM applications WHERE email = ?";
$stmt_applied = mysqli_prepare($conn, $applied_sql);
mysqli_stmt_bind_param($stmt_applied, "s", $user['email']);
mysqli_stmt_execute($stmt_applied);
$applied_result = mysqli_stmt_get_result($stmt_applied);
while ($applied_row = mysqli_fetch_assoc($applied_result)) {
    $applied_jobs[] = $applied_row['job_id'];
}

// Handle save job action (if coming from other pages)
if(isset($_GET['save_job'])) {
    $job_id = intval($_GET['save_job']);
    
    // Check if already saved
    $check_sql = "SELECT id FROM saved_jobs WHERE user_id = ? AND job_id = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $job_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($result) == 0) {
        $save_sql = "INSERT INTO saved_jobs (user_id, job_id) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $save_sql);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $job_id);
        mysqli_stmt_execute($stmt);
        
        $_SESSION['message'] = "Job saved successfully!";
    } else {
        $_SESSION['message'] = "Job is already saved";
    }
    
    header("Location: savedjobs.php");
    exit;
}
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
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
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
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary) 10%, var(--secondary) 100%);
            min-height: 100vh;
            position: fixed;
            width: 255px;
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
        
        .job-card {
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.3s;
            border-left: 4px solid var(--primary);
        }
        
        .job-card:hover {
            transform: translateY(-5px);
        }
        
        .job-card .card-body {
            padding: 1.5rem;
        }
        
        .job-card .company-logo {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            background: #f0f5ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .job-card .job-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .job-card .company-name {
            color: var(--gray);
            margin-bottom: 1rem;
        }
        
        .job-card .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 1.5rem;
        }
        
        .job-card .meta-item {
            background: #f0f5ff;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .saved-badge {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1.5rem;
        }
        
        .empty-state h4 {
            color: #6c757d;
            margin-bottom: 1rem;
        }
        
        .empty-state p {
            color: #6c757d;
            margin-bottom: 2rem;
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
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="sidebar-logo">
                    <h4 class="text-white"><i class="fas fa-briefcase me-2"></i>EmployeePortal</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="sidebar-section">Employee Portal</li>
                    <li class="nav-item">
                        <a class="nav-link" href="employee_dashboard.php">
                            <i class="fas fa-fw fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <?php if (isset($registration) && $registration): ?>
                            <a class="nav-link" href="javascript:void(0)" 
                            title="You are already registered. Go to profile to edit details." 
                            data-bs-toggle="tooltip" data-bs-placement="right">
                                <i class="fas fa-fw fa-user-plus"></i>
                                <span>Registration</span>
                            </a>
                        <?php else: ?>
                            <a class="nav-link" href="employee_registration.php">
                                <i class="fas fa-fw fa-user-plus"></i>
                                <span>Registration</span>
                            </a>
                        <?php endif; ?>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="resume_builder.php">
                            <i class="fas fa-fw fa-file-alt"></i>
                            <span>Resume Builder</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="application_status.php">
                            <i class="fas fa-fw fa-clipboard-check"></i>
                            <span>Application Status</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="savedjobs.php">
                            <i class="fas fa-fw fa-bookmark"></i>
                            <span>Saved Jobs</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <?php if (isset($registration) && !$registration): ?>
                            <a class="nav-link" href="javascript:void(0)" 
                            title="You have not registered. Go to Registration to create your profile." 
                            data-bs-toggle="tooltip" data-bs-placement="right">
                                <i class="fas fa-fw fa-user-circle"></i>
                                <span>Profile</span>
                            </a>
                        <?php else: ?>
                            <a class="nav-link" href="profile.php">
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
                    <h1 class="h3 mb-0"><i class="fas fa-bookmark me-2"></i>Saved Jobs</h1>
                    <div>
                        <a href="jobs.php" class="btn btn-outline-primary me-2">
                            <i class="fas fa-briefcase me-1"></i> Browse More Jobs
                        </a>
                        <a href="employee_dashboard.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                        </a>
                    </div>
                </div>

                <?php if(isset($_SESSION['message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">My Saved Jobs</h5>
                        <span class="badge bg-primary"><?= count($saved_jobs) ?> saved</span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($saved_jobs)): ?>
                            <div class="row">
                                <?php foreach ($saved_jobs as $job): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card job-card h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div class="company-logo">
                                                        <i class="fas fa-building"></i>
                                                    </div>
                                                    <span class="saved-badge">
                                                        <i class="fas fa-bookmark me-1"></i> Saved
                                                    </span>
                                                </div>
                                                
                                                <h5 class="job-title"><?= htmlspecialchars($job['job_designation']) ?></h5>
                                                <div class="company-name"><?= htmlspecialchars($job['organization_name']) ?></div>
                                                
                                                <div class="job-meta">
                                                    <span class="meta-item">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        <?= htmlspecialchars($job['job_location']) ?>
                                                    </span>
                                                    <span class="meta-item">
                                                        <i class="fas fa-money-bill-wave me-1"></i>
                                                        ₹<?= number_format($job['from_ctc'], 1) ?>L-<?= number_format($job['to_ctc'], 1) ?>L
                                                    </span>
                                                    <span class="meta-item">
                                                        <i class="fas fa-briefcase me-1"></i>
                                                        <?= $job['exp_from'] ?>-<?= $job['exp_to'] ?> yrs
                                                    </span>
                                                </div>
                                                
                                                <p class="text-muted small mb-3">
                                                    <?= strlen($job['job_description']) > 120 ? substr($job['job_description'], 0, 120) . '...' : $job['job_description'] ?>
                                                </p>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        Saved on <?= date('M d, Y', strtotime($job['saved_at'])) ?>
                                                    </small>
                                                    
                                                    <div class="btn-group">
                                                        <?php if (in_array($job['id'], $applied_jobs)): ?>
                                                            <button class="btn btn-success btn-sm" disabled>
                                                                <i class="fas fa-check me-1"></i> Applied
                                                            </button>
                                                        <?php else: ?>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                                                <button type="submit" name="apply_unsave" class="btn btn-primary btn-sm" 
                                                                        onclick="return confirm('Apply for this job and remove from saved jobs?')">
                                                                    <i class="fas fa-paper-plane me-1"></i> Apply Now
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                                            <button type="submit" name="unsave_job" class="btn btn-outline-danger btn-sm" 
                                                                    onclick="return confirm('Remove this job from saved jobs?')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-bookmark"></i>
                                <h4>No Saved Jobs Yet</h4>
                                <p>You haven't saved any jobs for later. Start browsing and save jobs that interest you!</p>
                                <a href="jobs.php" class="btn btn-primary">
                                    <i class="fas fa-briefcase me-2"></i> Browse Available Jobs
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tooltip initialization
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
</body>
</html>