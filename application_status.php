<?php
// application_status.php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'employee'){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user'];
$page_title = "Application Status";

// Fetch user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Get user's applications with job and company details
$applications = [];
$sql = "SELECT a.*, j.job_designation, j.job_location, c.organization_name,
               (SELECT COUNT(*) FROM application_views av WHERE av.application_id = a.id AND av.view_type = 'profile') as profile_views,
               (SELECT COUNT(*) FROM application_views av WHERE av.application_id = a.id AND av.view_type = 'resume') as resume_views,
               (SELECT MAX(av.viewed_at) FROM application_views av WHERE av.application_id = a.id) as last_viewed
        FROM applications a
        JOIN job_openings j ON a.job_id = j.id
        JOIN companies c ON j.company_id = c.id
        WHERE a.email = ?
        ORDER BY a.applied_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $user['email']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $applications[] = $row;
}

// Create application_views table if it doesn't exist (for tracking employer views)
$create_table_sql = "CREATE TABLE IF NOT EXISTS application_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    employer_id INT NOT NULL,
    view_type ENUM('profile', 'resume') NOT NULL,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_view (application_id, employer_id, view_type)
)";
mysqli_query($conn, $create_table_sql);

// Generate initials from name
$initials = '';
$name_parts = explode(' ', $user['name']);
if(count($name_parts) >= 2) {
    $initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[count($name_parts)-1], 0, 1));
} else {
    $initials = strtoupper(substr($user['name'], 0, 2));
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
        
        .application-card {
            border-left: 4px solid;
            transition: transform 0.3s;
        }
        
        .application-card:hover {
            transform: translateY(-2px);
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }
        
        .status-submitted { border-left-color: #ffc107; }
        .status-under-review { border-left-color: #17a2b8; }
        .status-shortlisted { border-left-color: #007bff; }
        .status-hired { border-left-color: #28a745; }
        .status-rejected { border-left-color: #dc3545; }
        
        .view-indicator {
            background: #e7f3ff;
            border: 1px solid #b6d4fe;
            border-radius: 20px;
            padding: 0.3rem 0.8rem;
            font-size: 0.75rem;
            color: #0d6efd;
        }
        
        .company-logo {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: #f0f5ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--primary);
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
                        <a class="nav-link" href="employee_dashboard.php">
                            <i class="fas fa-fw fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <?php 
                        // Check if user has registration
                        $check_reg_sql = "SELECT * FROM registrations WHERE email = ?";
                        $stmt_reg = mysqli_prepare($conn, $check_reg_sql);
                        mysqli_stmt_bind_param($stmt_reg, "s", $user['email']);
                        mysqli_stmt_execute($stmt_reg);
                        $reg_result = mysqli_stmt_get_result($stmt_reg);
                        $has_registration = mysqli_fetch_assoc($reg_result);
                        ?>
                        <?php if ($has_registration): ?>
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
                        <a class="nav-link active" href="application_status.php">
                            <i class="fas fa-fw fa-clipboard-check"></i>
                            <span>Application Status</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="savedjobs.php">
                            <i class="fas fa-fw fa-bookmark"></i>
                            <span>Saved Jobs</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-fw fa-user-circle"></i>
                            <span>Profile</span>
                        </a>
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

            <!-- Main Content -->
            <div class="main-content">
                <!-- Top Bar -->
                <div class="top-bar d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-0"><i class="fas fa-clipboard-check me-2"></i>Application Status</h1>
                    <div class="d-flex align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-2">
                                <?= $initials ?>
                            </div>
                            <div>
                                <div class="fw-bold"><?= $user['name'] ?></div>
                                <small class="text-muted">Employee</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Applications Summary -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card primary">
                            <div class="position-relative">
                                <div class="stat-title">Total Applications</div>
                                <div class="stat-value"><?= count($applications) ?></div>
                                <div class="stat-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card success">
                            <div class="position-relative">
                                <div class="stat-title">Under Review</div>
                                <div class="stat-value"><?= count(array_filter($applications, function($app) { return $app['status'] === 'Under Review'; })) ?></div>
                                <div class="stat-icon">
                                    <i class="fas fa-search"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card info">
                            <div class="position-relative">
                                <div class="stat-title">Shortlisted</div>
                                <div class="stat-value"><?= count(array_filter($applications, function($app) { return $app['status'] === 'Shortlisted'; })) ?></div>
                                <div class="stat-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card primary">
                            <div class="position-relative">
                                <div class="stat-title">Profile Views</div>
                                <div class="stat-value"><?= array_sum(array_column($applications, 'profile_views')) ?></div>
                                <div class="stat-icon">
                                    <i class="fas fa-eye"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Applications List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Your Job Applications</h5>
                        <span class="badge bg-primary"><?= count($applications) ?> Applications</span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($applications)): ?>
                            <div class="row">
                                <?php foreach ($applications as $application): 
                                    $status_class = strtolower(str_replace(' ', '-', $application['status']));
                                ?>
                                    <div class="col-lg-6 mb-4">
                                        <div class="card application-card status-<?= $status_class ?> h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="company-logo me-3">
                                                            <i class="fas fa-building"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0"><?= htmlspecialchars($application['job_designation']) ?></h6>
                                                            <small class="text-muted"><?= htmlspecialchars($application['organization_name']) ?></small>
                                                        </div>
                                                    </div>
                                                    <span class="badge status-badge 
                                                        <?= $application['status'] == 'Submitted' ? 'bg-warning' : '' ?>
                                                        <?= $application['status'] == 'Under Review' ? 'bg-info' : '' ?>
                                                        <?= $application['status'] == 'Shortlisted' ? 'bg-primary' : '' ?>
                                                        <?= $application['status'] == 'Hired' ? 'bg-success' : '' ?>
                                                        <?= $application['status'] == 'Rejected' ? 'bg-danger' : '' ?>">
                                                        <?= $application['status'] ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <strong>Location:</strong> <?= htmlspecialchars($application['job_location']) ?>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <strong>Applied:</strong> <?= date('M d, Y', strtotime($application['applied_at'])) ?>
                                                </div>
                                                
                                                <!-- Employer View Indicators -->
                                                <?php if ($application['profile_views'] > 0 || $application['resume_views'] > 0): ?>
                                                    <div class="employer-views mb-3">
                                                        <div class="d-flex flex-wrap gap-2">
                                                            <?php if ($application['profile_views'] > 0): ?>
                                                                <span class="view-indicator">
                                                                    <i class="fas fa-user-check me-1"></i>
                                                                    Profile viewed <?= $application['profile_views'] ?> time(s)
                                                                </span>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($application['resume_views'] > 0): ?>
                                                                <span class="view-indicator">
                                                                    <i class="fas fa-file-download me-1"></i>
                                                                    Resume downloaded <?= $application['resume_views'] ?> time(s)
                                                                </span>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($application['last_viewed']): ?>
                                                                <small class="text-muted w-100">
                                                                    Last viewed: <?= date('M d, Y g:i A', strtotime($application['last_viewed'])) ?>
                                                                </small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-muted mb-3">
                                                        <small>
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            Employers haven't viewed your application yet
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="d-flex justify-content-between align-items-center mt-3">
                                                    <?php if ($application['resume_path'] && file_exists($application['resume_path'])): ?>
                                                        <a href="<?= htmlspecialchars($application['resume_path']) ?>" 
                                                           target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-download me-1"></i>View Submitted Resume
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted small">No resume submitted</span>
                                                    <?php endif; ?>
                                                    
                                                    <button class="btn btn-sm btn-outline-secondary view-details-btn" 
                                                            data-application='<?= json_encode($application) ?>'>
                                                        <i class="fas fa-eye me-1"></i>View Details
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                <h4>No Applications Found</h4>
                                <p class="text-muted">You haven't applied for any jobs yet.</p>
                                <a href="jobs.php" class="btn btn-primary">Browse Jobs</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Application Details Modal -->
    <div class="modal fade" id="applicationDetailsModal" tabindex="-1" aria-labelledby="applicationDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="applicationDetailsModalLabel">Application Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="applicationDetailsContent">
                    <!-- Content will be populated by JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Application details modal
        document.addEventListener('DOMContentLoaded', function() {
            const viewDetailsButtons = document.querySelectorAll('.view-details-btn');
            const applicationDetailsModal = new bootstrap.Modal(document.getElementById('applicationDetailsModal'));
            
            viewDetailsButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const applicationData = JSON.parse(this.getAttribute('data-application'));
                    populateApplicationDetails(applicationData);
                    applicationDetailsModal.show();
                });
            });
            
            function populateApplicationDetails(application) {
                const modalContent = document.getElementById('applicationDetailsContent');
                
                let statusBadgeClass = 'bg-secondary';
                switch(application.status) {
                    case 'Submitted': statusBadgeClass = 'bg-warning'; break;
                    case 'Under Review': statusBadgeClass = 'bg-info'; break;
                    case 'Shortlisted': statusBadgeClass = 'bg-primary'; break;
                    case 'Hired': statusBadgeClass = 'bg-success'; break;
                    case 'Rejected': statusBadgeClass = 'bg-danger'; break;
                }
                
                modalContent.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Job Information</h6>
                            <p><strong>Position:</strong> ${application.job_designation}</p>
                            <p><strong>Company:</strong> ${application.organization_name}</p>
                            <p><strong>Location:</strong> ${application.job_location}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Application Status</h6>
                            <p>
                                <strong>Status:</strong> 
                                <span class="badge ${statusBadgeClass}">${application.status}</span>
                            </p>
                            <p><strong>Applied Date:</strong> ${new Date(application.applied_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6>Employer Activity</h6>
                            ${application.profile_views > 0 || application.resume_views > 0 ? `
                                <div class="alert alert-info">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><i class="fas fa-user-check me-2"></i><strong>Profile Views:</strong> ${application.profile_views}</p>
                                            <p><i class="fas fa-file-download me-2"></i><strong>Resume Downloads:</strong> ${application.resume_views}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><i class="fas fa-clock me-2"></i><strong>Last Viewed:</strong> ${application.last_viewed ? new Date(application.last_viewed).toLocaleString('en-US') : 'Not viewed yet'}</p>
                                        </div>
                                    </div>
                                    <p class="mb-0 mt-2"><small>Employers are showing interest in your application!</small></p>
                                </div>
                            ` : `
                                <div class="alert alert-light">
                                    <p class="mb-0"><i class="fas fa-info-circle me-2"></i>Employers haven't viewed your application yet. This is normal for recent applications.</p>
                                </div>
                            `}
                        </div>
                    </div>
                    
                    ${application.cover_letter ? `
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6>Cover Letter</h6>
                                <div class="border rounded p-3 bg-light">
                                    ${application.cover_letter}
                                </div>
                            </div>
                        </div>
                    ` : ''}
                    
                    ${application.resume_path ? `
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6>Submitted Resume</h6>
                                <a href="${application.resume_path}" target="_blank" class="btn btn-outline-primary">
                                    <i class="fas fa-download me-2"></i>Download Resume
                                </a>
                            </div>
                        </div>
                    ` : ''}
                `;
            }
            
            // Tooltip initialization
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
    </script>
</body>
</html>