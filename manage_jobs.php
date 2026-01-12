<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit;
}

$page_title = "Manage Jobs";

// Handle job actions
if(isset($_POST['action'])) {
    $job_id = $_POST['job_id'];
    
    if($_POST['action'] == 'delete') {
        $delete_query = "DELETE FROM job_openings WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $job_id);
        
        if(mysqli_stmt_execute($stmt)) {
            $_SESSION['success_msg'] = "Job deleted successfully";
        } else {
            $_SESSION['error_msg'] = "Error deleting job";
        }
    } elseif($_POST['action'] == 'toggle_status') {
        // For job status toggle if needed
    }
    
    header("Location: manage_jobs.php");
    exit;
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
    $job_details = mysqli_stmt_get_result($stmt)->fetch_assoc();
}

// Get all jobs with company info
$jobs_query = "
    SELECT jo.*, c.organization_name, c.unique_id as company_id,
           (SELECT COUNT(*) FROM applications WHERE job_id = jo.id) as application_count
    FROM job_openings jo 
    LEFT JOIN companies c ON jo.company_id = c.id 
    ORDER BY jo.created_at DESC
";
$jobs_result = mysqli_query($conn, $jobs_query);

// Get job statistics
$total_jobs = mysqli_num_rows($jobs_result);
$active_jobs = $total_jobs; // Assuming all are active
$total_applications = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM applications"))['count'];
$total_companies = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT company_id) as count FROM job_openings"))['count'];
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
        .top-bar { background: white; padding: 1rem 1.5rem; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); position: sticky; top: 0; z-index: 100; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .table th { font-weight: 600; color: #4e73df; }
        .table-hover tbody tr:hover { background-color: rgba(78, 115, 223, 0.05); }
        .job-description { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .detail-item { margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e9ecef; }
        .detail-label { font-weight: 600; color: #6c757d; }
        .detail-value { color: #212529; }
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

    <!-- View Job Modal -->
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

    <!-- Edit Job Modal -->
    <div class="modal fade" id="editJobModal" tabindex="-1" aria-labelledby="editJobModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editJobModalLabel">Edit Job</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editJobForm" method="post">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="job_designation" class="form-label">Job Designation</label>
                                <input type="text" class="form-control" id="job_designation" name="job_designation" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="job_location" class="form-label">Job Location</label>
                                <input type="text" class="form-control" id="job_location" name="job_location" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="vacancies" class="form-label">Vacancies</label>
                                <input type="number" class="form-control" id="vacancies" name="vacancies" min="1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="qualification" class="form-label">Qualification</label>
                                <input type="text" class="form-control" id="qualification" name="qualification" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="from_ctc" class="form-label">From CTC (L)</label>
                                <input type="number" step="0.01" class="form-control" id="from_ctc" name="from_ctc" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="to_ctc" class="form-label">To CTC (L)</label>
                                <input type="number" step="0.01" class="form-control" id="to_ctc" name="to_ctc" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="job_description" class="form-label">Job Description</label>
                            <textarea class="form-control" id="job_description" name="job_description" rows="4" required></textarea>
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
                        <a class="nav-link active" href="manage_jobs.php">
                            <i class="fas fa-fw fa-briefcase"></i>Job Listings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_diaries.php">
                            <i class="fas fa-fw fa-book"></i>Visitor Diaries
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_applications.php">
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
                    <h1 class="h3 mb-0">Manage Job Listings</h1>
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
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card primary">
                                <div class="stat-title">Total Jobs</div>
                                <div class="stat-value"><?= $total_jobs ?></div>
                                <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card success">
                                <div class="stat-title">Active Jobs</div>
                                <div class="stat-value"><?= $active_jobs ?></div>
                                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card warning">
                                <div class="stat-title">Total Applications</div>
                                <div class="stat-value"><?= $total_applications ?></div>
                                <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card info">
                                <div class="stat-title">Companies</div>
                                <div class="stat-value"><?= $total_companies ?></div>
                                <div class="stat-icon"><i class="fas fa-building"></i></div>
                            </div>
                        </div>
                    </div>

                    <!-- Jobs Table -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">All Job Listings</h5>
                            <div class="d-flex">
                                <input type="text" class="form-control form-control-sm me-2" placeholder="Search jobs..." id="searchInput">
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="jobsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Job Title</th>
                                            <th>Company</th>
                                            <th>Location</th>
                                            <th>Vacancies</th>
                                            <th>Experience</th>
                                            <th>Salary</th>
                                            <th>Applications</th>
                                            <th>Posted</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($job = mysqli_fetch_assoc($jobs_result)): 
                                            $experience = "{$job['exp_from']} - {$job['exp_to']} years";
                                            if($job['exp_from'] == 0 && $job['exp_to'] == 0) $experience = "Fresher";
                                            $salary = "₹{$job['from_ctc']}L - ₹{$job['to_ctc']}L";
                                        ?>
                                        <tr>
                                            <td><?= $job['id'] ?></td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($job['job_designation']) ?></div>
                                                <small class="text-muted job-description" title="<?= htmlspecialchars($job['job_description']) ?>">
                                                    <?= htmlspecialchars(substr($job['job_description'], 0, 50)) ?>...
                                                </small>
                                            </td>
                                            <td><?= htmlspecialchars($job['organization_name'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($job['job_location']) ?></td>
                                            <td><?= $job['vacancies'] ?></td>
                                            <td><?= $experience ?></td>
                                            <td><?= $salary ?></td>
                                            <td>
                                                <span class="badge bg-<?= $job['application_count'] > 0 ? 'primary' : 'secondary' ?>">
                                                    <?= $job['application_count'] ?>
                                                </span>
                                            </td>
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
                                                    <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this job?');">
                                                        <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
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
            const rows = document.querySelectorAll('#jobsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });

        // View Job Modal
        document.querySelectorAll('.view-job-btn').forEach(button => {
            button.addEventListener('click', function() {
                const jobId = this.getAttribute('data-job-id');
                window.location.href = `manage_jobs.php?view_job_id=${jobId}#viewJobModal`;
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
                document.getElementById('job_designation').value = jobTitle;
                document.getElementById('job_location').value = jobLocation;
                document.getElementById('vacancies').value = vacancies;
                document.getElementById('qualification').value = qualification;
                document.getElementById('from_ctc').value = fromCtc;
                document.getElementById('to_ctc').value = toCtc;
                document.getElementById('job_description').value = jobDescription;

                // Set form action
                document.getElementById('editJobForm').action = `update_job.php?id=${jobId}`;

                // Show modal
                const editModal = new bootstrap.Modal(document.getElementById('editJobModal'));
                editModal.show();
            });
        });

        // Auto-show view modal if URL has hash
        document.addEventListener('DOMContentLoaded', function() {
            if(window.location.hash === '#viewJobModal') {
                const viewModal = new bootstrap.Modal(document.getElementById('viewJobModal'));
                viewModal.show();
            }
        });
    </script>
</body>
</html>