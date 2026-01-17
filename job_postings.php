<?php

session_start();
require_once 'config.php';
// Check if user is logged in and is an employer
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'employer') {
    header('Location: login.php');
    exit;
}

$employer_id = $_SESSION['user'];
$message = '';

// Get company ID for the employer
$company_id = null;
$company_sql = "SELECT id FROM companies WHERE contact_person = (SELECT name FROM users WHERE id = ?) LIMIT 1";
$company_stmt = mysqli_prepare($conn, $company_sql);
mysqli_stmt_bind_param($company_stmt, "i", $employer_id);
mysqli_stmt_execute($company_stmt);
$company_result = mysqli_stmt_get_result($company_stmt);
if ($company_row = mysqli_fetch_assoc($company_result)) {
    $company_id = $company_row['id'];
}
mysqli_stmt_close($company_stmt);

// Process job posting form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_job'])) {
    if (!$company_id) {
        $message = '<div class="alert error">You need to register your company first before posting jobs.</div>';
    } else {
        // Sanitize inputs
        function sanitize($input) {
            global $conn;
            return mysqli_real_escape_string($conn, trim($input));
        }
        
        $vacancies = (int)sanitize($_POST['vacancies']);
        $job_designation = sanitize($_POST['job_designation']);
        $qualification = sanitize($_POST['qualification']);
        $course = sanitize($_POST['course']);
        $stream = sanitize($_POST['stream']);
        $from_ctc = (float)sanitize($_POST['from_ctc']);
        $to_ctc = (float)sanitize($_POST['to_ctc']);
        $cut_off = sanitize($_POST['cut_off']);
        $job_location = sanitize($_POST['job_location']);
        $job_description = sanitize($_POST['job_description']);
        $exp_from = (int)sanitize($_POST['exp_from']);
        $exp_to = (int)sanitize($_POST['exp_to']);
        
        // Insert job opening
        $sql = "INSERT INTO job_openings (company_id, vacancies, job_designation, qualification, course, stream, from_ctc, to_ctc, cut_off, job_location, job_description, exp_from, exp_to) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iissssddsssii", $company_id, $vacancies, $job_designation, $qualification, $course, $stream, $from_ctc, $to_ctc, $cut_off, $job_location, $job_description, $exp_from, $exp_to);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = '<div class="alert success">Job posted successfully!</div>';
        } else {
            $message = '<div class="alert error">Error posting job: ' . mysqli_error($conn) . '</div>';
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Process job update form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_job'])) {
    $job_id = (int)$_POST['job_id'];
    
    // Verify the job belongs to the employer's company
    $verify_sql = "SELECT jo.id FROM job_openings jo 
                   JOIN companies c ON jo.company_id = c.id 
                   WHERE jo.id = ? AND c.contact_person = (SELECT name FROM users WHERE id = ?)";
    $verify_stmt = mysqli_prepare($conn, $verify_sql);
    mysqli_stmt_bind_param($verify_stmt, "ii", $job_id, $employer_id);
    mysqli_stmt_execute($verify_stmt);
    $verify_result = mysqli_stmt_get_result($verify_stmt);
    
    if (mysqli_num_rows($verify_result) > 0) {
        // Sanitize inputs
        function sanitize($input) {
            global $conn;
            return mysqli_real_escape_string($conn, trim($input));
        }
        
        $vacancies = (int)sanitize($_POST['vacancies']);
        $job_designation = sanitize($_POST['job_designation']);
        $qualification = sanitize($_POST['qualification']);
        $course = sanitize($_POST['course']);
        $stream = sanitize($_POST['stream']);
        $from_ctc = (float)sanitize($_POST['from_ctc']);
        $to_ctc = (float)sanitize($_POST['to_ctc']);
        $cut_off = sanitize($_POST['cut_off']);
        $job_location = sanitize($_POST['job_location']);
        $job_description = sanitize($_POST['job_description']);
        $exp_from = (int)sanitize($_POST['exp_from']);
        $exp_to = (int)sanitize($_POST['exp_to']);
        
        // Update job opening
        $update_sql = "UPDATE job_openings SET 
                        vacancies = ?, job_designation = ?, qualification = ?, course = ?, stream = ?, 
                        from_ctc = ?, to_ctc = ?, cut_off = ?, job_location = ?, job_description = ?, 
                        exp_from = ?, exp_to = ? 
                      WHERE id = ?";
        
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "issssddsssiii", $vacancies, $job_designation, $qualification, $course, $stream, $from_ctc, $to_ctc, $cut_off, $job_location, $job_description, $exp_from, $exp_to, $job_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $message = '<div class="alert success">Job updated successfully!</div>';
        } else {
            $message = '<div class="alert error">Error updating job: ' . mysqli_error($conn) . '</div>';
        }
        
        mysqli_stmt_close($update_stmt);
    } else {
        $message = '<div class="alert error">You are not authorized to update this job.</div>';
    }
    
    mysqli_stmt_close($verify_stmt);
}

// Get employer's job openings
$job_openings = [];
if ($company_id) {
    $sql = "SELECT jo.*, c.organization_name 
            FROM job_openings jo 
            JOIN companies c ON jo.company_id = c.id 
            WHERE jo.company_id = ? 
            ORDER BY jo.created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $company_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $job_openings[] = $row;
    }
    mysqli_stmt_close($stmt);
}

if (isset($_GET['delete_job'])) {
    $job_id = (int)$_GET['delete_job'];
    
    // Verify the job belongs to the employer's company
    $verify_sql = "SELECT jo.id FROM job_openings jo 
                   JOIN companies c ON jo.company_id = c.id 
                   WHERE jo.id = ? AND c.contact_person = (SELECT name FROM users WHERE id = ?)";
    $verify_stmt = mysqli_prepare($conn, $verify_sql);
    mysqli_stmt_bind_param($verify_stmt, "ii", $job_id, $employer_id);
    mysqli_stmt_execute($verify_stmt);
    $verify_result = mysqli_stmt_get_result($verify_stmt);
    
    if (mysqli_num_rows($verify_result) > 0) {
        // Delete job applications first (if they exist)
        $delete_apps_sql = "DELETE FROM applications WHERE job_id = ?";
        $delete_apps_stmt = mysqli_prepare($conn, $delete_apps_sql);
        mysqli_stmt_bind_param($delete_apps_stmt, "i", $job_id);
        mysqli_stmt_execute($delete_apps_stmt);
        mysqli_stmt_close($delete_apps_stmt);
        
        // Delete the job
        $delete_sql = "DELETE FROM job_openings WHERE id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "i", $job_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $message = '<div class="alert success">Job deleted successfully!</div>';
        } else {
            $message = '<div class="alert error">Error deleting job: ' . mysqli_error($conn) . '</div>';
        }
        
        mysqli_stmt_close($delete_stmt);
    } else {
        $message = '<div class="alert error">You are not authorized to delete this job.</div>';
    }
    
    mysqli_stmt_close($verify_stmt);
    
    // Redirect to avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle job status update
if (isset($_GET['toggle_status'])) {
    $job_id = (int)$_GET['toggle_status'];
    
    // Verify the job belongs to the employer's company
    $verify_sql = "SELECT jo.id FROM job_openings jo 
                   JOIN companies c ON jo.company_id = c.id 
                   WHERE jo.id = ? AND c.contact_person = (SELECT name FROM users WHERE id = ?)";
    $verify_stmt = mysqli_prepare($conn, $verify_sql);
    mysqli_stmt_bind_param($verify_stmt, "ii", $job_id, $employer_id);
    mysqli_stmt_execute($verify_stmt);
    $verify_result = mysqli_stmt_get_result($verify_stmt);
    
    if (mysqli_num_rows($verify_result) > 0) {
        // Toggle job status (you might want to add a status field to job_openings table)
        // For now, we'll just update a hypothetical 'status' field
        $update_sql = "UPDATE job_openings SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $job_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $message = '<div class="alert success">Job status updated successfully!</div>';
        } else {
            $message = '<div class="alert error">Error updating job status: ' . mysqli_error($conn) . '</div>';
        }
        
        mysqli_stmt_close($update_stmt);
    } else {
        $message = '<div class="alert error">You are not authorized to update this job.</div>';
    }
    
    mysqli_stmt_close($verify_stmt);
    
    // Redirect to avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Postings | Talent Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 1.1em;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .tab {
            padding: 15px 30px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 16px;
            font-weight: 500;
            color: #7f8c8d;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .tab.active {
            color: #3498db;
            border-bottom-color: #3498db;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-card, .jobs-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #419e63ff;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .job-item {
            border: 1px solid #ecf0f1;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        
        .job-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .job-title {
            font-size: 1.3em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .job-company {
            color: #3498db;
            font-weight: 500;
        }
        
        .job-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .job-meta span {
            background: #ecf0f1;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            color: #2c3e50;
        }
        
        .job-description {
            color: #7f8c8d;
            margin-bottom: 15px;
        }
        
        .job-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .job-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .detail-item {
            margin-bottom: 8px;
        }
        
        .detail-label {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .detail-value {
            color: #7f8c8d;
        }
        
        .job-actions {
            display: flex;
            gap: 10px;
        }
        
        .no-jobs {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        
        .no-jobs i {
            font-size: 3em;
            margin-bottom: 20px;
            color: #bdc3c7;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .company-info {
            background: #e8f4fc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .modal-header {
            padding: 20px 30px;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            color: #2c3e50;
        }

        .close {
            color: #7f8c8d;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #e74c3c;
        }

        .modal-body {
            padding: 30px;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        .job-stats {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="employer_dashboard.php" class="btn btn-outline-secondary" style="text-decoration: none; position: absolute; left: 40px; box-shadow: none; margin-top: 8px;">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
        <div class="header">
            <h1>Job Postings</h1>
            <p>Post new job openings and manage your existing listings</p>
        </div>
        
        <?php echo $message; ?>
        
        <?php if (!$company_id): ?>
            <div class="alert warning">
                <strong>Company Registration Required:</strong> You need to register your company first before posting jobs. 
                <a href="company_registration.php" style="color: #856404; text-decoration: underline;">Register your company here</a>.
            </div>
        <?php else: ?>
            <div class="company-info">
                <strong>Posting as:</strong> <?php 
                    $company_sql = "SELECT organization_name FROM companies WHERE id = ?";
                    $company_stmt = mysqli_prepare($conn, $company_sql);
                    mysqli_stmt_bind_param($company_stmt, "i", $company_id);
                    mysqli_stmt_execute($company_stmt);
                    $company_result = mysqli_stmt_get_result($company_stmt);
                    if ($company_row = mysqli_fetch_assoc($company_result)) {
                        echo htmlspecialchars($company_row['organization_name']);
                    }
                    mysqli_stmt_close($company_stmt);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab active" onclick="switchTab('post-job')">Post New Job</button>
            <button class="tab" onclick="switchTab('manage-jobs')">Manage Jobs</button>
        </div>
        
        <!-- Post Job Tab -->
        <div id="post-job" class="tab-content active">
            <div class="form-card">
                <h2 style="margin-bottom: 20px; color: #2c3e50;">Post a New Job Opening</h2>
                
                <?php if ($company_id): ?>
                    <form method="POST" action="">
                        <input type="hidden" name="post_job" value="1">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="job_designation">Job Designation *</label>
                                <input type="text" id="job_designation" name="job_designation" class="form-control" placeholder="e.g., Software Engineer" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="vacancies">Number of Vacancies *</label>
                                <input type="number" id="vacancies" name="vacancies" class="form-control" min="1" placeholder="e.g., 5" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="qualification">Qualification *</label>
                                <select id="qualification" name="qualification" class="form-control" required>
                                    <option value="">Select Qualification</option>
                                    <option value="SSLC">SSLC</option>
                                    <option value="PUC">PUC</option>
                                    <option value="Diploma">Diploma</option>
                                    <option value="Graduation">Graduation</option>
                                    <option value="Post Graduation">Post Graduation</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="course">Course *</label>
                                <select id="course" name="course" class="form-control" required>
                                    <option value="">Select Course</option>
                                    <option value="B.Tech">B.Tech</option>
                                    <option value="B.E">B.E</option>
                                    <option value="B.Sc">B.Sc</option>
                                    <option value="B.Com">B.Com</option>
                                    <option value="BCA">BCA</option>
                                    <option value="MCA">MCA</option>
                                    <option value="M.Tech">M.Tech</option>
                                    <option value="M.Sc">M.Sc</option>
                                    <option value="Diploma">Diploma</option>
                                    <option value="ITI">ITI</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="stream">Stream *</label>
                                <select id="stream" name="stream" class="form-control" required>
                                    <option value="">Select Stream</option>
                                    <option value="Computer Science">Computer Science</option>
                                    <option value="Information Science">Information Science</option>
                                    <option value="Electronics">Electronics</option>
                                    <option value="Mechanical">Mechanical</option>
                                    <option value="Civil">Civil</option>
                                    <option value="Electrical">Electrical</option>
                                    <option value="Chemical">Chemical</option>
                                    <option value="Biotechnology">Biotechnology</option>
                                    <option value="Science">Science</option>
                                    <option value="Commerce">Commerce</option>
                                    <option value="Arts">Arts</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="job_location">Job Location *</label>
                                <input type="text" id="job_location" name="job_location" class="form-control" placeholder="e.g., Bangalore, Karnataka" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="from_ctc">CTC From (LPA) *</label>
                                <input type="number" id="from_ctc" name="from_ctc" class="form-control" step="0.01" min="0" placeholder="e.g., 5.00" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="to_ctc">CTC To (LPA) *</label>
                                <input type="number" id="to_ctc" name="to_ctc" class="form-control" step="0.01" min="0" placeholder="e.g., 10.00" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="cut_off">Cut-off Percentage/CGPA</label>
                                <input type="text" id="cut_off" name="cut_off" class="form-control" placeholder="e.g., 60% or 6.5 CGPA">
                            </div>
                            
                            <div class="form-group">
                                <label for="exp_from">Experience From (Years)</label>
                                <input type="number" id="exp_from" name="exp_from" class="form-control" min="0" placeholder="e.g., 0" value="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="exp_to">Experience To (Years)</label>
                                <input type="number" id="exp_to" name="exp_to" class="form-control" min="0" placeholder="e.g., 3" value="0">
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="job_description">Job Description *</label>
                                <textarea id="job_description" name="job_description" class="form-control" placeholder="Describe the role, responsibilities, and what you're looking for..." required></textarea>
                            </div>
                        </div>
                        
                        <div style="text-align: center; margin-top: 30px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Post Job Opening
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="no-jobs">
                        <i class="fas fa-building"></i>
                        <h3>Company Registration Required</h3>
                        <p>You need to register your company before you can post job openings.</p>
                        <a href="company_registration.php" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-plus-circle"></i> Register Company
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Manage Jobs Tab -->
        <div id="manage-jobs" class="tab-content">
            <div class="jobs-card">
                <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
                    <h2 style="color: #2c3e50; margin: 10px;">Your Job Openings</h2>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-secondary" onclick="refreshJobs()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <?php if (empty($job_openings)): ?>
                    <div class="no-jobs">
                        <i class="fas fa-briefcase"></i>
                        <h3>No Job Openings Yet</h3>
                        <p>Start by posting your first job opening using the "Post New Job" tab.</p>
                    </div>
                <?php else: ?>
                    <div class="job-stats" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                            <div>
                                <strong>Total Jobs:</strong> <?php echo count($job_openings); ?>
                            </div>
                            <div>
                                <strong>Total Vacancies:</strong> 
                                <?php 
                                    $total_vacancies = 0;
                                    foreach ($job_openings as $job) {
                                        $total_vacancies += $job['vacancies'];
                                    }
                                    echo $total_vacancies;
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php foreach ($job_openings as $job): ?>
                        <div class="job-item" id="job-<?php echo $job['id']; ?>" 
                             data-id="<?php echo $job['id']; ?>"
                             data-vacancies="<?php echo $job['vacancies']; ?>"
                             data-job_designation="<?php echo htmlspecialchars($job['job_designation']); ?>"
                             data-qualification="<?php echo htmlspecialchars($job['qualification']); ?>"
                             data-course="<?php echo htmlspecialchars($job['course']); ?>"
                             data-stream="<?php echo htmlspecialchars($job['stream']); ?>"
                             data-from_ctc="<?php echo $job['from_ctc']; ?>"
                             data-to_ctc="<?php echo $job['to_ctc']; ?>"
                             data-cut_off="<?php echo htmlspecialchars($job['cut_off']); ?>"
                             data-job_location="<?php echo htmlspecialchars($job['job_location']); ?>"
                             data-job_description="<?php echo htmlspecialchars($job['job_description']); ?>"
                             data-exp_from="<?php echo $job['exp_from']; ?>"
                             data-exp_to="<?php echo $job['exp_to']; ?>">
                            <div class="job-header">
                                <div>
                                    <div class="job-title"><?php echo htmlspecialchars($job['job_designation']); ?></div>
                                    <div class="job-company"><?php echo htmlspecialchars($job['organization_name']); ?></div>
                                </div>
                                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 5px;">
                                    <div style="color: #27ae60; font-weight: 500;">
                                        <?php echo $job['vacancies']; ?> Vacancy<?php echo $job['vacancies'] > 1 ? 'ies' : ''; ?>
                                    </div>
                                    <div style="font-size: 0.9em; color: #7f8c8d;">
                                        ID: <?php echo $job['id']; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="job-meta">
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['job_location']); ?></span>
                                <span><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($job['qualification']); ?></span>
                                <span><i class="fas fa-money-bill-wave"></i> ₹<?php echo $job['from_ctc']; ?> - ₹<?php echo $job['to_ctc']; ?> LPA</span>
                                <span><i class="fas fa-calendar-alt"></i> Posted: <?php echo date('M d, Y', strtotime($job['created_at'])); ?></span>
                            </div>
                            
                            <div class="job-details">
                                <div class="job-details-grid">
                                    <div class="detail-item">
                                        <span class="detail-label">Course:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($job['course']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Stream:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($job['stream']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Experience:</span>
                                        <span class="detail-value"><?php echo $job['exp_from']; ?> - <?php echo $job['exp_to']; ?> years</span>
                                    </div>
                                    <?php if (!empty($job['cut_off'])): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Cut-off:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($job['cut_off']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="job-description">
                                <strong>Job Description:</strong><br>
                                <?php echo nl2br(htmlspecialchars($job['job_description'])); ?>
                            </div>
                            
                            <div class="job-actions">
                                <button class="btn btn-primary" onclick="openEditModal(<?php echo $job['id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-danger" onclick="deleteJob(<?php echo $job['id']; ?>, '<?php echo htmlspecialchars($job['job_designation']); ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                                <button class="btn btn-info" onclick="viewApplications(<?php echo $job['id']; ?>)">
                                    <i class="fas fa-users"></i> View Applications
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Job Modal -->
    <div id="editJobModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Job Opening</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editJobForm" method="POST" action="">
                    <input type="hidden" name="update_job" value="1">
                    <input type="hidden" id="edit_job_id" name="job_id" value="">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="edit_job_designation">Job Designation *</label>
                            <input type="text" id="edit_job_designation" name="job_designation" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_vacancies">Number of Vacancies *</label>
                            <input type="number" id="edit_vacancies" name="vacancies" class="form-control" min="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_qualification">Qualification *</label>
                            <select id="edit_qualification" name="qualification" class="form-control" required>
                                <option value="">Select Qualification</option>
                                <option value="SSLC">SSLC</option>
                                <option value="PUC">PUC</option>
                                <option value="Diploma">Diploma</option>
                                <option value="Graduation">Graduation</option>
                                <option value="Post Graduation">Post Graduation</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_course">Course *</label>
                            <select id="edit_course" name="course" class="form-control" required>
                                <option value="">Select Course</option>
                                <option value="B.Tech">B.Tech</option>
                                <option value="B.E">B.E</option>
                                <option value="B.Sc">B.Sc</option>
                                <option value="B.Com">B.Com</option>
                                <option value="BCA">BCA</option>
                                <option value="MCA">MCA</option>
                                <option value="M.Tech">M.Tech</option>
                                <option value="M.Sc">M.Sc</option>
                                <option value="Diploma">Diploma</option>
                                <option value="ITI">ITI</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_stream">Stream *</label>
                            <select id="edit_stream" name="stream" class="form-control" required>
                                <option value="">Select Stream</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Information Science">Information Science</option>
                                <option value="Electronics">Electronics</option>
                                <option value="Mechanical">Mechanical</option>
                                <option value="Civil">Civil</option>
                                <option value="Electrical">Electrical</option>
                                <option value="Chemical">Chemical</option>
                                <option value="Biotechnology">Biotechnology</option>
                                <option value="Science">Science</option>
                                <option value="Commerce">Commerce</option>
                                <option value="Arts">Arts</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_job_location">Job Location *</label>
                            <input type="text" id="edit_job_location" name="job_location" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_from_ctc">CTC From (LPA) *</label>
                            <input type="number" id="edit_from_ctc" name="from_ctc" class="form-control" step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_to_ctc">CTC To (LPA) *</label>
                            <input type="number" id="edit_to_ctc" name="to_ctc" class="form-control" step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_cut_off">Cut-off Percentage/CGPA</label>
                            <input type="text" id="edit_cut_off" name="cut_off" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_exp_from">Experience From (Years)</label>
                            <input type="number" id="edit_exp_from" name="exp_from" class="form-control" min="0" value="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_exp_to">Experience To (Years)</label>
                            <input type="number" id="edit_exp_to" name="exp_to" class="form-control" min="0" value="0">
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="edit_job_description">Job Description *</label>
                            <textarea id="edit_job_description" name="job_description" class="form-control" required></textarea>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Job Opening
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()" style="margin-left: 10px;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openEditModal(jobId) {
            const jobElement = document.getElementById('job-' + jobId);
            
            // Populate form fields with job data
            document.getElementById('edit_job_id').value = jobId;
            document.getElementById('edit_vacancies').value = jobElement.dataset.vacancies;
            document.getElementById('edit_job_designation').value = jobElement.dataset.job_designation;
            document.getElementById('edit_qualification').value = jobElement.dataset.qualification;
            document.getElementById('edit_course').value = jobElement.dataset.course;
            document.getElementById('edit_stream').value = jobElement.dataset.stream;
            document.getElementById('edit_from_ctc').value = jobElement.dataset.from_ctc;
            document.getElementById('edit_to_ctc').value = jobElement.dataset.to_ctc;
            document.getElementById('edit_cut_off').value = jobElement.dataset.cut_off;
            document.getElementById('edit_job_location').value = jobElement.dataset.job_location;
            document.getElementById('edit_job_description').value = jobElement.dataset.job_description;
            document.getElementById('edit_exp_from').value = jobElement.dataset.exp_from;
            document.getElementById('edit_exp_to').value = jobElement.dataset.exp_to;
            
            // Show modal
            document.getElementById('editJobModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editJobModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editJobModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }

        function deleteJob(jobId, jobTitle) {
            if (confirm('Are you sure you want to delete the job: "' + jobTitle + '"?\nThis action cannot be undone.')) {
                window.location.href = '<?php echo $_SERVER['PHP_SELF']; ?>?delete_job=' + jobId;
            }
        }

        function viewApplications(jobId) {
            // Redirect to applications page for this job
            window.location.href = 'applications.php?job_id=' + jobId;
        }

        function refreshJobs() {
            window.location.reload();
        }

        // Enhanced tab switching with URL hash support
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
            
            // Update URL hash
            window.location.hash = tabName;
        }

        // Load correct tab from URL hash on page load
        window.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash.substring(1);
            if (hash && (hash === 'post-job' || hash === 'manage-jobs')) {
                switchTab(hash);
            }
        });
        
        // Set up course and stream options based on qualification for create form
        document.getElementById('qualification').addEventListener('change', function() {
            const qualification = this.value;
            const courseSelect = document.getElementById('course');
            const streamSelect = document.getElementById('stream');
            
            // Reset options
            courseSelect.innerHTML = '<option value="">Select Course</option>';
            streamSelect.innerHTML = '<option value="">Select Stream</option>';
            
            if (qualification === 'Graduation') {
                courseSelect.innerHTML += `
                    <option value="B.Tech">B.Tech</option>
                    <option value="B.E">B.E</option>
                    <option value="B.Sc">B.Sc</option>
                    <option value="B.Com">B.Com</option>
                    <option value="BCA">BCA</option>
                    <option value="BA">BA</option>
                    <option value="BBA">BBA</option>
                `;
            } else if (qualification === 'Post Graduation') {
                courseSelect.innerHTML += `
                    <option value="M.Tech">M.Tech</option>
                    <option value="MCA">MCA</option>
                    <option value="M.Sc">M.Sc</option>
                    <option value="MBA">MBA</option>
                    <option value="MA">MA</option>
                `;
            } else if (qualification === 'Diploma') {
                courseSelect.innerHTML += `
                    <option value="Diploma">Diploma</option>
                `;
            } else if (qualification === 'ITI') {
                courseSelect.innerHTML += `
                    <option value="ITI">ITI</option>
                `;
            }
        });
        
        // Auto-fill stream based on course for create form
        document.getElementById('course').addEventListener('change', function() {
            const course = this.value;
            const streamSelect = document.getElementById('stream');
            
            streamSelect.innerHTML = '<option value="">Select Stream</option>';
            
            if (course === 'B.Tech' || course === 'B.E' || course === 'M.Tech') {
                streamSelect.innerHTML += `
                    <option value="Computer Science">Computer Science</option>
                    <option value="Information Science">Information Science</option>
                    <option value="Electronics">Electronics</option>
                    <option value="Mechanical">Mechanical</option>
                    <option value="Civil">Civil</option>
                    <option value="Electrical">Electrical</option>
                    <option value="Chemical">Chemical</option>
                    <option value="Biotechnology">Biotechnology</option>
                `;
            } else if (course === 'B.Sc' || course === 'M.Sc') {
                streamSelect.innerHTML += `
                    <option value="Computer Science">Computer Science</option>
                    <option value="Mathematics">Mathematics</option>
                    <option value="Physics">Physics</option>
                    <option value="Chemistry">Chemistry</option>
                    <option value="Biology">Biology</option>
                `;
            } else if (course === 'BCA' || course === 'MCA') {
                streamSelect.innerHTML += `
                    <option value="Computer Science">Computer Science</option>
                    <option value="Information Technology">Information Technology</option>
                `;
            }
        });

        // Set up course and stream options based on qualification for edit form
        document.getElementById('edit_qualification').addEventListener('change', function() {
            const qualification = this.value;
            const courseSelect = document.getElementById('edit_course');
            const streamSelect = document.getElementById('edit_stream');
            
            // Reset options
            courseSelect.innerHTML = '<option value="">Select Course</option>';
            streamSelect.innerHTML = '<option value="">Select Stream</option>';
            
            if (qualification === 'Graduation') {
                courseSelect.innerHTML += `
                    <option value="B.Tech">B.Tech</option>
                    <option value="B.E">B.E</option>
                    <option value="B.Sc">B.Sc</option>
                    <option value="B.Com">B.Com</option>
                    <option value="BCA">BCA</option>
                    <option value="BA">BA</option>
                    <option value="BBA">BBA</option>
                `;
            } else if (qualification === 'Post Graduation') {
                courseSelect.innerHTML += `
                    <option value="M.Tech">M.Tech</option>
                    <option value="MCA">MCA</option>
                    <option value="M.Sc">M.Sc</option>
                    <option value="MBA">MBA</option>
                    <option value="MA">MA</option>
                `;
            } else if (qualification === 'Diploma') {
                courseSelect.innerHTML += `
                    <option value="Diploma">Diploma</option>
                `;
            } else if (qualification === 'ITI') {
                courseSelect.innerHTML += `
                    <option value="ITI">ITI</option>
                `;
            }
        });
        
        // Auto-fill stream based on course for edit form
        document.getElementById('edit_course').addEventListener('change', function() {
            const course = this.value;
            const streamSelect = document.getElementById('edit_stream');
            
            streamSelect.innerHTML = '<option value="">Select Stream</option>';
            
            if (course === 'B.Tech' || course === 'B.E' || course === 'M.Tech') {
                streamSelect.innerHTML += `
                    <option value="Computer Science">Computer Science</option>
                    <option value="Information Science">Information Science</option>
                    <option value="Electronics">Electronics</option>
                    <option value="Mechanical">Mechanical</option>
                    <option value="Civil">Civil</option>
                    <option value="Electrical">Electrical</option>
                    <option value="Chemical">Chemical</option>
                    <option value="Biotechnology">Biotechnology</option>
                `;
            } else if (course === 'B.Sc' || course === 'M.Sc') {
                streamSelect.innerHTML += `
                    <option value="Computer Science">Computer Science</option>
                    <option value="Mathematics">Mathematics</option>
                    <option value="Physics">Physics</option>
                    <option value="Chemistry">Chemistry</option>
                    <option value="Biology">Biology</option>
                `;
            } else if (course === 'BCA' || course === 'MCA') {
                streamSelect.innerHTML += `
                    <option value="Computer Science">Computer Science</option>
                    <option value="Information Technology">Information Technology</option>
                `;
            }
        });
    </script>
</body>
</html>