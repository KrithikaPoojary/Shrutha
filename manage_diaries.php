<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit;
}

$page_title = "Manage Visitor Diaries";

// Handle diary actions
if(isset($_POST['action'])) {
    $diary_id = $_POST['diary_id'];
    
    if($_POST['action'] == 'delete') {
        $delete_query = "DELETE FROM visitor_diaries WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $diary_id);
        
        if(mysqli_stmt_execute($stmt)) {
            $_SESSION['success_msg'] = "Visitor diary entry deleted successfully";
        } else {
            $_SESSION['error_msg'] = "Error deleting visitor diary entry";
        }
    }
    
    header("Location: manage_diaries.php");
    exit;
}

// Get all visitor diaries
$diaries_query = "SELECT * FROM visitor_diaries ORDER BY visit_date DESC";
$diaries_result = mysqli_query($conn, $diaries_query);

// Get diary statistics
$total_diaries = mysqli_num_rows($diaries_result);
$today_diaries = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM visitor_diaries WHERE DATE(visit_date) = CURDATE()"));
$avg_rating = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(rating) as avg_rating FROM visitor_diaries"))['avg_rating'];
$avg_rating = round($avg_rating, 1);
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
        .feedback-text { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .star-rating { color: #ffc107; }
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
                        <a class="nav-link active" href="manage_diaries.php">
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
                    <h1 class="h3 mb-0">Manage Visitor Diaries</h1>
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
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card stat-card primary">
                                <div class="stat-title">Total Entries</div>
                                <div class="stat-value"><?= $total_diaries ?></div>
                                <div class="stat-icon"><i class="fas fa-book"></i></div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card stat-card success">
                                <div class="stat-title">Today's Entries</div>
                                <div class="stat-value"><?= $today_diaries ?></div>
                                <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card stat-card warning">
                                <div class="stat-title">Avg. Rating</div>
                                <div class="stat-value"><?= $avg_rating ?>/5</div>
                                <div class="stat-icon"><i class="fas fa-star"></i></div>
                            </div>
                        </div>
                        <!-- <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card info">
                                <div class="stat-title">Actions</div>
                                <div class="stat-value">
                                    <a href="add_diary.php" class="btn btn-sm btn-light">Add Entry</a>
                                </div>
                                <div class="stat-icon"><i class="fas fa-plus"></i></div>
                            </div>
                        </div> -->
                    </div>

                    <!-- Diaries Table -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Visitor Diary Entries</h5>
                            <input type="text" class="form-control form-control-sm w-auto" placeholder="Search entries..." id="searchInput">
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="diariesTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Visitor Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Company</th>
                                            <th>Person to Visit</th>
                                            <th>Purpose</th>
                                            <th>Feedback</th>
                                            <th>Rating</th>
                                            <th>Visit Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($diary = mysqli_fetch_assoc($diaries_result)): ?>
                                        <tr>
                                            <td><?= $diary['id'] ?></td>
                                            <td><?= htmlspecialchars($diary['visitor_name']) ?></td>
                                            <td><?= htmlspecialchars($diary['email']) ?></td>
                                            <td><?= htmlspecialchars($diary['phone'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($diary['company'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($diary['person_to_visit'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($diary['purpose'] ?? 'N/A') ?></td>
                                            <td>
                                                <span class="feedback-text" title="<?= htmlspecialchars($diary['feedback']) ?>">
                                                    <?= htmlspecialchars(substr($diary['feedback'], 0, 30)) ?>...
                                                </span>
                                            </td>
                                            <td>
                                                <div class="star-rating">
                                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star<?= $i <= $diary['rating'] ? '' : '-o' ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </td>
                                            <td><?= date('M d, Y H:i', strtotime($diary['visit_date'])) ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-info" 
                                                            data-bs-toggle="modal" data-bs-target="#viewModal<?= $diary['id'] ?>" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this entry?');">
                                                        <input type="hidden" name="diary_id" value="<?= $diary['id'] ?>">
                                                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>

                                                <!-- View Modal -->
                                                <div class="modal fade" id="viewModal<?= $diary['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Visitor Diary Entry #<?= $diary['id'] ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-6"><strong>Visitor Name:</strong></div>
                                                                    <div class="col-6"><?= htmlspecialchars($diary['visitor_name']) ?></div>
                                                                    
                                                                    <div class="col-6"><strong>Email:</strong></div>
                                                                    <div class="col-6"><?= htmlspecialchars($diary['email']) ?></div>
                                                                    
                                                                    <div class="col-6"><strong>Phone:</strong></div>
                                                                    <div class="col-6"><?= htmlspecialchars($diary['phone'] ?? 'N/A') ?></div>
                                                                    
                                                                    <div class="col-6"><strong>Company:</strong></div>
                                                                    <div class="col-6"><?= htmlspecialchars($diary['company'] ?? 'N/A') ?></div>
                                                                    
                                                                    <div class="col-6"><strong>Person to Visit:</strong></div>
                                                                    <div class="col-6"><?= htmlspecialchars($diary['person_to_visit'] ?? 'N/A') ?></div>
                                                                    
                                                                    <div class="col-6"><strong>Purpose:</strong></div>
                                                                    <div class="col-6"><?= htmlspecialchars($diary['purpose'] ?? 'N/A') ?></div>
                                                                    
                                                                    <div class="col-12 mt-3">
                                                                        <strong>Feedback:</strong>
                                                                        <p class="mt-1"><?= htmlspecialchars($diary['feedback']) ?></p>
                                                                    </div>
                                                                    
                                                                    <div class="col-6"><strong>Rating:</strong></div>
                                                                    <div class="col-6">
                                                                        <div class="star-rating">
                                                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                                                <i class="fas fa-star<?= $i <= $diary['rating'] ? '' : '-o' ?>"></i>
                                                                            <?php endfor; ?>
                                                                            (<?= $diary['rating'] ?>/5)
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="col-6"><strong>Visit Date:</strong></div>
                                                                    <div class="col-6"><?= date('M d, Y H:i', strtotime($diary['visit_date'])) ?></div>
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
            const rows = document.querySelectorAll('#diariesTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });
    </script>
</body>
</html>