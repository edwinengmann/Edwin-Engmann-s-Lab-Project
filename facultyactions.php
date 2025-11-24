<?php
session_start();
require_once 'config.php';


requireRole('Faculty');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $action = $_POST['action'] ?? '';
    $faculty_id = $_SESSION['user_id'];

    switch ($action) {
        case 'create_course':
            
            $course_name = trim($_POST['course_name'] ?? '');
            $course_code = trim($_POST['course_code'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $day = trim($_POST['day'] ?? '');
            $time = trim($_POST['time'] ?? '');

            if (empty($course_name) || empty($course_code)) {
                throw new Exception('Course name and code are required');
            }

            
            $stmt = $conn->prepare("SELECT id FROM courses WHERE course_code = ?");
            $stmt->execute([$course_code]);
            if ($stmt->fetch()) {
                throw new Exception('Course code already exists');
            }

            
            $stmt = $conn->prepare("
                INSERT INTO courses (course_name, course_code, description, faculty_id, day, time) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$course_name, $course_code, $description, $faculty_id, $day, $time]);

            $response['success'] = true;
            $response['message'] = 'Course created successfully!';
            break;

        case 'get_courses':
            
            $stmt = $conn->prepare("
                SELECT id, course_name, course_code, description, day, time, created_at 
                FROM courses 
                WHERE faculty_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$faculty_id]);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['courses'] = $courses;
            break;

        case 'get_requests':
            
            $stmt = $conn->prepare("
                SELECT 
                    e.id,
                    e.enrollment_type,
                    e.request_message,
                    e.created_at,
                    u.first_name,
                    u.last_name,
                    u.email,
                    c.course_name,
                    c.course_code
                FROM enrollments e
                INNER JOIN users u ON e.user_id = u.id
                INNER JOIN courses c ON e.course_id = c.id
                WHERE c.faculty_id = ? AND e.status = 'pending'
                ORDER BY e.created_at DESC
            ");
            $stmt->execute([$faculty_id]);
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['requests'] = $requests;
            break;

        case 'approve_request':
            $request_id = intval($_POST['request_id'] ?? 0);
            
            if ($request_id <= 0) {
                throw new Exception('Invalid request ID');
            }

            
            $stmt = $conn->prepare("
                SELECT e.id 
                FROM enrollments e
                INNER JOIN courses c ON e.course_id = c.id
                WHERE e.id = ? AND c.faculty_id = ? AND e.status = 'pending'
            ");
            $stmt->execute([$request_id, $faculty_id]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Request not found or already processed');
            }

            
            $stmt = $conn->prepare("UPDATE enrollments SET status = 'approved' WHERE id = ?");
            $stmt->execute([$request_id]);

            $response['success'] = true;
            $response['message'] = 'Request approved successfully!';
            break;

        case 'reject_request':
            $request_id = intval($_POST['request_id'] ?? 0);
            
            if ($request_id <= 0) {
                throw new Exception('Invalid request ID');
            }

            
            $stmt = $conn->prepare("
                SELECT e.id 
                FROM enrollments e
                INNER JOIN courses c ON e.course_id = c.id
                WHERE e.id = ? AND c.faculty_id = ? AND e.status = 'pending'
            ");
            $stmt->execute([$request_id, $faculty_id]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Request not found or already processed');
            }

    
            $stmt = $conn->prepare("UPDATE enrollments SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$request_id]);

            $response['success'] = true;
            $response['message'] = 'Request rejected successfully!';
            break;

        case 'delete_course':
            $course_id = intval($_POST['course_id'] ?? 0);
            
            if ($course_id <= 0) {
                throw new Exception('Invalid course ID');
            }

            
            $stmt = $conn->prepare("SELECT id FROM courses WHERE id = ? AND faculty_id = ?");
            $stmt->execute([$course_id, $faculty_id]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Course not found or unauthorized');
            }

            
            $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->execute([$course_id]);

            $response['success'] = true;
            $response['message'] = 'Course deleted successfully!';
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>