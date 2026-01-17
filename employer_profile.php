<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'employer') {
    header("Location: login.php");
    exit;
}

$page_title = "Employer Profile";
include 'header.php';
$user_id = $_SESSION['user'];

// Fetch user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Fetch company details
$company = null;
$company_sql = "SELECT * FROM companies WHERE email = ? ORDER BY id DESC LIMIT 1";
$stmt_company = mysqli_prepare($conn, $company_sql);
mysqli_stmt_bind_param($stmt_company, "s", $user['email']);
mysqli_stmt_execute($stmt_company);
$company_result = mysqli_stmt_get_result($stmt_company);
$company = mysqli_fetch_assoc($company_result);

// Fetch registration details for the unique ID
$registration = null;
if ($company) {
    $registration_sql = "SELECT unique_id, created_at as registration_date FROM companies WHERE id = ?";
    $stmt_reg = mysqli_prepare($conn, $registration_sql);
    mysqli_stmt_bind_param($stmt_reg, "i", $company['id']);
    mysqli_stmt_execute($stmt_reg);
    $registration_result = mysqli_stmt_get_result($stmt_reg);
    $registration = mysqli_fetch_assoc($registration_result);
}

// Handle form submission
$success_msg = $error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    function sanitize($input) {
        global $conn;
        return mysqli_real_escape_string($conn, trim($input));
    }
    
    // Get form data
    $organization_name = sanitize($_POST['organization_name']);
    $sector = sanitize($_POST['sector']);
    $contact_person = sanitize($_POST['contact_person']);
    $designation = sanitize($_POST['designation']);
    $gender = sanitize($_POST['gender']);
    $country_code = sanitize($_POST['country_code']);
    $mobile = sanitize($_POST['mobile']);
    $landline = sanitize($_POST['landline'] ?? '');
    $address = sanitize($_POST['address']);
    $interview_type = sanitize($_POST['interview_type']);
    
    // Boolean fields
    $accommodation_required = isset($_POST['accommodation_required']) ? 1 : 0;
    $accommodation_type = sanitize($_POST['accommodation_type'] ?? '');
    $transportation_required = isset($_POST['transportation_required']) ? 1 : 0;
    $transportation_type = sanitize($_POST['transportation_type'] ?? '');
    
    // Facility requirements
    $interview_rooms = sanitize($_POST['interview_rooms'] ?? 0);
    $interview_panels = sanitize($_POST['interview_panels'] ?? 0);
    $aptitude_hall = isset($_POST['aptitude_hall']) ? 1 : 0;
    $online_exam = isset($_POST['online_exam']) ? 1 : 0;
    $written_exam = isset($_POST['written_exam']) ? 1 : 0;
    $group_discussion = isset($_POST['group_discussion']) ? 1 : 0;
    
    // Handle profile image upload
    $profile_image = $company['profile_image'] ?? null;

    // Check if user wants to remove the photo
    if (isset($_POST['remove_photo']) && $_POST['remove_photo'] == '1') {
        // Delete the old file if it exists
        if (!empty($company['profile_image']) && file_exists($company['profile_image'])) {
            unlink($company['profile_image']);
        }
        $profile_image = NULL; // Set to NULL for DB update

    // Check if a new photo is being uploaded (and 'remove' wasn't checked)
    } elseif (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/employers/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            $filename = 'employer_' . $user_id . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
                // Delete old profile image if exists
                if (!empty($company['profile_image']) && file_exists($company['profile_image'])) {
                    unlink($company['profile_image']);
                }
                $profile_image = $target_path;
            } else {
                $error_msg = "Error uploading profile image.";
            }
        } else {
            $error_msg = "Only JPG, PNG, and GIF files are allowed for profile image.";
        }
    }
    
    if ($company) {
        // Update existing company
        $update_sql = "UPDATE companies SET 
            organization_name = ?, sector = ?, contact_person = ?, designation = ?, 
            gender = ?, country_code = ?, mobile = ?, landline = ?, address = ?, 
            interview_type = ?, accommodation_required = ?, accommodation_type = ?, 
            transportation_required = ?, transportation_type = ?, interview_rooms = ?, 
            interview_panels = ?, aptitude_hall = ?, online_exam = ?, written_exam = ?, 
            group_discussion = ?, profile_image = ?
            WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "sssssssssssssssssssssi", 
            $organization_name, $sector, $contact_person, $designation, $gender,
            $country_code, $mobile, $landline, $address, $interview_type,
            $accommodation_required, $accommodation_type, $transportation_required,
            $transportation_type, $interview_rooms, $interview_panels, $aptitude_hall,
            $online_exam, $written_exam, $group_discussion, $profile_image, $company['id']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Company details updated successfully!";
            // Refresh company data
            $company_sql = "SELECT * FROM companies WHERE email = ? ORDER BY id DESC LIMIT 1";
            $stmt_company = mysqli_prepare($conn, $company_sql);
            mysqli_stmt_bind_param($stmt_company, "s", $user['email']);
            mysqli_stmt_execute($stmt_company);
            $company_result = mysqli_stmt_get_result($stmt_company);
            $company = mysqli_fetch_assoc($company_result);
        } else {
            $error_msg = "Error updating company details: " . mysqli_error($conn);
        }
    } else {
        // Insert new company (shouldn't happen if already registered, but just in case)
        $insert_sql = "INSERT INTO companies (
            organization_name, sector, contact_person, email, designation, gender,
            country_code, mobile, landline, address, interview_type, accommodation_required,
            accommodation_type, transportation_required, transportation_type, interview_rooms,
            interview_panels, aptitude_hall, online_exam, written_exam, group_discussion, profile_image
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "ssssssssssssssssssssss", 
            $organization_name, $sector, $contact_person, $user['email'], $designation, $gender,
            $country_code, $mobile, $landline, $address, $interview_type,
            $accommodation_required, $accommodation_type, $transportation_required,
            $transportation_type, $interview_rooms, $interview_panels, $aptitude_hall,
            $online_exam, $written_exam, $group_discussion, $profile_image
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Company details saved successfully!";
            // Refresh company data
            $company_sql = "SELECT * FROM companies WHERE email = ? ORDER BY id DESC LIMIT 1";
            $stmt_company = mysqli_prepare($conn, $company_sql);
            mysqli_stmt_bind_param($stmt_company, "s", $user['email']);
            mysqli_stmt_execute($stmt_company);
            $company_result = mysqli_stmt_get_result($stmt_company);
            $company = mysqli_fetch_assoc($company_result);
        } else {
            $error_msg = "Error saving company details: " . mysqli_error($conn);
        }
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
            --primary: #3a0ca3;
            --primary-light: #4361ee;
            --secondary: #7209b7;
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
        
        .profile-photo-section {
            position: relative;
        }
        
        .section-header {
            border-bottom: 2px solid #4361ee;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .form-check-inline {
            margin-right: 15px;
            margin-bottom: 5px;
        }
        
        .nav-buttons {
            margin-bottom: 20px;
        }
        
        .nav-buttons .btn {
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .profile-stats {
            border-top: 1px solid #dee2e6;
            padding-top: 1rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <a href="employer_dashboard.php" class="btn btn-outline-secondary" style="text-decoration: none; position: absolute; left: 40px; box-shadow: none; margin-top: 8px;">
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
                            <?php if ($company && !empty($company['profile_image']) && file_exists($company['profile_image'])): ?>
                                <img src="<?= htmlspecialchars($company['profile_image']) ?>" 
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
                                <?php if ($company): ?>
                                    <p class="text-info">
                                        <i class="fas fa-building me-2"></i>
                                        <?= htmlspecialchars($company['organization_name']) ?>
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
            </div>
            
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>
                            <?= $company ? 'Update Company Information' : 'Company Registration' ?>
                        </h5>
                        <span class="badge bg-light text-dark">
                            <?= $company ? 'Registered' : 'Not Registered' ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <!-- Profile Photo -->
                            <div class="section-header">
                                <h6 class="text-primary"><i class="fas fa-camera me-2"></i>Profile Photo</h6>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Upload Profile Photo</label>
                                    <input type="file" name="profile_image" class="form-control" accept="image/*">
                                    <small class="text-muted">Max file size: 2MB. Supported formats: JPG, PNG, GIF</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Current Photo</label>
                                    <div>
                                        <?php if ($company && !empty($company['profile_image']) && file_exists($company['profile_image'])): ?>
                                            <img src="<?= htmlspecialchars($company['profile_image']) ?>" 
                                                 alt="Current Profile Photo" 
                                                 class="rounded"
                                                 style="width: 100px; height: 100px; object-fit: cover;">
                                            <br>
                                            <small class="text-muted">
                                                File: <?= basename($company['profile_image']) ?>
                                            </small>
                                            <!-- Add remove photo checkbox -->
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" name="remove_photo" value="1" id="remove_photo">
                                                <label class="form-check-label" for="remove_photo">
                                                    Remove current profile photo
                                                </label>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">No profile photo uploaded</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Company Information -->
                            <div class="section-header">
                                <h6 class="text-primary"><i class="fas fa-building me-2"></i>Company Information</h6>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Organization Name *</label>
                                    <input type="text" name="organization_name" class="form-control" 
                                           value="<?= htmlspecialchars($company['organization_name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Sector *</label>
                                    <select name="sector" class="form-control" required>
                                        <option value="">Select Sector</option>
                                        <option value="IT & ITES" <?= ($company['sector'] ?? '') === 'IT & ITES' ? 'selected' : '' ?>>IT & ITES</option>
                                        <option value="Manufacturing" <?= ($company['sector'] ?? '') === 'Manufacturing' ? 'selected' : '' ?>>Manufacturing</option>
                                        <option value="Healthcare" <?= ($company['sector'] ?? '') === 'Healthcare' ? 'selected' : '' ?>>Healthcare</option>
                                        <option value="Education" <?= ($company['sector'] ?? '') === 'Education' ? 'selected' : '' ?>>Education</option>
                                        <option value="Banking" <?= ($company['sector'] ?? '') === 'Banking' ? 'selected' : '' ?>>Banking</option>
                                        <option value="Retail" <?= ($company['sector'] ?? '') === 'Retail' ? 'selected' : '' ?>>Retail</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Contact Information -->
                            <div class="section-header mt-4">
                                <h6 class="text-primary"><i class="fas fa-user me-2"></i>Contact Person Details</h6>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Contact Person Name *</label>
                                    <input type="text" name="contact_person" class="form-control" 
                                           value="<?= htmlspecialchars($company['contact_person'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Designation *</label>
                                    <input type="text" name="designation" class="form-control" 
                                           value="<?= htmlspecialchars($company['designation'] ?? '') ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Gender *</label>
                                    <select name="gender" class="form-control" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?= ($company['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= ($company['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                        <option value="Other" <?= ($company['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Country Code *</label>
                                    <select name="country_code" class="form-control" required>
                                        <option value="+91" <?= ($company['country_code'] ?? '+91') === '+91' ? 'selected' : '' ?>>+91 (India)</option>
                                        <option value="+1" <?= ($company['country_code'] ?? '') === '+1' ? 'selected' : '' ?>>+1 (USA)</option>
                                        <option value="+44" <?= ($company['country_code'] ?? '') === '+44' ? 'selected' : '' ?>>+44 (UK)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Mobile Number *</label>
                                    <input type="text" name="mobile" class="form-control" 
                                           value="<?= htmlspecialchars($company['mobile'] ?? '') ?>" 
                                           pattern="[0-9]{10}" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Landline Number</label>
                                    <input type="text" name="landline" class="form-control" 
                                           value="<?= htmlspecialchars($company['landline'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" 
                                           value="<?= htmlspecialchars($user['email']) ?>" disabled>
                                    <small class="text-muted">Email cannot be changed</small>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label">Address *</label>
                                    <textarea name="address" class="form-control" rows="3" required><?= htmlspecialchars($company['address'] ?? '') ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Interview Requirements -->
                            <div class="section-header mt-4">
                                <h6 class="text-primary"><i class="fas fa-calendar-alt me-2"></i>Interview Requirements</h6>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Interview Type *</label>
                                    <select name="interview_type" class="form-control" required>
                                        <option value="">Select Type</option>
                                        <option value="Online" <?= ($company['interview_type'] ?? '') === 'Online' ? 'selected' : '' ?>>Online</option>
                                        <option value="Offline" <?= ($company['interview_type'] ?? '') === 'Offline' ? 'selected' : '' ?>>Offline</option>
                                        <option value="Hybrid" <?= ($company['interview_type'] ?? '') === 'Hybrid' ? 'selected' : '' ?>>Hybrid</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Accommodation & Transportation -->
                            <div class="section-header mt-4">
                                <h6 class="text-primary"><i class="fas fa-hotel me-2"></i>Accommodation & Transportation</h6>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="accommodation_required" value="1" 
                                               <?= ($company['accommodation_required'] ?? 0) ? 'checked' : '' ?>>
                                        <label class="form-check-label">Accommodation Required</label>
                                    </div>
                                    <select name="accommodation_type" class="form-control">
                                        <option value="">Select Accommodation Type</option>
                                        <option value="Standard" <?= ($company['accommodation_type'] ?? '') === 'Standard' ? 'selected' : '' ?>>Standard</option>
                                        <option value="Premium" <?= ($company['accommodation_type'] ?? '') === 'Premium' ? 'selected' : '' ?>>Premium</option>
                                        <option value="Deluxe" <?= ($company['accommodation_type'] ?? '') === 'Deluxe' ? 'selected' : '' ?>>Deluxe</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="transportation_required" value="1" 
                                               <?= ($company['transportation_required'] ?? 0) ? 'checked' : '' ?>>
                                        <label class="form-check-label">Transportation Required</label>
                                    </div>
                                    <select name="transportation_type" class="form-control">
                                        <option value="">Select Transportation Type</option>
                                        <option value="Bus" <?= ($company['transportation_type'] ?? '') === 'Bus' ? 'selected' : '' ?>>Bus</option>
                                        <option value="Car" <?= ($company['transportation_type'] ?? '') === 'Car' ? 'selected' : '' ?>>Car</option>
                                        <option value="Flight" <?= ($company['transportation_type'] ?? '') === 'Flight' ? 'selected' : '' ?>>Flight</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Facility Requirements -->
                            <div class="section-header mt-4">
                                <h6 class="text-primary"><i class="fas fa-clipboard-list me-2"></i>Facility Requirements</h6>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Interview Rooms</label>
                                    <input type="number" name="interview_rooms" class="form-control" 
                                           value="<?= htmlspecialchars($company['interview_rooms'] ?? 0) ?>" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Interview Panels</label>
                                    <input type="number" name="interview_panels" class="form-control" 
                                           value="<?= htmlspecialchars($company['interview_panels'] ?? 0) ?>" min="0">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="aptitude_hall" value="1" 
                                               <?= ($company['aptitude_hall'] ?? 0) ? 'checked' : '' ?>>
                                        <label class="form-check-label">Aptitude Hall</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="online_exam" value="1" 
                                               <?= ($company['online_exam'] ?? 0) ? 'checked' : '' ?>>
                                        <label class="form-check-label">Online Exam</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="written_exam" value="1" 
                                               <?= ($company['written_exam'] ?? 0) ? 'checked' : '' ?>>
                                        <label class="form-check-label">Written Exam</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="group_discussion" value="1" 
                                               <?= ($company['group_discussion'] ?? 0) ? 'checked' : '' ?>>
                                        <label class="form-check-label">Group Discussion</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    <?= $company ? 'Update Company Details' : 'Register Company' ?>
                                </button>
                                <a href="employer_dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-format phone numbers
            const phoneInputs = document.querySelectorAll('input[type="text"][pattern="[0-9]{10}"]');
            phoneInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    this.value = this.value.replace(/\D/g, '').slice(0, 10);
                });
            });
            
            // Toggle accommodation and transportation fields
            const accommodationCheckbox = document.querySelector('input[name="accommodation_required"]');
            const accommodationSelect = document.querySelector('select[name="accommodation_type"]');
            const transportationCheckbox = document.querySelector('input[name="transportation_required"]');
            const transportationSelect = document.querySelector('select[name="transportation_type"]');
            
            function toggleField(checkbox, select) {
                if (!checkbox.checked) {
                    select.value = '';
                    select.disabled = true;
                } else {
                    select.disabled = false;
                }
            }
            
            // Initial state
            toggleField(accommodationCheckbox, accommodationSelect);
            toggleField(transportationCheckbox, transportationSelect);
            
            // Add event listeners
            accommodationCheckbox.addEventListener('change', function() {
                toggleField(accommodationCheckbox, accommodationSelect);
            });
            
            transportationCheckbox.addEventListener('change', function() {
                toggleField(transportationCheckbox, transportationSelect);
            });
        });
    </script>
<?php include 'footer.php'; ?>