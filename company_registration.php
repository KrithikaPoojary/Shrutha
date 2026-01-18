<?php
require_once 'config.php';

// Process form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];
    $success_message = "";
    
    // Validate and sanitize inputs
    function validate_input($data, $field) {
        global $errors;
        if (empty($data)) {
            $errors[] = "$field is required";
            return null;
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    $organization_name = validate_input($_POST['organization_name'], 'Organization Name');
    $sector = validate_input($_POST['sector'], 'Sector');
    $contact_person = validate_input($_POST['contact_person'], 'Contact Person');
    $designation = validate_input($_POST['designation'], 'Designation');
    $gender = validate_input($_POST['gender'], 'Gender');
    $country_code = validate_input($_POST['country_code'], 'Country Code');
    $interview_type = validate_input($_POST['interview_type'], 'Interview Type');
    $address = validate_input($_POST['address'], 'Address');
    
    // Email validation
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Mobile validation
    $mobile = preg_replace('/[^0-9]/', '', $_POST['mobile']);
    if (strlen($mobile) !== 10) {
        $errors[] = "Mobile must be 10 digits";
    }
    
    $landline = mysqli_real_escape_string($conn, $_POST['landline']);
    
    $accommodation_required = isset($_POST['accommodation_required']) ? 1 : 0;
    $accommodation_type = isset($_POST['accommodation_type']) ? mysqli_real_escape_string($conn, $_POST['accommodation_type']) : '';
    $transportation_required = isset($_POST['transportation_required']) ? 1 : 0;
    $transportation_type = isset($_POST['transportation_type']) ? mysqli_real_escape_string($conn, $_POST['transportation_type']) : '';
    
    $interview_rooms = intval($_POST['interview_rooms']);
    $interview_panels = intval($_POST['interview_panels']);
    $aptitude_hall = isset($_POST['aptitude_hall']) && $_POST['aptitude_hall'] == 'Yes' ? 1 : 0;
    $online_exam = isset($_POST['online_exam']) ? 1 : 0;
    $written_exam = isset($_POST['written_exam']) ? 1 : 0;
    $group_discussion = isset($_POST['group_discussion']) ? 1 : 0;
    
    // Validate executives
    $executiveErrors = [];
    if (isset($_POST['executive_name'])) {
        for ($i = 0; $i < count($_POST['executive_name']); $i++) {
            $exec_name = trim($_POST['executive_name'][$i]);
            $exec_designation = trim($_POST['executive_designation'][$i]);
            $exec_mobile = preg_replace('/[^0-9]/', '', $_POST['executive_mobile'][$i]);
            $exec_email = filter_var($_POST['executive_email'][$i], FILTER_SANITIZE_EMAIL);
            $exec_gender = trim($_POST['executive_gender'][$i]);
            
            if (empty($exec_name)) {
                $executiveErrors[] = "Executive name is required in row ".($i+1);
            }
            if (empty($exec_designation)) {
                $executiveErrors[] = "Executive designation is required in row ".($i+1);
            }
            if (strlen($exec_mobile) !== 10) {
                $executiveErrors[] = "Executive mobile must be 10 digits in row ".($i+1);
            }
            if (!filter_var($exec_email, FILTER_VALIDATE_EMAIL)) {
                $executiveErrors[] = "Invalid executive email format in row ".($i+1);
            }
            if (empty($exec_gender)) {
                $executiveErrors[] = "Executive gender is required in row ".($i+1);
            }
        }
    } else {
        $executiveErrors[] = "At least one executive is required";
    }
    
    // Validate job openings
    $jobErrors = [];
    if (isset($_POST['vacancies'])) {
        for ($i = 0; $i < count($_POST['vacancies']); $i++) {
            $vacancies = intval($_POST['vacancies'][$i]);
            $job_designation = trim($_POST['job_designation'][$i]);
            $qualification = trim($_POST['qualification'][$i]);
            $from_ctc = floatval($_POST['from_ctc'][$i]);
            $to_ctc = floatval($_POST['to_ctc'][$i]);
            
            if ($vacancies <= 0) {
                $jobErrors[] = "Vacancies must be greater than 0 in row ".($i+1);
            }
            if (empty($job_designation)) {
                $jobErrors[] = "Job designation is required in row ".($i+1);
            }
            if (empty($qualification)) {
                $jobErrors[] = "Qualification is required in row ".($i+1);
            }
            if ($to_ctc < $from_ctc) {
                $jobErrors[] = "To CTC must be greater than or equal to From CTC in row ".($i+1);
            }
        }
    } else {
        $jobErrors[] = "At least one job opening is required";
    }
    
    // Merge all errors
    $errors = array_merge($errors, $executiveErrors, $jobErrors);
    
    // If no errors, proceed with database insertion
    if (empty($errors)) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert company information
            $sql = "INSERT INTO companies (organization_name, sector, contact_person, email, designation, gender, 
                    country_code, mobile, landline, address, interview_type, accommodation_required, accommodation_type, 
                    transportation_required, transportation_type, interview_rooms, interview_panels, aptitude_hall, 
                    online_exam, written_exam, group_discussion) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssssssssssssiiiiiiii", 
                $organization_name, $sector, $contact_person, $email, $designation, $gender, 
                $country_code, $mobile, $landline, $address, $interview_type, $accommodation_required, $accommodation_type, 
                $transportation_required, $transportation_type, $interview_rooms, $interview_panels, $aptitude_hall, 
                $online_exam, $written_exam, $group_discussion);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Company insert failed: " . mysqli_stmt_error($stmt));
            }
            
            $company_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            
            // Insert executives
            if (isset($_POST['executive_name'])) {
                for ($i = 0; $i < count($_POST['executive_name']); $i++) {
                    $name = mysqli_real_escape_string($conn, $_POST['executive_name'][$i]);
                    $designation = mysqli_real_escape_string($conn, $_POST['executive_designation'][$i]);
                    $mobile = preg_replace('/[^0-9]/', '', $_POST['executive_mobile'][$i]);
                    $email = filter_var($_POST['executive_email'][$i], FILTER_SANITIZE_EMAIL);
                    $gender = mysqli_real_escape_string($conn, $_POST['executive_gender'][$i]);
                    
                    $sql = "INSERT INTO executives (company_id, name, designation, mobile, email, gender) 
                            VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "isssss", $company_id, $name, $designation, $mobile, $email, $gender);
                    
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Executive insert failed: " . mysqli_stmt_error($stmt));
                    }
                    mysqli_stmt_close($stmt);
                }
            }
            
            // Insert job openings
            if (isset($_POST['vacancies'])) {
                for ($i = 0; $i < count($_POST['vacancies']); $i++) {
                    $vacancies = intval($_POST['vacancies'][$i]);
                    $job_designation = mysqli_real_escape_string($conn, $_POST['job_designation'][$i]);
                    $qualification = mysqli_real_escape_string($conn, $_POST['qualification'][$i]);
                    $course = mysqli_real_escape_string($conn, $_POST['course'][$i]);
                    $stream = mysqli_real_escape_string($conn, $_POST['stream'][$i]);
                    $from_ctc = floatval($_POST['from_ctc'][$i]);
                    $to_ctc = floatval($_POST['to_ctc'][$i]);
                    $cut_off = mysqli_real_escape_string($conn, $_POST['cut_off'][$i]);
                    $job_location = mysqli_real_escape_string($conn, $_POST['job_location'][$i]);
                    $job_description = mysqli_real_escape_string($conn, $_POST['job_description'][$i]);
                    $exp_from = intval($_POST['exp_from'][$i]);
                    $exp_to = intval($_POST['exp_to'][$i]);
                    
                    $sql = "INSERT INTO job_openings (company_id, vacancies, job_designation, qualification, course, 
                            stream, from_ctc, to_ctc, cut_off, job_location, job_description, exp_from, exp_to) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "iissssddsssii", $company_id, $vacancies, $job_designation, $qualification, $course, 
                            $stream, $from_ctc, $to_ctc, $cut_off, $job_location, $job_description, $exp_from, $exp_to);
                    
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Job opening insert failed: " . mysqli_stmt_error($stmt));
                    }
                    mysqli_stmt_close($stmt);
                }
            }
            // Generate unique ID
            $unique_id = 'EMP' . date('Ymd') . str_pad($company_id, 5, '0', STR_PAD_LEFT);

            // Update the company with the unique ID
            $update_sql = "UPDATE companies SET unique_id = ? WHERE id = ?";
            $stmt_update = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($stmt_update, "si", $unique_id, $company_id);
            if (!mysqli_stmt_execute($stmt_update)) {
                throw new Exception("Failed to update unique ID: " . mysqli_stmt_error($stmt_update));
            }
            mysqli_stmt_close($stmt_update);
                        
            // Commit transaction
            mysqli_commit($conn);
            $success_message = "Registration successful!";
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Registration </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-bg: #f8f9fa;
            --border-radius: 8px;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .registration-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .form-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .progress-container {
            padding: 20px;
            background-color: var(--light-bg);
        }
        
        .progress-bar {
            transition: width 0.5s ease-in-out;
        }
        
        .step {
            display: none;
            padding: 30px;
            animation: fadeIn 0.5s ease;
        }
        
        .step.active {
            display: block;
        }
        
        .step-title {
            color: var(--primary-color);
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 15px;
            margin-bottom: 25px;
            font-weight: 600;
        }
        
        .form-section {
            background-color: var(--light-bg);
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            border-left: 4px solid var(--secondary-color);
        }
        
        .form-section h4 {
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 500;
            color: #555;
        }
        
        .form-control, .form-select {
            border-radius: var(--border-radius);
            padding: 10px 15px;
            border: 1px solid #ddd;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        
        .btn-container {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--secondary-color), #2980b9);
            border: none;
            padding: 10px 25px;
            font-weight: 500;
            border-radius: var(--border-radius);
        }
        
        .btn-secondary {
            background: #6c757d;
            border: none;
            padding: 10px 25px;
            font-weight: 500;
            border-radius: var(--border-radius);
        }
        
        .btn-success {
            background: linear-gradient(to right, #27ae60, #2ecc71);
            border: none;
            padding: 10px 25px;
            font-weight: 500;
            border-radius: var(--border-radius);
        }
        
        .summary-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #ddd;
        }
        
        .summary-label {
            font-weight: 600;
            color: var(--primary-color);
            min-width: 200px;
        }
        
        .assistance-banner {
            background: linear-gradient(to right, #e74c3c, #c0392b);
            color: white;
            padding: 15px;
            border-radius: var(--border-radius);
            margin-top: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .table-responsive {
            border-radius: var(--border-radius);
            overflow: hidden;
        }
        
        .table th {
            background-color: var(--primary-color);
            color: white;
            font-size: 0.85rem;
            white-space: nowrap;
        }
        
        .table td {
            font-size: 0.85rem;
        }
        
        .add-btn {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 15px auto;
            transition: all 0.3s;
        }
        
        .add-btn:hover {
            transform: scale(1.1);
            background-color: #2980b9;
        }
        
        .remove-btn {
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            font-size: 0.9rem;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .gender-container {
            display: flex;
            gap: 15px;
        }
        
        .gender-option {
            flex: 1;
            text-align: center;
            padding: 15px;
            border: 2px solid #eee;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .gender-option.selected {
            border-color: var(--secondary-color);
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .gender-option i {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--secondary-color);
        }
        
        .facility-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .facility-item label {
            flex: 1;
            margin-bottom: 0;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 10px;
        }
        
        .step-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #ddd;
            transition: all 0.3s;
        }
        
        .step-dot.active {
            background-color: var(--secondary-color);
            transform: scale(1.2);
        }
        
        .executive-form {
            position: relative;
            padding: 15px;
            background: white;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .table-container {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .job-opening-row {
            border-bottom: 1px solid #eee;
        }
        
        .table th, .table td {
            vertical-align: middle;
        }
        
        .exp-select {
            min-width: 70px;
        }
        
        .form-section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .form-section-title h4 {
            margin-bottom: 0;
        }
        .error-border {
            border: 1px solid #dc3545 !important;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
        .alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            max-width: 400px;
        }
        .alert {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="alert-container">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> 
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> <?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>

    <div class="registration-container">
        <div class="form-header">
            <h1><i class="fas fa-building me-2"></i>Employer Registration </h1>
            <p class="mb-0">Register your organization for campus placements</p>
        </div>
        
        <div class="progress-container">
            <div class="progress" style="height: 15px;">
                <div class="progress-bar" id="form-progress" role="progressbar" style="width: 20%;" 
                    aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="step-indicator">
                <div class="step-dot active" data-step="1"></div>
                <div class="step-dot" data-step="2"></div>
                <div class="step-dot" data-step="3"></div>
                <div class="step-dot" data-step="4"></div>
                <div class="step-dot" data-step="5"></div>
            </div>
        </div>
        
        <form id="registrationForm" action="company_registration.php" method="POST">
            <div class="step active" id="step1">
                <h3 class="step-title">Step 1: Company Profile</h3>
                
                <div class="form-section">
                    <div class="form-section-title">
                        <h4><i class="fas fa-info-circle me-2"></i>Organization Information</h4>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="organization_name" class="form-label">Organization Name *</label>
                            <input type="text" class="form-control" id="organization_name" name="organization_name" required>
                            <div class="error-message" id="org-name-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="sector" class="form-label">Sector *</label>
                            <select class="form-select" id="sector" name="sector" required>
                                <option value="">-- Select Sector --</option>
                                <option value="IT & ITES">IT & ITES</option>
                                <option value="Manufacturing">Manufacturing</option>
                                <option value="Finance & Banking">Finance & Banking</option>
                                <option value="Healthcare">Healthcare</option>
                                <option value="Education">Education</option>
                                <option value="Retail">Retail</option>
                                <option value="Automotive">Automotive</option>
                                <option value="Telecom">Telecom</option>
                                <option value="Hospitality">Hospitality</option>
                                <option value="Energy">Energy</option>
                                <option value="Media & Entertainment">Media & Entertainment</option>
                                <option value="Logistics">Logistics</option>
                            </select>
                            <div class="error-message" id="sector-error"></div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="contact_person" class="form-label">Contact Person *</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person" required>
                            <div class="error-message" id="contact-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email ID *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="error-message" id="email-error"></div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="designation" class="form-label">Designation *</label>
                            <select class="form-select" id="designation" name="designation" required>
                                <option value="">-- Select Designation --</option>
                                <option value="HR Manager">HR Manager</option>
                                <option value="Recruitment Head">Recruitment Head</option>
                                <option value="Talent Acquisition">Talent Acquisition</option>
                                <option value="Department Head">Department Head</option>
                                <option value="Director">Director</option>
                                <option value="CEO">CEO</option>
                                <option value="CTO">CTO</option>
                                <option value="Other">Other</option>
                            </select>
                            <div class="error-message" id="designation-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender *</label>
                            <div class="gender-container">
                                <div class="gender-option" data-value="Male">
                                    <i class="fas fa-male"></i>
                                    <div>Male</div>
                                </div>
                                <div class="gender-option" data-value="Female">
                                    <i class="fas fa-female"></i>
                                    <div>Female</div>
                                </div>
                                <div class="gender-option" data-value="Other">
                                    <i class="fas fa-user"></i>
                                    <div>Other</div>
                                </div>
                            </div>
                            <input type="hidden" id="gender" name="gender" value="">
                            <div class="error-message" id="gender-error"></div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="country_code" class="form-label">Country Code *</label>
                            <select class="form-select" id="country_code" name="country_code" required>
                                <option value="+91">India (+91)</option>
                                <option value="+1">USA (+1)</option>
                                <option value="+44">UK (+44)</option>
                                <option value="+61">Australia (+61)</option>
                                <option value="+65">Singapore (+65)</option>
                                <option value="+971">UAE (+971)</option>
                                <option value="+86">China (+86)</option>
                                <option value="+81">Japan (+81)</option>
                                <option value="+82">South Korea (+82)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="mobile" class="form-label">Mobile Number *</label>
                            <input type="tel" class="form-control" id="mobile" name="mobile" pattern="[0-9]{10}" placeholder="10 digit number" required>
                            <div class="error-message" id="mobile-error"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="landline" class="form-label">Landline No</label>
                            <input type="tel" class="form-control" id="landline" name="landline">
                        </div>
                        <div class="col-md-3">
                            <label for="interview_type" class="form-label">Interview Type *</label>
                            <select class="form-select" id="interview_type" name="interview_type" required>
                                <option value="">-- Select Type --</option>
                                <option value="Offline">Offline</option>
                                <option value="Online">Online</option>
                                <option value="Hybrid">Hybrid (Online + Offline)</option>
                            </select>
                            <div class="error-message" id="interview-error"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address *</label>
                        <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                        <div class="error-message" id="address-error"></div>
                    </div>
                </div>
                
                <div class="btn-container">
                    <div></div> <button type="button" class="btn btn-primary" onclick="validateStep1()">Next <i class="fas fa-arrow-right ms-2"></i></button>
                </div>
            </div>
            
            <div class="step" id="step2">
                <h3 class="step-title">Step 2: Accommodation Details</h3>
                
                <div class="form-section">
                    <div class="form-section-title">
                        <h4><i class="fas fa-hotel me-2"></i>Logistical Requirements</h4>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="accommodation_required" name="accommodation_required">
                                <label class="form-check-label" for="accommodation_required">Accommodation Required</label>
                            </div>
                            <div class="mt-2">
                                <label class="form-label">Accommodation Type</label>
                                <select class="form-select" id="accommodation_type" name="accommodation_type" disabled>
                                    <option value="">-- Select Type --</option>
                                    <option value="Standard">Standard (3 Star)</option>
                                    <option value="Premium">Premium (4 Star)</option>
                                    <option value="Deluxe">Deluxe (5 Star)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="transportation_required" name="transportation_required">
                                <label class="form-check-label" for="transportation_required">Transportation Required</label>
                            </div>
                            <div class="mt-2">
                                <label class="form-label">Transportation Type</label>
                                <select class="form-select" id="transportation_type" name="transportation_type" disabled>
                                    <option value="">-- Select Type --</option>
                                    <option value="Bus">Bus</option>
                                    <option value="Car">Car</option>
                                    <option value="Van">Van</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="assistance-banner">
                    <i class="fas fa-phone-alt me-2"></i> For any assistance in registration, please call: +91-1234567898
                </div>
                
                <div class="btn-container">
                    <button type="button" class="btn btn-secondary" onclick="showStep(1)"><i class="fas fa-arrow-left me-2"></i>Previous</button>
                    <button type="button" class="btn btn-primary" onclick="validateStep2()">Next <i class="fas fa-arrow-right ms-2"></i></button>
                </div>
            </div>
            
            <div class="step" id="step3">
                <h3 class="step-title">Step 3: Facilities Required</h3>
                
                <div class="form-section">
                    <div class="form-section-title">
                        <h4><i class="fas fa-building me-2"></i>Interview Facilities</h4>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="interview_rooms" class="form-label">Interview Rooms (1000sq ft)</label>
                            <input type="number" class="form-control" id="interview_rooms" name="interview_rooms" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label for="interview_panels" class="form-label">Interview Panels</label>
                            <input type="number" class="form-control" id="interview_panels" name="interview_panels" min="0" value="0">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Aptitude Test Hall Required</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="aptitude_hall" id="aptitude_yes" value="Yes">
                            <label class="form-check-label" for="aptitude_yes">Yes</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="aptitude_hall" id="aptitude_no" value="No" checked>
                            <label class="form-check-label" for="aptitude_no">No</label>
                        </div>
                    </div>
                    
                    <h5 class="mt-4 mb-3">Additional Requirements</h5>
                    
                    <div class="facility-item">
                        <label for="online_exam">Online Exam</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="online_exam" name="online_exam">
                            <label class="form-check-label" for="online_exam"></label>
                        </div>
                    </div>
                    
                    <div class="facility-item">
                        <label for="written_exam">Written Exam</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="written_exam" name="written_exam">
                            <label class="form-check-label" for="written_exam"></label>
                        </div>
                    </div>
                    
                    <div class="facility-item">
                        <label for="group_discussion">Group Discussion</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="group_discussion" name="group_discussion">
                            <label class="form-check-label" for="group_discussion"></label>
                        </div>
                    </div>
                </div>
                
                <div class="btn-container">
                    <button type="button" class="btn btn-secondary" onclick="showStep(2)"><i class="fas fa-arrow-left me-2"></i>Previous</button>
                    <button type="button" class="btn btn-primary" onclick="validateStep3()">Next <i class="fas fa-arrow-right ms-2"></i></button>
                </div>
            </div>
            
            <div class="step" id="step4">
                <h3 class="step-title">Step 4: Executive Details & Current Openings</h3>
                
                <div class="form-section">
                    <div class="form-section-title">
                        <h4><i class="fas fa-user-tie me-2"></i>Details of the Executive</h4>
                    </div>
                    
                    <div id="executiveContainer">
                        <div class="executive-form">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Name *</label>
                                    <input type="text" class="form-control" name="executive_name[]" required>
                                    <div class="error-message"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Designation *</label>
                                    <input type="text" class="form-control" name="executive_designation[]" required>
                                    <div class="error-message"></div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Mobile No *</label>
                                    <input type="tel" class="form-control" name="executive_mobile[]" pattern="[0-9]{10}" required>
                                    <div class="error-message"></div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Email Id *</label>
                                    <input type="email" class="form-control" name="executive_email[]" required>
                                    <div class="error-message"></div>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">Gender *</label>
                                    <select class="form-select" name="executive_gender[]" required>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <div class="error-message"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="add-btn" id="addExecutive">
                        <i class="fas fa-plus"></i>
                    </button>
                    <div class="error-message" id="executives-error"></div>
                </div>
                
                <div class="form-section mt-4">
                    <div class="form-section-title">
                        <h4><i class="fas fa-briefcase me-2"></i>Current Openings</h4>
                        <button type="button" class="btn btn-sm btn-primary" id="addJobOpening">
                            <i class="fas fa-plus me-1"></i> Add Job Opening
                        </button>
                    </div>
                    <p class="text-muted">Please mark NA for cut off percentage if not required.</p>
                    <div class="error-message" id="openings-error"></div>
                    
                    <div class="table-responsive table-container">
                        <table class="table table-bordered" id="openingsTable">
                            <thead>
                                <tr>
                                    <th>Vacancies</th>
                                    <th>Position</th>
                                    <th>Qualification</th>
                                    <th>Course</th>
                                    <th>Stream</th>
                                    <th>From CTC</th>
                                    <th>To CTC</th>
                                    <th>Cut Off%</th>
                                    <th>Location</th>
                                    <th>Job Desc.</th>
                                    <th>Min Exp</th>
                                    <th>Max Exp</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="job-opening-row">
                                    <td><input type="number" class="form-control" name="vacancies[]" min="0"></td>
                                    <td><input type="text" class="form-control" name="job_designation[]"></td>
                                    <td>
                                        <select class="form-select" name="qualification[]">
                                            <option value="">Select</option>
                                            <option value="SSLC">SSLC</option>
                                            <option value="PUC">PUC</option>
                                            <option value="Diploma">Diploma</option>
                                            <option value="Graduation">Graduation</option>
                                            <option value="Post Graduation">Post Graduation</option>
                                            <option value="Doctorate">Doctorate</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select" name="course[]">
                                            <option value="">Select</option>
                                            <option value="B.E">B.E</option>
                                            <option value="B.Tech">B.Tech</option>
                                            <option value="M.Tech">M.Tech</option>
                                            <option value="B.Sc">B.Sc</option>
                                            <option value="M.Sc">M.Sc</option>
                                            <option value="B.Com">B.Com</option>
                                            <option value="M.Com">M.Com</option>
                                            <option value="BCA">BCA</option>
                                            <option value="MCA">MCA</option>
                                            <option value="MBA">MBA</option>
                                            <option value="BBA">BBA</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select" name="stream[]">
                                            <option value="">Select</option>
                                            <option value="Computer Science">Computer Science</option>
                                            <option value="Electronics">Electronics</option>
                                            <option value="Mechanical">Mechanical</option>
                                            <option value="Civil">Civil</option>
                                            <option value="Information Science">Information Science</option>
                                            <option value="Electrical">Electrical</option>
                                            <option value="Chemical">Chemical</option>
                                            <option value="Biotechnology">Biotechnology</option>
                                            <option value="Aeronautical">Aeronautical</option>
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control" name="from_ctc[]" step="0.1" min="0"></td>
                                    <td><input type="number" class="form-control" name="to_ctc[]" step="0.1" min="0"></td>
                                    <td><input type="text" class="form-control" name="cut_off[]" placeholder="% or NA"></td>
                                    <td><input type="text" class="form-control" name="job_location[]"></td>
                                    <td><input type="text" class="form-control" name="job_description[]"></td>
                                    <td>
                                        <select class="form-select exp-select" name="exp_from[]">
                                            <option value="">Exp</option>
                                            <option value="0">0</option>
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                            <option value="5">5</option>
                                            <option value="6">6</option>
                                            <option value="7">7</option>
                                            <option value="8">8</option>
                                            <option value="9">9</option>
                                            <option value="10">10+</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select exp-select" name="exp_to[]">
                                            <option value="">Exp</option>
                                            <option value="0">0</option>
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                            <option value="5">5</option>
                                            <option value="6">6</option>
                                            <option value="7">7</option>
                                            <option value="8">8</option>
                                            <option value="9">9</option>
                                            <option value="10">10+</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="assistance-banner">
                    <i class="fas fa-phone-alt me-2"></i> For any assistance in registration, please call: +91-1234567898
                </div>
                
                <div class="btn-container">
                    <button type="button" class="btn btn-secondary" onclick="showStep(3)"><i class="fas fa-arrow-left me-2"></i>Previous</button>
                    <button type="button" class="btn btn-primary" onclick="validateStep4()">Next <i class="fas fa-arrow-right ms-2"></i></button>
                </div>
            </div>
            
            <div class="step" id="step5">
                <h3 class="step-title">Step 5: Review & Submit</h3>
                
                <div class="form-section">
                    <h4><i class="fas fa-building me-2"></i>Company Details</h4>
                    
                    <div class="summary-item row">
                        <div class="summary-label col-md-3">Organization Name:</div>
                        <div class="col-md-9" id="summary-organization">-</div>
                    </div>
                    
                    <div class="summary-item row">
                        <div class="summary-label col-md-3">Sector:</div>
                        <div class="col-md-9" id="summary-sector">-</div>
                    </div>
                    
                    <div class="summary-item row">
                        <div class="summary-label col-md-3">Contact Person:</div>
                        <div class="col-md-9" id="summary-contact">-</div>
                    </div>
                    
                    <div class="summary-item row">
                        <div class="summary-label col-md-3">Email ID:</div>
                        <div class="col-md-9" id="summary-email">-</div>
                    </div>
                    
                    <div class="summary-item row">
                        <div class="summary-label col-md-3">Mobile Number:</div>
                        <div class="col-md-9" id="summary-mobile">-</div>
                    </div>
                    
                    <div class="summary-item row">
                        <div class="summary-label col-md-3">Landline No:</div>
                        <div class="col-md-9" id="summary-landline">-</div>
                    </div>
                    
                    <div class="summary-item row">
                        <div class="summary-label col-md-3">Address:</div>
                        <div class="col-md-9" id="summary-address">-</div>
                    </div>
                </div>
                
                <div class="form-section mt-4">
                    <h4><i class="fas fa-hotel me-2"></i>Logistical Requirements</h4>
                    
                    <div class="summary-item row">
                        <div class="summary-label col-md-3">Accommodation Required:</div>
                        <div class="col-md-9" id="summary-accommodation">-</div>
                    </div>
                    
                    <div class="summary-item row">
                        <div class="summary-label col-md-3">Transportation Required:</div>
                        <div class="col-md-9" id="summary-transportation">-</div>
                    </div>
                </div>
                
                <div class="form-section mt-4">
                    <h4><i class="fas fa-building me-2"></i>Facilities Required</h4>
                    
                    <div class="summary-item row">
                        <div class="summary-label col-md-3">Interview Rooms:</div>
                        <div class="col-md-9" id="summary-rooms">-</div>
                    </div>
                    
                    <div class="summary-item row">
                        <div class="summary-label col-md-3">Interview Panels:</div>
                        <div class="col-md-9" id="summary-panels">-</div>
                    </div>
                    
                    <div class="summary-item row">
                        <div class="summary-label col-md-3">Aptitude Test Hall:</div>
                        <div class="col-md-9" id="summary-aptitude">-</div>
                    </div>
                    
                    <div class="summary-item row">
                        <div class="summary-label col-md-3">Online Exam:</div>
                        <div class="col-md-9" id="summary-online">-</div>
                    </div>
                    
                    <div class="summary-item row">
                        <div class="summary-label col-md-3">Written Exam:</div>
                        <div class="col-md-9" id="summary-written">-</div>
                    </div>
                    
                    <div class="summary-item row">
                        <div class="summary-label col-md-3">Group Discussion:</div>
                        <div class="col-md-9" id="summary-discussion">-</div>
                    </div>
                </div>
                
                <div class="form-section mt-4">
                    <h4><i class="fas fa-user-tie me-2"></i>Executive Details</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="summary-executives">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Designation</th>
                                    <th>Mobile No</th>
                                    <th>Email Id</th>
                                    <th>Gender</th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="form-section mt-4">
                    <h4><i class="fas fa-briefcase me-2"></i>Current Openings</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="summary-openings">
                            <thead>
                                <tr>
                                    <th>Vacancies</th>
                                    <th>Position</th>
                                    <th>Qualification</th>
                                    <th>Course</th>
                                    <th>Stream</th>
                                    <th>From CTC</th>
                                    <th>To CTC</th>
                                    <th>Cut Off%</th>
                                    <th>Location</th>
                                    <th>Job Desc.</th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="assistance-banner">
                    <i class="fas fa-phone-alt me-2"></i> For any assistance in registration, please call: +91-1234567898
                </div>
                
                <div class="btn-container">
                    <button type="button" class="btn btn-secondary" onclick="showStep(4)"><i class="fas fa-arrow-left me-2"></i>Previous</button>
                    <button type="submit" class="btn btn-success">Submit Registration <i class="fas fa-check ms-2"></i></button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Step navigation and progress bar
        let currentStep = 1;
        
        function showStep(step) {
            // Hide current step
            document.querySelector(`#step${currentStep}`).classList.remove('active');
            
            // Show new step
            document.querySelector(`#step${step}`).classList.add('active');
            
            // Update current step
            currentStep = step;
            
            // Update progress bar
            const progress = (step / 5) * 100;
            document.getElementById('form-progress').style.width = `${progress}%`;
            
            // Update step indicators
            document.querySelectorAll('.step-dot').forEach((dot, index) => {
                if (index < step) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
            
            // If we're on the summary page, populate the data
            if (step === 5) {
                populateSummary();
            }
        }
        
        // Gender selection
        document.querySelectorAll('.gender-option').forEach(option => {
            option.addEventListener('click', () => {
                // Remove selected class from all options
                document.querySelectorAll('.gender-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // Add selected class to clicked option
                option.classList.add('selected');
                
                // Update hidden input
                document.getElementById('gender').value = option.getAttribute('data-value');
            });
        });
        
        // Accommodation toggle
        document.getElementById('accommodation_required').addEventListener('change', function() {
            document.getElementById('accommodation_type').disabled = !this.checked;
            if (!this.checked) {
                document.getElementById('accommodation_type').value = '';
            }
        });
        
        // Transportation toggle
        document.getElementById('transportation_required').addEventListener('change', function() {
            document.getElementById('transportation_type').disabled = !this.checked;
            if (!this.checked) {
                document.getElementById('transportation_type').value = '';
            }
        });
        
        // Add executive
        document.getElementById('addExecutive').addEventListener('click', function() {
            const container = document.getElementById('executiveContainer');
            const newForm = document.createElement('div');
            newForm.className = 'executive-form mt-3 position-relative';
            
            newForm.innerHTML = `
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Name *</label>
                        <input type="text" class="form-control" name="executive_name[]" required>
                        <div class="error-message"></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Designation *</label>
                        <input type="text" class="form-control" name="executive_designation[]" required>
                        <div class="error-message"></div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Mobile No *</label>
                        <input type="tel" class="form-control" name="executive_mobile[]" pattern="[0-9]{10}" required>
                        <div class="error-message"></div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Email Id *</label>
                        <input type="email" class="form-control" name="executive_email[]" required>
                        <div class="error-message"></div>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Gender *</label>
                        <select class="form-select" name="executive_gender[]" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                        <div class="error-message"></div>
                    </div>
                </div>
                <button type="button" class="remove-btn position-absolute top-0 end-0" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i> Remove
                </button>
            `;
            
            container.appendChild(newForm);
        });
        
        // Add job opening
        document.getElementById('addJobOpening').addEventListener('click', function() {
            const table = document.querySelector('#openingsTable tbody');
            const newRow = document.createElement('tr');
            newRow.className = 'job-opening-row';
            
            newRow.innerHTML = `
                <td><input type="number" class="form-control" name="vacancies[]" min="0"></td>
                <td><input type="text" class="form-control" name="job_designation[]"></td>
                <td>
                    <select class="form-select" name="qualification[]">
                        <option value="">Select</option>
                        <option value="SSLC">SSLC</option>
                        <option value="PUC">PUC</option>
                        <option value="Diploma">Diploma</option>
                        <option value="Graduation">Graduation</option>
                        <option value="Post Graduation">Post Graduation</option>
                        <option value="Doctorate">Doctorate</option>
                    </select>
                </td>
                <td>
                    <select class="form-select" name="course[]">
                        <option value="">Select</option>
                        <option value="B.E">B.E</option>
                        <option value="B.Tech">B.Tech</option>
                        <option value="M.Tech">M.Tech</option>
                        <option value="B.Sc">B.Sc</option>
                        <option value="M.Sc">M.Sc</option>
                        <option value="B.Com">B.Com</option>
                        <option value="M.Com">M.Com</option>
                        <option value="BCA">BCA</option>
                        <option value="MCA">MCA</option>
                        <option value="MBA">MBA</option>
                        <option value="BBA">BBA</option>
                    </select>
                </td>
                <td>
                    <select class="form-select" name="stream[]">
                        <option value="">Select</option>
                        <option value="Computer Science">Computer Science</option>
                        <option value="Electronics">Electronics</option>
                        <option value="Mechanical">Mechanical</option>
                        <option value="Civil">Civil</option>
                        <option value="Information Science">Information Science</option>
                        <option value="Electrical">Electrical</option>
                        <option value="Chemical">Chemical</option>
                        <option value="Biotechnology">Biotechnology</option>
                        <option value="Aeronautical">Aeronautical</option>
                    </select>
                </td>
                <td><input type="number" class="form-control" name="from_ctc[]" step="0.1" min="0"></td>
                <td><input type="number" class="form-control" name="to_ctc[]" step="0.1" min="0"></td>
                <td><input type="text" class="form-control" name="cut_off[]" placeholder="% or NA"></td>
                <td><input type="text" class="form-control" name="job_location[]"></td>
                <td><input type="text" class="form-control" name="job_description[]"></td>
                <td>
                    <select class="form-select exp-select" name="exp_from[]">
                        <option value="">Exp</option>
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10+</option>
                    </select>
                </td>
                <td>
                    <select class="form-select exp-select" name="exp_to[]">
                        <option value="">Exp</option>
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10+</option>
                    </select>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            
            table.appendChild(newRow);
        });
        
        // Populate summary
        function populateSummary() {
            // Company details
            document.getElementById('summary-organization').textContent = 
                document.getElementById('organization_name').value || '-';
            document.getElementById('summary-sector').textContent = 
                document.getElementById('sector').value || '-';
            document.getElementById('summary-contact').textContent = 
                document.getElementById('contact_person').value || '-';
            document.getElementById('summary-email').textContent = 
                document.getElementById('email').value || '-';
            document.getElementById('summary-mobile').textContent = 
                (document.getElementById('country_code').value || '') + ' ' + 
                (document.getElementById('mobile').value || '-');
            document.getElementById('summary-landline').textContent = 
                document.getElementById('landline').value || '-';
            document.getElementById('summary-address').textContent = 
                document.getElementById('address').value || '-';
            
            // Logistics
            const accommodation = document.getElementById('accommodation_required');
            document.getElementById('summary-accommodation').textContent = 
                accommodation.checked ? 'Yes' : 'No';
            
            const transportation = document.getElementById('transportation_required');
            document.getElementById('summary-transportation').textContent = 
                transportation.checked ? 'Yes' : 'No';
            
            // Facilities
            document.getElementById('summary-rooms').textContent = 
                document.getElementById('interview_rooms').value || '0';
            document.getElementById('summary-panels').textContent = 
                document.getElementById('interview_panels').value || '0';
            
            const aptitude = document.querySelector('input[name="aptitude_hall"]:checked');
            document.getElementById('summary-aptitude').textContent = 
                aptitude ? aptitude.value : 'No';
            
            document.getElementById('summary-online').textContent = 
                document.getElementById('online_exam').checked ? 'Yes' : 'No';
            document.getElementById('summary-written').textContent = 
                document.getElementById('written_exam').checked ? 'Yes' : 'No';
            document.getElementById('summary-discussion').textContent = 
                document.getElementById('group_discussion').checked ? 'Yes' : 'No';
            
            // Executives
            const executivesTable = document.querySelector('#summary-executives tbody');
            executivesTable.innerHTML = '';
            
            const executiveNames = document.getElementsByName('executive_name[]');
            for (let i = 0; i < executiveNames.length; i++) {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${executiveNames[i].value}</td>
                    <td>${document.getElementsByName('executive_designation[]')[i].value}</td>
                    <td>${document.getElementsByName('executive_mobile[]')[i].value}</td>
                    <td>${document.getElementsByName('executive_email[]')[i].value}</td>
                    <td>${document.getElementsByName('executive_gender[]')[i].value}</td>
                `;
                executivesTable.appendChild(row);
            }
            
            // Job openings
            const openingsTable = document.querySelector('#summary-openings tbody');
            openingsTable.innerHTML = '';
            
            const vacancies = document.getElementsByName('vacancies[]');
            for (let i = 0; i < vacancies.length; i++) {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${vacancies[i].value}</td>
                    <td>${document.getElementsByName('job_designation[]')[i].value}</td>
                    <td>${document.getElementsByName('qualification[]')[i].value}</td>
                    <td>${document.getElementsByName('course[]')[i].value}</td>
                    <td>${document.getElementsByName('stream[]')[i].value}</td>
                    <td>${document.getElementsByName('from_ctc[]')[i].value}</td>
                    <td>${document.getElementsByName('to_ctc[]')[i].value}</td>
                    <td>${document.getElementsByName('cut_off[]')[i].value}</td>
                    <td>${document.getElementsByName('job_location[]')[i].value}</td>
                    <td>${document.getElementsByName('job_description[]')[i].value}</td>
                `;
                openingsTable.appendChild(row);
            }
        }
        
        // Client-side validation helpers
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        function validatePhone(phone) {
            return /^\d{10}$/.test(phone);
        }
        
        function showError(element, message) {
            // Clear previous error
            let errorElement = element.nextElementSibling;
            if (!errorElement || !errorElement.classList.contains('error-message')) {
                errorElement = document.createElement('div');
                errorElement.className = 'error-message';
                element.parentNode.appendChild(errorElement);
            }
            
            errorElement.textContent = message;
            element.classList.add('error-border');
        }
        
        function clearError(element) {
            element.classList.remove('error-border');
            const errorElement = element.nextElementSibling;
            if (errorElement && errorElement.classList.contains('error-message')) {
                errorElement.textContent = '';
            }
        }
        
        // Step validations
        function validateStep1() {
            let isValid = true;
            
            // Clear previous errors
            document.querySelectorAll('#step1 .error-message').forEach(el => el.textContent = '');
            document.querySelectorAll('#step1 .error-border').forEach(el => el.classList.remove('error-border'));
            
            // Organization name
            const orgName = document.getElementById('organization_name');
            if (!orgName.value.trim()) {
                showError(orgName, 'Organization name is required');
                isValid = false;
            }
            
            // Sector
            const sector = document.getElementById('sector');
            if (sector.value === '') {
                showError(sector, 'Sector is required');
                isValid = false;
            }
            
            // Contact person
            const contactPerson = document.getElementById('contact_person');
            if (!contactPerson.value.trim()) {
                showError(contactPerson, 'Contact person is required');
                isValid = false;
            }
            
            // Email
            const email = document.getElementById('email');
            if (!email.value.trim()) {
                showError(email, 'Email is required');
                isValid = false;
            } else if (!validateEmail(email.value)) {
                showError(email, 'Invalid email format');
                isValid = false;
            }
            
            // Designation
            const designation = document.getElementById('designation');
            if (designation.value === '') {
                showError(designation, 'Designation is required');
                isValid = false;
            }
            
            // Gender
            const gender = document.getElementById('gender');
            if (!gender.value) {
                document.getElementById('gender-error').textContent = 'Gender is required';
                isValid = false;
            } else {
                document.getElementById('gender-error').textContent = '';
            }
            
            // Mobile
            const mobile = document.getElementById('mobile');
            if (!mobile.value.trim()) {
                showError(mobile, 'Mobile number is required');
                isValid = false;
            } else if (!validatePhone(mobile.value)) {
                showError(mobile, 'Mobile must be 10 digits');
                isValid = false;
            }
            
            // Interview type
            const interviewType = document.getElementById('interview_type');
            if (interviewType.value === '') {
                showError(interviewType, 'Interview type is required');
                isValid = false;
            }
            
            // Address
            const address = document.getElementById('address');
            if (!address.value.trim()) {
                showError(address, 'Address is required');
                isValid = false;
            }
            
            if (isValid) {
                showStep(2);
            }
            
            return isValid;
        }
        
        function validateStep2() {
            // No required fields in step 2
            showStep(3);
            return true;
        }
        
        function validateStep3() {
            // No required fields in step 3
            showStep(4);
            return true;
        }
        
        function validateStep4() {
            let isValid = true;
            
            // Clear previous errors
            document.getElementById('executives-error').textContent = '';
            document.getElementById('openings-error').textContent = '';
            document.querySelectorAll('#step4 .error-message').forEach(el => el.textContent = '');
            document.querySelectorAll('#step4 .error-border').forEach(el => el.classList.remove('error-border'));
            
            // Validate executives
            const executiveNames = document.getElementsByName('executive_name[]');
            if (executiveNames.length === 0) {
                document.getElementById('executives-error').textContent = 'At least one executive is required';
                isValid = false;
            }
            
            for (let i = 0; i < executiveNames.length; i++) {
                const name = executiveNames[i];
                const designation = document.getElementsByName('executive_designation[]')[i];
                const mobile = document.getElementsByName('executive_mobile[]')[i];
                const email = document.getElementsByName('executive_email[]')[i];
                const gender = document.getElementsByName('executive_gender[]')[i];
                
                if (!name.value.trim()) {
                    showError(name, 'Name is required');
                    isValid = false;
                }
                
                if (!designation.value.trim()) {
                    showError(designation, 'Designation is required');
                    isValid = false;
                }
                
                if (!mobile.value.trim()) {
                    showError(mobile, 'Mobile is required');
                    isValid = false;
                } else if (!validatePhone(mobile.value)) {
                    showError(mobile, '10 digits required');
                    isValid = false;
                }
                
                if (!email.value.trim()) {
                    showError(email, 'Email is required');
                    isValid = false;
                } else if (!validateEmail(email.value)) {
                    showError(email, 'Invalid email format');
                    isValid = false;
                }
                
                if (!gender.value) {
                    const errorEl = gender.nextElementSibling;
                    errorEl.textContent = 'Gender is required';
                    isValid = false;
                }
            }
            
            // Validate job openings
            const vacancies = document.getElementsByName('vacancies[]');
            if (vacancies.length === 0) {
                document.getElementById('openings-error').textContent = 'At least one job opening is required';
                isValid = false;
            }
            
            for (let i = 0; i < vacancies.length; i++) {
                const vacancy = vacancies[i];
                const jobDesignation = document.getElementsByName('job_designation[]')[i];
                const qualification = document.getElementsByName('qualification[]')[i];
                const fromCTC = document.getElementsByName('from_ctc[]')[i];
                const toCTC = document.getElementsByName('to_ctc[]')[i];
                
                // Validate vacancies
                if (!vacancy.value || parseInt(vacancy.value) <= 0) {
                    vacancy.classList.add('error-border');
                    isValid = false;
                }
                
                // Validate job designation
                if (!jobDesignation.value.trim()) {
                    jobDesignation.classList.add('error-border');
                    isValid = false;
                }
                
                // Validate qualification
                if (!qualification.value) {
                    qualification.classList.add('error-border');
                    isValid = false;
                }
                
                // Validate CTC range
                if (parseFloat(toCTC.value) < parseFloat(fromCTC.value)) {
                    fromCTC.classList.add('error-border');
                    toCTC.classList.add('error-border');
                    isValid = false;
                }
            }
            
            if (isValid) {
                showStep(5);
            }
            
            return isValid;
        }
        
        // Form submission validation
        function validateForm() {
            return validateStep1() && validateStep4();
        }
        
        // Attach event listeners for real-time validation
        document.getElementById('organization_name').addEventListener('input', function() {
            clearError(this);
        });
        
        document.getElementById('sector').addEventListener('change', function() {
            clearError(this);
        });
        
        document.getElementById('contact_person').addEventListener('input', function() {
            clearError(this);
        });
        
        document.getElementById('email').addEventListener('input', function() {
            clearError(this);
        });
        
        document.getElementById('designation').addEventListener('change', function() {
            clearError(this);
        });
        
        document.getElementById('mobile').addEventListener('input', function() {
            clearError(this);
        });
        
        document.getElementById('interview_type').addEventListener('change', function() {
            clearError(this);
        });
        
        document.getElementById('address').addEventListener('input', function() {
            clearError(this);
        });
        
        // Form submission
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                alert('Please fix the errors in the form before submitting.');
            }
        });
    </script>
</body>
</html>