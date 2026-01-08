    <style>
        /* Footer */
        .footer-links a {
        position: relative;
        z-index: 10;
        pointer-events: auto;
        }
        
        .footer-links li {
            position: relative;
            z-index: 10;
        }
        
        .footer {
            background: linear-gradient(to right, var(--dark) 0%, #0f172a 100%);
            color: var(--white);
            padding-top: 100px;
            padding-bottom: 40px;
            padding-left: 100px;
            padding-right: 100px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%231e293b' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.1;
        }

        .footer-content {
            position: relative;
            z-index: 2;
        }

        .footer-logo {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-logo i {
            color: var(--white);
            background: rgba(255,255,255,0.15);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .footer-text {
            color: rgba(255,255,255,0.7);
            margin-bottom: 30px;
            max-width: 400px;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .social-links a {
            position: relative;
            z-index: 10;
            pointer-events: auto !important;
            text-decoration: none;
        }

        .social-links {
            position: relative;
            z-index: 10;
        }

        .social-links a:hover {
            
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(67, 97, 238, 0.3);
        }
        .social-links a i {
            color: var(--white);
            background: rgba(255,255,255,0.15);
            width: 40px;
            height: 40px;
            border-radius: 10%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .footer-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 25px;
            color: var(--white);
            position: relative;
            padding-bottom: 15px;
        }

        .footer-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--primary);
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 15px;
        }

        .footer-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-links a:hover {
            color: var(--white);
            transform: translateX(5px);
        }

        .footer-links a i {
            color: var(--primary);
        }

        .contact-info {
            list-style: none;
            padding: 0;
        }

        .contact-info li {
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            color: rgba(255,255,255,0.7);
        }

        .contact-info i {
            color: var(--primary);
            font-size: 1.2rem;
            margin-top: 5px;
        }

        .copyright {
            text-align: center;
            padding-top: 40px;
            color: rgba(255,255,255,0.5);
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 60px;
        }
    </style>
    <footer class="footer">
    <div class="container-fluid">
        <div class="row g-5">
            <div class="col-lg-4">
                <div class="footer-logo">
                    <i class="fas fa-briefcase"></i> EmployeePortal
                </div>
                <p class="footer-text">Connecting talented professionals with top employers to build successful careers and thriving organizations.</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/share/1MiQLLNC4z/" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.linkedin.com/company/abhimo-technologies-private-limited/" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                    <a href="https://www.instagram.com/abhimo_technologies?igsh=Mm90N2N1bWY4OGcy" target="_blank"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <h5 class="footer-title">Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                    <li><a href="about.php"><i class="fas fa-chevron-right"></i> About Us</a></li>
                    <li><a href="jobs.php"><i class="fas fa-chevron-right"></i> Job Listings</a></li>
                    <li><a href="resources.php"><i class="fas fa-chevron-right"></i> Resources</a></li>
                    <li><a href="contact.php"><i class="fas fa-chevron-right"></i> Contact</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h5 class="footer-title">Resources</h5>
                <ul class="footer-links">
                    <li><a href="resume_tips.php"><i class="fas fa-chevron-right"></i> Resume Tips</a></li>
                    <li><a href="interview_prep.php"><i class="fas fa-chevron-right"></i> Interview Preparation</a></li>
                    <li><a href="career_advice.php"><i class="fas fa-chevron-right"></i> Career Advice</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h5 class="footer-title">Contact Us</h5>
                <ul class="contact-info">
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <span>F07, D.No 2-11/26(27), "Green City", Behind Naganakatte, N.H.66, Thokottu, Mangaluru, Karnataka-575017.</span>
                    </li>
                    <li>
                        <i class="fas fa-phone-alt"></i>
                        <span>078297 38999</span>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <span>naveennayak.i@abhimo.com</span>
                    </li>
                    <li>
                        <i class="fas fa-clock"></i>
                        <span>Mon-Fri: 9AM - 6PM</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="copyright">
            <p>&copy; 2023 Employee Portal. All rights reserved. Designed with <i class="fas fa-heart text-danger"></i> for job seekers</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Simple fade-in animation on scroll
    document.addEventListener('DOMContentLoaded', function() {
        const fadeElements = document.querySelectorAll('.fade-in');
        
        const fadeInOnScroll = function() {
            fadeElements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const elementVisible = 150;
                
                if (elementTop < window.innerHeight - elementVisible) {
                    element.style.opacity = "1";
                    element.style.transform = "translateY(0)";
                }
            });
        };
        
        // Set initial state
        fadeElements.forEach(element => {
            element.style.opacity = "0";
            element.style.transform = "translateY(20px)";
            element.style.transition = "opacity 0.6s ease, transform 0.6s ease";
        });
        
        // Check on scroll
        window.addEventListener('scroll', fadeInOnScroll);
        fadeInOnScroll(); // Initialize
        
        // Create background particles
        createParticles();
        
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            if (!particlesContainer) return; // Prevents errors if the element doesn't exist

            const particlesCount = 30;
            
            for (let i = 0; i < particlesCount; i++) {
                const particle = document.createElement('div');
                particle.style.position = 'absolute';
                particle.style.width = `${Math.random() * 5 + 2}px`;
                particle.style.height = particle.style.width;
                particle.style.backgroundColor = '#4361ee';
                particle.style.borderRadius = '50%';
                particle.style.opacity = Math.random() * 0.5 + 0.2;
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                
                // Random animation properties
                const tx = (Math.random() - 0.5) * 200;
                const ty = (Math.random() - 0.5) * 200;
                const duration = Math.random() * 20 + 10;
                
                particle.style.setProperty('--tx', `${tx}px`);
                particle.style.setProperty('--ty', `${ty}px`);
                particle.style.animation = `particle ${duration}s linear infinite`;
                
                particlesContainer.appendChild(particle);
            }
        }
    });
</script>
</body>
</html>