<?php
require_once 'config.php';
$page_title = "Contact Us";
include 'header.php';

// Initialize variables
$success_message = $error_message = '';
$name = $email = $subject = $message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Validate form data
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (empty($subject)) {
        $errors[] = "Subject is required";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required";
    } elseif (strlen($message) < 10) {
        $errors[] = "Message should be at least 10 characters long";
    }
    
    // If no errors, send email
    if (empty($errors)) {
        try {
            // Create PHPMailer instance
            require_once 'vendor/autoload.php'; // Make sure PHPMailer is installed via Composer
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Set your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'shruthaportal@gmail.com'; // Your email
            $mail->Password = 'sttt vkri eeug bxxq'; // Your app password
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            // Recipients
            $mail->setFrom($email, $name);
            $mail->addAddress('shruthaportal@gmail.com', 'Shrutha Portal Admin'); // Admin email
            $mail->addReplyTo($email, $name);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = "Contact Form: " . $subject;
            $mail->Body = "
                <h2>New Contact Form Submission</h2>
                <p><strong>Name:</strong> {$name}</p>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Subject:</strong> {$subject}</p>
                <p><strong>Message:</strong></p>
                <p>" . nl2br(htmlspecialchars($message)) . "</p>
                <hr>
                <p><small>Sent from Employee Portal Contact Form on " . date('Y-m-d H:i:s') . "</small></p>
            ";
            
            $mail->AltBody = "
                New Contact Form Submission
                Name: {$name}
                Email: {$email}
                Subject: {$subject}
                Message: {$message}
                Sent from Employee Portal Contact Form on " . date('Y-m-d H:i:s') . "
            ";
            
            if ($mail->send()) {
                $success_message = "Thank you for your message! We'll get back to you soon.";
                // Clear form fields
                $name = $email = $subject = $message = '';
            } else {
                $error_message = "Sorry, there was an error sending your message. Please try again later.";
            }
            
        } catch (Exception $e) {
            $error_message = "Message could not be sent. Error: " . $mail->ErrorInfo;
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}
?>

<div class="container py-5">
    <!-- Success/Error Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="text-center mb-5">
                <h1 class="display-5 fw-bold text-primary mb-3">Get In Touch</h1>
                <p class="lead text-muted">We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
            </div>
            
            <div class="row g-5">
                <!-- Contact Form -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-lg rounded-3">
                        <div class="card-header bg-primary text-white py-3">
                            <h4 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Send us a Message</h4>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="" id="contactForm" novalidate>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-user text-primary"></i>
                                            </span>
                                            <input type="text" class="form-control border-start-0" id="name" name="name" 
                                                   value="<?php echo htmlspecialchars($name); ?>" 
                                                   placeholder="Enter your full name" required>
                                        </div>
                                        <div class="invalid-feedback">Please provide your name.</div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-envelope text-primary"></i>
                                            </span>
                                            <input type="email" class="form-control border-start-0" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($email); ?>" 
                                                   placeholder="Enter your email" required>
                                        </div>
                                        <div class="invalid-feedback">Please provide a valid email address.</div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label for="subject" class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-tag text-primary"></i>
                                            </span>
                                            <input type="text" class="form-control border-start-0" id="subject" name="subject" 
                                                   value="<?php echo htmlspecialchars($subject); ?>" 
                                                   placeholder="What is this regarding?" required>
                                        </div>
                                        <div class="invalid-feedback">Please provide a subject.</div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label for="message" class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0 align-items-start pt-3">
                                                <i class="fas fa-comment text-primary"></i>
                                            </span>
                                            <textarea class="form-control border-start-0" id="message" name="message" 
                                                      rows="6" placeholder="Tell us how we can help you..." 
                                                      required><?php echo htmlspecialchars($message); ?></textarea>
                                        </div>
                                        <div class="invalid-feedback">Please write your message (minimum 10 characters).</div>
                                        <div class="form-text">Minimum 10 characters required.</div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary btn-lg px-4 py-2">
                                            <i class="fas fa-paper-plane me-2"></i>Send Message
                                        </button>
                                        <button type="reset" class="btn btn-outline-secondary btn-lg px-4 py-2 ms-2">
                                            <i class="fas fa-redo me-2"></i>Reset Form
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-lg rounded-3 h-100">
                        <div class="card-header bg-success text-white py-3">
                            <h4 class="mb-0"><i class="fas fa-info-circle me-2"></i>Our Information</h4>
                        </div>
                        <div class="card-body p-4">
                            <div class="contact-info">
                                <div class="d-flex align-items-start mb-4">
                                    <div class="flex-shrink-0">
                                        <div class="icon-circle bg-primary text-white">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-bold mb-1">Our Address</h6>
                                        <p class="text-muted mb-0">F07, D.No 2-11/26(27), "Green City", Behind Naganakatte, N.H.66, Thokottu, Mangaluru, Karnataka-575017.</p>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-start mb-4">
                                    <div class="flex-shrink-0">
                                        <div class="icon-circle bg-success text-white">
                                            <i class="fas fa-phone-alt"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-bold mb-1">Phone Number</h6>
                                        <p class="text-muted mb-0">078297 38999</p>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-start mb-4">
                                    <div class="flex-shrink-0">
                                        <div class="icon-circle bg-info text-white">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-bold mb-1">Email Address</h6>
                                        <p class="text-muted mb-0">naveennayak.i@abhimo.com</p>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-start mb-4">
                                    <div class="flex-shrink-0">
                                        <div class="icon-circle bg-warning text-white">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-bold mb-1">Business Hours</h6>
                                        <p class="text-muted mb-0">Mon-Fri: 9AM - 6PM</p>
                                        <p class="text-muted mb-0">Sat: 10AM - 2PM</p>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="social-section">
                                <h6 class="fw-bold mb-3">Follow Us On Social Media</h6>
                                <div class="d-flex justify-content-start">
                                    <a href="https://www.facebook.com/share/1MiQLLNC4z/" class="social-btn btn-facebook me-2" title="Facebook">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                    <a href="https://www.linkedin.com/company/abhimo-technologies-private-limited/" class="social-btn btn-linkedin me-2" title="LinkedIn">
                                        <i class="fab fa-linkedin-in"></i>
                                    </a>
                                    <a href="https://www.instagram.com/abhimo_technologies?igsh=Mm90N2N1bWY4OGcy" class="social-btn btn-instagram" title="Instagram">
                                        <i class="fab fa-instagram"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.icon-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.contact-info h6 {
    font-size: 0.95rem;
}

.social-btn {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.btn-facebook {
    background: #3b5998;
    color: white;
}

.btn-twitter {
    background: #1da1f2;
    color: white;
}

.btn-linkedin {
    background: #0077b5;
    color: white;
}

.btn-instagram {
    background: #e4405f;
    color: white;
}

.social-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    color: white;
}

.card {
    transition: transform 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
}

.input-group-text {
    transition: all 0.3s ease;
}

.form-control:focus + .input-group-text {
    background-color: #e3f2fd;
    border-color: #0d6efd;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .display-5 {
        font-size: 2rem;
    }
    
    .btn-lg {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>

<script>
// Client-side form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contactForm');
    
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        form.classList.add('was-validated');
    });
    
    // Real-time validation for message length
    const messageInput = document.getElementById('message');
    messageInput.addEventListener('input', function() {
        if (this.value.length < 10) {
            this.setCustomValidity('Message must be at least 10 characters long');
        } else {
            this.setCustomValidity('');
        }
    });
});
</script>

<?php include 'footer.php'; ?>