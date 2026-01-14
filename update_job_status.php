<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'employee'){
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$user_id = $_SESSION['user'];
$status = $_POST['job_status'] ?? null;

// Get user email
$sql_user = "SELECT email FROM users WHERE id = ?";
$stmt_user = mysqli_prepare($conn, $sql_user);
mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
$result_user = mysqli_stmt_get_result($stmt_user);
$user = mysqli_fetch_assoc($result_user);

if (!$user) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$email = $user['email'];

// Validate status
$allowed_statuses = [
    'actively_searching', 'preparing_for_interviews', 'appearing_for_interviews',
    'received_a_job_offer', 'casually_exploring', 'not_looking'
];

header('Content-Type: application/json');
if ($status && in_array($status, $allowed_statuses)) {
    // Check if a registration record exists
    $check_sql = "SELECT id FROM registrations WHERE email = ?";
    $stmt_check = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt_check, "s", $email);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result_check) > 0) {
        $sql = "UPDATE registrations SET job_search_status = ? WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $status, $email);
    } else {
        // If no registration, we can't save this. This should ideally not happen
        // if the dashboard is only for registered users, but as a fallback:
         echo json_encode(['success' => false, 'message' => 'Registration profile not found.']);
         exit;
    }
    
    if (mysqli_stmt_execute($stmt)) {
        $status_text = ucwords(str_replace('_', ' ', $status));
        echo json_encode(['success' => true, 'status_text' => $status_text]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
}
exit;
?>