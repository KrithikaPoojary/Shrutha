<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit;
}

$page_title = "Recent Job Listings";

$result = mysqli_query($conn, "SELECT * FROM jobs ORDER BY created_at DESC LIMIT 20");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recent Job Listings - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --border: #dee2e6;
            --card-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08);
            --hover-shadow: 0 0.5rem 1.5rem rgba(67, 97, 238, 0.2);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            color: var(--dark);
            min-height: 100vh;
            padding: 20px;
        }
        
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 25px;
        }
        
        /* Sidebar Styles */
        .sidebar {
            background: linear-gradient(160deg, var(--primary), var(--secondary));
            border-radius: 20px;
            padding: 30px 20px;
            height: fit-content;
            box-shadow: var(--card-shadow);
        }
        
        .sidebar-header {
            display: flex;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 25px;
        }
        
        .sidebar-header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255,255,255,0.3);
            margin-right: 15px;
        }
        
        .sidebar-header h3 {
            color: white;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            color: rgba(255,255,255,0.8);
            font-size: 13px;
            margin: 0;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 8px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255,255,255,0.15);
            color: white;
        }
        
        .sidebar-menu i {
            margin-right: 12px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px 30px;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
        }
        
        .top-bar h1 {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-info h4 {
            font-size: 16px;
            margin: 0;
            font-weight: 600;
        }
        
        .user-info p {
            font-size: 14px;
            color: var(--gray);
            margin: 0;
        }
        
        .user-profile img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--light-gray);
        }
        
        /* Card Styles */
        .card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .card-header {
            padding: 20px 30px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h5 {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            color: var(--dark);
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(120deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(120deg, var(--secondary), var(--primary));
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.4);
        }
        
        .card-body {
            padding: 30px;
        }
        
        /* Table Styles */
        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
        }
        
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0;
        }
        
        .table thead th {
            background: linear-gradient(120deg, var(--primary), var(--secondary));
            color: white;
            padding: 16px 20px;
            font-weight: 500;
            text-align: left;
            border: none;
        }
        
        .table thead th:first-child {
            border-top-left-radius: 15px;
        }
        
        .table thead th:last-child {
            border-top-right-radius: 15px;
        }
        
        .table tbody td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--light-gray);
            vertical-align: middle;
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .table tbody tr {
            transition: background 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .status-active {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }
        
        .status-inactive {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }
        
        .status-pending {
            background: rgba(255, 193, 7, 0.15);
            color: #ffc107;
        }
        
        .application-count {
            display: inline-block;
            background: rgba(76, 201, 240, 0.15);
            color: var(--success);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .job-type {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            background: rgba(108, 117, 125, 0.1);
            color: var(--gray);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--light-gray);
            color: var(--gray);
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-icon:hover {
            background: var(--primary);
            color: white;
        }
        
        .job-title {
            font-weight: 500;
            color: var(--dark);
        }
        
        .company-name {
            color: var(--gray);
            font-size: 14px;
        }
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            padding: 20px;
            border-radius: 15px;
            color: white;
            display: flex;
            flex-direction: column;
        }
        
        .stat-card:nth-child(1) {
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
        }
        
        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #f72585, #b5179e);
        }
        
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #4cc9f0, #4895ef);
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: 600;
            margin: 10px 0 5px;
        }
        
        .stat-card .label {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .stat-card i {
            font-size: 28px;
            align-self: flex-end;
            opacity: 0.8;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .user-profile {
                align-self: flex-end;
            }
            
            .table thead {
                display: none;
            }
            
            .table, .table tbody, .table tr, .table td {
                display: block;
                width: 100%;
            }
            
            .table tr {
                margin-bottom: 20px;
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            }
            
            .table td {
                padding: 12px 20px;
                border-bottom: 1px solid var(--light-gray);
                text-align: right;
                position: relative;
                padding-left: 50%;
            }
            
            .table td:before {
                content: attr(data-label);
                position: absolute;
                left: 20px;
                width: 45%;
                text-align: left;
                font-weight: 600;
                color: var(--dark);
            }
            
            .action-buttons {
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Admin Profile">
                <div>
                    <h3>Robert Johnson</h3>
                    <p>Administrator</p>
                </div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="manage_jobs.php" class="active"><i class="fas fa-briefcase"></i> Manage Jobs</a></li>
                <li><a href="#"><i class="fas fa-users"></i> Candidates</a></li>
                <li><a href="#"><i class="fas fa-building"></i> Companies</a></li>
                <li><a href="#"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-briefcase me-2"></i> Recent Job Listings</h1>
                <div class="user-profile">
                    <div class="user-info">
                        <h4>Robert Johnson</h4>
                        <p>Administrator</p>
                    </div>
                    <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="User Profile">
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <i class="fas fa-briefcase"></i>
                    <div class="value">142</div>
                    <div class="label">Total Jobs</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-file-alt"></i>
                    <div class="value">1,248</div>
                    <div class="label">Applications</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-building"></i>
                    <div class="value">87</div>
                    <div class="label">Companies</div>
                </div>
            </div>
            
            <!-- Job Listings Card -->
            <div class="card">
                <div class="card-header">
                    <h5>Recent Job Listings</h5>
                    <a href="manage_jobs.php" class="btn btn-primary">
                        <i class="fas fa-eye me-1"></i> View All Jobs
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Company</th>
                                    <th>Type</th>
                                    <th>Applications</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="job-title">Senior Frontend Developer</div>
                                        <div class="company-name">Tech Innovations Inc.</div>
                                    </td>
                                    <td>Tech Innovations</td>
                                    <td><span class="job-type">Full-time</span></td>
                                    <td><span class="application-count">42</span></td>
                                    <td><span class="status-badge status-active">Active</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="job-title">UX/UI Designer</div>
                                        <div class="company-name">Creative Solutions</div>
                                    </td>
                                    <td>Creative Solutions</td>
                                    <td><span class="job-type">Contract</span></td>
                                    <td><span class="application-count">28</span></td>
                                    <td><span class="status-badge status-active">Active</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="job-title">Data Analyst</div>
                                        <div class="company-name">Data Insights Co.</div>
                                    </td>
                                    <td>Data Insights</td>
                                    <td><span class="job-type">Part-time</span></td>
                                    <td><span class="application-count">19</span></td>
                                    <td><span class="status-badge status-pending">Pending</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="job-title">DevOps Engineer</div>
                                        <div class="company-name">Cloud Systems Ltd.</div>
                                    </td>
                                    <td>Cloud Systems</td>
                                    <td><span class="job-type">Full-time</span></td>
                                    <td><span class="application-count">35</span></td>
                                    <td><span class="status-badge status-active">Active</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="job-title">Marketing Manager</div>
                                        <div class="company-name">Global Brands</div>
                                    </td>
                                    <td>Global Brands</td>
                                    <td><span class="job-type">Full-time</span></td>
                                    <td><span class="application-count">24</span></td>
                                    <td><span class="status-badge status-inactive">Inactive</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Simple animations and interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add animation to table rows
            const rows = document.querySelectorAll('.table tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, 100 * index);
            });
            
            // Hover effects for buttons
            const buttons = document.querySelectorAll('.btn-icon');
            buttons.forEach(button => {
                button.addEventListener('mouseover', function() {
                    this.style.transform = 'scale(1.1)';
                });
                button.addEventListener('mouseout', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>