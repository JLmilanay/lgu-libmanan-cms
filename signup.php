<?php
session_start();
include_once('config.php');

$message = "";

// Fetch offices from the 'offices' table
$offices = array();
$resultOffices = $conn->query("SELECT id, office_name FROM offices ORDER BY office_name");
if ($resultOffices && $resultOffices->num_rows > 0) {
    while ($row = $resultOffices->fetch_assoc()) {
        $offices[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username         = trim($_POST['username']);
    $password         = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $office_id        = trim($_POST['office_id']);

    // Basic validations
    if (empty($office_id)) {
        $message = "Please select an office.";
    } elseif (empty($username) || empty($password) || empty($confirm_password)) {
        $message = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        // Check if the username already exists.
        $stmt = $conn->prepare("SELECT id FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Username already exists. Please choose another.";
        } else {
            // Hash the password.
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert the new user with the selected office.
            $stmt = $conn->prepare("INSERT INTO accounts (username, password, office_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $username, $hashed_password, $office_id);

            if ($stmt->execute()) {
                $_SESSION['user_id']   = $stmt->insert_id;
                $_SESSION['username']  = $username;
                $_SESSION['office_id'] = $office_id;

                // Redirect to the office-specific management page.
                header("Location: manage_offices_interface.php?office_id=" . $office_id);
            } else {
                $message = "Error creating account. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign Up - Municipal Planning and Development Office</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <style>
    body {
      background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), 
                  url("ASSETS/bg-lgu.JPG") no-repeat center center/cover;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      font-family: Arial, sans-serif;
      padding: 20px;
      position: relative;
    }
    .container {
      background: rgba(255, 255, 255, 0.15); /* Semi-transparent white */
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
      width: 90%;
      max-width: 500px;  /* Adjusted to center the form */
      backdrop-filter: blur(10px); /* Glassmorphism effect */
      border: 1px solid rgba(255, 255, 255, 0.2);
      text-align: center; /* Center align the content */
    }
    .signup-container {
      width: 100%;
    }
    .signup-header {
      text-align: center;
      margin-bottom: 20px;
    }
    .signup-header img {
      width: 100px;
      animation: fadeIn 1s ease-in-out;
    }
    .signup-header h4 {
      font-size: 22px;
      color: rgb(255, 255, 255);
      margin-top: 10px;
      font-weight: bold;
    }
    .form-group {
      margin-bottom: 15px;
      text-align: left;
    }
    .form-group label {
      font-weight: bold;
      color: #fff;
    }
    input[type="text"], input[type="password"] {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      outline: none;
      transition: 0.3s;
    }
    input[type="text"]:focus, input[type="password"]:focus {
      border-color: #388E3C;
      box-shadow: 0px 0px 5px rgba(56, 142, 60, 0.5);
    }
    .btn-signup {
      background-color: #388E3C;
      border: none;
      color: white;
      padding: 12px;
      width: 100%;
      font-size: 16px;
      font-weight: bold;
      border-radius: 5px;
      cursor: pointer;
      transition: background 0.3s ease-in-out, transform 0.2s;
    }
    .btn-signup:hover {
      background-color: #2E7D32;
      transform: translateY(-2px);
    }
    .extra-link {
      text-align: center;
      margin-top: 15px;
      color: #fff;
    }
    .extra-link a {
      color:rgb(237, 227, 227);
      text-decoration: none;
      font-weight: bold;
      transition: color 0.3s;
    }
    .extra-link a:hover {
      color:rgb(50, 182, 244);
    }
    .forgot-password {
      text-align: center;
      margin-top: 15px;
    }
    .forgot-password a {
      color:rgb(102, 217, 255);
      text-decoration: none;
      font-weight: bold;
    }
    .forgot-password a:hover {
      color:rgb(255, 255, 255);
    }
    /* Animations */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
 </style>
</head>
<body>
  <div class="container">
    <div class="signup-container">
      <div class="signup-header">
      <img src="ASSETS/LIBMANAN LOGO.png" alt="Office Logo">
      <h4>LGU-LIBMANAN OFFICE INFORMATION MANAGEMENT SYSTEM</h4>
      </div>

      <?php if (!empty($message)) : ?>
      <div class="alert alert-danger"><?php echo $message; ?></div>
      <?php endif; ?>

      <form action="signup.php" method="POST">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" class="form-control" id="username" name="username" required autofocus>
        </div>

        <!-- Office Selection -->
        <div class="form-group">
          <label for="office_id">Select Office</label>
          <select class="form-control" id="office_id" name="office_id" required>
              <option value="">Select Office</option>
              <?php foreach ($offices as $office): ?>
              <option value="<?php echo $office['id']; ?>">
                <?php echo $office['office_name']; ?>
              </option>
              <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-signup btn-block">Sign Up</button>
      </form>
      <div class="extra-link">
        <a href="login.php">Already have an account? Login</a>
      </div>
    </div>
  </div>
</body>
</html>
