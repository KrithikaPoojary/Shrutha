<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'employer'){
    header("Location: login.php");
    exit;
}

if(isset($_GET['id'])) {
    $resume_id = $_GET['id'];
    
    $sql = "SELECT * FROM resumes WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $resume_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $resume = mysqli_fetch_assoc($result);
    
    if($resume) {
        $page_title = "Candidate Profile - " . $resume['full_name'];
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
        <?php if($resume): ?>
            <div class="card">
                <div class="card-header">
                    <h2><?= htmlspecialchars($resume['full_name']) ?></h2>
                    <p class="mb-0"><?= htmlspecialchars($resume['job_title']) ?></p>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Contact Information</h5>
                            <p><strong>Email:</strong> <?= htmlspecialchars($resume['email']) ?></p>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($resume['phone']) ?></p>
                            <p><strong>Address:</strong> <?= htmlspecialchars($resume['address']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Professional Links</h5>
                            <?php if($resume['linkedin']): ?>
                                <p><strong>LinkedIn:</strong> <a href="<?= htmlspecialchars($resume['linkedin']) ?>" target="_blank">View Profile</a></p>
                            <?php endif; ?>
                            <?php if($resume['portfolio']): ?>
                                <p><strong>Portfolio:</strong> <a href="<?= htmlspecialchars($resume['portfolio']) ?>" target="_blank">View Portfolio</a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if($resume['summary']): ?>
                        <h5>Professional Summary</h5>
                        <p><?= nl2br(htmlspecialchars($resume['summary'])) ?></p>
                    <?php endif; ?>
                    
                    <h5>Skills</h5>
                    <div class="mb-3">
                        <?php if($resume['technical_skills']): ?>
                            <p><strong>Technical:</strong> <?= htmlspecialchars($resume['technical_skills']) ?></p>
                        <?php endif; ?>
                        <?php if($resume['soft_skills']): ?>
                            <p><strong>Soft Skills:</strong> <?= htmlspecialchars($resume['soft_skills']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if($resume['photo_path']): ?>
                        <div class="text-center mb-3">
                            <img src="<?= htmlspecialchars($resume['photo_path']) ?>" alt="Profile Photo" class="img-fluid rounded" style="max-height: 200px;">
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