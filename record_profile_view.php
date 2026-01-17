<?php
session_start();
require_once 'config.php';

// Check if user is an employer
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'employer') {
    exit; 
}

if (isset($_POST['candidate_id']) && isset($_POST['view_type'])) {
    
    $employer_id = (int)$_SESSION['user']; // employer's users.id
    $candidate_registration_id = (int)$_POST['candidate_id'];
    $view_type = $_POST['view_type']; // 'profile'

    if ($view_type !== 'profile' && $view_type !== 'resume') {
        exit; // Invalid type
    }

    // 1. Get candidate's email from their registration ID
    $sql_email = "SELECT email FROM registrations WHERE id = ?";
    $stmt_email = mysqli_prepare($conn, $sql_email);
    mysqli_stmt_bind_param($stmt_email, "i", $candidate_registration_id);
    mysqli_stmt_execute($stmt_email);
    $result_email = mysqli_stmt_get_result($stmt_email);
    
    if ($row_email = mysqli_fetch_assoc($result_email)) {
        $candidate_email = $row_email['email'];
        
        // 2. Log the view in the new 'profile_views' table
        // We use ON DUPLICATE KEY UPDATE to simply update the timestamp
        // if this employer views the same profile again.
        $sql_log = "INSERT INTO profile_views (candidate_email, employer_id, view_type, viewed_at) 
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE viewed_at = NOW()";
        
        $stmt_log = mysqli_prepare($conn, $sql_log);
        mysqli_stmt_bind_param($stmt_log, "sis", $candidate_email, $employer_id, $view_type);
        mysqli_stmt_execute($stmt_log);
        
        echo "View logged."; // Success
    }
}
?>