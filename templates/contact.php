<?php
// Start session for messages
// Check if the office variable is set
if (!isset($office) || empty($office['id'])) {
    die("Error: Office information is not available.");
}

// Process form submission if POST request
$form_error = '';
$form_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    // Validate and sanitize inputs
    $name = trim($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_error = 'Please enter a valid email address';
    } elseif (empty($message)) {
        $form_error = 'Please enter your message';
    } else {
        // Database connection
        $db_host = '127.0.0.1';
        $db_name = 'office_management';
        $db_user = 'root'; // Change to your database username
        $db_pass = ''; // Change to your database password
        
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Prepare SQL statement
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages 
                (name, email, phone, subject, message, ip_address, user_agent, office_id) 
                VALUES 
                (:name, :email, :phone, :subject, :message, :ip_address, :user_agent, :office_id)
            ");
            
            // Use the office ID from the current office context
            $office_id = $office['id']; // Get the office ID from the office variable
            
            // Execute with parameters
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':subject' => $subject,
                ':message' => $message,
                ':ip_address' => $_SERVER['REMOTE_ADDR'],
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ':office_id' => $office_id // Use the office ID from the current office
            ]);
            
            $form_success = 'Message sent successfully! We will get back to you soon.';
            
            // Clear form
            $_POST = [];
            
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $form_error = 'Sorry, there was an error sending your message. Please try again later.';
        }
    }
}

// Decode the JSON content safely
$content = !empty($section['content']) ? json_decode($section['content'], true) : [];
$contact_email = $content['email'] ?? 'Not provided';
$contact_phone = $content['phone'] ?? 'Not provided';

// Fetch the office address from the $office record
$office_location = $office['office_address'] ?? 'Not provided';

?>
<div id="section-<?php echo $section['id']; ?>" class="section contact-footer py-5" style="<?php 
  // Set background color if provided
  $background_color = !empty($section['background_color']) ? htmlspecialchars($section['background_color']) : '#f8f9fa';
  echo "background-color: $background_color;"; 
  // Set background image if provided
  if (!empty($section['background_path'])) {
    echo "background-image: url('" . htmlspecialchars($section['background_path']) . "'); background-size: cover; background-position: center;";
  }
?>">
  <div class="container">
    <div class="text-center mb-5">
      <div class="text-center mt-4">
        <h4>
          <?php 
            if (!empty($section['title'])) {
                echo htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8');
            } else {
                echo '<strong style="font-size: 2em; font-weight: bold;"> CONTACT US </strong>';
            }
          ?>
        </h4>
        <hr>
        <p class="lead">
             Get in touch with us for any inquiries or support.
        </p>
      </div>
    </div>

    <div class="row">
      <!-- Contact Form Column -->
      <div class="col-md-6 mb-4">
        <div class="contact-card-footer p-4 rounded" style="background-color: white; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);">
          <h3 class="text-mb-4">Send Us a Message</h3>
          
          <?php if ($form_error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
              <?php echo htmlspecialchars($form_error); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>
          
          <?php if ($form_success): ?>
            <div class="alert alert-success alert-dismissible fade show" id="successAlert">
              <?php echo htmlspecialchars($form_success); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>
          
          <form id="contactForm" method="POST" action="#section-<?php echo $section['id']; ?>">
            <div class="form-group">
              <label for="name">Your Name</label>
              <input type="text" id="name" name="name" class="form-control" 
                     placeholder="Your name" 
                     value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>
            <div class="form-group">
              <label for="email">Email Address</label>
              <input type="email" id="email" name="email" class="form-control" 
                     placeholder="Email" required
                     value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div class="form-group">
              <label for="phone">Phone Number (optional)</label>
              <input type="tel" id="phone" name="phone" class="form-control" 
                     placeholder="Phone number"
                     value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>
            <div class="form-group">
              <label for="subject">Subject (optional)</label>
              <input type="text" id="subject" name="subject" class="form-control" 
                     placeholder="Subject"
                     value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
            </div>
            <div class="form-group">
              <label for="message">Your Message</label>
              <textarea id="message" name="message" class="form-control" rows="4" 
                        placeholder="Type your message here..." required><?php 
                echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; 
              ?></textarea>
            </div>
            <button type="submit" name="contact_submit" class="btn btn-primary btn-block">SUBMIT</button>
          </form>
        </div>
      </div>

      <!-- Contact Info Column -->
      <div class="col-md-6 mb-4">
        <div class="contact-card-footer p-4 rounded" style="background-color: white; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);">
          <h3 class="text-mb-4">Contact Information</h3>
          <div class="contact-info" style="padding-left: 15px;">
            <div class="d-flex align-items-start mb-4">
              <i class="fas fa-map-marker-alt mt-1 mr-3" style="font-size: 1.2em; min-width: 20px;"></i>
              <div>
                <h5 class="mb-1" style="font-size: 1em; font-weight: 600;">Address</h5>
                <p class="text-muted mb-0" style="line-height: 1.5;"><?php echo nl2br(htmlspecialchars($office_location)); ?></p>
              </div>
            </div>
            <div class="d-flex align-items-start mb-4">
              <i class="fas fa-phone mt-1 mr-3" style="font-size: 1.2em; min-width: 20px;"></i>
              <div>
                <h5 class="mb-1" style="font-size: 1em; font-weight: 600;">Phone</h5>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($contact_phone); ?></p>
              </div>
            </div>
            <div class="d-flex align-items-start mb-4">
              <i class="fas fa-envelope mt-1 mr-3" style="font-size: 1.2em; min-width: 20px;"></i>
              <div>
                <h5 class="mb-1" style="font-size: 1em; font-weight: 600;">Email</h5>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($contact_email); ?></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .contact-footer {
    padding: 60px 0;
  }

  .contact-card-footer {
    background-color: white;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    height: 100%;
  }

  .contact-card-footer:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
  }

  .contact-card-footer h2, 
  .contact-card-footer h3, 
  .contact-card-footer h4 {
    color: #000;
    margin-bottom: 20px;
  }

  .contact-card-footer .form-group label {
    color: #6c757d;
    font-size: 0.9em;
    margin-bottom: 5px;
    display: block;
  }

  .contact-card-footer .form-control {
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 12px;
    font-size: 1em;
    margin-bottom: 15px;
  }

  .contact-card-footer .btn-primary {
    background-color: #000;
    border: none;
    padding: 12px;
    font-size: 1em;
    font-weight: 600;
    transition: background-color 0.3s;
  }

  .contact-card-footer .btn-primary:hover {
    background-color: #333;
  }

  .contact-info p {
    font-size: 1em;
    margin: 0;
  }

  .contact-info i {
    color: #000;
    margin-right: 15px;
  }

  .lead {
    font-size: 1.25em;
    font-weight: 300;
    margin-bottom: 30px;
  }

  .alert {
    position: relative;
    padding: 0.75rem 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: 0.25rem;
  }

  .alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
  }

  .alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
  }

  .alert-dismissible {
    padding-right: 4rem;
  }

  .alert-dismissible .close {
    position: absolute;
    top: 0;
    right: 0;
    padding: 0.75rem 1.25rem;
    color: inherit;
  }

  @media (max-width: 768px) {
    .contact-card-footer {
      margin-bottom: 30px;
    }
  }
</style>

<script>
  // Client-side validation for better UX
  document.getElementById('contactForm').addEventListener('submit', function(e) {
      const email = document.getElementById('email').value;
      const message = document.getElementById('message').value;
      
      if (!email || !email.includes('@')) {
          alert('Please enter a valid email address');
          e.preventDefault();
          return false;
      }
      
      if (!message.trim()) {
          alert('Please enter your message');
          e.preventDefault();
          return false;
      }
      
      return true;
  });

  // Auto-hide success message after 5 seconds if not closed manually
  document.addEventListener('DOMContentLoaded', function() {
      const successAlert = document.getElementById('successAlert');
      if (successAlert) {
          setTimeout(function() {
              successAlert.style.transition = 'opacity 0.5s';
              successAlert.style.opacity = '0';
              setTimeout(function() {
                  successAlert.style.display = 'none';
              }, 500);
          }, 5000);
      }
  });
</script>