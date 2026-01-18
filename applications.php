<?php
// applications.php
session_start();
require_once 'config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'employer'){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user'];
$page_title = "Manage Applications";

// Check if a specific job_id is provided in the URL
$specific_job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$specific_job_info = null;

// Get employer's company ID
$company_id = 0;
$company_name = '';
$sql_company = "SELECT c.id, c.organization_name 
               FROM companies c 
               JOIN users u ON c.email = u.email 
               WHERE u.id = ?";
$stmt_company = mysqli_prepare($conn, $sql_company);
mysqli_stmt_bind_param($stmt_company, "i", $user_id);
mysqli_stmt_execute($stmt_company);
$result_company = mysqli_stmt_get_result($stmt_company);
if ($row_company = mysqli_fetch_assoc($result_company)) {
    $company_id = $row_company['id'];
    $company_name = $row_company['organization_name'];
}

// If a specific job_id is provided, verify it belongs to the employer and get job details
if ($specific_job_id > 0) {
    $sql_job = "SELECT j.job_designation, j.job_location 
                FROM job_openings j 
                WHERE j.id = ? AND j.company_id = ?";
    $stmt_job = mysqli_prepare($conn, $sql_job);
    mysqli_stmt_bind_param($stmt_job, "ii", $specific_job_id, $company_id);
    mysqli_stmt_execute($stmt_job);
    $result_job = mysqli_stmt_get_result($stmt_job);
    $specific_job_info = mysqli_fetch_assoc($result_job);
    
    // If job doesn't exist or doesn't belong to employer, reset the job_id
    if (!$specific_job_info) {
        $specific_job_id = 0;
    }
}

if (isset($_GET['view_application'])) {
    $application_id = (int)$_GET['view_application'];
    
    // Get employer's company ID first
    $company_id = 0;
    $sql_company = "SELECT c.id FROM companies c 
                   JOIN users u ON c.email = u.email 
                   WHERE u.id = ?";
    $stmt_company = mysqli_prepare($conn, $sql_company);
    mysqli_stmt_bind_param($stmt_company, "i", $user_id);
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
            mysqli_stmt_bind_param($stmt_view, "ii", $application_id, $user_id);
            mysqli_stmt_execute($stmt_view);
        }
    }
}

// Track resume downloads
if (isset($_GET['download_resume']) && isset($_GET['application_id'])) {
    $application_id = (int)$_GET['application_id'];
    
    // Get employer's company ID first
    $company_id = 0;
    $sql_company = "SELECT c.id FROM companies c 
                   JOIN users u ON c.email = u.email 
                   WHERE u.id = ?";
    $stmt_company = mysqli_prepare($conn, $sql_company);
    mysqli_stmt_bind_param($stmt_company, "i", $user_id);
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
            // Record the resume view - INSERT IGNORE to avoid duplicates
            $view_sql = "INSERT IGNORE INTO application_views (application_id, employer_id, view_type) 
                         VALUES (?, ?, 'resume')";
            $stmt_view = mysqli_prepare($conn, $view_sql);
            mysqli_stmt_bind_param($stmt_view, "ii", $application_id, $user_id);
            mysqli_stmt_execute($stmt_view);
        }
    }
}

// Handle application status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // Get the application_id and new status from the form
    $application_id = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;
    $new_status = isset($_POST['update_status']) ? $_POST['update_status'] : '';
    
    // Validate the status value
    $allowed_statuses = ['Submitted', 'Under Review', 'Shortlisted', 'Hired', 'Rejected'];
    if (!in_array($new_status, $allowed_statuses)) {
        $_SESSION['error'] = "Invalid status value!";
        header("Location: applications.php" . ($specific_job_id > 0 ? "?job_id=" . $specific_job_id : ""));
        exit;
    }
    
    // Update the application status
    $sql = "UPDATE applications SET status = ? WHERE id = ? 
            AND job_id IN (SELECT id FROM job_openings WHERE company_id = ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sii", $new_status, $application_id, $company_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Application status updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating application status: " . mysqli_error($conn);
    }
    
    // Redirect to prevent form resubmission
    header("Location: applications.php" . ($specific_job_id > 0 ? "?job_id=" . $specific_job_id : ""));
    exit;
}

// Handle interview scheduling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_interview'])) {
    $application_id = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;
    $interview_date = isset($_POST['interview_date']) ? $_POST['interview_date'] : '';
    $interview_time = isset($_POST['interview_time']) ? $_POST['interview_time'] : '';
    $position = isset($_POST['position']) ? $_POST['position'] : '';
    
    // Validate required fields
    if (empty($interview_date) || empty($interview_time) || empty($position)) {
        $_SESSION['error'] = "Please fill in all required fields!";
        header("Location: applications.php" . ($specific_job_id > 0 ? "?job_id=" . $specific_job_id : ""));
        exit;
    }
    
    // Get user_id from application
    $sql_user = "SELECT a.name, a.email, j.job_designation 
                FROM applications a 
                JOIN job_openings j ON a.job_id = j.id 
                WHERE a.id = ?";
    $stmt_user = mysqli_prepare($conn, $sql_user);
    mysqli_stmt_bind_param($stmt_user, "i", $application_id);
    mysqli_stmt_execute($stmt_user);
    $result_user = mysqli_stmt_get_result($stmt_user);
    $application_data = mysqli_fetch_assoc($result_user);
    
    if ($application_data) {
        // Get company contact information
        $sql_company_contact = "SELECT c.contact_person, c.email as company_email, c.mobile, c.landline, c.address 
                               FROM companies c 
                               WHERE c.id = ?";
        $stmt_company_contact = mysqli_prepare($conn, $sql_company_contact);
        mysqli_stmt_bind_param($stmt_company_contact, "i", $company_id);
        mysqli_stmt_execute($stmt_company_contact);
        $result_company_contact = mysqli_stmt_get_result($stmt_company_contact);
        $company_contact = mysqli_fetch_assoc($result_company_contact);
        
        // Create interview record
        $interview_datetime = $interview_date . ' ' . $interview_time;
        $sql_interview = "INSERT INTO interviews (user_id, company, position, interview_time, status) 
                         VALUES ((SELECT id FROM users WHERE email = ?), ?, ?, ?, 'scheduled')";
        $stmt_interview = mysqli_prepare($conn, $sql_interview);
        mysqli_stmt_bind_param($stmt_interview, "ssss", $application_data['email'], $company_name, $position, $interview_datetime);
        
        if (mysqli_stmt_execute($stmt_interview)) {
            $interview_id = mysqli_insert_id($conn);
            
            // Update application status to 'Under Review'
            $sql_update = "UPDATE applications SET status = 'Under Review' WHERE id = ?";
            $stmt_update = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt_update, "i", $application_id);
            mysqli_stmt_execute($stmt_update);
            
            // Send email notification to candidate using PHPMailer
            if (sendInterviewEmail(
                $application_data['email'], 
                $application_data['name'], 
                $position, 
                $company_name, 
                $interview_datetime,
                $company_contact,
                $interview_id
            )) {
                $_SESSION['success'] = "Interview scheduled successfully and email notification sent!";
            } else {
                $_SESSION['success'] = "Interview scheduled successfully, but email notification failed to send.";
            }
        } else {
            $_SESSION['error'] = "Error scheduling interview: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "Application not found!";
    }
    
    // Redirect to prevent form resubmission
    header("Location: applications.php" . ($specific_job_id > 0 ? "?job_id=" . $specific_job_id : ""));
    exit;
}

// Handle interview cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_interview'])) {
    $interview_id = isset($_POST['interview_id']) ? (int)$_POST['interview_id'] : 0;
    $application_id = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;
    $cancellation_reason = isset($_POST['cancellation_reason']) ? trim($_POST['cancellation_reason']) : '';
    
    if (empty($cancellation_reason)) {
        $_SESSION['error'] = "Please provide a reason for cancellation!";
        header("Location: applications.php" . ($specific_job_id > 0 ? "?job_id=" . $specific_job_id : ""));
        exit;
    }
    
    // Get interview and candidate details
    $sql_interview = "SELECT i.*, a.name, a.email, a.job_id, j.job_designation 
                     FROM interviews i 
                     JOIN users u ON i.user_id = u.id 
                     JOIN applications a ON u.email = a.email 
                     JOIN job_openings j ON a.job_id = j.id 
                     WHERE i.id = ? AND j.company_id = ? AND a.id = ?";
    $stmt_interview = mysqli_prepare($conn, $sql_interview);
    mysqli_stmt_bind_param($stmt_interview, "iii", $interview_id, $company_id, $application_id);
    mysqli_stmt_execute($stmt_interview);
    $result_interview = mysqli_stmt_get_result($stmt_interview);
    $interview_data = mysqli_fetch_assoc($result_interview);
    
    if ($interview_data) {
        // Get company contact information
        $sql_company_contact = "SELECT c.contact_person, c.email as company_email, c.mobile, c.landline, c.address 
                               FROM companies c 
                               WHERE c.id = ?";
        $stmt_company_contact = mysqli_prepare($conn, $sql_company_contact);
        mysqli_stmt_bind_param($stmt_company_contact, "i", $company_id);
        mysqli_stmt_execute($stmt_company_contact);
        $result_company_contact = mysqli_stmt_get_result($stmt_company_contact);
        $company_contact = mysqli_fetch_assoc($result_company_contact);
        
        // Update interview status to cancelled
        $sql_cancel = "UPDATE interviews SET status = 'cancelled', cancellation_reason = ? WHERE id = ?";
        $stmt_cancel = mysqli_prepare($conn, $sql_cancel);
        mysqli_stmt_bind_param($stmt_cancel, "si", $cancellation_reason, $interview_id);
        
        if (mysqli_stmt_execute($stmt_cancel)) {
            // Update application status back to 'Under Review'
            $sql_update = "UPDATE applications SET status = 'Under Review' WHERE id = ?";
            $stmt_update = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt_update, "i", $application_id);
            mysqli_stmt_execute($stmt_update);
            
            // Send cancellation email
            if (sendCancellationEmail(
                $interview_data['email'],
                $interview_data['name'],
                $interview_data['position'],
                $interview_data['company'],
                $interview_data['interview_time'],
                $company_contact,
                $cancellation_reason
            )) {
                $_SESSION['success'] = "Interview cancelled successfully and cancellation email sent!";
            } else {
                $_SESSION['success'] = "Interview cancelled successfully, but cancellation email failed to send.";
            }
        } else {
            $_SESSION['error'] = "Error cancelling interview: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "Interview not found or you don't have permission to cancel it!";
    }
    
    // Redirect to prevent form resubmission
    header("Location: applications.php" . ($specific_job_id > 0 ? "?job_id=" . $specific_job_id : ""));
    exit;
}

// Function to send interview email using PHPMailer
function sendInterviewEmail($candidate_email, $candidate_name, $position, $company_name, $interview_datetime, $company_contact, $interview_id) {
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'shruthaportal@gmail.com'; // Your email
        $mail->Password = 'sttt vkri eeug bxxq'; // Your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('shruthaportal@gmail.com', 'Shrutha Portal');
        $mail->addAddress($candidate_email, $candidate_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Interview Scheduled - ' . $company_name;
        
        $formatted_date = date('F j, Y', strtotime($interview_datetime));
        $formatted_time = date('h:i A', strtotime($interview_datetime));
        
        // Prepare contact information
        $contact_person = $company_contact['contact_person'] ?? 'HR Department';
        $company_email = $company_contact['company_email'] ?? $company_contact['email'] ?? 'hr@company.com';
        $company_phone = $company_contact['mobile'] ?? $company_contact['landline'] ?? 'Not specified';
        $company_address = $company_contact['address'] ?? 'Not specified';
        
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f9f9f9; }
                    .details { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #007bff; }
                    .contact-info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #28a745; }
                    .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                    .important { color: #dc3545; font-weight: bold; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Interview Scheduled</h1>
                    </div>
                    <div class='content'>
                        <p>Dear <strong>$candidate_name</strong>,</p>
                        
                        <p>Thank you for your application. We are pleased to invite you for an interview for the position of <strong>$position</strong> at <strong>$company_name</strong>.</p>
                        
                        <div class='details'>
                            <h3><i class='fas fa-calendar-alt'></i> Interview Details:</h3>
                            <p><strong>Position:</strong> $position</p>
                            <p><strong>Company:</strong> $company_name</p>
                            <p><strong>Date:</strong> $formatted_date</p>
                            <p><strong>Time:</strong> $formatted_time</p>
                        </div>
                        
                        <div class='contact-info'>
                            <h3><i class='fas fa-phone'></i> Contact Information:</h3>
                            <p><strong>Contact Person:</strong> $contact_person</p>
                            <p><strong>Email:</strong> $company_email</p>
                            <p><strong>Phone:</strong> $company_phone</p>
                            <p><strong>Address:</strong> $company_address</p>
                        </div>
                        
                        <p class='important'><i class='fas fa-lightbulb'></i> Important Notes:</p>
                        <ul>
                            <li>Please arrive 10-15 minutes before the scheduled time</li>
                            <li>Bring copies of your resume, educational certificates, and ID proof</li>
                            <li>Be prepared to discuss your experience, skills, and portfolio</li>
                            <li>Dress professionally for the interview</li>
                        </ul>
                        
                        <p><strong>Need to reschedule?</strong><br>
                        If you need to reschedule the interview, please contact us at least 24 hours in advance using the contact information provided above.</p>
                        
                        <p>We look forward to meeting you!</p>
                        
                        <p>Best regards,<br>
                        <strong>$contact_person</strong><br>
                        <strong>$company_name</strong> Hiring Team</p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message. Please do not reply to this email directly.<br>
                        For any queries, use the contact information provided above.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        // Plain text version for non-HTML email clients
        $mail->AltBody = "
            Interview Scheduled - $company_name
            
            Dear $candidate_name,
            
            Thank you for your application. We are pleased to invite you for an interview for the position of $position at $company_name.
            
            INTERVIEW DETAILS:
            Position: $position
            Company: $company_name
            Date: $formatted_date
            Time: $formatted_time
            
            CONTACT INFORMATION:
            Contact Person: $contact_person
            Email: $company_email
            Phone: $company_phone
            Address: $company_address
            
            IMPORTANT NOTES:
            - Please arrive 10-15 minutes before the scheduled time
            - Bring copies of your resume, educational certificates, and ID proof
            - Be prepared to discuss your experience, skills, and portfolio
            - Dress professionally for the interview
            
            NEED TO RESCHEDULE?
            If you need to reschedule the interview, please contact us at least 24 hours in advance using the contact information provided above.
            
            We look forward to meeting you!
            
            Best regards,
            $contact_person
            $company_name Hiring Team
            
            ---
            This is an automated message. Please do not reply to this email directly.
            For any queries, use the contact information provided above.
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Function to send cancellation email using PHPMailer
function sendCancellationEmail($candidate_email, $candidate_name, $position, $company_name, $interview_datetime, $company_contact, $cancellation_reason) {
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'shruthaportal@gmail.com';
        $mail->Password = 'sttt vkri eeug bxxq';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('shruthaportal@gmail.com', 'Shrutha Portal');
        $mail->addAddress($candidate_email, $candidate_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Interview Cancelled - ' . $company_name;
        
        $formatted_date = date('F j, Y', strtotime($interview_datetime));
        $formatted_time = date('h:i A', strtotime($interview_datetime));
        
        // Prepare contact information
        $contact_person = $company_contact['contact_person'] ?? 'HR Department';
        $company_email = $company_contact['company_email'] ?? $company_contact['email'] ?? 'hr@company.com';
        $company_phone = $company_contact['mobile'] ?? $company_contact['landline'] ?? 'Not specified';
        
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f9f9f9; }
                    .cancellation-info { background: #ffe6e6; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #dc3545; }
                    .contact-info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #28a745; }
                    .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Interview Cancelled</h1>
                    </div>
                    <div class='content'>
                        <p>Dear <strong>$candidate_name</strong>,</p>
                        
                        <p>We regret to inform you that your scheduled interview has been cancelled.</p>
                        
                        <div class='cancellation-info'>
                            <h3><i class='fas fa-exclamation-triangle'></i> Cancelled Interview Details:</h3>
                            <p><strong>Position:</strong> $position</p>
                            <p><strong>Company:</strong> $company_name</p>
                            <p><strong>Original Date:</strong> $formatted_date</p>
                            <p><strong>Original Time:</strong> $formatted_time</p>
                            <p><strong>Reason for Cancellation:</strong> $cancellation_reason</p>
                        </div>
                        
                        <div class='contact-info'>
                            <h3><i class='fas fa-phone'></i> Contact Information:</h3>
                            <p><strong>Contact Person:</strong> $contact_person</p>
                            <p><strong>Email:</strong> $company_email</p>
                            <p><strong>Phone:</strong> $company_phone</p>
                        </div>
                        
                        <p>We apologize for any inconvenience this may cause. We will contact you shortly if we are able to reschedule the interview.</p>
                        
                        <p>If you have any questions, please don't hesitate to contact us using the information above.</p>
                        
                        <p>Best regards,<br>
                        <strong>$contact_person</strong><br>
                        <strong>$company_name</strong> Hiring Team</p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message. Please do not reply to this email directly.<br>
                        For any queries, use the contact information provided above.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        // Plain text version for non-HTML email clients
        $mail->AltBody = "
            Interview Cancelled - $company_name
            
            Dear $candidate_name,
            
            We regret to inform you that your scheduled interview has been cancelled.
            
            CANCELLED INTERVIEW DETAILS:
            Position: $position
            Company: $company_name
            Original Date: $formatted_date
            Original Time: $formatted_time
            Reason for Cancellation: $cancellation_reason
            
            CONTACT INFORMATION:
            Contact Person: $contact_person
            Email: $company_email
            Phone: $company_phone
            
            We apologize for any inconvenience this may cause. We will contact you shortly if we are able to reschedule the interview.
            
            If you have any questions, please don't hesitate to contact us using the information above.
            
            Best regards,
            $contact_person
            $company_name Hiring Team
            
            ---
            This is an automated message. Please do not reply to this email directly.
            For any queries, use the contact information provided above.
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Cancellation email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Fetch applications for this employer's company
$applications = [];
$sql = "SELECT a.*, j.job_designation, j.job_location, c.organization_name,
        (SELECT id FROM interviews 
         WHERE user_id = (SELECT id FROM users WHERE email = a.email) 
         AND company = c.organization_name 
         ORDER BY interview_time DESC LIMIT 1) as interview_id,
        (SELECT interview_time FROM interviews 
         WHERE user_id = (SELECT id FROM users WHERE email = a.email) 
         AND company = c.organization_name 
         ORDER BY interview_time DESC LIMIT 1) as interview_time,
        (SELECT status FROM interviews 
         WHERE user_id = (SELECT id FROM users WHERE email = a.email) 
         AND company = c.organization_name 
         ORDER BY interview_time DESC LIMIT 1) as interview_status,
        (SELECT cancellation_reason FROM interviews 
         WHERE user_id = (SELECT id FROM users WHERE email = a.email) 
         AND company = c.organization_name 
         ORDER BY interview_time DESC LIMIT 1) as cancellation_reason
        FROM applications a
        JOIN job_openings j ON a.job_id = j.id
        JOIN companies c ON j.company_id = c.id
        WHERE j.company_id = ?";

// Add job filter if a specific job is selected
if ($specific_job_id > 0) {
    $sql .= " AND j.id = ?";
    $sql .= " ORDER BY a.applied_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $company_id, $specific_job_id);
} else {
    $sql .= " ORDER BY a.applied_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $company_id);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $applications[] = $row;
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
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }
        .application-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        .application-card:hover {
            position: relative; /* Required for the 'top' property to work */
            top: -2px;          /* Creates the same "lift" effect as transform */
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }   
        .status-submitted { border-left-color: #ffc107; }
        .status-under-review { border-left-color: #17a2b8; }
        .status-shortlisted { border-left-color: #007bff; }
        .status-hired { border-left-color: #28a745; }
        .status-rejected { border-left-color: #dc3545; }
        
        .filter-buttons .btn-group .btn {
            border-radius: 20px;
            margin: 0 2px;
        }
        
        .candidate-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #f0f5ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #3a0ca3;
            font-size: 1.1rem;
        }
        
        .action-buttons .btn {
            margin: 2px;
        }
        
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="employer_dashboard.php">
                <i class="fas fa-building me-2"></i>Employer Portal
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="employer_dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                <a class="nav-link active" href="applications.php"><i class="fas fa-file-alt me-1"></i> Applications</a>
                <a class="nav-link" href="job_postings.php"><i class="fas fa-briefcase me-1"></i> Jobs</a>
                <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="h2">
                    <?php if ($specific_job_id > 0): ?>
                        Applications for: <?php echo htmlspecialchars($specific_job_info['job_designation']); ?>
                        <small class="text-muted d-block"><?php echo htmlspecialchars($specific_job_info['job_location']); ?></small>
                    <?php else: ?>
                        Manage Job Applications
                    <?php endif; ?>
                </h1>
                <p class="text-muted">
                    <?php if ($specific_job_id > 0): ?>
                        Review candidates who applied for this specific position
                    <?php else: ?>
                        Review, accept, reject candidates and schedule interviews
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-auto">
                <?php if ($specific_job_id > 0): ?>
                    <a href="applications.php" class="btn btn-outline-primary me-2">
                        <i class="fas fa-list me-2"></i>View All Applications
                    </a>
                <?php endif; ?>
                <a href="employer_dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Applications List -->
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                 <h5 class="mb-0">
                    <?php if ($specific_job_id > 0): ?>
                        Job Applications (<?= count($applications) ?>)
                    <?php else: ?>
                        All Applications (<?= count($applications) ?>)
                    <?php endif; ?>
                </h5>
                <div class="filter-buttons">
                    <div class="btn-group">
                        <button class="btn btn-outline-primary btn-sm active" data-filter="all">All</button>
                        <button class="btn btn-outline-warning btn-sm" data-filter="Submitted">New</button>
                        <button class="btn btn-outline-info btn-sm" data-filter="Under Review">Review</button>
                        <button class="btn btn-outline-success btn-sm" data-filter="Hired">Hired</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($applications)): ?>
                    <div class="row">
                        <?php foreach ($applications as $application): 
                            $status_class = strtolower(str_replace(' ', '-', $application['status']));
                        ?>
                            <div class="col-lg-6 mb-3 application-item" data-status="<?= $application['status'] ?>">
                                <div class="card application-card status-<?= $status_class ?> h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="candidate-avatar me-3">
                                                    <?php 
                                                    $name_parts = explode(' ', $application['name']);
                                                    if (count($name_parts) >= 2) {
                                                        echo strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[count($name_parts)-1], 0, 1));
                                                    } else {
                                                        echo strtoupper(substr($application['name'], 0, 2));
                                                    }
                                                    ?>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($application['name']) ?></h6>
                                                    <small class="text-muted"><?= htmlspecialchars($application['email']) ?></small>
                                                </div>
                                            </div>
                                            <span class="badge status-badge 
                                                <?= $application['status'] == 'Submitted' ? 'bg-warning' : '' ?>
                                                <?= $application['status'] == 'Under Review' ? 'bg-info' : '' ?>
                                                <?= $application['status'] == 'Shortlisted' ? 'bg-primary' : '' ?>
                                                <?= $application['status'] == 'Hired' ? 'bg-success' : '' ?>
                                                <?= $application['status'] == 'Rejected' ? 'bg-danger' : '' ?>">
                                                <?= $application['status'] ?>
                                            </span>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <strong>Position:</strong> <?= htmlspecialchars($application['job_designation']) ?>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <strong>Experience:</strong> <?= htmlspecialchars($application['experience']) ?>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <strong>Skills:</strong> 
                                            <span class="text-muted"><?= htmlspecialchars($application['skills']) ?></span>
                                        </div>
                                        
                                        <?php if ($application['interview_time'] && $application['interview_status'] == 'scheduled'): ?>
                                            <?php 
                                            $interview_timestamp = strtotime($application['interview_time']);
                                            $current_timestamp = time();
                                            $is_upcoming_interview = $interview_timestamp > $current_timestamp;
                                            ?>
                                            <div class="mb-2 <?= $is_upcoming_interview ? 'text-info' : 'text-muted' ?>">
                                                <i class="fas fa-calendar-check me-1"></i>
                                                <strong>Interview:</strong> 
                                                <?= date('M d, Y h:i A', $interview_timestamp) ?>
                                                <?php if (!$is_upcoming_interview): ?>
                                                    <span class="badge bg-secondary ms-1">Completed</span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary ms-1">Upcoming</span>
                                                    <!-- Cancel Interview Button -->
                                                    <button class="btn btn-sm btn-outline-danger ms-2" data-bs-toggle="modal" 
                                                            data-bs-target="#cancelModal<?= $application['id'] ?>">
                                                        <i class="fas fa-times"></i> Cancel Interview
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($application['interview_time'] && $application['interview_status'] == 'cancelled'): ?>
                                            <div class="mb-2 text-danger">
                                                <i class="fas fa-times-circle me-1"></i>
                                                <strong>Interview Cancelled:</strong> 
                                                <?= date('M d, Y h:i A', strtotime($application['interview_time'])) ?>
                                                <br>
                                                <small><strong>Reason:</strong> <?= htmlspecialchars($application['cancellation_reason']) ?></small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                Applied: <?= date('M d, Y', strtotime($application['applied_at'])) ?>
                                            </small>
                                            
                                            <div class="action-buttons">
                                                <!-- View Application Details -->
                                                <button class="btn btn-sm btn-outline-primary view-profile-btn" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#viewModal<?= $application['id'] ?>"
                                                        data-application-id="<?= $application['id'] ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <!-- Status Update Dropdown -->
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                            data-bs-toggle="dropdown">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><h6 class="dropdown-header">Update Status</h6></li>
                                                        <li>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="application_id" value="<?= $application['id'] ?>">
                                                                <button type="submit" name="update_status" value="Under Review" 
                                                                        class="dropdown-item <?= $application['status'] == 'Under Review' ? 'active' : '' ?>">
                                                                    Under Review
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="application_id" value="<?= $application['id'] ?>">
                                                                <button type="submit" name="update_status" value="Shortlisted" 
                                                                        class="dropdown-item <?= $application['status'] == 'Shortlisted' ? 'active' : '' ?>">
                                                                    Shortlisted
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="application_id" value="<?= $application['id'] ?>">
                                                                <button type="submit" name="update_status" value="Hired" 
                                                                        class="dropdown-item text-success <?= $application['status'] == 'Hired' ? 'active' : '' ?>">
                                                                    Hire
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="application_id" value="<?= $application['id'] ?>">
                                                                <button type="submit" name="update_status" value="Rejected" 
                                                                        class="dropdown-item text-danger <?= $application['status'] == 'Rejected' ? 'active' : '' ?>">
                                                                    Reject
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                                
                                                <!-- Schedule Interview Button -->
                                                <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" 
                                                        data-bs-target="#interviewModal<?= $application['id'] ?>"
                                                        <?= $application['status'] == 'Rejected' ? 'disabled' : '' ?>>
                                                    <i class="fas fa-calendar-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- View Application Modal -->
                            <div class="modal fade" id="viewModal<?= $application['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Application Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6>Personal Information</h6>
                                                    <p><strong>Name:</strong> <?= htmlspecialchars($application['name']) ?></p>
                                                    <p><strong>Email:</strong> <?= htmlspecialchars($application['email']) ?></p>
                                                    <p><strong>Phone:</strong> <?= htmlspecialchars($application['phone']) ?></p>
                                                    <p><strong>Address:</strong> <?= htmlspecialchars($application['address']) ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6>Professional Information</h6>
                                                    <p><strong>Position:</strong> <?= htmlspecialchars($application['job_designation']) ?></p>
                                                    <p><strong>Experience:</strong> <?= htmlspecialchars($application['experience']) ?></p>
                                                    <p><strong>Current Company:</strong> <?= htmlspecialchars($application['current_company'] ?? 'N/A') ?></p>
                                                    <p><strong>Current Position:</strong> <?= htmlspecialchars($application['current_position'] ?? 'N/A') ?></p>
                                                </div>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <h6>Skills & Education</h6>
                                                    <p><strong>Skills:</strong> <?= htmlspecialchars($application['skills']) ?></p>
                                                    <p><strong>Education:</strong> <?= htmlspecialchars($application['education']) ?></p>
                                                    <?php if ($application['linkedin']): ?>
                                                        <p><strong>LinkedIn:</strong> <a href="<?= htmlspecialchars($application['linkedin']) ?>" target="_blank">View Profile</a></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php if ($application['cover_letter']): ?>
                                                <div class="row mt-3">
                                                    <div class="col-12">
                                                        <h6>Cover Letter</h6>
                                                        <p><?= nl2br(htmlspecialchars($application['cover_letter'])) ?></p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($application['resume_path']): ?>
                                                <div class="row mt-3">
                                                    <div class="col-12">
                                                        <h6>Resume</h6>
                                                        <a href="track_resume_view.php?application_id=<?= $application['id'] ?>&resume_path=<?= urlencode($application['resume_path']) ?>" 
                                                        class="btn btn-outline-primary btn-sm" target="_blank">
                                                            <i class="fas fa-download me-1"></i>Download Resume
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Schedule Interview Modal -->
                            <div class="modal fade" id="interviewModal<?= $application['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Schedule Interview</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="application_id" value="<?= $application['id'] ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Candidate</label>
                                                    <input type="text" class="form-control" value="<?= htmlspecialchars($application['name']) ?>" readonly>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Position</label>
                                                    <input type="text" name="position" class="form-control" value="<?= htmlspecialchars($application['job_designation']) ?>" required>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Interview Date</label>
                                                        <input type="date" name="interview_date" class="form-control" 
                                                               min="<?= date('Y-m-d') ?>" required>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Interview Time</label>
                                                        <input type="time" name="interview_time" class="form-control" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="schedule_interview" class="btn btn-primary">Schedule Interview</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <!-- Cancel Interview Modal -->
                            <div class="modal fade" id="cancelModal<?= $application['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <div class="modal-header">
                                                <h5 class="modal-title text-danger">Cancel Interview</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="interview_id" value="<?= $application['interview_id'] ?>">
                                                <input type="hidden" name="application_id" value="<?= $application['id'] ?>">
                                                
                                                <div class="alert alert-warning">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    Are you sure you want to cancel this interview?
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>Candidate:</strong></label>
                                                    <input type="text" class="form-control" value="<?= htmlspecialchars($application['name']) ?>" readonly>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>Scheduled Interview:</strong></label>
                                                    <input type="text" class="form-control" value="<?= date('M d, Y h:i A', strtotime($application['interview_time'])) ?>" readonly>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>Reason for Cancellation *</strong></label>
                                                    <textarea name="cancellation_reason" class="form-control" rows="3" 
                                                            placeholder="Please provide a reason for cancelling this interview..." required></textarea>
                                                    <small class="text-muted">This reason will be sent to the candidate.</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Interview</button>
                                                <button type="submit" name="cancel_interview" class="btn btn-danger">Cancel Interview</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                        <h4>No Applications Found</h4>
                        <p class="text-muted">You haven't received any job applications yet.</p>
                        <a href="job_postings.php" class="btn btn-primary">Post a Job</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter applications by status
        document.querySelectorAll('.filter-buttons .btn').forEach(button => {
            button.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                
                // Update active button
                document.querySelectorAll('.filter-buttons .btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
                
                // Filter applications
                document.querySelectorAll('.application-item').forEach(item => {
                    if (filter === 'all' || item.getAttribute('data-status') === filter) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    </script>
    <script>
    // Auto-open view modal when view_application parameter is present
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const viewApplicationId = urlParams.get('view_application');
        
        if (viewApplicationId) {
            // Find the modal for this application
            const modalElement = document.getElementById('viewModal' + viewApplicationId);
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
                
                // Update URL to remove the parameter without page reload
                const newUrl = window.location.pathname + window.location.search.replace(/[?&]view_application=[^&]+/, '').replace(/^&/, '?');
                window.history.replaceState({}, document.title, newUrl);
            }
        }
    });
    </script>
    <script>
    // Track profile views when modal is opened
    document.addEventListener('DOMContentLoaded', function() {
        // Add click event to all eye icons
        document.querySelectorAll('.view-profile-btn').forEach(button => {
            button.addEventListener('click', function() {
                const applicationId = this.getAttribute('data-application-id');
                trackProfileView(applicationId);
            });
        });
        
        // Function to track profile view via AJAX
        function trackProfileView(applicationId) {
            fetch('track_profile_view.php?application_id=' + applicationId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Profile view tracked');
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Also track when modal is shown via URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const viewApplicationId = urlParams.get('view_application');
        
        if (viewApplicationId) {
            trackProfileView(viewApplicationId);
        }
    });
    </script>
</body>
</html>