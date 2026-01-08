<?php
require_once 'config.php';
$page_title = "About Us";
include 'header.php';
?>
<style>
    .about-page {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 60px 0;
        min-height: calc(100vh - 140px);
    }
    
    .section-header {
        text-align: center;
        margin-bottom: 50px;
    }
    
    .section-header h2 {
        font-size: 2.8rem;
        font-weight: 700;
        margin-bottom: 20px;
        background: linear-gradient(135deg, #2575fc 0%, #6a11cb 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        position: relative;
        display: inline-block;
    }
    
    .section-header h2:after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: linear-gradient(135deg, #2575fc 0%, #6a11cb 100%);
        border-radius: 2px;
    }
    
    .section-header p {
        font-size: 1.2rem;
        color: #555;
        max-width: 700px;
        margin: 0 auto;
    }
    
    .mission-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        padding: 40px;
        margin-bottom: 50px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-left: 5px solid #2575fc;
    }
    
    .mission-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    }
    
    .mission-card h3 {
        color: #2c3e50;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
    }
    
    .mission-card h3 i {
        background: linear-gradient(135deg, #2575fc 0%, #6a11cb 100%);
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        color: white;
        font-size: 1.4rem;
    }
    
    .features-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-bottom: 60px;
    }
    
    .feature-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        padding: 30px;
        text-align: center;
        transition: all 0.3s ease;
        border-top: 4px solid #6a11cb;
    }
    
    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    }
    
    .feature-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #2575fc 0%, #6a11cb 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: white;
        font-size: 2rem;
    }
    
    .feature-card h4 {
        color: #2c3e50;
        margin-bottom: 15px;
        font-size: 1.4rem;
    }
    
    .stats-container {
        background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
        border-radius: 15px;
        padding: 50px 30px;
        margin-bottom: 60px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 30px;
        text-align: center;
    }
    
    .stat-item {
        color: white;
    }
    
    .stat-number {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .stat-label {
        font-size: 1.1rem;
        opacity: 0.9;
    }
    
    .team-section {
        margin-bottom: 60px;
    }
    
    .team-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
    }
    
    .team-member {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }
    
    .team-member:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    }
    
    .member-img {
        height: 250px;
        background: linear-gradient(135deg, #2575fc 0%, #6a11cb 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 5rem;
    }
    
    .member-info {
        padding: 25px;
        text-align: center;
    }
    
    .member-info h4 {
        color: #2c3e50;
        margin-bottom: 5px;
    }
    
    .member-info p {
        color: #6a11cb;
        font-weight: 500;
        margin-bottom: 15px;
    }
    
    .cta-section {
        background: white;
        border-radius: 15px;
        padding: 50px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }
    
    .cta-section h3 {
        font-size: 2.2rem;
        margin-bottom: 20px;
        color: #2c3e50;
    }
    
    .cta-section p {
        font-size: 1.2rem;
        color: #555;
        max-width: 700px;
        margin: 0 auto 30px;
    }
    
    .cta-btn {
        background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
        color: white;
        border: none;
        padding: 15px 40px;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(106, 17, 203, 0.3);
    }
    
    .cta-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 7px 20px rgba(106, 17, 203, 0.4);
    }
</style>
<main>
<div class="about-page">
    <div class="container">
        <div class="section-header">
            <h2>About Employee Portal</h2>
            <p>We're revolutionizing the way job seekers connect with employers through our comprehensive job management system</p>
        </div>
        
        <div class="mission-card">
            <h3><i class="fas fa-bullseye"></i> Our Mission</h3>
            <p>At Employee Portal, we're dedicated to transforming the employment landscape by creating meaningful connections between talented professionals and forward-thinking employers. Our mission is to empower individuals to build fulfilling careers while helping organizations find the perfect talent to drive their success.</p>
            <p>We believe that the right job can change a life, and the right employee can transform a business. That's why we've built a platform that goes beyond traditional job boards - we're creating a community where careers are nurtured, skills are developed, and opportunities are limitless.</p>
        </div>
        
        <div class="section-header">
            <h2>Why Choose Us</h2>
            <p>Discover the features that make Employee Portal the preferred choice for job seekers and employers</p>
        </div>
        
        <div class="features-container">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h4>Advanced Job Search</h4>
                <p>Find your dream job with our intelligent search filters, personalized recommendations, and location-based opportunities.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h4>Resume Builder</h4>
                <p>Create professional resumes with our easy-to-use builder that highlights your skills and achievements effectively.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h4>Application Tracking</h4>
                <p>Monitor your job applications in real-time with our intuitive tracking system that keeps you informed at every step.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-star"></i>
                </div>
                <h4>Company Reviews</h4>
                <p>Make informed decisions with authentic company reviews and ratings from current and former employees.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h4>Career Resources</h4>
                <p>Access our extensive library of career advice, interview tips, and professional development resources.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <h4>Smart Alerts</h4>
                <p>Get instant notifications when new jobs matching your profile become available.</p>
            </div>
        </div>
        
        <div class="stats-container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">50,000+</div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">12,000+</div>
                    <div class="stat-label">Companies Registered</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">85%</div>
                    <div class="stat-label">Success Rate</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">120+</div>
                    <div class="stat-label">Countries Served</div>
                </div>
            </div>
        </div>
        
        <div class="section-header">
            <h2>Our Leadership Team</h2>
            <p>The dedicated professionals driving our mission forward</p>
        </div>
        
        <div class="team-section">
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-img">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="member-info">
                        <h4>Sarah Johnson</h4>
                        <p>CEO & Founder</p>
                        <p>15+ years in HR technology</p>
                    </div>
                </div>
                
                <div class="team-member">
                    <div class="member-img">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="member-info">
                        <h4>Michael Chen</h4>
                        <p>CTO</p>
                        <p>AI & Machine Learning expert</p>
                    </div>
                </div>
                
                <div class="team-member">
                    <div class="member-img">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="member-info">
                        <h4>Priya Sharma</h4>
                        <p>Head of Product</p>
                        <p>User experience specialist</p>
                    </div>
                </div>
                
                <div class="team-member">
                    <div class="member-img">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="member-info">
                        <h4>David Wilson</h4>
                        <p>Head of Partnerships</p>
                        <p>Industry relationship builder</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="cta-section">
            <h3>Ready to Transform Your Career?</h3>
            <p>Join thousands of professionals who have found their dream jobs through Employee Portal. Create your profile today and take the first step toward your ideal career.</p>
            <a href="signup.php" class="cta-btn" style="text-decoration: none; display: inline-block;">
                Get Started Now
            </a>
        </div>
    </div>
</div>
</main>
<?php include 'footer.php'; ?>