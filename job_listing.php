<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareerConnect - Find Your Dream Job</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        
        /* Header Styles */
        header {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
            padding: 1.5rem 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
        }
        
        .logo i {
            margin-right: 10px;
            font-size: 2rem;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav li {
            margin-left: 2rem;
        }
        
        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
        }
        
        nav a:hover {
            opacity: 0.9;
        }
        
        .search-section {
            background: white;
            max-width: 1200px;
            margin: -30px auto 30px;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            position: relative;
            z-index: 10;
        }
        
        .search-container {
            display: flex;
            gap: 15px;
        }
        
        .search-container input {
            flex: 1;
            padding: 15px 20px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .search-container input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        .search-container button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .search-container button:hover {
            background: #2563eb;
        }
        
        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            gap: 30px;
        }
        
        /* Filters Section */
        .filters {
            width: 280px;
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            height: fit-content;
        }
        
        .filters h3 {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }
        
        .filter-group {
            margin-bottom: 25px;
        }
        
        .filter-group h4 {
            margin-bottom: 12px;
            color: #334155;
            font-size: 1.05rem;
        }
        
        .filter-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .filter-option {
            display: flex;
            align-items: center;
        }
        
        .filter-option input {
            margin-right: 10px;
            width: 18px;
            height: 18px;
        }
        
        .filter-option label {
            color: #475569;
            cursor: pointer;
        }
        
        .filter-option input:checked + label {
            color: #2563eb;
            font-weight: 500;
        }
        
        /* Job Listings */
        .job-listings {
            flex: 1;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .section-header h2 {
            color: #1e293b;
            font-size: 1.5rem;
        }
        
        .sort-select {
            padding: 8px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: white;
            color: #475569;
        }
        
        .job-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
            gap: 25px;
        }
        
        .job-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #e2e8f0;
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .job-header {
            padding: 25px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .company-logo {
            width: 60px;
            height: 60px;
            background: #f1f5f9;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 24px;
            color: #3b82f6;
        }
        
        .job-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .company-name {
            color: #64748b;
            font-size: 1rem;
            margin-bottom: 15px;
        }
        
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
        }
        
        .job-meta-item {
            display: flex;
            align-items: center;
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .job-meta-item i {
            margin-right: 6px;
            color: #94a3b8;
        }
        
        .job-body {
            padding: 20px 25px;
        }
        
        .job-description {
            color: #475569;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .job-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 25px;
        }
        
        .job-tag {
            background: #f1f5f9;
            color: #475569;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .job-footer {
            padding: 15px 25px;
            background: #f8fafc;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .job-salary {
            font-weight: 600;
            color: #10b981;
        }
        
        .apply-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .apply-btn:hover {
            background: #2563eb;
        }
        
        /* Footer */
        footer {
            background: #0f172a;
            color: #cbd5e1;
            padding: 50px 0 20px;
            margin-top: 60px;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
        }
        
        .footer-column h3 {
            color: white;
            margin-bottom: 20px;
            font-size: 1.2rem;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 12px;
        }
        
        .footer-links a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: #3b82f6;
        }
        
        .copyright {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid #1e293b;
            color: #94a3b8;
            font-size: 0.9rem;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .container {
                flex-direction: column;
            }
            
            .filters {
                width: 100%;
            }
            
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .search-container {
                flex-direction: column;
            }
            
            .job-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            nav ul {
                margin-top: 15px;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
        
        @media (max-width: 576px) {
            .footer-content {
                grid-template-columns: 1fr;
            }
            
            nav ul {
                flex-direction: column;
                gap: 10px;
            }
            
            nav li {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-briefcase"></i>
                CareerConnect
            </div>
            <nav>
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#" class="active">Jobs</a></li>
                    <li><a href="#">Companies</a></li>
                    <li><a href="#">Career Resources</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Search Section -->
    <div class="search-section">
        <div class="search-container">
            <input type="text" placeholder="Search for jobs, companies, or keywords...">
            <input type="text" placeholder="Location">
            <button>Search Jobs</button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Filters -->
        <div class="filters">
            <h3>Filters</h3>
            
            <div class="filter-group">
                <h4>Job Type</h4>
                <div class="filter-options">
                    <div class="filter-option">
                        <input type="checkbox" id="full-time" checked>
                        <label for="full-time">Full-time</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="part-time">
                        <label for="part-time">Part-time</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="contract">
                        <label for="contract">Contract</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="remote">
                        <label for="remote">Remote</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="internship">
                        <label for="internship">Internship</label>
                    </div>
                </div>
            </div>
            
            <div class="filter-group">
                <h4>Experience Level</h4>
                <div class="filter-options">
                    <div class="filter-option">
                        <input type="checkbox" id="entry-level" checked>
                        <label for="entry-level">Entry Level</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="mid-level">
                        <label for="mid-level">Mid Level</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="senior-level">
                        <label for="senior-level">Senior Level</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="manager">
                        <label for="manager">Manager</label>
                    </div>
                </div>
            </div>
            
            <div class="filter-group">
                <h4>Salary Range</h4>
                <div class="filter-options">
                    <div class="filter-option">
                        <input type="checkbox" id="salary1">
                        <label for="salary1">$30K - $50K</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="salary2" checked>
                        <label for="salary2">$50K - $80K</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="salary3">
                        <label for="salary3">$80K - $100K</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="salary4">
                        <label for="salary4">$100K+</label>
                    </div>
                </div>
            </div>
            
            <div class="filter-group">
                <h4>Industry</h4>
                <div class="filter-options">
                    <div class="filter-option">
                        <input type="checkbox" id="tech" checked>
                        <label for="tech">Technology</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="finance">
                        <label for="finance">Finance</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="healthcare">
                        <label for="healthcare">Healthcare</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="education">
                        <label for="education">Education</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="marketing">
                        <label for="marketing">Marketing</label>
                    </div>
                </div>
            </div>
            
            <button class="apply-btn" style="width: 100%; margin-top: 10px;">Apply Filters</button>
        </div>

        <!-- Job Listings -->
        <div class="job-listings">
            <div class="section-header">
                <h2>Latest Job Openings <span style="font-size: 1rem; color: #64748b;">(126 jobs found)</span></h2>
                <div>
                    <label>Sort by:</label>
                    <select class="sort-select">
                        <option>Most Recent</option>
                        <option>Salary: High to Low</option>
                        <option>Salary: Low to High</option>
                        <option>Alphabetical</option>
                    </select>
                </div>
            </div>
            
            <div class="job-grid">
                <!-- Job Card 1 -->
                <div class="job-card">
                    <div class="job-header">
                        <div class="company-logo">
                            <i class="fab fa-google"></i>
                        </div>
                        <h3 class="job-title">Senior Frontend Developer</h3>
                        <div class="company-name">Google Inc.</div>
                        <div class="job-meta">
                            <div class="job-meta-item">
                                <i class="fas fa-map-marker-alt"></i> Mountain View, CA
                            </div>
                            <div class="job-meta-item">
                                <i class="fas fa-clock"></i> Full-time
                            </div>
                        </div>
                    </div>
                    <div class="job-body">
                        <p class="job-description">
                            We're looking for an experienced frontend developer to join our team. You'll be responsible for building responsive web applications using React, TypeScript, and modern CSS.
                        </p>
                        <div class="job-tags">
                            <span class="job-tag">React</span>
                            <span class="job-tag">TypeScript</span>
                            <span class="job-tag">CSS3</span>
                        </div>
                    </div>
                    <div class="job-footer">
                        <div class="job-salary">$120,000 - $150,000</div>
                        <button class="apply-btn">Apply Now</button>
                    </div>
                </div>
                
                <!-- Job Card 2 -->
                <div class="job-card">
                    <div class="job-header">
                        <div class="company-logo">
                            <i class="fab fa-microsoft"></i>
                        </div>
                        <h3 class="job-title">UX/UI Designer</h3>
                        <div class="company-name">Microsoft</div>
                        <div class="job-meta">
                            <div class="job-meta-item">
                                <i class="fas fa-map-marker-alt"></i> Redmond, WA
                            </div>
                            <div class="job-meta-item">
                                <i class="fas fa-clock"></i> Full-time
                            </div>
                        </div>
                    </div>
                    <div class="job-body">
                        <p class="job-description">
                            Join our design team to create intuitive and beautiful user experiences for enterprise software. Proficiency in Figma, Adobe XD, and user research required.
                        </p>
                        <div class="job-tags">
                            <span class="job-tag">Figma</span>
                            <span class="job-tag">UI Design</span>
                            <span class="job-tag">User Research</span>
                        </div>
                    </div>
                    <div class="job-footer">
                        <div class="job-salary">$95,000 - $125,000</div>
                        <button class="apply-btn">Apply Now</button>
                    </div>
                </div>
                
                <!-- Job Card 3 -->
                <div class="job-card">
                    <div class="job-header">
                        <div class="company-logo">
                            <i class="fab fa-amazon"></i>
                        </div>
                        <h3 class="job-title">Data Scientist</h3>
                        <div class="company-name">Amazon</div>
                        <div class="job-meta">
                            <div class="job-meta-item">
                                <i class="fas fa-map-marker-alt"></i> Seattle, WA
                            </div>
                            <div class="job-meta-item">
                                <i class="fas fa-clock"></i> Full-time
                            </div>
                        </div>
                    </div>
                    <div class="job-body">
                        <p class="job-description">
                            Seeking a data scientist to analyze large datasets and build predictive models. Experience with Python, SQL, and machine learning frameworks required.
                        </p>
                        <div class="job-tags">
                            <span class="job-tag">Python</span>
                            <span class="job-tag">Machine Learning</span>
                            <span class="job-tag">SQL</span>
                        </div>
                    </div>
                    <div class="job-footer">
                        <div class="job-salary">$110,000 - $140,000</div>
                        <button class="apply-btn">Apply Now</button>
                    </div>
                </div>
                
                <!-- Job Card 4 -->
                <div class="job-card">
                    <div class="job-header">
                        <div class="company-logo">
                            <i class="fab fa-apple"></i>
                        </div>
                        <h3 class="job-title">iOS Developer</h3>
                        <div class="company-name">Apple</div>
                        <div class="job-meta">
                            <div class="job-meta-item">
                                <i class="fas fa-map-marker-alt"></i> Cupertino, CA
                            </div>
                            <div class="job-meta-item">
                                <i class="fas fa-clock"></i> Full-time
                            </div>
                        </div>
                    </div>
                    <div class="job-body">
                        <p class="job-description">
                            Develop cutting-edge iOS applications for Apple's ecosystem. Strong knowledge of Swift, UIKit, and Apple's design principles required.
                        </p>
                        <div class="job-tags">
                            <span class="job-tag">Swift</span>
                            <span class="job-tag">iOS</span>
                            <span class="job-tag">UIKit</span>
                        </div>
                    </div>
                    <div class="job-footer">
                        <div class="job-salary">$130,000 - $160,000</div>
                        <button class="apply-btn">Apply Now</button>
                    </div>
                </div>
                
                <!-- Job Card 5 -->
                <div class="job-card">
                    <div class="job-header">
                        <div class="company-logo">
                            <i class="fas fa-building"></i>
                        </div>
                        <h3 class="job-title">DevOps Engineer</h3>
                        <div class="company-name">Netflix</div>
                        <div class="job-meta">
                            <div class="job-meta-item">
                                <i class="fas fa-map-marker-alt"></i> Remote
                            </div>
                            <div class="job-meta-item">
                                <i class="fas fa-clock"></i> Full-time
                            </div>
                        </div>
                    </div>
                    <div class="job-body">
                        <p class="job-description">
                            We're looking for a DevOps engineer to manage our cloud infrastructure. Experience with AWS, Kubernetes, and CI/CD pipelines required.
                        </p>
                        <div class="job-tags">
                            <span class="job-tag">AWS</span>
                            <span class="job-tag">Kubernetes</span>
                            <span class="job-tag">CI/CD</span>
                        </div>
                    </div>
                    <div class="job-footer">
                        <div class="job-salary">$125,000 - $155,000</div>
                        <button class="apply-btn">Apply Now</button>
                    </div>
                </div>
                
                <!-- Job Card 6 -->
                <div class="job-card">
                    <div class="job-header">
                        <div class="company-logo">
                            <i class="fab fa-facebook"></i>
                        </div>
                        <h3 class="job-title">Product Manager</h3>
                        <div class="company-name">Meta</div>
                        <div class="job-meta">
                            <div class="job-meta-item">
                                <i class="fas fa-map-marker-alt"></i> Menlo Park, CA
                            </div>
                            <div class="job-meta-item">
                                <i class="fas fa-clock"></i> Full-time
                            </div>
                        </div>
                    </div>
                    <div class="job-body">
                        <p class="job-description">
                            Lead product development for our social media platforms. Experience in product strategy, user research, and cross-functional team leadership required.
                        </p>
                        <div class="job-tags">
                            <span class="job-tag">Product Strategy</span>
                            <span class="job-tag">Agile</span>
                            <span class="job-tag">User Research</span>
                        </div>
                    </div>
                    <div class="job-footer">
                        <div class="job-salary">$140,000 - $180,000</div>
                        <button class="apply-btn">Apply Now</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>CareerConnect</h3>
                <ul class="footer-links">
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">Press</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>For Job Seekers</h3>
                <ul class="footer-links">
                    <li><a href="#">Browse Jobs</a></li>
                    <li><a href="#">Salary Calculator</a></li>
                    <li><a href="#">Career Advice</a></li>
                    <li><a href="#">Resume Builder</a></li>
                    <li><a href="#">Job Alerts</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>For Employers</h3>
                <ul class="footer-links">
                    <li><a href="#">Post a Job</a></li>
                    <li><a href="#">Pricing Plans</a></li>
                    <li><a href="#">Recruiting Solutions</a></li>
                    <li><a href="#">HR Resources</a></li>
                    <li><a href="#">Talent Search</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Connect With Us</h3>
                <ul class="footer-links">
                    <li><a href="#"><i class="fab fa-facebook"></i> Facebook</a></li>
                    <li><a href="#"><i class="fab fa-twitter"></i> Twitter</a></li>
                    <li><a href="#"><i class="fab fa-linkedin"></i> LinkedIn</a></li>
                    <li><a href="#"><i class="fab fa-instagram"></i> Instagram</a></li>
                    <li><a href="#"><i class="fab fa-youtube"></i> YouTube</a></li>
                </ul>
            </div>
        </div>
        
        <div class="copyright">
            &copy; 2023 CareerConnect. All rights reserved.
        </div>
    </footer>
</body>
</html>