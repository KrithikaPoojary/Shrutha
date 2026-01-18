<?php

session_start();
require_once 'config.php';
// Check if user is logged in and is an employer
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'employer') {
    header('Location: login.php');
    exit;
}

$search_results = [];
$search_performed = false;

// Process search form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['search_candidates'])) {
        $search_performed = true;
        
        // Build search query
        $sql = "SELECT * FROM registrations WHERE 1=1";
        $params = [];
        $types = "";
        
        // Skills search
        if (!empty($_POST['skills'])) {
            $skills = explode(',', $_POST['skills']);
            $skill_conditions = [];
            foreach ($skills as $skill) {
                $skill_conditions[] = "skills LIKE ?";
                $params[] = '%' . trim($skill) . '%';
                $types .= "s";
            }
            $sql .= " AND (" . implode(" OR ", $skill_conditions) . ")";
        }
        
        // Course/Education search
        if (!empty($_POST['course'])) {
            $sql .= " AND (degree_course LIKE ? OR pg_course LIKE ? OR diploma_course LIKE ?)";
            $params[] = '%' . $_POST['course'] . '%';
            $params[] = '%' . $_POST['course'] . '%';
            $params[] = '%' . $_POST['course'] . '%';
            $types .= "sss";
        }
        
        // Stream search
        if (!empty($_POST['stream'])) {
            $sql .= " AND (degree_stream LIKE ? OR pg_stream LIKE ? OR diploma_stream LIKE ?)";
            $params[] = '%' . $_POST['stream'] . '%';
            $params[] = '%' . $_POST['stream'] . '%';
            $params[] = '%' . $_POST['stream'] . '%';
            $types .= "sss";
        }
        
        // Location search
        if (!empty($_POST['location'])) {
            $sql .= " AND (state LIKE ? OR district LIKE ? OR hometown LIKE ?)";
            $params[] = '%' . $_POST['location'] . '%';
            $params[] = '%' . $_POST['location'] . '%';
            $params[] = '%' . $_POST['location'] . '%';
            $types .= "sss";
        }
        
        // Experience filter
        if (!empty($_POST['experience'])) {
            if ($_POST['experience'] === 'experienced') {
                $sql .= " AND experience = 'Yes'";
            } elseif ($_POST['experience'] === 'fresher') {
                $sql .= " AND experience = 'No'";
            }
        }
        
        // Availability for relocation
        if (!empty($_POST['relocation'])) {
            $sql .= " AND relocation = ?";
            $params[] = $_POST['relocation'];
            $types .= "s";
        }
        
        // Sort order
        $sql .= " ORDER BY registration_date DESC";
        
        // Execute query
        $stmt = mysqli_prepare($conn, $sql);
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $search_results[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
    
    // Handle reset form
    if (isset($_POST['reset_form'])) {
        // Clear the POST data and redirect to same page to clear form
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get available courses and streams for filters
$courses = [];
$streams = [];
$courses_sql = "SELECT DISTINCT degree_course FROM registrations WHERE degree_course IS NOT NULL AND degree_course != '' 
                UNION SELECT DISTINCT pg_course FROM registrations WHERE pg_course IS NOT NULL AND pg_course != '' 
                UNION SELECT DISTINCT diploma_course FROM registrations WHERE diploma_course IS NOT NULL AND diploma_course != ''";
$courses_result = mysqli_query($conn, $courses_sql);
while ($row = mysqli_fetch_assoc($courses_result)) {
    if (!empty($row['degree_course'])) {
        $courses[] = $row['degree_course'];
    }
}

$streams_sql = "SELECT DISTINCT degree_stream FROM registrations WHERE degree_stream IS NOT NULL AND degree_stream != '' 
                UNION SELECT DISTINCT pg_stream FROM registrations WHERE pg_stream IS NOT NULL AND pg_stream != '' 
                UNION SELECT DISTINCT diploma_stream FROM registrations WHERE diploma_stream IS NOT NULL AND diploma_stream != ''";
$streams_result = mysqli_query($conn, $streams_sql);
while ($row = mysqli_fetch_assoc($streams_result)) {
    if (!empty($row['degree_stream'])) {
        $streams[] = $row['degree_stream'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Search | Shrutha Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 1.1em;
        }
        
        .search-card, .results-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .candidate-item {
            border: 1px solid #ecf0f1;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        
        .candidate-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .candidate-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .candidate-name {
            font-size: 1.3em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .candidate-education {
            color: #3498db;
            font-weight: 500;
        }
        
        .candidate-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .candidate-meta span {
            background: #ecf0f1;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            color: #2c3e50;
        }
        
        .candidate-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .detail-item {
            margin-bottom: 8px;
        }
        
        .detail-label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9em;
        }
        
        .detail-value {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .candidate-skills {
            margin-bottom: 15px;
        }
        
        .skill-tag {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            margin-right: 8px;
            margin-bottom: 8px;
        }
        
        .candidate-actions {
            display: flex;
            gap: 10px;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        
        .no-results i {
            font-size: 3em;
            margin-bottom: 20px;
            color: #bdc3c7;
        }
        
        .search-info {
            background: #e8f4fc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .results-count {
            font-weight: 600;
            color: #3498db;
        }
        
        .filter-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .filter-tag {
            background: #3498db;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .filter-tag .remove {
            cursor: pointer;
            font-weight: bold;
        }
        
        /* Modal styles */
        .modal-candidate-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            display: block;
            border: 4px solid #3498db;
        }
        
        .modal-section {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-section-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.2em;
        }
        
        .education-item, .experience-item {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #eee;
        }
        
        .education-item:last-child, .experience-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="employer_dashboard.php" class="btn btn-outline-secondary" style="text-decoration: none; position: absolute; left: 40px; box-shadow: none; margin-top: 8px;">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
        <div class="header">
            <h1>Candidate Search</h1>
            <p>Find the perfect candidates for your job openings</p>
        </div>
        
        <!-- Search Form -->
        <div class="search-card">
            <h2 style="margin-bottom: 20px; color: #2c3e50;">Search Candidates</h2>
            
            <form method="POST" action="" id="searchForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="skills">Skills</label>
                        <input type="text" id="skills" name="skills" class="form-control" placeholder="e.g., Java, Python, React (comma separated)" value="<?php echo isset($_POST['skills']) ? htmlspecialchars($_POST['skills']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="course">Course/Education</label>
                        <select id="course" name="course" class="form-control">
                            <option value="">Any Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course); ?>" <?php echo (isset($_POST['course']) && $_POST['course'] === $course) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="stream">Stream</label>
                        <select id="stream" name="stream" class="form-control">
                            <option value="">Any Stream</option>
                            <?php foreach ($streams as $stream): ?>
                                <option value="<?php echo htmlspecialchars($stream); ?>" <?php echo (isset($_POST['stream']) && $_POST['stream'] === $stream) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($stream); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" class="form-control" placeholder="e.g., Bangalore, Karnataka" value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="experience">Experience</label>
                        <select id="experience" name="experience" class="form-control">
                            <option value="">Any Experience</option>
                            <option value="fresher" <?php echo (isset($_POST['experience']) && $_POST['experience'] === 'fresher') ? 'selected' : ''; ?>>Fresher</option>
                            <option value="experienced" <?php echo (isset($_POST['experience']) && $_POST['experience'] === 'experienced') ? 'selected' : ''; ?>>Experienced</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="relocation">Willing to Relocate</label>
                        <select id="relocation" name="relocation" class="form-control">
                            <option value="">Any</option>
                            <option value="Yes" <?php echo (isset($_POST['relocation']) && $_POST['relocation'] === 'Yes') ? 'selected' : ''; ?>>Yes</option>
                            <option value="No" <?php echo (isset($_POST['relocation']) && $_POST['relocation'] === 'No') ? 'selected' : ''; ?>>No</option>
                        </select>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" name="search_candidates" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search Candidates
                    </button>
                    <button type="submit" name="reset_form" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset Filters
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Search Results -->
        <?php if ($search_performed): ?>
            <div class="results-card">
                <h2 style="margin-bottom: 20px; color: #2c3e50;">Search Results</h2>
                
                <div class="search-info">
                    Found <span class="results-count"><?php echo count($search_results); ?></span> candidates matching your criteria
                </div>
                
                <?php if (empty($search_results)): ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>No Candidates Found</h3>
                        <p>Try adjusting your search criteria to find more candidates.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($search_results as $candidate): ?>
                        <div class="candidate-item" id="candidate-<?php echo $candidate['id']; ?>" data-email="<?php echo htmlspecialchars($candidate['email']); ?>" data-name="<?php echo htmlspecialchars($candidate['full_name']); ?>">
                            <div class="candidate-header">
                                <div>
                                    <div class="candidate-name"><?php echo htmlspecialchars($candidate['full_name']); ?></div>
                                    <div class="candidate-education">
                                        <?php 
                                        $education = [];
                                        if (!empty($candidate['degree_course'])) {
                                            $education[] = $candidate['degree_course'] . ' (' . $candidate['degree_stream'] . ')';
                                        }
                                        if (!empty($candidate['pg_course'])) {
                                            $education[] = $candidate['pg_course'] . ' (' . $candidate['pg_stream'] . ')';
                                        }
                                        if (!empty($candidate['diploma_course'])) {
                                            $education[] = $candidate['diploma_course'] . ' (' . $candidate['diploma_stream'] . ')';
                                        }
                                        echo implode(' | ', $education);
                                        ?>
                                    </div>
                                </div>
                                <div style="color: <?php echo $candidate['experience'] === 'Yes' ? '#27ae60' : '#3498db'; ?>; font-weight: 500;">
                                    <?php echo $candidate['experience'] === 'Yes' ? 'Experienced' : 'Fresher'; ?>
                                </div>
                            </div>
                            
                            <div class="candidate-meta">
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($candidate['hometown'] . ', ' . $candidate['state']); ?></span>
                                <span><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($candidate['college_name']); ?></span>
                                <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($candidate['mobile']); ?></span>
                                <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($candidate['email']); ?></span>
                            </div>
                            
                            <div class="candidate-details">
                                <div class="details-grid">
                                    <div class="detail-item">
                                        <span class="detail-label">University:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($candidate['university']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Location:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($candidate['college_location']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Degree Marks:</span>
                                        <span class="detail-value">
                                            <?php 
                                            // Fix for degree marks display
                                            if ($candidate['degree_marking_system'] === 'CGPA') {
                                                echo number_format((float)$candidate['degree_marks'], 2) . ' CGPA';
                                            } else {
                                                echo number_format((float)$candidate['degree_marks'], 2) . '%';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Relocation:</span>
                                        <span class="detail-value"><?php echo $candidate['relocation']; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($candidate['skills'])): ?>
                                <div class="candidate-skills">
                                    <strong>Skills:</strong><br>
                                    <?php 
                                    $skills = explode(',', $candidate['skills']);
                                    foreach ($skills as $skill): 
                                        if (!empty(trim($skill))):
                                    ?>
                                        <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="candidate-actions">
                                <button class="btn btn-primary" onclick="viewCandidate(<?php echo $candidate['id']; ?>)">
                                    <i class="fas fa-eye"></i> View Full Profile
                                </button>
                                <button class="btn btn-secondary" onclick="contactCandidate(<?php echo $candidate['id']; ?>)">
                                    <i class="fas fa-envelope"></i> Contact
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Candidate Profile Modal -->
    <div class="modal fade" id="candidateModal" tabindex="-1" aria-labelledby="candidateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="candidateModalLabel">Candidate Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="candidateModalBody">
                    <!-- Content will be loaded here by JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="contactCandidateBtn" class="btn btn-primary">
                        <i class="fas fa-envelope"></i> Contact Candidate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentCandidateId = null;
        
        function viewCandidate(candidateId) {
            fetch('record_profile_view.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `candidate_id=${candidateId}&view_type=profile`
            }).catch(error => console.error('Error logging view:', error));

            currentCandidateId = candidateId;
            // Get candidate data from the page
            const candidateElement = document.getElementById('candidate-' + candidateId);
            const candidateName = candidateElement.querySelector('.candidate-name').textContent;
            const candidateEmail = candidateElement.getAttribute('data-email');
            
            // Create modal content
            const modalBody = document.getElementById('candidateModalBody');
            modalBody.innerHTML = `
                <div class="text-center mb-4">
                    <div class="modal-candidate-photo bg-light d-flex align-items-center justify-content-center">
                        <i class="fas fa-user fa-3x text-muted"></i>
                    </div>
                    <h3>${candidateName}</h3>
                    <p class="text-muted">${candidateElement.querySelector('.candidate-education').textContent}</p>
                </div>
                
                <div class="modal-section">
                    <h5 class="modal-section-title">Contact Information</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Email:</strong> ${candidateEmail}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Phone:</strong> ${candidateElement.querySelector('.candidate-meta span:nth-child(3)').textContent.replace('📱 ', '')}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Location:</strong> ${candidateElement.querySelector('.candidate-meta span:nth-child(1)').textContent.replace('📍 ', '')}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>College:</strong> ${candidateElement.querySelector('.candidate-meta span:nth-child(2)').textContent.replace('🎓 ', '')}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <h5 class="modal-section-title">Education</h5>
                    <div class="education-item">
                        <h6>${candidateElement.querySelector('.candidate-education').textContent}</h6>
                        <p><strong>University:</strong> ${candidateElement.querySelector('.detail-item:nth-child(1) .detail-value').textContent}</p>
                        <p><strong>Marks:</strong> ${candidateElement.querySelector('.detail-item:nth-child(3) .detail-value').textContent}</p>
                    </div>
                </div>
                
                <div class="modal-section">
                    <h5 class="modal-section-title">Skills</h5>
                    <div class="skills-container">
                        ${candidateElement.querySelector('.candidate-skills') ? candidateElement.querySelector('.candidate-skills').innerHTML : 'No skills listed'}
                    </div>
                </div>
                
                <div class="modal-section">
                    <h5 class="modal-section-title">Additional Information</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Experience:</strong> ${candidateElement.querySelector('.candidate-header div:nth-child(2)').textContent.trim()}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Willing to Relocate:</strong> ${candidateElement.querySelector('.detail-item:nth-child(4) .detail-value').textContent}</p>
                        </div>
                    </div>
                </div>
            `;
            
            // Show the modal
            const candidateModal = new bootstrap.Modal(document.getElementById('candidateModal'));
            candidateModal.show();
        }
        
        function contactCandidate(candidateId) {
            const candidateElement = document.getElementById('candidate-' + candidateId);
            const candidateEmail = candidateElement.getAttribute('data-email');
            const candidateName = candidateElement.getAttribute('data-name');
            
            // Create proper email subject and body without URL encoding issues
            const subject = "Job Opportunity - Shrutha Portal";
            const body = `Dear ${candidateName},\n\nI came across your profile on our Shrutha portal and would like to discuss potential opportunities with you.\n\nBest regards,\n[Your Name]`;
            
            // Create mailto link
            const mailtoLink = `mailto:${candidateEmail}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
            
            // Open email client
            window.location.href = mailtoLink;
        }
        
        // Add event listener for modal contact button
        document.getElementById('contactCandidateBtn').addEventListener('click', function() {
            if (currentCandidateId) {
                contactCandidate(currentCandidateId);
            }
        });
        
        // Add some sample skill suggestions
        document.getElementById('skills').addEventListener('focus', function() {
            if (!this.getAttribute('data-suggestions-shown')) {
                this.setAttribute('placeholder', 'e.g., Java, Python, React, HTML, CSS, JavaScript, PHP, MySQL');
                this.setAttribute('data-suggestions-shown', 'true');
            }
        });
    </script>
</body>
</html>