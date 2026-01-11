<?php
session_start();
require_once 'config.php';
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// Admin email configuration
$admin_email = "shruthaportal@gmail.com";
$subject = "New Visitor Feedback Received";

// Initialize messages
$success = $error = "";

// Create table if not exists
$createTable = "CREATE TABLE IF NOT EXISTS visitor_diaries (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visitor_name VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    company VARCHAR(50),
    person_to_visit VARCHAR(50),
    purpose VARCHAR(100),
    rating INT(1),
    feedback TEXT NOT NULL,
    visit_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($createTable)) {
    $error = "Error creating table: " . $conn->error;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $visitor_name = htmlspecialchars(trim($_POST['visitor_name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));
    $company = htmlspecialchars(trim($_POST['company']));
    $person_to_visit = htmlspecialchars(trim($_POST['person_to_visit']));
    $purpose = htmlspecialchars(trim($_POST['purpose']));
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $feedback = htmlspecialchars(trim($_POST['feedback']));

    // Validate inputs
    if (empty($visitor_name) || empty($email) || empty($feedback)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO visitor_diaries 
            (visitor_name, email, phone, company, person_to_visit, purpose, rating, feedback) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("ssssssis", 
            $visitor_name, 
            $email, 
            $phone, 
            $company, 
            $person_to_visit, 
            $purpose, 
            $rating,
            $feedback
        );

        if ($stmt->execute()) {
            // Prepare email content
            $message = "New visitor feedback received:\n\n"
                . "Name: $visitor_name\n"
                . "Email: $email\n"
                . "Phone: $phone\n"
                . "Company: $company\n"
                . "Person to Visit: $person_to_visit\n"
                . "Purpose: $purpose\n"
                . "Rating: " . str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) . "\n\n"
                . "Feedback:\n$feedback";

            // Send email using PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'shruthaportal@gmail.com'; // Your Gmail
                $mail->Password   = 'sttt vkri eeug bxxq';    // Gmail App Password
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom($email, $visitor_name);
                $mail->addAddress($admin_email);

                $mail->isHTML(false);
                $mail->Subject = $subject;
                $mail->Body    = $message;

                $mail->send();
                $success = "Thank you for your feedback! Email notification sent.";
            } catch (Exception $e) {
                $success = "Feedback saved. Email could not be sent. Error: {$mail->ErrorInfo}";
            }
        } else {
            $error = "Error saving feedback: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Feedback Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .feedback-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-top: 30px;
            margin-bottom: 30px;
            border: none;
        }
        .card-header {
            background: linear-gradient(90deg, #4b6cb7 0%, #182848 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .form-container {
            padding: 30px;
        }
        .form-control:focus {
            border-color: #4b6cb7;
            box-shadow: 0 0 0 0.25rem rgba(75, 108, 183, 0.25);
        }
        .btn-submit {
            background: linear-gradient(90deg, #4b6cb7 0%, #182848 100%);
            border: none;
            padding: 12px 25px;
            font-size: 18px;
            transition: all 0.3s ease;
        }
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .required:after {
            content: " *";
            color: red;
        }
        .feedback-icon {
            font-size: 5rem;
            color: #4b6cb7;
            margin-bottom: 20px;
        }
        .banner-text {
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        }
        .rating-container {
            text-align: center;
            margin: 20px 0;
        }
        .rating {
            display: inline-block;
            position: relative;
            height: 50px;
            line-height: 50px;
            font-size: 30px;
        }
        .rating label {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            cursor: pointer;
        }
        .rating label:last-child {
            position: static;
        }
        .rating label:nth-child(1) {
            z-index: 5;
        }
        .rating label:nth-child(2) {
            z-index: 4;
        }
        .rating label:nth-child(3) {
            z-index: 3;
        }
        .rating label:nth-child(4) {
            z-index: 2;
        }
        .rating label:nth-child(5) {
            z-index: 1;
        }
        .rating label input {
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0;
        }
        .rating label .icon {
            float: left;
            color: transparent;
        }
        .rating label:last-child .icon {
            color: #ddd;
        }
        .rating:not(:hover) label input:checked ~ .icon,
        .rating:hover label:hover input ~ .icon {
            color: #ffc107;
        }
        .rating label input:focus:not(:checked) ~ .icon:last-child {
            color: #ddd;
            text-shadow: 0 0 5px #ffc107;
        }
        .rating-text {
            font-size: 18px;
            margin-top: 10px;
            font-weight: 500;
            color: #4b6cb7;
        }
        .success-rating {
            font-size: 24px;
            color: #ffc107;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
         <?php if(isset($_SESSION['user'])): ?>
            <a href="employee_dashboard.php" class="btn btn-outline-secondary" style="text-decoration: none; position: absolute; left: 40px; box-shadow: none; margin-top: 8px;">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        <?php else: ?>
            <a href="index.php" class="btn btn-outline-secondary" style="text-decoration: none; position: absolute; left: 40px; box-shadow: none; margin-top: 8px;">
                <i class="fas fa-arrow-left me-2"></i>Back to Home
            </a>
        <?php endif; ?>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card feedback-card">
                    <div class="card-header">
                        <h1 class="banner-text"><i class="fas fa-comments me-3"></i>Visitor Feedback Diary</h1>
                        <p class="mb-0">Share your experience with us</p>
                    </div>
                    <div class="card-body form-container">
                        <?php if ($success): ?>
                            <div class="alert alert-success text-center">
                                <i class="fas fa-check-circle fa-2x mb-3"></i>
                                <h4><?php echo $success; ?></h4>
                                <?php if (isset($rating) && $rating > 0): ?>
                                    <div class="success-rating">
                                        Your rating: <?php echo str_repeat('★', $rating) . str_repeat('☆', 5 - $rating); ?>
                                    </div>
                                <?php endif; ?>
                                <p>We value your input and will respond if needed</p>
                                <a href="visitor_diaries.php" class="btn btn-outline-success mt-2">Submit Another Feedback</a>
                            </div>
                        <?php else: ?>
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            
                            <div class="text-center mb-4">
                                <i class="fas fa-headset feedback-icon"></i>
                                <h3>How was your visit experience?</h3>
                                <p class="text-muted">Your feedback helps us improve our services</p>
                            </div>
                            
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required">Full Name</label>
                                        <input type="text" class="form-control" name="visitor_name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required">Email</label>
                                        <input type="email" class="form-control" name="email" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" class="form-control" name="phone">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Company</label>
                                        <input type="text" class="form-control" name="company">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Person to Visit</label>
                                        <input type="text" class="form-control" name="person_to_visit">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Purpose of Visit</label>
                                        <select class="form-select" name="purpose">
                                            <option value="">Select purpose</option>
                                            <option value="Meeting">Meeting</option>
                                            <option value="Interview">Interview</option>
                                            <option value="Delivery">Delivery</option>
                                            <option value="Business">Business</option>
                                            <option value="Personal">Personal</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- 5-star rating system -->
                                <div class="rating-container">
                                    <h5 class="text-center mb-3">How would you rate your experience?</h5>
                                    <div class="rating">
                                        <label>
                                            <input type="radio" name="rating" value="1" />
                                            <span class="icon">★</span>
                                        </label>
                                        <label>
                                            <input type="radio" name="rating" value="2" />
                                            <span class="icon">★</span>
                                            <span class="icon">★</span>
                                        </label>
                                        <label>
                                            <input type="radio" name="rating" value="3" />
                                            <span class="icon">★</span>
                                            <span class="icon">★</span>
                                            <span class="icon">★</span>
                                        </label>
                                        <label>
                                            <input type="radio" name="rating" value="4" />
                                            <span class="icon">★</span>
                                            <span class="icon">★</span>
                                            <span class="icon">★</span>
                                            <span class="icon">★</span>
                                        </label>
                                        <label>
                                            <input type="radio" name="rating" value="5" />
                                            <span class="icon">★</span>
                                            <span class="icon">★</span>
                                            <span class="icon">★</span>
                                            <span class="icon">★</span>
                                            <span class="icon">★</span>
                                        </label>
                                    </div>
                                    <div class="rating-text" id="rating-text">Select a rating</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label required">Your Feedback</label>
                                    <textarea class="form-control" name="feedback" rows="5" required placeholder="Share your experience..."></textarea>
                                </div>
                                
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-submit btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Feedback
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center py-3">
                        <p class="mb-0">We appreciate your time and feedback!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Rating system interaction
        document.addEventListener('DOMContentLoaded', function() {
            const ratingInputs = document.querySelectorAll('.rating input');
            const ratingText = document.getElementById('rating-text');
            
            // Rating descriptions
            const ratingDescriptions = {
                1: "Poor - We're sorry to hear about your experience",
                2: "Fair - We'll work to improve",
                3: "Good - We're glad you had a decent experience",
                4: "Very Good - We're happy you enjoyed your visit",
                5: "Excellent - We're thrilled you had a great experience!"
            };
            
            // Add event listeners to each rating input
            ratingInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const value = this.value;
                    ratingText.textContent = ratingDescriptions[value] || "Select a rating";
                });
                
                // Add hover effect
                input.addEventListener('mouseover', function() {
                    const value = this.value;
                    ratingText.textContent = ratingDescriptions[value] || "Select a rating";
                });
            });
            
            // Reset to default when mouse leaves rating area
            document.querySelector('.rating').addEventListener('mouseleave', function() {
                const checkedInput = document.querySelector('.rating input:checked');
                if (!checkedInput) {
                    ratingText.textContent = "Select a rating";
                }
            });
        });
    </script>
</body>
</html>