<?php
// track_profile_view.php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'employer'){
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if(isset($_GET['application_id'])) {
    $application_id = (int)$_GET['application_id'];
    $employer_id = $_SESSION['user'];
    
    // Get employer's company ID first
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
        
        // Verify the application belongs to the employer's company
        $verify_sql = "SELECT a.id FROM applications a 
                       JOIN job_openings j ON a.job_id = j.id 
                       WHERE a.id = ? AND j.company_id = ?";
        $stmt_verify = mysqli_prepare($conn, $verify_sql);
        mysqli_stmt_bind_param($stmt_verify, "ii", $application_id, $company_id);
        mysqli_stmt_execute($stmt_verify);
        $verify_result = mysqli_stmt_get_result($stmt_verify);
        
        if (mysqli_fetch_assoc($verify_result)) {
            // Record the profile view - INSERT IGNORE to avoid duplicates
            $view_sql = "INSERT IGNORE INTO application_views (application_id, employer_id, view_type) 
                         VALUES (?, ?, 'profile')";
            $stmt_view = mysqli_prepare($conn, $view_sql);
            mysqli_stmt_bind_param($stmt_view, "ii", $application_id, $employer_id);
            
            if (mysqli_stmt_execute($stmt_view)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Application not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Company not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No application ID']);
}
?>