<?php
require_once 'config.php';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input data
    function sanitize($input) {
        global $conn;
        return mysqli_real_escape_string($conn, trim($input));
    }
    
    // Phone number validation
    function validatePhone($phone) {
        return preg_match('/^[0-9]{10}$/', $phone);
    }
    
    $college_name = sanitize($_POST['college_name']);
    $university = sanitize($_POST['university']);
    $college_location = sanitize($_POST['college_location']);
    $full_name = sanitize($_POST['full_name']);
    $country_code = sanitize($_POST['country_code']);
    $mobile = sanitize($_POST['mobile']);
    $alternate_mobile = isset($_POST['alternate_mobile']) ? sanitize($_POST['alternate_mobile']) : '';
    $state = sanitize($_POST['state']);
    $district = sanitize($_POST['district']);
    $email = sanitize($_POST['email']);
    $hometown = sanitize($_POST['hometown']);
    $dob = sanitize($_POST['dob']);
    $permanent_address = sanitize($_POST['permanent_address']);
    $pincode = sanitize($_POST['pincode']);
    
    // Validate phone numbers
    $errors = [];
    
    if (!validatePhone($mobile)) {
        $errors[] = "Mobile number must be exactly 10 digits";
    }
    
    if (!empty($alternate_mobile) && !validatePhone($alternate_mobile)) {
        $errors[] = "Alternate mobile number must be exactly 10 digits";
    }
    
    // If there are validation errors, return them
    if (!empty($errors)) {
        echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
        exit;
    }

     // Handle resume upload
    $resume_path = null;
    $no_resume = isset($_POST['no_resume']) ? 1 : 0;
    
    // Define allowed types and max size (used for all file uploads)
    $allowed_types = ['application/pdf', 'application/msword', 
                     'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                     'image/jpeg', 'image/png'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!$no_resume && isset($_FILES['resume']) && $_FILES['resume']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/resumes/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = uniqid() . '_' . basename($_FILES['resume']['name']);
        $target_path = $upload_dir . $file_name;
        
        if (in_array($_FILES['resume']['type'], $allowed_types) && 
            $_FILES['resume']['size'] <= $max_size) {
            
            if (move_uploaded_file($_FILES['resume']['tmp_name'], $target_path)) {
                $resume_path = $target_path;
            }
        }
    }
    
    // If no resume and checkbox not checked, return error
    if (!$no_resume && empty($resume_path) && (!isset($_FILES['resume']) || $_FILES['resume']['error'] != UPLOAD_ERR_OK)) {
        echo json_encode(['status' => 'error', 'message' => 'Please upload your resume or check "I don\'t have a resume"']);
        exit;
    }
    
    // ===================================================================
    // START OF CORRECTION
    // ===================================================================
    
    // Academic fields
    $sslc_course = sanitize($_POST['sslc_course'] ?? 'SSLC');
    if ($sslc_course === 'Below SSLC') {
        // Set SSLC stream to indicate Below SSLC
        $sslc_stream = 'Below SSLC';
        $sslc_specialization = ''; // Define it here
        $sslc_year = '';
        $sslc_marks = '';
        $sslc_mode = '';
        $sslc_marking_system = '';
    } else {
        // These values are fixed in the HTML form, so set them directly
        $sslc_stream = 'SSLC'; // Fix: Set directly
        $sslc_specialization = 'Not Applicable'; // Fix: Define the missing variable
        
        // Get the rest of the values from the form
        $sslc_year = sanitize($_POST['sslc_year'] ?? '');
        $sslc_marks = sanitize($_POST['sslc_marks'] ?? '');
        $sslc_mode = sanitize($_POST['sslc_mode'] ?? '');
        $sslc_marking_system = sanitize($_POST['sslc_marking_system'] ?? 'Percentage');
    }

    // ===================================================================
    // END OF CORRECTION
    // ===================================================================

    $puc_course = sanitize($_POST['puc_course'] ?? '');
    $puc_stream = sanitize($_POST['puc_stream'] ?? '');
    $puc_specialization = sanitize($_POST['puc_specialization'] ?? '');
    $puc_mode = sanitize($_POST['puc_mode'] ?? '');
    $puc_year = sanitize($_POST['puc_year'] ?? '');
    $puc_marks = sanitize($_POST['puc_marks'] ?? '');
    $puc_marking_system = sanitize($_POST['puc_marking_system'] ?? 'Percentage'); // New field

    $iti_course = sanitize($_POST['iti_course'] ?? '');
    $iti_stream = sanitize($_POST['iti_stream'] ?? '');
    $iti_specialization = sanitize($_POST['iti_specialization'] ?? '');
    $iti_mode = sanitize($_POST['iti_mode'] ?? '');
    $iti_year = sanitize($_POST['iti_year'] ?? '');
    $iti_marks = sanitize($_POST['iti_marks'] ?? '');
    $iti_marking_system = sanitize($_POST['iti_marking_system'] ?? 'Percentage'); // New field
    $iti_pursuing = (isset($_POST['iti_pursuing']) && $_POST['iti_pursuing'] == '1') ? 1 : 0;

    $diploma_course = sanitize($_POST['diploma_course'] ?? '');
    $diploma_stream = sanitize($_POST['diploma_stream'] ?? '');
    $diploma_specialization = sanitize($_POST['diploma_specialization'] ?? '');
    $diploma_mode = sanitize($_POST['diploma_mode'] ?? '');
    $diploma_year = sanitize($_POST['diploma_year'] ?? '');
    $diploma_marks = sanitize($_POST['diploma_marks'] ?? '');
    $diploma_marking_system = sanitize($_POST['diploma_marking_system'] ?? 'Percentage'); // New field
    $diploma_pursuing = (isset($_POST['diploma_pursuing']) && $_POST['diploma_pursuing'] == '1') ? 1 : 0;

    $degree_course = sanitize($_POST['degree_course'] ?? '');
    $degree_stream = sanitize($_POST['degree_stream'] ?? '');
    $degree_specialization = sanitize($_POST['degree_specialization'] ?? '');
    $degree_mode = sanitize($_POST['degree_mode'] ?? '');
    $degree_year = sanitize($_POST['degree_year'] ?? '');
    $degree_marks = sanitize($_POST['degree_marks'] ?? '');
    $degree_marking_system = sanitize($_POST['degree_marking_system'] ?? 'Percentage'); // New field
    // Handle "Others" option for degree
        if ($degree_course === 'Others') {
            $degree_course = sanitize($_POST['degree_course_other'] ?? '');
            $degree_stream = sanitize($_POST['degree_stream_other'] ?? '');
            $degree_specialization = sanitize($_POST['degree_specialization_other'] ?? '');
        }
    $degree_pursuing = (isset($_POST['degree_pursuing']) && $_POST['degree_pursuing'] == '1') ? 1 : 0; 

    $pg_course = sanitize($_POST['pg_course'] ?? '');
    $pg_stream = sanitize($_POST['pg_stream'] ?? '');
    $pg_specialization = sanitize($_POST['pg_specialization'] ?? '');
    $pg_mode = sanitize($_POST['pg_mode'] ?? '');
    $pg_year = sanitize($_POST['pg_year'] ?? '');
    $pg_marks = sanitize($_POST['pg_marks'] ?? '');
    $pg_marking_system = sanitize($_POST['pg_marking_system'] ?? 'Percentage'); // New field
    // Handle "Others" option for post graduation
    if ($pg_course === 'Others') {
        $pg_course = sanitize($_POST['pg_course_other'] ?? '');
        $pg_stream = sanitize($_POST['pg_stream_other'] ?? '');
        $pg_specialization = sanitize($_POST['pg_specialization_other'] ?? '');
    }
    $pg_pursuing = (isset($_POST['pg_pursuing']) && $_POST['pg_pursuing'] == '1') ? 1 : 0;
    
    $doctorate = sanitize($_POST['doctorate'] ?? 'No');
    $experience = sanitize($_POST['experience'] ?? 'No');
    
    
    
    // Skills & Aspirations
    $skills = sanitize($_POST['skills'] ?? '');
    $languages = sanitize($_POST['languages'] ?? '');
    $industries = sanitize($_POST['industries'] ?? '');
    $other_skills = sanitize($_POST['other_skills'] ?? '');
    $other_languages = sanitize($_POST['other_languages'] ?? '');
    $other_industries = sanitize($_POST['other_industries'] ?? '');
    $relocation = sanitize($_POST['relocation'] ?? 'No');
    
    // Preferences
    $higher_studies = sanitize($_POST['higher_studies'] ?? 'No');
    $shift_work = sanitize($_POST['shift_work'] ?? 'No');
    $passport = sanitize($_POST['passport'] ?? 'No');
    $driving_license = sanitize($_POST['driving_license'] ?? 'No');

    // ===================================================================
    // START OF DISABILITY SECTION (PHP)
    // ===================================================================
    $disability = sanitize($_POST['disability'] ?? 'No');
    $disability_type = '';
    $disability_percentage = '';
    $has_udid = 'No';
    $udid_number = '';
    $udid_path = null;
    $disability_certificate_path = null;

    if ($disability === 'Yes') {
        $disability_type = sanitize($_POST['disability_type'] ?? '');
        $disability_percentage = sanitize($_POST['disability_percentage'] ?? '');
        $has_udid = sanitize($_POST['has_udid'] ?? 'No');
        
        $upload_dir_disability = 'uploads/disability/';
        if (!is_dir($upload_dir_disability)) {
            mkdir($upload_dir_disability, 0755, true);
        }
        
        if ($has_udid === 'Yes') {
            $udid_number = sanitize($_POST['udid_number'] ?? '');
            
            // Handle UDID card upload
            if (isset($_FILES['udid_card']) && $_FILES['udid_card']['error'] == UPLOAD_ERR_OK) {
                if (in_array($_FILES['udid_card']['type'], $allowed_types) && $_FILES['udid_card']['size'] <= $max_size) {
                    $file_name = uniqid() . '_udid_' . basename($_FILES['udid_card']['name']);
                    $target_path = $upload_dir_disability . $file_name;
                    if (move_uploaded_file($_FILES['udid_card']['tmp_name'], $target_path)) {
                        $udid_path = $target_path;
                    }
                }
            }
        } else {
            // Handle Disability Certificate upload
            if (isset($_FILES['disability_certificate']) && $_FILES['disability_certificate']['error'] == UPLOAD_ERR_OK) {
                 if (in_array($_FILES['disability_certificate']['type'], $allowed_types) && $_FILES['disability_certificate']['size'] <= $max_size) {
                    $file_name = uniqid() . '_cert_' . basename($_FILES['disability_certificate']['name']);
                    $target_path = $upload_dir_disability . $file_name;
                    if (move_uploaded_file($_FILES['disability_certificate']['tmp_name'], $target_path)) {
                        $disability_certificate_path = $target_path;
                    }
                }
            }
        }
    }
    // ===================================================================
    // END OF DISABILITY SECTION (PHP)
    // ===================================================================
    
    // Prepare and execute SQL statement
    $sql = "INSERT INTO registrations (
        college_name, university, college_location, full_name, country_code, 
        mobile, alternate_mobile, state, district, email, hometown, dob, 
        permanent_address, pincode, sslc_stream, sslc_specialization, 
        sslc_mode, sslc_year, sslc_marks, puc_course, puc_stream, 
        puc_specialization, puc_mode, puc_year, puc_marks, iti_course, 
        iti_stream, iti_specialization, iti_mode, iti_year, iti_marks,
        diploma_course, diploma_stream, diploma_specialization, diploma_mode, 
        diploma_year, diploma_marks, degree_course, degree_stream, 
        degree_specialization, degree_mode, degree_year, degree_marks,
        pg_course, pg_stream, pg_specialization, pg_mode, pg_year, pg_marks,
        doctorate, experience, skills, languages, industries, other_skills, 
        other_languages, other_industries, relocation, higher_studies, 
        shift_work, passport, driving_license, sslc_marking_system, 
        puc_marking_system, iti_marking_system, diploma_marking_system, 
        degree_marking_system, pg_marking_system, resume_path, no_resume, 
        iti_pursuing, diploma_pursuing, degree_pursuing, pg_pursuing,
        
        disability, disability_type, disability_percentage, has_udid, 
        udid_number, udid_path, disability_certificate_path
        
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; // Added 7 new '?'

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        // Detailed error for debugging
        echo json_encode(['status' => 'error', 'message' => 'SQL Prepare Failed: ' . mysqli_error($conn)]);
        exit;
    }

    // Note: 81 parameters total (74 + 7)
    mysqli_stmt_bind_param($stmt, "sssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssiiiii" . "sssssss",      
        $college_name, $university, $college_location, $full_name, $country_code,
        $mobile, $alternate_mobile, $state, $district, $email, $hometown, $dob,
        $permanent_address, $pincode, $sslc_stream, $sslc_specialization, // $sslc_specialization is now defined
        $sslc_mode, $sslc_year, $sslc_marks, $puc_course, $puc_stream,
        $puc_specialization, $puc_mode, $puc_year, $puc_marks, $iti_course,
        $iti_stream, $iti_specialization, $iti_mode, $iti_year, $iti_marks,
        $diploma_course, $diploma_stream, $diploma_specialization, $diploma_mode,
        $diploma_year, $diploma_marks, $degree_course, $degree_stream,
        $degree_specialization, $degree_mode, $degree_year, $degree_marks,
        $pg_course, $pg_stream, $pg_specialization, $pg_mode, $pg_year, $pg_marks,
        $doctorate, $experience, $skills, $languages, $industries, $other_skills,
        $other_languages, $other_industries, $relocation, $higher_studies,
        $shift_work, $passport, $driving_license, $sslc_marking_system,
        $puc_marking_system, $iti_marking_system, $diploma_marking_system,
        $degree_marking_system, $pg_marking_system, $resume_path, $no_resume, 
        $iti_pursuing, $diploma_pursuing, $degree_pursuing, $pg_pursuing,
        
        // Added disability fields
        $disability, $disability_type, $disability_percentage, $has_udid, 
        $udid_number, $udid_path, $disability_certificate_path
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $registration_id = mysqli_insert_id($conn);
        
        // Generate unique ID: EMP + YearMonth + 5-digit sequence
        $unique_id = 'EMP' . date('Ym') . str_pad($registration_id, 5, '0', STR_PAD_LEFT);
        
        // Update with unique ID
        $update_sql = "UPDATE registrations SET unique_id = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $unique_id, $registration_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            echo json_encode(['status' => 'success', 'unique_id' => $unique_id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Registration successful but unique ID generation failed']);
        }
        
        mysqli_stmt_close($update_stmt);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Execute Failed: ' . mysqli_stmt_error($stmt)]);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Registration | Talent Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
          .form-control.error {
        border-color: #ff3860;
        box-shadow: 0 0 0 2px rgba(255, 56, 96, 0.2);
    }

    .error-message {
        color: #ff3860;
        font-size: 0.875rem;
        margin-top: 5px;
        display: none;
    }
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    .full-width {
        grid-column: 1 / -1;
    }
    
    .phone-input {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 20px;
    }
    
    .date-picker-container {
        position: relative;
        z-index: 0;
    }
    
    .calendar-icon{
        position:absolute;
        right: 65px;
        top:69%;
        transform:translateY(-50%);
        cursor:pointer;
        color:#6c757d;
        z-index: 1;
    }
    
    /* Academic table improvements */
    .table-container {
        overflow-x: auto;
        margin-bottom: 20px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
    }
    
    .academic-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
        min-width: 900px; /* Ensure table has minimum width */
    }
    
    .academic-table th,
    .academic-table td {
        padding: 10px 8px;
        border: 1px solid #e0e0e0;
        text-align: left;
        vertical-align: middle;
    }
    
    .academic-table th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #fff;
    }
    
    .academic-table input[type="text"],
    .academic-table input[type="number"],
    .academic-table select {
        width: 100%;
        padding: 6px 8px;
        border: 1px solid #d0d0d0;
        border-radius: 4px;
        font-size: 0.875rem;
        box-sizing: border-box;
    }
    
    .academic-table input:disabled {
        background-color: #f5f5f5;
        color: #999;
        cursor: not-allowed;
    }
    
    .marking-system {
        width: 100px;
    }
    
    .marks-input {
        width: 100px;
    }
    
    .optional-column {
        min-width: 120px;
    }
    
    /* Ensure all columns are visible */
    .academic-table th:last-child,
    .academic-table td:last-child {
        display: table-cell;
        width: 120px;
    }
    .review-section {
    margin-bottom: 25px;
    padding: 15px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background-color: #f9f9f9;
}

.review-section h4 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #2c3e50;
    border-bottom: 2px solid #3498db;
    padding-bottom: 8px;
}

.review-item {
    display: flex;
    margin-bottom: 10px;
    padding: 8px;
    background-color: white;
    border-radius: 4px;
    border-left: 4px solid #3498db;
}

.review-label {
    font-weight: 600;
    min-width: 200px;
    color: #2c3e50;
}

.review-value {
    flex: 1;
    color: #34495e;
}

@media (max-width: 768px) {
    .review-item {
        flex-direction: column;
    }
    
    .review-label {
        min-width: auto;
        margin-bottom: 5px;
    }
}
/* Resume upload styling */
.resume-preview {
    margin-top: 10px;
    padding: 10px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    background-color: #f9f9f9;
}

.resume-preview h5 {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.resume-preview a {
    color: #3498db;
    text-decoration: none;
}

.resume-preview a:hover {
    text-decoration: underline;
}
.btn-view-resume {
    display: inline-block;
    padding: 4px 12px;
    margin-left: 15px;
    background-color: #3498db; /* A nice blue color */
    color: white;
    text-align: center;
    text-decoration: none;
    border-radius: 5px;
    font-size: 0.85rem;
    font-weight: 500;
    vertical-align: middle;
    border: none;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.btn-view-resume:hover {
    background-color: #2980b9; /* A darker blue on hover */
    color: white;
}
.form-group.error label {
    color: #ff3860;
}

.form-group.error input[type="file"] {
    border-color: #ff3860;
    box-shadow: 0 0 0 2px rgba(255, 56, 96, 0.2);
}

.checkbox-container.error label {
    color: #ff3860;
}
<style>
.academic-table th:nth-child(9),
.academic-table td:nth-child(9) {
    width: 120px;
    text-align: center;
}

.pursuing-checkbox {
    margin-right: 5px;
}

.academic-table td:nth-child(9) label {
    font-size: 0.8rem;
    cursor: pointer;
}

/* Styling for nested conditional fields */
#disability-details-container {
    padding: 15px;
    margin-top: 10px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background-color: #fcfcfc;
}
</style>
</style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Employee Registration Portal</h1>
            <p>Join our talent network and connect with top employers. Complete your profile to unlock career opportunities.</p>
        </div>
        
        <div class="registration-card">
            <div class="card-header">
                <h2>Create Your Professional Profile</h2>
            </div>
            
            <div class="card-body">
                <div class="progress-container">
                    <div class="progress-steps">
                        <div class="progress-bar" style="width: 0%;"></div>
                        <div class="step completed" data-step="1">
                            <div class="step-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <span class="step-label">Personal</span>
                        </div>
                        <div class="step" data-step="2">
                            <div class="step-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <span class="step-label">Academic</span>
                        </div>
                        <div class="step" data-step="3">
                            <div class="step-icon">
                                <i class="fas fa-code"></i>
                            </div>
                            <span class="step-label">Skills</span>
                        </div>
                        <div class="step" data-step="4">
                            <div class="step-icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <span class="step-label">Preferences</span>
                        </div>
                        <div class="step" data-step="5">
                            <div class="step-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <span class="step-label">Review</span>
                        </div>
                    </div>
                </div>
                
                <form id="registrationForm" method="POST" enctype="multipart/form-data">
                    <div class="step-content active" id="step1">
                        <div class="form-step-header">
                            <h3>Personal Information</h3>
                            <p>Tell us about yourself and your educational background</p>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <input type="text" name="college_name" class="form-control" placeholder=" " required>
                                <label>College/Institute Name *</label>
                                <div class="error-message">Please enter your college name</div>
                            </div>
                            
                           <div class="form-group">
                            <select name="university" class="form-control" required>
                                <option value="" selected disabled></option>
                                <optgroup label="Karnataka">
                                    <option value="Visvesvaraya Technological University (VTU)">Visvesvaraya Technological University (VTU)</option>
                                    <option value="Bangalore University">Bangalore University</option>
                                    <option value="Mysore University">Mysore University</option>
                                    <option value="Gulbarga University">Gulbarga University</option>
                                    <option value="Mangalore University">Mangalore University</option>
                                    <option value="Kuvempu University">Kuvempu University</option>
                                    <option value="Karnataka State Open University">Karnataka State Open University</option>
                                    <option value="Rajiv Gandhi University of Health Sciences">Rajiv Gandhi University of Health Sciences</option>
                                    <option value="Karnataka State Law University">Karnataka State Law University</option>
                                    <option value="Karnataka State Women's University">Karnataka State Women's University</option>
                                    <option value="Karnataka Veterinary, Animal and Fisheries Sciences University">Karnataka Veterinary, Animal and Fisheries Sciences University</option>
                                    <option value="University of Agricultural Sciences, Bangalore">University of Agricultural Sciences, Bangalore</option>
                                    <option value="University of Agricultural Sciences, Dharwad">University of Agricultural Sciences, Dharwad</option>
                                    <option value="Rani Channamma University">Rani Channamma University</option>
                                    <option value="Karnataka State Akkamahadevi Women's University">Karnataka State Akkamahadevi Women's University</option>
                                    <option value="National Law School of India University">National Law School of India University</option>
                                    <option value="National Institute of Technology Karnataka">National Institute of Technology Karnataka</option>
                                    <option value="Indian Institute of Science">Indian Institute of Science</option>
                                    <option value="Indian Institute of Management Bangalore">Indian Institute of Management Bangalore</option>
                                </optgroup>
                                <optgroup label="Other States">
                                    <option value="Anna University">Anna University</option>
                                    <option value="Mumbai University">Mumbai University</option>
                                    <option value="Delhi University">Delhi University</option>
                                    <option value="Calcutta University">Calcutta University</option>
                                    <option value="JNTU">Jawaharlal Nehru Technological University (JNTU)</option>
                                    <option value="Osmania University">Osmania University</option>
                                    <option value="Pune University">Pune University</option>
                                    <option value="Bharathiar University">Bharathiar University</option>
                                    <option value="Amity University">Amity University</option>
                                    <option value="VIT University">VIT University</option>
                                    <option value="SRM University">SRM University</option>
                                    <option value="Manipal University">Manipal University</option>
                                    <option value="Christ University">Christ University</option>
                                    <option value="IIT Bombay">Indian Institute of Technology Bombay</option>
                                    <option value="IIT Delhi">Indian Institute of Technology Delhi</option>
                                    <option value="IIT Madras">Indian Institute of Technology Madras</option>
                                    <option value="IIT Kharagpur">Indian Institute of Technology Kharagpur</option>
                                    <option value="IIT Kanpur">Indian Institute of Technology Kanpur</option>
                                    <option value="IIT Roorkee">Indian Institute of Technology Roorkee</option>
                                    <option value="IIT Guwahati">Indian Institute of Technology Guwahati</option>
                                    <option value="IIT Hyderabad">Indian Institute of Technology Hyderabad</option>
                                    <option value="NIT Trichy">National Institute of Technology Trichy</option>
                                    <option value="NIT Surathkal">National Institute of Technology Karnataka</option>
                                    <option value="BITS Pilani">Birla Institute of Technology & Science</option>
                                    <option value="VIT Vellore">Vellore Institute of Technology</option>
                                    <option value="SRM University">SRM Institute of Science and Technology</option>
                                    <option value="LPU">Lovely Professional University</option>
                                    <option value="Symbiosis">Symbiosis International University</option>
                                    <option value="University of Delhi">University of Delhi</option>
                                    <option value="University of Mumbai">University of Mumbai</option>
                                    <option value="University of Calcutta">University of Calcutta</option>
                                    <option value="University of Madras">University of Madras</option>
                                    <option value="JNU">Jawaharlal Nehru University</option>
                                    <option value="Banaras Hindu University">Banaras Hindu University</option>
                                    <option value="Aligarh Muslim University">Aligarh Muslim University</option>
                                    <option value="Jadavpur University">Jadavpur University</option>
                                    <option value="Andhra University">Andhra University</option>
                                    <option value="Punjab University">Panjab University</option>
                                    <option value="University of Rajasthan">University of Rajasthan</option>
                                    <option value="Gujarat University">Gujarat University</option>
                                    <option value="Other">Other University/Board</option>
                                </optgroup>
                            </select>
                            <label>University/Board *</label>
                            <div class="error-message">Please select your university</div>
                        </div>
                            
                            <div class="form-group">
                                <input type="text" name="college_location" class="form-control" placeholder=" " required>
                                <label>College/Institute Location *</label>
                                <div class="error-message">Please enter college location</div>
                            </div>
                            
                            <div class="form-group" >
                                <input type="text" name="full_name" class="form-control" placeholder=" " required>
                                <label>Full Name *</label>
                                <div class="error-message">Please enter your full name</div>
                            </div>
                            
                          <div class="phone-input full-width">
                            <div class="form-group" style="margin-bottom: 20px;">
                                <select name="country_code" id="country_code" class="form-control" required>
                                    <option value="+91" selected>+91 (India)</option>
                                    <option value="+1">+1 (USA)</option>
                                    <option value="+44">+44 (UK)</option>
                                    <option value="+65">+65 (Singapore)</option>
                                    <option value="+61">+61 (Australia)</option>
                                </select>
                                <label>Country Code *</label>
                            </div>
                            <div class="form-group" style="margin-bottom: 20px;">
                                <input type="tel" name="mobile" class="form-control" placeholder=" " pattern="[0-9]{10}" maxlength="10" required>
                                <label>Mobile No. *</label>
                                <div class="error-message">Please enter a valid 10-digit mobile number</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <input type="tel" name="alternate_mobile" class="form-control" placeholder=" " pattern="[0-9]{10}" maxlength="10">
                            <label>Alternate Mobile No.</label>
                            <div class="error-message">Please enter a valid 10-digit number</div>
                        </div>
                            
                            <div class="form-group">
                                <select name="state" id="state" class="form-control" required>
                                    <option value="" selected disabled></option>
                                    <option value="Karnataka">Karnataka</option>
                                    <option value="Tamil Nadu">Tamil Nadu</option>
                                    <option value="Maharashtra">Maharashtra</option>
                                    <option value="Kerala">Kerala</option>
                                    <option value="Delhi">Delhi</option>
                                    <option value="Andhra Pradesh">Andhra Pradesh</option>
                                    <option value="Telangana">Telangana</option>
                                    <option value="West Bengal">West Bengal</option>
                                    <option value="Gujarat">Gujarat</option>
                                    <option value="Rajasthan">Rajasthan</option>
                                </select>
                                <label>State *</label>
                                <div class="error-message">Please select your state</div>
                            </div>
                            
                            <div class="form-group">
                                <select name="district" id="district" class="form-control" required>
                                    <option value="" selected disabled>Select District</option>
                                </select>
                                <label>District *</label>
                                <div class="error-message">Please select your district</div>
                            </div>
                            
                            <div class="form-group">
                                <input type="email" name="email" class="form-control" placeholder=" " required>
                                <label>Email *</label>
                                <div class="error-message">Please enter a valid email address</div>
                            </div>
                            
                            <div class="form-group">
                                <input type="text" name="hometown" class="form-control" placeholder=" " required>
                                <label>Hometown/City/Village *</label>
                                <div class="error-message">Please enter your hometown</div>
                            </div>
                            <i class="fas fa-calendar-alt calendar-icon" id="calendarTrigger"></i>
                            <div class="form-group date-picker-container">
                                <label>Date of Birth *</label>
                                <input type="date" name="dob" id="dob" class="form-control" placeholder=" " required max="">
                                 
                                <div class="error-message">Please select your date of birth</div>
                            </div>
                            
                            <div class="form-group full-width">
                                <textarea name="permanent_address" class="form-control" placeholder=" " rows="3" required></textarea>
                                <label>Permanent Address *</label>
                                <div class="error-message">Please enter your permanent address</div>
                            </div>
                            
                            <div class="form-group">
                                <input type="text" name="pincode" class="form-control" placeholder=" " pattern="[0-9]{6}" required>
                                <label>PinCode *</label>
                                <div class="error-message">Please enter a valid 6-digit pincode</div>
                            </div>
                        </div>
                        
                        <div class="form-footer">
                            <button type="button" class="btn btn-prev" disabled>
                                <i class="fas fa-arrow-left"></i> Previous
                            </button>
                            <button type="button" class="btn btn-next next-step">
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="step-content" id="step2">
                        <div class="form-step-header">
                            <h3>Academic Details</h3>
                            <p>Provide your educational qualifications (SSLC is required, others are optional)</p>
                        </div>
                        <div class="table-container">
                            <table class="academic-table">
                                <thead>
                                    <tr>
                                        <th>Qualification</th>
                                        <th>Course</th>
                                        <th class="optional-column">Stream</th>
                                        <th class="optional-column">Specialization</th>
                                        <th>Mode</th>
                                        <th>Year</th>
                                        <th>Marking System</th>
                                        <th>Marks</th>
                                        <th>Currently Pursuing</th> </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>SSLC *</td>
                                        <td>
                                            <select name="sslc_course" required>
                                                <option value="SSLC" selected>SSLC</option>
                                                <option value="Below SSLC">Below SSLC</option>
                                            </select>
                                        </td>
                                        <td class="optional-column">
                                            <input type="text" name="sslc_stream" value="SSLC" disabled style="background-color: #f5f5f5; color: #999;">
                                        </td>
                                        <td class="optional-column">
                                            <input type="text" name="sslc_specialization" value="Not Applicable" disabled style="background-color: #f5f5f5; color: #999;">
                                        </td>
                                        <td>
                                            <select name="sslc_mode" required>
                                                <option value="Regular" selected>Regular</option>
                                                <option value="Correspondence">Correspondence</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="sslc_year" class="year-select" required>
                                                <option value="" disabled selected>Select</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="sslc_marking_system" class="marking-system">
                                                <option value="Percentage">%</option>
                                                <option value="CGPA">CGPA</option>
                                            </select>
                                        </td>
                                        <td><input type="number" name="sslc_marks" class="marks-input" min="0" max="100" step="0.01" required></td>
                                        <td></td>
                                    </tr>

                                    <tr>
                                        <td>PUC</td>
                                        <td>
                                            <select name="puc_course">
                                                <option value="" selected>Not Completed</option>
                                                <option value="PUC">PUC</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="puc_stream" disabled>
                                                <option value="Science" selected>Science</option>
                                                <option value="Commerce">Commerce</option>
                                                <option value="Arts">Arts</option>
                                            </select>
                                        </td>
                                        <td><input type="text" name="puc_specialization" placeholder="Specialization" disabled></td>
                                        <td>
                                            <select name="puc_mode" disabled>
                                                <option value="Regular" selected>Regular</option>
                                                <option value="Correspondence">Correspondence</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="puc_year" class="year-select" disabled>
                                                <option value="" disabled selected>Select</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="puc_marking_system" class="marking-system" disabled>
                                                <option value="Percentage">%</option>
                                                <option value="CGPA">CGPA</option>
                                            </select>
                                        </td>
                                        <td><input type="number" name="puc_marks" class="marks-input" min="0" max="100" step="0.01" disabled></td>
                                        <td></td>
                                    </tr>

                                    <tr>
                                        <td>ITI</td>
                                        <td>
                                            <select name="iti_course">
                                                <option value="" selected>Not Completed</option>
                                                <option value="Electrician">Electrician</option>
                                                <option value="Fitter">Fitter</option>
                                                <option value="Mechanic">Mechanic</option>
                                                <option value="Carpenter">Carpenter</option>
                                                <option value="Welder">Welder</option>
                                                <option value="MRAC">MRAC</option>
                                                <option value="Others">Others</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="iti_stream" disabled>
                                                <option value="" selected disabled>Select</option>
                                                <option value="Engineering">Engineering</option>
                                                <option value="Non-Engineering">Non-Engineering</option>
                                            </select>
                                        </td>
                                        <td><input type="text" name="iti_specialization" placeholder="Specialization" disabled></td>
                                        <td>
                                            <select name="iti_mode" disabled>
                                                <option value="Regular" selected>Regular</option>
                                                <option value="Correspondence">Correspondence</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="iti_year" class="year-select" disabled>
                                                <option value="" disabled selected>Select</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="iti_marking_system" class="marking-system" disabled>
                                                <option value="Percentage">%</option>
                                                <option value="CGPA">CGPA</option>
                                            </select>
                                        </td>
                                        <td><input type="number" name="iti_marks" class="marks-input" min="0" max="100" step="0.01" disabled></td>
                                        <td>
                                            <input type="checkbox" name="iti_pursuing" class="pursuing-checkbox" disabled>
                                            <label>Pursuing</label>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Diploma</td>
                                        <td>
                                            <select name="diploma_course" class="course-select">
                                                <option value="" selected>Not Completed</option>
                                                <option value="Mechanical Engineering">Mechanical Engineering</option>
                                                <option value="Civil Engineering">Civil Engineering</option>
                                                <option value="Electrical Engineering">Electrical Engineering</option>
                                                <option value="Electronics Engineering">Electronics Engineering</option>
                                                <option value="Computer Science Engineering">Computer Science Engineering</option>
                                                <option value="Information Science">Information Science</option>
                                                <option value="Automobile Engineering">Automobile Engineering</option>
                                                <option value="Chemical Engineering">Chemical Engineering</option>
                                                <option value="Biotechnology">Biotechnology</option>
                                                <option value="Architecture">Architecture</option>
                                                <option value="Pharmacy">Pharmacy</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="diploma_stream" class="stream-select" disabled>
                                                <option value="" selected disabled>Select</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="diploma_specialization" class="specialization-select" disabled>
                                                <option value="" selected disabled>Select</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="diploma_mode" disabled>
                                                <option value="Regular" selected>Regular</option>
                                                <option value="Correspondence">Correspondence</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="diploma_year" class="year-select" disabled>
                                                <option value="" disabled selected>Select</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="diploma_marking_system" class="marking-system" disabled>
                                                <option value="Percentage">%</option>
                                                <option value="CGPA">CGPA</option>
                                            </select>
                                        </td>
                                        <td><input type="number" name="diploma_marks" class="marks-input" min="0" max="100" step="0.01" disabled></td>
                                        <td>
                                            <input type="checkbox" name="diploma_pursuing" class="pursuing-checkbox" disabled>
                                            <label>Pursuing</label>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Degree</td>
                                        <td>
                                            <select name="degree_course" class="course-select">
                                                <option value="" selected>Not Completed</option>
                                                <option value="B.Tech">B.Tech</option>
                                                <option value="B.E">B.E</option>
                                                <option value="B.Sc">B.Sc</option>
                                                <option value="B.Com">B.Com</option>
                                                <option value="BBA">BBA</option>
                                                <option value="BA">BA</option>
                                                <option value="B.Arch">B.Arch</option>
                                                <option value="B.Pharm">B.Pharm</option>
                                                <option value="BHM">BHM (Hotel Management)</option>
                                                <option value="BCA">BCA</option>
                                                <option value="Others">Others</option>
                                            </select>
                                            <input type="text" name="degree_course_other" class="course-other-input" style="display: none; margin-top: 5px;" placeholder="Specify Course">
                                        </td>
                                        <td>
                                            <select name="degree_stream" class="stream-select" disabled>
                                                <option value="" disabled selected>Select Stream</option>
                                            </select>
                                            <input type="text" name="degree_stream_other" class="stream-other-input" style="display: none; margin-top: 5px;" placeholder="Specify Stream" disabled>
                                        </td>
                                        <td>
                                            <select name="degree_specialization" class="specialization-select" disabled>
                                                <option value="" disabled selected>Select</option>
                                            </select>
                                            <input type="text" name="degree_specialization_other" class="specialization-other-input" style="display: none; margin-top: 5px;" placeholder="Specify Specialization" disabled>
                                        </td>
                                        <td>
                                            <select name="degree_mode" disabled>
                                                <option value="Regular" selected>Regular</option>
                                                <option value="Correspondence">Correspondence</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="degree_year" class="year-select" disabled>
                                                <option value="" disabled selected>Select</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="degree_marking_system" class="marking-system" disabled>
                                                <option value="Percentage">%</option>
                                                <option value="CGPA">CGPA</option>
                                            </select>
                                        </td>
                                        <td><input type="number" name="degree_marks" class="marks-input" min="0" max="100" step="0.01" disabled></td>
                                        <td>
                                            <input type="checkbox" name="degree_pursuing" class="pursuing-checkbox" disabled>
                                            <label>Pursuing</label>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Post Grad</td>
                                        <td>
                                            <select name="pg_course" class="course-select">
                                                <option value="" selected>Not Completed</option>
                                                <option value="M.Tech">M.Tech</option>
                                                <option value="MBA">MBA</option>
                                                <option value="M.Sc">M.Sc</option>
                                                <option value="M.Com">M.Com</option>
                                                <option value="MCA">MCA</option>
                                                <option value="MA">MA</option>
                                                <option value="M.Arch">M.Arch</option>
                                                <option value="M.Pharm">M.Pharm</option>
                                                <option value="PG Diploma">PG Diploma</option>
                                                <option value="Others">Others</option>
                                            </select>
                                            <input type="text" name="pg_course_other" class="course-other-input" style="display: none; margin-top: 5px;" placeholder="Specify Course">
                                        </td>
                                        <td>
                                            <select name="pg_stream" class="stream-select" disabled>
                                                <option value="" selected disabled>Select</option>
                                            </select>
                                            <input type="text" name="pg_stream_other" class="stream-other-input" style="display: none; margin-top: 5px;" placeholder="Specify Stream" disabled>
                                        </td>
                                        <td>
                                            <select name="pg_specialization" class="specialization-select" disabled>
                                                <option value="" selected disabled>Select</option>
                                            </select>
                                            <input type="text" name="pg_specialization_other" class="specialization-other-input" style="display: none; margin-top: 5px;" placeholder="Specify Specialization" disabled>
                                        </td>
                                        <td>
                                            <select name="pg_mode" disabled>
                                                <option value="Regular" selected>Regular</option>
                                                <option value="Correspondence">Correspondence</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="pg_year" class="year-select" disabled>
                                                <option value="" disabled selected>Select</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="pg_marking_system" class="marking-system" disabled>
                                                <option value="Percentage">%</option>
                                                <option value="CGPA">CGPA</option>
                                            </select>
                                        </td>
                                        <td><input type="number" name="pg_marks" class="marks-input" min="0" max="100" step="0.01" disabled></td>
                                        <td>
                                            <input type="checkbox" name="pg_pursuing" class="pursuing-checkbox" disabled>
                                            <label>Pursuing</label>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="form-group">
  <label>Doctorate:</label>
  <div class="radio-group">
    <div class="radio-container">
      <input type="radio" id="doctorateYes" name="doctorate" value="Yes">
      <label for="doctorateYes">Yes</label>
    </div>
    <div class="radio-container">
      <input type="radio" id="doctorateNo" name="doctorate" value="No" checked>
      <label for="doctorateNo">No</label>
    </div>
  </div>
</div>

                        
                       <div class="form-group">
  <label>Work Experience:</label>
  <div class="radio-group">
    <div class="radio-container">
      <input type="radio" id="experienceYes" name="experience" value="Yes">
      <label for="experienceYes">Yes</label>
    </div>
    <div class="radio-container">
      <input type="radio" id="experienceNo" name="experience" value="No" checked>
      <label for="experienceNo">No</label>
    </div>
  </div>
</div>

                        
                        <div class="form-footer">
                            <button type="button" class="btn btn-prev prev-step">
                                <i class="fas fa-arrow-left"></i> Previous
                            </button>
                            <button type="button" class="btn btn-next next-step">
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    
                  <div class="step-content" id="step3">
  <div class="form-step-header">
    <h3>Skills & Aspirations</h3>
    <p>Tell us about your technical skills and career preferences</p>
  </div>

  <div class="form-group">
    <label>Technical Skills:</label>
    <div class="checkbox-group">
      <div class="checkbox-container">
        <input type="checkbox" id="skill-c" name="skills[]" value="C">
        <label for="skill-c">C</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="skill-cpp" name="skills[]" value="C++">
        <label for="skill-cpp">C++</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="skill-dotnet" name="skills[]" value=".NET Framework">
        <label for="skill-dotnet">.NET Framework</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="skill-java" name="skills[]" value="Java">
        <label for="skill-java">Java</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="skill-plsql" name="skills[]" value="PL/SQL">
        <label for="skill-plsql">PL/SQL</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="skill-mssql" name="skills[]" value="Microsoft SQL Server">
        <label for="skill-mssql">Microsoft SQL Server</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="skill-oracle" name="skills[]" value="Oracle">
        <label for="skill-oracle">Oracle</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="skill-azure" name="skills[]" value="Microsoft Azure">
        <label for="skill-azure">Microsoft Azure</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="skill-autocad" name="skills[]" value="AutoCAD">
        <label for="skill-autocad">AutoCAD</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="skill-maya" name="skills[]" value="Maya">
        <label for="skill-maya">Maya</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="skill-photoshop" name="skills[]" value="Adobe Photoshop">
        <label for="skill-photoshop">Adobe Photoshop</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="skill-coreldraw" name="skills[]" value="CorelDRAW">
        <label for="skill-coreldraw">CorelDRAW</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="skill-android" name="skills[]" value="Android">
        <label for="skill-android">Android</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="skill-ios" name="skills[]" value="iOS">
        <label for="skill-ios">iOS</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="skill-html" name="skills[]" value="HTML">
        <label for="skill-html">HTML</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="skill-css" name="skills[]" value="CSS">
        <label for="skill-css">CSS</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="skill-js" name="skills[]" value="JavaScript">
        <label for="skill-js">JavaScript</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="skill-react" name="skills[]" value="React">
        <label for="skill-react">React</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="skill-python" name="skills[]" value="Python">
        <label for="skill-python">Python</label>
      </div>
    </div>
    <input type="text" class="form-control" name="other_skills" placeholder="Other Skills">
  </div>

  <div class="form-group">
    <label>Languages Known:</label>
    <div class="checkbox-group">
      <div class="checkbox-container">
        <input type="checkbox" id="lang-english" name="languages[]" value="English" checked>
        <label for="lang-english">English</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="lang-kannada" name="languages[]" value="Kannada">
        <label for="lang-kannada">Kannada</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="lang-hindi" name="languages[]" value="Hindi">
        <label for="lang-hindi">Hindi</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="lang-malayalam" name="languages[]" value="Malayalam">
        <label for="lang-malayalam">Malayalam</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="lang-tamil" name="languages[]" value="Tamil">
        <label for="lang-tamil">Tamil</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="lang-telugu" name="languages[]" value="Telugu">
        <label for="lang-telugu">Telugu</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="lang-marathi" name="languages[]" value="Marathi">
        <label for="lang-marathi">Marathi</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="lang-bengali" name="languages[]" value="Bengali">
        <label for="lang-bengali">Bengali</label>
      </div>
    </div>
    <input type="text" class="form-control" name="other_languages" placeholder="Other Languages">
  </div>

  <div class="form-group">
    <label>Industry Aspiration:</label>
    <div class="checkbox-group">
      <div class="checkbox-container">
        <input type="checkbox" id="ind-auto" name="industries[]" value="Automobile">
        <label for="ind-auto">Automobile</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="ind-banking" name="industries[]" value="Banking and Financial Services">
        <label for="ind-banking">Banking & Financial Services</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="ind-construction" name="industries[]" value="Construction">
        <label for="ind-construction">Construction</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="ind-defence" name="industries[]" value="Defence">
        <label for="ind-defence">Defence</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="ind-education" name="industries[]" value="Education/NGO">
        <label for="ind-education">Education/NGO</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="ind-healthcare" name="industries[]" value="Healthcare">
        <label for="ind-healthcare">Healthcare</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="ind-hospitality" name="industries[]" value="Hospitality">
        <label for="ind-hospitality">Hospitality</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="ind-hr" name="industries[]" value="HR Consultancy">
        <label for="ind-hr">HR Consultancy</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="ind-infra" name="industries[]" value="Infrastructure">
        <label for="ind-infra">Infrastructure</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="ind-it" name="industries[]" value="IT&ITES">
        <label for="ind-it">IT & ITES</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="ind-manufacturing" name="industries[]" value="Manufacturing">
        <label for="ind-manufacturing">Manufacturing</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="ind-media" name="industries[]" value="Media">
        <label for="ind-media">Media</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="ind-pharma" name="industries[]" value="Pharmaceuticals">
        <label for="ind-pharma">Pharmaceuticals</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="ind-retail" name="industries[]" value="Retail and Sales">
        <label for="ind-retail">Retail & Sales</label>
      </div>
      <div class="checkbox-container">
        <input type="checkbox" id="ind-telecom" name="industries[]" value="Telecom">
        <label for="ind-telecom">Telecom</label>
      </div>
    </div>
    <input type="text" class="form-control" name="other_industries" placeholder="Other Industries">
  </div>

  <div class="form-group">
    <label>Relocation:</label>
    <div class="radio-group">
      <div class="radio-container">
        <input type="radio" id="relocation-yes" name="relocation" value="Yes">
        <label for="relocation-yes">Yes</label>
      </div>
      <div class="radio-container">
        <input type="radio" id="relocation-no" name="relocation" value="No" checked>
        <label for="relocation-no">No</label>
      </div>
    </div>
  </div>

  <div class="form-footer">
    <button type="button" class="btn btn-prev prev-step">
      <i class="fas fa-arrow-left"></i> Previous
    </button>
    <button type="button" class="btn btn-next next-step">
      Next <i class="fas fa-arrow-right"></i>
    </button>
  </div>
</div>

                  <div class="step-content" id="step4">
  <div class="form-step-header">
    <h3>Job Preferences & Other Details</h3>
    <p>Share your preferences for future opportunities</p>
  </div>

  <div class="form-group">
    <label>Are you interested in further studies?</label>
    <div class="radio-group">
      <div class="radio-container">
        <input type="radio" id="higher-yes" name="higher_studies" value="Yes">
        <label for="higher-yes">Yes</label>
      </div>
      <div class="radio-container">
        <input type="radio" id="higher-no" name="higher_studies" value="No" checked>
        <label for="higher-no">No</label>
      </div>
    </div>
  </div>

  <div class="form-group">
    <label>Are you ready to work in shift?</label>
    <div class="radio-group">
      <div class="radio-container">
        <input type="radio" id="shift-yes" name="shift_work" value="Yes">
        <label for="shift-yes">Yes</label>
      </div>
      <div class="radio-container">
        <input type="radio" id="shift-no" name="shift_work" value="No" checked>
        <label for="shift-no">No</label>
      </div>
    </div>
  </div>

  <div class="form-group">
    <label>Do you have a valid passport?</label>
    <div class="radio-group">
      <div class="radio-container">
        <input type="radio" id="passport-yes" name="passport" value="Yes">
        <label for="passport-yes">Yes</label>
      </div>
      <div class="radio-container">
        <input type="radio" id="passport-no" name="passport" value="No" checked>
        <label for="passport-no">No</label>
      </div>
    </div>
  </div>

  <div class="form-group">
    <label>Do you have a valid driving license?</label>
    <div class="radio-group">
      <div class="radio-container">
        <input type="radio" id="license-yes" name="driving_license" value="Yes">
        <label for="license-yes">Yes</label>
      </div>
      <div class="radio-container">
        <input type="radio" id="license-no" name="driving_license" value="No" checked>
        <label for="license-no">No</label>
      </div>
    </div>
  </div>
  
  <div class="form-group">
    <label>Do you have disability?</label>
    <div class="radio-group">
      <div class="radio-container">
        <input type="radio" id="disability-yes" name="disability" value="Yes">
        <label for="disability-yes">Yes</label>
      </div>
      <div class="radio-container">
        <input type="radio" id="disability-no" name="disability" value="No" checked>
        <label for="disability-no">No</label>
      </div>
    </div>
  </div>
  
  <div id="disability-details-container" style="display: none;">
  
    <div class="form-group">
      <select name="disability_type" class="form-control">
        <option value="" selected disabled></option>
        <option value="Physical">Physical Disability</option>
        <option value="Visual">Visual Impairment</option>
        <option value="Hearing">Hearing Impairment</option>
        <option value="Speech">Speech Disability</option>
        <option value="Intellectual">Intellectual Disability</option>
        <option value="Mental">Mental Illness</option>
        <option value="Multiple">Multiple Disabilities</option>
        <option value="Other">Other</option>
      </select>
      <label>Type of disability *</label>
      <div class="error-message">Please select your disability type</div>
    </div>
  
    <div class="form-group">
      <input type="number" name="disability_percentage" class="form-control" placeholder=" " min="0" max="100">
      <label>% of disability *</label>
      <div class="error-message">Please enter your disability percentage</div>
    </div>
  
    <div class="form-group">
      <label>Do you have UDID card?</label>
      <div class="radio-group">
        <div class="radio-container">
          <input type="radio" id="udid-yes" name="has_udid" value="Yes">
          <label for="udid-yes">Yes</label>
        </div>
        <div class="radio-container">
          <input type="radio" id="udid-no" name="has_udid" value="No" checked>
          <label for="udid-no">No</label>
        </div>
      </div>
    </div>
  
    <div id="udid-yes-container" style="display: none;">
      <div class="form-group">
        <input type="text" name="udid_number" class="form-control" placeholder=" ">
        <label>UDID Number *</label>
        <div class="error-message">Please enter your UDID number</div>
      </div>
  
      <div class="form-group">
        <label>Upload UDID Card *:</label>
        <input type="file" name="udid_card" id="udid_card" class="form-control" accept=".pdf,.jpg,.png">
      </div>
    </div>
  
    <div id="udid-no-container" style="display: none;">
      <div class="form-group">
        <label>Upload Disability Certificate *:</label>
        <input type="file" name="disability_certificate" id="disability_certificate" class="form-control" accept=".pdf,.jpg,.png">
      </div>
    </div>
  
  </div>
  <div class="form-step-header">
    <p>Share your Resume for future opportunities</p>
  </div>
  <div class="form-group">
        <label>Upload Resume:</label>
        <input type="file" name="resume" id="resume" class="form-control" accept=".pdf,.doc,.docx,.jpg,.png">
        <div class="checkbox-container" style="margin-top: 10px;">
            <input type="checkbox" id="no_resume" name="no_resume" value="1">
            <label for="no_resume">I don't have a resume</label>
        </div>
    </div>

  <div class="form-footer">
    <button type="button" class="btn btn-prev prev-step">
      <i class="fas fa-arrow-left"></i> Previous
    </button>
    <button type="button" class="btn btn-next next-step">
      Next <i class="fas fa-arrow-right"></i>
    </button>
  </div>
</div>

                    <div class="step-content" id="step5">
                        <div class="form-step-header">
                            <h3>Review and Submit</h3>
                            <p>Confirm your information before submitting</p>
                        </div>
                        
                        <div class="review-card">
                            <div id="reviewData"></div>
                        </div>
                        
                        <div class="terms">
                            <input type="checkbox" id="terms" required>
                            <label for="terms">I agree to the <a href="#">Terms and Conditions</a> and confirm that the information provided is accurate to the best of my knowledge.</label>
                        </div>
                        
                        <div class="form-footer">
                            <button type="button" class="btn btn-prev prev-step">
                                <i class="fas fa-arrow-left"></i> Previous
                            </button>
                            <button type="submit" class="btn btn-submit">
                                <i class="fas fa-paper-plane"></i> Submit Registration
                            </button>
                        </div>
                    </div>
                    
                    <div class="step-content" id="successMessage" style="display: none;">
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i>
                            <h3>Registration Successful!</h3>
                            <p>Thank you for completing your registration. Your profile has been successfully created in our talent portal. Employers will now be able to view your profile and contact you for suitable opportunities.</p>
                            <button type="button" class="btn btn-submit" onclick="location.reload()">
                                <i class="fas fa-user-plus"></i> Create Another Registration
                            </button>
                            <a href="employee_dashboard.php" class="btn btn-outline-secondary" style="text-decoration: none;">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        // Handle SSLC course change
        document.addEventListener('DOMContentLoaded', function() {
            const sslcCourseSelect = document.querySelector('select[name="sslc_course"]');
            
            if (sslcCourseSelect) {
                sslcCourseSelect.addEventListener('change', function() {
                    const isBelowSSLC = this.value === 'Below SSLC';
                    const sslcRow = this.closest('tr');
                    const sslcFields = sslcRow.querySelectorAll('select:not([name="sslc_course"]), input');
                    
                    // Toggle disabled state and required attributes
                    sslcFields.forEach(field => {
                        field.disabled = isBelowSSLC;
                        if (isBelowSSLC) {
                            field.removeAttribute('required');
                            if (field.tagName === 'SELECT') {
                                field.selectedIndex = 0;
                            } else {
                                field.value = '';
                            }
                        } else {
                            if (field.name === 'sslc_year' || field.name === 'sslc_marks') {
                                field.setAttribute('required', 'required');
                            }
                        }
                    });
                });
                
                // Trigger change event on page load to set initial state
                sslcCourseSelect.dispatchEvent(new Event('change'));
            }
        });
    </script>

<script>
        function populateYearDropdown(selectElement, required = false) {
                const currentYear = new Date().getFullYear();
                let html = '<option value="" disabled selected>Select</option>';
                
                for (let year = currentYear; year >= 1947; year--) {
                    html += `<option value="${year}">${year}</option>`;
                }
                
                selectElement.innerHTML = html;
                if (required) {
                    selectElement.required = true;
                }
            }
        document.addEventListener('DOMContentLoaded', function() {
            // Function to update marks input based on marking system
            function updateMarksInput(markingSystemSelect, marksInput) {
                if (markingSystemSelect.value === 'CGPA') {
                    marksInput.max = 10;
                    marksInput.step = 0.1;
                    marksInput.placeholder = "0.0 - 10.0";
                } else {
                    marksInput.max = 100;
                    marksInput.step = 0.01;
                    marksInput.placeholder = "0 - 100";
                }
            }

            // Add event listeners to all marking system dropdowns
            const markingSystemSelects = document.querySelectorAll('select[name$="_marking_system"]');
            markingSystemSelects.forEach(select => {
                const marksInput = select.closest('tr').querySelector('input[name$="_marks"]');
                
                // Set initial state
                updateMarksInput(select, marksInput);
                
                // Add change event listener
                select.addEventListener('change', function() {
                    updateMarksInput(this, marksInput);
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            // State to districts mapping
            const stateDistricts = {
                'Karnataka': [
                    'Bangalore Urban', 'Bangalore Rural', 'Mysore', 'Dakshina Kannada', 'Udupi', 
                    'Belgaum', 'Bellary', 'Bidar', 'Bijapur', 'Chamarajanagar', 'Chikballapur', 
                    'Chikkamagaluru', 'Chitradurga', 'Davanagere', 'Dharwad', 'Gadag', 'Gulbarga', 
                    'Hassan', 'Haveri', 'Kodagu', 'Kolar', 'Koppal', 'Mandya', 'Raichur', 
                    'Ramanagara', 'Shimoga', 'Tumkur', 'Uttara Kannada', 'Vijayanagara', 
                    'Vijayapura', 'Yadgir'
                ],
                'Tamil Nadu': [
                    'Ariyalur', 'Chengalpattu', 'Chennai', 'Coimbatore', 'Cuddalore', 
                    'Dharmapuri', 'Dindigul', 'Erode', 'Kallakurichi', 'Kanchipuram', 
                    'Kanyakumari', 'Karur', 'Krishnagiri', 'Madurai', 'Mayiladuthurai', 
                    'Nagapattinam', 'Namakkal', 'Nilgiris', 'Perambalur', 'Pudukkottai', 
                    'Ramanathapuram', 'Ranipet', 'Salem', 'Sivaganga', 'Tenkasi', 'Thanjavur', 
                    'Theni', 'Thoothukudi', 'Tiruchirappalli', 'Tirunelveli', 'Tirupathur', 
                    'Tiruppur', 'Tiruvallur', 'Tiruvannamalai', 'Tiruvarur', 'Vellore', 
                    'Viluppuram', 'Virudhunagar'
                ],
                'Maharashtra': [
                    'Ahmednagar', 'Akola', 'Amravati', 'Aurangabad', 'Beed', 'Bhandara', 
                    'Buldhana', 'Chandrapur', 'Dhule', 'Gadchiroli', 'Gondia', 'Hingoli', 
                    'Jalgaon', 'Jalna', 'Kolhapur', 'Latur', 'Mumbai City', 'Mumbai Suburban', 
                    'Nagpur', 'Nanded', 'Nandurbar', 'Nashik', 'Osmanabad', 'Palghar', 
                    'Parbhani', 'Pune', 'Raigad', 'Ratnagiri', 'Sangli', 'Satara', 'Sindhudurg', 
                    'Solapur', 'Thane', 'Wardha', 'Washim', 'Yavatmal'
                ],
                'Kerala': [
                    'Alappuzha', 'Ernakulam', 'Idukki', 'Kannur', 'Kasaragod', 'Kollam', 
                    'Kottayam', 'Kozhikode', 'Malappuram', 'Palakkad', 'Pathanamthitta', 
                    'Thiruvananthapuram', 'Thrissur', 'Wayanad'
                ],
                'Delhi': [
                    'Central Delhi', 'East Delhi', 'New Delhi', 'North Delhi', 'North East Delhi', 
                    'North West Delhi', 'Shahdara', 'South Delhi', 'South East Delhi', 
                    'South West Delhi', 'West Delhi'
                ],
                'Andhra Pradesh': [
                    'Anantapur', 'Chittoor', 'East Godavari', 'Guntur', 'Krishna', 'Kurnool', 
                    'Nellore', 'Prakasam', 'Srikakulam', 'Visakhapatnam', 'Vizianagaram', 
                    'West Godavari', 'YSR Kadapa'
                ],
                'Telangana': [
                    'Adilabad', 'Bhadradri Kothagudem', 'Hyderabad', 'Jagtial', 'Jangaon', 
                    'Jayashankar Bhupalpally', 'Jogulamba Gadwal', 'Kamareddy', 'Karimnagar', 
                    'Khammam', 'Komaram Bheem', 'Mahabubabad', 'Mahabubnagar', 'Mancherial', 
                    'Medak', 'Medchal–Malkajgiri', 'Mulugu', 'Nagarkurnool', 'Nalgonda', 
                    'Narayanpet', 'Nirmal', 'Nizamabad', 'Peddapalli', 'Rajanna Sircilla', 
                    'Rangareddy', 'Sangareddy', 'Siddipet', 'Suryapet', 'Vikarabad', 
                    'Wanaparthy', 'Warangal Rural', 'Warangal Urban', 'Yadadri Bhuvanagiri'
                ],
                'West Bengal': [
                    'Alipurduar', 'Bankura', 'Birbhum', 'Cooch Behar', 'Dakshin Dinajpur', 
                    'Darjeeling', 'Hooghly', 'Howrah', 'Jalpaiguri', 'Jhargram', 'Kalimpong', 
                    'Kolkata', 'Malda', 'Murshidabad', 'Nadia', 'North 24 Parganas', 
                    'Paschim Bardhaman', 'Paschim Medinipur', 'Purba Bardhaman', 
                    'Purba Medinipur', 'Purulia', 'South 24 Parganas', 'Uttar Dinajpur'
                ],
                'Gujarat': [
                    'Ahmedabad', 'Amreli', 'Anand', 'Aravalli', 'Banaskantha', 'Bharuch', 
                    'Bhavnagar', 'Botad', 'Chhota Udaipur', 'Dahod', 'Dang', 'Devbhoomi Dwarka', 
                    'Gandhinagar', 'Gir Somnath', 'Jamnagar', 'Junagadh', 'Kheda', 'Kutch', 
                    'Mahisagar', 'Mehsana', 'Morbi', 'Narmada', 'Navsari', 'Panchmahal', 
                    'Patan', 'Porbandar', 'Rajkot', 'Sabarkantha', 'Surat', 'Surendranagar', 
                    'Tapi', 'Vadodara', 'Valsad'
                ],
                'Rajasthan': [
                    'Ajmer', 'Alwar', 'Banswara', 'Baran', 'Barmer', 'Bharatpur', 'Bhilwara', 
                    'Bikaner', 'Bundi', 'Chittorgarh', 'Churu', 'Dausa', 'Dholpur', 'Dungarpur', 
                    'Hanumangarh', 'Jaipur', 'Jaisalmer', 'Jalore', 'Jhalawar', 'Jhunjhunu', 
                    'Jodhpur', 'Karauli', 'Kota', 'Nagaur', 'Pali', 'Pratapgarh', 'Rajsamand', 
                    'Sawai Madhopur', 'Sikar', 'Sirohi', 'Sri Ganganagar', 'Tonk', 'Udaipur'
                ]
            };
            
            // Function to update district dropdown based on selected state
            function updateDistricts() {
                const stateSelect = document.getElementById('state');
                const districtSelect = document.getElementById('district');
                const selectedState = stateSelect.value;

                // Clear existing options
                districtSelect.innerHTML = '<option value="" selected disabled>Select District</option>';

                if (selectedState && stateDistricts[selectedState]) {
                    stateDistricts[selectedState].forEach(district => {
                        const option = document.createElement('option');
                        option.value = district;
                        option.textContent = district;
                        districtSelect.appendChild(option);
                    });
                }
            }

            // Set max date for date of birth to today
            const dobInput = document.getElementById('dob');
            const today = new Date().toISOString().split('T')[0];
            dobInput.setAttribute('max', today);

            // Make calendar icon clickable to open the date picker
            const calendarIcon = document.getElementById('calendarTrigger');
            calendarIcon.addEventListener('click', function() {
            console.log("Calendar icon was clicked!"); // <-- ADD THIS LINE
            // The showPicker() method programmatically opens the browser's date picker UI
            try {
                dobInput.showPicker();
            } catch (error) {
                // Fallback for browsers that don't support showPicker()
                dobInput.focus();
            }
        });

            // Initialize districts when state changes
            document.getElementById('state').addEventListener('change', updateDistricts);

            // Initialize districts on page load if state is already selected
            updateDistricts();
        });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to handle "Others" option for course selection
        function handleOthersOption(courseSelect) {
            const row = courseSelect.closest('tr');
            const courseOtherInput = row.querySelector('.course-other-input');
            const streamSelect = row.querySelector('.stream-select');
            const streamOtherInput = row.querySelector('.stream-other-input');
            const specializationSelect = row.querySelector('.specialization-select');
            const specializationOtherInput = row.querySelector('.specialization-other-input');

            if (courseSelect.value === 'Others') {
                // Show other course input and hide the dropdown
                courseSelect.style.display = 'none';
                courseOtherInput.style.display = 'block';
                courseOtherInput.disabled = false;
                
                // For "Others", show input fields for stream and specialization
                streamSelect.style.display = 'none';
                streamOtherInput.style.display = 'block';
                streamOtherInput.disabled = false;
                
                specializationSelect.style.display = 'none';
                specializationOtherInput.style.display = 'block';
                specializationOtherInput.disabled = false;
            } else {
                // Show normal dropdown and hide other inputs
                courseSelect.style.display = 'block';
                courseOtherInput.style.display = 'none';
                courseOtherInput.disabled = true;
                
                streamSelect.style.display = 'block';
                streamOtherInput.style.display = 'none';
                streamOtherInput.disabled = true;
                
                specializationSelect.style.display = 'block';
                specializationOtherInput.style.display = 'none';
                specializationOtherInput.disabled = true;
            }
        }

        // Add event listeners to all course selects
        document.querySelectorAll('.course-select').forEach(select => {
            select.addEventListener('change', function() {
                if (this.value === 'Others') {
                    handleOthersOption(this);
                } else {
                    handleOthersOption(this); // Reset to normal state
                    toggleQualificationFields(this); // Existing functionality
                }
            });
            
            // Initialize state
            if (select.value === 'Others') {
                handleOthersOption(select);
            }
        });
    });
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const disabilityYes = document.getElementById('disability-yes');
            const disabilityNo = document.getElementById('disability-no');
            const disabilityDetailsContainer = document.getElementById('disability-details-container');
            
            const udidYes = document.getElementById('udid-yes');
            const udidNo = document.getElementById('udid-no');
            const udidYesContainer = document.getElementById('udid-yes-container');
            const udidNoContainer = document.getElementById('udid-no-container');
            
            // Inputs inside the containers
            const disabilityType = document.querySelector('select[name="disability_type"]');
            const disabilityPercentage = document.querySelector('input[name="disability_percentage"]');
            const udidNumber = document.querySelector('input[name="udid_number"]');
            const udidCardFile = document.getElementById('udid_card');
            const disabilityCertificateFile = document.getElementById('disability_certificate');

            function toggleDisabilityFields() {
                if (disabilityYes.checked) {
                    disabilityDetailsContainer.style.display = 'block';
                    disabilityType.required = true;
                    disabilityPercentage.required = true;
                    // Trigger UDID check to set initial sub-state
                    toggleUdidFields();
                } else {
                    disabilityDetailsContainer.style.display = 'none';
                    // Unset fields as required
                    disabilityType.required = false;
                    disabilityPercentage.required = false;
                    udidNumber.required = false;
                    udidCardFile.required = false;
                    disabilityCertificateFile.required = false;
                    
                    // Also reset sub-fields
                    udidYesContainer.style.display = 'none';
                    udidNoContainer.style.display = 'none'; // Hide both when disability is 'No'
                }
            }

            function toggleUdidFields() {
                // This function should only run if disability is 'Yes'
                if (disabilityYes.checked) {
                    if (udidYes.checked) {
                        udidYesContainer.style.display = 'block';
                        udidNoContainer.style.display = 'none';
                        // Set fields as required
                        udidNumber.required = true;
                        udidCardFile.required = true;
                        // Unset other
                        disabilityCertificateFile.required = false;
                    } else { // udidNo.checked
                        udidYesContainer.style.display = 'none';
                        udidNoContainer.style.display = 'block';
                        // Set fields as required
                        disabilityCertificateFile.required = true;
                        // Unset other
                        udidNumber.required = false;
                        udidCardFile.required = false;
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
        });
    </script>
    </body>
</html>