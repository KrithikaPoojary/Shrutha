<?php
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$page_title = "Your Profile";
include 'header.php';

// Fetch user details
$user_id = $_SESSION['user'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Fetch registration details
$registration = null;
$reg_sql = "SELECT * FROM registrations WHERE email = ? ORDER BY id DESC LIMIT 1";
$stmt_reg = mysqli_prepare($conn, $reg_sql);
mysqli_stmt_bind_param($stmt_reg, "s", $user['email']);
mysqli_stmt_execute($stmt_reg);
$reg_result = mysqli_stmt_get_result($stmt_reg);
$registration = mysqli_fetch_assoc($reg_result);

// If no unique_id exists but registration exists, generate one
if ($registration && empty($registration['unique_id'])) {
    $unique_id = 'EMP' . date('Ym') . str_pad($registration['id'], 5, '0', STR_PAD_LEFT);
    
    // Update the registration with unique ID
    $update_unique_sql = "UPDATE registrations SET unique_id = ? WHERE id = ?";
    $stmt_unique = mysqli_prepare($conn, $update_unique_sql);
    mysqli_stmt_bind_param($stmt_unique, "si", $unique_id, $registration['id']);
    mysqli_stmt_execute($stmt_unique);
    mysqli_stmt_close($stmt_unique);
    
    // Refresh registration data
    $registration['unique_id'] = $unique_id;
}

// Fetch user's photo and job title
$resume_sql = "SELECT photo_path, job_title FROM resumes WHERE email = ? ORDER BY id DESC LIMIT 1";
$stmt_resume = mysqli_prepare($conn, $resume_sql);
mysqli_stmt_bind_param($stmt_resume, "s", $user['email']);
mysqli_stmt_execute($stmt_resume);
$resume_result = mysqli_stmt_get_result($stmt_resume);
$resume_data = mysqli_fetch_assoc($resume_result);
$current_photo = $resume_data ? $resume_data['photo_path'] : null;
$job_title = $resume_data ? $resume_data['job_title'] : null;

// // **FIX:** Define $user_email (lowercase) here, using the $user variable from line 21
$user_email = $user['email']; 

// 1. Helper function for combined, de-duplicated search (profile) views
function get_search_count($conn, $email, $days = null) {
    $sql = "
        SELECT COUNT(DISTINCT employer_id) AS count
        FROM (
            -- Get employers from profile_views
            SELECT employer_id
            FROM profile_views
            WHERE candidate_email = ? 
              AND view_type = 'profile'
    ";
    
    if ($days) {
        $sql .= " AND viewed_at >= NOW() - INTERVAL ? DAY";
    }
    
    $sql .= "
        UNION
            
        -- Get employers from application_views
        SELECT av.employer_id
        FROM application_views av
        JOIN applications a ON av.application_id = a.id
        WHERE a.email = ?
          AND av.view_type = 'profile'
    ";
    
    if ($days) {
        $sql .= " AND av.viewed_at >= NOW() - INTERVAL ? DAY";
    }
    
    $sql .= ") AS combined_views";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($days) {
        // 4 placeholders: email, days, email, days
        mysqli_stmt_bind_param($stmt, "sisi", $email, $days, $email, $days);
    } else {
        // 2 placeholders: email, email
        mysqli_stmt_bind_param($stmt, "ss", $email, $email);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $data ? $data['count'] : 0;
}

// 2. Helper function for action (resume) views from application_views only
function get_action_count($conn, $email, $days = null) {
    $sql = "
        SELECT COUNT(av.id) AS count
        FROM application_views av
        JOIN applications a ON av.application_id = a.id
        WHERE a.email = ?
          AND av.view_type = 'resume'
    ";
    
    if ($days) {
        $sql .= " AND av.viewed_at >= NOW() - INTERVAL ? DAY";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $email, $days);
    } else {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $data ? $data['count'] : 0;
}


// 3. Get all 4 counts using new functions
$search_30_days = get_search_count($conn, $user_email, 30);
$actions_30_days = get_action_count($conn, $user_email, 30);
$search_all_time = get_search_count($conn, $user_email);
$actions_all_time = get_action_count($conn, $user_email);

// 4. Get Recent Viewers (Combined Query)
$recent_views = [];
$views_sql = "
    SELECT 
        COALESCE(c.organization_name, u.name) AS viewer_name, 
        MAX(v.viewed_at) AS last_viewed, 
        v.view_type
    FROM (
        -- Source 1: profile_views
        SELECT 
            pv.employer_id, 
            pv.viewed_at, 
            pv.view_type COLLATE utf8mb4_general_ci AS view_type
        FROM 
            profile_views AS pv
        WHERE 
            pv.candidate_email = ?
            
        UNION ALL
        
        -- Source 2: application_views
        SELECT 
            av.employer_id, 
            av.viewed_at, 
            av.view_type COLLATE utf8mb4_general_ci AS view_type
        FROM 
            application_views AS av
        JOIN 
            applications AS a ON av.application_id = a.id
        WHERE 
            a.email = ?
    ) AS v
    JOIN 
        users AS u ON v.employer_id = u.id AND u.role = 'employer'
    LEFT JOIN 
        companies AS c ON u.email = c.email
    GROUP BY 
        viewer_name, v.view_type
    ORDER BY 
        last_viewed DESC
    LIMIT 5
";
$stmt_views = mysqli_prepare($conn, $views_sql);
if ($stmt_views) {
    // **FIX:** Use $user_email (lowercase) here for both placeholders
    mysqli_stmt_bind_param($stmt_views, "ss", $user_email, $user_email);
    mysqli_stmt_execute($stmt_views);
    $views_result = mysqli_stmt_get_result($stmt_views);
    while ($row = mysqli_fetch_assoc($views_result)) {
        $recent_views[] = $row;
    }
    mysqli_stmt_close($stmt_views);
}

// 5. Helper function for "time ago"
function time_ago($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
    $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
// // Handle form submission
$success_msg = $error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    function sanitize($input) {
        global $conn;
        return mysqli_real_escape_string($conn, trim($input));
    }
    
    // Get all form data
    $name = sanitize($_POST['name']);
    $college_name = sanitize($_POST['college_name'] ?? '');
    $university = sanitize($_POST['university'] ?? '');
    $college_location = sanitize($_POST['college_location'] ?? '');
    $country_code = sanitize($_POST['country_code'] ?? '+91');
    $mobile = sanitize($_POST['mobile'] ?? '');
    $alternate_mobile = sanitize($_POST['alternate_mobile'] ?? '');
    $state = sanitize($_POST['state'] ?? '');
    $district = sanitize($_POST['district'] ?? '');
    $hometown = sanitize($_POST['hometown'] ?? '');
    $dob = sanitize($_POST['dob'] ?? '');
    $permanent_address = sanitize($_POST['permanent_address'] ?? '');
    $pincode = sanitize($_POST['pincode'] ?? '');
    
    // Academic fields
    $sslc_year = sanitize($_POST['sslc_year'] ?? '');
    $sslc_marks = sanitize($_POST['sslc_marks'] ?? '');
    $puc_year = sanitize($_POST['puc_year'] ?? '');
    $puc_marks = sanitize($_POST['puc_marks'] ?? '');
    $puc_stream = sanitize($_POST['puc_stream'] ?? '');
    $sslc_marking_system = sanitize($_POST['sslc_marking_system'] ?? 'Percentage');
    $puc_marking_system = sanitize($_POST['puc_marking_system'] ?? 'Percentage');
    
    // Get pursuing flags FIRST
    $iti_pursuing = (isset($_POST['iti_pursuing']) && $_POST['iti_pursuing'] == '1') ? 1 : 0;
    $diploma_pursuing = (isset($_POST['diploma_pursuing']) && $_POST['diploma_pursuing'] == '1') ? 1 : 0;
    $degree_pursuing = (isset($_POST['degree_pursuing']) && $_POST['degree_pursuing'] == '1') ? 1 : 0;
    $pg_pursuing = (isset($_POST['pg_pursuing']) && $_POST['pg_pursuing'] == '1') ? 1 : 0;

    // Also get the ITI and Diploma fields:
    $iti_course = sanitize($_POST['iti_course'] ?? '');
    $iti_stream = sanitize($_POST['iti_stream'] ?? '');
    $iti_specialization = sanitize($_POST['iti_specialization'] ?? '');
    $iti_mode = sanitize($_POST['iti_mode'] ?? '');
    $iti_year = sanitize($_POST['iti_year'] ?? '');
    $iti_marks = sanitize($_POST['iti_marks'] ?? '0.00'); // readOnly field IS submitted, so this is fine.
    // **FIX**: If pursuing, use existing value from $registration. Otherwise, use POST data.
    $iti_marking_system = $iti_pursuing ? ($registration['iti_marking_system'] ?? 'Percentage') : sanitize($_POST['iti_marking_system'] ?? 'Percentage');

    $diploma_course = sanitize($_POST['diploma_course'] ?? '');
    $diploma_stream = sanitize($_POST['diploma_stream'] ?? '');
    $diploma_specialization = sanitize($_POST['diploma_specialization'] ?? '');
    $diploma_mode = sanitize($_POST['diploma_mode'] ?? '');
    $diploma_year = sanitize($_POST['diploma_year'] ?? '');
    $diploma_marks = sanitize($_POST['diploma_marks'] ?? '0.00');
    // **FIX**:
    $diploma_marking_system = $diploma_pursuing ? ($registration['diploma_marking_system'] ?? 'Percentage') : sanitize($_POST['diploma_marking_system'] ?? 'Percentage');
        
    // Degree fields
    $degree_course = sanitize($_POST['degree_course'] ?? '');
    $degree_stream = sanitize($_POST['degree_stream'] ?? '');
    $degree_year = sanitize($_POST['degree_year'] ?? '');
    $degree_marks = sanitize($_POST['degree_marks'] ?? '0.00');
    // **FIX**:
    $degree_marking_system = $degree_pursuing ? ($registration['degree_marking_system'] ?? 'Percentage') : sanitize($_POST['degree_marking_system'] ?? 'Percentage');
    
    // PG fields
    $pg_course = sanitize($_POST['pg_course'] ?? '');
    $pg_stream = sanitize($_POST['pg_stream'] ?? '');
    $pg_year = sanitize($_POST['pg_year'] ?? '');
    $pg_marks = sanitize($_POST['pg_marks'] ?? '0.00');
    // **FIX**:
    $pg_marking_system = $pg_pursuing ? ($registration['pg_marking_system'] ?? 'Percentage') : sanitize($_POST['pg_marking_system'] ?? 'Percentage');
        
    // Skills and preferences
    $skills = isset($_POST['skills']) ? implode(',', $_POST['skills']) : '';
    $languages = isset($_POST['languages']) ? implode(',', $_POST['languages']) : '';
    $industries = isset($_POST['industries']) ? implode(',', $_POST['industries']) : '';
    $other_skills = sanitize($_POST['other_skills'] ?? '');
    $other_languages = sanitize($_POST['other_languages'] ?? '');
    $other_industries = sanitize($_POST['other_industries'] ?? '');
    $relocation = sanitize($_POST['relocation'] ?? 'No');
    $higher_studies = sanitize($_POST['higher_studies'] ?? 'No');
    $shift_work = sanitize($_POST['shift_work'] ?? 'No');
    $passport = sanitize($_POST['passport'] ?? 'No');
    $driving_license = sanitize($_POST['driving_license'] ?? 'No');
    $experience = sanitize($_POST['experience'] ?? 'No');
    $doctorate = sanitize($_POST['doctorate'] ?? 'No');
    
    // Job title
    $job_title = sanitize($_POST['job_title'] ?? '');

    // === START: Disability Fields ===
    $disability = sanitize($_POST['disability'] ?? 'No');
    $disability_type = sanitize($_POST['disability_type'] ?? '');
    $disability_percentage = sanitize($_POST['disability_percentage'] ?? '');
    $has_udid = sanitize($_POST['has_udid'] ?? 'No');
    $udid_number = sanitize($_POST['udid_number'] ?? '');

    // Get current paths from registration
    $udid_path = $registration['udid_path'] ?? null;
    $disability_certificate_path = $registration['disability_certificate_path'] ?? null;
    $upload_dir_disability = 'uploads/disability/';

    // Handle removal checkboxes
    if (isset($_POST['remove_udid_card']) && $_POST['remove_udid_card'] == '1') {
        if (!empty($udid_path) && file_exists($udid_path)) {
            unlink($udid_path);
        }
        $udid_path = NULL;
    }
    if (isset($_POST['remove_disability_certificate']) && $_POST['remove_disability_certificate'] == '1') {
        if (!empty($disability_certificate_path) && file_exists($disability_certificate_path)) {
            unlink($disability_certificate_path);
        }
        $disability_certificate_path = NULL;
    }

    // Handle new UDID card upload
    if (isset($_FILES['udid_card']) && $_FILES['udid_card']['error'] === UPLOAD_ERR_OK) {
        if (!is_dir($upload_dir_disability)) {
            mkdir($upload_dir_disability, 0755, true);
        }
        $filename = uniqid() . '_udid_' . basename($_FILES['udid_card']['name']);
        $target_path = $upload_dir_disability . $filename;
        
        if (move_uploaded_file($_FILES['udid_card']['tmp_name'], $target_path)) {
            // Delete old file if it exists
            if (!empty($udid_path) && file_exists($udid_path)) {
                unlink($udid_path);
            }
            $udid_path = $target_path;
        } else {
            $error_msg = "Error uploading UDID card.";
        }
    }

    // Handle new Disability Certificate upload
    if (isset($_FILES['disability_certificate']) && $_FILES['disability_certificate']['error'] === UPLOAD_ERR_OK) {
        if (!is_dir($upload_dir_disability)) {
            mkdir($upload_dir_disability, 0755, true);
        }
        $filename = uniqid() . '_cert_' . basename($_FILES['disability_certificate']['name']);
        $target_path = $upload_dir_disability . $filename;
        
        if (move_uploaded_file($_FILES['disability_certificate']['tmp_name'], $target_path)) {
            // Delete old file if it exists
            if (!empty($disability_certificate_path) && file_exists($disability_certificate_path)) {
                unlink($disability_certificate_path);
            }
            $disability_certificate_path = $target_path;
        } else {
            $error_msg = "Error uploading disability certificate.";
        }
    }
    // === END: Disability Fields ===
    
    // Handle resume upload
    $resume_path = $registration['resume_path'] ?? '';
    $no_resume = isset($_POST['no_resume']) ? 1 : 0;
    
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/resumes/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['pdf', 'doc', 'docx'];
        
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            $filename = 'resume_' . uniqid() . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['resume']['tmp_name'], $target_path)) {
                $resume_path = $target_path;
                $no_resume = 0; // Reset no_resume flag if file is uploaded
                
                // Delete old resume file if exists
                if (!empty($registration['resume_path']) && file_exists($registration['resume_path'])) {
                    unlink($registration['resume_path']);
                }
            } else {
                $error_msg = "Error uploading resume file.";
            }
        } else {
            $error_msg = "Only PDF, DOC, and DOCX files are allowed for resume.";
        }
    }
    
    // Update users table
    $update_user_sql = "UPDATE users SET name = ? WHERE id = ?";
    $stmt_user = mysqli_prepare($conn, $update_user_sql);
    mysqli_stmt_bind_param($stmt_user, "si", $name, $user_id);
    
    // Handle photo upload
        $photo_path = $current_photo; // Default: keep the current photo

        // Check if user wants to remove the photo
        if (isset($_POST['remove_photo']) && $_POST['remove_photo'] == '1') {
            
            // Delete the old file if it exists
            if (!empty($current_photo) && file_exists($current_photo)) {
                unlink($current_photo);
            }
            $photo_path = NULL; // Set path to NULL for DB update

        // Check if a new photo is being uploaded (and 'remove' wasn't checked)
        } elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            
            $upload_dir = 'uploads/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                
                // Delete the old photo if it exists
                if (!empty($current_photo) && file_exists($current_photo)) {
                    unlink($current_photo);
                }
                $photo_path = $target_path; // Set path to the new photo
                
            } else {
                // Optional: Set an error message if upload fails
                $error_msg = "Error uploading new profile photo. Please try again.";
            }
        }
    
    // Update or insert resume table for photo and job title
    if ($resume_data) {
        // Update existing resume record
        $update_resume_sql = "UPDATE resumes SET photo_path = ?, job_title = ? WHERE email = ? ORDER BY id DESC LIMIT 1";
        $stmt_resume_update = mysqli_prepare($conn, $update_resume_sql);
        mysqli_stmt_bind_param($stmt_resume_update, "sss", $photo_path, $job_title, $user['email']);
        mysqli_stmt_execute($stmt_resume_update);
    } else {
        // Insert new resume record
        $insert_resume_sql = "INSERT INTO resumes (email, photo_path, job_title, full_name) VALUES (?, ?, ?, ?)";
        $stmt_resume_insert = mysqli_prepare($conn, $insert_resume_sql);
        mysqli_stmt_bind_param($stmt_resume_insert, "ssss", $user['email'], $photo_path, $job_title, $name);
        mysqli_stmt_execute($stmt_resume_insert);
    }
    
    // Update or insert registration table
    if ($registration) {
    $update_reg_sql = "UPDATE registrations SET 
    college_name = ?, university = ?, college_location = ?, 
    full_name = ?, country_code = ?, mobile = ?, alternate_mobile = ?, 
    state = ?, district = ?, hometown = ?, dob = ?, permanent_address = ?, 
    pincode = ?, sslc_year = ?, sslc_marks = ?, puc_year = ?, 
    puc_marks = ?, puc_stream = ?, iti_course = ?, iti_stream = ?,
    iti_specialization = ?, iti_mode = ?, iti_year = ?, iti_marks = ?,
    diploma_course = ?, diploma_stream = ?, diploma_specialization = ?, diploma_mode = ?,
    diploma_year = ?, diploma_marks = ?, degree_course = ?, degree_stream = ?, 
    degree_year = ?, degree_marks = ?, pg_course = ?, pg_stream = ?, 
    pg_year = ?, pg_marks = ?, skills = ?, languages = ?, industries = ?, 
    other_skills = ?, other_languages = ?, other_industries = ?, 
    relocation = ?, higher_studies = ?, shift_work = ?, passport = ?, 
    driving_license = ?, experience = ?, doctorate = ?, resume_path = ?, no_resume = ?,
    sslc_marking_system = ?, puc_marking_system = ?, iti_marking_system = ?,
    diploma_marking_system = ?, degree_marking_system = ?, pg_marking_system = ?,
    iti_pursuing = ?, diploma_pursuing = ?, degree_pursuing = ?, pg_pursuing = ?,
    disability = ?, disability_type = ?, disability_percentage = ?, has_udid = ?,
    udid_number = ?, udid_path = ?, disability_certificate_path = ?
    WHERE id = ?";

    $stmt_reg = mysqli_prepare($conn, $update_reg_sql);
    
    $update_params = [
        $college_name, $university, $college_location, $name, $country_code,
        $mobile, $alternate_mobile, $state, $district, $hometown, $dob,
        $permanent_address, $pincode, $sslc_year, $sslc_marks, $puc_year,
        $puc_marks, $puc_stream, $iti_course, $iti_stream, $iti_specialization,
        $iti_mode, $iti_year, $iti_marks, $diploma_course, $diploma_stream,
        $diploma_specialization, $diploma_mode, $diploma_year, $diploma_marks,
        $degree_course, $degree_stream, $degree_year, $degree_marks, $pg_course, 
        $pg_stream, $pg_year, $pg_marks, $skills, $languages, $industries, 
        $other_skills, $other_languages, $other_industries, $relocation, 
        $higher_studies, $shift_work, $passport, $driving_license, $experience, 
        $doctorate, $resume_path, $no_resume, $sslc_marking_system, 
        $puc_marking_system, $iti_marking_system, $diploma_marking_system, 
        $degree_marking_system, $pg_marking_system, $iti_pursuing, 
        $diploma_pursuing, $degree_pursuing, $pg_pursuing,
        // Add disability fields
        $disability, $disability_type, $disability_percentage, $has_udid,
        $udid_number, $udid_path, $disability_certificate_path,
        // Last param is the ID for WHERE clause
        $registration['id']
    ];
    
    // 61 base fields + 7 disability fields = 68. + 1 id = 69 params.
    // 68 's' + 1 'i'
    $types = str_repeat('s', count($update_params) - 1) . 'i';
    mysqli_stmt_bind_param($stmt_reg, $types, ...$update_params);
    } else {
        // Insert new registration
        $insert_reg_sql = "INSERT INTO registrations (
            college_name, university, college_location, full_name, country_code, 
            mobile, alternate_mobile, state, district, email, hometown, dob, 
            permanent_address, pincode, sslc_year, sslc_marks, puc_year, 
            puc_marks, puc_stream, 
            
            iti_course, iti_stream, iti_specialization, iti_mode, iti_year, iti_marks,
            diploma_course, diploma_stream, diploma_specialization, diploma_mode, diploma_year, diploma_marks,
            degree_course, degree_stream, degree_year, degree_marks, 
            pg_course, pg_stream, pg_year, pg_marks, 
            
            skills, languages, industries, other_skills, other_languages, other_industries, 
            relocation, higher_studies, shift_work, passport, driving_license, 
            experience, doctorate, resume_path, no_resume, 
            
            sslc_marking_system, puc_marking_system, iti_marking_system, 
            diploma_marking_system, degree_marking_system, pg_marking_system,
            
            iti_pursuing, diploma_pursuing, degree_pursuing, pg_pursuing,

            disability, disability_type, disability_percentage, has_udid, 
            udid_number, udid_path, disability_certificate_path
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?, ?, ?
        )"; // 61 '?' + 7 '?' = 68 '?'
        
        $stmt_reg = mysqli_prepare($conn, $insert_reg_sql);
        
        // Create an array of all parameters for the INSERT
        $insert_params = [
            $college_name, $university, $college_location, $name, $country_code,
            $mobile, $alternate_mobile, $state, $district, $user['email'], $hometown, $dob,
            $permanent_address, $pincode, $sslc_year, $sslc_marks, $puc_year,
            $puc_marks, $puc_stream,
            
            // Add missing ITI fields
            $iti_course, $iti_stream, $iti_specialization, $iti_mode, $iti_year, $iti_marks,
            
            // Add missing Diploma fields
            $diploma_course, $diploma_stream, $diploma_specialization, $diploma_mode, $diploma_year, $diploma_marks,
            
            // Degree fields
            $degree_course, $degree_stream, $degree_year, $degree_marks,
            
            // PG fields
            $pg_course, $pg_stream, $pg_year, $pg_marks,
            
            // Skills and preferences
            $skills, $languages, $industries, $other_skills, $other_languages, $other_industries,
            $relocation, $higher_studies, $shift_work, $passport, $driving_license,
            $experience, $doctorate, $resume_path, $no_resume,
            
            // Marking systems
            $sslc_marking_system, $puc_marking_system, $iti_marking_system,
            $diploma_marking_system, $degree_marking_system, $pg_marking_system,
            
            // Pursuing flags
            $iti_pursuing, $diploma_pursuing, $degree_pursuing, $pg_pursuing,

            // Add disability fields
            $disability, $disability_type, $disability_percentage, $has_udid,
            $udid_number, $udid_path, $disability_certificate_path
        ];
        
        // Dynamically create the type string (61 + 7 = 68)
        $types = str_repeat('s', count($insert_params));
        
        // Bind parameters using the spread operator
        mysqli_stmt_bind_param($stmt_reg, $types, ...$insert_params);
    }
    
    // Execute updates
    $user_updated = mysqli_stmt_execute($stmt_user);
    $reg_updated = mysqli_stmt_execute($stmt_reg);
    
    if ($user_updated && $reg_updated) {
        $success_msg = "Profile updated successfully!";
        // Refresh data by re-querying
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        
        // Refresh registration data
        $reg_sql = "SELECT * FROM registrations WHERE email = ? ORDER BY id DESC LIMIT 1";
        $stmt_reg = mysqli_prepare($conn, $reg_sql);
        mysqli_stmt_bind_param($stmt_reg, "s", $user['email']);
        mysqli_stmt_execute($stmt_reg);
        $reg_result = mysqli_stmt_get_result($stmt_reg);
        $registration = mysqli_fetch_assoc($reg_result);
        
        // Refresh resume data
        $resume_sql = "SELECT photo_path, job_title FROM resumes WHERE email = ? ORDER BY id DESC LIMIT 1";
        $stmt_resume = mysqli_prepare($conn, $resume_sql);
        mysqli_stmt_bind_param($stmt_resume, "s", $user['email']);
        mysqli_stmt_execute($stmt_resume);
        $resume_result = mysqli_stmt_get_result($stmt_resume);
        $resume_data = mysqli_fetch_assoc($resume_result);
        $current_photo = $resume_data ? $resume_data['photo_path'] : null;
        $job_title = $resume_data ? $resume_data['job_title'] : null;
    } else {
        $error_msg = "Error updating profile: " . mysqli_error($conn);
    }
}

// Generate initials for avatar
$initials = '';
$name_parts = explode(' ', $user['name']);
if(count($name_parts) >= 2) {
    $initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[count($name_parts)-1], 0, 1));
} else {
    $initials = strtoupper(substr($user['name'], 0, 2));
}

// Pre-fill skills arrays
$current_skills = $registration ? explode(',', $registration['skills']) : [];
$current_languages = $registration ? explode(',', $registration['languages']) : [];
$current_industries = $registration ? explode(',', $registration['industries']) : [];
?>

<div class="container py-4">
    <a href="employee_dashboard.php" class="btn btn-outline-secondary" style="text-decoration: none; position: absolute; left: 40px; box-shadow: none; margin-top: 8px;">
        <i class="fas fa-arrow-left me-2"></i>
    </a>
    <h1 class="mb-4">Your Profile</h1>
    
    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $success_msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error_msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow">
                <div class="card-body text-center">
                    <div class="profile-photo-section mb-4">
                        <?php if ($current_photo && file_exists($current_photo)): ?>
                            <img src="<?= htmlspecialchars($current_photo) ?>" 
                                 alt="Profile Photo" 
                                 class="rounded-circle mb-3"
                                 style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #4361ee;">
                        <?php else: ?>
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                 style="width: 150px; height: 150px; font-size: 3rem; font-weight: bold;">
                                <?= $initials ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <h4><?= htmlspecialchars($user['name']) ?></h4>
                            <p class="text-muted"><?= htmlspecialchars($user['email']) ?></p>
                            <?php if (!empty($job_title)): ?>
                                <p class="text-info">
                                    <i class="fas fa-briefcase me-2"></i>
                                    <?= htmlspecialchars($job_title) ?>
                                </p>
                            <?php elseif ($registration && !empty($registration['degree_course'])): ?>
                                <p classs="text-info">
                                    <i class="fas fa-graduation-cap me-2"></i>
                                    <?= htmlspecialchars($registration['degree_course']) ?>
                                    <?= !empty($registration['degree_stream']) ? ' - ' . htmlspecialchars($registration['degree_stream']) : '' ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="profile-stats">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="fw-bold text-primary">
                                    <?= $registration ? '100%' : '0%' ?>
                                </div>
                                <small class="text-muted">Profile Complete</small>
                            </div>
                            <div class="col-3">
                                <div class="fw-bold text-primary">
                                    <?= $registration ? date('M Y', strtotime($registration['registration_date'])) : 'Not Registered' ?>
                                </div>
                                <small class="text-muted">Registered</small>
                            </div>
                            <div class="col-5">
                                <div class="fw-bold text-primary" style="font-size: 0.9rem;">
                                    <?= htmlspecialchars($registration['unique_id'] ?? '---') ?>
                                </div>
                                <small class="text-muted">Unique ID</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mt-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Profile Performance</h5>
                        <i class="fas fa-info-circle text-muted" data-bs-toggle="tooltip" title="Profile performance based on recruiter interactions"></i>
                    </div>

                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <h4 class="fw-bold mb-0 text-primary"><?= $search_30_days ?></h4>
                            <small class="text-muted">Searches (30 days)</small>
                        </div>
                        <div class="col-6">
                            <h4 class="fw-bold mb-0 text-primary"><?= $actions_30_days ?></h4>
                            <small class="text-muted">Actions (30 days)</small>
                        </div>
                    </div>
                    <div class="row text-center small text-muted">
                        <div class="col-6">
                            <span class="fw-bold"><?= $search_all_time ?></span> All-time
                        </div>
                        <div class="col-6">
                            <span class="fw-bold"><?= $actions_all_time ?></span> All-time
                        </div>
                    </div>

                    <hr>

                    <h6 class="text-muted mb-2">Recent Activity</h6>
                    <?php if (empty($recent_views)): ?>
                        <p class="text-center text-muted small">No recruiter activity yet.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush activity-list">
                            <?php foreach ($recent_views as $view): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-bold d-block">
                                            <?php if ($view['view_type'] == 'profile'): ?>
                                                <i class="fas fa-user-circle text-info me-2"></i>
                                            <?php else: ?>
                                                <i class="fas fa-file-alt text-success me-2"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($view['viewer_name']) ?>
                                        </span>
                                        <small class="text-muted">
                                            Viewed your <?= htmlspecialchars($view['view_type']) ?>
                                        </small>
                                    </div>
                                    <small class="text-muted"><?= time_ago($view['last_viewed']) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card shadow mt-4">
                <div class="card-body">
                    <h5 class="card-title text-center mb-3">Career Corner: Tips for Success</h5>
                    
                    <div class="advice-section mb-3">
                        <h6 class="text-primary">Resume & Profile Tips</h6>
                        <ul class="list-unstyled advice-list">
                            <li><i class="fas fa-check-circle me-2"></i>Tailor your resume</li>
                            <li><i class="fas fa-check-circle me-2"></i>Use keywords</li>
                            <li><i class="fas fa-check-circle me-2"></i>Quantify achievements</li>
                            <li><i class="fas fa-check-circle me-2"></i>Keep it concise</li>
                            <li><i class="fas fa-check-circle me-2"></i>Proofread meticulously</li>
                            <li><i class="fas fa-check-circle me-2"></i>Update your online profile</li>
                            <li><i class="fas fa-check-circle me-2"></i>Showcase your portfolio</li>
                        </ul>
                    </div>

                    <div class="advice-section mb-3">
                        <h6 class="text-primary">Interview Tips</h6>
                        <ul class="list-unstyled advice-list">
                            <li><i class="fas fa-check-circle me-2"></i>Research the company</li>
                            <li><i class="fas fa-check-circle me-2"></i>Practice common questions</li>
                            <li><i class="fas fa-check-circle me-2"></i>Prepare questions to ask</li>
                            <li><i class="fas fa-check-circle me-2"></i>Dress professionally</li>
                            <li><i class="fas fa-check-circle me-2"></i>Arrive early</li>
                            <li><i class="fas fa-check-circle me-2"></i>Send a thank-you note</li>
                        </ul>
                    </div>

                    <div class="advice-section">
                        <h6 class="text-primary">Career Growth Advice</h6>
                        <ul class="list-unstyled advice-list">
                            <li><i class="fas fa-check-circle me-2"></i>Continuous learning</li>
                            <li><i class="fas fa-check-circle me-2"></i>Network effectively</li>
                            <li><i class="fas fa-check-circle me-2"></i>Seek mentorship</li>
                            <li><i class="fas fa-check-circle me-2"></i>Develop soft skills</li>
                            <li><i class="fas fa-check-circle me-2"></i>Set clear goals</li>
                            <li><i class="fas fa-check-circle me-2"></i>Embrace challenges</li>
                            <li><i class="fas fa-check-circle me-2"></i>Maintain a positive attitude</li>
                        </ul>
                    </div>

                </div>
            </div>
            </div>
        
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Profile Information</h5>
                    <span class="badge bg-light text-dark">
                        <?= $registration ? 'Registered' : 'Not Registered' ?>
                    </span>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="section-header mb-3">
                            <h6 class="text-primary"><i class="fas fa-user me-2"></i>Personal Information</h6>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" 
                                       value="<?= htmlspecialchars($user['email']) ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">College/Institute Name</label>
                                <input type="text" name="college_name" class="form-control" 
                                       value="<?= htmlspecialchars($registration['college_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">University/Board</label>
                                <input type="text" name="university" class="form-control" 
                                       value="<?= htmlspecialchars($registration['university'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">College Location</label>
                                <input type="text" name="college_location" class="form-control" 
                                       value="<?= htmlspecialchars($registration['college_location'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="dob" class="form-control" 
                                       value="<?= htmlspecialchars($registration['dob'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="section-header mb-3 mt-4">
                            <h6 class="text-primary"><i class="fas fa-phone me-2"></i>Contact Information</h6>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Mobile Number *</label>
                                <div class="input-group">
                                    <select name="country_code" class="form-select" style="max-width: 120px;">
                                        <option value="+91" <?= ($registration['country_code'] ?? '+91') === '+91' ? 'selected' : '' ?>>+91 (India)</option>
                                        <option value="+1" <?= ($registration['country_code'] ?? '') === '+1' ? 'selected' : '' ?>>+1 (USA)</option>
                                        <option value="+44" <?= ($registration['country_code'] ?? '') === '+44' ? 'selected' : '' ?>>+44 (UK)</option>
                                    </select>
                                    <input type="text" name="mobile" class="form-control" 
                                           value="<?= htmlspecialchars($registration['mobile'] ?? '') ?>" 
                                           placeholder="10-digit mobile number" pattern="[0-9]{10}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Alternate Mobile</label>
                                <input type="text" name="alternate_mobile" class="form-control" 
                                       value="<?= htmlspecialchars($registration['alternate_mobile'] ?? '') ?>" 
                                       pattern="[0-9]{10}">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">State</label>
                                <input type="text" name="state" class="form-control" 
                                       value="<?= htmlspecialchars($registration['state'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">District</label>
                                <input type="text" name="district" class="form-control" 
                                       value="<?= htmlspecialchars($registration['district'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Hometown</label>
                                <input type="text" name="hometown" class="form-control" 
                                       value="<?= htmlspecialchars($registration['hometown'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Permanent Address</label>
                                <textarea name="permanent_address" class="form-control" rows="3"><?= htmlspecialchars($registration['permanent_address'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pincode</label>
                                <input type="text" name="pincode" class="form-control" 
                                       value="<?= htmlspecialchars($registration['pincode'] ?? '') ?>" 
                                       pattern="[0-9]{6}">
                            </div>
                        </div>
                        
                        <div class="section-header mb-3 mt-4">
                            <h6 class="text-primary"><i class="fas fa-graduation-cap me-2"></i>Academic Information</h6>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">SSLC Year</label>
                                <input type="text" name="sslc_year" class="form-control" 
                                    value="<?= htmlspecialchars($registration['sslc_year'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SSLC Marking System</label>
                                <select name="sslc_marking_system" class="form-control marking-system" data-qualification="sslc">
                                    <option value="Percentage" <?= ($registration['sslc_marking_system'] ?? 'Percentage') === 'Percentage' ? 'selected' : '' ?>>Percentage</option>
                                    <option value="CGPA" <?= ($registration['sslc_marking_system'] ?? 'Percentage') === 'CGPA' ? 'selected' : '' ?>>CGPA</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label sslc_marks_label">SSLC Marks (<?= ($registration['sslc_marking_system'] ?? 'Percentage') === 'CGPA' ? 'CGPA' : '%' ?>)</label>
                                <input type="number" name="sslc_marks" class="form-control sslc_marks" 
                                    value="<?= htmlspecialchars($registration['sslc_marks'] ?? '') ?>" 
                                    min="0" 
                                    max="<?= ($registration['sslc_marking_system'] ?? 'Percentage') === 'CGPA' ? '10' : '100' ?>" 
                                    step="<?= ($registration['sslc_marking_system'] ?? 'Percentage') === 'CGPA' ? '0.1' : '0.01' ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">PUC Year</label>
                                <input type="text" name="puc_year" class="form-control" 
                                    value="<?= htmlspecialchars($registration['puc_year'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">PUC Stream</label>
                                <select name="puc_stream" class="form-control">
                                    <option value="">Select Stream</option>
                                    <option value="Science" <?= ($registration['puc_stream'] ?? '') === 'Science' ? 'selected' : '' ?>>Science</option>
                                    <option value="Commerce" <?= ($registration['puc_stream'] ?? '') === 'Commerce' ? 'selected' : '' ?>>Commerce</option>
                                    <option value="Arts" <?= ($registration['puc_stream'] ?? '') === 'Arts' ? 'selected' : '' ?>>Arts</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">PUC Marking System</label>
                                <select name="puc_marking_system" class="form-control marking-system" data-qualification="puc">
                                    <option value="Percentage" <?= ($registration['puc_marking_system'] ?? 'Percentage') === 'Percentage' ? 'selected' : '' ?>>Percentage</option>
                                    <option value="CGPA" <?= ($registration['puc_marking_system'] ?? 'Percentage') === 'CGPA' ? 'selected' : '' ?>>CGPA</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label puc_marks_label">PUC Marks (<?= ($registration['puc_marking_system'] ?? 'Percentage') === 'CGPA' ? 'CGPA' : '%' ?>)</label>
                                <input type="number" name="puc_marks" class="form-control puc_marks" 
                                    value="<?= htmlspecialchars($registration['puc_marks'] ?? '') ?>" 
                                    min="0" 
                                    max="<?= ($registration['puc_marking_system'] ?? 'Percentage') === 'CGPA' ? '10' : '100' ?>" 
                                    step="<?= ($registration['puc_marking_system'] ?? 'Percentage') === 'CGPA' ? '0.1' : '0.01' ?>">
                            </div>
                        </div>

                        <h6 class="mt-4 mb-2" style="font-size: 0.9rem; font-weight: bold; color: #555;">ITI Details</h6>
                        <div class="row mb-2">
                            <div class="col-md-3">
                                <label class="form-label">ITI Course</label>
                                <input type="text" name="iti_course" class="form-control iti-course" 
                                    value="<?= htmlspecialchars($registration['iti_course'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">ITI Stream</label>
                                <input type="text" name="iti_stream" class="form-control iti-stream" 
                                    value="<?= htmlspecialchars($registration['iti_stream'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">ITI Specialization</label>
                                <input type="text" name="iti_specialization" class="form-control iti-specialization" 
                                    value="<?= htmlspecialchars($registration['iti_specialization'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">ITI Mode</label>
                                <input type="text" name="iti_mode" class="form-control iti-mode" 
                                    value="<?= htmlspecialchars($registration['iti_mode'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">ITI Year</label>
                                <input type="text" name="iti_year" class="form-control iti-year" 
                                    value="<?= htmlspecialchars($registration['iti_year'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label iti_marks_label">ITI Marks</label>
                                <input type="number" name="iti_marks" class="form-control iti-marks" 
                                    value="<?= htmlspecialchars($registration['iti_marks'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Marking System</label>
                                <select name="iti_marking_system" class="form-control marking-system iti-marking-system" data-qualification="iti">
                                    <option value="Percentage" <?= ($registration['iti_marking_system'] ?? 'Percentage') === 'Percentage' ? 'selected' : '' ?>>Percentage</option>
                                    <option value="CGPA" <?= ($registration['iti_marking_system'] ?? 'Percentage') === 'CGPA' ? 'selected' : '' ?>>CGPA</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input iti-pursuing pursuing-checkbox" type="checkbox" name="iti_pursuing" value="1" 
                                        <?= ($registration['iti_pursuing'] ?? 0) ? 'checked' : '' ?> data-qual="iti">
                                    <label class="form-check-label">Currently Pursuing</label>
                                </div>
                            </div>
                        </div>

                        <h6 class="mt-4 mb-2" style="font-size: 0.9rem; font-weight: bold; color: #555;">Diploma Details</h6>
                        <div class="row mb-2">
                            <div class="col-md-3">
                                <label class="form-label">Diploma Course</label>
                                <input type="text" name="diploma_course" class="form-control diploma-course" 
                                    value="<?= htmlspecialchars($registration['diploma_course'] ?? '') ?>">
                            </div>
                             <div class="col-md-3">
                                <label class="form-label">Diploma Stream</label>
                                <input type="text" name="diploma_stream" class="form-control diploma-stream" 
                                    value="<?= htmlspecialchars($registration['diploma_stream'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Diploma Specialization</label>
                                <input type="text" name="diploma_specialization" class="form-control diploma-specialization" 
                                    value="<?= htmlspecialchars($registration['diploma_specialization'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Diploma Mode</label>
                                <input type="text" name="diploma_mode" class="form-control diploma-mode" 
                                    value="<?= htmlspecialchars($registration['diploma_mode'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="row mb-3">
                           <div class="col-md-3">
                                <label class="form-label">Diploma Year</label>
                                <input type="text" name="diploma_year" class="form-control diploma-year" 
                                    value="<?= htmlspecialchars($registration['diploma_year'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label diploma_marks_label">Diploma Marks</label>
                                <input type="number" name="diploma_marks" class="form-control diploma-marks" 
                                    value="<?= htmlspecialchars($registration['diploma_marks'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Marking System</label>
                                <select name="diploma_marking_system" class="form-control marking-system diploma-marking-system" data-qualification="diploma">
                                    <option value="Percentage" <?= ($registration['diploma_marking_system'] ?? 'Percentage') === 'Percentage' ? 'selected' : '' ?>>Percentage</option>
                                    <option value="CGPA" <?= ($registration['diploma_marking_system'] ?? 'Percentage') === 'CGPA' ? 'selected' : '' ?>>CGPA</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input diploma-pursuing pursuing-checkbox" type="checkbox" name="diploma_pursuing" value="1" 
                                        <?= ($registration['diploma_pursuing'] ?? 0) ? 'checked' : '' ?> data-qual="diploma">
                                    <label class="form-check-label">Currently Pursuing</label>
                                </div>
                            </div>
                        </div>

                        <h6 class="mt-4 mb-2" style="font-size: 0.9rem; font-weight: bold; color: #555;">Degree Details</h6>
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label class="form-label">Degree Course</label>
                                <input type="text" name="degree_course" class="form-control degree-course" 
                                    value="<?= htmlspecialchars($registration['degree_course'] ?? '') ?>">
                            </div>
                             <div class="col-md-6">
                                <label class="form-label">Degree Stream</label>
                                <input type="text" name="degree_stream" class="form-control degree-stream" 
                                    value="<?= htmlspecialchars($registration['degree_stream'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="row mb-3">
                           <div class="col-md-3">
                                <label class="form-label">Degree Year</label>
                                <input type="text" name="degree_year" class="form-control degree-year" 
                                    value="<?= htmlspecialchars($registration['degree_year'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label degree_marks_label">Degree Marks</label>
                                <input type="number" name="degree_marks" class="form-control degree-marks" 
                                    value="<?= htmlspecialchars($registration['degree_marks'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Marking System</label>
                                <select name="degree_marking_system" class="form-control marking-system degree-marking-system" data-qualification="degree">
                                    <option value="Percentage" <?= ($registration['degree_marking_system'] ?? 'Percentage') === 'Percentage' ? 'selected' : '' ?>>Percentage</option>
                                    <option value="CGPA" <?= ($registration['degree_marking_system'] ?? 'Percentage') === 'CGPA' ? 'selected' : '' ?>>CGPA</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input degree-pursuing pursuing-checkbox" type="checkbox" name="degree_pursuing" value="1" 
                                        <?= ($registration['degree_pursuing'] ?? 0) ? 'checked' : '' ?> data-qual="degree">
                                    <label class="form-check-label">Currently Pursuing</label>
                                </div>
                            </div>
                        </div>

                        <h6 class="mt-4 mb-2" style="font-size: 0.9rem; font-weight: bold; color: #555;">Post Graduation Details</h6>
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label class="form-label">PG Course</label>
                                <input type="text" name="pg_course" class="form-control pg-course" 
                                    value="<?= htmlspecialchars($registration['pg_course'] ?? '') ?>">
                            </div>
                             <div class="col-md-6">
                                <label class="form-label">PG Stream</label>
                                <input type="text" name="pg_stream" class="form-control pg-stream" 
                                    value="<?= htmlspecialchars($registration['pg_stream'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="row mb-3">
                           <div class="col-md-3">
                                <label class="form-label">PG Year</label>
                                <input type="text" name="pg_year" class="form-control pg-year" 
                                    value="<?= htmlspecialchars($registration['pg_year'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label pg_marks_label">PG Marks</label>
                                <input type="number" name="pg_marks" class="form-control pg-marks" 
                                    value="<?= htmlspecialchars($registration['pg_marks'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Marking System</label>
                                <select name="pg_marking_system" class="form-control marking-system pg-marking-system" data-qualification="pg">
                                    <option value="Percentage" <?= ($registration['pg_marking_system'] ?? 'Percentage') === 'Percentage' ? 'selected' : '' ?>>Percentage</option>
                                    <option value="CGPA" <?= ($registration['pg_marking_system'] ?? 'Percentage') === 'CGPA' ? 'selected' : '' ?>>CGPA</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input pg-pursuing pursuing-checkbox" type="checkbox" name="pg_pursuing" value="1" 
                                        <?= ($registration['pg_pursuing'] ?? 0) ? 'checked' : '' ?> data-qual="pg">
                                    <label class="form-check-label">Currently Pursuing</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="section-header mb-3 mt-4">
                            <h6 class="text-primary"><i class="fas fa-code me-2"></i>Skills & Preferences</h6>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Technical Skills</label>
                                <div class="skills-checkbox">
                                    <?php
                                    $all_skills = ['C', 'C++', 'Java', 'Python', 'HTML', 'CSS', 'JavaScript', 'React', 'PHP', 'MySQL'];
                                    foreach ($all_skills as $skill): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="skills[]" 
                                                   value="<?= $skill ?>" 
                                                   <?= in_array($skill, $current_skills) ? 'checked' : '' ?>>
                                            <label class="form-check-label"><?= $skill ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="text" name="other_skills" class="form-control mt-2" 
                                       value="<?= htmlspecialchars($registration['other_skills'] ?? '') ?>" 
                                       placeholder="Other skills (comma separated)">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Languages Known</label>
                                <div class="languages-checkbox">
                                    <?php
                                    $all_languages = ['English', 'Kannada', 'Hindi', 'Tamil', 'Telugu', 'Malayalam'];
                                    foreach ($all_languages as $lang): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="languages[]" 
                                                   value="<?= $lang ?>" 
                                                   <?= in_array($lang, $current_languages) ? 'checked' : '' ?>>
                                            <label class="form-check-label"><?= $lang ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="text" name="other_languages" class="form-control mt-2" 
                                       value="<?= htmlspecialchars($registration['other_languages'] ?? '') ?>" 
                                       placeholder="Other languages">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Industry Preferences</label>
                                <div class="industries-checkbox">
                                    <?php
                                    $all_industries = ['IT&ITES', 'Manufacturing', 'Healthcare', 'Education', 'Banking', 'Retail'];
                                    foreach ($all_industries as $industry): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="industries[]" 
                                                   value="<?= $industry ?>" 
                                                   <?= in_array($industry, $current_industries) ? 'checked' : '' ?>>
                                            <label class="form-check-label"><?= $industry ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="text" name="other_industries" class="form-control mt-2" 
                                       value="<?= htmlspecialchars($registration['other_industries'] ?? '') ?>" 
                                       placeholder="Other industries">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Preferences</label>
                                <div class="preferences">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="relocation" value="Yes" 
                                               <?= ($registration['relocation'] ?? 'No') === 'Yes' ? 'checked' : '' ?>>
                                        <label class="form-check-label">Willing to relocate</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="relocation" value="No" 
                                               <?= ($registration['relocation'] ?? 'No') === 'No' ? 'checked' : '' ?>>
                                        <label class="form-check-label">Not willing to relocate</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="higher_studies" value="Yes" 
                                           id="higher_studies" <?= ($registration['higher_studies'] ?? 'No') === 'Yes' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="higher_studies">Interested in higher studies</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="shift_work" value="Yes" 
                                           id="shift_work" <?= ($registration['shift_work'] ?? 'No') === 'Yes' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="shift_work">Willing for shift work</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="passport" value="Yes" 
                                           id="passport" <?= ($registration['passport'] ?? 'No') === 'Yes' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="passport">Have passport</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="driving_license" value="Yes" 
                                           id="driving_license" <?= ($registration['driving_license'] ?? 'No') === 'Yes' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="driving_license">Have driving license</label>
                                </div>
                            </div>
                        </div>

                        <div class="section-header mb-3 mt-4">
                            <h6 class="text-primary"><i class="fas fa-wheelchair me-2"></i>Disability Details</h6>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Do you have disability?</label>
                            <div class="d-flex">
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="radio" name="disability" id="disability-yes" value="Yes" <?= ($registration['disability'] ?? 'No') === 'Yes' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="disability-yes">Yes</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="disability" id="disability-no" value="No" <?= ($registration['disability'] ?? 'No') === 'No' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="disability-no">No</label>
                                </div>
                            </div>
                        </div>

                        <div id="disability-details-container" style="display: none; border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin-top: 10px;">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Type of disability</label>
                                    <select name="disability_type" class="form-control">
                                        <option value="" disabled <?= empty($registration['disability_type']) ? 'selected' : '' ?>>Select Type</option>
                                        <option value="Physical" <?= ($registration['disability_type'] ?? '') === 'Physical' ? 'selected' : '' ?>>Physical Disability</option>
                                        <option value="Visual" <?= ($registration['disability_type'] ?? '') === 'Visual' ? 'selected' : '' ?>>Visual Impairment</option>
                                        <option value="Hearing" <?= ($registration['disability_type'] ?? '') === 'Hearing' ? 'selected' : '' ?>>Hearing Impairment</option>
                                        <option value="Speech" <?= ($registration['disability_type'] ?? '') === 'Speech' ? 'selected' : '' ?>>Speech Disability</option>
                                        <option value="Intellectual" <?= ($registration['disability_type'] ?? '') === 'Intellectual' ? 'selected' : '' ?>>Intellectual Disability</option>
                                        <option value="Mental" <?= ($registration['disability_type'] ?? '') === 'Mental' ? 'selected' : '' ?>>Mental Illness</option>
                                        <option value="Multiple" <?= ($registration['disability_type'] ?? '') === 'Multiple' ? 'selected' : '' ?>>Multiple Disabilities</option>
                                        <option value="Other" <?= ($registration['disability_type'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">% of disability</label>
                                    <input type="number" name="disability_percentage" class="form-control" placeholder="e.g., 40" min="0" max="100" value="<?= htmlspecialchars($registration['disability_percentage'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="form-group mt-3">
                                <label class="form-label">Do you have UDID card?</label>
                                <div class="d-flex">
                                    <div class="form-check me-3">
                                        <input class="form-check-input" type="radio" name="has_udid" id="udid-yes" value="Yes" <?= ($registration['has_udid'] ?? 'No') === 'Yes' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="udid-yes">Yes</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="has_udid" id="udid-no" value="No" <?= ($registration['has_udid'] ?? 'No') === 'No' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="udid-no">No</label>
                                    </div>
                                </div>
                            </div>

                            <div id="udid-yes-container" style="display: none; margin-top: 15px;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">UDID Number</label>
                                        <input type="text" name="udid_number" class="form-control" placeholder="Enter UDID Number" value="<?= htmlspecialchars($registration['udid_number'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Upload UDID Card</label>
                                        <input type="file" name="udid_card" class="form-control" accept=".pdf,.jpg,.png">
                                        <?php if (!empty($registration['udid_path']) && file_exists($registration['udid_path'])): ?>
                                            <div class="mt-2">
                                                <a href="<?= htmlspecialchars($registration['udid_path']) ?>" target="_blank" class="btn btn-outline-info btn-sm">View Current Card</a>
                                                <div class="form-check form-check-inline ms-2">
                                                    <input class="form-check-input" type="checkbox" name="remove_udid_card" value="1" id="remove_udid_card">
                                                    <label class="form-check-label" for="remove_udid_card">Remove</label>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div id="udid-no-container" style="display: none; margin-top: 15px;">
                                <label class="form-label">Upload Disability Certificate</label>
                                <input type="file" name="disability_certificate" class="form-control" accept=".pdf,.jpg,.png">
                                <?php if (!empty($registration['disability_certificate_path']) && file_exists($registration['disability_certificate_path'])): ?>
                                    <div class="mt-2">
                                        <a href="<?= htmlspecialchars($registration['disability_certificate_path']) ?>" target="_blank" class="btn btn-outline-info btn-sm">View Current Certificate</a>
                                        <div class="form-check form-check-inline ms-2">
                                            <input class="form-check-input" type="checkbox" name="remove_disability_certificate" value="1" id="remove_disability_certificate">
                                            <label class="form-check-label" for="remove_disability_certificate">Remove</label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="section-header mb-3 mt-4">
                            <h6 class="text-primary"><i class="fas fa-file me-2"></i>Resume</h6>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Upload Resume</label>
                                <input type="file" name="resume" class="form-control" accept=".pdf,.doc,.docx">
                                <small class="text-muted">Supported formats: PDF, DOC, DOCX (Max: 5MB)</small>
                                
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="no_resume" value="1" 
                                           id="no_resume" <?= ($registration['no_resume'] ?? 0) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="no_resume">I don't have a resume</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Current Resume</label>
                                <div>
                                    <?php if (!empty($registration['resume_path']) && file_exists($registration['resume_path'])): ?>
                                        <a href="<?= htmlspecialchars($registration['resume_path']) ?>" 
                                           target="_blank" 
                                           class="btn btn-outline-primary btn-sm mb-2">
                                            <i class="fas fa-download me-2"></i>Download Current Resume
                                        </a>
                                        <br>
                                        <small class="text-muted">
                                            File: <?= basename($registration['resume_path']) ?>
                                        </small>
                                    <?php elseif ($registration['no_resume'] ?? 0): ?>
                                        <span class="text-muted">No resume uploaded (user selected "I don't have a resume")</span>
                                    <?php else: ?>
                                        <span class="text-muted">No resume uploaded</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="section-header mb-3 mt-4">
                            <h6 class="text-primary"><i class="fas fa-briefcase me-2"></i>Job Title / Designation</h6>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Current Job Title / Designation</label>
                                <input type="text" name="job_title" class="form-control" 
                                       value="<?= htmlspecialchars($job_title ?? '') ?>" 
                                       placeholder="e.g., Software Engineer, Web Developer, etc.">
                                <small class="text-muted">This will be displayed on your dashboard profile</small>
                            </div>
                        </div>
                        
                        <div class="section-header mb-3 mt-4">
                            <h6 class="text-primary"><i class="fas fa-camera me-2"></i>Profile Photo</h6>
                        </div>
                        
                        <div class="mb-3">
                            <input type="file" name="photo" class="form-control" accept="image/*">
                            <small class="text-muted">Max file size: 2MB. Supported formats: JPG, PNG, GIF</small>
                        </div>
                            <?php if ($current_photo && file_exists($current_photo)): ?>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="remove_photo" value="1" id="remove_photo">
                                <label class="form-check-label" for="remove_photo">
                                    Remove current profile photo
                                </label>
                            </div>
                            <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                            <a href="employee_dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-photo-section {
    position: relative;
}
.section-header {
    border-bottom: 2px solid #4361ee;
    padding-bottom: 0.5rem;
}
.skills-checkbox, .languages-checkbox, .industries-checkbox {
    max-height: 120px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    padding: 10px;
    border-radius: 5px;
}
.form-check-inline {
    margin-right: 15px;
    margin-bottom: 5px;
}
.card {
    transition: transform 0.3s ease;
}
.card:hover {
    transform: translateY(-2px);
}
.profile-stats {
    border-top: 1px solid #dee2e6;
    padding-top: 1rem;
    margin-top: 1rem;
}

/* === START: Added Styles === */
.advice-list li {
    font-size: 0.9rem;
    color: #555;
    margin-bottom: 5px;
    display: flex;
    align-itemss: flex-start;
}

.advice-list li i {
    color: #0d6efd; /* Bootstrap primary */
    font-size: 0.8rem;
    margin-top: 4px; /* Align icon with text */
}

.fa-xs {
    font-size: 0.6rem;
    vertical-align: middle;
}

.activity-list .list-group-item {
    padding: 0.75rem 0.25rem; /* Tighter padding */
    border: 0;
    border-bottom: 1px solid #eee;
}
.activity-list .list-group-item:last-child {
    border-bottom: 0;
}
/* === END: Added Styles === */
</style>

<script>
// Add some interactivity
document.addEventListener('DOMContentLoaded', function() {
    
    // Enable Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    // Function to update marks input based on marking system
    function updateMarksInput(qualification, markingSystem) {
        const marksInput = document.querySelector(`input[name="${qualification}_marks"]`);
        const marksLabel = document.querySelector(`.${qualification}_marks_label`);
        
        if (marksInput && marksLabel) {
            if (markingSystem === 'CGPA') {
                marksLabel.textContent = `${qualification.toUpperCase()} Marks (CGPA)`;
                marksInput.setAttribute('max', '10');
                marksInput.setAttribute('step', '0.1');
                marksInput.setAttribute('placeholder', '0.0 - 10.0');
            } else {
                marksLabel.textContent = `${qualification.toUpperCase()} Marks (%)`;
                marksInput.setAttribute('max', '100');
                marksInput.setAttribute('step', '0.01');
                marksInput.setAttribute('placeholder', '0 - 100');
            }
        }
    }

    // Add event listeners to all marking system dropdowns
    const markingSystemSelects = document.querySelectorAll('.marking-system');
    markingSystemSelects.forEach(select => {
        const qualification = select.getAttribute('data-qualification');
        
        // Set initial state
        updateMarksInput(qualification, select.value);
        
        // Add change event listener
        select.addEventListener('change', function() {
            updateMarksInput(qualification, this.value);
        });
    });

    // Auto-format phone numbers
    const phoneInputs = document.querySelectorAll('input[type="text"][pattern="[0-9]{10}"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').slice(0, 10);
        });
    });
    
    // Auto-format pincode
    const pincodeInput = document.querySelector('input[name="pincode"]');
    if (pincodeInput) {
        pincodeInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });
    }

    // Handle resume checkbox
    const noResumeCheckbox = document.querySelector('input[name="no_resume"]');
    const resumeFileInput = document.querySelector('input[name="resume"]');
    
    if (noResumeCheckbox && resumeFileInput) {
        noResumeCheckbox.addEventListener('change', function() {
            if (this.checked) {
                resumeFileInput.disabled = true;
                resumeFileInput.value = '';
            } else {
                resumeFileInput.disabled = false;
            }
        });

        // Initialize state on page load
        if (noResumeCheckbox.checked) {
            resumeFileInput.disabled = true;
        }
    }

    function handlePursuingCheckbox(checkbox, qualification) {
        // Find all the fields for this qualification
        const yearInput = document.querySelector(`input[name="${qualification}_year"]`);
        const marksInput = document.querySelector(`input[name="${qualification}_marks"]`);
        const markingSystem = document.querySelector(`select[name="${qualification}_marking_system"]`);
        
        if (checkbox.checked) {
            // If pursuing, make marks read-only and clear value
            if (marksInput) {
                marksInput.readOnly = true;
                marksInput.value = ''; // Clear marks
            }
            // Selects must use 'disabled'
            if (markingSystem) {
                markingSystem.disabled = true; 
            }
            // Year should remain editable
            if (yearInput) {
                yearInput.readOnly = false; // Make sure year is editable
            }
        } else {
            // If not pursuing, make fields editable
            if (marksInput) {
                marksInput.readOnly = false;
            }
            if (markingSystem) {
                markingSystem.disabled = false;
            }
            if (yearInput) {
                yearInput.readOnly = false;
            }
        }
    }

    // Add event listeners to all pursuing checkboxes
    const pursuingCheckboxes = [
        { selector: '.iti-pursuing', qualification: 'iti' },
        { selector: '.diploma-pursuing', qualification: 'diploma' },
        { selector: '.degree-pursuing', qualification: 'degree' },
        { selector: '.pg-pursuing', qualification: 'pg' }
    ];

    pursuingCheckboxes.forEach(item => {
        const checkbox = document.querySelector(item.selector);
        if (checkbox) {
            // Get the qualification name (e.g., 'iti', 'degree')
            const qualification = item.qualification; 
            
            checkbox.addEventListener('change', function() {
                handlePursuingCheckbox(this, qualification);
            });
            
            // Initialize state on page load
            handlePursuingCheckbox(checkbox, qualification);
        }
    });

    // === START: Disability JS ===
    const disabilityYes = document.getElementById('disability-yes');
    const disabilityNo = document.getElementById('disability-no');
    const disabilityDetailsContainer = document.getElementById('disability-details-container');
    
    const udidYes = document.getElementById('udid-yes');
    const udidNo = document.getElementById('udid-no');
    const udidYesContainer = document.getElementById('udid-yes-container');
    const udidNoContainer = document.getElementById('udid-no-container');

    function toggleDisabilityFields() {
        if (disabilityYes.checked) {
            disabilityDetailsContainer.style.display = 'block';
            toggleUdidFields(); // Check sub-state
        } else {
            disabilityDetailsContainer.style.display = 'none';
        }
    }

    function toggleUdidFields() {
        // This function should only run if disability is 'Yes'
        if (disabilityYes.checked) {
            if (udidYes.checked) {
                udidYesContainer.style.display = 'block';
                udidNoContainer.style.display = 'none';
            } else { // udidNo.checked
                udidYesContainer.style.display = 'none';
                udidNoContainer.style.display = 'block';
            }
        } else {
            // If disability is 'No', hide both
            udidYesContainer.style.display = 'none';
            udidNoContainer.style.display = 'none';
        }
    }

    // Add event listeners
    if(disabilityYes) disabilityYes.addEventListener('change', toggleDisabilityFields);
    if(disabilityNo) disabilityNo.addEventListener('change', toggleDisabilityFields);
    if(udidYes) udidYes.addEventListener('change', toggleUdidFields);
    if(udidNo) udidNo.addEventListener('change', toggleUdidFields);

    // Initial state check on page load
    toggleDisabilityFields();
    // === END: Disability JS ===
});
</script>

<?php include 'footer.php'; ?>