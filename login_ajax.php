<?php
session_start();

$host = 'localhost';
$dbname = 'webtech_2025A_edwin_engman';
$username = 'edwin.engmann';
$password = '20277505';


header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'redirect' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $email = trim($_POST['username']);
        $password_input = $_POST['password'];
        
        
        if (empty($email) || empty($password_input)) {
            throw new Exception('Email and password are required');
        }
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password_input, $user['password'])) {
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];
            
            
            $role = strtolower(trim($user['role']));
            
            if ($role === 'student') {
                $response['redirect'] = 'studentdashboard.php';
            } elseif ($role === 'faculty') {
                $response['redirect'] = 'facultydashboard.php';
            } elseif ($role === 'facultyintern') {
                $response['redirect'] = 'fidashboard.php';
            } else {
                $response['redirect'] = 'studentdashboard.php';
            }
            
            $response['success'] = true;
            $response['message'] = 'Login successful! Redirecting...';
            
        } else {
            throw new Exception('Invalid email or password!');
        }
    } catch(PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    } catch(Exception $e) {
        $response['message'] = $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
?>