<?php
session_start();
require_once 'config.php';

// Define user_id for saved jobs logic
$user_id = null;
$user_email = null;
if (isset($_SESSION['user']) && isset($_SESSION['role']) && $_SESSION['role'] === 'employee') {
    $user_id = $_SESSION['user'];
    
    // Get user email to check for existing applications
    $email_query = "SELECT email FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $email_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $email_result = mysqli_stmt_get_result($stmt);
    $user_data = mysqli_fetch_assoc($email_result);
    $user_email = $user_data['email'] ?? null;
}

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sector = isset($_GET['sector']) ? trim($_GET['sector']) : '';

// Build the base query
$query = "SELECT 
            j.*, 
            c.organization_name, 
            c.sector 
          FROM 
            job_openings j
          JOIN 
            companies c ON j.company_id = c.id
          WHERE 1=1";

// Add condition to exclude jobs the user has already applied for
if (!empty($user_email)) {
    $query .= " AND j.id NOT IN (
                SELECT job_id FROM applications 
                WHERE email = '" . mysqli_real_escape_string($conn, $user_email) . "'
              )";
}

// Add search filter if provided
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $query .= " AND (j.job_designation LIKE '%$search%' OR c.organization_name LIKE '%$search%')";
}

// Add sector filter if provided and not "All Sectors"
if (!empty($sector) && $sector !== 'All Sectors') {
    $sector = mysqli_real_escape_string($conn, $sector);
    $query .= " AND c.sector = '$sector'";
}

// Add ordering
$query .= " ORDER BY j.id DESC";

$result = mysqli_query($conn, $query);
$jobs = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get unique sectors for the dropdown
$sectors_query = "SELECT DISTINCT sector FROM companies WHERE sector IS NOT NULL AND sector != '' ORDER BY sector";
$sectors_result = mysqli_query($conn, $sectors_query);
$sectors = mysqli_fetch_all($sectors_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Current Job Openings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .job-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            border-left: 4px solid #3498db;
        }
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
        }
        .job-header {
            border-bottom: 1px solid #eee;
            padding: 20px;
        }
        .job-body {
            padding: 20px;
        }
        .job-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .company-name {
            color: #3498db;
            font-weight: 500;
        }
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
        }
        .meta-item {
            display: flex;
            align-items: center;
            color: #555;
        }
        .meta-item i {
            margin-right: 8px;
            color: #3498db;
        }
        .ctc-badge {
            background-color: #27ae60;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        .sector-badge {
            background-color: #e74c3c;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .search-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        .page-header {
            background: linear-gradient(to right, #2c3e50, #3498db);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        .empty-state {
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .empty-state i {
            font-size: 3rem;
            color: #95a5a6;
            margin-bottom: 20px;
        }
        .active-filters {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .filter-badge {
            background: #3498db;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            margin-right: 10px;
        }
        .applied-badge {
            background-color: #6c757d;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
         <?php if(isset($_SESSION['user'])): ?>
            <a href="employee_dashboard.php" class="btn btn-outline-secondary" style="text-decoration: none; position: absolute; left: 40px; box-shadow: none; margin-top: 8px;">
                <i class="fas fa-arrow-left me-2"></i>
            </a>
        <?php else: ?>
            <a href="index.php" class="btn btn-outline-secondary" style="text-decoration: none; position: absolute; left: 40px; box-shadow: none; margin-top: 8px;">
                <i class="fas fa-arrow-left me-2"></i>
            </a>
        <?php endif; ?>
        <div class="page-header text-center">
            <h1><i class="fas fa-briefcase me-2"></i>Current Job Openings</h1>
            <p class="lead mb-0">Browse through all available positions from our recruiting partners</p>
            <?php if(!empty($user_email)): ?>
                <p class="mt-2"><small><i class="fas fa-info-circle me-1"></i>Jobs you've already applied for are hidden</small></p>
            <?php endif; ?>
        </div>
        
        <div class="search-container mb-4">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-8">
                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" name="search" placeholder="Search for jobs by designation or company..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="sector">
                            <option value="All Sectors" <?= $sector === 'All Sectors' || empty($sector) ? 'selected' : '' ?>>All Sectors</option>
                            <?php foreach ($sectors as $sector_option): ?>
                                <option value="<?= htmlspecialchars($sector_option['sector']) ?>" 
                                        <?= $sector === $sector_option['sector'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sector_option['sector']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i> Apply Filters
                            </button>
                            <?php if (!empty($search) || (!empty($sector) && $sector !== 'All Sectors')): ?>
                                <a href="jobs.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> Clear Filters
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <?php if (!empty($search) || (!empty($sector) && $sector !== 'All Sectors')): ?>
            <div class="active-filters">
                <h6 class="mb-2"><i class="fas fa-filter me-1"></i> Active Filters:</h6>
                <?php if (!empty($search)): ?>
                    <span class="filter-badge">
                        <i class="fas fa-search me-1"></i> Search: "<?= htmlspecialchars($search) ?>"
                    </span>
                <?php endif; ?>
                <?php if (!empty($sector) && $sector !== 'All Sectors'): ?>
                    <span class="filter-badge">
                        <i class="fas fa-industry me-1"></i> Sector: <?= htmlspecialchars($sector) ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($jobs)): ?>
            <div class="row">
                <?php foreach ($jobs as $job): ?>
                    <div class="col-md-6">
                        <div class="job-card">
                            <div class="job-header">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h3 class="job-title"><?= htmlspecialchars($job['job_designation']) ?></h3>
                                        <p class="company-name mb-1"><?= htmlspecialchars($job['organization_name']) ?></p>
                                        <span class="sector-badge"><?= htmlspecialchars($job['sector']) ?></span>
                                    </div>
                                    <div class="ctc-badge">
                                        ₹<?= number_format($job['from_ctc'], 1) ?> - <?= number_format($job['to_ctc'], 1) ?> LPA
                                    </div>
                                </div>
                            </div>
                            <div class="job-body">
                                <p class="text-muted"><?= htmlspecialchars($job['job_description']) ?></p>
                                
                                <div class="job-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-graduation-cap"></i>
                                        <?= htmlspecialchars($job['qualification']) ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?= htmlspecialchars($job['job_location']) ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-briefcase"></i>
                                        <?= $job['exp_from'] ?>-<?= $job['exp_to'] ?> years
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-users"></i>
                                        <?= $job['vacancies'] ?> openings
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i> Posted on <?= date('M d, Y', strtotime($job['created_at'])) ?>
                                    </small>
                                    
                                    <div class="text-end">
                                        <a href="apply.php?job_id=<?= $job['id'] ?>" class="btn btn-primary btn-sm d-block w-100">Apply Now</a>
                                    
                                        <?php if (isset($_SESSION['user']) && $_SESSION['role'] === 'employee'): ?>
                                            <?php
                                            // Check if job is already saved
                                            $is_saved = false;
                                            if (isset($user_id)) {
                                                $check_saved_sql = "SELECT id FROM saved_jobs WHERE user_id = ? AND job_id = ?";
                                                $stmt_check = mysqli_prepare($conn, $check_saved_sql);
                                                mysqli_stmt_bind_param($stmt_check, "ii", $user_id, $job['id']);
                                                mysqli_stmt_execute($stmt_check);
                                                $saved_result = mysqli_stmt_get_result($stmt_check);
                                                $is_saved = mysqli_num_rows($saved_result) > 0;
                                            }
                                            ?>
                                            
                                            <?php if ($is_saved): ?>
                                                <a href="savedjobs.php" class="btn btn-success btn-sm mt-2 d-block w-100">
                                                    <i class="fas fa-bookmark me-1"></i> Saved
                                                </a>
                                            <?php else: ?>
                                                <a href="savedjobs.php?save_job=<?= $job['id'] ?>" class="btn btn-outline-secondary btn-sm mt-2 d-block w-100">
                                                    <i class="far fa-bookmark me-1"></i> Save Job
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-briefcase"></i>
                <h3>
                    <?php if (!empty($search) || (!empty($sector) && $sector !== 'All Sectors')): ?>
                        No Jobs Match Your Criteria
                    <?php else: ?>
                        <?php if (!empty($user_email)): ?>
                            No New Job Openings Available
                        <?php else: ?>
                            No Job Openings Available
                        <?php endif; ?>
                    <?php endif; ?>
                </h3>
                <p class="text-muted">
                    <?php if (!empty($search) || (!empty($sector) && $sector !== 'All Sectors')): ?>
                        No job openings found matching your search criteria. Try adjusting your filters or search terms.
                    <?php else: ?>
                        <?php if (!empty($user_email)): ?>
                            You have either applied to all available jobs or there are no new job openings at the moment.
                        <?php else: ?>
                            There are currently no job openings posted. Please check back later.
                        <?php endif; ?>
                    <?php endif; ?>
                </p>
                <?php if (!empty($search) || (!empty($sector) && $sector !== 'All Sectors')): ?>
                    <a href="jobs.php" class="btn btn-primary mt-3">
                        <i class="fas fa-times me-1"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>