<?php
session_start();
require_once 'config.php';

requireRole('FacultyIntern');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $action = $_POST['action'] ?? '';
    $intern_id = $_SESSION['user_id'];

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
            $stmt->execute([$course_name, $course_code, $description, $intern_id, $day, $time]);

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
            $stmt->execute([$intern_id]);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['courses'] = $courses;
            break;

        case 'create_session':
            $course_id = intval($_POST['course_id'] ?? 0);
            $session_date = trim($_POST['session_date'] ?? '');
            $session_time = trim($_POST['session_time'] ?? '');
            $code_expiry = intval($_POST['code_expiry'] ?? 2);

            if ($course_id <= 0 || empty($session_date) || empty($session_time)) {
                throw new Exception('All fields are required');
            }

            $stmt = $conn->prepare("SELECT id FROM courses WHERE id = ? AND faculty_id = ?");
            $stmt->execute([$course_id, $intern_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Course not found or unauthorized');
            }

            do {
                $attendance_code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
                $stmt = $conn->prepare("SELECT id FROM sessions WHERE attendance_code = ?");
                $stmt->execute([$attendance_code]);
            } while ($stmt->fetch());

            $code_expires_at = date('Y-m-d H:i:s', strtotime($session_date . ' +' . $code_expiry . ' hours'));

            $stmt = $conn->prepare("
                INSERT INTO sessions (course_id, session_date, session_time, attendance_code, code_expires_at, created_by, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([$course_id, $session_date, $session_time, $attendance_code, $code_expires_at, $intern_id]);

            $response['success'] = true;
            $response['message'] = 'Session created successfully!';
            $response['code'] = $attendance_code;
            break;

        case 'get_sessions':
            $stmt = $conn->prepare("
                SELECT 
                    s.*,
                    c.course_name,
                    c.course_code,
                    COUNT(DISTINCT e.user_id) as total_enrolled,
                    COUNT(DISTINCT a.student_id) as present_count
                FROM sessions s
                INNER JOIN courses c ON s.course_id = c.id
                LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'approved'
                LEFT JOIN attendance a ON s.id = a.session_id AND a.status = 'present'
                WHERE c.faculty_id = ?
                GROUP BY s.id
                ORDER BY s.session_date DESC, s.created_at DESC
            ");
            $stmt->execute([$intern_id]);
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['sessions'] = $sessions;
            break;

        case 'get_session_attendance':
            $session_id = intval($_POST['session_id'] ?? 0);

            if ($session_id <= 0) {
                throw new Exception('Invalid session ID');
            }

            $stmt = $conn->prepare("
                SELECT s.*, c.course_name, c.course_code 
                FROM sessions s
                INNER JOIN courses c ON s.course_id = c.id
                WHERE s.id = ? AND c.faculty_id = ?
            ");
            $stmt->execute([$session_id, $intern_id]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$session) {
                throw new Exception('Session not found or unauthorized');
            }

            $stmt = $conn->prepare("
                SELECT 
                    u.id as student_id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    COALESCE(a.status, 'absent') as status,
                    a.marked_at
                FROM enrollments e
                INNER JOIN users u ON e.user_id = u.id
                LEFT JOIN attendance a ON a.session_id = ? AND a.student_id = u.id
                WHERE e.course_id = ? AND e.status = 'approved'
                ORDER BY u.first_name, u.last_name
            ");
            $stmt->execute([$session_id, $session['course_id']]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['session'] = $session;
            $response['students'] = $students;
            break;

        case 'mark_attendance_manual':
            $session_id = intval($_POST['session_id'] ?? 0);
            $student_id = intval($_POST['student_id'] ?? 0);
            $status = trim($_POST['status'] ?? 'present');

            if ($session_id <= 0 || $student_id <= 0) {
                throw new Exception('Invalid session or student ID');
            }

            $stmt = $conn->prepare("
                SELECT s.course_id 
                FROM sessions s
                INNER JOIN courses c ON s.course_id = c.id
                WHERE s.id = ? AND c.faculty_id = ?
            ");
            $stmt->execute([$session_id, $intern_id]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$session) {
                throw new Exception('Session not found or unauthorized');
            }

            $stmt = $conn->prepare("
                INSERT INTO attendance (session_id, student_id, status, marked_by, marked_at) 
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    status = VALUES(status),
                    marked_by = VALUES(marked_by),
                    marked_at = NOW()
            ");
            $stmt->execute([$session_id, $student_id, $status, $intern_id]);

            $response['success'] = true;
            $response['message'] = 'Attendance marked successfully!';
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
            $stmt->execute([$intern_id]);
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
            $stmt->execute([$request_id, $intern_id]);
            
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
            $stmt->execute([$request_id, $intern_id]);
            
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
            $stmt->execute([$course_id, $intern_id]);
            
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