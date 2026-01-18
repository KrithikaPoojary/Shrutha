<?php
// apply.php
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize variables
$job = null;
$success = false;
$errors = [];

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if job_id is provided
if (!isset($_GET['job_id']) || !is_numeric($_GET['job_id'])) {
    header("Location: jobs.php");
    exit();
}

$job_id = intval($_GET['job_id']);

// Get job details
$job_query = "SELECT j.*, c.organization_name FROM job_openings j 
              JOIN companies c ON j.company_id = c.id 
              WHERE j.id = ?";
$stmt = mysqli_prepare($conn, $job_query);
if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}

if (!mysqli_stmt_bind_param($stmt, "i", $job_id)) {
    die("Bind failed: " . mysqli_stmt_error($stmt));
}

if (!mysqli_stmt_execute($stmt)) {
    die("Execute failed: " . mysqli_stmt_error($stmt));
}

$result = mysqli_stmt_get_result($stmt);
$job = mysqli_fetch_assoc($result);

if (!$job) {
    header("Location: jobs.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $current_company = trim($_POST['current_company'] ?? '');
    $current_position = trim($_POST['current_position'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $pg_course = trim($_POST['pg_course'] ?? '');
    $pg_passing_year = trim($_POST['pg_passing_year'] ?? '');
    $pg_college = trim($_POST['pg_college'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $passing_year = trim($_POST['passing_year'] ?? '');
    $college = trim($_POST['college'] ?? '');
    $puc_course = trim($_POST['puc_course'] ?? '');
    $puc_passing_year = trim($_POST['puc_passing_year'] ?? '');
    $puc_college = trim($_POST['puc_college'] ?? '');
    $linkedin = trim($_POST['linkedin'] ?? '');
    $cover_letter = trim($_POST['cover_letter'] ?? '');
    
    // Validation
    if (empty($name)) $errors['name'] = 'Full name is required';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email is required';
    if (empty($phone)) $errors['phone'] = 'Phone number is required';
    if (empty($address)) $errors['address'] = 'Address is required';
    if (empty($city)) $errors['city'] = 'City is required';
    if (empty($state)) $errors['state'] = 'State/Province is required';
    if (empty($zip)) $errors['zip'] = 'Postal/Zip code is required';
    if (empty($country)) $errors['country'] = 'Country is required';
    if (empty($experience)) $errors['experience'] = 'Years of experience is required';
    if (empty($skills)) $errors['skills'] = 'Skills are required';
    if (empty($course)) $errors['course'] = 'Course is required';
    if (empty($passing_year)) $errors['passing_year'] = 'Passing year is required';
    if (empty($college)) $errors['college'] = 'College name is required';
    
    // Handle file upload with improved error checking
    $resume_path = null;
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = [
            'application/pdf', 
            'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/octet-stream'
        ];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Verify file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES['resume']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            $errors['resume'] = 'Only PDF and Word documents are allowed (detected type: ' . $mime_type . ')';
        } elseif ($_FILES['resume']['size'] > $max_size) {
            $errors['resume'] = 'File size must be less than 5MB';
        } else {
            $upload_dir = 'uploads/resumes/';
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    $errors['resume'] = 'Failed to create upload directory';
                }
            }
            
            if (empty($errors)) {
                $file_ext = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('resume_', true) . '.' . strtolower($file_ext);
                $resume_path = $upload_dir . $filename;
                
                if (!move_uploaded_file($_FILES['resume']['tmp_name'], $resume_path)) {
                    $errors['resume'] = 'Failed to upload resume';
                    $resume_path = null;
                }
            }
        }
    } else {
        $upload_error = $_FILES['resume']['error'] ?? UPLOAD_ERR_NO_FILE;
        switch ($upload_error) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors['resume'] = 'File size exceeds limit';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errors['resume'] = 'File upload was incomplete';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errors['resume'] = 'No file was uploaded';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errors['resume'] = 'Missing temporary folder';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errors['resume'] = 'Failed to write file to disk';
                break;
            case UPLOAD_ERR_EXTENSION:
                $errors['resume'] = 'File upload stopped by extension';
                break;
            default:
                $errors['resume'] = 'Unknown upload error';
        }
    }
    
    // If no errors and resume was uploaded, save to database
    if (empty($errors) && $resume_path !== null) {
        // Combine education details
        $education_parts = [];
        if (!empty($pg_course)) {
            $education_parts[] = "PG: $pg_course ($pg_passing_year) - $pg_college";
        }
        $education_parts[] = "Degree: $course ($passing_year) - $college";
        if (!empty($puc_course)) {
            $education_parts[] = "PUC: $puc_course ($puc_passing_year) - $puc_college";
        }
        $education = implode(' | ', $education_parts);
        
        $insert_query = "INSERT INTO applications 
                (job_id, name, email, phone, address, city, state, zip, country, 
                experience, current_company, current_position, skills, education, 
                linkedin, cover_letter, resume_path, applied_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
        $stmt = mysqli_prepare($conn, $insert_query);
        if (!$stmt) {
            die("Prepare failed: " . mysqli_error($conn));
        }
        
        // Bind parameters
        $bind_result = mysqli_stmt_bind_param($stmt, "issssssssssssssss", 
            $job_id, $name, $email, $phone, $address, $city, $state, $zip, $country,
            $experience, $current_company, $current_position, $skills, $education,
            $linkedin, $cover_letter, $resume_path);
        
        if (!$bind_result) {
            die("Bind failed: " . mysqli_stmt_error($stmt));
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $success = true;
            
        } else {
            // Delete the uploaded file if database insert failed
            if (file_exists($resume_path)) {
                unlink($resume_path);
            }
            error_log("Database error: " . mysqli_error($conn));
            $errors['database'] = 'Failed to submit application. Please try again. Error: ' . mysqli_error($conn);
        }
    } elseif ($resume_path === null && !isset($errors['resume'])) {
        $errors['resume'] = 'Resume upload failed or was not provided';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for <?= htmlspecialchars($job['job_designation']) ?> | <?= htmlspecialchars($job['organization_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
            --success-color: #28a745;
        }
        
        body {
            background-color: var(--light-gray);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            line-height: 1.6;
        }
        
        .application-container {
            max-width: 1000px;
            margin: 30px auto;
        }
        
        .application-header {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
            padding: 30px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 0;
        }
        
        .application-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .form-container {
            padding: 30px;
        }
        
        .section-title {
            color: var(--secondary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark-gray);
        }
        
        .form-control, .form-select {
            border-radius: 4px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        
        .is-invalid {
            border-color: #dc3545;
        }
        
        .invalid-feedback {
            color: #dc3545;
            display: block;
            font-size: 0.85rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-outline-secondary {
            padding: 10px 25px;
            font-weight: 500;
        }
        
        .file-upload {
            position: relative;
            overflow: hidden;
        }
        
        .file-upload-input {
            position: absolute;
            font-size: 100px;
            opacity: 0;
            right: 0;
            top: 0;
            cursor: pointer;
        }
        
        .file-upload-label {
            display: block;
            padding: 10px;
            background: var(--light-gray);
            border: 1px dashed #ced4da;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-upload-label:hover {
            background: #e9ecef;
        }
        
        .success-message {
            text-align: center;
            padding: 50px 30px;
        }
        
        .success-icon {
            font-size: 4rem;
            color: var(--success-color);
            margin-bottom: 20px;
        }
        
        .form-note {
            font-size: 0.85rem;
            color: #6c757d;
            font-style: italic;
        }
        
        .progress-container {
            margin-top: 10px;
            display: none;
        }
        
        .progress-bar {
            background-color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .application-header {
                padding: 20px;
            }
            
            .form-container {
                padding: 20px;
            }
        }
        .skills-input-container {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 8px;
            background: white;
            min-height: 45px;
        }

        .skill-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 8px;
        }

        .skill-tag {
            background-color: #e9ecef;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .skill-tag .remove-skill {
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            font-size: 1rem;
            padding: 0;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .skill-tag .remove-skill:hover {
            background-color: #dc3545;
            color: white;
        }

        #skill-input {
            border: none;
            outline: none;
            width: 100%;
            padding: 0;
            background: transparent;
        }

        #skill-input:focus {
            box-shadow: none;
            border: none;
        }
    </style>
</head>
<body>
    <div class="application-container">
        <div class="application-card">
            <div class="application-header">
                <h2 class="mb-3"><i class="fas fa-user-tie me-2"></i><?= htmlspecialchars($job['job_designation']) ?></h2>
                <h4 class="mb-0"><i class="fas fa-building me-2"></i><?= htmlspecialchars($job['organization_name']) ?></h4>
            </div>
            
            <?php if ($success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle success-icon"></i>
                    <h3 class="mb-3">Application Submitted Successfully!</h3>
                    <p class="lead">Thank you for applying to <?= htmlspecialchars($job['organization_name']) ?>.</p>
                    <p>We've received your application for the <strong><?= htmlspecialchars($job['job_designation']) ?></strong> position.</p>
                    <p>Our HR team will review your application and contact you if your qualifications match our requirements.</p>
                    <div class="mt-4">
                        <a href="jobs.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Job Openings
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-home me-2"></i>Return Home
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="form-container">
                    <h4 class="section-title"><i class="fas fa-user-edit me-2"></i>Application Form</h4>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger mb-4">
                            <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h5>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" id="applicationForm">
                        <!-- Personal Information Section -->
                        <h5 class="mt-5 mb-3 text-primary"><i class="fas fa-user-circle me-2"></i>Personal Information</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                       id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                                <?php if (isset($errors['name'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                       id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" 
                                       id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
                                <?php if (isset($errors['phone'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['phone']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>" 
                                       id="address" name="address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" required>
                                <?php if (isset($errors['address'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['address']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['city']) ? 'is-invalid' : '' ?>" 
                                       id="city" name="city" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" required>
                                <?php if (isset($errors['city'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['city']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="state" class="form-label">State/Province <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['state']) ? 'is-invalid' : '' ?>" 
                                       id="state" name="state" value="<?= htmlspecialchars($_POST['state'] ?? '') ?>" required>
                                <?php if (isset($errors['state'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['state']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2">
                                <label for="zip" class="form-label">Postal Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['zip']) ? 'is-invalid' : '' ?>" 
                                       id="zip" name="zip" value="<?= htmlspecialchars($_POST['zip'] ?? '') ?>" required>
                                <?php if (isset($errors['zip'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['zip']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2">
                                <label for="country" class="form-label">Country <span class="text-danger">*</span></label>
                                <select class="form-select <?= isset($errors['country']) ? 'is-invalid' : '' ?>" 
                                        id="country" name="country" required>
                                    <option value="">Select...</option>
                                    <option value="USA" <?= (isset($_POST['country']) && $_POST['country'] === 'USA') ? 'selected' : '' ?>>United States</option>
                                    <option value="Canada" <?= (isset($_POST['country']) && $_POST['country'] === 'Canada') ? 'selected' : '' ?>>Canada</option>
                                    <option value="UK" <?= (isset($_POST['country']) && $_POST['country'] === 'UK') ? 'selected' : '' ?>>United Kingdom</option>
                                    <option value="India" <?= (isset($_POST['country']) && $_POST['country'] === 'India') ? 'selected' : '' ?>>India</option>
                                    <option value="Australia" <?= (isset($_POST['country']) && $_POST['country'] === 'Australia') ? 'selected' : '' ?>>Australia</option>
                                    <option value="Other" <?= (isset($_POST['country']) && $_POST['country'] === 'Other') ? 'selected' : '' ?>>Other</option>
                                </select>
                                <?php if (isset($errors['country'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['country']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Professional Information Section -->
                        <h5 class="mt-5 mb-3 text-primary"><i class="fas fa-briefcase me-2"></i>Professional Information</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="experience" class="form-label">Years of Experience <span class="text-danger">*</span></label>
                                <select class="form-select <?= isset($errors['experience']) ? 'is-invalid' : '' ?>" 
                                        id="experience" name="experience" required>
                                    <option value="">Select...</option>
                                    <option value="0-1 years" <?= (isset($_POST['experience']) && $_POST['experience'] === '0-1 years') ? 'selected' : '' ?>>0-1 years</option>
                                    <option value="1-3 years" <?= (isset($_POST['experience']) && $_POST['experience'] === '1-3 years') ? 'selected' : '' ?>>1-3 years</option>
                                    <option value="3-5 years" <?= (isset($_POST['experience']) && $_POST['experience'] === '3-5 years') ? 'selected' : '' ?>>3-5 years</option>
                                    <option value="5-7 years" <?= (isset($_POST['experience']) && $_POST['experience'] === '5-7 years') ? 'selected' : '' ?>>5-7 years</option>
                                    <option value="7-10 years" <?= (isset($_POST['experience']) && $_POST['experience'] === '7-10 years') ? 'selected' : '' ?>>7-10 years</option>
                                    <option value="10+ years" <?= (isset($_POST['experience']) && $_POST['experience'] === '10+ years') ? 'selected' : '' ?>>10+ years</option>
                                </select>
                                <?php if (isset($errors['experience'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['experience']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="current_company" class="form-label">Current Company</label>
                                <input type="text" class="form-control" id="current_company" name="current_company" 
                                       value="<?= htmlspecialchars($_POST['current_company'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="current_position" class="form-label">Current Position</label>
                                <input type="text" class="form-control" id="current_position" name="current_position" 
                                       value="<?= htmlspecialchars($_POST['current_position'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="linkedin" class="form-label">LinkedIn Profile</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                                    <input type="url" class="form-control" id="linkedin" name="linkedin" 
                                           placeholder="https://linkedin.com/in/yourprofile" 
                                           value="<?= htmlspecialchars($_POST['linkedin'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Skills & Education Section -->
                        <h5 class="mt-5 mb-3 text-primary"><i class="fas fa-graduation-cap me-2"></i>Skills & Education</h5>
                        <div class="row g-3">
                            <!-- Skills Input with Tag Interface -->
                            <div class="col-md-12">
                                <label for="skills" class="form-label">Skills <span class="text-danger">*</span></label>
                                <div class="skills-input-container">
                                    <div id="skill-tags" class="skill-tags">
                                        <!-- Skill tags will be dynamically added here -->
                                    </div>
                                    <input type="text" id="skill-input" class="form-control <?= isset($errors['skills']) ? 'is-invalid' : '' ?>" 
                                        placeholder="Type a skill and press Enter to add" list="skill-suggestions">
                                    <datalist id="skill-suggestions">
                                        <option value="C">C</option>
                                        <option value="C++">C++</option>
                                        <option value="C#">C#</option>
                                        <option value="Customer Service">Customer Service</option>
                                        <option value="C (Programming Language)">C (Programming Language)</option>
                                        <option value="Change Management">Change Management</option>
                                        <option value="Communication">Communication</option>
                                        <option value="Coaching">Coaching</option>
                                        <option value="Customer Relationship Management (CRM)">Customer Relationship Management (CRM)</option>
                                        <option value="Contract Negotiation">Contract Negotiation</option>
                                        <option value="JavaScript">JavaScript</option>
                                        <option value="Python">Python</option>
                                        <option value="Java">Java</option>
                                        <option value="PHP">PHP</option>
                                        <option value="HTML">HTML</option>
                                        <option value="CSS">CSS</option>
                                        <option value="React">React</option>
                                        <option value="Node.js">Node.js</option>
                                        <option value="MySQL">MySQL</option>
                                        <option value="MongoDB">MongoDB</option>
                                        <option value="Git">Git</option>
                                        <option value="AWS">AWS</option>
                                        <option value="Docker">Docker</option>
                                        <option value="Problem Solving">Problem Solving</option>
                                        <option value="Teamwork">Teamwork</option>
                                        <option value="Leadership">Leadership</option>
                                    </datalist>
                                    <input type="hidden" id="skills" name="skills" value="<?= htmlspecialchars($_POST['skills'] ?? '') ?>">
                                </div>
                                <?php if (isset($errors['skills'])): ?>
                                    <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['skills']) ?></div>
                                <?php endif; ?>
                                <div class="form-note">Type a skill and press Enter to add it. Click 'X' to remove a skill.</div>
                            </div>

                            <!-- PG Degree Section -->
                            <div class="col-md-6">
                                <label for="pg_course" class="form-label">PG Course/Degree</label>
                                <input type="text" class="form-control" id="pg_course" name="pg_course" 
                                    value="<?= htmlspecialchars($_POST['pg_course'] ?? '') ?>">
                                <div class="form-note">e.g., M.Tech in Computer Science</div>
                            </div>
                            <div class="col-md-3">
                                <label for="pg_passing_year" class="form-label">PG Passing Year</label>
                                <input type="text" class="form-control" id="pg_passing_year" name="pg_passing_year" 
                                    value="<?= htmlspecialchars($_POST['pg_passing_year'] ?? '') ?>">
                                <div class="form-note">e.g., 2022</div>
                            </div>
                            <div class="col-md-3">
                                <label for="pg_college" class="form-label">PG College/University</label>
                                <input type="text" class="form-control" id="pg_college" name="pg_college" 
                                    value="<?= htmlspecialchars($_POST['pg_college'] ?? '') ?>">
                                <div class="form-note">e.g., University of XYZ</div>
                            </div>

                            <!-- Degree Section (Existing) -->
                            <div class="col-md-6">
                                <label for="course" class="form-label">Course/Degree <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['course']) ? 'is-invalid' : '' ?>" 
                                    id="course" name="course" value="<?= htmlspecialchars($_POST['course'] ?? '') ?>" required>
                                <?php if (isset($errors['course'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['course']) ?></div>
                                <?php endif; ?>
                                <div class="form-note">e.g., B.Sc in Computer Science</div>
                            </div>
                            <div class="col-md-3">
                                <label for="passing_year" class="form-label">Passing Year <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['passing_year']) ? 'is-invalid' : '' ?>" 
                                    id="passing_year" name="passing_year" value="<?= htmlspecialchars($_POST['passing_year'] ?? '') ?>" required>
                                <?php if (isset($errors['passing_year'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['passing_year']) ?></div>
                                <?php endif; ?>
                                <div class="form-note">e.g., 2019</div>
                            </div>
                            <div class="col-md-3">
                                <label for="college" class="form-label">College/University <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['college']) ? 'is-invalid' : '' ?>" 
                                    id="college" name="college" value="<?= htmlspecialchars($_POST['college'] ?? '') ?>" required>
                                <?php if (isset($errors['college'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['college']) ?></div>
                                <?php endif; ?>
                                <div class="form-note">e.g., University of XYZ</div>
                            </div>

                            <!-- PUC Section -->
                            <div class="col-md-6">
                                <label for="puc_course" class="form-label">PUC/Course</label>
                                <input type="text" class="form-control" id="puc_course" name="puc_course" 
                                    value="<?= htmlspecialchars($_POST['puc_course'] ?? '') ?>">
                                <div class="form-note">e.g., PUC Science</div>
                            </div>
                            <div class="col-md-3">
                                <label for="puc_passing_year" class="form-label">PUC Passing Year</label>
                                <input type="text" class="form-control" id="puc_passing_year" name="puc_passing_year" 
                                    value="<?= htmlspecialchars($_POST['puc_passing_year'] ?? '') ?>">
                                <div class="form-note">e.g., 2017</div>
                            </div>
                            <div class="col-md-3">
                                <label for="puc_college" class="form-label">PUC College</label>
                                <input type="text" class="form-control" id="puc_college" name="puc_college" 
                                    value="<?= htmlspecialchars($_POST['puc_college'] ?? '') ?>">
                                <div class="form-note">e.g., ABC PU College</div>
                            </div>
                        </div>
                        
                        <!-- Cover Letter Section -->
                        <h5 class="mt-5 mb-3 text-primary"><i class="fas fa-envelope me-2"></i>Cover Letter</h5>
                        <div class="mb-4">
                            <label for="cover_letter" class="form-label">Why are you a good fit for this position?</label>
                            <textarea class="form-control" id="cover_letter" name="cover_letter" rows="5"><?= htmlspecialchars($_POST['cover_letter'] ?? '') ?></textarea>
                            <div class="form-note">Tell us why you're interested in this position and what makes you a strong candidate (optional but recommended)</div>
                        </div>
                        
                        <!-- Resume Upload Section -->
                        <h5 class="mt-5 mb-3 text-primary"><i class="fas fa-file-upload me-2"></i>Resume Upload</h5>
                        <div class="mb-4">
                            <label for="resume" class="form-label">Upload Your Resume <span class="text-danger">*</span></label>
                            <div class="file-upload">
                                <input type="file" class="form-control file-upload-input <?= isset($errors['resume']) ? 'is-invalid' : '' ?>" 
                                       id="resume" name="resume" accept=".pdf,.doc,.docx" required>
                                <label for="resume" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                    <h6>Choose a file or drag it here</h6>
                                    <p class="small text-muted">PDF or Word document (max 5MB)</p>
                                    <?php if (isset($_FILES['resume']['name']) && !empty($_FILES['resume']['name'])): ?>
                                        <p class="text-success mt-2"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_FILES['resume']['name']) ?></p>
                                    <?php endif; ?>
                                </label>
                                <?php if (isset($errors['resume'])): ?>
                                    <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['resume']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="progress-container mt-2">
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="upload-status">Uploading: <span class="percent">0</span>%</small>
                            </div>
                        </div>
                        
                        <!-- Form Submission -->
                        <div class="d-flex justify-content-between align-items-center mt-5 pt-3 border-top">
                            <a href="jobs.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Submit Application
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File upload preview
        document.getElementById('resume').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                const label = document.querySelector('.file-upload-label');
                label.innerHTML = `
                    <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                    <h6>${fileName}</h6>
                    <p class="small text-muted">Click to change file</p>
                `;
            }
        });
        
        // Form submission with progress indicator
        document.getElementById('applicationForm').addEventListener('submit', function(e) {
            const form = e.target;
            const progressContainer = document.querySelector('.progress-container');
            const progressBar = document.querySelector('.progress-bar');
            const uploadStatus = document.querySelector('.upload-status .percent');
            
            // Only show progress if files are being uploaded
            if (document.getElementById('resume').files.length > 0) {
                progressContainer.style.display = 'block';
                
                const xhr = new XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(event) {
                    if (event.lengthComputable) {
                        const percentComplete = Math.round((event.loaded / event.total) * 100);
                        progressBar.style.width = percentComplete + '%';
                        uploadStatus.textContent = percentComplete;
                    }
                }, false);
                
                return true;
            }
        });
        // Skills tag functionality
        document.addEventListener('DOMContentLoaded', function() {
            const skillInput = document.getElementById('skill-input');
            const skillTags = document.getElementById('skill-tags');
            const hiddenSkills = document.getElementById('skills');
            
            // Initialize skills from hidden input
            const initialSkills = hiddenSkills.value ? hiddenSkills.value.split(',').map(s => s.trim()).filter(s => s) : [];
            initialSkills.forEach(skill => addSkillTag(skill));
            
            // Add skill on Enter key or when selecting from datalist
            skillInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const skill = skillInput.value.trim();
                    if (skill && !skillExists(skill)) {
                        addSkillTag(skill);
                        skillInput.value = '';
                    }
                }
            });
            
            // Add skill when selecting from datalist
            skillInput.addEventListener('change', function() {
                const skill = skillInput.value.trim();
                if (skill && !skillExists(skill)) {
                    addSkillTag(skill);
                    skillInput.value = '';
                }
            });
            
            function skillExists(skill) {
                const currentSkills = hiddenSkills.value.split(',').map(s => s.trim()).filter(s => s);
                return currentSkills.includes(skill);
            }
            
            function addSkillTag(skill) {
                // Add to hidden input
                const currentSkills = hiddenSkills.value ? hiddenSkills.value.split(',').map(s => s.trim()).filter(s => s) : [];
                currentSkills.push(skill);
                hiddenSkills.value = currentSkills.join(', ');
                
                // Create visual tag
                const tag = document.createElement('div');
                tag.className = 'skill-tag';
                tag.innerHTML = `
                    ${skill}
                    <button type="button" class="remove-skill" data-skill="${skill}">&times;</button>
                `;
                skillTags.appendChild(tag);
                
                // Add remove functionality
                tag.querySelector('.remove-skill').addEventListener('click', function() {
                    removeSkillTag(skill, tag);
                });
            }
            
            function removeSkillTag(skill, tagElement) {
                // Remove from hidden input
                const currentSkills = hiddenSkills.value.split(',').map(s => s.trim()).filter(s => s);
                const index = currentSkills.indexOf(skill);
                if (index > -1) {
                    currentSkills.splice(index, 1);
                    hiddenSkills.value = currentSkills.join(', ');
                }
                
                // Remove visual tag
                tagElement.remove();
            }
        });
    </script>
</body>
</html>