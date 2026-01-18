<?php

session_start();
require_once 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'employee'){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user'];
$page_title = "Employee Dashboard";

// Fetch user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Get user's registration details for profile completeness
$registration_sql = "SELECT * FROM registrations WHERE email = ? ORDER BY id DESC LIMIT 1";
$stmt_reg = mysqli_prepare($conn, $registration_sql);
mysqli_stmt_bind_param($stmt_reg, "s", $user['email']);
mysqli_stmt_execute($stmt_reg);
$registration_result = mysqli_stmt_get_result($stmt_reg);
$registration = mysqli_fetch_assoc($registration_result);

// === NEW: Get Job Search Status ===
$job_status = $registration['job_search_status'] ?? null;
$is_status_set = !empty($job_status);

// Calculate profile completeness based on registration data
$profile_completeness = 0;
$completeness_tasks = []; // NEW: Array for gamification
$has_complete_qualification = false; // Flag for education *percentage*

if ($registration) {
    
    // --- 1. Check Education Completeness (for PERCENTAGE calculation) ---
    // This logic remains the same. If ANY qualification is 100% complete (with marks),
    // they get the points for the main percentage bar.
    
    // Check Degree
    if (!empty($registration['degree_course']) && 
        !empty($registration['degree_stream']) && 
        !empty($registration['degree_year']) && 
        (float)$registration['degree_marks'] > 0) { // <-- FIXED
        $has_complete_qualification = true;
    }

    // Check PG
    if (!$has_complete_qualification && 
        !empty($registration['pg_course']) && 
        !empty($registration['pg_stream']) && 
        !empty($registration['pg_year']) && 
        (float)$registration['pg_marks'] > 0) { // <-- FIXED
        $has_complete_qualification = true;
    }

    // Check Diploma
    if (!$has_complete_qualification && 
        !empty($registration['diploma_course']) && 
        !empty($registration['diploma_stream']) && 
        !empty($registration['diploma_year']) && 
        (float)$registration['diploma_marks'] > 0) { // <-- FIXED
        $has_complete_qualification = true;
    }

    // Check ITI
    if (!$has_complete_qualification && 
        !empty($registration['iti_course']) && 
        !empty($registration['iti_stream']) && 
        !empty($registration['iti_year']) && 
        (float)$registration['iti_marks'] > 0) { // <-- FIXED
        $has_complete_qualification = true;
    }
    
    
    // --- 2. Build the array of missing tasks (NEW SPECIFIC LOGIC) ---
    

    // Check for Personal Details
    if (empty($registration['hometown']) || empty($registration['dob']) || empty($registration['permanent_address']) || empty($registration['pincode'])) {
        $completeness_tasks[] = [
            'text' => 'Add your personal details (DOB, Address...)',
            'points' => '+15%',
            'link' => 'profile.php#personal-details'
        ];
    }

    // === START: New Specific Education Checks ===
    // This will add a specific task for EACH qualification that is "pursuing" or missing marks.
    
    // Check Degree
    if (!empty($registration['degree_course']) && ($registration['degree_pursuing'] == 1)) {
        $completeness_tasks[] = [
            'text' => 'Enter your Degree marks if you completed the course',
            'points' => '+25%',
            'link' => 'profile.php#education-details'
        ];
    }

    // Check PG
    if (!empty($registration['pg_course']) && ($registration['pg_pursuing'] == 1)) {
        $completeness_tasks[] = [
            'text' => 'Enter your Post Grad marks if you completed the course',
            'points' => '+25%',
            'link' => 'profile.php#education-details'
        ];
    }

    // Check Diploma
    if (!empty($registration['diploma_course']) && ($registration['diploma_pursuing'] == 1)) {
        $completeness_tasks[] = [
            'text' => 'Enter your Diploma marks if you completed the course',
            'points' => '+25%',
            'link' => 'profile.php#education-details'
        ];
    }
    
    // Check ITI
    if (!empty($registration['iti_course']) && ($registration['iti_pursuing'] == 1)) {
        $completeness_tasks[] = [
            'text' => 'Enter your ITI marks if you completed the course',
            'points' => '+25%',
            'link' => 'profile.php#education-details'
        ];
    }
    // === END: New Specific Education Checks ===

    if (empty($registration['degree_course']) && empty($registration['pg_course']) && empty($registration['diploma_course']) && empty($registration['iti_course'])) {
        $completeness_tasks[] = [
            'text' => 'Complete your education details',
            'points' => '+25%',
            'link' => 'profile.php#personal-details'
        ];
    }

    // Check for Skills
    if (empty($registration['skills'])) {
        $completeness_tasks[] = [
            'text' => 'Add your skills',
            'points' => '+6%',
            'link' => 'profile.php#skills-details'
        ];
    }

    // Check for Languages
    if (empty($registration['languages'])) {
        $completeness_tasks[] = [
            'text' => 'Add languages you know',
            'points' => '+7%',
            'link' => 'profile.php#skills-details'
        ];
    }

    // Check for missing Resume
    if (empty($registration['resume_path'])) {
        $completeness_tasks[] = [
            'text' => 'Upload your resume',
            'points' => '+6%',
            'link' => 'profile.php#resume-details'
        ];
    }
    
    // --- 3. Calculate the total percentage (using original logic) ---
    // This part remains unchanged. The percentage is based on the single $has_complete_qualification flag.
    $filled_fields = 0;
    $total_fields = 16; 
    
    $important_fields = ['full_name', 'mobile', 'email', 'state', 'district', 
                        'hometown', 'dob', 'permanent_address', 'pincode',
                        'skills', 'languages', 
                        'resume_path'
                       ];
    
    foreach ($important_fields as $field) {
        if (!empty($registration[$field])) {
            $filled_fields++;
        }
    }
    
    // If they have at least one complete qualification, grant the 4 points
    if ($has_complete_qualification) {
        $filled_fields += 4; // Adds points for the education group
    }
    
    $profile_completeness = round(($filled_fields / $total_fields) * 100);
}
// Get job applications count from applications table
$sql = "SELECT COUNT(*) AS count FROM applications WHERE email = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $user['email']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$stats['job_applications'] = $row['count'];

// Get interviews count from interviews table
$sql = "SELECT COUNT(*) AS count FROM interviews WHERE user_id = ? AND status = 'scheduled'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$stats['interviews'] = $row['count'];

// Get resume count from resumes table
$sql = "SELECT COUNT(*) AS count FROM resumes WHERE email = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $user['email']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$stats['resume_created'] = $row['count'];

// --- Get all job IDs that the user has applied for (MOVED UP) ---
$applied_jobs = [];
if ($registration) {
    $applied_sql = "SELECT job_id FROM applications WHERE email = ?";
    $stmt_applied = mysqli_prepare($conn, $applied_sql);
    mysqli_stmt_bind_param($stmt_applied, "s", $user['email']);
    mysqli_stmt_execute($stmt_applied);
    $applied_result = mysqli_stmt_get_result($stmt_applied);
    while ($applied_row = mysqli_fetch_assoc($applied_result)) {
        $applied_jobs[] = $applied_row['job_id'];
    }
}

// --- START: Improved Job Recommendation Logic ---
$job_recommendations = [];
$user_job_title = null; // Initialize user_job_title

// Only run if user is registered
if ($registration) {
    // Get user's job title from resume
    $job_title_sql = "SELECT job_title FROM resumes WHERE email = ? ORDER BY id DESC LIMIT 1";
    $stmt_job_title = mysqli_prepare($conn, $job_title_sql);
    mysqli_stmt_bind_param($stmt_job_title, "s", $user['email']);
    mysqli_stmt_execute($stmt_job_title);
    $job_title_result = mysqli_stmt_get_result($stmt_job_title);
    if($job_title_data = mysqli_fetch_assoc($job_title_result)) {
        $user_job_title = $job_title_data['job_title'];
    }

    // --- NEW: Check job status to decide IF and HOW to query jobs ---
    if ($job_status == 'not_looking' || $job_status == 'received_a_job_offer') {
        $job_recommendations = []; // Don't fetch any jobs
    } else {
        // --- Determine LIMIT based on job status ---
        $limit = 4; // Default for 'actively_searching'
        if ($job_status == 'preparing_for_interviews' || $job_status == 'casually_exploring') {
            $limit = 2;
        } elseif ($job_status == 'appearing_for_interviews') {
            $limit = 1;
        }

        // Get user profile data (skills, courses, streams, experience)
        $user_skills = [];
        $user_courses = [];
        $user_streams = [];
        $user_experience_sql = "jo.exp_from >= 0"; // Default: any experience
        
        $user_skills = !empty($registration['skills']) ? explode(',', $registration['skills']) : [];
        $user_courses = array_filter([$registration['degree_course'], $registration['pg_course']]);
        $user_streams = array_filter([$registration['degree_stream'], $registration['pg_stream']]); // Added streams
        if ($registration['experience'] == 'No') {
            $user_experience_sql = "jo.exp_from = 0";
        } else if ($registration['experience'] == 'Yes') {
             $user_experience_sql = "jo.exp_from > 0";
        }

        $params = [];
        $types = "";

        // Base query
        $job_sql = "SELECT jo.*, c.organization_name, (";

        // 1. Job Title Score (Weight: 3)
        $job_sql .= "(CASE WHEN ? IS NOT NULL AND jo.job_designation LIKE ? THEN 3 ELSE 0 END)";
        $params[] = $user_job_title;
        $params[] = "%" . $user_job_title . "%";
        $types .= "ss";

        // 2. Course Score (Weight: 2)
        if (!empty($user_courses)) {
            $placeholders = implode(',', array_fill(0, count($user_courses), '?'));
            $job_sql .= " + (CASE WHEN jo.course IN ($placeholders) THEN 2 ELSE 0 END)";
            $params = array_merge($params, $user_courses);
            $types .= str_repeat('s', count($user_courses));
        } else {
            $job_sql .= " + 0";
        }

        // 3. Stream Score (Weight: 2)
        if (!empty($user_streams)) {
            $placeholders = implode(',', array_fill(0, count($user_streams), '?'));
            $job_sql .= " + (CASE WHEN jo.stream IN ($placeholders) THEN 2 ELSE 0 END)";
            $params = array_merge($params, $user_streams);
            $types .= str_repeat('s', count($user_streams));
        } else {
            $job_sql .= " + 0";
        }

        // 4. Skills Score (Weight: 1 per skill)
        $skill_scores = [];
        foreach ($user_skills as $skill) {
            $trimmed_skill = trim($skill);
            if (!empty($trimmed_skill)) {
                $skill_scores[] = "(CASE WHEN jo.job_designation LIKE ? OR jo.job_description LIKE ? THEN 1 ELSE 0 END)";
                $params[] = "%" . $trimmed_skill . "%";
                $params[] = "%" . $trimmed_skill . "%";
                $types .= "ss";
            }
        }
        if (!empty($skill_scores)) {
            $job_sql .= " + " . implode(' + ', $skill_scores);
        } else {
            $job_sql .= " + 0";
        }

        // Finalize query
        $job_sql .= " ) AS match_score
                    FROM job_openings jo 
                    JOIN companies c ON jo.company_id = c.id 
                    WHERE jo.vacancies > 0 AND ($user_experience_sql)";

        // Exclude already applied jobs
        if (!empty($applied_jobs)) {
            $placeholders = implode(',', array_fill(0, count($applied_jobs), '?'));
            $job_sql .= " AND jo.id NOT IN ($placeholders)";
            $params = array_merge($params, $applied_jobs);
            $types .= str_repeat('i', count($applied_jobs));
        }

        $job_sql .= " ORDER BY match_score DESC, jo.created_at DESC 
                    LIMIT " . intval($limit); // Append LIMIT directly (safe as it's an int)

        $stmt_job = mysqli_prepare($conn, $job_sql);
        if ($stmt_job) {
             if (!empty($types)) {
                mysqli_stmt_bind_param($stmt_job, $types, ...$params);
             }
             mysqli_stmt_execute($stmt_job);
             $job_result = mysqli_stmt_get_result($stmt_job);
        } else {
            // Fallback in case of query error
            $job_sql_fallback = "SELECT jo.*, c.organization_name 
                                FROM job_openings jo 
                                JOIN companies c ON jo.company_id = c.id 
                                WHERE jo.vacancies > 0 ";
            if (!empty($applied_jobs)) {
                 $placeholders = implode(',', array_fill(0, count($applied_jobs), '?'));
                 $job_sql_fallback .= " AND jo.id NOT IN ($placeholders)";
                 // Note: Fallback query here is simplified and won't bind params,
                 // so we'd need a different approach if we wanted to exclude applied jobs here.
                 // For simplicity, the fallback will just show any jobs.
            }
            $job_sql_fallback .= " ORDER BY jo.created_at DESC LIMIT " . intval($limit);
            $job_result = mysqli_query($conn, $job_sql_fallback);
        }

        while ($row = mysqli_fetch_assoc($job_result)) {
            $job_recommendations[] = $row;
        }
    }
}

// --- END: Improved Job Recommendation Logic ---


// Get upcoming interviews
$upcoming_interviews = [];
$sql = "SELECT * FROM interviews WHERE user_id = ? AND status = 'scheduled' AND interview_time > NOW() ORDER BY interview_time ASC LIMIT 3";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $upcoming_interviews[] = $row;
}

// Get user's photo from resumes table if available
$photo_sql = "SELECT photo_path FROM resumes WHERE email = ? ORDER BY id DESC LIMIT 1";
$stmt_photo = mysqli_prepare($conn, $photo_sql);
mysqli_stmt_bind_param($stmt_photo, "s", $user['email']);
mysqli_stmt_execute($stmt_photo);
$photo_result = mysqli_stmt_get_result($stmt_photo);
$photo_data = mysqli_fetch_assoc($photo_result);
$user_photo = $photo_data ? $photo_data['photo_path'] : null;

// Generate initials from name
$initials = '';
$name_parts = explode(' ', $user['name']);
if(count($name_parts) >= 2) {
    $initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[count($name_parts)-1], 0, 1));
} else {
    $initials = strtoupper(substr($user['name'], 0, 2));
}

// ===================================================
// == START: NOTIFICATION SYSTEM LOGIC
// ===================================================

$notification_jobs = [];
$notification_views = []; // To track viewed notifications
$notification_generic_views = [];
$notification_count = 0;

if ($registration) {
    // Get user profile data for matching
    $user_skills = !empty($registration['skills']) ? explode(',', $registration['skills']) : [];
    $user_courses = array_filter([$registration['degree_course'], $registration['pg_course']]);
    $user_streams = array_filter([$registration['degree_stream'], $registration['pg_stream']]);

    // Get jobs user has already applied for (already fetched as $applied_jobs)
    $applied_job_ids = $applied_jobs;

    // === UPDATED SQL QUERY ===
    // Base query for notifications (Added jo.created_at)
    $sql_notify = "SELECT jo.id, jo.job_designation, jo.job_location, c.organization_name,
                          jo.from_ctc, jo.to_ctc, jo.exp_from, jo.exp_to, jo.created_at
                   FROM job_openings jo 
                   JOIN companies c ON jo.company_id = c.id 
                   WHERE jo.vacancies > 0";
    
    $conditions = [];
    $params = [];
    $types = "";

    // 1. Match Courses
    if (!empty($user_courses)) {
        $placeholders = implode(',', array_fill(0, count($user_courses), '?'));
        $conditions[] = "jo.course IN ($placeholders)";
        $params = array_merge($params, $user_courses);
        $types .= str_repeat('s', count($user_courses));
    }
    
    // 2. Match Streams
    if (!empty($user_streams)) {
        $placeholders = implode(',', array_fill(0, count($user_streams), '?'));
        $conditions[] = "jo.stream IN ($placeholders)";
        $params = array_merge($params, $user_streams);
        $types .= str_repeat('s', count($user_streams));
    }

    // 3. Match Skills (in designation or description)
    $skill_conditions = [];
    foreach ($user_skills as $skill) {
        $trimmed_skill = trim($skill);
        if (!empty($trimmed_skill)) {
            $skill_conditions[] = "jo.job_designation LIKE ?";
            $params[] = "%" . $trimmed_skill . "%";
            $types .= "s";
            $skill_conditions[] = "jo.job_description LIKE ?";
            $params[] = "%" . $trimmed_skill . "%";
            $types .= "s";
        }
    }
    if (!empty($skill_conditions)) {
        $conditions[] = "(" . implode(' OR ', $skill_conditions) . ")";
    }
    
    // 4. Exclude already applied jobs
    if (!empty($applied_job_ids)) {
        $placeholders = implode(',', array_fill(0, count($applied_job_ids), '?'));
        $sql_notify .= " AND jo.id NOT IN ($placeholders)";
        $params = array_merge($params, $applied_job_ids);
        $types .= str_repeat('i', count($applied_job_ids));
    }

    // Combine conditions: must match at least one profile criterion
    if (!empty($conditions)) {
        $sql_notify .= " AND (" . implode(' OR ', $conditions) . ")";
    } else {
        // If no profile criteria, just show new jobs (excluding applied)
        $sql_notify .= ""; 
    }
    
    $sql_notify .= " ORDER BY jo.id DESC LIMIT 10"; // Order by ID DESC to get newest first
    
    $stmt_notify = mysqli_prepare($conn, $sql_notify);
    
    if ($stmt_notify) {
        if (!empty($types)) {
            mysqli_stmt_bind_param($stmt_notify, $types, ...$params);
        }
        mysqli_stmt_execute($stmt_notify);
        $result_notify = mysqli_stmt_get_result($stmt_notify);
        while ($row = mysqli_fetch_assoc($result_notify)) {
            $notification_jobs[] = $row;
        }
        $notification_count = count($notification_jobs);
    }

     // === NEW: Profile/Resume View Notifications ===
    $view_sql = "SELECT av.id as application_view_id, av.view_type, av.viewed_at, c.organization_name, 
                    j.job_designation, a.job_id
                 FROM application_views av
                 JOIN applications a ON av.application_id = a.id
                 JOIN job_openings j ON a.job_id = j.id
                 JOIN companies c ON j.company_id = c.id
                 WHERE a.email = ?
                 ORDER BY av.viewed_at DESC
                 LIMIT 10";
    
    $stmt_views = mysqli_prepare($conn, $view_sql);
    mysqli_stmt_bind_param($stmt_views, "s", $user['email']);
    mysqli_stmt_execute($stmt_views);
    $result_views = mysqli_stmt_get_result($stmt_views);
    
    while ($row = mysqli_fetch_assoc($result_views)) {
        $notification_views[] = $row;
    }

    // === NEW: Generic Profile View Notifications ===
    $generic_view_sql = "SELECT 
                            pv.id as generic_view_id,
                            pv.view_type, 
                            pv.viewed_at,
                            c.organization_name
                         FROM profile_views pv
                         JOIN users u ON pv.employer_id = u.id
                         JOIN companies c ON u.email = c.email
                         WHERE pv.candidate_email = ?
                         ORDER BY pv.viewed_at DESC
                         LIMIT 10";
    
    $stmt_generic_views = mysqli_prepare($conn, $generic_view_sql);
    mysqli_stmt_bind_param($stmt_generic_views, "s", $user['email']);
    mysqli_stmt_execute($stmt_generic_views);
    $result_generic_views = mysqli_stmt_get_result($stmt_generic_views);
    
    while ($row = mysqli_fetch_assoc($result_generic_views)) {
        $notification_generic_views[] = $row;
    }
}

// Combine all types of notifications
$all_notifications = [];

// Add job recommendations (Only if not 'not_looking' or 'received_a_job_offer')
if ($job_status != 'not_looking' && $job_status != 'received_a_job_offer') {
    foreach ($notification_jobs as $job) {
        $all_notifications[] = [
            'type' => 'job_recommendation',
            'data' => $job,
            'timestamp' => $job['created_at'] // Using job creation time
        ];
    }
}

// Add profile/resume views
foreach ($notification_views as $view) {
    $all_notifications[] = [
        'type' => 'application_view', // <-- This now matches the HTML
        'data' => $view,
        'timestamp' => $view['viewed_at']
    ];
}

// === NEW: Add generic profile views ===
foreach ($notification_generic_views as $view) {
    $all_notifications[] = [
        'type' => 'generic_view',
        'data' => $view,
        'timestamp' => $view['viewed_at']
    ];
}

// Sort all notifications by timestamp (newest first)
usort($all_notifications, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Limit to 10 total notifications
$all_notifications = array_slice($all_notifications, 0, 15);
$notification_count = count($all_notifications);

// ===================================================
// == END: NOTIFICATION SYSTEM LOGIC
// ===================================================

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
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #f72585;
            --warning: #f8961e;
            --dark: #2b2d42;
            --light: #f8f9fa;
            --gray: #6c757d;
            --border: #dee2e6;
        }
        
        body {
            background-color: #f5f7fb;
            color: #343a40;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary) 10%, var(--secondary) 100%);
            min-height: 100vh;
            position: fixed;
            width: 255px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.2);
            z-index: 1000;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 3px solid #fff;
        }
        
        .sidebar .nav-link i {
            margin-right: 0.8rem;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .sidebar-logo {
            padding: 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }

        .sidebar-section {
            padding: 1rem 1.5rem 0.5rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            margin-top: 1rem;
        }
        
        .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 20px;
        }
        
        .top-bar {
            background: white;
            padding: 1rem 1.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            border: 2px solid white;
            box-shadow: 0 4px 10px rgba(78, 115, 223, 0.25);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #e74a3b;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 0.75rem;
            display: flex; /* Changed from 'none' to 'flex' */
            align-items: center;
            justify-content: center;
            border: 2px solid white;
        }
        
        .notification-icon {
            position: relative;
            color: var(--gray);
            cursor: pointer;
        }

        .notification-icon:hover {
            color: var(--dark);
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 0.5rem 2rem rgba(58, 59, 69, 0.2);
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: var(--dark);
            font-size: 1.1rem;
        }
        
        .stat-card {
            border-left: 0.35rem solid;
            padding: 1.5rem;
        }
        
        .stat-card.primary {
            border-left-color: var(--primary);
        }
        
        .stat-card.success {
            border-left-color: var(--success);
        }
        
        .stat-card.info {
            border-left-color: var(--info);
        }
        
        .stat-card .stat-icon {
            position: absolute;
            right: 1.25rem;
            top: 1.25rem;
            opacity: 0.2;
            font-size: 2.8rem;
        }
        
        .stat-card .stat-title {
            font-size: 0.95rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }
        
        .stat-card .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .quick-action-btn {
            display: flex;
            align-items: center;
            padding: 1.2rem 1.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            color: white;
            border: none;
            border-radius: 12px;
            text-align: left;
            transition: all 0.3s;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.25);
        }
        
        .quick-action-btn:hover {
            background: linear-gradient(to right, #3a55c8, #5a75e0);
            color: white;
            text-decoration: none;
        }
        
        .quick-action-btn i {
            font-size: 1.8rem;
            margin-right: 1rem;
            background: rgba(255,255,255,0.2);
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .welcome-card {
            background: linear-gradient(120deg, var(--primary), var(--primary-light));
            color: white;
            border-radius: 12px;
            padding: 2.5rem;
        }
        
        .job-card {
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.3s;
        }
        
        .job-card:hover {
            transform: translateY(-5px);
        }
        
        .job-card .card-body {
            padding: 1.5rem;
        }
        
        .job-card .company-logo {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            background: #f0f5ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .job-card .job-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .job-card .company-name {
            color: var(--gray);
            margin-bottom: 1rem;
        }
        
        .job-card .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 1.5rem;
        }
        
        .job-card .meta-item {
            background: #f0f5ff;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .progress-bar {
            transition: width 1s ease-in-out;
        }
        
        /* === NEW/UPDATED NOTIFICATION STYLES === */
        .offcanvas-body .list-group-item {
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 1.5rem;
        }
        .offcanvas-body .list-group-item:last-child {
            border-bottom: none;
        }
        
        .notification-icon-wrapper {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-light);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        
        .notification-content {
            flex-grow: 1;
        }
        
        .notification-content .notification-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }
        
        .notification-content .notification-meta {
            font-size: 0.85rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
            display: block;
        }
        
        /* NEW STYLE FOR TIME */
        .notification-content .notification-time {
            font-size: 0.75rem;
            padding-left: 1rem;
        }

        #no-notifications-msg {
            display: none; /* Hide by default, JS will show it */
        }
        /* === END NOTIFICATION STYLES === */
        /* === NEW: GAMIFICATION STYLES === */
        .completeness-tasks {
            margin-top: 20px;
        }
        
        .task-item {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            margin-bottom: 8px;
            text-decoration: none;
            color: white;
            transition: background 0.3s;
        }
        
        .task-item:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .task-icon {
            width: 28px;
            height: 28px;
            border: 2px solid white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
            margin-right: 12px;
            flex-shrink: 0;
        }
        
        .task-text {
            flex-grow: 1;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .task-points {
            font-weight: bold;
            opacity: 0.8;
            font-size: 0.9rem;
            margin-left: 10px;
        }
        /* === END: GAMIFICATION STYLES === */

        /* === NEW: Job Journey Card Styles === */
        #job-journey-card .list-group-item {
            padding: 0.75rem 0.5rem;
            font-size: 0.9rem;
            border-radius: 8px;
            margin-bottom: 4px;
            border: 1px solid transparent;
        }
        #job-journey-card .list-group-item:hover {
            background-color: #f8f9fa;
        }
        #job-journey-card .list-group-item-action {
            cursor: pointer;
        }
        #job-journey-card .form-check-input {
            margin-top: 0.1em;
        }
        /* === END: Job Journey Card Styles === */


        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar .nav-link span {
                display: none;
            }
            
            .sidebar .nav-link i {
                margin-right: 0;
                font-size: 1.4rem;
            }
            
            .sidebar-logo h4 {
                display: none;
            }
            
            .sidebar-logo {
                text-align: center;
                padding: 1rem 0;
            }
            
            .main-content {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                min-height: auto;
                position: relative;
            }
            
            .sidebar .nav {
                flex-direction: row;
                overflow-x: auto;
            }
            
            .sidebar .nav-link {
                padding: 1rem;
                border-left: none;
                border-bottom: 3px solid transparent;
            }
            
            .sidebar .nav-link:hover, .sidebar .nav-link.active {
                border-left: none;
                border-bottom: 3px solid #fff;
            }
            
            .sidebar-logo {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="sidebar-logo">
                    <h4 class="text-white"><i class="fas fa-briefcase me-2"></i>EmployeePortal</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="sidebar-section">Employee Portal</li>
                    <li class="nav-item">
                        <a class="nav-link active" href="employee_dashboard.php">
                            <i class="fas fa-fw fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <?php if ($registration): ?>
                            <a class="nav-link id" href="javascript:void(0)" 
                            title="You are already registered. Go to profile to edit details." 
                            data-bs-toggle="tooltip" data-bs-placement="right">
                                <i class="fas fa-fw fa-user-plus"></i>
                                <span>Registration</span>
                            </a>
                        <?php else: ?>
                            <a class="nav-link" href="employee_registration.php">
                                <i class="fas fa-fw fa-user-plus"></i>
                                <span>Registration</span>
                            </a>
                        <?php endif; ?>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="resume_builder.php">
                            <i class="fas fa-fw fa-file-alt"></i>
                            <span>Resume Builder</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="application_status.php">
                            <i class="fas fa-fw fa-clipboard-check"></i>
                            <span>Application Status</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="savedjobs.php">
                            <i class="fas fa-fw fa-bookmark"></i>
                            <span>Saved Jobs</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <?php if (!$registration): ?>
                            <a class="nav-link user" href="javascript:void(0)" 
                            title="You have not registered. Go to Registration to create your profile." 
                            data-bs-toggle="tooltip" data-bs-placement="right">
                                <i class="fas fa-fw fa-user-circle"></i>
                                <span>Profile</span>
                            </a>
                        <?php else: ?>
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-fw fa-user-circle"></i>
                                <span>Profile</span>
                            </a>
                        <?php endif; ?>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">
                            <i class="fas fa-fw fa-envelope"></i>
                            <span>Contact</span>
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link" href="index.php">
                            <i class="fa fa-home"></i>
                            <span>Home</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?logout=1">
                            <i class="fas fa-fw fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="main-content">
                <div class="top-bar d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-0"><i class="fas fa-user me-2"></i>Employee Dashboard</h1>
                    <div class="d-flex align-items-center">
                        <a class="nav-link notification-icon me-3" id="notificationBell" role="button" data-bs-toggle="offcanvas" data-bs-target="#notificationPanel" aria-controls="notificationPanel">
                            <i class="fas fa-bell fs-5"></i>
                            <span class="notification-badge notification-badge-count"><?= $notification_count ?></span>
                        </a>
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-2">
                                <?= $initials ?>
                            </div>
                            <div>
                                <div class="fw-bold"><?= $user['name'] ?></div>
                                <small class="text-muted">Employee</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card welcome-card mb-4">
                    <div class="card-body">
                        <?php
                        // Check if this is user's first login by comparing created_at with current time
                        $user_created = strtotime($user['created_at']);
                        $current_time = time();
                        $time_difference = $current_time - $user_created;
                        
                        // If account was created within the last 24 hours, consider it first login
                        $is_first_login = ($time_difference <= 86400); // 86400 seconds = 24 hours
                        
                        if ($is_first_login): ?>
                            <h1>Welcome, <?= htmlspecialchars($user['name']) ?>!</h1>
                            <?php if ($profile_completeness > 0): ?>
                                <?php if ($profile_completeness == 100): ?>
                                    <p>Congratulations! Your profile is 100% complete. You're now fully visible to employers and ready to explore new opportunities.</p>
                                <?php else: ?>
                                    <p>Your profile is <?= $profile_completeness ?>% complete. Complete your profile to increase your visibility to employers and explore new opportunities.</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p>We're excited to have you on board! Complete your profile to get started and explore new opportunities.</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <h1>Welcome back, <?= htmlspecialchars($user['name']) ?>!</h1>
                            <?php if ($profile_completeness < 100): ?>
                                <p>Your profile is <?= $profile_completeness ?>% complete. Complete your profile to increase your visibility to employers and explore new opportunities.</p>
                            <?php else: ?>
                                <p>Congratulations! Your profile is 100% complete. You're now fully visible to employers and ready to explore new opportunities.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="progress mt-3" style="height: 10px; background-color: rgba(0, 0, 0, 0.25);">
                            <div class="progress-bar bg-white" role="progressbar" style="width: <?= $profile_completeness ?>%"></div>
                        </div>
                        <?php // --- NEW: Gamification Section --- ?>
                        <?php if ($profile_completeness < 100 && !empty($completeness_tasks)): ?>
                            <div class="completeness-tasks">
                                <h6 class="text-white opacity-75 mb-3">What's Next?</h6>
                                <?php 
                                // Show only the top 3 tasks to avoid clutter
                                $tasks_to_show = array_slice($completeness_tasks, 0, 3); 
                                ?>
                                <?php foreach ($tasks_to_show as $task): ?>
                                    <a href="<?= htmlspecialchars($task['link']) ?>" class="task-item">
                                        <div class="task-icon">+</div>
                                        <div class="task-text"><?= htmlspecialchars($task['text']) ?></div>
                                        <div class="task-points"><?= htmlspecialchars($task['points']) ?></div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php // --- END: Gamification Section --- ?>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card primary">
                            <div class="position-relative">
                                <div class="stat-title">Profile Completeness</div>
                                <div class="stat-value"><?= $profile_completeness ?>%</div>
                                <div class="stat-icon">
                                    <i class="fas fa-user-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card success">
                            <div class="position-relative">
                                <div class="stat-title">Job Applications</div>
                                <div class="stat-value"><?= $stats['job_applications'] ?></div>
                                <div class="stat-icon">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card info">
                            <div class="position-relative">
                                <div class="stat-title">Interviews</div>
                                <div class="stat-value"><?= $stats['interviews'] ?></div>
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card primary">
                            <div class="position-relative">
                                <div class="stat-title">Resumes Created</div>
                                <div class="stat-value"><?= $stats['resume_created'] ?></div>
                                <div class="stat-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <?php
                                    // Set dynamic title based on job status
                                    switch ($job_status) {
                                        case 'preparing_for_interviews': echo '<i class="fas fa-brain me-2"></i>Your Applications & Prep'; break;
                                        case 'appearing_for_interviews': echo '<i class="fas fa-user-tie me-2"></i>Your Interview Hub'; break;
                                        case 'received_a_job_offer': echo '<i class="fas fa-party-horn me-2"></i>Congratulations!'; break;
                                        case 'not_looking': echo '<i class="fas fa-seedling me-2"></i>Career Growth'; break;
                                        case 'casually_exploring': echo '<i class="fas fa-search me-2"></i>Jobs for You'; break;
                                        default: echo '<i class="fas fa-briefcase me-2"></i>Job Recommendations';
                                    }
                                    ?>
                                </h5>
                                <?php if ($registration && ($job_status == 'actively_searching' || $job_status == 'casually_exploring' || $job_status == null)): ?>
                                    <a href="jobs.php" class="btn btn-sm btn-primary">View All</a>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                
                                <?php // --- START CONDITIONAL CONTENT --- ?>
                                
                                <?php if (!$registration): ?>
                                    <div class="col-12 text-center py-4">
                                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">Complete Your Registration</h5>
                                        <p class="text-muted">Please complete your registration to view job recommendations.</p>
                                        <a href="employee_registration.php" class="btn btn-primary">
                                            <i class="fas fa-user-plus me-2"></i>Complete Registration
                                        </a>
                                    </div>

                                <?php elseif ($job_status == 'received_a_job_offer'): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-glass-cheers fa-3x text-success mb-3"></i>
                                        <h4>Congratulations on your job offer!</h4>
                                        <p class="text-muted">What's next? Prepare for your new role.</p>
                                        <div class="d-flex justify-content-center gap-3 mt-4">
                                            <a href="career_advice.php" class="btn btn-outline-primary"><i class="fas fa-chart-line me-2"></i>Career Advice</a>
                                            <a href="resume_tips.php" class="btn btn-outline-primary"><i class="fas fa-lightbulb me-2"></i>Skill Building</a>
                                        </div>
                                    </div>

                                <?php elseif ($job_status == 'not_looking'): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-seedling fa-3x text-success mb-3"></i>
                                        <h4>Focus on Growth</h4>
                                        <p class="text-muted">You're not looking for jobs, so here are some resources to help you grow in your career.</p>
                                        <div class="d-flex justify-content-center gap-3 mt-4">
                                            <a href="career_advice.php" class="btn btn-primary"><i class="fas fa-chart-line me-2"></i>Career Development</a>
                                            <a href="interview_prep.php" class="btn btn-outline-primary"><i class="fas fa-brain me-2"></i>Sharpen Interview Skills</a>
                                        </div>
                                    </div>
                                
                                <?php elseif ($job_status == 'preparing_for_interviews'): ?>
                                    <h5><i class="fas fa-clipboard-check me-2 text-primary"></i>Your Recent Applications</h5>
                                    <?php
                                    $applied_jobs_list = [];
                                    $applied_sql = "SELECT a.status, j.job_designation, c.organization_name 
                                                    FROM applications a 
                                                    JOIN job_openings j ON a.job_id = j.id 
                                                    JOIN companies c ON j.company_id = c.id 
                                                    WHERE a.email = ? ORDER BY a.applied_at DESC LIMIT 3";
                                    $stmt_applied = mysqli_prepare($conn, $applied_sql);
                                    mysqli_stmt_bind_param($stmt_applied, "s", $user['email']);
                                    mysqli_stmt_execute($stmt_applied);
                                    $applied_result_list = mysqli_stmt_get_result($stmt_applied);
                                    while ($applied_row = mysqli_fetch_assoc($applied_result_list)) {
                                        $applied_jobs_list[] = $applied_row;
                                    }
                                    ?>
                                    <?php if (!empty($applied_jobs_list)): ?>
                                        <ul class="list-group list-group-flush mb-3">
                                        <?php foreach($applied_jobs_list as $applied_job): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                <div>
                                                    <strong><?= htmlspecialchars($applied_job['job_designation']) ?></strong>
                                                    <div class="text-muted small"><?= htmlspecialchars($applied_job['organization_name']) ?></div>
                                                </div>
                                                <span class="badge bg-info rounded-pill"><?= htmlspecialchars($applied_job['status']) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                        </ul>
                                        <a href="application_status.php" class="btn btn-sm btn-outline-primary mb-4">View All Applications</a>
                                    <?php else: ?>
                                        <p class="text-muted">You haven't applied for any jobs yet.</p>
                                    <?php endif; ?>

                                    <hr>
                                    <h5><i class="fas fa-brain me-2 text-primary"></i>Interview Prep Resources</h5>
                                    <p class="text-muted">Get ready for your interviews with these guides.</p>
                                    <a href="interview_prep.php" class="btn btn-primary mb-4"><i class="fas fa-book-open me-2"></i>Interview Guide</a>
                                    
                                    <?php if (!empty($job_recommendations)): ?>
                                        <hr>
                                        <h5 class="mt-4">Highly Matched Jobs</h5>
                                        <div class="row">
                                            <?php foreach ($job_recommendations as $job): ?>
                                                <div class="col-md-6 mb-4">
                                                    <div class="card job-card h-100">
                                                        <div class="card-body">
                                                            <div class="company-logo"><i class="fas fa-building"></i></div>
                                                            <h5 class="job-title"><?= htmlspecialchars($job['job_designation']) ?></h5>
                                                            <div class="company-name"><?= htmlspecialchars($job['organization_name'] ?? 'Company') ?></div>
                                                            <div class="job-meta">
                                                                <span class="meta-item"><?= htmlspecialchars($job['job_location']) ?></span>
                                                                <span class="meta-item">₹<?= number_format($job['from_ctc'], 1) ?>L-<?= number_format($job['to_ctc'], 1) ?>L</span>
                                                                <span class="meta-item"><?= $job['exp_from'] ?>-<?= $job['exp_to'] ?> yrs</span>
                                                            </div>
                                                            <?php if (in_array($job['id'], $applied_jobs)): ?>
                                                                <button class="btn btn-success w-100" disabled><i class="fas fa-check me-2"></i>Applied</button>
                                                            <?php else: ?>
                                                                <a href="apply.php?job_id=<?= $job['id'] ?>" class="btn btn-outline-primary w-100">Apply Now</a>
                                                            <?php endif; ?>
                                                            <?php if (isset($_SESSION['user']) && $_SESSION['role'] === 'employee'): 
                                                                $is_saved = false;
                                                                $check_saved_sql = "SELECT id FROM saved_jobs WHERE user_id = ? AND job_id = ?";
                                                                $stmt_check = mysqli_prepare($conn, $check_saved_sql);
                                                                mysqli_stmt_bind_param($stmt_check, "ii", $user_id, $job['id']);
                                                                mysqli_stmt_execute($stmt_check);
                                                                $saved_result = mysqli_stmt_get_result($stmt_check);
                                                                $is_saved = mysqli_num_rows($saved_result) > 0;
                                                                ?>
                                                                <?php if ($is_saved): ?>
                                                                    <a href="savedjobs.php" class="btn btn-success btn-sm mt-2 w-100"><i class="fas fa-bookmark me-1"></i> Saved</a>
                                                                <?php else: ?>
                                                                    <a href="savedjobs.php?save_job=<?= $job['id'] ?>" class="btn btn-outline-secondary btn-sm mt-2 w-100"><i class="far fa-bookmark me-1"></i> Save Job</a>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                <?php elseif ($job_status == 'appearing_for_interviews'): ?>
                                    <h5><i class="fas fa-calendar-check me-2 text-primary"></i>Your Upcoming Interviews</h5>
                                    <?php if (!empty($upcoming_interviews)): ?>
                                        <?php foreach ($upcoming_interviews as $interview): ?>
                                            <div class="d-flex mb-3">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-primary text-white rounded p-2" style="width: 40px; height: 40px; display: grid; place-items: center;">
                                                        <i class="fas fa-calendar"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-0"><?= $interview['company'] ?> - <?= $interview['position'] ?></h6>
                                                    <small class="text-muted"><?= date('M d, h:i A', strtotime($interview['interview_time'])) ?></small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted">You have no upcoming interviews scheduled.</p>
                                    <?php endif; ?>
                                    
                                    <hr>
                                    <h5><i class="fas fa-eye me-2 text-primary"></i>Who's Viewed Your Profile</h5>
                                    <?php 
                                    $all_views = array_merge($notification_views, $notification_generic_views);
                                    usort($all_views, function($a, $b) { return strtotime($b['viewed_at']) - strtotime($a['viewed_at']); });
                                    $recent_views = array_slice($all_views, 0, 3);
                                    ?>
                                    <?php if (!empty($recent_views)): ?>
                                        <ul class="list-group list-group-flush mb-4">
                                        <?php foreach($recent_views as $view): ?>
                                            <li class="list-group-item px-0">
                                                <strong><?= htmlspecialchars($view['organization_name']) ?></strong>
                                                <div class="text-muted small">
                                                    Viewed your <?= htmlspecialchars($view['view_type']) ?>
                                                    <?php if (isset($view['job_designation'])): ?>
                                                        for "<?= htmlspecialchars($view['job_designation']) ?>"
                                                    <?php endif; ?>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                         <p class="text-muted">No recent profile views.</p>
                                    <?php endif; ?>

                                    <?php if (!empty($job_recommendations)): ?>
                                        <hr>
                                        <h5 class="mt-4">A Top Match for You</h5>
                                        <div class="row">
                                            <?php foreach ($job_recommendations as $job): ?>
                                                <div class="col-md-6 mb-4">
                                                    <div class="card job-card h-100">
                                                        <div class="card-body">
                                                            <div class="company-logo"><i class="fas fa-building"></i></div>
                                                            <h5 class="job-title"><?= htmlspecialchars($job['job_designation']) ?></h5>
                                                            <div class="company-name"><?= htmlspecialchars($job['organization_name'] ?? 'Company') ?></div>
                                                            <div class="job-meta">
                                                                <span class="meta-item"><?= htmlspecialchars($job['job_location']) ?></span>
                                                                <span class="meta-item">₹<?= number_format($job['from_ctc'], 1) ?>L-<?= number_format($job['to_ctc'], 1) ?>L</span>
                                                                <span class="meta-item"><?= $job['exp_from'] ?>-<?= $job['exp_to'] ?> yrs</span>
                                                            </div>
                                                            <?php if (in_array($job['id'], $applied_jobs)): ?>
                                                                <button class="btn btn-success w-100" disabled><i class="fas fa-check me-2"></i>Applied</button>
                                                            <?php else: ?>
                                                                <a href="apply.php?job_id=<?= $job['id'] ?>" class="btn btn-outline-primary w-100">Apply Now</a>
                                                            <?php endif; ?>
                                                            <?php if (isset($_SESSION['user']) && $_SESSION['role'] === 'employee'): 
                                                                $is_saved = false;
                                                                $check_saved_sql = "SELECT id FROM saved_jobs WHERE user_id = ? AND job_id = ?";
                                                                $stmt_check = mysqli_prepare($conn, $check_saved_sql);
                                                                mysqli_stmt_bind_param($stmt_check, "ii", $user_id, $job['id']);
                                                                mysqli_stmt_execute($stmt_check);
                                                                $saved_result = mysqli_stmt_get_result($stmt_check);
                                                                $is_saved = mysqli_num_rows($saved_result) > 0;
                                                                ?>
                                                                <?php if ($is_saved): ?>
                                                                    <a href="savedjobs.php" class="btn btn-success btn-sm mt-2 w-100"><i class="fas fa-bookmark me-1"></i> Saved</a>
                                                                <?php else: ?>
                                                                    <a href="savedjobs.php?save_job=<?= $job['id'] ?>" class="btn btn-outline-secondary btn-sm mt-2 w-100"><i class="far fa-bookmark me-1"></i> Save Job</a>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                <?php else: ?>
                                    <?php if ($job_status == 'casually_exploring'): ?>
                                        <p class="text-muted mb-3">You're casually exploring. Here are a few curated roles that match your profile.</p>
                                    <?php endif; ?>
                                    
                                    <div class="row">
                                        <?php if (!empty($job_recommendations)): ?>
                                            <?php foreach ($job_recommendations as $job): ?>
                                                <div class="col-md-6 mb-4">
                                                    <div class="card job-card h-100">
                                                        <div class="card-body">
                                                            <div class="company-logo"><i class="fas fa-building"></i></div>
                                                            <h5 class="job-title"><?= htmlspecialchars($job['job_designation']) ?></h5>
                                                            <div class="company-name"><?= htmlspecialchars($job['organization_name'] ?? 'Company') ?></div>
                                                            <div class="job-meta">
                                                                <span class="meta-item"><?= htmlspecialchars($job['job_location']) ?></span>
                                                                <span class="meta-item">₹<?= number_format($job['from_ctc'], 1) ?>L-<?= number_format($job['to_ctc'], 1) ?>L</span>
                                                                <span class="meta-item"><?= $job['exp_from'] ?>-<?= $job['exp_to'] ?> yrs</span>
                                                            </div>
                                                            <?php if (in_array($job['id'], $applied_jobs)): ?>
                                                                <button class="btn btn-success w-100" disabled><i class="fas fa-check me-2"></i>Applied</button>
                                                            <?php else: ?>
                                                                <a href="apply.php?job_id=<?= $job['id'] ?>" class="btn btn-outline-primary w-100">Apply Now</a>
                                                            <?php endif; ?>
                                                            <?php if (isset($_SESSION['user']) && $_SESSION['role'] === 'employee'): 
                                                                $is_saved = false;
                                                                $check_saved_sql = "SELECT id FROM saved_jobs WHERE user_id = ? AND job_id = ?";
                                                                $stmt_check = mysqli_prepare($conn, $check_saved_sql);
                                                                mysqli_stmt_bind_param($stmt_check, "ii", $user_id, $job['id']);
                                                                mysqli_stmt_execute($stmt_check);
                                                                $saved_result = mysqli_stmt_get_result($stmt_check);
                                                                $is_saved = mysqli_num_rows($saved_result) > 0;
                                                                ?>
                                                                <?php if ($is_saved): ?>
                                                                    <a href="savedjobs.php" class="btn btn-success btn-sm mt-2 w-100"><i class="fas fa-bookmark me-1"></i> Saved</a>
                                                                <?php else: ?>
                                                                    <a href="savedjobs.php?save_job=<?= $job['id'] ?>" class="btn btn-outline-secondary btn-sm mt-2 w-100"><i class="far fa-bookmark me-1"></i> Save Job</a>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="col-12 text-center py-4">
                                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">No new job recommendations</h5>
                                                <p class="text-muted">We're looking for new roles for you. Check back soon!</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php // --- END CONDITIONAL CONTENT --- ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        
                        <?php if ($registration): // Only show this if user is registered ?>
                            <div class="card mb-4" id="job-journey-card" <?php if ($is_status_set) echo 'style="display: none;"'; ?>>
                                <div class="card-body">
                                    <h6 class="text-muted">Needs attention</h6>
                                    <h5 class="card-title mb-3">Where are you in your job search journey?</h5>
                                    <form id="job-journey-form">
                                        <div class="list-group list-group-flush">
                                            <label class="list-group-item list-group-item-action px-2">
                                                <input type="radio" name="job_status" value="actively_searching" class="form-check-input me-2" checked> Actively searching jobs
                                            </label>
                                            <label class="list-group-item list-group-item-action px-2">
                                                <input type="radio" name="job_status" value="preparing_for_interviews" class="form-check-input me-2"> Preparing for interviews
                                            </label>
                                            <label class="list-group-item list-group-item-action px-2">
                                                <input type="radio" name="job_status" value="appearing_for_interviews" class="form-check-input me-2"> Appearing for interviews
                                            </label>
                                            <label class="list-group-item list-group-item-action px-2">
                                                <input type="radio" name="job_status" value="received_a_job_offer" class="form-check-input me-2"> Received a job offer
                                            </label>
                                            <label class="list-group-item list-group-item-action px-2">
                                                <input type="radio" name="job_status" value="casually_exploring" class="form-check-input me-2"> Casually exploring jobs
                                            </label>
                                            <label class="list-group-item list-group-item-action px-2">
                                                <input type="radio" name="job_status" value="not_looking" class="form-check-input me-2"> Not looking for jobs
                                            </label>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100 mt-3">Submit</button>
                                    </form>
                                </div>
                            </div>
                        
                            <div class="card mb-4" id="profile-card-wrapper" <?php if (!$is_status_set) echo 'style="display: none;"'; ?>>
                                <div class="card-body text-center">
                                    <div class="profile-img mb-3">
                                        <?php if ($user_photo && file_exists($user_photo)): ?>
                                            <img src="<?= htmlspecialchars($user_photo) ?>" alt="Profile Photo" 
                                                style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #4361ee;">
                                        <?php else: ?>
                                            <div style="width: 120px; height: 120px; border-radius: 50%; background: #4361ee; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: white; margin: 0 auto;">
                                                <?= $initials ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <h4><?= htmlspecialchars($user['name']) ?></h4>
                                    <p class="text-muted">
                                        <?php 
                                        if (!empty($user_job_title)): ?>
                                            <?= htmlspecialchars($user_job_title) ?>
                                        <?php elseif ($registration): ?>
                                            <?php
                                            if (!empty($registration['pg_course'])) {
                                                echo htmlspecialchars($registration['pg_course']) . ' Student';
                                            } elseif (!empty($registration['degree_course'])) {
                                                echo htmlspecialchars($registration['degree_course']) . ' Student';
                                            } else {
                                                echo 'Job Seeker';
                                            }
                                            ?>
                                        <?php else: ?>
                                            Job Seeker
                                        <?php endif; ?>
                                    </p>
                                    
                                    <div class="d-flex justify-content-center gap-3 mt-3">
                                        <div class="text-center">
                                            <div class="fw-bold text-primary"><?= $profile_completeness ?>%</div>
                                            <div class="text-muted small">Profile</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="fw-bold text-primary"><?= $stats['job_applications'] ?></div>
                                            <div class="text-muted small">Applications</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="fw-bold text-primary"><?= $stats['interviews'] ?></div>
                                            <div class="text-muted small">Interviews</div>
                                        </div>
                                    </div>
                                    
                                    <a href="profile.php" class="btn btn-primary mt-3">
                                        <i class="fas fa-edit me-2"></i> Edit Profile
                                    </a>

                                    <div class="text-center mt-3 border-top pt-3">
                                        <small class="text-muted">Your Status: 
                                            <strong id="job-status-display"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $job_status ?? 'Not Set'))) ?></strong>
                                        </small>
                                        <a href="#" id="change-job-status-btn" class="btn btn-sm btn-outline-secondary d-block mt-2">Change Status</a>
                                    </div>
                                </div>
                            </div>

                        <?php else: // If user is not registered, show a simplified profile card ?>
                             <div class="card mb-4">
                                <div class="card-body text-center">
                                    <div style="width: 120px; height: 120px; border-radius: 50%; background: #4361ee; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: white; margin: 0 auto;">
                                        <?= $initials ?>
                                    </div>
                                    <h4 class_="mt-3"><?= htmlspecialchars($user['name']) ?></h4>
                                    <p class="text-muted">Job Seeker</p>
                                    <button class="btn btn-primary mt-3" 
                                            title="You have not registered. Go to Registration to create your profile." 
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            onclick="alert('You have not registered yet. Please complete your registration to create your profile.')">
                                        <i class="fas fa-edit me-2"></i> Edit Profile
                                    </button>
                                </div>
                             </div>
                        <?php endif; ?>
                        
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Upcoming Interviews</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($upcoming_interviews)): ?>
                                    <?php foreach ($upcoming_interviews as $interview): ?>
                                        <div class="d-flex mb-3">
                                            <div class="flex-shrink-0">
                                                <div class="bg-primary text-white rounded p-2" style="width: 40px; height: 40px; display: grid; place-items: center;">
                                                    <i class="fas fa-calendar"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0"><?= $interview['company'] ?> - <?= $interview['position'] ?></h6>
                                                <small class="text-muted">
                                                    <?= date('M d, h:i A', strtotime($interview['interview_time'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">No upcoming interviews</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    </div>
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="notificationPanel" aria-labelledby="notificationPanelLabel">
        <div class="offcanvas-header border-bottom">
            <h5 id="notificationPanelLabel"><i class="fas fa-bell me-2"></i>Notifications</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <ul class="list-group list-group-flush" id="notification-list">
                <?php if ($notification_count > 0): ?>
                    <?php foreach ($all_notifications as $notification): ?>
                        <?php if ($notification['type'] == 'job_recommendation'): ?>
                            <li class="list-group-item list-group-item-action py-3 notification-item" 
                                data-job-id="<?= $notification['data']['id'] ?>" 
                                data-timestamp="<?= htmlspecialchars($notification['timestamp']) ?>"
                                data-type="job">
                                <div class="notification-icon-wrapper" style="background-color: #4361ee;">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <div class="notification-content">
                                    <small class="float-end text-muted notification-time">--</small>
                                    
                                    <div class="notification-title">New Job Match: <?= htmlspecialchars($notification['data']['job_designation']) ?></div>
                                    <span class="notification-meta">
                                        <i class="fas fa-building me-1 opacity-75"></i> <?= htmlspecialchars($notification['data']['organization_name']) ?>
                                    </span>
                                    <span class="notification-meta">
                                        <i class="fas fa-map-marker-alt me-1 opacity-75"></i> <?= htmlspecialchars($notification['data']['job_location']) ?>
                                    </span>
                                    <span class="notification-meta">
                                        <i class="fas fa-money-bill-wave me-1 opacity-75"></i> ₹<?= number_format((float)$notification['data']['from_ctc'], 1) ?>L - ₹<?= number_format((float)$notification['data']['to_ctc'], 1) ?>L
                                    </span>
                                    
                                    <a href="apply.php?job_id=<?= $notification['data']['id'] ?>" class="btn btn-primary btn-sm mt-2">
                                        <i class="fas fa-paper-plane me-1"></i> Apply Now
                                    </a>
                                </div>
                            </li>
                        <?php elseif ($notification['type'] == 'application_view'): ?>
                            <li class="list-group-item list-group-item-action py-3 notification-item" 
                                data-view-id="<?= $notification['data']['application_view_id'] ?>" 
                                data-timestamp="<?= htmlspecialchars($notification['timestamp']) ?>"
                                data-type="view">
                                <div class="notification-icon-wrapper" style="background-color: #f72585;">
                                    <i class="fas fa-eye"></i>
                                </div>
                                <div class="notification-content">
                                    <small class="float-end text-muted notification-time">--</small>
                                    
                                    <div class="notification-title">
                                        <?php if ($notification['data']['view_type'] == 'profile'): ?>
                                            Profile Viewed for Application
                                        <?php else: ?>
                                            Resume Viewed for Application
                                        <?php endif; ?>
                                    </div>
                                    <span class="notification-meta">
                                        <i class="fas fa-building me-1 opacity-75"></i> <?= htmlspecialchars($notification['data']['organization_name']) ?>
                                    </span>
                                    <span class="notification-meta">
                                        <i class="fas fa-briefcase me-1 opacity-75"></i> <?= htmlspecialchars($notification['data']['job_designation']) ?>
                                    </span>
                                    
                                    <a href="application_status.php" class="btn btn-outline-primary btn-sm mt-2">
                                        <i class="fas fa-clipboard-check me-1"></i> Check Status
                                    </a>
                                </div>
                            </li>
                        
                        <?php elseif ($notification['type'] == 'generic_view'): ?>
                            <li class="list-group-item list-group-item-action py-3 notification-item" 
                                data-generic-id="<?= $notification['data']['generic_view_id'] ?>" 
                                data-timestamp="<?= htmlspecialchars($notification['timestamp']) ?>"
                                data-type="generic_view">
                                <div class="notification-icon-wrapper" style="background-color: #4cc9f0;">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="notification-content">
                                    <small class="float-end text-muted notification-time">--</small>
                                    
                                    <div class="notification-title">
                                        Your Profile was Viewed
                                    </div>
                                    <span class="notification-meta">
                                        <i class="fas fa-building me-1 opacity-75"></i> 
                                        <?= htmlspecialchars($notification['data']['organization_name'] ?? 'An Employer') ?>
                                    </span>
                                    <span class="notification-meta">
                                        <i class="fas fa-clock me-1 opacity-75"></i> 
                                        Viewed your profile from candidate search
                                    </span>
                                    
                                    <a href="profile.php" class="btn btn-outline-primary btn-sm mt-2">
                                        <i class="fas fa-user me-1"></i> View Your Profile
                                    </a>
                                </div>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <li class="list-group-item text-center text-muted p-4" id="no-notifications-msg">
                    <i class="fas fa-check-circle fs-3 d-block mb-2 text-success"></i>
                    You're all caught up!
                    <br>
                    <small>No new notifications.</small>
                </li>
            </ul>
        </div>
        
        <div class="offcanvas-footer p-3 border-top notification-footer">
            <button class="btn btn-outline-secondary w-100 mark-all-read-btn" id="mark-all-read">
                <i class="fas fa-check-double me-2"></i> Mark all as read
            </button>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Tooltip initialization
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Show alert when clicking disabled registration link
    document.querySelectorAll('.nav-link.id').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            alert('You have already registered. If you want to edit your details, please go to the Profile section.');
        });
    });

    // Show alert when clicking disabled profile link
    document.querySelectorAll('.nav-link.user').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            alert('You have not registered yet. Please complete your registration to create your profile.');
        });
    });

    // === NEW timeAgo function ===
    function timeAgo(dateString) {
        // Must parse the SQL datetime string correctly
        const date = new Date(dateString.replace(' ', 'T')); // Replace '-' with '/' for cross-browser compatibility
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        let interval = seconds / 31536000;
        if (interval > 1) {
            return Math.floor(interval) + (Math.floor(interval) === 1 ? " year" : " years") + " ago";
        }
        interval = seconds / 2592000;
        if (interval > 1) {
            return Math.floor(interval) + (Math.floor(interval) === 1 ? " month" : " months") + " ago";
        }
        interval = seconds / 86400;
        if (interval > 1) {
            return Math.floor(interval) + (Math.floor(interval) === 1 ? " day" : " days") + " ago";
        }
        interval = seconds / 3600;
        if (interval > 1) {
            return Math.floor(interval) + (Math.floor(interval) === 1 ? " hour" : " hours") + " ago";
        }
        interval = seconds / 60;
        if (interval > 1) {
            return Math.floor(interval) + (Math.floor(interval) === 1 ? " minute" : " minutes") + " ago";
        }
        if (seconds < 10) return "just now";
        
        return Math.floor(seconds) + " seconds ago";
    }

    // === NEW Job Search Journey Script ===
    document.addEventListener('DOMContentLoaded', function() {
        const journeyForm = document.getElementById('job-journey-form');
        const journeyCard = document.getElementById('job-journey-card');
        const profileCard = document.getElementById('profile-card-wrapper');
        const changeBtn = document.getElementById('change-job-status-btn');
        const statusDisplay = document.getElementById('job-status-display');

        journeyForm?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const newStatus = formData.get('job_status');
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';

            fetch('update_job_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the text on the profile card (which is hidden)
                    if (statusDisplay) {
                        statusDisplay.textContent = data.status_text; 
                    }
                    // Hide journey card, show profile card
                    if (journeyCard) journeyCard.style.display = 'none';
                    if (profileCard) profileCard.style.display = 'block';
                    
                    // Reload the whole page to update the col-lg-8 content
                    location.reload(); 
                } else {
                    alert('Error updating status: ' + (data.message || 'Please try again.'));
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Submit';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Submit';
            });
        });

        changeBtn?.addEventListener('click', function(e) {
            e.preventDefault();
            // Hide profile card, show journey card
            if (profileCard) profileCard.style.display = 'none';
            if (journeyCard) {
                journeyCard.style.display = 'block';
                // Select the current status in the form
                const currentStatus = "<?= $job_status ?? 'actively_searching' ?>";
                const radio = journeyForm.querySelector(`input[name="job_status"][value="${currentStatus}"]`);
                if (radio) {
                    radio.checked = true;
                }
            }
        });
    });
    // === END Job Search Journey Script ===


    // === UPDATED NOTIFICATION SCRIPT (handles both job and view notifications) ===
    document.addEventListener('DOMContentLoaded', function() {
        const currentUserId = <?= $user_id ?>;
        const jobStorageKey = 'lastReadJobId_' + currentUserId;
        const viewStorageKey = 'lastReadViewId_' + currentUserId;
        const genericViewStorageKey = 'lastReadGenericViewId_' + currentUserId;
        
        const notificationList = document.getElementById('notification-list');
        const notificationItems = document.querySelectorAll('.notification-item');
        const notificationBadge = document.querySelector('.notification-badge-count');
        const noNotificationsMsg = document.getElementById('no-notifications-msg');
        const notificationFooter = document.querySelector('.notification-footer');
        const markAllReadBtn = document.querySelector('.mark-all-read-btn');

        let visibleCount = 0;
        const lastReadJobId = parseInt(localStorage.getItem(jobStorageKey) || '0');
        const lastReadViewId = parseInt(localStorage.getItem(viewStorageKey) || '0');
        const lastReadGenericViewId = parseInt(localStorage.getItem(genericViewStorageKey) || '0');
        
        if (notificationItems.length > 0) {
            
            // --- Set all timestamps first ---
            notificationItems.forEach(item => {
                const timestamp = item.dataset.timestamp;
                const timeEl = item.querySelector('.notification-time');
                if (timestamp && timeEl) {
                    timeEl.textContent = timeAgo(timestamp);
                }
            });

            // --- Count visible notifications ---
            notificationItems.forEach(item => {
                const type = item.dataset.type;
                let isVisible = false;
                
                if (type === 'job') {
                    const jobId = parseInt(item.dataset.jobId);
                    if (jobId > lastReadJobId) {
                        isVisible = true;
                        visibleCount++;
                    }
                } else if (type === 'view') {
                    const viewId = parseInt(item.dataset.viewId);
                    if (viewId > lastReadViewId) {
                        isVisible = true;
                        visibleCount++;
                    }
                }
                 else if (type === 'generic_view') { // ADD THIS BLOCK
                    const genericViewId = parseInt(item.dataset.genericId);
                    if (genericViewId > lastReadGenericViewId) {
                        isVisible = true;
                        visibleCount++;
                    }
                }
                
                if (!isVisible) {
                    item.style.display = 'none';
                }
            });

            if (visibleCount === 0) {
                if (notificationBadge) notificationBadge.style.display = 'none';
                if (noNotificationsMsg) noNotificationsMsg.style.display = 'block';
                if (notificationFooter) notificationFooter.style.display = 'none';
            } else {
                if (notificationBadge) {
                    notificationBadge.style.display = 'flex';
                    notificationBadge.textContent = visibleCount;
                }
                if (noNotificationsMsg) noNotificationsMsg.style.display = 'none';
                if (notificationFooter) notificationFooter.style.display = 'block';
            }
        } else {
            // No notifications came from server
            if (notificationBadge) notificationBadge.style.display = 'none';
            if (noNotificationsMsg) noNotificationsMsg.style.display = 'block';
            if (notificationFooter) notificationFooter.style.display = 'none';
        }

        // "Mark all as read" button click listener
        markAllReadBtn?.addEventListener('click', function() {
            let latestJobId = lastReadJobId;
            let latestViewId = lastReadViewId;
            let latestGenericViewId = lastReadGenericViewId;

            // Find the newest IDs for each type
            notificationItems.forEach(item => {
                const type = item.dataset.type;
                if (type === 'job') {
                    const jobId = parseInt(item.dataset.jobId);
                    if (jobId > latestJobId) latestJobId = jobId;
                } else if (type === 'view') {
                    const viewId = parseInt(item.dataset.viewId);
                    if (viewId > latestViewId) latestViewId = viewId;
                }
                else if (type === 'generic_view') { // ADD THIS BLOCK
                    const genericViewId = parseInt(item.dataset.genericId);
                    if (genericViewId > latestGenericViewId) latestGenericViewId = genericViewId;
                }
            });

            // Store the latest IDs in localStorage
            localStorage.setItem(jobStorageKey, latestJobId);
            localStorage.setItem(viewStorageKey, latestViewId);
            localStorage.setItem(genericViewStorageKey, latestGenericViewId);

            // Clear the UI
            notificationItems.forEach(item => {
                item.style.display = 'none';
            });
            
            if (notificationBadge) notificationBadge.style.display = 'none';
            if (noNotificationsMsg) noNotificationsMsg.style.display = 'block';
            if (notificationFooter) notificationFooter.style.display = 'none';
        });
    });
    // === END NOTIFICATION SCRIPT ===
    </script>
</body>
</html>