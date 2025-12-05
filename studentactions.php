<?php
session_start();
require_once 'config.php';

requireRole('student');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $action = $_POST['action'] ?? '';
    $user_id = $_SESSION['user_id'];

    switch ($action) {
        case 'request_enrollment':
            $course_id = intval($_POST['course_id'] ?? 0);
            $enrollment_type = trim($_POST['enrollment_type'] ?? '');
            $message = trim($_POST['message'] ?? '');

            if ($course_id <= 0) {
                throw new Exception('Invalid course ID');
            }

            if (!in_array($enrollment_type, ['auditor', 'student'])) {
                throw new Exception('Invalid enrollment type');
            }

            $stmt = $conn->prepare("SELECT id FROM courses WHERE id = ?");
            $stmt->execute([$course_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Course not found');
            }

            $stmt = $conn->prepare("
                SELECT id, status 
                FROM enrollments 
                WHERE user_id = ? AND course_id = ?
            ");
            $stmt->execute([$user_id, $course_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                if ($existing['status'] === 'pending') {
                    throw new Exception('You already have a pending request for this course');
                } elseif ($existing['status'] === 'approved') {
                    throw new Exception('You are already enrolled in this course');
                } elseif ($existing['status'] === 'rejected') {
                    $stmt = $conn->prepare("
                        UPDATE enrollments 
                        SET enrollment_type = ?, request_message = ?, status = 'pending', updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$enrollment_type, $message, $existing['id']]);
                    
                    $response['success'] = true;
                    $response['message'] = 'Request resubmitted successfully! Waiting for faculty approval.';
                    break;
                }
            }

            $stmt = $conn->prepare("
                INSERT INTO enrollments (user_id, course_id, enrollment_type, request_message, status) 
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$user_id, $course_id, $enrollment_type, $message]);

            $response['success'] = true;
            $response['message'] = 'Request sent successfully! Waiting for faculty approval.';
            break;

        case 'mark_attendance':
        
            $code = strtoupper(trim($_POST['code'] ?? ''));

            if (empty($code) || strlen($code) !== 6) {
                throw new Exception('Please enter a valid 6-digit code');
            }

        
            $stmt = $conn->prepare("
                SELECT s.*, c.id as course_id, c.course_name 
                FROM sessions s
                INNER JOIN courses c ON s.course_id = c.id
                WHERE s.attendance_code = ? 
                AND s.status = 'active'
                AND s.code_expires_at > NOW()
            ");
            $stmt->execute([$code]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$session) {
                throw new Exception('Invalid or expired attendance code');
            }

            
            $stmt = $conn->prepare("
                SELECT id FROM enrollments 
                WHERE user_id = ? AND course_id = ? AND status = 'approved'
            ");
            $stmt->execute([$user_id, $session['course_id']]);

            if (!$stmt->fetch()) {
                throw new Exception('You are not enrolled in this course');
            }

            
            $stmt = $conn->prepare("
                SELECT id FROM attendance 
                WHERE session_id = ? AND student_id = ?
            ");
            $stmt->execute([$session['id'], $user_id]);

            if ($stmt->fetch()) {
                throw new Exception('You have already marked attendance for this session');
            }

            
            $stmt = $conn->prepare("
                INSERT INTO attendance (session_id, student_id, status, marked_at) 
                VALUES (?, ?, 'present', NOW())
            ");
            $stmt->execute([$session['id'], $user_id]);

            $response['success'] = true;
            $response['message'] = 'Attendance marked successfully for ' . $session['course_name'] . '!';
            break;

        case 'get_attendance_report':
            
            
            
            $stmt = $conn->prepare("
                SELECT 
                    COUNT(DISTINCT s.id) as total_sessions,
                    COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.id END) as attended,
                    COUNT(DISTINCT CASE WHEN a.status IS NULL OR a.status = 'absent' THEN s.id END) as missed
                FROM sessions s
                INNER JOIN courses c ON s.course_id = c.id
                INNER JOIN enrollments e ON c.id = e.course_id
                LEFT JOIN attendance a ON s.id = a.session_id AND a.student_id = ?
                WHERE e.user_id = ? AND e.status = 'approved'
            ");
            $stmt->execute([$user_id, $user_id]);
            $overall = $stmt->fetch(PDO::FETCH_ASSOC);

            $overall['percentage'] = $overall['total_sessions'] > 0 
                ? round(($overall['attended'] / $overall['total_sessions']) * 100) 
                : 0;

            
            $stmt = $conn->prepare("
                SELECT 
                    c.course_name,
                    c.course_code,
                    COUNT(DISTINCT s.id) as total,
                    COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.id END) as attended
                FROM courses c
                INNER JOIN enrollments e ON c.id = e.course_id
                LEFT JOIN sessions s ON c.id = s.course_id
                LEFT JOIN attendance a ON s.id = a.session_id AND a.student_id = ?
                WHERE e.user_id = ? AND e.status = 'approved'
                GROUP BY c.id, c.course_name, c.course_code
                HAVING total > 0
                ORDER BY c.course_name
            ");
            $stmt->execute([$user_id, $user_id]);
            $by_course = $stmt->fetchAll(PDO::FETCH_ASSOC);

            
            foreach ($by_course as &$course) {
                $course['percentage'] = $course['total'] > 0 
                    ? round(($course['attended'] / $course['total']) * 100) 
                    : 0;
            }

            
            $stmt = $conn->prepare("
                SELECT 
                    s.session_date,
                    s.session_time,
                    c.course_name,
                    c.course_code,
                    COALESCE(a.status, 'absent') as status,
                    a.marked_at
                FROM sessions s
                INNER JOIN courses c ON s.course_id = c.id
                INNER JOIN enrollments e ON c.id = e.course_id
                LEFT JOIN attendance a ON s.id = a.session_id AND a.student_id = ?
                WHERE e.user_id = ? AND e.status = 'approved'
                ORDER BY s.session_date DESC, s.created_at DESC
                LIMIT 10
            ");
            $stmt->execute([$user_id, $user_id]);
            $recent_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['overall'] = $overall;
            $response['by_course'] = $by_course;
            $response['recent_sessions'] = $recent_sessions;
            break;

        case 'get_my_courses':
            $stmt = $conn->prepare("
                SELECT 
                    c.id,
                    c.course_name,
                    c.course_code,
                    c.description,
                    c.day,
                    c.time,
                    e.enrollment_type,
                    e.created_at as enrolled_at
                FROM courses c
                INNER JOIN enrollments e ON c.id = e.course_id
                WHERE e.user_id = ? AND e.status = 'approved'
                ORDER BY e.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['courses'] = $courses;
            break;

        case 'get_available_courses':
            $stmt = $conn->prepare("
                SELECT 
                    c.id,
                    c.course_name,
                    c.course_code,
                    c.description,
                    c.day,
                    c.time,
                    u.first_name as faculty_first_name,
                    u.last_name as faculty_last_name
                FROM courses c
                LEFT JOIN users u ON c.faculty_id = u.id
                WHERE c.id NOT IN (
                    SELECT course_id FROM enrollments WHERE user_id = ?
                )
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['courses'] = $courses;
            break;

        case 'get_pending_requests':
            $stmt = $conn->prepare("
                SELECT 
                    e.id,
                    e.enrollment_type,
                    e.request_message,
                    e.created_at,
                    c.course_name,
                    c.course_code
                FROM enrollments e
                INNER JOIN courses c ON e.course_id = c.id
                WHERE e.user_id = ? AND e.status = 'pending'
                ORDER BY e.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['requests'] = $requests;
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