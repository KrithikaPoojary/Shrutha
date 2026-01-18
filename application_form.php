<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_application'])) {
    // ... (same form handling as before, but save job_id if available)
    
    // After successful submission:
    $success = "Job application submitted successfully!";
    header("Refresh: 2; url=user_dashboard.php");
}

ob_start();
?>
<?php include 'includes/header.php'; ?>

<div class="form-container">
    <h2 class="section-title">Job Application Form</h2>
    <?php if ($job_id): ?>
        <div class="job-info">
            <h3>Applying for: <?php echo htmlspecialchars($job_title); ?></h3>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="job_application" value="1">
        <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
        
        <!-- Form sections same as before but with improved styling -->
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Submit Application</button>
        </div>
    </form>
</div>

<?php 
$content = ob_get_clean();
echo $content;
include 'includes/footer.php'; 
?>