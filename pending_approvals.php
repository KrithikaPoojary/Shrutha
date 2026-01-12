<?php
session_start();
require_once 'config.php';

// Redirect if not admin
if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit;
}

$page_title = "Pending Approvals";

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php'; // Or adjust path if using manual installation

// Handle approval/rejection
if(isset($_POST['action']) && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $user_email = mysqli_real_escape_string($conn, $_POST['user_email'] ?? '');
    $user_name = mysqli_real_escape_string($conn, $_POST['user_name'] ?? '');
    
    if($_POST['action'] === 'approve') {
        $sql = "UPDATE users SET is_active=1 WHERE id=$user_id";
        if(mysqli_query($conn, $sql)) {
            // Send approval email
            sendApprovalEmail($user_email, $user_name, true);
            $_SESSION['approval_success'] = "User $user_email has been approved successfully!";
        } else {
            $_SESSION['approval_error'] = "Error approving user: " . mysqli_error($conn);
        }
    } elseif($_POST['action'] === 'reject') {
        $sql = "DELETE FROM users WHERE id=$user_id";
        if(mysqli_query($conn, $sql)) {
            // Send rejection email
            sendApprovalEmail($user_email, $user_name, false);
            $_SESSION['approval_success'] = "User $user_email has been rejected and removed!";
        } else {
            $_SESSION['approval_error'] = "Error rejecting user: " . mysqli_error($conn);
        }
    }
    
    // Redirect back to prevent form resubmission
    header("Location: pending_approvals.php");
    exit;
}

// Rest of the existing code remains the same...
// Get pending users
$pending_users = mysqli_query($conn, "SELECT * FROM users WHERE is_active=0 ORDER BY created_at DESC");
$pending_count = mysqli_num_rows($pending_users);

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

/**
 * Send approval/rejection email to user
 */
function sendApprovalEmail($email, $name, $approved = true) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Replace with your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'shruthaportal@gmail.com'; // Replace with your email
        $mail->Password   = 'sttt vkri eeug bxxq'; // Replace with your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('shruthaportal@gmail.com', 'Shrutha Portal');
        $mail->addAddress($email, $name);
        
        // Content
        $mail->isHTML(true);
        
        if ($approved) {
            $mail->Subject = 'Account Approved - Shrutha Portal';
            $mail->Body    = "
                <h2>Account Approved</h2>
                <p>Dear $name,</p>
                <p>Your account has been approved by the administrator. You can now login to the Employment Portal.</p>
                <p><strong>Login URL:</strong> <a href=\"http://localhost/employee_portal/login.php\">http://localhost/employee_portal/login.php</a></p>
                <p>Thank you for registering with us!</p>
                <br>
                <p>Best regards,<br>Shrutha Portal Team</p>
            ";
        } else {
            $mail->Subject = 'Account Registration Rejected - Shrutha Portal';
            $mail->Body    = "
                <h2>Registration Rejected</h2>
                <p>Dear $name,</p>
                <p>We regret to inform you that your account registration has been rejected by the administrator.</p>
                <p>If you believe this is a mistake, please contact the administrator or try registering again with complete information.</p>
                <br>
                <p>Best regards,<br>Shrutha Portal Team</p>
            ";
        }
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
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
        .approval-alert {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1050;
            max-width: 400px;
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
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
        .btn-group .btn {
            margin-right: 2px;
        }
        .btn-group .btn:last-child {
            margin-right: 0;
        }
    </style>
</head>
<body>
    <!-- Success/Error Messages -->
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
                            <i class="fas fa-fw fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="pending_approvals.php">
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
                    <!-- <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-fw fa-cog"></i>
                            Settings
                        </a>
                    </li> -->
                    <li class="nav-item mt-4">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-fw fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <!-- Top Bar -->
                <div class="top-bar d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-0">Pending Approvals</h1>
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

                <!-- Pending Approvals Card -->
                <div class="container-fluid py-4">
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-warning">
                                        <i class="fas fa-clock me-2"></i>
                                        Pending User Approvals
                                        <span class="badge bg-warning ms-2"><?= $pending_count ?></span>
                                    </h6>
                                    <a href="admin_dashboard.php" class="btn btn-sm btn-primary">
                                        <i class="fas fa-arrow-left me-1"></i>
                                        Back to Dashboard
                                    </a>
                                </div>
                                <div class="card-body">
                                    <?php if ($pending_count > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover">
                                                <thead class="table-warning">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Role</th>
                                                        <th>Payment Reference</th>
                                                        <th>Registered Date</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $count = 1;
                                                    while($pending_row = mysqli_fetch_assoc($pending_users)):
                                                    ?>
                                                    <tr>
                                                        <td><?= $count++ ?></td>
                                                        <td><?= htmlspecialchars($pending_row['name']) ?></td>
                                                        <td><?= htmlspecialchars($pending_row['email']) ?></td>
                                                        <td>
                                                            <span class="badge bg-<?= $pending_row['role'] == 'admin' ? 'danger' : 'primary' ?>">
                                                                <?= ucfirst($pending_row['role']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($pending_row['payment_ref'])): ?>
                                                                <code><?= htmlspecialchars($pending_row['payment_ref']) ?></code>
                                                            <?php else: ?>
                                                                <span class="text-muted">No reference</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= date('M d, Y', strtotime($pending_row['created_at'])) ?></td>
                                                        <td>
                                                            <form method="post" action="pending_approvals.php" style="display:inline;">
                                                                <input type="hidden" name="user_id" value="<?= $pending_row['id'] ?>">
                                                                <input type="hidden" name="user_email" value="<?= htmlspecialchars($pending_row['email']) ?>">
                                                                <div class="btn-group" role="group">
                                                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success" 
                                                                            onclick="return confirm('Are you sure you want to approve <?= htmlspecialchars($pending_row['name']) ?>?')">
                                                                        <i class="fas fa-check me-1"></i>Approve
                                                                    </button>
                                                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger"
                                                                            onclick="return confirm('Are you sure you want to reject and delete <?= htmlspecialchars($pending_row['name']) ?>? This action cannot be undone.')">
                                                                        <i class="fas fa-times me-1"></i>Reject
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <i class="fas fa-check-circle"></i>
                                            <h4>No Pending Approvals</h4>
                                            <p class="mb-4">All users have been approved. You're all caught up!</p>
                                            
                                        </div>
                                    <?php endif; ?>
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
        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.approval-alert').forEach(alert => {
                new bootstrap.Alert(alert).close();
            });
        }, 5000);
    </script>
</body>
</html>