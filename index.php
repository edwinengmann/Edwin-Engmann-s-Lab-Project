<?php


session_start();


$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'attendance_system';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$port = getenv('DB_PORT') ?: '3306';


try {
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /");
        exit();
    }
}

function requireRole($requiredRole) {
    requireLogin();
    if ($_SESSION['user_role'] !== $requiredRole) {
        header("Location: /");
        exit();
    }
}

$request_uri = $_SERVER['REQUEST_URI'];
$script_name = dirname($_SERVER['SCRIPT_NAME']);
$path = str_replace($script_name, '', $request_uri);
$path = parse_url($path, PHP_URL_PATH);
$path = trim($path, '/');


if (empty($path)) {
    $path = 'login';
}


switch($path) {
    case 'login':
    case '':
    
        if (isset($_SESSION['user_id'])) {
            $role = strtolower(trim($_SESSION['user_role']));
            if ($role === 'student') {
                header("Location: /student-dashboard");
            } elseif ($role === 'faculty') {
                header("Location: /faculty-dashboard");
            } elseif ($role === 'facultyintern') {
                header("Location: /faculty-intern-dashboard");
            }
            exit();
        }
        include 'login.php';
        break;

    case 'signup':
        include 'signup.php';
        break;

    case 'login-ajax':
        include 'login_ajax.php';
        break;

    case 'logout':
        include 'logout.php';
        break;

    case 'student-dashboard':
        requireRole('student');
        include 'studentdashboard.php';
        break;

    case 'student-actions':
        requireRole('student');
        include 'studentactions.php';
        break;

    case 'faculty-dashboard':
        requireRole('Faculty');
        include 'facultydashboard.php';
        break;

    case 'faculty-actions':
        requireRole('Faculty');
        include 'facultyactions.php';
        break;

    case 'faculty-intern-dashboard':
        requireRole('FacultyIntern');
        include 'fidashboard.php';
        break;

    case 'faculty-intern-actions':
        requireRole('FacultyIntern');
        include 'fiactions.php';
        break;


    case 'login.css':
        header('Content-Type: text/css');
        include 'login.css';
        break;

    case 'signup.css':
        header('Content-Type: text/css');
        include 'signup.css';
        break;

    case 'student.css':
        header('Content-Type: text/css');
        include 'student.css';
        break;

    case 'faculty.css':
        header('Content-Type: text/css');
        include 'faculty.css';
        break;

    case 'fi.css':
        header('Content-Type: text/css');
        include 'fi.css';
        break;

    default:
        header("HTTP/1.0 404 Not Found");
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>404 - Page Not Found</title>
            <style>
                body {
                    font-family: 'Segoe UI', Arial, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                }
                .error-container {
                    background: white;
                    padding: 50px;
                    border-radius: 15px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                    text-align: center;
                    max-width: 500px;
                }
                h1 {
                    font-size: 4em;
                    color: #E63946;
                    margin: 0;
                }
                h2 {
                    color: #333;
                    margin: 20px 0;
                }
                p {
                    color: #666;
                    line-height: 1.6;
                }
                a {
                    display: inline-block;
                    margin-top: 20px;
                    padding: 12px 30px;
                    background: #E63946;
                    color: white;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: bold;
                    transition: background 0.3s;
                }
                a:hover {
                    background: #B22222;
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h1>404</h1>
                <h2>Page Not Found</h2>
                <p>The page you're looking for doesn't exist or has been moved.</p>
                <a href="/">Return to Login</a>
            </div>
        </body>
        </html>
        <?php
        break;
}
?>