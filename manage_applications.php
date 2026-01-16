<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit;
}

$page_title = "Manage Applications";

// Handle application actions
if(isset($_POST['action'])) {
    $application_id = $_POST['application_id'];
    $new_status = $_POST['status'] ?? null;
    
    if($_POST['action'] == 'update_status' && $new_status) {
        $update_query = "UPDATE applications SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $new_status, $application_id);
        
        if(mysqli_stmt_execute($stmt)) {
            $_SESSION['success_msg'] = "Application status updated successfully";
        } else {
            $_SESSION['error_msg'] = "Error updating application status";
        }
    } elseif($_POST['action'] == 'delete') {
        $delete_query = "DELETE FROM applications WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $application_id);
        
        if(mysqli_stmt_execute($stmt)) {
            $_SESSION['success_msg'] = "Application deleted successfully";
        } else {
            $_SESSION['error_msg'] = "Error deleting application";
        }
    }
    
    header("Location: manage_applications.php");
    exit;
}

// Get all applications with job and company info
$applications_query = "
    SELECT a.*, jo.job_designation, jo.job_location, c.organization_name 
    FROM applications a 
    LEFT JOIN job_openings jo ON a.job_id = jo.id 
    LEFT JOIN companies c ON jo.company_id = c.id 
    ORDER BY a.applied_at DESC
";
$applications_result = mysqli_query($conn, $applications_query);

// Get application statistics
$total_applications = mysqli_num_rows($applications_result);
$submitted_apps = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM applications WHERE status = 'Submitted'"));
$review_apps = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM applications WHERE status = 'Under Review'"));
$shortlisted_apps = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM applications WHERE status = 'Shortlisted'"));
$hired_apps = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM applications WHERE status = 'Hired'"));
$rejected_apps = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM applications WHERE status = 'Rejected'"));
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
        /* Same styles as previous files */
        :root { --primary: #4e73df; --secondary: #1cc88a; --danger: #e74a3b; --warning: #f6c23e; --dark: #5a5c69; }
        .sidebar { background: linear-gradient(180deg, var(--primary) 10%, #224abe 100%); min-height: 100vh; position: fixed; width: 250px; }
        .sidebar .nav-link { color: rgba(255, 255, 255, 0.8); padding: 1rem; font-weight: 600; transition: all 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #fff; background-color: rgba(255, 255, 255, 0.1); transform: translateX(5px); }
        .sidebar .nav-link i { margin-right: 0.5rem; width: 20px; text-align: center; }
        .sidebar-logo { padding: 1.5rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 1rem; }
        .main-content { margin-left: 250px; width: calc(100% - 250px); }
        .card { border: none; border-radius: 0.5rem; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); margin-bottom: 1.5rem; }
        .card-header { background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0; padding: 1rem 1.25rem; font-weight: 600; color: var(--dark); }
        .stat-card { border-left: 0.35rem solid; padding: 1.25rem; }
        .stat-card.primary { border-left-color: var(--primary); }
        .stat-card.success { border-left-color: var(--secondary); }
        .stat-card.warning { border-left-color: var(--warning); }
        .stat-card.info { border-left-color: #36b9cc; }
        .stat-card.danger { border-left-color: var(--danger); }
        .stat-card .stat-icon {
            position: absolute;
            right: 1.25rem;
            top: 1.85rem;
            opacity: 0.2;
            font-size: 2.5rem;
        }
        .stat-card .stat-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: #5a5c69;
            margin-bottom: 0.25rem;
        }
        .stat-card .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2e2f37;
        }
        .top-bar { background: white; padding: 1rem 1.5rem; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); position: sticky; top: 0; z-index: 100; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .table th { font-weight: 600; color: #4e73df; }
        .table-hover tbody tr:hover { background-color: rgba(78, 115, 223, 0.05); }
        .badge-status { font-size: 0.75rem; }
        .skills-text { max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    </style>
</head>
<body>
    <!-- Success/Error Messages -->
    <?php if(isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            <?= $_SESSION['success_msg'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="sidebar-logo">
                    <h5 class="text-white"><i class="fas fa-briefcase me-2"></i>EmployeePortal</h5>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">
                            <i class="fas fa-fw fa-tachometer-alt"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pending_approvals.php">
                            <i class="fas fa-fw fa-clock"></i>
                            Pending Approvals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">
                            <i class="fas fa-fw fa-users"></i>Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_jobs.php">
                            <i class="fas fa-fw fa-briefcase"></i>Job Listings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_diaries.php">
                            <i class="fas fa-fw fa-book"></i>Visitor Diaries
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_applications.php">
                            <i class="fas fa-fw fa-file-alt"></i>Applications
                        </a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-fw fa-cog"></i>Settings
                        </a>
                    </li> -->
                    <li class="nav-item mt-4">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-fw fa-sign-out-alt"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <!-- Top Bar -->
                <div class="top-bar d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-0">Manage Job Applications</h1>
                    <div class="d-flex align-items-center">
                        <div class="user-avatar me-2">
                            <?= substr($_SESSION['name'], 0, 1) ?>
                        </div>
                        <div>
                            <div class="fw-bold"><?= $_SESSION['name'] ?></div>
                            <small class="text-muted">Admin</small>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="container-fluid py-4">
                    <div class="row mb-4">
                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card stat-card primary">
                                <div class="stat-title">Total Appiled</div>
                                <div class="stat-value"><?= $total_applications ?></div>
                                <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card stat-card info">
                                <div class="stat-title">Submitted</div>
                                <div class="stat-value"><?= $submitted_apps ?></div>
                                <div class="stat-icon"><i class="fas fa-paper-plane"></i></div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card stat-card warning">
                                <div class="stat-title">Under Review</div>
                                <div class="stat-value"><?= $review_apps ?></div>
                                <div class="stat-icon"><i class="fas fa-search"></i></div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card stat-card success">
                                <div class="stat-title">Shortlisted</div>
                                <div class="stat-value"><?= $shortlisted_apps ?></div>
                                <div class="stat-icon"><i class="fas fa-list"></i></div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card stat-card secondary">
                                <div class="stat-title">Hired</div>
                                <div class="stat-value"><?= $hired_apps ?></div>
                                <div class="stat-icon"><i class="fas fa-check"></i></div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card stat-card danger">
                                <div class="stat-title">Rejected</div>
                                <div class="stat-value"><?= $rejected_apps ?></div>
                                <div class="stat-icon"><i class="fas fa-times"></i></div>
                            </div>
                        </div>
                    </div>

                    <!-- Applications Table -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">All Job Applications</h5>
                            <input type="text" class="form-control form-control-sm w-auto" placeholder="Search applications..." id="searchInput">
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="applicationsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Applicant</th>
                                            <th>Email</th>
                                            <th>Job Position</th>
                                            <th>Company</th>
                                            <th>Location</th>
                                            <th>Experience</th>
                                            <th>Skills</th>
                                            <th>Status</th>
                                            <th>Applied Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($app = mysqli_fetch_assoc($applications_result)): 
                                            $status_class = [
                                                'Submitted' => 'info',
                                                'Under Review' => 'warning',
                                                'Shortlisted' => 'success',
                                                'Hired' => 'primary',
                                                'Rejected' => 'danger'
                                            ][$app['status']] ?? 'secondary';
                                        ?>
                                        <tr>
                                            <td><?= $app['id'] ?></td>
                                            <td><?= htmlspecialchars($app['name']) ?></td>
                                            <td><?= htmlspecialchars($app['email']) ?></td>
                                            <td><?= htmlspecialchars($app['job_designation'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($app['organization_name'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($app['job_location'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($app['experience']) ?></td>
                                            <td>
                                                <span class="skills-text" title="<?= htmlspecialchars($app['skills']) ?>">
                                                    <?= htmlspecialchars(substr($app['skills'], 0, 20)) ?>...
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $status_class ?> badge-status">
                                                    <?= $app['status'] ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($app['applied_at'])) ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-info" 
                                                            data-bs-toggle="modal" data-bs-target="#viewModal<?= $app['id'] ?>" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <!-- Status Dropdown -->
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-warning dropdown-toggle" type="button" 
                                                                data-bs-toggle="dropdown" title="Change Status">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <?php foreach(['Submitted', 'Under Review', 'Shortlisted', 'Hired', 'Rejected'] as $status): ?>
                                                                <li>
                                                                    <form method="post" class="d-inline">
                                                                        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                                                        <input type="hidden" name="status" value="<?= $status ?>">
                                                                        <button type="submit" name="action" value="update_status" 
                                                                                class="dropdown-item <?= $app['status'] == $status ? 'active' : '' ?>">
                                                                            <?= $status ?>
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                    
                                                    <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this application?');">
                                                        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>

                                                <!-- View Modal -->
                                                <div class="modal fade" id="viewModal<?= $app['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Application #<?= $app['id'] ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <h6>Personal Information</h6>
                                                                        <p><strong>Name:</strong> <?= htmlspecialchars($app['name']) ?></p>
                                                                        <p><strong>Email:</strong> <?= htmlspecialchars($app['email']) ?></p>
                                                                        <p><strong>Phone:</strong> <?= htmlspecialchars($app['phone']) ?></p>
                                                                        <p><strong>Address:</strong> <?= htmlspecialchars($app['address']) ?></p>
                                                                        <p><strong>City:</strong> <?= htmlspecialchars($app['city']) ?></p>
                                                                        <p><strong>State:</strong> <?= htmlspecialchars($app['state']) ?></p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <h6>Professional Information</h6>
                                                                        <p><strong>Job:</strong> <?= htmlspecialchars($app['job_designation'] ?? 'N/A') ?></p>
                                                                        <p><strong>Company:</strong> <?= htmlspecialchars($app['organization_name'] ?? 'N/A') ?></p>
                                                                        <p><strong>Experience:</strong> <?= htmlspecialchars($app['experience']) ?></p>
                                                                        <p><strong>Current Company:</strong> <?= htmlspecialchars($app['current_company'] ?? 'N/A') ?></p>
                                                                        <p><strong>Current Position:</strong> <?= htmlspecialchars($app['current_position'] ?? 'N/A') ?></p>
                                                                        <p><strong>Education:</strong> <?= htmlspecialchars($app['education']) ?></p>
                                                                    </div>
                                                                    <div class="col-12 mt-3">
                                                                        <h6>Skills & Additional Info</h6>
                                                                        <p><strong>Skills:</strong> <?= htmlspecialchars($app['skills']) ?></p>
                                                                        <p><strong>LinkedIn:</strong> 
                                                                            <?php if($app['linkedin']): ?>
                                                                                <a href="<?= htmlspecialchars($app['linkedin']) ?>" target="_blank">View Profile</a>
                                                                            <?php else: ?>
                                                                                N/A
                                                                            <?php endif; ?>
                                                                        </p>
                                                                        <?php if($app['cover_letter']): ?>
                                                                            <p><strong>Cover Letter:</strong><br><?= nl2br(htmlspecialchars($app['cover_letter'])) ?></p>
                                                                        <?php endif; ?>
                                                                        <?php if($app['resume_path']): ?>
                                                                            <p><strong>Resume:</strong> 
                                                                                <a href="<?= htmlspecialchars($app['resume_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                                    <i class="fas fa-download"></i> Download
                                                                                </a>
                                                                            </p>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const rows = document.querySelectorAll('#applicationsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });
    </script>
</body>
</html>