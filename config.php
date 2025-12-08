<?php
$host = 'localhost';
$dbname = 'webtech_2025A_edwin_engmann';
$username = 'edwin.engmann';
$password = '20277505';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}


function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}


function requireRole($requiredRole) {
    requireLogin();
    if ($_SESSION['user_role'] !== $requiredRole) {
        header("Location: login.php");
        exit();
    }
}
?>