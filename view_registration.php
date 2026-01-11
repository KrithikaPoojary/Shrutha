<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'employer'){
    header("Location: login.php");
    exit;
}

if(isset($_GET['id'])) {
    $reg_id = $_GET['id'];
    
    $sql = "SELECT * FROM registrations WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $reg_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $candidate = mysqli_fetch_assoc($result);
    
    if($candidate) {
        $page_title = "Candidate Profile - " . $candidate['full_name'];
    } else {
        $page_title = "Candidate Not Found";
    }
} else {
    header("Location: employer_dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <?php if($candidate): ?>
            <div class="card">
                <div class="card-header">
                    <h2><?= htmlspecialchars($candidate['full_name']) ?></h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Contact Information</h5>
                            <p><strong>Email:</strong> <?= htmlspecialchars($candidate['email']) ?></p>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($candidate['mobile']) ?></p>
                            <p><strong>Address:</strong> <?= htmlspecialchars($candidate['permanent_address']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Education</h5>
                            <p><strong>College:</strong> <?= htmlspecialchars($candidate['college_name']) ?></p>
                            <p><strong>Degree:</strong> <?= htmlspecialchars($candidate['degree_course']) ?> in <?= htmlspecialchars($candidate['degree_stream']) ?></p>
                        </div>
                    </div>
                    
                    <?php if($candidate['skills']): ?>
                        <h5>Skills</h5>
                        <p><?= htmlspecialchars($candidate['skills']) ?></p>
                    <?php endif; ?>
                    
                    <?php if($candidate['experience'] === 'Yes'): ?>
                        <div class="alert alert-info">
                            This candidate has professional experience.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="employer_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                Candidate profile not found.
            </div>
            <a href="employer_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        <?php endif; ?>
    </div>
</body>
</html>