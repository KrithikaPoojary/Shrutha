<?php
require_once 'config.php';
$page_title = "Resume Tips";
include 'header.php';
?>

<style>
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

.qualification-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 10px;
    margin: 2rem 0;
}

.qualification-section h6 {
    color: #ffd700;
    font-weight: bold;
}

.tip-item {
    background: #f8f9fa;
    border-left: 4px solid #007bff;
    padding: 1rem;
    margin: 0.5rem 0;
    border-radius: 0 5px 5px 0;
}

.action-verbs {
    background: linear-gradient(45deg, #28a745, #20c997);
    color: white;
    padding: 1.5rem;
    border-radius: 10px;
    text-align: center;
}

.quick-resource-item {
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
    margin-bottom: 0.5rem;
    cursor: pointer;
}

.quick-resource-item:hover {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.badge-qualification {
    background: #6c757d;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 15px;
    font-size: 0.75rem;
    margin-left: 0.5rem;
}

.modal-content {
    border-radius: 10px;
    border: none;
}

.modal-header {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    border-radius: 10px 10px 0 0;
}

.modal-footer {
    border-top: 1px solid #dee2e6;
    border-radius: 0 0 10px 10px;
}
</style>

<div class="container py-4">
    <a href="resources.php" class="btn btn-outline-secondary" style="text-decoration: none; position: absolute; left: 40px; box-shadow: none; margin-top: 8px;">
        <i class="fas fa-arrow-left me-2"></i>
    </a>
    <h1 class="mb-4">Resume Writing Tips</h1>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card resource-card">
                <div class="card-body">
                    <h3 class="card-title text-primary">Professional Resume Guidelines</h3>
                    
                    <div class="mt-4">
                        <h5 class="text-success">📝 Formatting & Structure</h5>
                        <div class="tip-item">
                            <ul class="mb-0">
                                <li>Keep your resume clean and well-organized</li>
                                <li>Use professional fonts (Arial, Calibri, Times New Roman)</li>
                                <li>Maintain consistent formatting throughout</li>
                                <li>Keep it to 1-2 pages maximum</li>
                                <li>Use bullet points for readability</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5 class="text-success">📋 Essential Sections</h5>
                        <div class="tip-item">
                            <ul class="mb-0">
                                <li><strong>Contact Information:</strong> Name, phone, email, LinkedIn</li>
                                <li><strong>Professional Summary:</strong> 2-3 sentence overview</li>
                                <li><strong>Work Experience:</strong> Reverse chronological order</li>
                                <li><strong>Education:</strong> Degrees, certifications, training</li>
                                <li><strong>Skills:</strong> Technical and soft skills</li>
                                <li><strong>Achievements:</strong> Awards, recognitions</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5 class="text-success">🎯 Qualification-Based Tips</h5>
                        
                        <div class="qualification-section">
                            <h6>For Freshers/Entry Level (0-2 years):</h6>
                            <ul>
                                <li>Emphasize education and academic projects</li>
                                <li>Include internships and training programs</li>
                                <li>Highlight relevant coursework</li>
                                <li>Showcase extracurricular activities and leadership roles</li>
                                <li>Include technical skills and certifications</li>
                            </ul>
                        </div>

                        <div class="qualification-section mt-3" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <h6>For Intermediate (2-5 years):</h6>
                            <ul>
                                <li>Focus on work experience and accomplishments</li>
                                <li>Use quantifiable achievements</li>
                                <li>Highlight project leadership and initiatives</li>
                                <li>Show career progression</li>
                                <li>Include professional development activities</li>
                            </ul>
                        </div>

                        <div class="qualification-section mt-3" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <h6>For Experienced (5+ years):</h6>
                            <ul>
                                <li>Emphasize leadership and management experience</li>
                                <li>Show strategic impact and business results</li>
                                <li>Include budget and team management</li>
                                <li>Highlight industry expertise</li>
                                <li>Focus on achievements rather than duties</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5 class="text-success">🚀 Action Verbs to Use</h5>
                        <div class="action-verbs">
                            <p class="mb-0 fw-bold">Managed • Developed • Implemented • Led • Created • Improved • Organized • 
                            Coordinated • Analyzed • Designed • Built • Increased • Reduced • Streamlined</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5 class="text-success">⚠️ Common Mistakes to Avoid</h5>
                        <div class="tip-item">
                            <ul class="mb-0">
                                <li>Spelling and grammatical errors</li>
                                <li>Using unprofessional email addresses</li>
                                <li>Including irrelevant personal information</li>
                                <li>Being too vague or generic</li>
                                <li>Using passive language</li>
                                <li>Not tailoring for specific jobs</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card resource-card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">🚀 Quick Resources</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item list-group-item-action quick-resource-item d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#resumeTemplatesModal">
                            Resume Templates
                    
                        </div>
                        <div class="list-group-item list-group-item-action quick-resource-item d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#atsTipsModal">
                            ATS-Friendly Tips
                            
                        </div>
                        <div class="list-group-item list-group-item-action quick-resource-item" data-bs-toggle="modal" data-bs-target="#coverLetterModal">
                            Cover Letter Guide
                        </div>
                        <!-- <div class="list-group-item list-group-item-action quick-resource-item d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#resumeReviewModal">
                            Resume Review Service
                            <span class="badge bg-warning rounded-pill">Hot</span>
                        </div>
                        <div class="list-group-item list-group-item-action quick-resource-item" data-bs-toggle="modal" data-bs-target="#resumeExamplesModal">
                            Resume Examples by Industry
                        </div> -->
                        <div class="list-group-item list-group-item-action quick-resource-item" data-bs-toggle="modal" data-bs-target="#resumeChecklistModal">
                            Resume Checklist
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card resource-card mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-file-alt fa-3x text-primary"></i>
                    </div>
                    <h5 class="card-title">Need Professional Help?</h5>
                    <p class="card-text">Get your resume professionally reviewed by our career experts.</p>
                    <a href="contact.php" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-envelope me-2"></i>Contact Career Services
                    </a>
                </div>
            </div>

            <div class="card resource-card">
                <div class="card-body">
                    <h5 class="card-title">📊 Resume Builder Tool</h5>
                    <p class="card-text">Create a professional resume in minutes with our easy-to-use builder.</p>
                    <div class="progress mb-3">
                        <div class="progress-bar" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <a href="resume_builder.php" class="btn btn-outline-primary w-100">
                        <i class="fas fa-magic me-2"></i>Start Building
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals for Quick Resources -->
<!-- Resume Templates Modal -->
<div class="modal fade" id="resumeTemplatesModal" tabindex="-1" aria-labelledby="resumeTemplatesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resumeTemplatesModalLabel">Resume Templates</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Professional Resume Templates</h6>
                <p>Choose from our collection of 12 professionally designed resume templates:</p>
                <ul>
                    <li><strong>Chronological:</strong> Traditional format highlighting work history</li>
                    <li><strong>Functional:</strong> Focus on skills and abilities</li>
                    <li><strong>Combination:</strong> Mix of chronological and functional</li>
                    <li><strong>Creative:</strong> For design and creative industries</li>
                    <li><strong>Minimalist:</strong> Clean and modern design</li>
                </ul>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    All templates are ATS-friendly and customizable to your needs.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.location.href='resume_builder.php'">Use Template</button>
            </div>
        </div>
    </div>
</div>

<!-- ATS Tips Modal -->
<div class="modal fade" id="atsTipsModal" tabindex="-1" aria-labelledby="atsTipsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="atsTipsModalLabel">ATS-Friendly Tips</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Optimize Your Resume for Applicant Tracking Systems</h6>
                <ul>
                    <li><strong>Use standard section headings:</strong> "Work Experience", "Education", "Skills"</li>
                    <li><strong>Include relevant keywords:</strong> Mirror language from the job description</li>
                    <li><strong>Use simple formatting:</strong> Avoid tables, columns, and graphics</li>
                    <li><strong>Standard fonts:</strong> Use Arial, Calibri, Georgia, or Times New Roman</li>
                    <li><strong>File format:</strong> Save as .docx or .pdf (unless specified otherwise)</li>
                    <li><strong>No headers/footers:</strong> ATS may not read content in these areas</li>
                    <li><strong>Spell out acronyms:</strong> Include both "Search Engine Optimization (SEO)"</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Cover Letter Guide Modal -->
<div class="modal fade" id="coverLetterModal" tabindex="-1" aria-labelledby="coverLetterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="coverLetterModalLabel">Cover Letter Guide</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Writing an Effective Cover Letter</h6>
                <div class="mb-3">
                    <h6>Structure:</h6>
                    <ol>
                        <li><strong>Header:</strong> Your contact information and date</li>
                        <li><strong>Salutation:</strong> Address to specific hiring manager</li>
                        <li><strong>Opening paragraph:</strong> State the position and your interest</li>
                        <li><strong>Body paragraphs:</strong> Connect your experience to the job requirements</li>
                        <li><strong>Closing paragraph:</strong> Reiterate interest and call to action</li>
                        <li><strong>Signature:</strong> Professional closing and your name</li>
                    </ol>
                </div>
                <div class="alert alert-success">
                    <i class="fas fa-lightbulb me-2"></i>
                    <strong>Pro Tip:</strong> Customize each cover letter for the specific company and role. Research the company and mention why you're interested in working for them specifically.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Resume Review Service Modal -->
<div class="modal fade" id="resumeReviewModal" tabindex="-1" aria-labelledby="resumeReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resumeReviewModalLabel">Resume Review Service</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Professional Resume Review</h6>
                <p>Our career experts will provide comprehensive feedback on your resume:</p>
                <ul>
                    <li><strong>Content Analysis:</strong> Relevance, impact, and clarity of information</li>
                    <li><strong>Formatting Review:</strong> Layout, design, and readability</li>
                    <li><strong>ATS Optimization:</strong> Keyword optimization and compatibility</li>
                    <li><strong>Industry-Specific Advice:</strong> Tailored to your target roles</li>
                    <li><strong>Actionable Recommendations:</strong> Specific improvements you can make</li>
                </ul>
                <div class="alert alert-warning">
                    <i class="fas fa-clock me-2"></i>
                    Turnaround time: 2-3 business days
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.location.href='contact.php'">Request Review</button>
            </div>
        </div>
    </div>
</div>

<!-- Resume Examples Modal -->
<div class="modal fade" id="resumeExamplesModal" tabindex="-1" aria-labelledby="resumeExamplesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resumeExamplesModalLabel">Resume Examples by Industry</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Industry-Specific Resume Examples</h6>
                <div class="row">
                    <div class="col-md-6">
                        <ul>
                            <li>Technology & IT</li>
                            <li>Healthcare & Medical</li>
                            <li>Finance & Banking</li>
                            <li>Marketing & Sales</li>
                            <li>Education & Teaching</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul>
                            <li>Engineering</li>
                            <li>Creative & Design</li>
                            <li>Hospitality & Tourism</li>
                            <li>Non-Profit & Government</li>
                            <li>Business & Management</li>
                        </ul>
                    </div>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-download me-2"></i>
                    Each example includes industry-specific keywords, appropriate section emphasis, and relevant formatting.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.location.href='resume_examples.php'">View Examples</button>
            </div>
        </div>
    </div>
</div>

<!-- Resume Checklist Modal -->
<div class="modal fade" id="resumeChecklistModal" tabindex="-1" aria-labelledby="resumeChecklistModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resumeChecklistModalLabel">Resume Checklist</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Final Resume Review Checklist</h6>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check1">
                    <label class="form-check-label" for="check1">Contact information is current and professional</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check2">
                    <label class="form-check-label" for="check2">No spelling or grammatical errors</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check3">
                    <label class="form-check-label" for="check3">Consistent formatting and font usage</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check4">
                    <label class="form-check-label" for="check4">Quantifiable achievements included</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check5">
                    <label class="form-check-label" for="check5">Relevant keywords from job description</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check6">
                    <label class="form-check-label" for="check6">Appropriate length (1-2 pages)</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check7">
                    <label class="form-check-label" for="check7">File saved in appropriate format</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printChecklist()">Print Checklist</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add click tracking for resource links
    const resourceLinks = document.querySelectorAll('.quick-resource-item');
    resourceLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const resourceName = this.textContent.trim();
            console.log(`Resource clicked: ${resourceName}`);
            // Here you can add analytics tracking
        });
    });

    // Add smooth scrolling for qualification sections
    const qualificationSections = document.querySelectorAll('.qualification-section');
    qualificationSections.forEach(section => {
        section.addEventListener('click', function() {
            this.style.transform = 'scale(1.02)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 200);
        });
    });

    // Progress bar animation
    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        setTimeout(() => {
            progressBar.style.width = '75%';
        }, 500);
    }
});

function printChecklist() {
    const checkboxes = document.querySelectorAll('.form-check-input');
    let checkedCount = 0;
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) checkedCount++;
    });
    
    alert(`You have completed ${checkedCount} out of ${checkboxes.length} checklist items!`);
    window.print();
}
</script>

<?php include 'footer.php'; ?>