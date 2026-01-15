<?php
require_once 'config.php';
$page_title = "Career Resources";
include 'header.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Career Resources</h1>
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Resume Tips</h5>
                    <p class="card-text">Learn how to create a professional resume that stands out to employers.</p>
                    <a href="resume_tips.php" class="btn btn-primary">Learn More</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Interview Preparation</h5>
                    <p class="card-text">Prepare for your next job interview with our comprehensive guide.</p>
                    <a href="interview_prep.php" class="btn btn-primary">Learn More</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Career Advice</h5>
                    <p class="card-text">Get expert advice on advancing your career and professional development.</p>
                    <a href="career_advice.php" class="btn btn-primary">Learn More</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>