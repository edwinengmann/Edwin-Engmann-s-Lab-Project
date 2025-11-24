<?php
session_start();

$host = 'localhost';
$dbname = 'attendance_system';
$username = 'root';
$password = '';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $firstName = trim($_POST['firstName']);
        $lastName = trim($_POST['lastName']);
        $role = $_POST['role'];
        $email = trim($_POST['email']);
        $contact = trim($_POST['contact']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirmPassword'];
        
        
        if ($password !== $confirmPassword) {
            $error_message = "Passwords do not match!";
        } elseif (strlen($password) < 6) {
            $error_message = "Password must be at least 6 characters long!";
        } else {
            
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error_message = "Email already registered!";
            } else {
                
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, role, email, contact, password) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$firstName, $lastName, $role, $email, $contact, $hashedPassword]);
                
                $success_message = "Registration successful! Redirecting to login...";
                header("refresh:2;url=login.php");
            }
        }
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>ASHESI ATTENDANCE - SIGN UP</title>
  <link rel="stylesheet" href="signup.css">
</head>
<body>
  <div class="main-title">ASHESI ATTENDANCE MANAGEMENT SYSTEM</div>
  
  <?php if ($error_message): ?>
    <div style="text-align: center; padding: 10px; margin: 10px auto; width: 360px; border-radius: 5px; background-color: #ffdddd; color: #d8000c; border: 1px solid #d8000c;">
      <?php echo htmlspecialchars($error_message); ?>
    </div>
  <?php endif; ?>
  
  <?php if ($success_message): ?>
    <div style="text-align: center; padding: 10px; margin: 10px auto; width: 360px; border-radius: 5px; background-color: #ddffdd; color: #008c00; border: 1px solid #008c00;">
      <?php echo htmlspecialchars($success_message); ?>
    </div>
  <?php endif; ?>
  
  <form action="signup.php" method="post">
    <div class="signup-page">
      <label for="firstName">First Name</label>
      <input type="text" id="firstName" name="firstName" placeholder="Enter your first name" required>
      
      <label for="lastName">Last Name</label>
      <input type="text" id="lastName" name="lastName" placeholder="Enter your last name" required>
      
      <label for="role">Role</label>
      <select id="role" name="role" required>
        <option value="">Select your role</option>
        <option value="student">Student</option>
        <option value="Faculty">Faculty</option>
        <option value="FacultyIntern">Faculty Intern</option>
      </select>
      
      <label for="email">Email Address</label>
      <input type="email" id="email" name="email" placeholder="Enter your email address" required>
      
      <label for="contact">Contact</label>
      <input type="text" id="contact" name="contact" placeholder="Enter your contact" required>
      
      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="Enter your password" required>
      
      <label for="confirmPassword">Confirm Password</label>
      <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password" required>
      
      <input type="submit" value="SUBMIT">
    </div>
    <div class="login-link">
      Already have an account?
      <a href="login.php">Login here</a>
    </div>
  </form>
</body>
</html>