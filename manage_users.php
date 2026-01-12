<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit;
}

$page_title = "Manage Users";

// Handle user actions
if(isset($_POST['action'])) {
    $user_id = $_POST['user_id'];
    
    if($_POST['action'] == 'delete') {
        $delete_query = "DELETE FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if(mysqli_stmt_execute($stmt)) {
            $_SESSION['success_msg'] = "User deleted successfully";
        } else {
            $_SESSION['error_msg'] = "Error deleting user";
        }
    } elseif($_POST['action'] == 'toggle_status') {
        $current_status = $_POST['current_status'];
        $new_status = $current_status ? 0 : 1;
        
        $update_query = "UPDATE users SET is_active = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ii", $new_status, $user_id);
        
        if(mysqli_stmt_execute($stmt)) {
            $_SESSION['success_msg'] = "User status updated successfully";
        } else {
            $_SESSION['error_msg'] = "Error updating user status";
        }
    }
    
    header("Location: manage_users.php");
    exit;
}

// Get user details for modal
$user_details = null;
if(isset($_GET['view_user_id'])) {
    $user_id = $_GET['view_user_id'];
    $user_query = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $user_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $user_details = mysqli_stmt_get_result($stmt)->fetch_assoc();
}

// Get all users
$users_query = "SELECT * FROM users ORDER BY created_at DESC";
$users_result = mysqli_query($conn, $users_query);

// Get user statistics
$total_users = mysqli_num_rows($users_result);
$active_users = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE is_active = 1"));
$pending_users = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE is_active = 0"));
$employee_users = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE role = 'employee'"));
$employer_users = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE role = 'employer'"));
$admin_users = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE role = 'admin'"));
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
            margin-bottom: 1.5rem;
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
        .stat-card.primary { border-left-color: var(--primary); }
        .stat-card.success { border-left-color: var(--secondary); }
        .stat-card.warning { border-left-color: var(--warning); }
        .stat-card.danger { border-left-color: var(--danger); }
        .stat-card.info { border-left-color: #36b9cc; }
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
        .table th { font-weight: 600; color: #4e73df; }
        .table-hover tbody tr:hover { background-color: rgba(78, 115, 223, 0.05); }
        .badge-role-admin { background-color: var(--danger); }
        .badge-role-employer { background-color: var(--primary); }
        .badge-role-employee { background-color: var(--secondary); }
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

    <?php if(isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
            <?= $_SESSION['error_msg'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_msg']); ?>
    <?php endif; ?>

    <!-- View User Modal -->
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

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editUserForm" method="post">
                    <div class="modal-body">
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
                        <a class="nav-link active" href="manage_users.php">
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
                    <h1 class="h3 mb-0">Manage Users</h1>
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
                                <div class="stat-title">Total Users</div>
                                <div class="stat-value"><?= $total_users ?></div>
                                <div class="stat-icon"><i class="fas fa-users"></i></div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card stat-card success">
                                <div class="stat-title">Active Users</div>
                                <div class="stat-value"><?= $active_users ?></div>
                                <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card stat-card warning">
                                <div class="stat-title">Pending Users</div>
                                <div class="stat-value"><?= $pending_users ?></div>
                                <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card stat-card info">
                                <div class="stat-title">Employees</div>
                                <div class="stat-value"><?= $employee_users ?></div>
                                <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card stat-card danger">
                                <div class="stat-title">Employers</div>
                                <div class="stat-value"><?= $employer_users ?></div>
                                <div class="stat-icon"><i class="fas fa-building"></i></div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card stat-card dark">
                                <div class="stat-title">Admins</div>
                                <div class="stat-value"><?= $admin_users ?></div>
                                <div class="stat-icon"><i class="fas fa-crown"></i></div>
                            </div>
                        </div>
                    </div>

                    <!-- Users Table -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">All Users</h5>
                            <div class="d-flex">
                                <input type="text" class="form-control form-control-sm me-2" placeholder="Search users..." id="searchInput">
                                <button class="btn btn-sm btn-primary" onclick="exportUsers()">
                                    <i class="fas fa-download me-1"></i>Export
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="usersTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Payment Ref</th>
                                            <th>Status</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                                        <tr>
                                            <td><?= $user['id'] ?></td>
                                            <td><?= htmlspecialchars($user['name']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <span class="badge badge-role-<?= $user['role'] ?>">
                                                    <?= ucfirst($user['role']) ?>
                                                </span>
                                            </td>
                                            <td><code><?= htmlspecialchars($user['payment_ref'] ?? 'N/A') ?></code></td>
                                            <td>
                                                <span class="badge bg-<?= $user['is_active'] ? 'success' : 'warning' ?>">
                                                    <?= $user['is_active'] ? 'Active' : 'Pending' ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-info view-user-btn" 
                                                            data-user-id="<?= $user['id'] ?>" 
                                                            title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-warning edit-user-btn" 
                                                            data-user-id="<?= $user['id'] ?>"
                                                            data-user-name="<?= htmlspecialchars($user['name']) ?>"
                                                            data-user-email="<?= htmlspecialchars($user['email']) ?>"
                                                            data-user-role="<?= $user['role'] ?>"
                                                            data-user-status="<?= $user['is_active'] ?>"
                                                            data-user-payment-ref="<?= htmlspecialchars($user['payment_ref'] ?? '') ?>"
                                                            title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="post" style="display:inline;">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <input type="hidden" name="current_status" value="<?= $user['is_active'] ?>">
                                                        <button type="submit" name="action" value="toggle_status" 
                                                                class="btn btn-sm btn-<?= $user['is_active'] ? 'warning' : 'success' ?>" 
                                                                title="<?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                                            <i class="fas fa-<?= $user['is_active'] ? 'times' : 'check' ?>"></i>
                                                        </button>
                                                    </form>
                                                    <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
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
            const rows = document.querySelectorAll('#usersTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });

        // View User Modal
        document.querySelectorAll('.view-user-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                window.location.href = `manage_users.php?view_user_id=${userId}#viewUserModal`;
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
                document.getElementById('edit_name').value = userName;
                document.getElementById('edit_email').value = userEmail;
                document.getElementById('edit_role').value = userRole;
                document.getElementById('edit_status').value = userStatus;
                document.getElementById('edit_payment_ref').value = userPaymentRef;

                // Set form action
                document.getElementById('editUserForm').action = `update_user.php?id=${userId}`;

                // Show modal
                const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                editModal.show();
            });
        });

        // Auto-show view modal if URL has hash
        document.addEventListener('DOMContentLoaded', function() {
            if(window.location.hash === '#viewUserModal') {
                const viewModal = new bootstrap.Modal(document.getElementById('viewUserModal'));
                viewModal.show();
            }
        });

        function exportUsers() {
            // Simple CSV export implementation
            const rows = document.querySelectorAll('#usersTable tbody tr');
            let csv = 'ID,Name,Email,Role,Payment Ref,Status,Joined Date\n';
            
            rows.forEach(row => {
                if(row.style.display !== 'none') {
                    const cells = row.querySelectorAll('td');
                    const rowData = [];
                    cells.forEach((cell, index) => {
                        if(index !== 7) { // Skip actions column
                            let text = cell.textContent.trim();
                            // Remove badge text and get clean data
                            text = text.replace(/Active|Pending|admin|employer|employee/g, '').trim();
                            // Handle payment ref
                            if(index === 4 && text === 'N/A') text = '';
                            rowData.push(`"${text}"`);
                        }
                    });
                    csv += rowData.join(',') + '\n';
                }
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('hidden', '');
            a.setAttribute('href', url);
            a.setAttribute('download', 'users_export.csv');
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    </script>
</body>
</html>