<?php
session_start();

$host = 'localhost';
$dbname = 'attendance_system';
$username = 'root';
$password = '';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $email = trim($_POST['username']);
        $password_input = $_POST['password'];
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password_input, $user['password'])) {
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];
            
            
            error_log("User role from database: " . $user['role']);
            
            
            $role = strtolower(trim($user['role']));
            
            if ($role === 'student') {
                header("Location: studentdashboard.php");
                exit();
            } elseif ($role === 'faculty') {
                header("Location: facultydashboard.php");
                exit();
            } elseif ($role === 'facultyintern') {
                header("Location: fidashboard.php");
                exit();
            } else {
                
                header("Location: studentdashboard.php");
                exit();
            }
        } else {
            $error_message = "Invalid email or password!";
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LOGIN PAGE</title>
<link rel="stylesheet" href="login.css">
</head>

<body>
    <div class="main-title">ASHESI ATTENDANCE MANAGEMENT SYSTEM</div>
    
    <?php if ($error_message): ?>
      <div style="text-align: center; padding: 10px; margin: 10px auto; width: 320px; border-radius: 5px; background-color: #ffdddd; color: #d8000c; border: 1px solid #d8000c;">
        <?php echo htmlspecialchars($error_message); ?>
      </div>
    <?php endif; ?>
    
    <form action="login.php" method="post">
        <div class="login-page">
        <label for="Email_Input">Enter your email: </label>
        <input type="email" name="username" id="Email_Input" placeholder="email" required>
        
        <label for="Password_Input">Enter your password: </label>
        <input type="password" name="password" id="Password_Input" placeholder="password" required>

        <input type="submit" value="LOGIN">
        </div>

        <div class="signup-link">
        Don't have an account?
        <a href="signup.php">You can sign up here</a>
        </div>
    </form>
</body>
</html>