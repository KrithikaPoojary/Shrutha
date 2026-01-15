<?php
// track_resume_view.php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'employer'){
    header("Location: login.php");
    exit;
}

if(isset($_GET['application_id']) && isset($_GET['resume_path'])) {
    $application_id = (int)$_GET['application_id'];
    $resume_path = $_GET['resume_path'];
    $employer_id = $_SESSION['user'];
    
    // Verify the application belongs to the employer's company
    $company_id = 0;
    $sql_company = "SELECT c.id FROM companies c 
                   JOIN users u ON c.email = u.email 
                   WHERE u.id = ?";
    $stmt_company = mysqli_prepare($conn, $sql_company);
    mysqli_stmt_bind_param($stmt_company, "i", $employer_id);
    mysqli_stmt_execute($stmt_company);
    $result_company = mysqli_stmt_get_result($stmt_company);
    
    if ($row_company = mysqli_fetch_assoc($result_company)) {
        $company_id = $row_company['id'];
        
        $verify_sql = "SELECT a.id FROM applications a 
                       JOIN job_openings j ON a.job_id = j.id 
                       WHERE a.id = ? AND j.company_id = ?";
        $stmt_verify = mysqli_prepare($conn, $verify_sql);
        mysqli_stmt_bind_param($stmt_verify, "ii", $application_id, $company_id);
        mysqli_stmt_execute($stmt_verify);
        $verify_result = mysqli_stmt_get_result($stmt_verify);
        
        if (mysqli_fetch_assoc($verify_result)) {
            // Record the resume view
            $view_sql = "INSERT IGNORE INTO application_views (application_id, employer_id, view_type) 
                         VALUES (?, ?, 'resume')";
            $stmt_view = mysqli_prepare($conn, $view_sql);
            mysqli_stmt_bind_param($stmt_view, "ii", $application_id, $employer_id);
            mysqli_stmt_execute($stmt_view);
        }
    }
    
    // Redirect to the actual resume file
    header("Location: " . $resume_path);
    exit;
} else {
    header("Location: employer_dashboard.php");
    exit;
}
?>