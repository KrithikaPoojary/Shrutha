<?php
require_once 'config.php';
$page_title = "Career Advice";
include 'header.php';
?>

<style>
.career-phase {
    border-radius: 15px;
    padding: 2rem;
    margin: 1.5rem 0;
    color: white;
    position: relative;
    overflow: hidden;
    transition: transform 0.3s ease;
}

.career-phase::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.1);
    z-index: 1;
}

.career-phase > * {
    position: relative;
    z-index: 2;
}

.career-phase:hover {
    transform: translateY(-5px);
}

.early-career {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.mid-career {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.senior-career {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.skill-pill {
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    margin: 0.25rem;
    display: inline-block;
    transition: all 0.3s ease;
}

.skill-pill:hover {
    background: rgba(255,255,255,0.3);
    transform: scale(1.05);
}

.network-stats {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1.5rem;
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #007bff;
}

.career-timeline {
    position: relative;
    padding-left: 2rem;
}

.career-timeline::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: #007bff;
}

.resource-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: none;
    border-radius: 10px;
    overflow: hidden;
}

.resource-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}
</style>

<div class="container py-4">
    <a href="resources.php" class="btn btn-outline-secondary" style="text-decoration: none; position: absolute; left: 40px; box-shadow: none; margin-top: 8px;">
        <i class="fas fa-arrow-left me-2"></i>
    </a>
    <h1 class="mb-4">Career Development Advice</h1>
    
    <div class="row">
        <div class="col-12">
            <div class="card resource-card">
                <div class="card-body">
                    <h3 class="card-title text-primary">Professional Career Guidance</h3>
                    
                    <div class="mt-4">
                        <h5 class="text-success">📈 Career Planning by Experience Level</h5>
                        
                        <div class="career-phase early-career">
                            <h6>🚀 Early Career (0-5 years):</h6>
                            <ul>
                                <li><strong>Focus on learning:</strong> Absorb as much knowledge as possible</li>
                                <li><strong>Build your network:</strong> Connect with colleagues and mentors</li>
                                <li><strong>Develop core skills:</strong> Master the fundamentals of your field</li>
                                <li><strong>Seek feedback:</strong> Regularly ask for performance input</li>
                                <li><strong>Explore different roles:</strong> Understand what you enjoy</li>
                                <li><strong>Set short-term goals:</strong> 1-2 year career objectives</li>
                                <li><strong>Find a mentor:</strong> Learn from experienced professionals</li>
                            </ul>
                            <div class="mt-3">
                                <span class="skill-pill">Learning</span>
                                <span class="skill-pill">Networking</span>
                                <span class="skill-pill">Feedback</span>
                                <span class="skill-pill">Exploration</span>
                            </div>
                        </div>

                        <div class="career-phase mid-career">
                            <h6>⚡ Mid-Career (5-15 years):</h6>
                            <ul>
                                <li><strong>Specialize or generalize:</strong> Decide on depth vs breadth</li>
                                <li><strong>Develop leadership skills:</strong> Even if not in management</li>
                                <li><strong>Build your personal brand:</strong> Establish expertise</li>
                                <li><strong>Consider advanced education:</strong> Certifications, degrees</li>
                                <li><strong>Mentor others:</strong> Share your knowledge</li>
                                <li><strong>Network strategically:</strong> Build meaningful connections</li>
                                <li><strong>Plan for advancement:</strong> Next career moves</li>
                            </ul>
                            <div class="mt-3">
                                <span class="skill-pill">Specialization</span>
                                <span class="skill-pill">Leadership</span>
                                <span class="skill-pill">Brand Building</span>
                                <span class="skill-pill">Mentoring</span>
                            </div>
                        </div>

                        <div class="career-phase senior-career">
                            <h6>🎯 Senior Level (15+ years):</h6>
                            <ul>
                                <li><strong>Executive presence:</strong> Develop strategic thinking</li>
                                <li><strong>Thought leadership:</strong> Contribute to your industry</li>
                                <li><strong>Succession planning:</strong> Develop future leaders</li>
                                <li><strong>Board positions:</strong> Expand your influence</li>
                                <li><strong>Legacy building:</strong> Long-term impact</li>
                                <li><strong>Work-life integration:</strong> Balance and fulfillment</li>
                                <li><strong>Planning for transition:</strong> Next career chapter</li>
                            </ul>
                            <div class="mt-3">
                                <span class="skill-pill">Strategy</span>
                                <span class="skill-pill">Leadership</span>
                                <span class="skill-pill">Legacy</span>
                                <span class="skill-pill">Transition</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5 class="text-success">🛠️ Skill Development Strategies</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">Continuous Learning</h6>
                                        <p class="card-text">Stay updated with industry trends and technologies through online courses, workshops, and professional development programs.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">Cross-functional Skills</h6>
                                        <p class="card-text">Develop adjacent capabilities for versatility and better collaboration across different departments and teams.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mt-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">Soft Skills Enhancement</h6>
                                        <p class="card-text">Improve communication, leadership, emotional intelligence, and other interpersonal skills crucial for career growth.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mt-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">Technical Proficiency</h6>
                                        <p class="card-text">Keep up with technological changes and industry-specific tools to maintain competitive advantage.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5 class="text-success">🤝 Networking & Relationship Building</h5>
                        <div class="network-stats">
                            <div class="row">
                                <div class="col-4">
                                    <div class="stat-number">85%</div>
                                    <small>Jobs through networking</small>
                                </div>
                                <div class="col-4">
                                    <div class="stat-number">3-5x</div>
                                    <small>More opportunities</small>
                                </div>
                                <div class="col-4">
                                    <div class="stat-number">70%</div>
                                    <small>Career growth</small>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <ul>
                                <li><strong>Attend industry conferences and events:</strong> Stay visible and make new connections</li>
                                <li><strong>Join professional associations:</strong> Access to exclusive networks and resources</li>
                                <li><strong>Participate in online professional communities:</strong> LinkedIn groups, industry forums</li>
                                <li><strong>Schedule regular coffee meetings:</strong> Maintain existing relationships</li>
                                <li><strong>Offer value before asking for help:</strong> Build genuine relationships</li>
                                <li><strong>Maintain your network consistently:</strong> Regular follow-ups and updates</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5 class="text-success">⚖️ Work-Life Balance</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">Set Clear Boundaries</h6>
                                        <p class="card-text">Establish clear boundaries between work and personal life to prevent burnout and maintain productivity.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">Health & Well-being</h6>
                                        <p class="card-text">Prioritize physical and mental health through regular exercise, proper nutrition, and stress management.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5 class="text-success">🔄 Career Transition Tips</h5>
                        <div class="card">
                            <div class="card-body">
                                <ul>
                                    <li><strong>Research target industries thoroughly:</strong> Understand market trends and requirements</li>
                                    <li><strong>Identify transferable skills:</strong> Map your current skills to new opportunities</li>
                                    <li><strong>Network with people in target field:</strong> Gain insights and referrals</li>
                                    <li><strong>Consider interim steps or bridge roles:</strong> Smooth transition path</li>
                                    <li><strong>Update your personal brand and materials:</strong> Resume, LinkedIn, portfolio</li>
                                    <li><strong>Prepare for potential salary adjustments:</strong> Financial planning for transition</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-center">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title">Need Personalized Career Advice?</h5>
                                <p class="card-text">Get one-on-one guidance from our experienced career counselors.</p>
                                <a href="contact.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-tie me-2"></i>Schedule Career Consultation
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Career phase interaction
    const careerPhases = document.querySelectorAll('.career-phase');
    careerPhases.forEach(phase => {
        phase.addEventListener('click', function() {
            const phaseTitle = this.querySelector('h6').textContent;
            console.log(`Selected career phase: ${phaseTitle}`);
            // Show detailed information about this career phase
            showCareerPhaseDetails(phaseTitle);
        });
    });

    // Skill pills interaction
    const skillPills = document.querySelectorAll('.skill-pill');
    skillPills.forEach(pill => {
        pill.addEventListener('click', function(e) {
            e.stopPropagation();
            const skill = this.textContent.trim();
            console.log(`Selected skill: ${skill}`);
            // Show resources for this specific skill
            showSkillResources(skill);
        });
    });
});

function showCareerPhaseDetails(phaseTitle) {
    // This would typically show a modal with detailed information
    console.log(`Showing details for: ${phaseTitle}`);
    // For now, we'll just show an alert
    alert(`Loading detailed information for: ${phaseTitle}`);
}

function showSkillResources(skill) {
    console.log(`Showing resources for skill: ${skill}`);
    // This would filter and show relevant resources
    alert(`Finding resources for: ${skill}`);
}
</script>

<?php include 'footer.php'; ?>