<?php
session_start();
require_once 'config.php';

// Redirect if not admin
if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit;
}

$page_title = "Admin Dashboard";

// Get statistics safely
$users_count = 0;
$jobs_count = 0;
$diaries_count = 0;
$applications_count = 0;

// Users
$result = mysqli_query($conn, "SELECT COUNT(*) FROM users");
if ($result) {
    $users_count = mysqli_fetch_row($result)[0];
} else {
    error_log("Error in users count query: " . mysqli_error($conn));
}

// Jobs
$result = mysqli_query($conn, "SELECT COUNT(*) FROM job_openings"); // Changed from 'jobs' to 'job_openings' for consistency
if ($result) {
    $jobs_count = mysqli_fetch_row($result)[0];
} else {
    error_log("Error in jobs count query: " . mysqli_error($conn));
}

// Visitor Diaries
$result = mysqli_query($conn, "SELECT COUNT(*) FROM visitor_diaries");
if ($result) {
    $diaries_count = mysqli_fetch_row($result)[0];
} else {
    error_log("Error in diaries count query: " . mysqli_error($conn));
}

// Applications
$result = mysqli_query($conn, "SELECT COUNT(*) FROM applications");
if ($result) {
    $applications_count = mysqli_fetch_row($result)[0];
} else {
    error_log("Error in applications count query: " . mysqli_error($conn));
}

// Handle approval messages
$successMsg = '';
if (isset($_SESSION['approval_success'])) {
    $successMsg = $_SESSION['approval_success'];
    unset($_SESSION['approval_success']);
}

$errorMsg = '';
if (isset($_SESSION['approval_error'])) {
    $errorMsg = $_SESSION['approval_error'];
    unset($_SESSION['approval_error']);
}

// Get user details for modal
$user_details = null;
if(isset($_GET['view_user_id'])) {
    $user_id = $_GET['view_user_id'];
    $user_query = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $user_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $user_details_result = mysqli_stmt_get_result($stmt);
    if ($user_details_result) {
        $user_details = mysqli_fetch_assoc($user_details_result);
    } else {
        error_log("Error in view_user_id query: " . mysqli_error($conn));
    }
}

// Get job details for modal
$job_details = null;
if(isset($_GET['view_job_id'])) {
    $job_id = $_GET['view_job_id'];
    $job_query = "SELECT jo.*, c.organization_name, c.unique_id as company_id,
                         (SELECT COUNT(*) FROM applications WHERE job_id = jo.id) as application_count
                  FROM job_openings jo 
                  LEFT JOIN companies c ON jo.company_id = c.id 
                  WHERE jo.id = ?";
    $stmt = mysqli_prepare($conn, $job_query);
    mysqli_stmt_bind_param($stmt, "i", $job_id);
    mysqli_stmt_execute($stmt);
    $job_details_result = mysqli_stmt_get_result($stmt);
    if ($job_details_result) {
        $job_details = mysqli_fetch_assoc($job_details_result);
    } else {
        error_log("Error in view_job_id query: " . mysqli_error($conn));
    }
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
            --primary: #4e73df;
            --secondary: #1cc88a;
            --danger: #e74a3b;
            --warning: #f6c23e;
            --dark: #5a5c69;
        }
        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background: linear-gradient(180deg, var(--primary) 10%, #224abe 100%);
            min-height: 100vh;
            position: fixed;
            width: 250px;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        .sidebar .nav-link i {
            margin-right: 0.5rem;
            width: 20px;
            text-align: center;
        }
        .sidebar-logo {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }
        .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
        }
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 1.5rem;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem rgba(58, 59, 69, 0.25);
        }
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }
        .stat-card {
            border-left: 0.35rem solid;
            padding: 1.25rem;
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
        .stat-card.warning {
            border-left-color: var(--warning);
        }
        .stat-card .stat-icon {
            position: absolute;
            right: 1.25rem;
            top: 1.25rem;
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
        .badge-paid {
            background-color: var(--secondary);
        }
        .badge-pending {
            background-color: var(--warning);
        }
        .table-responsive {
            overflow-x: auto;
        }
        .table th {
            font-weight: 600;
            color: #4e73df;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }
        .quick-action-btn {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            margin-bottom: 0.75rem;
            background: linear-gradient(to right, #4e73df, #6f8de8);
            color: white;
            border: none;
            border-radius: 0.35rem;
            text-align: left;
            transition: all 0.3s;
        }
        .quick-action-btn:hover {
            background: linear-gradient(to right, #3a55c8, #5a75e0);
            transform: translateY(-3px);
        }
        .quick-action-btn i {
            font-size: 1.25rem;
            margin-right: 0.75rem;
        }
        .top-bar {
            background: white;
            padding: 1rem 1.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .notification-icon {
            position: relative;
        }
        .approval-alert {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1050;
            max-width: 400px;
        }
        .btn-group .btn {
            margin-right: 2px;
        }
        .btn-group .btn:last-child {
            margin-right: 0;
        }
        /* Modal Styles */
        .badge-role-admin { background-color: var(--danger); }
        .badge-role-employer { background-color: var(--primary); }
        .badge-role-employee { background-color: var(--secondary); }
        .detail-item { margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e9ecef; }
        .detail-label { font-weight: 600; color: #6c757d; }
        .detail-value { color: #212529; }
    </style>
</head>
<body>
    <?php if ($successMsg): ?>
        <div class="approval-alert alert alert-success alert-dismissible fade show">
            <?= $successMsg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($errorMsg): ?>
        <div class="approval-alert alert alert-danger alert-dismissible fade show">
            <?= $errorMsg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewUserModalLabel">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if($user_details): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">User ID</div>
                                <div class="detail-value"><?= $user_details['id'] ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Name</div>
                                <div class="detail-value"><?= htmlspecialchars($user_details['name']) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Email</div>
                                <div class="detail-value"><?= htmlspecialchars($user_details['email']) ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">Role</div>
                                <div class="detail-value">
                                    <span class="badge badge-role-<?= $user_details['role'] ?>">
                                        <?= ucfirst($user_details['role']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Status</div>
                                <div class="detail-value">
                                    <span class="badge bg-<?= $user_details['is_active'] ? 'success' : 'warning' ?>">
                                        <?= $user_details['is_active'] ? 'Active' : 'Pending' ?>
                                    </span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Joined Date</div>
                                <div class="detail-value"><?= date('M d, Y', strtotime($user_details['created_at'])) ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Payment Reference</div>
                        <div class="detail-value">
                            <code><?= htmlspecialchars($user_details['payment_ref'] ?? 'N/A') ?></code>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <div class="text-muted">User details not found.</div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editUserForm" action="update_user.php" method="post"> <div class="modal-body">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Role</label>
                            <select class="form-control" id="edit_role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="employer">Employer</option>
                                <option value="employee">Employee</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-control" id="edit_status" name="is_active" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_payment_ref" class="form-label">Payment Reference</label>
                            <input type="text" class="form-control" id="edit_payment_ref" name="payment_ref">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewJobModal" tabindex="-1" aria-labelledby="viewJobModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewJobModalLabel">Job Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if($job_details): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">Job Title</div>
                                <div class="detail-value"><?= htmlspecialchars($job_details['job_designation']) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Company</div>
                                <div class="detail-value"><?= htmlspecialchars($job_details['organization_name'] ?? 'N/A') ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Location</div>
                                <div class="detail-value"><?= htmlspecialchars($job_details['job_location']) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Vacancies</div>
                                <div class="detail-value"><?= $job_details['vacancies'] ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">Experience</div>
                                <div class="detail-value">
                                    <?php 
                                    $experience = "{$job_details['exp_from']} - {$job_details['exp_to']} years";
                                    if($job_details['exp_from'] == 0 && $job_details['exp_to'] == 0) $experience = "Fresher";
                                    echo $experience;
                                    ?>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Salary</div>
                                <div class="detail-value">₹<?= $job_details['from_ctc'] ?>L - ₹<?= $job_details['to_ctc'] ?>L</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Applications</div>
                                <div class="detail-value"><?= $job_details['application_count'] ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Posted</div>
                                <div class="detail-value"><?= date('M d, Y', strtotime($job_details['created_at'])) ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Job Description</div>
                        <div class="detail-value"><?= nl2br(htmlspecialchars($job_details['job_description'])) ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">Qualification</div>
                                <div class="detail-value"><?= htmlspecialchars($job_details['qualification']) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Course</div>
                                <div class="detail-value"><?= htmlspecialchars($job_details['course']) ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <div class="detail-label">Stream</div>
                                <div class="detail-value"><?= htmlspecialchars($job_details['stream']) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Cut-off</div>
                                <div class="detail-value"><?= htmlspecialchars($job_details['cut_off']) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <div class="text-muted">Job details not found.</div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editJobModal" tabindex="-1" aria-labelledby="editJobModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editJobModalLabel">Edit Job</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editJobForm" action="update_job.php" method="post"> <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_job_designation" class="form-label">Job Designation</label>
                                <input type="text" class="form-control" id="edit_job_designation" name="job_designation" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_job_location" class="form-label">Job Location</label>
                                <input type="text" class="form-control" id="edit_job_location" name="job_location" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_vacancies" class="form-label">Vacancies</label>
                                <input type="number" class="form-control" id="edit_vacancies" name="vacancies" min="1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_qualification" class="form-label">Qualification</label>
                                <input type="text" class="form-control" id="edit_qualification" name="qualification" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_from_ctc" class="form-label">From CTC (L)</label>
                                <input type="number" step="0.01" class="form-control" id="edit_from_ctc" name="from_ctc" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_to_ctc" class="form-label">To CTC (L)</label>
                                <input type="number" step="0.01" class="form-control" id="edit_to_ctc" name="to_ctc" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_job_description" class="form-label">Job Description</label>
                            <textarea class="form-control" id="edit_job_description" name="job_description" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="sidebar-logo">
                    <h5 class="text-white"><i class="fas fa-briefcase me-2"></i>EmployeePortal</h5>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_dashboard.php">
                            <i class="fas fa-fw fa-tachometer-alt"></i>
                            Dashboard
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
                            <i class="fas fa-fw fa-users"></i>
                            Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_jobs.php">
                            <i class="fas fa-fw fa-briefcase"></i>
                            Job Listings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_diaries.php">
                            <i class="fas fa-fw fa-book"></i>
                            Visitor Diaries
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_applications.php">
                            <i class="fas fa-fw fa-file-alt"></i>
                            Applications
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-fw fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>

            <div class="main-content">
                <div class="top-bar d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-0"><i class="fas fa-fw fa-user-shield"></i> Admin Dashboard</h1>
                    <div class="d-flex align-items-center">
                        
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
                </div>

                <div class="container-fluid py-4">
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card primary">
                                <div class="position-relative">
                                    <div class="stat-title">Total Users</div>
                                    <div class="stat-value"><?= $users_count ?></div>
                                    <div class="stat-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card success">
                                <div class="position-relative">
                                    <div class="stat-title">Job Listings</div>
                                    <div class="stat-value"><?= $jobs_count ?></div>
                                    <div class="stat-icon">
                                        <i class="fas fa-briefcase"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card info">
                                <div class="position-relative">
                                    <div class="stat-title">Visitor Diaries</div>
                                    <div class="stat-value"><?= $diaries_count ?></div>
                                    <div class="stat-icon">
                                        <i class="fas fa-book"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card warning">
                                <div class="position-relative">
                                    <div class="stat-title">Applications</div>
                                    <div class="stat-value"><?= $applications_count ?></div>
                                    <div class="stat-icon">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-warning">
                                        <i class="fas fa-clock me-2"></i>
                                        Pending Approvals
                                        <?php
                                        $pending_count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE is_active=0");
                                        $pending_total = $pending_count_result ? mysqli_fetch_assoc($pending_count_result)['count'] : 0;
                                        if ($pending_total > 0): ?>
                                            <span class="badge bg-warning ms-2"><?= $pending_total ?></span>
                                        <?php endif; ?>
                                    </h6>
                                    <a href="pending_approvals.php" class="btn btn-sm btn-warning">View All</a>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $pending_result = mysqli_query($conn, "SELECT * FROM users WHERE is_active=0 ORDER BY created_at DESC LIMIT 3");
                                    $pending_count = mysqli_num_rows($pending_result);
                                    
                                    if ($pending_count > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Payment Reference</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while($pending_row = mysqli_fetch_assoc($pending_result)): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($pending_row['name']) ?></td>
                                                        <td><?= htmlspecialchars($pending_row['email']) ?></td>
                                                        <td>
                                                            <?php if (!empty($pending_row['payment_ref'])): ?>
                                                                <code><?= htmlspecialchars($pending_row['payment_ref']) ?></code>
                                                            <?php else: ?>
                                                                <span class="text-muted">No reference</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <form method="post" action="process_approval.php" style="display:inline;">
                                                                <input type="hidden" name="user_id" value="<?= $pending_row['id'] ?>">
                                                                <div class="btn-group" role="group">
                                                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                                                                </div>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-check-circle text-success mb-3" style="font-size: 3rem;"></i>
                                            <h5 class="text-muted">No Pending Approvals</h5>
                                            <p class="text-muted">All users have been approved.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Recent Users</h5>
                                    <a href="manage_users.php" class="btn btn-sm btn-primary">View All</a>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Role</th>
                                                    <th>Joined</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $result = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC LIMIT 6");
                                                while($row = mysqli_fetch_assoc($result)):
                                                    $status_class = $row['is_active'] ? 'success' : 'danger';
                                                    $status_text = $row['is_active'] ? 'Active' : 'Inactive';
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $row['role'] == 'admin' ? 'danger' : ($row['role'] == 'employer' ? 'primary' : 'secondary') ?>">
                                                            <?= ucfirst($row['role']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $status_class ?>">
                                                            <?= $status_text ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-info view-user-btn" 
                                                                    data-user-id="<?= $row['id'] ?>" 
                                                                    title="View">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-warning edit-user-btn" 
                                                                    data-user-id="<?= $row['id'] ?>"
                                                                    data-user-name="<?= htmlspecialchars($row['name']) ?>"
                                                                    data-user-email="<?= htmlspecialchars($row['email']) ?>"
                                                                    data-user-role="<?= $row['role'] ?>"
                                                                    data-user-status="<?= $row['is_active'] ?>"
                                                                    data-user-payment-ref="<?= htmlspecialchars($row['payment_ref'] ?? '') ?>"
                                                                    title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Recent Job Listings</h5>
                                    <a href="manage_jobs.php" class="btn btn-sm btn-primary">View All</a>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Job Designation</th>
                                                    <th>Company</th>
                                                    <th>Location</th>
                                                    <th>Vacancies</th>
                                                    <th>Experience</th>
                                                    <th>Applications</th>
                                                    <th>Posted</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $jobs_result = mysqli_query($conn, "
                                                    SELECT jo.*, c.organization_name 
                                                    FROM job_openings jo 
                                                    LEFT JOIN companies c ON jo.company_id = c.id 
                                                    ORDER BY jo.created_at DESC 
                                                    LIMIT 3
                                                ");

                                                if (!$jobs_result) {
                                                    die("Query Error (Job Openings): " . mysqli_error($conn));
                                                }

                                                // Check if there are any results
                                                if (mysqli_num_rows($jobs_result) > 0) {
                                                    while ($job = mysqli_fetch_assoc($jobs_result)):
                                                        // Count applications for this job opening
                                                        $app_count = 0;
                                                        $app_result = mysqli_query($conn, "SELECT COUNT(*) FROM applications WHERE job_id = {$job['id']}");
                                                        if ($app_result) {
                                                            $app_count = mysqli_fetch_row($app_result)[0];
                                                        } else {
                                                            error_log("Error fetching application count: " . mysqli_error($conn));
                                                        }

                                                        // Format experience range
                                                        $experience = "{$job['exp_from']} - {$job['exp_to']} years";
                                                        if ($job['exp_from'] == 0 && $job['exp_to'] == 0) {
                                                            $experience = "Fresher";
                                                        } elseif ($job['exp_from'] == 0) {
                                                            $experience = "Up to {$job['exp_to']} years";
                                                        }
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($job['job_designation']) ?></td>
                                                    <td><?= htmlspecialchars($job['organization_name'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($job['job_location']) ?></td>
                                                    <td><?= $job['vacancies'] ?></td>
                                                    <td><?= $experience ?></td>
                                                    <td><?= $app_count ?></td>
                                                    <td><?= date('M d, Y', strtotime($job['created_at'])) ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-info view-job-btn" 
                                                                    data-job-id="<?= $job['id'] ?>" 
                                                                    title="View">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-warning edit-job-btn" 
                                                                    data-job-id="<?= $job['id'] ?>"
                                                                    data-job-title="<?= htmlspecialchars($job['job_designation']) ?>"
                                                                    data-job-location="<?= htmlspecialchars($job['job_location']) ?>"
                                                                    data-vacancies="<?= $job['vacancies'] ?>"
                                                                    data-qualification="<?= htmlspecialchars($job['qualification']) ?>"
                                                                    data-from-ctc="<?= $job['from_ctc'] ?>"
                                                                    data-to-ctc="<?= $job['to_ctc'] ?>"
                                                                    data-job-description="<?= htmlspecialchars($job['job_description']) ?>"
                                                                    title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php 
                                                    endwhile;
                                                } else {
                                                    echo '<tr><td colspan="8" class="text-center">No job openings found.</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mt-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Recent Job Applications</h5>
                                    <a href="manage_applications.php" class="btn btn-sm btn-primary">View All</a>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Applicant</th>
                                                    <th>Job Title</th>
                                                    <th>Email</th>
                                                    <th>Applied Date</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // STEP 1: Fetch applications into an array
                                                $applications_query = "
                                                    SELECT a.*, jo.job_designation, c.organization_name 
                                                    FROM applications a 
                                                    LEFT JOIN job_openings jo ON a.job_id = jo.id 
                                                    LEFT JOIN companies c ON jo.company_id = c.id
                                                    ORDER BY a.applied_at DESC 
                                                    LIMIT 5
                                                ";
                                                $applications_result = mysqli_query($conn, $applications_query);
                                                
                                                if (!$applications_result) {
                                                    die("Query Error (Applications): " . mysqli_error($conn));
                                                }
                                                
                                                $applications_data = []; // Array to hold app data
                                                if (mysqli_num_rows($applications_result) > 0) {
                                                    while ($row = mysqli_fetch_assoc($applications_result)) {
                                                        $applications_data[] = $row;
                                                    }
                                                }

                                                // STEP 2: Loop 1 (Table)
                                                if (!empty($applications_data)) {
                                                    foreach ($applications_data as $application):
                                                        // Status badge colors
                                                        $status_class = '';
                                                        switch ($application['status']) {
                                                            case 'Submitted': $status_class = 'bg-secondary'; break;
                                                            case 'Under Review': $status_class = 'bg-info'; break;
                                                            case 'Shortlisted': $status_class = 'bg-primary'; break;
                                                            case 'Hired': $status_class = 'bg-success'; break;
                                                            case 'Rejected': $status_class = 'bg-danger'; break;
                                                            default: $status_class = 'bg-secondary';
                                                        }
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($application['name']) ?></td>
                                                    <td><?= htmlspecialchars($application['job_designation'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($application['email']) ?></td>
                                                    <td><?= date('M d, Y', strtotime($application['applied_at'])) ?></td>
                                                    <td>
                                                        <span class="badge <?= $status_class ?>">
                                                            <?= $application['status'] ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-info" 
                                                                    data-bs-toggle="modal" data-bs-target="#viewAppModal<?= $application['id'] ?>" title="View">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            
                                                            <div class="dropdown">
                                                                <button class="btn btn-sm btn-warning dropdown-toggle" type="button" 
                                                                        data-bs-toggle="dropdown" title="Change Status">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <ul class="dropdown-menu">
                                                                    <?php foreach(['Submitted', 'Under Review', 'Shortlisted', 'Hired', 'Rejected'] as $status): ?>
                                                                        <li>
                                                                            <form method="post" action="manage_applications.php" class="d-inline">
                                                                                <input type="hidden" name="application_id" value="<?= $application['id'] ?>">
                                                                                <input type="hidden" name="status" value="<?= $status ?>">
                                                                                <button type="submit" name="action" value="update_status" 
                                                                                        class="dropdown-item <?= $application['status'] == $status ? 'active' : '' ?>">
                                                                                    <?= $status ?>
                                                                                </button>
                                                                            </form>
                                                                        </li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php 
                                                    endforeach;
                                                } else {
                                                    echo '<tr><td colspan="6" class="text-center">No applications found.</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">System Status</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // --- Queries for "Recent Activity" ---
                                    $new_users_query = "SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
                                    $new_users_result = mysqli_query($conn, $new_users_query);
                                    $new_users_count = $new_users_result ? mysqli_fetch_assoc($new_users_result)['count'] : 0;
                                    
                                    $new_apps_query = "SELECT COUNT(*) as count FROM applications WHERE applied_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
                                    $new_apps_result = mysqli_query($conn, $new_apps_query);
                                    $new_apps_count = $new_apps_result ? mysqli_fetch_assoc($new_apps_result)['count'] : 0;
                                    
                                    $new_jobs_query = "SELECT COUNT(*) as count FROM job_openings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
                                    $new_jobs_result = mysqli_query($conn, $new_jobs_query);
                                    $new_jobs_count = $new_jobs_result ? mysqli_fetch_assoc($new_jobs_result)['count'] : 0;

                                    // --- Queries for "Content Overview" ---
                                    $resume_count_query = "SELECT COUNT(*) as count FROM applications WHERE resume_path IS NOT NULL AND resume_path != ''";
                                    $resume_count_result = mysqli_query($conn, $resume_count_query);
                                    $resume_count = $resume_count_result ? mysqli_fetch_assoc($resume_count_result)['count'] : 0;
                                    
                                    $company_count_query = "SELECT COUNT(*) as count FROM companies";
                                    $company_count_result = mysqli_query($conn, $company_count_query);
                                    $company_count = $company_count_result ? mysqli_fetch_assoc($company_count_result)['count'] : 0;

                                    // $diaries_count is available from the top of the file
                                    // $pending_total is available from the 'Pending Approvals' card query
                                    ?>

                                    <h6 class="mb-3 fw-bold text-primary">Activity (Last 24h)</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><i class="fas fa-user-plus fa-fw me-2 text-muted"></i>New Users</span>
                                        <span class="fw-bold"><?= $new_users_count ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><i class="fas fa-file-alt fa-fw me-2 text-muted"></i>New Applications</span>
                                        <span class="fw-bold"><?= $new_apps_count ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span><i class="fas fa-briefcase fa-fw me-2 text-muted"></i>New Job Listings</span>
                                        <span class="fw-bold"><?= $new_jobs_count ?></span>
                                    </div>

                                    <hr>

                                    <h6 class="mb-3 mt-4 fw-bold text-primary">Content Overview</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><i class="fas fa-building fa-fw me-2 text-muted"></i>Total Companies</span>
                                        <span class="fw-bold"><?= $company_count ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><i class="fas fa-file-invoice fa-fw me-2 text-muted"></i>Total Resumes</span>
                                        <span class="fw-bold"><?= $resume_count ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><i class="fas fa-book fa-fw me-2 text-muted"></i>Diary Entries</span>
                                        <span class="fw-bold"><?= $diaries_count ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><i class="fas fa-clock fa-fw me-2 text-warning"></i>Pending Approvals</span>
                                        <span class="fw-bold text-warning"><?= $pending_total ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Recent Activities</h5>
                                </div>
                                <div class="card-body">
                                    <div class="list-group">
                                        <?php
                                        // Query to get recent activities from multiple tables
                                        $activities_query = "
                                            (SELECT 'user_registered' as type, name as title, CONCAT('New ', role, ' registered') as description, created_at as activity_date FROM users ORDER BY created_at DESC LIMIT 2)
                                            UNION ALL
                                            (SELECT 'job_created' as type, job_designation as title, CONCAT('New job listing created') as description, created_at as activity_date FROM job_openings ORDER BY created_at DESC LIMIT 2)
                                            UNION ALL
                                            (SELECT 'application_submitted' as type, name as title, CONCAT('New application submitted') as description, applied_at as activity_date FROM applications ORDER BY applied_at DESC LIMIT 2)
                                            UNION ALL
                                            (SELECT 'visitor_diary' as type, visitor_name as title, CONCAT('New visitor diary entry') as description, visit_date as activity_date FROM visitor_diaries ORDER BY visit_date DESC LIMIT 2)
                                            ORDER BY activity_date DESC 
                                            LIMIT 4
                                        ";
                                        
                                        $activities_result = mysqli_query($conn, $activities_query);
                                        
                                        if ($activities_result && mysqli_num_rows($activities_result) > 0) {
                                            while ($activity = mysqli_fetch_assoc($activities_result)) {
                                                $time_ago = get_time_ago(strtotime($activity['activity_date']));
                                                $icon = get_activity_icon($activity['type']);
                                                ?>
                                                <a href="#" class="list-group-item list-group-item-action">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1"><?= htmlspecialchars($activity['title']) ?></h6>
                                                        <small><?= $time_ago ?></small>
                                                    </div>
                                                    <p class="mb-1"><?= htmlspecialchars($activity['description']) ?></p>
                                                    <small><i class="fas <?= $icon ?> me-1"></i><?= ucfirst(str_replace('_', ' ', $activity['type'])) ?></small>
                                                </a>
                                                <?php
                                            }
                                        } else {
                                            echo '<div class="list-group-item text-center text-muted">No recent activities found.</div>';
                                        }
                                        
                                        // Helper function to get time ago
                                        function get_time_ago($time) {
                                            $time_difference = time() - $time;
                                            
                                            if ($time_difference < 1) { return 'less than 1 second ago'; }
                                            $condition = array(
                                                12 * 30 * 24 * 60 * 60 => 'year',
                                                30 * 24 * 60 * 60 => 'month',
                                                24 * 60 * 60 => 'day',
                                                60 * 60 => 'hour',
                                                60 => 'minute',
                                                1 => 'second'
                                            );
                                            
                                            foreach ($condition as $secs => $str) {
                                                $d = $time_difference / $secs;
                                                if ($d >= 1) {
                                                    $t = round($d);
                                                    return $t . ' ' . $str . ($t > 1 ? 's' : '') . ' ago';
                                                }
                                            }
                                        }
                                        
                                        // Helper function to get activity icon
                                        function get_activity_icon($type) {
                                            switch ($type) {
                                                case 'user_registered': return 'fa-user-plus';
                                                case 'job_created': return 'fa-briefcase';
                                                case 'application_submitted': return 'fa-file-alt';
                                                case 'visitor_diary': return 'fa-book';
                                                default: return 'fa-circle';
                                            }
                                        }

                                        // Helper function to render star ratings
                                        function render_stars($rating) {
                                            $stars_html = '';
                                            $rating = (int)$rating;
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $rating) {
                                                    // Use 'fas' for a filled star
                                                    $stars_html .= '<i class="fas fa-star text-warning"></i>';
                                                } else {
                                                    // Use 'far' for an empty star
                                                    $stars_html .= '<i class="far fa-star text-muted"></i>';
                                                }
                                            }
                                            return $stars_html;
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Recent Visitor Ratings</h5>
                                </div>
                                <div class="card-body">
                                    <div class="list-group">
                                        <?php
                                        // Query to get recent ratings from visitor_diaries
                                        $ratings_query = "
                                            SELECT visitor_name, rating, feedback, visit_date 
                                            FROM visitor_diaries 
                                            WHERE rating > 0 
                                            ORDER BY visit_date DESC 
                                            LIMIT 3
                                        ";
                                        
                                        $ratings_result = mysqli_query($conn, $ratings_query);
                                        
                                        if ($ratings_result && mysqli_num_rows($ratings_result) > 0) {
                                            while ($rating_row = mysqli_fetch_assoc($ratings_result)) {
                                                $time_ago = get_time_ago(strtotime($rating_row['visit_date']));
                                                $feedback_snippet = strlen($rating_row['feedback']) > 50 
                                                                    ? substr($rating_row['feedback'], 0, 50) . "..." 
                                                                    : $rating_row['feedback'];
                                                ?>
                                                <div class="list-group-item">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1"><?= htmlspecialchars($rating_row['visitor_name']) ?></h6>
                                                        <small class="text-muted"><?= $time_ago ?></small>
                                                    </div>
                                                    <div class="mb-1">
                                                        <?= render_stars($rating_row['rating']) ?>
                                                    </div>
                                                    <p class="mb-1 text-muted">"<?= htmlspecialchars($feedback_snippet) ?>"</p>
                                                </div>
                                                <?php
                                            }
                                        } else {
                                            echo '<div class="list-group-item text-center text-muted">No recent ratings found.</div>';
                                        }
                                        ?>
                                    </div>
                                    <a href="manage_diaries.php" class="btn btn-sm btn-outline-primary w-100 mt-3">View All Feedback</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    if (!empty($applications_data)) {
        foreach ($applications_data as $application):
    ?>
    <div class="modal fade" id="viewAppModal<?= $application['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Application #<?= $application['id'] ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Personal Information</h6>
                            <p><strong>Name:</strong> <?= htmlspecialchars($application['name']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($application['email']) ?></p>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($application['phone']) ?></p>
                            <p><strong>Address:</strong> <?= htmlspecialchars($application['address']) ?></p>
                            <p><strong>City:</strong> <?= htmlspecialchars($application['city']) ?></p>
                            <p><strong>State:</strong> <?= htmlspecialchars($application['state']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Professional Information</h6>
                            <p><strong>Job:</strong> <?= htmlspecialchars($application['job_designation'] ?? 'N/A') ?></p>
                            <p><strong>Company:</strong> <?= htmlspecialchars($application['organization_name'] ?? 'N/A') ?></p>
                            <p><strong>Experience:</strong> <?= htmlspecialchars($application['experience']) ?></p>
                            <p><strong>Current Company:</strong> <?= htmlspecialchars($application['current_company'] ?? 'N/A') ?></p>
                            <p><strong>Current Position:</strong> <?= htmlspecialchars($application['current_position'] ?? 'N/A') ?></p>
                            <p><strong>Education:</strong> <?= htmlspecialchars($application['education']) ?></p>
                        </div>
                        <div class="col-12 mt-3">
                            <h6>Skills & Additional Info</h6>
                            <p><strong>Skills:</strong> <?= htmlspecialchars($application['skills']) ?></p>
                            <p><strong>LinkedIn:</strong> 
                                <?php if($application['linkedin']): ?>
                                    <a href="<?= htmlspecialchars($application['linkedin']) ?>" target="_blank">View Profile</a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </p>
                            <?php if($application['cover_letter']): ?>
                                <p><strong>Cover Letter:</strong><br><?= nl2br(htmlspecialchars($application['cover_letter'])) ?></p>
                            <?php endif; ?>
                            <?php if($application['resume_path']): ?>
                                <p><strong>Resume:</strong> 
                                    <a href="<?= htmlspecialchars($application['resume_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
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
    <?php
        endforeach;
    }
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.approval-alert').forEach(alert => {
                if (alert) {
                    new bootstrap.Alert(alert).close();
                }
            });
        }, 5000);

        // Auto-show modals if URL has hash
        document.addEventListener('DOMContentLoaded', function() {
            if(window.location.hash === '#viewUserModal' && <?= isset($user_details) ? 'true' : 'false' ?>) {
                const viewModal = new bootstrap.Modal(document.getElementById('viewUserModal'));
                viewModal.show();
            }
            if(window.location.hash === '#viewJobModal' && <?= isset($job_details) ? 'true' : 'false' ?>) {
                const viewModal = new bootstrap.Modal(document.getElementById('viewJobModal'));
                viewModal.show();
            }
        });

        // View User Modal
        document.querySelectorAll('.view-user-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                // Reload the page with the correct GET parameter and hash
                window.location.href = `admin_dashboard.php?view_user_id=${userId}#viewUserModal`;
            });
        });

        // Edit User Modal
        document.querySelectorAll('.edit-user-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const userName = this.getAttribute('data-user-name');
                const userEmail = this.getAttribute('data-user-email');
                const userRole = this.getAttribute('data-user-role');
                const userStatus = this.getAttribute('data-user-status');
                const userPaymentRef = this.getAttribute('data-user-payment-ref');

                // Populate form fields
                const editModal = document.getElementById('editUserModal');
                editModal.querySelector('#edit_user_id').value = userId;
                editModal.querySelector('#edit_name').value = userName;
                editModal.querySelector('#edit_email').value = userEmail;
                editModal.querySelector('#edit_role').value = userRole;
                editModal.querySelector('#edit_status').value = userStatus;
                editModal.querySelector('#edit_payment_ref').value = userPaymentRef;

                // Set form action
                editModal.querySelector('form').action = `update_user.php?id=${userId}`;

                // Show modal
                const modalInstance = new bootstrap.Modal(editModal);
                modalInstance.show();
            });
        });

        // View Job Modal
        document.querySelectorAll('.view-job-btn').forEach(button => {
            button.addEventListener('click', function() {
                const jobId = this.getAttribute('data-job-id');
                window.location.href = `admin_dashboard.php?view_job_id=${jobId}#viewJobModal`;
            });
        });

        // Edit Job Modal
        document.querySelectorAll('.edit-job-btn').forEach(button => {
            button.addEventListener('click', function() {
                const jobId = this.getAttribute('data-job-id');
                const jobTitle = this.getAttribute('data-job-title');
                const jobLocation = this.getAttribute('data-job-location');
                const vacancies = this.getAttribute('data-vacancies');
                const qualification = this.getAttribute('data-qualification');
                const fromCtc = this.getAttribute('data-from-ctc');
                const toCtc = this.getAttribute('data-to-ctc');
                const jobDescription = this.getAttribute('data-job-description');

                // Populate form fields
                const editModal = document.getElementById('editJobModal');
                editModal.querySelector('#edit_job_designation').value = jobTitle;
                editModal.querySelector('#edit_job_location').value = jobLocation;
                editModal.querySelector('#edit_vacancies').value = vacancies;
                editModal.querySelector('#edit_qualification').value = qualification;
                editModal.querySelector('#edit_from_ctc').value = fromCtc;
                editModal.querySelector('#edit_to_ctc').value = toCtc;
                editModal.querySelector('#edit_job_description').value = jobDescription;

                // Set form action
                editModal.querySelector('form').action = `update_job.php?id=${jobId}`;

                // Show modal
                const modalInstance = new bootstrap.Modal(editModal);
                modalInstance.show();
            });
        });
    </script>
</body>
</html>