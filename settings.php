<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit;
}

$page_title = "Settings";

// Handle settings update
if(isset($_POST['update_settings'])) {
    // In a real application, you would update settings in a database table
    // For now, we'll just show a success message
    $_SESSION['success_msg'] = "Settings updated successfully";
    header("Location: settings.php");
    exit;
}

// Handle password change
if(isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password (in real app, check against database)
    $user_query = "SELECT password FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $user_query);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    if(password_verify($current_password, $user['password'])) {
        if($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "si", $hashed_password, $_SESSION['user_id']);
            
            if(mysqli_stmt_execute($stmt)) {
                $_SESSION['success_msg'] = "Password changed successfully";
            } else {
                $_SESSION['error_msg'] = "Error changing password";
            }
        } else {
            $_SESSION['error_msg'] = "New passwords do not match";
        }
    } else {
        $_SESSION['error_msg'] = "Current password is incorrect";
    }
    
    header("Location: settings.php");
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
        .settings-nav .nav-link {
            color: var(--dark);
            padding: 0.75rem 1rem;
            border-radius: 0.35rem;
            margin-bottom: 0.5rem;
        }
        .settings-nav .nav-link.active {
            background-color: var(--primary);
            color: white;
        }
        .form-section {
            display: none;
        }
        .form-section.active {
            display: block;
        }
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
                        <a class="nav-link" href="manage_applications.php">
                            <i class="fas fa-fw fa-file-alt"></i>Applications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="settings.php">
                            <i class="fas fa-fw fa-cog"></i>Settings
                        </a>
                    </li>
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
                    <h1 class="h3 mb-0">System Settings</h1>
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

                <div class="container-fluid py-4">
                    <div class="row">
                        <!-- Settings Navigation -->
                        <div class="col-lg-3 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Settings</h6>
                                </div>
                                <div class="card-body p-0">
                                    <nav class="settings-nav">
                                        <a class="nav-link active" href="#" data-target="general-settings">
                                            <i class="fas fa-cog me-2"></i>General Settings
                                        </a>
                                        <a class="nav-link" href="#" data-target="password-settings">
                                            <i class="fas fa-lock me-2"></i>Password
                                        </a>
                                        <a class="nav-link" href="#" data-target="email-settings">
                                            <i class="fas fa-envelope me-2"></i>Email Settings
                                        </a>
                                        <a class="nav-link" href="#" data-target="notification-settings">
                                            <i class="fas fa-bell me-2"></i>Notifications
                                        </a>
                                        <a class="nav-link" href="#" data-target="backup-settings">
                                            <i class="fas fa-database me-2"></i>Backup & Restore
                                        </a>
                                    </nav>
                                </div>
                            </div>
                        </div>

                        <!-- Settings Content -->
                        <div class="col-lg-9">
                            <!-- General Settings -->
                            <div class="form-section active" id="general-settings">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">General Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="post">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Portal Name</label>
                                                    <input type="text" class="form-control" name="portal_name" value="Employee Portal" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Admin Email</label>
                                                    <input type="email" class="form-control" name="admin_email" value="admin@example.com" required>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Timezone</label>
                                                    <select class="form-select" name="timezone">
                                                        <option value="UTC">UTC</option>
                                                        <option value="Asia/Kolkata" selected>India (IST)</option>
                                                        <option value="America/New_York">Eastern Time (ET)</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Date Format</label>
                                                    <select class="form-select" name="date_format">
                                                        <option value="Y-m-d">YYYY-MM-DD</option>
                                                        <option value="d/m/Y" selected>DD/MM/YYYY</option>
                                                        <option value="m/d/Y">MM/DD/YYYY</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-12">
                                                    <label class="form-label">Portal Description</label>
                                                    <textarea class="form-control" name="portal_description" rows="3">Employment Portal for managing jobs, applications, and users.</textarea>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Max File Upload Size (MB)</label>
                                                    <input type="number" class="form-control" name="max_upload_size" value="10" min="1" max="100">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Allowed File Types</label>
                                                    <input type="text" class="form-control" name="allowed_files" value="pdf,doc,docx,jpg,png" placeholder="pdf,doc,jpg,png">
                                                </div>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="user_registration" checked>
                                                <label class="form-check-label">Allow User Registration</label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="email_verification" checked>
                                                <label class="form-check-label">Require Email Verification</label>
                                            </div>
                                            <button type="submit" name="update_settings" class="btn btn-primary">Save Changes</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Password Settings -->
                            <div class="form-section" id="password-settings">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Change Password</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="post">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Current Password</label>
                                                    <input type="password" class="form-control" name="current_password" required>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">New Password</label>
                                                    <input type="password" class="form-control" name="new_password" required>
                                                    <div class="form-text">Password must be at least 8 characters long.</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Confirm New Password</label>
                                                    <input type="password" class="form-control" name="confirm_password" required>
                                                </div>
                                            </div>
                                            <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Email Settings -->
                            <div class="form-section" id="email-settings">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Email Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="post">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">SMTP Host</label>
                                                    <input type="text" class="form-control" name="smtp_host" value="smtp.gmail.com">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">SMTP Port</label>
                                                    <input type="number" class="form-control" name="smtp_port" value="587">
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">SMTP Username</label>
                                                    <input type="email" class="form-control" name="smtp_username" value="your-email@gmail.com">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">SMTP Password</label>
                                                    <input type="password" class="form-control" name="smtp_password">
                                                </div>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="smtp_auth" checked>
                                                <label class="form-check-label">SMTP Authentication</label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="smtp_secure" checked>
                                                <label class="form-check-label">Use TLS/SSL</label>
                                            </div>
                                            <button type="submit" name="update_settings" class="btn btn-primary">Save Email Settings</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Notification Settings -->
                            <div class="form-section" id="notification-settings">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Notification Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="post">
                                            <h6 class="mb-3">Email Notifications</h6>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="notify_new_user" checked>
                                                <label class="form-check-label">New User Registration</label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="notify_new_job" checked>
                                                <label class="form-check-label">New Job Posting</label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="notify_new_application" checked>
                                                <label class="form-check-label">New Job Application</label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="notify_approval" checked>
                                                <label class="form-check-label">Approval Requests</label>
                                            </div>
                                            
                                            <h6 class="mb-3 mt-4">System Notifications</h6>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="system_errors" checked>
                                                <label class="form-check-label">System Error Reports</label>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="security_alerts" checked>
                                                <label class="form-check-label">Security Alerts</label>
                                            </div>
                                            
                                            <button type="submit" name="update_settings" class="btn btn-primary">Save Notification Settings</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Backup Settings -->
                            <div class="form-section" id="backup-settings">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Backup & Restore</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-4">
                                            <div class="col-md-6">
                                                <h6>Database Backup</h6>
                                                <p class="text-muted">Create a backup of your database.</p>
                                                <a href="backup_database.php" class="btn btn-success">
                                                    <i class="fas fa-download me-2"></i>Backup Database
                                                </a>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>System Backup</h6>
                                                <p class="text-muted">Backup all system files and database.</p>
                                                <a href="backup_system.php" class="btn btn-info">
                                                    <i class="fas fa-archive me-2"></i>Full System Backup
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-12">
                                                <h6>Restore Database</h6>
                                                <p class="text-muted">Restore database from backup file.</p>
                                                <form method="post" enctype="multipart/form-data">
                                                    <div class="mb-3">
                                                        <label class="form-label">Select Backup File</label>
                                                        <input type="file" class="form-control" name="backup_file" accept=".sql,.zip">
                                                    </div>
                                                    <button type="submit" name="restore_backup" class="btn btn-warning">
                                                        <i class="fas fa-upload me-2"></i>Restore Backup
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <div class="row mt-4">
                                            <div class="col-12">
                                                <div class="alert alert-warning">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    <strong>Warning:</strong> Restoring from backup will overwrite all current data. Make sure to backup current data first.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Settings navigation
        document.querySelectorAll('.settings-nav .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all links and sections
                document.querySelectorAll('.settings-nav .nav-link').forEach(l => l.classList.remove('active'));
                document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
                
                // Add active class to clicked link
                this.classList.add('active');
                
                // Show corresponding section
                const target = this.getAttribute('data-target');
                document.getElementById(target).classList.add('active');
            });
        });

        // Password strength indicator (basic)
        document.querySelector('input[name="new_password"]')?.addEventListener('input', function() {
            const password = this.value;
            const strength = document.getElementById('password-strength');
            
            if (!strength) {
                const strengthDiv = document.createElement('div');
                strengthDiv.id = 'password-strength';
                strengthDiv.className = 'form-text';
                this.parentNode.appendChild(strengthDiv);
            }
            
            let strengthText = '';
            let strengthClass = '';
            
            if (password.length === 0) {
                strengthText = '';
            } else if (password.length < 8) {
                strengthText = 'Weak';
                strengthClass = 'text-danger';
            } else if (password.length < 12) {
                strengthText = 'Medium';
                strengthClass = 'text-warning';
            } else {
                strengthText = 'Strong';
                strengthClass = 'text-success';
            }
            
            document.getElementById('password-strength').innerHTML = strengthText;
            document.getElementById('password-strength').className = `form-text ${strengthClass}`;
        });
    </script>
</body>
</html>