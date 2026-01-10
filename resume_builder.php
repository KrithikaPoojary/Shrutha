<?php
require_once 'config.php';
// Start the session only if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Initialize variables
$error = '';
$success = '';
$registrationData = null;
$loggedInUserEmail = null;
$submittedResumeType = '';

// Check if user is logged in (assuming user_id is stored in session)
if (isset($_SESSION['user'])) {
    $userId = $_SESSION['user'];
    
    // Get user's email from the users table
    $stmtUser = $conn->prepare("SELECT email FROM users WHERE id = ?");
    if ($stmtUser) {
        $stmtUser->bind_param("i", $userId);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();
        if ($user = $resultUser->fetch_assoc()) {
            $loggedInUserEmail = $user['email'];
        }
        $stmtUser->close();
    }

    // If email is found, get the latest registration data from the registrations table
    if ($loggedInUserEmail) {
        $stmtReg = $conn->prepare("SELECT * FROM registrations WHERE email = ? ORDER BY id DESC LIMIT 1");
        if ($stmtReg) {
            $stmtReg->bind_param("s", $loggedInUserEmail);
            $stmtReg->execute();
            $resultReg = $stmtReg->get_result();
            if ($data = $resultReg->fetch_assoc()) {
                $registrationData = $data;
            }
            $stmtReg->close();
        }
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
     $submittedResumeType = $_POST['resume_type'] ?? '';
    // Collect form data
    $resumeData = [
        'name' => $_POST['name'] ?? '',
        'title' => $_POST['title'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'address' => $_POST['address'] ?? '',
        'linkedin' => $_POST['linkedin'] ?? '',
        'portfolio' => $_POST['portfolio'] ?? '',
        'summary' => $_POST['summary'] ?? '',
        'technical_skills' => $_POST['technical_skills'] ?? '',
        'soft_skills' => $_POST['soft_skills'] ?? ''
    ];

    // Process arrays from hidden textareas
    $jsonFields = ['education', 'experience', 'projects', 'achievements', 'languages'];
    $jsonData = [];
    foreach ($jsonFields as $field) {
        // Decode the JSON string from the hidden textarea
        $jsonData[$field] = json_decode($_POST[$field] ?? '[]', true);
    }

    // Handle photo upload
    $photoPath = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/resumes/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $filename = uniqid() . '_' . basename($_FILES['photo']['name']);
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
            $photoPath = $targetPath;
        }
    }

    // Prepare JSON data for DB insertion
    $jsonToInsert = [];
    foreach ($jsonFields as $field) {
        $jsonToInsert[$field] = json_encode($jsonData[$field]);
    }

    // Insert into database
    $sql = "INSERT INTO resumes (
        full_name, job_title, email, phone, address, linkedin, portfolio, 
        summary, education, experience, technical_skills, soft_skills, 
        projects, achievements, languages, photo_path
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssssssssss",
        $resumeData['name'],
        $resumeData['title'],
        $resumeData['email'],
        $resumeData['phone'],
        $resumeData['address'],
        $resumeData['linkedin'],
        $resumeData['portfolio'],
        $resumeData['summary'],
        $jsonToInsert['education'],
        $jsonToInsert['experience'],
        $resumeData['technical_skills'],
        $resumeData['soft_skills'],
        $jsonToInsert['projects'],
        $jsonToInsert['achievements'],
        $jsonToInsert['languages'],
        $photoPath
    );

    if ($stmt->execute()) {
        $success = "Resume saved successfully!";
    } else {
        $error = "Error saving resume: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Resume Builder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --primary-light: #eef2ff;
            --secondary: #64748b;
            --light: #f8fafc;
            --success: #10b981;
            --success-light: #d1fae5;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --gray: #e2e8f0;
            --card-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
            --border-radius: 16px;
            --accent: #7209b7;
            --glass-bg: rgba(255, 255, 255, 0.85);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
            padding: 20px;
            color: #1e293b;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="none" stroke="%234361ee" stroke-width="0.5" stroke-opacity="0.1"/></svg>');
        }

        .container {
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px 0;
            position: relative;
            background: var(--glass-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            backdrop-filter: blur(10px);
            padding: 40px 20px;
        }

        .header h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 3.2rem;
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 10px;
            background: linear-gradient(to right, #4361ee, #7209b7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 4px rgba(37, 99, 235, 0.1);
            letter-spacing: -0.5px;
        }

        .header p {
            font-size: 1.2rem;
            color: var(--secondary);
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .header::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            margin: 20px auto;
            border-radius: 2px;
        }

        .card {
            background: var(--glass-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 30px;
            transition: var(--transition);
            border: none;
            backdrop-filter: blur(10px);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            padding: 20px 30px;
            position: relative;
        }

        .card-header h4 {
            font-size: 1.4rem;
            font-weight: 600;
        }

        .card-body {
            padding: 25px;
        }

        .resume-type-container {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            margin: 20px 0;
        }

        .resume-type {
            width: 220px;
            height: 180px;
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: white;
            border: 2px solid #e2e8f0;
            transition: var(--transition);
            cursor: pointer;
            padding: 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .resume-type::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s ease;
        }

        .resume-type:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.15);
        }

        .resume-type:hover::before {
            transform: scaleX(1);
        }

        .resume-type.selected {
            border-color: var(--primary);
            background: #eff6ff;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }

        .resume-type.selected::before {
            transform: scaleX(1);
        }

        .resume-type i {
            font-size: 3.5rem;
            color: var(--primary);
            margin-bottom: 15px;
            transition: var(--transition);
        }

        .resume-type:hover i {
            transform: scale(1.1);
        }

        .resume-type h3 {
            font-size: 1.3rem;
            color: var(--primary-dark);
            font-weight: 600;
            margin-bottom: 5px;
        }

        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .template-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            transition: var(--transition);
            margin-bottom: 25px;
            height: 100%;
            position: relative;
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .template-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .template-preview {
            height: 250px;
            overflow: hidden;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .template-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .template-card:hover .template-preview img {
            transform: scale(1.05);
        }

        .template-info {
            padding: 20px;
            background: white;
            text-align: center;
        }

        .template-info h6 {
            font-size: 1.1rem;
            color: var(--primary-dark);
            margin-bottom: 15px;
            font-weight: 600;
        }

        .template-select-btn {
            width: 100%;
            padding: 12px;
            font-weight: 600;
            transition: var(--transition);
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .template-select-btn:hover {
            background: var(--primary-dark);
        }

        .template-select-btn.selected {
            background: var(--success);
        }

        .section-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .section-link {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            background: #f8fafc;
            border-radius: 10px;
            color: #334155;
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
            position: relative;
            overflow: hidden;
            z-index: 1;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .section-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary);
            transform: scaleY(0);
            transform-origin: top;
            transition: transform 0.3s ease;
            z-index: -1;
        }

        .section-link:hover {
            background: #e0f2fe;
            color: var(--primary-dark);
            transform: translateX(5px);
        }

        .section-link:hover::before {
            transform: scaleY(1);
        }

        .section-link.active {
            background: var(--primary);
            color: white;
        }

        .section-link.active::before {
            transform: scaleY(1);
        }

        .section-link i {
            margin-right: 12px;
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        .resume-section {
            margin-bottom: 30px;
            padding: 30px;
            background: var(--glass-bg);
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--primary);
            backdrop-filter: blur(10px);
        }

        .resume-section h4 {
            font-size: 1.5rem;
            color: var(--primary-dark);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .resume-section h4 i {
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #334155;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: var(--transition);
            background: #f8fafc;
            font-family: 'Poppins', sans-serif;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .education-item, .project-item {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 3px solid var(--primary);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .remove-btn {
            background: #fee2e2;
            color: #ef4444;
            border: none;
            border-radius: 6px;
            padding: 8px 15px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .remove-btn:hover {
            background: #fecaca;
        }

        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 14px 30px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #334155;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
            transform: translateY(-2px);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #e69500;
            transform: translateY(-2px);
        }

        /* FIXED: PDF styling - optimized for single page */
        .preview-container {
            background: white;
            display: none;
            width: 210mm; /* A4 width */
            min-height: 297mm; /* A4 height */
            margin: 0 auto;
            padding: 10mm; /* Reduced padding */
            box-sizing: border-box;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            font-size: 10pt; /* Smaller base font size */
            line-height: 1.3; /* Tighter line spacing */
            position: relative; /* MODIFIED: Added position relative to anchor the controls */
        }

        .preview-header {
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            padding: 15px 20px; /* Reduced padding */
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .preview-header h2 {
            font-size: 18pt; /* Reduced size */
            margin-bottom: 5px; /* Tighter spacing */
            font-weight: 700;
        }

        .preview-header h3 {
            font-size: 13pt; /* Reduced size */
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 8px; /* Tighter spacing */
        }

        .preview-content {
            padding: 15px; /* Reduced padding */
            overflow: visible;
        }

        .preview-section {
            margin-bottom: 12px; /* Reduced spacing */
            page-break-inside: avoid;
        }

        .preview-section h4 {
            font-size: 13pt; /* Reduced size */
            color: var(--primary-dark);
            margin-bottom: 8px; /* Reduced spacing */
            padding-bottom: 5px; /* Reduced spacing */
            border-bottom: 1px solid #e2e8f0; /* Thinner border */
        }

        .preview-item {
            margin-bottom: 8px; /* Reduced spacing */
            page-break-inside: avoid;
        }

        .preview-item h5 {
            font-size: 11pt; /* Reduced size */
            color: #334155;
            margin-bottom: 3px; /* Tighter spacing */
        }

        .preview-item p {
            color: #64748b;
            margin-bottom: 3px; /* Tighter spacing */
            font-size: 9.5pt; /* Smaller text */
        }

        .preview-item ul {
            padding-left: 15px; /* Reduced padding */
            margin-top: 3px; /* Reduced spacing */
            margin-bottom: 5px; /* Reduced spacing */
        }

        .preview-item ul li {
            margin-bottom: 2px; /* Reduced spacing */
            font-size: 9.5pt; /* Smaller text */
        }

        .preview-photo {
            width: 90px; /* Smaller photo */
            height: 90px; /* Smaller photo */
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white; /* Thinner border */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); /* Reduced shadow */
        }

        .contact-info {
            margin-top: 5px; /* Reduced spacing */
            font-size: 9.5pt; /* Smaller text */
            line-height: 1.4;
        }

        .contact-links {
            margin-top: 3px; /* Reduced spacing */
            font-size: 9pt; /* Smaller text */
        }

        .template-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--success);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .resume-builder-container {
            display: none;
        }

        .achievement-item, .language-item {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .achievement-item input, .language-item input {
            flex: 1;
        }

        .language-proficiency {
            width: 150px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.6s ease forwards;
        }

        .placeholder-img {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e0f2fe;
            border-radius: 8px;
            color: var(--primary);
            font-size: 1.2rem;
            font-weight: 500;
        }

        .placeholder-img i {
            font-size: 3rem;
            margin-bottom: 10px;
        }

        /* MODIFIED: Changed positioning for buttons */
        .preview-controls {
            position: absolute;
            top: 15px;
            right: 15px;
            display: flex;
            gap: 10px;
            z-index: 10; /* Ensure buttons are above the header */
        }

        .template-placeholder {
            height: 250px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-radius: 8px;
            color: var(--primary);
            padding: 20px;
            text-align: center;
        }
        
        .template-placeholder i {
            font-size: 3.5rem;
            margin-bottom: 15px;
        }
        
        .progress-container {
            display: flex;
            justify-content: center;
            margin: 30px 0;
        }

        .progress-steps {
            display: flex;
            gap: 30px;
        }

        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

         .progress-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px; /* Stays the same, half of the step number's height */
            left: 50%; /* Start the line from the center of the container */
            width: calc(100% + 30px); /* Dynamically calculate width to span the step and the gap */
            height: 2px;
            background: #cbd5e1;
            z-index: 1; /* Ensure the line is behind the number */
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 10px;
            color: #64748b;
            transition: var(--transition);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative; /* Add position relative */
            z-index: 2; /* Ensure the number is in front of the line */
        }

        .step-text {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 500;
        }

        .progress-step.active .step-number {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
        }

        .progress-step.active .step-text {
            color: var(--primary-dark);
            font-weight: 600;
        }

        .resume-preview {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .photo-upload-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .photo-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #e2e8f0;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .contact-info {
            margin-top: 10px;
            font-size: 0.9rem;
        }
        
        .contact-links {
            margin-top: 5px;
            font-size: 0.85rem;
        }

        @media (max-width: 992px) {
            .resume-builder-container {
                flex-direction: column;
            }
            
            .template-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .resume-type {
                width: 100%;
                max-width: 300px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 15px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }

            .header h1 {
                font-size: 2.5rem;
            }
            
            .preview-header .row {
                flex-direction: column-reverse;
            }
            
            .preview-header .col-3 {
                text-align: center;
                margin-bottom: 20px;
            }
        }

        .floating-preview-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--primary);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            cursor: pointer;
            z-index: 100;
            font-size: 1.5rem;
            transition: var(--transition);
        }

        .floating-preview-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-5px);
        }
        
        .photo-upload-label {
            display: inline-block;
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
        }
        
        .photo-upload-label:hover {
            background: var(--primary-dark);
        }
        
        /* PDF-specific styles */
        .pdf-hide {
            display: none !important;
        }

        /* Page break helpers */
        .page-break {
            page-break-before: always;
        }
        .avoid-break {
            page-break-inside: avoid;
        }
        
        /* Improve print styling */
        @media print {
            /* Hide specific UI elements that should not be printed */
            .header,
            .progress-container,
            .card,
            .resume-builder-container,
            .floating-preview-btn,
            .alert {
                display: none !important;
            }

            /* Make the preview container the ONLY thing visible, and have it fill the page */
            .preview-container {
                display: block !important;
                position: absolute !important;
                top: 0;
                left: 0;
                margin: 0;
                padding: 10mm; /* Restore some padding for aesthetics */
                width: 100%;
                height: auto;
                box-shadow: none !important;
                border: none !important;
            }

            /* Hide the control buttons inside the preview */
            .preview-controls {
                display: none !important;
            }
        }
        
        /* New design enhancements */
        .builder-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }
        
        .preview-column {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 20px;
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        
        .form-column {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 20px;
        }
        
        .preview-mini {
            width: 89%;
            height: 500px; /* Increased height for better view */
            overflow: auto;
            border: 1px solid var(--gray);
            border-radius: 10px;
            background: #eef2ff;
            padding: 0; /* Remove padding to allow the scaled element to align perfectly */
        }

        .scaled-preview-content {
            transform: scale(0.45);
            transform-origin: top left;
            width: 222.22%;
            background: white;
        }
        .progress-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .status-badge {
            background: var(--success-light);
            color: var(--success);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-left: 10px;
        }
        
        /* Fix for PDF overflow */
        .preview-container {
            /* position: static !important; */ /* Removing this line to allow relative positioning for controls */
        }
        
        .preview-header, .preview-content {
            position: relative;
            z-index: 1;
        }
        
        /* Alert styles */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .alert-success {
            background-color: var(--success-light);
            color: var(--success);
            border: 1px solid var(--success);
        }
        
        .alert-danger {
            background-color: #fee2e2;
            color: var(--danger);
            border: 1px solid var(--danger);
        }

        /* Style for the print button in alerts */
        .alert .btn {
            margin-left: 15px;
            vertical-align: middle;
        }
        .form-check {
            margin-top: 10px;
            display: flex;
            align-items: center;
        }

        .form-check-input {
            margin-right: 8px;
        }

        .form-check-label {
            font-size: 0.9rem;
            color: #334155;
            cursor: pointer;
        }
        .preview-header .contact-info,
        .preview-header .contact-links {
            margin-top: 5px;
            font-size: 9.5pt;
            line-height: 1.4;
        }

        .preview-header .contact-links {
            margin-top: 3px;
            font-size: 9pt;
        }

        .preview-header .contact-links a {
            color: white !important;
            text-decoration: underline !important;
        }

        .preview-header .contact-info span,
        .preview-header .contact-links span {
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="header fade-in">
            <a href="employee_dashboard.php" class="btn btn-outline-secondary" style="text-decoration: none; position: absolute; left: 10px; box-shadow: none;">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
            <h1>Professional Resume Builder</h1>
            <p>Create a standout resume in minutes with our easy-to-use builder. Impress employers with a polished CV.</p>
        </div>
        
        <div class="progress-container fade-in">
            <div class="progress-steps">
                <div class="progress-step active" id="step1">
                    <div class="step-number">1</div>
                    <div class="step-text">Experience Level</div>
                </div>
                <div class="progress-step" id="step2">
                    <div class="step-number">2</div>
                    <div class="step-text">Template</div>
                </div>
                <div class="progress-step" id="step3">
                    <div class="step-number">3</div>
                    <div class="step-text">Build Resume</div>
                </div>
                <div class="progress-step" id="step4">
                    <div class="step-number">4</div>
                    <div class="step-text">Download</div>
                </div>
            </div>
        </div>
        
        <div class="card fade-in">
            <div class="card-header" style="text-align: center;">
                <h4>Are you a fresher or experienced?</h4>
            </div>
            <div class="card-body">
                <div class="resume-type-container">
                    <div class="resume-type" data-type="fresher">
                        <i class="fas fa-user-graduate"></i>
                        <h3>Fresher</h3>
                        <p>Recent graduate or entry-level</p>
                    </div>
                    <div class="resume-type" data-type="experienced">
                        <i class="fas fa-briefcase"></i>
                        <h3>Experienced</h3>
                        <p>Professional with work experience</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="resume-builder-container" id="builder-section">
            <form id="resume-form" method="post" enctype="multipart/form-data">
                <div class="builder-container">
                    <div class="preview-column">
                        <h3>Resume Preview <span class="status-badge"><i class="fas fa-check-circle"></i> PDF Ready</span></h3>
                        <div class="preview-mini">
                            <div class="resume-preview-mini" id="mini-preview">
                                </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-header">
                                <h4>Resume Sections</h4>
                            </div>
                            <div class="card-body">
                                <div class="section-list">
                                    <a href="#" class="section-link active" data-section="personal">
                                        <i class="fas fa-user"></i> Personal Information
                                    </a>
                                    <a href="#" class="section-link" data-section="summary">
                                        <i class="fas fa-file-alt"></i> Professional Summary
                                    </a>
                                    <a href="#" class="section-link" data-section="education">
                                        <i class="fas fa-graduation-cap"></i> Education
                                    </a>
                                    <a href="#" class="section-link" data-section="experience">
                                        <i class="fas fa-briefcase"></i> Work Experience
                                    </a>
                                    <a href="#" class="section-link" data-section="skills">
                                        <i class="fas fa-star"></i> Skills
                                    </a>
                                    <a href="#" class="section-link" data-section="projects">
                                        <i class="fas fa-project-diagram"></i> Projects
                                    </a>
                                    <a href="#" class="section-link" data-section="achievements">
                                        <i class="fas fa-trophy"></i> Achievements
                                    </a>
                                    <a href="#" class="section-link" data-section="languages">
                                        <i class="fas fa-language"></i> Languages
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="action-buttons mt-4">
                            <button class="btn btn-success" id="download-pdf-mini" type="button">
                                <i class="fas fa-download me-2"></i>Download PDF
                            </button>
                            <button class="btn btn-primary" id="preview-resume" type="button">
                                <i class="fas fa-eye me-2"></i>Full Preview
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-column">
                        <div class="resume-section" id="personal-section">
                            <h4><i class="fas fa-user"></i> Personal Information</h4>
                            
                            <div class="photo-upload-container mb-4">
                                <div class="photo-preview" id="photo-preview">
                                    <img id="photo-preview-img" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='150' height='150' viewBox='0 0 24 24'%3E%3Cpath fill='%234361ee' d='M12 12q-1.65 0-2.825-1.175T8 8q0-1.65 1.175-2.825T12 4q1.65 0 2.825 1.175T16 8q0 1.65-1.175 2.825T12 12Zm-8 8v-2.8q0-.85.438-1.563T5.6 14.55q1.55-.775 3.15-1.163T12 13q1.65 0 3.25.388t3.15 1.162q.725.375 1.163 1.088T20 17.2V20H4Z'/%3E%3C/svg%3E" alt="Preview">
                                </div>
                                <label for="photo-upload" class="photo-upload-label">
                                    <i class="fas fa-camera me-2"></i>Upload Photo
                                </label>
                                <input type="file" id="photo-upload" name="photo" accept="image/*" style="display: none;">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" class="form-control resume-input" data-field="name" placeholder="John Doe">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Job Title</label>
                                        <input type="text" class="form-control resume-input" data-field="title" placeholder="Software Engineer">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control resume-input" data-field="email" placeholder="john.doe@example.com">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" class="form-control resume-input" data-field="phone" placeholder="(123) 456-7890">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <textarea class="form-control resume-input" rows="2" data-field="address" placeholder="123 Main St, City, Country"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">LinkedIn</label>
                                        <input type="url" class="form-control resume-input" data-field="linkedin" placeholder="linkedin.com/in/johndoe">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Portfolio/GitHub</label>
                                        <input type="url" class="form-control resume-input" data-field="portfolio" placeholder="github.com/johndoe">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="resume-section" id="summary-section" style="display: none;">
                            <h4><i class="fas fa-file-alt"></i> Professional Summary</h4>
                            <div class="form-group">
                                <textarea class="form-control resume-input" rows="5" data-field="summary" placeholder="Passionate software engineer with 5+ years of experience developing scalable web applications..."></textarea>
                                <div class="form-text">Write a brief overview of your professional background and career goals</div>
                            </div>
                        </div>
                        
                        <div class="resume-section" id="education-section" style="display: none;">
                            <h4><i class="fas fa-graduation-cap"></i> Education</h4>
                            <div id="education-container">
                                </div>
                            <button type="button" class="btn btn-outline-primary" id="add-education">
                                <i class="fas fa-plus me-2"></i>Add Education
                            </button>
                        </div>
                        
                        <div class="resume-section" id="experience-section" style="display: none;">
                            <h4><i class="fas fa-briefcase"></i> Work Experience</h4>
                            <div id="experience-container">
                                </div>
                            <button type="button" class="btn btn-outline-primary" id="add-experience">
                                <i class="fas fa-plus me-2"></i>Add Experience
                            </button>
                        </div>
                        
                        <div class="resume-section" id="skills-section" style="display: none;">
                            <h4><i class="fas fa-star"></i> Skills</h4>
                            <div class="form-group">
                                <label class="form-label">Technical Skills</label>
                                <textarea class="form-control resume-input" rows="3" data-field="technical_skills" placeholder="JavaScript, React, Node.js, Python..."></textarea>
                                <div class="form-text">Separate skills with commas</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Soft Skills</label>
                                <textarea class="form-control resume-input" rows="2" data-field="soft_skills" placeholder="Communication, Teamwork, Problem-solving..."></textarea>
                            </div>
                        </div>
                        
                        <div class="resume-section" id="projects-section" style="display: none;">
                            <h4><i class="fas fa-project-diagram"></i> Projects</h4>
                            <div id="projects-container">
                                <div class="project-item">
                                    <div class="form-group">
                                        <label class="form-label">Project Name</label>
                                        <input type="text" class="form-control resume-input" data-field="projects[0].name" placeholder="E-commerce Website">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control resume-input" rows="2" data-field="projects[0].description" placeholder="Developed a responsive e-commerce platform with payment integration..."></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Technologies Used</label>
                                        <input type="text" class="form-control resume-input" data-field="projects[0].technologies" placeholder="React, Node.js, MongoDB">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Project URL</label>
                                        <input type="url" class="form-control resume-input" data-field="projects[0].url" placeholder="https://example.com">
                                    </div>
                                    <button type="button" class="remove-btn remove-project">
                                        <i class="fas fa-trash me-1"></i>Remove
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary" id="add-project">
                                <i class="fas fa-plus me-2"></i>Add Project
                            </button>
                        </div>
                        
                        <div class="resume-section" id="achievements-section" style="display: none;">
                            <h4><i class="fas fa-trophy"></i> Achievements</h4>
                            <div id="achievements-container">
                                <div class="achievement-item">
                                    <input type="text" class="form-control resume-input" data-field="achievements[0]" placeholder="Employee of the Year 2022">
                                    <button type="button" class="remove-btn remove-achievement">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary" id="add-achievement">
                                <i class="fas fa-plus me-2"></i>Add Achievement
                            </button>
                        </div>
                        
                        <div class="resume-section" id="languages-section" style="display: none;">
                            <h4><i class="fas fa-language"></i> Languages</h4>
                            <div id="languages-container">
                                <div class="language-item">
                                    <input type="text" class="form-control resume-input" data-field="languages[0].name" placeholder="English">
                                    <select class="form-control resume-input language-proficiency" data-field="languages[0].proficiency">
                                        <option value="Native">Native</option>
                                        <option value="Fluent">Fluent</option>
                                        <option value="Intermediate">Intermediate</option>
                                        <option value="Basic">Basic</option>
                                    </select>
                                    <button type="button" class="remove-btn remove-language">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary" id="add-language">
                                <i class="fas fa-plus me-2"></i>Add Language
                            </button>
                        </div>
                        
                        <div class="action-buttons">
                            <button class="btn btn-secondary" id="save-draft" type="button">
                                <i class="fas fa-save me-2"></i>Save Draft
                            </button>
                            <button class="btn btn-success" id="generate-resume" type="submit">
                                <i class="fas fa-file-download me-2"></i>Generate & Save
                            </button>
                        </div>
                    </div>
                </div>
                
                <input type="hidden" name="name" id="form-name">
                <input type="hidden" name="title" id="form-title">
                <input type="hidden" name="email" id="form-email">
                <input type="hidden" name="phone" id="form-phone">
                <input type="hidden" name="address" id="form-address">
                <input type="hidden" name="linkedin" id="form-linkedin">
                <input type="hidden" name="portfolio" id="form-portfolio">
                <input type="hidden" name="summary" id="form-summary">
                <input type="hidden" name="technical_skills" id="form-technical_skills">
                <input type="hidden" name="soft_skills" id="form-soft_skills">
                <textarea name="education" id="form-education" style="display: none;"></textarea>
                <textarea name="experience" id="form-experience" style="display: none;"></textarea>
                <textarea name="projects" id="form-projects" style="display: none;"></textarea>
                <textarea name="achievements" id="form-achievements" style="display: none;"></textarea>
                <textarea name="languages" id="form-languages" style="display: none;"></textarea>
                <input type="hidden" name="resume_type" id="form-resume-type">
            </form>
        </div>
        
        <div class="preview-container" id="preview-container">
            <div class="preview-header">
                <div>
                    <h2 id="preview-name"></h2>
                    <h3 id="preview-title"></h3>
                    <div class="contact-info">
                        <!-- Contact info will be inserted here -->
                    </div>
                    <div class="contact-links">
                        <!-- Contact links will be inserted here -->
                    </div>
                </div>
                <div>
                    <img id="preview-photo" class="preview-photo" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 24 24'%3E%3Cpath fill='%23ffffff' d='M12 12q-1.65 0-2.825-1.175T8 8q0-1.65 1.175-2.825T12 4q1.65 0 2.825 1.175T16 8q0 1.65-1.175 2.825T12 12Zm-8 8v-2.8q0-.85.438-1.563T5.6 14.55q1.55-.775 3.15-1.163T12 13q1.65 0 3.25.388t3.15 1.162q.725.375 1.163 1.088T20 17.2V20H4Z'/%3E%3C/svg%3E" alt="Photo">
                </div>
            </div>
            <div class="preview-controls">
                <button class="btn btn-secondary" id="close-preview">
                    <i class="fas fa-times me-2"></i>Close
                </button>
                <button class="btn btn-success" id="download-pdf">
                    <i class="fas fa-download me-2"></i>Download PDF
                </button>
            </div>
            <div class="preview-content">
                <div class="preview-section" id="preview-summary-section">
                    <h4>Professional Summary</h4>
                    <p id="summary-content"></p>
                </div>
                
                <div class="preview-section" id="preview-education-section">
                    <h4>Education</h4>
                    <div id="education-content"></div>
                </div>
                
                <div class="preview-section" id="preview-experience-section">
                    <h4>Work Experience</h4>
                    <div id="experience-content"></div>
                </div>
                
                <div class="preview-section" id="preview-skills-section">
                    <h4>Skills</h4>
                    <div id="skills-content"></div>
                </div>
                
                <div class="preview-section" id="preview-projects-section">
                    <h4>Projects</h4>
                    <div id="projects-content"></div>
                </div>
                
                <div class="preview-section" id="preview-achievements-section">
                    <h4>Achievements</h4>
                    <ul id="achievements-content"></ul>
                </div>
                
                <div class="preview-section" id="preview-languages-section">
                    <h4>Languages</h4>
                    <div id="languages-content"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="floating-preview-btn" id="floating-preview">
        <i class="fas fa-eye"></i>
    </div>

    <script>
        // Pass PHP data to JavaScript
        const registrationData = <?= $registrationData ? json_encode($registrationData) : 'null' ?>;
        
        // Global state
        let resumeType = <?= !empty($submittedResumeType) ? json_encode($submittedResumeType) : "''" ?>; // 'fresher' or 'experienced'
        let educationCount = 0;
        let experienceCount = 0;
        let projectCount = 0;
        let achievementCount = 0;
        let languageCount = 0;

        // Default resume data structure
        let resumeData = {
            name: "John Doe",
            title: "Software Engineer",
            email: "john.doe@example.com",
            phone: "(123) 456-7890",
            address: "123 Main St, City, Country",
            linkedin: "linkedin.com/in/johndoe",
            portfolio: "github.com/johndoe",
            summary: "", // Set to empty to show placeholder
            education: [
                {
                    degree: "Bachelor of Science in Computer Science",
                    institution: "University of Technology",
                    start: "2018-09",
                    end: "2022-06",
                    currently_pursuing: false,
                    description: "Relevant coursework, achievements, etc."
                }
            ],
            experience: [
                {
                    title: "Senior Developer",
                    company: "Tech Solutions Inc.",
                    start: "2020-01",
                    end: "",
                    description: "Describe your responsibilities and achievements..."
                }
            ],
            technical_skills: "JavaScript, React, Node.js, Python",
            soft_skills: "Communication, Teamwork, Problem-solving",
            projects: [
                {
                    name: "", // Set to empty to show placeholder
                    description: "", // Set to empty to show placeholder
                    technologies: "", // Set to empty to show placeholder
                    url: "" // Set to empty to show placeholder
                }
            ],
            achievements: [""], // Set to empty to show placeholder
            languages: [
                {name: "English", proficiency: "Fluent"},
                {name: "Spanish", proficiency: "Intermediate"}
            ]
        };

        /* MODIFIED: Date Formatting to show "MMM YYYY" */
        function formatDate(dateStr) {
            if (!dateStr) return '';
            const parts = dateStr.split('-');
            if (parts.length === 2) {
                const year = parts[0];
                const month = parts[1];
                const date = new Date(year, month - 1);
                return date.toLocaleString('default', { month: 'short' }) + ' ' + year;
            }
            return dateStr;
        }
        
        // Add alert function
        function showAlert(message, type) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;
            
            document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.header'));
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
        
        function populateWithRegistrationData() {
            if (!registrationData) return;

            resumeData.name = registrationData.full_name || '';
            resumeData.email = registrationData.email || '';
            resumeData.phone = registrationData.mobile || '';
            resumeData.address = [registrationData.permanent_address, registrationData.hometown, registrationData.district, registrationData.state, registrationData.pincode].filter(Boolean).join(', ');
            resumeData.summary = ''; // Summary is not in registration
            resumeData.linkedin = '';
            resumeData.portfolio = '';

            // Populate Education
            const education = [];
            if (registrationData.pg_year > 0) {
                education.push({
                    degree: registrationData.pg_course,
                    institution: registrationData.college_name,
                    start: '', // Not available in registration data
                    end: registrationData.pg_year,
                    description: `Stream: ${registrationData.pg_stream}, Specialization: ${registrationData.pg_specialization}`
                });
            }
            if (registrationData.degree_year > 0) {
                education.push({
                    degree: registrationData.degree_course,
                    institution: registrationData.college_name,
                    start: '',
                    end: registrationData.degree_year,
                    description: `Stream: ${registrationData.degree_stream}, Specialization: ${registrationData.degree_specialization}`
                });
            }
            if (registrationData.puc_year > 0) {
                education.push({
                    degree: registrationData.puc_course,
                    institution: '', // Not available
                    start: '',
                    end: registrationData.puc_year,
                    description: `Stream: ${registrationData.puc_stream}`
                });
            }
            resumeData.education = education.length > 0 ? education : [];

            resumeData.experience = []; // No experience data in registration

            // Populate skills & languages
            resumeData.technical_skills = registrationData.skills || '';
            resumeData.soft_skills = '';
            const languages = (registrationData.languages || '').split(',').filter(lang => lang.trim() !== '');
            resumeData.languages = languages.map(lang => ({ name: lang.trim(), proficiency: 'Fluent' }));
        }

        function setInitialFormValues() {
            document.querySelector('[data-field="name"]').value = resumeData.name;
            document.querySelector('[data-field="title"]').value = resumeData.title;
            document.querySelector('[data-field="email"]').value = resumeData.email;
            document.querySelector('[data-field="phone"]').value = resumeData.phone;
            document.querySelector('[data-field="address"]').value = resumeData.address;
            document.querySelector('[data-field="linkedin"]').value = resumeData.linkedin;
            document.querySelector('[data-field="portfolio"]').value = resumeData.portfolio;
            document.querySelector('[data-field="summary"]').value = resumeData.summary;
            document.querySelector('[data-field="technical_skills"]').value = resumeData.technical_skills;
            document.querySelector('[data-field="soft_skills"]').value = resumeData.soft_skills;
            
            // Clear and populate dynamic sections
            document.getElementById('education-container').innerHTML = '';
            resumeData.education.forEach(edu => addEducation(edu));
            
            document.getElementById('experience-container').innerHTML = '';
            resumeData.experience.forEach(exp => addExperience(exp));

            document.getElementById('projects-container').innerHTML = '';
            resumeData.projects.forEach(proj => addProject(proj));

            document.getElementById('achievements-container').innerHTML = '';
            resumeData.achievements.forEach(ach => addAchievement(ach));
            
            document.getElementById('languages-container').innerHTML = '';
            resumeData.languages.forEach(lang => addLanguage(lang));
        }

        function getDraftKey() {
            const userId = <?= isset($_SESSION['user']) ? $_SESSION['user'] : '0' ?>;
            return `resumeDraft_${userId}`;
        }
        // Add this function to load draft data
        function loadDraft() {
            const draft = localStorage.getItem(getDraftKey());
            if (draft) {
                const draftData = JSON.parse(draft);
                
                // Populate form fields from draft
                document.querySelector('[data-field="name"]').value = draftData.name || '';
                document.querySelector('[data-field="title"]').value = draftData.title || '';
                document.querySelector('[data-field="email"]').value = draftData.email || '';
                document.querySelector('[data-field="phone"]').value = draftData.phone || '';
                document.querySelector('[data-field="address"]').value = draftData.address || '';
                document.querySelector('[data-field="linkedin"]').value = draftData.linkedin || '';
                document.querySelector('[data-field="portfolio"]').value = draftData.portfolio || '';
                document.querySelector('[data-field="summary"]').value = draftData.summary || '';
                document.querySelector('[data-field="technical_skills"]').value = draftData.technical_skills || '';
                document.querySelector('[data-field="soft_skills"]').value = draftData.soft_skills || '';
                
                // Load photo if exists
                if (draftData.photoData) {
                    document.getElementById('photo-preview-img').src = draftData.photoData;
                    window.resumePhoto = draftData.photoData;
                }
                
                // Repopulate dynamic sections from draft
                document.getElementById('education-container').innerHTML = '';
                (JSON.parse(draftData.education || '[]')).forEach(item => addEducation(item));
                
                document.getElementById('experience-container').innerHTML = '';
                (JSON.parse(draftData.experience || '[]')).forEach(item => addExperience(item));

                document.getElementById('projects-container').innerHTML = '';
                (JSON.parse(draftData.projects || '[]')).forEach(item => addProject(item));
                
                document.getElementById('achievements-container').innerHTML = '';
                (JSON.parse(draftData.achievements || '[]')).forEach(item => addAchievement(item));

                document.getElementById('languages-container').innerHTML = '';
                (JSON.parse(draftData.languages || '[]')).forEach(item => addLanguage(item));
                
                // Update previews
                updatePreview();
                updateMiniPreview();
                
                showAlert('Draft loaded successfully!', 'success');
            }
        }


        // Photo upload handling
        document.getElementById('photo-upload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const base64 = event.target.result;
                    document.getElementById('photo-preview-img').src = base64;
                    window.resumePhoto = base64;
                    updatePreview();
                    updateMiniPreview();
                };
                reader.readAsDataURL(file);
            }
        });

        // Resume type selection
        document.querySelectorAll('.resume-type').forEach(button => {
            button.addEventListener('click', function() {
                resumeType = this.dataset.type;
                document.getElementById('form-resume-type').value = resumeType;
                document.querySelectorAll('.resume-type').forEach(btn => btn.classList.remove('selected'));
                this.classList.add('selected');
                
                document.getElementById('builder-section').style.display = 'block';
                document.getElementById('step2').classList.add('active');
                
                const expSection = document.getElementById('experience-section');
                const expNav = document.querySelector('.section-link[data-section="experience"]');
                const expHeader = expSection.querySelector('h4');
                const addExpBtn = document.getElementById('add-experience');

                if (resumeType === 'fresher') {
                    expHeader.innerHTML = '<i class="fas fa-id-badge"></i> Internships';
                    expNav.innerHTML = '<i class="fas fa-id-badge"></i> Internships';
                    addExpBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Add Internship';
                } else {
                    expHeader.innerHTML = '<i class="fas fa-briefcase"></i> Work Experience';
                    expNav.innerHTML = '<i class="fas fa-briefcase"></i> Work Experience';
                    addExpBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Add Experience';
                }
                
                document.getElementById('builder-section').scrollIntoView({ behavior: 'smooth' });
                updatePreview();
                updateMiniPreview();
            });
        });
        
        // Section navigation
        document.querySelectorAll('.section-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const sectionId = this.dataset.section;
                document.querySelectorAll('.section-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                document.querySelectorAll('.resume-section').forEach(sec => sec.style.display = 'none');
                document.getElementById(`${sectionId}-section`).style.display = 'block';
            });
        });
        
        // Generic input change listener
        document.getElementById('resume-form').addEventListener('input', () => {
             updatePreview();
             updateMiniPreview();
        });
        
        // Add functions for dynamic sections
        function addEducation(data = {}) {
            const index = educationCount++;
            const newEducation = document.createElement('div');
            newEducation.className = 'education-item';
            newEducation.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Degree/Certificate</label>
                            <input type="text" class="form-control resume-input" data-field="education[${index}].degree" placeholder="Bachelor of Science" value="${data.degree || ''}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Institution</label>
                            <input type="text" class="form-control resume-input" data-field="education[${index}].institution" placeholder="University Name" value="${data.institution || ''}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Start Date</label>
                            <input type="month" class="form-control resume-input" data-field="education[${index}].start" value="${data.start || ''}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">End Date</label>
                            <input type="month" class="form-control resume-input education-end-date" data-field="education[${index}].end" value="${data.end || ''}" ${data.currently_pursuing ? 'disabled' : ''}>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input currently-pursuing-checkbox" data-index="${index}" ${data.currently_pursuing ? 'checked' : ''}>
                            <label class="form-check-label">Currently Pursuing</label>
                        </div>
                        <br>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control resume-input" rows="2" data-field="education[${index}].description" placeholder="Relevant coursework, achievements, etc.">${data.description || ''}</textarea>
                </div>
                <button type="button" class="remove-btn"><i class="fas fa-trash me-1"></i>Remove</button>`;
            
            document.getElementById('education-container').appendChild(newEducation);
            
            // Add event listener for the "Currently Pursuing" checkbox
            const currentlyPursuingCheckbox = newEducation.querySelector('.currently-pursuing-checkbox');
            const endDateInput = newEducation.querySelector('.education-end-date');
            
            currentlyPursuingCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                endDateInput.disabled = isChecked;
                if (isChecked) {
                    endDateInput.value = '';
                }
                updatePreview();
                updateMiniPreview();
            });
            
            newEducation.querySelector('.remove-btn').addEventListener('click', () => { 
                newEducation.remove(); 
                updatePreview(); 
                updateMiniPreview(); 
            });
        }
        
        function addExperience(data = {}) {
            const index = experienceCount++;
            const newExperience = document.createElement('div');
            newExperience.className = 'experience-item';
            const titleLabel = resumeType === 'fresher' ? 'Internship Role' : 'Job Title';
            const companyLabel = resumeType === 'fresher' ? 'Organization' : 'Company';
            newExperience.innerHTML = `
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label class="form-label">${titleLabel}</label><input type="text" class="form-control resume-input" data-field="experience[${index}].title" placeholder="${titleLabel}" value="${data.title || ''}"></div></div>
                    <div class="col-md-6"><div class="form-group"><label class="form-label">${companyLabel}</label><input type="text" class="form-control resume-input" data-field="experience[${index}].company" placeholder="${companyLabel}" value="${data.company || ''}"></div></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label class="form-label">Start Date</label><input type="month" class="form-control resume-input" data-field="experience[${index}].start" value="${data.start || ''}"></div></div>
                    <div class="col-md-6"><div class="form-group"><label class="form-label">End Date</label><input type="month" class="form-control resume-input" data-field="experience[${index}].end" value="${data.end || ''}"></div></div>
                </div>
                <div class="form-group"><label class="form-label">Responsibilities & Achievements</label><textarea class="form-control resume-input" rows="3" data-field="experience[${index}].description" placeholder="Describe your responsibilities...">${data.description || ''}</textarea></div>
                <button type="button" class="remove-btn"><i class="fas fa-trash me-1"></i>Remove</button>`;
            document.getElementById('experience-container').appendChild(newExperience);
            newExperience.querySelector('.remove-btn').addEventListener('click', () => { newExperience.remove(); updatePreview(); updateMiniPreview(); });
        }
        
        function addProject(data = {}) {
            // Re-initialize the first project item if container is empty
            if (projectCount === 0 && document.querySelectorAll('#projects-container .project-item').length > 0 && !data.name) {
                 projectCount = 1;
                 return;
            }
            const index = projectCount++;
            const newProject = document.createElement('div');
            newProject.className = 'project-item';
            newProject.innerHTML = `<div class="form-group"><label class="form-label">Project Name</label><input type="text" class="form-control resume-input" data-field="projects[${index}].name" placeholder="E-commerce Website" value="${data.name || ''}"></div><div class="form-group"><label class="form-label">Description</label><textarea class="form-control resume-input" rows="2" data-field="projects[${index}].description" placeholder="Project description...">${data.description || ''}</textarea></div><div class="form-group"><label class="form-label">Technologies Used</label><input type="text" class="form-control resume-input" data-field="projects[${index}].technologies" placeholder="React, Node.js" value="${data.technologies || ''}"></div><div class="form-group"><label class="form-label">Project URL</label><input type="url" class="form-control resume-input" data-field="projects[${index}].url" placeholder="https://example.com" value="${data.url || ''}"></div><button type="button" class="remove-btn"><i class="fas fa-trash me-1"></i>Remove</button>`;
            document.getElementById('projects-container').appendChild(newProject);
            newProject.querySelector('.remove-btn').addEventListener('click', () => { newProject.remove(); updatePreview(); updateMiniPreview(); });
        }
        
        function addAchievement(data = '') {
            if (achievementCount === 0 && document.querySelectorAll('#achievements-container .achievement-item').length > 0 && !data) {
                achievementCount = 1;
                return;
            }
            const index = achievementCount++;
            const newAchievement = document.createElement('div');
            newAchievement.className = 'achievement-item';
            newAchievement.innerHTML = `<input type="text" class="form-control resume-input" data-field="achievements[${index}]" placeholder="Achievement" value="${data || ''}"><button type="button" class="remove-btn remove-achievement"><i class="fas fa-trash"></i></button>`;
            document.getElementById('achievements-container').appendChild(newAchievement);
            newAchievement.querySelector('.remove-btn').addEventListener('click', () => { newAchievement.remove(); updatePreview(); updateMiniPreview(); });
        }
        
        function addLanguage(data = {}) {
            if (languageCount === 0 && document.querySelectorAll('#languages-container .language-item').length > 0 && !data.name) {
                 languageCount = 1;
                 return;
            }
            const index = languageCount++;
            const newLang = document.createElement('div');
            newLang.className = 'language-item';
            const proficiencies = ['Native', 'Fluent', 'Intermediate', 'Basic'];
            const options = proficiencies.map(p => `<option value="${p}" ${data.proficiency === p ? 'selected' : ''}>${p}</option>`).join('');
            newLang.innerHTML = `<input type="text" class="form-control resume-input" data-field="languages[${index}].name" placeholder="Language" value="${data.name || ''}"><select class="form-control resume-input language-proficiency" data-field="languages[${index}].proficiency">${options}</select><button type="button" class="remove-btn"><i class="fas fa-trash"></i></button>`;
            document.getElementById('languages-container').appendChild(newLang);
            newLang.querySelector('.remove-btn').addEventListener('click', () => { newLang.remove(); updatePreview(); updateMiniPreview(); });
        }

        // Add event listeners for "Add" buttons
        document.getElementById('add-education').addEventListener('click', () => addEducation());
        document.getElementById('add-experience').addEventListener('click', () => addExperience());
        document.getElementById('add-project').addEventListener('click', () => addProject());
        document.getElementById('add-achievement').addEventListener('click', () => addAchievement());
        document.getElementById('add-language').addEventListener('click', () => addLanguage());

        // Initial remove buttons
        document.querySelectorAll('.remove-project, .remove-achievement, .remove-language').forEach(btn => {
            btn.addEventListener('click', (e) => { e.target.closest('div').remove(); updatePreview(); updateMiniPreview(); });
        });
        
        // Preview handling
        document.getElementById('preview-resume').addEventListener('click', () => {
            updatePreview();
            document.getElementById('preview-container').style.display = 'block';
            document.getElementById('step4').classList.add('active');
            document.getElementById('preview-container').scrollIntoView({ behavior: 'smooth' });
        });
        document.getElementById('floating-preview').addEventListener('click', () => document.getElementById('preview-resume').click());
        document.getElementById('close-preview').addEventListener('click', () => document.getElementById('preview-container').style.display = 'none');
        
        // Download PDF
        function downloadPDF() {
            updatePreview();
            const previewContainer = document.getElementById('preview-container');
            previewContainer.style.display = 'block';
            const pdfControls = document.querySelector('.preview-controls');
            pdfControls.classList.add('pdf-hide');
            
            const downloadBtn = document.getElementById('download-pdf');
            const originalText = downloadBtn.innerHTML;
            downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';
            downloadBtn.disabled = true;
            
            setTimeout(() => {
                const element = previewContainer;
                const opt = {
                    margin: [5, 5, 5, 5], filename: 'professional_resume.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2, useCORS: true, logging: false, backgroundColor: '#FFFFFF', scrollX: 0, scrollY: 0, windowHeight: element.scrollHeight + 100 },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true },
                    pagebreak: { mode: ['avoid-all', 'css'] }
                };
                html2pdf().set(opt).from(element).save().then(() => {
                    downloadBtn.innerHTML = originalText;
                    downloadBtn.disabled = false;
                    pdfControls.classList.remove('pdf-hide');
                    setTimeout(() => { previewContainer.style.display = 'none'; }, 500);
                });
            }, 500);
        }
        document.getElementById('download-pdf').addEventListener('click', downloadPDF);
        document.getElementById('download-pdf-mini').addEventListener('click', downloadPDF);
        
        // Form submission handling
        function prepareFormData(isDraft = false) {
            const getValues = (selector, isObject) => {
                const data = [];
                document.querySelectorAll(selector).forEach(item => {
                    if (isObject) {
                        const obj = {};
                        item.querySelectorAll('.resume-input').forEach(input => {
                            const field = input.dataset.field.match(/\[\d+\]\.(\w+)/);
                            if (field) obj[field[1]] = input.value;
                        });
                        
                        // Handle currently pursuing checkbox
                        const checkbox = item.querySelector('.currently-pursuing-checkbox');
                        if (checkbox) {
                            obj['currently_pursuing'] = checkbox.checked;
                        }
                        
                        if (Object.keys(obj).length > 0 && Object.values(obj).some(v => v !== '')) data.push(obj);
                    } else {
                        const input = item.querySelector('input');
                        if (input && input.value) data.push(input.value);
                    }
                });
                return data;
            };

            // Update hidden form fields
            document.getElementById('form-name').value = document.querySelector('[data-field="name"]').value;
            document.getElementById('form-title').value = document.querySelector('[data-field="title"]').value;
            document.getElementById('form-email').value = document.querySelector('[data-field="email"]').value;
            document.getElementById('form-phone').value = document.querySelector('[data-field="phone"]').value;
            document.getElementById('form-address').value = document.querySelector('[data-field="address"]').value;
            document.getElementById('form-linkedin').value = document.querySelector('[data-field="linkedin"]').value;
            document.getElementById('form-portfolio').value = document.querySelector('[data-field="portfolio"]').value;
            document.getElementById('form-summary').value = document.querySelector('[data-field="summary"]').value;
            document.getElementById('form-technical_skills').value = document.querySelector('[data-field="technical_skills"]').value;
            document.getElementById('form-soft_skills').value = document.querySelector('[data-field="soft_skills"]').value;
            
            document.getElementById('form-education').value = JSON.stringify(getValues('#education-container .education-item', true));
            document.getElementById('form-experience').value = JSON.stringify(getValues('#experience-container .experience-item', true));
            document.getElementById('form-projects').value = JSON.stringify(getValues('#projects-container .project-item', true));
            document.getElementById('form-achievements').value = JSON.stringify(getValues('#achievements-container .achievement-item', false));
            document.getElementById('form-languages').value = JSON.stringify(getValues('#languages-container .language-item', true));

            return true;
        }

        
        // Add this function to handle draft saving
        function saveDraft() {
            if (prepareFormData(true)) {
                // Save to localStorage as draft with user-specific key
                const formData = new FormData(document.getElementById('resume-form'));
                const draftData = {};
                
                formData.forEach((value, key) => {
                    draftData[key] = value;
                });
                
                // Save current photo
                if (window.resumePhoto) {
                    draftData.photoData = window.resumePhoto;
                }
                
                // Save with user-specific key
                localStorage.setItem(getDraftKey(), JSON.stringify(draftData));
                
                // Show success message
                showAlert('Draft saved successfully!', 'success');
            }
        }
        
        // Update Preview Functions
        function updatePreview() {
            document.getElementById('preview-name').textContent = document.querySelector('[data-field="name"]').value;
            document.getElementById('preview-title').textContent = document.querySelector('[data-field="title"]').value;
            const email = document.querySelector('[data-field="email"]').value;
            const phone = document.querySelector('[data-field="phone"]').value;
            const address = document.querySelector('[data-field="address"]').value;
            const linkedin = document.querySelector('[data-field="linkedin"]').value;
            const portfolio = document.querySelector('[data-field="portfolio"]').value;

            // Build contact info with conditional separators
            const contactInfo = [];
            if (email) contactInfo.push(`<span id="preview-email">${email}</span>`);
            if (phone) contactInfo.push(`<span id="preview-phone">${phone}</span>`);
            if (address) contactInfo.push(`<span id="preview-address">${address}</span>`);

            // Build contact links with conditional separators
            const contactLinks = [];
            if (linkedin) {
                // Format LinkedIn URL properly
                let linkedinUrl = linkedin;
                if (!linkedin.startsWith('http')) {
                    linkedinUrl = 'https://' + linkedin;
                }
                contactLinks.push(`<span id="preview-linkedin"><a href="${linkedinUrl}" target="_blank" style="color: white; text-decoration: underline;">${linkedin}</a></span>`);
            }
            if (portfolio) {
                // Format Portfolio URL properly
                let portfolioUrl = portfolio;
                if (!portfolio.startsWith('http')) {
                    portfolioUrl = 'https://' + portfolio;
                }
                contactLinks.push(`<span id="preview-portfolio"><a href="${portfolioUrl}" target="_blank" style="color: white; text-decoration: underline;">${portfolio}</a></span>`);
            }

            // Update the HTML - FIXED: Ensure both contact info and links are properly updated
            const contactInfoElement = document.querySelector('#preview-container .contact-info');
            const contactLinksElement = document.querySelector('#preview-container .contact-links');

            if (contactInfoElement) {
                contactInfoElement.innerHTML = contactInfo.join(' | ');
            } else {
                // Create contact-info element if it doesn't exist
                const headerTextContainer = document.querySelector('#preview-container .preview-header > div:first-child');
                if (headerTextContainer) {
                    const newContactInfo = document.createElement('div');
                    newContactInfo.className = 'contact-info';
                    newContactInfo.innerHTML = contactInfo.join(' | ');
                    // Insert after the h3 element
                    const titleElement = headerTextContainer.querySelector('h3');
                    if (titleElement) {
                        titleElement.parentNode.insertBefore(newContactInfo, titleElement.nextSibling);
                    } else {
                        headerTextContainer.appendChild(newContactInfo);
                    }
                }
            }

            if (contactLinksElement) {
                contactLinksElement.innerHTML = contactLinks.join(' | ');
            } else {
                // Create contact-links element if it doesn't exist
                const headerTextContainer = document.querySelector('#preview-container .preview-header > div:first-child');
                if (headerTextContainer) {
                    const newContactLinks = document.createElement('div');
                    newContactLinks.className = 'contact-links';
                    newContactLinks.innerHTML = contactLinks.join(' | ');
                    // Insert after the contact-info element
                    const contactInfoElement = headerTextContainer.querySelector('.contact-info');
                    if (contactInfoElement) {
                        contactInfoElement.parentNode.insertBefore(newContactLinks, contactInfoElement.nextSibling);
                    } else {
                        headerTextContainer.appendChild(newContactLinks);
                    }
                }
            }

            if (window.resumePhoto) document.getElementById('preview-photo').src = window.resumePhoto;
            
            document.getElementById('summary-content').textContent = document.querySelector('[data-field="summary"]').value;
            
            const renderList = (containerId, selector, template) => {
                const container = document.getElementById(containerId);
                container.innerHTML = '';
                document.querySelectorAll(selector).forEach(item => {
                    const data = {};
                    item.querySelectorAll('.resume-input').forEach(input => {
                        const match = input.dataset.field.match(/\.(\w+)$/);
                        if (match) data[match[1]] = input.value;
                    });
                    if(Object.values(data).some(val => val)) container.innerHTML += template(data);
                });
            };
            
            renderList('education-content', '#education-container .education-item', d => {
                const start = formatDate(d.start);
                let end = formatDate(d.end);
                
                // Handle currently pursuing
                if (d.currently_pursuing === 'true' || d.currently_pursuing === true) {
                    end = ' Present';
                } else if (!end) {
                    end = ' Present'; // Fallback if no end date but currently pursuing is true
                }
                
                return `<div class="preview-item"><h5>${d.degree || ''}</h5><p>${d.institution || ''} | ${start} ${start && end !== 'Present' ? 'to' : ''} ${end}</p><p>${d.description || ''}</p></div>`;
            });

            const expHeader = document.querySelector('#preview-experience-section h4');
            expHeader.textContent = resumeType === 'fresher' ? 'Internships' : 'Work Experience';
            
            renderList('experience-content', '#experience-container .experience-item', d => {
                const start = formatDate(d.start);
                const end = formatDate(d.end) || 'Present';
                return `<div class="preview-item"><h5>${d.title || ''}</h5><p>${d.company || ''} | ${start} ${start && end !== 'Present' ? 'to' : ''} ${end}</p><p>${d.description || ''}</p></div>`;
            });

            document.getElementById('skills-content').innerHTML = `<p><strong>Technical:</strong> ${document.querySelector('[data-field="technical_skills"]').value}</p><p><strong>Soft Skills:</strong> ${document.querySelector('[data-field="soft_skills"]').value}</p>`;

            renderList('projects-content', '#projects-container .project-item', d => `<div class="preview-item"><h5>${d.name || ''}</h5><p>${d.description || ''}</p><p><strong>Technologies:</strong> ${d.technologies || ''}</p><p><a href="${d.url || '#'}" target="_blank">${d.url || ''}</a></p></div>`);
            
            document.getElementById('achievements-content').innerHTML = Array.from(document.querySelectorAll('#achievements-container .achievement-item input')).map(i => i.value ? `<li>${i.value}</li>` : '').join('');

            renderList('languages-content', '#languages-container .language-item', d => `<p><strong>${d.name || ''}:</strong> ${d.proficiency || ''}</p>`);

            // Hide empty sections in preview
            document.querySelectorAll('.preview-section').forEach(section => {
                const content = section.querySelector('[id$="-content"]');
                section.style.display = content && content.innerHTML.trim() === '' ? 'none' : 'block';
            });
        }
        
         function updateMiniPreview() {
            // Get the source of the full preview
            const fullPreviewNode = document.getElementById('preview-container');
            
            // Get the destination container for the mini preview
            const miniPreviewContainer = document.getElementById('mini-preview');
            
            // Clone the entire full preview node
            const previewClone = fullPreviewNode.cloneNode(true);
            
            // Remove the control buttons from the cloned version
            const controls = previewClone.querySelector('.preview-controls');
            if (controls) {
                controls.remove();
            }
            
            // --- FIXED SECTION ---
            // 1. Remove the ID from the top-level container of the clone
            previewClone.removeAttribute('id');
            
            // 2. Find ALL elements with an ID inside the clone and remove it
            previewClone.querySelectorAll('[id]').forEach(el => el.removeAttribute('id'));
            
            // Add a class for styling, which is safer than using an ID
            previewClone.classList.add('scaled-preview-content');
            
            // Make sure the cloned preview is visible
            previewClone.style.display = 'block';

            // Clear the mini preview container and append the new, sanitized clone
            miniPreviewContainer.innerHTML = '';
            miniPreviewContainer.appendChild(previewClone);
        }

        // Add print resume function
        function printResume() {
            updatePreview();
            const previewContainer = document.getElementById('preview-container');
            const wasHidden = previewContainer.style.display === 'none';
            previewContainer.style.display = 'block';
            
            window.print();
            
            if (wasHidden) {
                 previewContainer.style.display = 'none';
            }
        }

        // Add print functionality after successful save
        function enablePrintAfterSave() {
            const successAlert = document.querySelector('.alert-success');
            if (successAlert && !successAlert.querySelector('.btn-primary')) {
                const printBtn = document.createElement('button');
                printBtn.className = 'btn btn-primary btn-sm';
                printBtn.innerHTML = '<i class="fas fa-print me-1"></i>Print Resume';
                printBtn.onclick = function() {
                    printResume();
                };
                
                successAlert.appendChild(printBtn);
            }
        }
        
        // --- EVENT LISTENERS ---
        
        // Update the form submission handler
        document.getElementById('resume-form').addEventListener('submit', function(e) {
            if (!prepareFormData()) {
                e.preventDefault(); // Prevent submission if data prep fails (optional)
            }
            // Let the form submit to the server
        });
        
        // Update save-draft event listener
        document.getElementById('save-draft').addEventListener('click', function(e) {
            e.preventDefault();
            saveDraft();
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Check if draft exists and offer to load it
            if (localStorage.getItem(getDraftKey())) {
                if (confirm('A saved draft was found. Would you like to load it?')) {
                    loadDraft();
                }
            }
            
            // Add print button to preview controls
            const printBtn = document.createElement('button');
            printBtn.className = 'btn btn-warning';
            printBtn.innerHTML = '<i class="fas fa-print me-2"></i>Print';
            printBtn.onclick = printResume;
            
            document.querySelector('.preview-controls').appendChild(printBtn);
            
            // Enable print after save when page loads
            enablePrintAfterSave();
        });
        function adjustUIForResumeType() {
            if (!resumeType) return; // Do nothing if it's not set

            const expSection = document.getElementById('experience-section');
            const expNav = document.querySelector('.section-link[data-section="experience"]');
            const expHeader = expSection.querySelector('h4');
            const addExpBtn = document.getElementById('add-experience');

            // This makes sure the correct button is visually selected after reload
            const typeButton = document.querySelector(`.resume-type[data-type="${resumeType}"]`);
            if (typeButton) {
                typeButton.classList.add('selected');
            }

            if (resumeType === 'fresher') {
                expHeader.innerHTML = '<i class="fas fa-id-badge"></i> Internships';
                expNav.innerHTML = '<i class="fas fa-id-badge"></i> Internships';
                addExpBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Add Internship';
            } else {
                expHeader.innerHTML = '<i class="fas fa-briefcase"></i> Work Experience';
                expNav.innerHTML = '<i class="fas fa-briefcase"></i> Work Experience';
                addExpBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Add Experience';
            }
        }
        
        // Initialize the app
        populateWithRegistrationData();
        setInitialFormValues();
        adjustUIForResumeType();
        updatePreview();
        updateMiniPreview();
    </script>
</body>
</html>