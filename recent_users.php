<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit;
}

$page_title = "Recent Users";

$result = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC LIMIT 20");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recent Users - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #7209b7;
            --warning: #f72585;
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
            background: linear-gradient(160deg, var(--info), #560bad);
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
            background: linear-gradient(120deg, var(--info), #560bad);
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
            background-color: rgba(114, 9, 183, 0.05);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
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
        
        .user-role {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            background: rgba(76, 201, 240, 0.15);
            color: var(--success);
        }
        
        .user-role.admin {
            background: rgba(247, 37, 133, 0.15);
            color: var(--warning);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--light-gray);
            margin-right: 10px;
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
            background: var(--info);
            color: white;
            transform: scale(1.1);
        }
        
        .user-name {
            font-weight: 500;
            color: var(--dark);
        }
        
        .user-email {
            color: var(--gray);
            font-size: 14px;
        }
        
        .joined-date {
            font-size: 14px;
            color: var(--gray);
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
            background: linear-gradient(135deg, var(--info), #480ca8);
        }
        
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #f72585, #b5179e);
        }
        
        .stat-card:nth-child(4) {
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
        
        /* Search and Filter */
        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 15px 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: var(--card-shadow);
        }
        
        .search-box {
            position: relative;
            width: 300px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border-radius: 50px;
            border: 1px solid var(--light-gray);
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--info);
            box-shadow: 0 0 0 3px rgba(114, 9, 183, 0.1);
        }
        
        .search-box i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        .filter-options {
            display: flex;
            gap: 15px;
        }
        
        .filter-select {
            padding: 10px 20px;
            border-radius: 50px;
            border: 1px solid var(--light-gray);
            font-size: 14px;
            background: white;
            cursor: pointer;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--info);
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
            
            .filter-bar {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .search-box {
                width: 100%;
            }
            
            .filter-options {
                flex-wrap: wrap;
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
                <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Admin Profile">
                <div>
                    <h3>Sarah Johnson</h3>
                    <p>Administrator</p>
                </div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="#"><i class="fas fa-briefcase"></i> Manage Jobs</a></li>
                <li><a href="recent_users.php" class="active"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="#"><i class="fas fa-building"></i> Companies</a></li>
                <li><a href="#"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-users me-2"></i> Recent Users</h1>
                <div class="user-profile">
                    <div class="user-info">
                        <h4>Sarah Johnson</h4>
                        <p>Administrator</p>
                    </div>
                    <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="User Profile">
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <div class="value">1,248</div>
                    <div class="label">Total Users</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-check"></i>
                    <div class="value">1,102</div>
                    <div class="label">Active Users</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-shield"></i>
                    <div class="value">24</div>
                    <div class="label">Administrators</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-clock"></i>
                    <div class="value">122</div>
                    <div class="label">New This Month</div>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search users by name, email or role...">
                </div>
                <div class="filter-options">
                    <select class="filter-select">
                        <option>All Roles</option>
                        <option>Administrator</option>
                        <option>Employer</option>
                        <option>Job Seeker</option>
                    </select>
                    <select class="filter-select">
                        <option>All Statuses</option>
                        <option>Active</option>
                        <option>Inactive</option>
                        <option>Pending</option>
                    </select>
                    <select class="filter-select">
                        <option>Sort by: Newest</option>
                        <option>Sort by: Oldest</option>
                        <option>Sort by: Name</option>
                    </select>
                </div>
            </div>
            
            <!-- Users Card -->
            <div class="card">
                <div class="card-header">
                    <h5>Recent Users</h5>
                    <a href="manage_users.php" class="btn btn-primary">
                        <i class="fas fa-eye me-1"></i> View All Users
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center;">
                                            <img src="https://randomuser.me/api/portraits/men/32.jpg" class="user-avatar" alt="User">
                                            <div>
                                                <div class="user-name">Robert Johnson</div>
                                                <div class="user-email">robert@example.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>robert@example.com</td>
                                    <td><span class="user-role admin">Administrator</span></td>
                                    <td><span class="joined-date">Jun 12, 2023</span></td>
                                    <td><span class="status-badge status-active"><i class="fas fa-check-circle"></i> Active</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center;">
                                            <img src="https://randomuser.me/api/portraits/women/65.jpg" class="user-avatar" alt="User">
                                            <div>
                                                <div class="user-name">Jennifer Smith</div>
                                                <div class="user-email">jennifer@example.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>jennifer@example.com</td>
                                    <td><span class="user-role">Employer</span></td>
                                    <td><span class="joined-date">Jul 5, 2023</span></td>
                                    <td><span class="status-badge status-active"><i class="fas fa-check-circle"></i> Active</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center;">
                                            <img src="https://randomuser.me/api/portraits/men/22.jpg" class="user-avatar" alt="User">
                                            <div>
                                                <div class="user-name">Michael Brown</div>
                                                <div class="user-email">michael@example.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>michael@example.com</td>
                                    <td><span class="user-role">Job Seeker</span></td>
                                    <td><span class="joined-date">Aug 18, 2023</span></td>
                                    <td><span class="status-badge status-inactive"><i class="fas fa-times-circle"></i> Inactive</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center;">
                                            <img src="https://randomuser.me/api/portraits/women/32.jpg" class="user-avatar" alt="User">
                                            <div>
                                                <div class="user-name">Emily Davis</div>
                                                <div class="user-email">emily@example.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>emily@example.com</td>
                                    <td><span class="user-role">Employer</span></td>
                                    <td><span class="joined-date">Sep 2, 2023</span></td>
                                    <td><span class="status-badge status-pending"><i class="fas fa-clock"></i> Pending</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon"><i class="fas fa-eye"></i></button>
                                            <button class="btn-icon"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center;">
                                            <img src="https://randomuser.me/api/portraits/men/67.jpg" class="user-avatar" alt="User">
                                            <div>
                                                <div class="user-name">David Wilson</div>
                                                <div class="user-email">david@example.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>david@example.com</td>
                                    <td><span class="user-role">Job Seeker</span></td>
                                    <td><span class="joined-date">Sep 15, 2023</span></td>
                                    <td><span class="status-badge status-active"><i class="fas fa-check-circle"></i> Active</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon"><i class="fas fa-eye"></i></button>
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
            
            // Search functionality
            const searchInput = document.querySelector('.search-box input');
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('.table tbody tr');
                
                rows.forEach(row => {
                    const name = row.querySelector('.user-name').textContent.toLowerCase();
                    const email = row.querySelector('.user-email').textContent.toLowerCase();
                    const role = row.querySelector('.user-role').textContent.toLowerCase();
                    
                    if (name.includes(searchTerm) || email.includes(searchTerm) || role.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            
            // Filter by role
            const roleFilter = document.querySelector('.filter-select:first-child');
            roleFilter.addEventListener('change', function() {
                const selectedRole = this.value;
                const rows = document.querySelectorAll('.table tbody tr');
                
                rows.forEach(row => {
                    const role = row.querySelector('.user-role').textContent;
                    
                    if (selectedRole === 'All Roles' || role === selectedRole) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            
            // Filter by status
            const statusFilter = document.querySelectorAll('.filter-select')[1];
            statusFilter.addEventListener('change', function() {
                const selectedStatus = this.value;
                const rows = document.querySelectorAll('.table tbody tr');
                
                rows.forEach(row => {
                    const status = row.querySelector('.status-badge').textContent.trim();
                    
                    if (selectedStatus === 'All Statuses' || status === selectedStatus) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>