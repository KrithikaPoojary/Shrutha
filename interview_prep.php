<?php
require_once 'config.php';
$page_title = "Interview Preparation";
include 'header.php';
?>

<style>
.interview-type-card {
    transition: all 0.3s ease;
    border: none;
    border-radius: 10px;
    margin-bottom: 1rem;
    cursor: pointer;
}

.interview-type-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.star-method {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 10px;
    margin: 1rem 0;
}

.star-letter {
    font-size: 1.5rem;
    font-weight: bold;
    background: rgba(255,255,255,0.2);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}

.timeline-item {
    border-left: 3px solid #007bff;
    padding-left: 1.4rem;
    margin-bottom: 2rem;
    position: relative;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -8px;
    top: 0;
    width: 13px;
    height: 13px;
    border-radius: 50%;
    background: #007bff;
}

.question-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin: 0.5rem 0;
    border-left: 4px solid #28a745;
}

.practice-btn {
    background: linear-gradient(45deg, #FF6B6B, #FF8E53);
    border: none;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    transition: all 0.3s ease;
}

.practice-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(255,107,107,0.4);
}

.additional-tip-card {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1rem;
}

.quick-tip-item {
    background: rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 1rem;
    margin: 0.5rem 0;
    border-left: 4px solid #ffd700;
}
</style>

<div class="container py-4">
    <a href="resources.php" class="btn btn-outline-secondary" style="text-decoration: none; position: absolute; left: 40px; box-shadow: none; margin-top: 8px;">
        <i class="fas fa-arrow-left me-2"></i>
    </a>
    <h1 class="mb-4">Interview Preparation Guide</h1>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card interview-type-card">
                <div class="card-body">
                    <h3 class="card-title text-primary">Comprehensive Interview Preparation</h3>
                    
                    <div class="mt-4">
                        <h5 class="text-success">📅 Before the Interview</h5>
                        <div class="timeline-item">
                            <ul>
                                <li><strong>Research the company:</strong> Mission, values, products, recent news</li>
                                <li><strong>Understand the role:</strong> Job description, required skills, responsibilities</li>
                                <li><strong>Practice common questions:</strong> Prepare and rehearse answers</li>
                                <li><strong>Prepare questions to ask:</strong> Show your interest and engagement</li>
                                <li><strong>Plan your route:</strong> Arrive 10-15 minutes early</li>
                                <li><strong>Dress appropriately:</strong> Professional attire</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5 class="text-success">❓ Common Interview Questions by Experience Level</h5>
                        
                        <div class="mb-3">
                            <h6 class="text-info">For Freshers/Entry Level:</h6>
                            <div class="question-card">
                                <ul class="mb-0">
                                    <li>"Tell me about yourself and your background"</li>
                                    <li>"Why did you choose your field of study?"</li>
                                    <li>"What are your strengths and weaknesses?"</li>
                                    <li>"Where do you see yourself in 5 years?"</li>
                                    <li>"Why do you want to work for our company?"</li>
                                    <li>"Tell me about your academic projects"</li>
                                </ul>
                            </div>
                        </div>

                        <div class="mb-3">
                            <h6 class="text-info">For Intermediate Level:</h6>
                            <div class="question-card">
                                <ul class="mb-0">
                                    <li>"Walk me through your experience and accomplishments"</li>
                                    <li>"Tell me about a challenging project you worked on"</li>
                                    <li>"How do you handle conflict in the workplace?"</li>
                                    <li>"What is your leadership style?"</li>
                                    <li>"How do you prioritize your work?"</li>
                                    <li>"Why are you looking to leave your current position?"</li>
                                </ul>
                            </div>
                        </div>

                        <div class="mb-3">
                            <h6 class="text-info">For Senior/Experienced Level:</h6>
                            <div class="question-card">
                                <ul class="mb-0">
                                    <li>"What is your management philosophy?"</li>
                                    <li>"How do you drive business results?"</li>
                                    <li>"Tell me about a time you led organizational change"</li>
                                    <li>"How do you develop and mentor your team?"</li>
                                    <li>"What is your approach to strategic planning?"</li>
                                    <li>"How do you handle budget management?"</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5 class="text-success">⭐ Behavioral Questions (STAR Method)</h5>
                        <div class="star-method">
                            <p class="fw-bold mb-3">Use the STAR method to structure your answers:</p>
                            <div class="d-flex align-items-center mb-2">
                                <span class="star-letter">S</span>
                                <span><strong>Situation:</strong> Describe the context</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <span class="star-letter">T</span>
                                <span><strong>Task:</strong> Explain what needed to be done</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <span class="star-letter">A</span>
                                <span><strong>Action:</strong> Describe what you did</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="star-letter">R</span>
                                <span><strong>Result:</strong> Share the outcome and what you learned</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5 class="text-success">🎯 During the Interview</h5>
                        <div class="timeline-item">
                            <ul>
                                <li>Maintain eye contact and positive body language</li>
                                <li>Listen carefully before answering</li>
                                <li>Be concise but thorough in your responses</li>
                                <li>Show enthusiasm and interest</li>
                                <li>Ask thoughtful questions</li>
                                <li>Be honest and authentic</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5 class="text-success">📧 After the Interview</h5>
                        <div class="timeline-item">
                            <ul>
                                <li>Send a thank-you email within 24 hours</li>
                                <li>Follow up if you haven't heard back</li>
                                <li>Reflect on what went well and what could improve</li>
                                <li>Continue your job search while waiting</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card interview-type-card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">🎯 Interview Types</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="phone_interview.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center interview-type-item">
                            <div>
                                <i class="fas fa-phone me-2 text-primary"></i>
                                Phone Screening
                            </div>
                            <span class="badge bg-primary rounded-pill">15 min</span>
                        </a>
                        <a href="video_interview.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center interview-type-item">
                            <div>
                                <i class="fas fa-video me-2 text-success"></i>
                                Video Interviews
                            </div>
                            <span class="badge bg-success rounded-pill">30 min</span>
                        </a>
                        <a href="technical_interview.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center interview-type-item">
                            <div>
                                <i class="fas fa-code me-2 text-warning"></i>
                                Technical Interviews
                            </div>
                            <span class="badge bg-warning rounded-pill">60 min</span>
                        </a>
                        <a href="panel_interview.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center interview-type-item">
                            <div>
                                <i class="fas fa-users me-2 text-info"></i>
                                Panel Interviews
                            </div>
                            <span class="badge bg-info rounded-pill">45 min</span>
                        </a>
                        <a href="assessment_center.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center interview-type-item">
                            <div>
                                <i class="fas fa-clipboard-check me-2 text-danger"></i>
                                Assessment Centers
                            </div>
                            <span class="badge bg-danger rounded-pill">Half Day</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Additional Tips Section (Replaces Mock Interviews) -->
            <div class="card interview-type-card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">💡 Quick Interview Tips</h5>
                </div>
                <div class="card-body">
                    <div class="quick-tip-item">
                        <h6><i class="fas fa-brain me-2"></i>Mindset Preparation</h6>
                        <p class="mb-0">Visualize success and practice positive self-talk before the interview.</p>
                    </div>
                    <div class="quick-tip-item">
                        <h6><i class="fas fa-stopwatch me-2"></i>Time Management</h6>
                        <p class="mb-0">Keep answers concise - aim for 1-2 minutes per response unless asked for more detail.</p>
                    </div>
                    <div class="quick-tip-item">
                        <h6><i class="fas fa-list-alt me-2"></i>Question Preparation</h6>
                        <p class="mb-0">Prepare 3-5 thoughtful questions about the role, team, and company culture.</p>
                    </div>
                    <div class="quick-tip-item">
                        <h6><i class="fas fa-pause-circle me-2"></i>Active Listening</h6>
                        <p class="mb-0">Pause briefly before answering to collect your thoughts and show you're considering the question.</p>
                    </div>
                </div>
            </div>

            <!-- Body Language Tips (Replaces Interview Preparation Progress) -->
            <div class="card interview-type-card">
                <div class="card-header bg-warning text-white">
                    <h5 class="card-title mb-0">👤 Body Language Guide</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-success">✅ Do:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>Maintain eye contact (60-70%)</li>
                            <li><i class="fas fa-check text-success me-2"></i>Sit up straight with open posture</li>
                            <li><i class="fas fa-check text-success me-2"></i>Use natural hand gestures</li>
                            <li><i class="fas fa-check text-success me-2"></i>Smile and nod appropriately</li>
                            <li><i class="fas fa-check text-success me-2"></i>Lean slightly forward to show interest</li>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-danger">❌ Don't:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-times text-danger me-2"></i>Cross arms (appears defensive)</li>
                            <li><i class="fas fa-times text-danger me-2"></i>Fidget or play with objects</li>
                            <li><i class="fas fa-times text-danger me-2"></i>Slouch or lean back too much</li>
                            <li><i class="fas fa-times text-danger me-2"></i>Avoid eye contact completely</li>
                            <li><i class="fas fa-times text-danger me-2"></i>Check phone or watch frequently</li>
                        </ul>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Interview type selection
    const interviewTypeItems = document.querySelectorAll('.interview-type-item');
    interviewTypeItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const interviewType = this.querySelector('div').textContent.trim();
            console.log(`Selected interview type: ${interviewType}`);
            // Here you can redirect or show specific content
            alert(`Preparing ${interviewType} content...`);
        });
    });

    // Question card interaction
    const questionCards = document.querySelectorAll('.question-card');
    questionCards.forEach(card => {
        card.addEventListener('click', function() {
            this.style.transform = 'scale(1.02)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 200);
        });
    });

    // Quick tip interaction
    const quickTips = document.querySelectorAll('.quick-tip-item');
    quickTips.forEach(tip => {
        tip.addEventListener('click', function() {
            this.style.transform = 'scale(1.02)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 200);
        });
    });
});

function startPractice() {
    // Start practice session
    const practiceSession = {
        type: 'interview_prep',
        startTime: new Date(),
        questions: []
    };
    
    console.log('Starting practice session:', practiceSession);
    alert('Practice session started! You will be redirected to the practice area.');
    window.location.href = 'interview_practice.php';
}
</script>

<?php include 'footer.php'; ?>