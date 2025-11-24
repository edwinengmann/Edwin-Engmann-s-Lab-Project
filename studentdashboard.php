<?php
session_start();
require_once 'config.php';

requireRole('student');

$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_id = $_SESSION['user_id'];


$stmt = $conn->prepare("
    SELECT c.*, e.enrollment_type, e.created_at as enrolled_at
    FROM courses c
    INNER JOIN enrollments e ON c.id = e.course_id
    WHERE e.user_id = ? AND e.status = 'approved'
    ORDER BY e.created_at DESC
");
$stmt->execute([$user_id]);
$enrolled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmt = $conn->prepare("
    SELECT c.*, u.first_name as faculty_first_name, u.last_name as faculty_last_name
    FROM courses c
    LEFT JOIN users u ON c.faculty_id = u.id
    WHERE c.id NOT IN (
        SELECT course_id FROM enrollments WHERE user_id = ?
    )
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$available_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmt = $conn->prepare("
    SELECT e.*, c.course_name, c.course_code
    FROM enrollments e
    INNER JOIN courses c ON e.course_id = c.id
    WHERE e.user_id = ? AND e.status = 'pending'
    ORDER BY e.created_at DESC
");
$stmt->execute([$user_id]);
$pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Ashesi Attendance System</title>
    <link rel="stylesheet" href="student.css">
    <style>
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: #E63946;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
        }
        .logout-btn:hover {
            background-color: #B22222;
        }
        .course-card {
            background: #f8f9fa;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 4px solid #457B9D;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .course-card h4 {
            margin: 0 0 10px 0;
            color: #1D3557;
            font-size: 1.3em;
        }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
            background: #E63946;
            color: white;
            margin-left: 10px;
        }
        .badge-success {
            background: #28a745;
        }
        .badge-warning {
            background: #ffc107;
            color: #333;
        }
        .badge-info {
            background: #17a2b8;
        }
        .alert {
            padding: 15px;
            margin: 15px auto;
            border-radius: 5px;
            max-width: 660px;
            text-align: center;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .empty-state-icon {
            font-size: 3em;
            margin-bottom: 10px;
        }
        button.btn-join {
            background-color: #28a745;
        }
        button.btn-join:hover {
            background-color: #218838;
        }
        .stats-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stats-number {
            font-size: 2em;
            font-weight: bold;
            color: #E63946;
        }
        .schedule-table {
            width: 100%;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <a href="logout.php" class="logout-btn">Logout</a>

    <h1>Student Dashboard</h1>
    <p style="text-align: center; color: #1D3557; margin: 10px 0; font-size: 1.1em;">
        Welcome, <?php echo htmlspecialchars($user_name); ?>! 👋
    </p>

    <nav>
        <p>ASHESI ATTENDANCE MANAGEMENT SYSTEM</p>
    </nav>

    <h2>Sections</h2>
    <ul>
        <li><a href="#my-courses"><b>My Courses</b></a></li>
        <li><a href="#schedule"><b>Session Schedule</b></a></li>
        <li><a href="#available"><b>Join New Courses</b></a></li>
        <li><a href="#grades"><b>Grades/Reports</b></a></li>
    </ul>

    <hr>

    <div id="alertContainer"></div>

    
    <section id="my-courses">
        <h3>My Courses</h3>
        <p><b>List of enrolled courses:</b></p>
        
        <?php if (count($enrolled_courses) > 0): ?>
            <ul>
                <?php foreach ($enrolled_courses as $course): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($course['course_name']); ?></strong>
                        <span class="badge"><?php echo htmlspecialchars($course['course_code']); ?></span>
                        <span class="badge badge-success"><?php echo ucfirst(htmlspecialchars($course['enrollment_type'])); ?></span>
                        <?php if ($course['description']): ?>
                            <br><small><?php echo htmlspecialchars($course['description']); ?></small>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📚</div>
                <p>You are not enrolled in any courses yet.</p>
            </div>
        <?php endif; ?>

        <?php if (count($pending_requests) > 0): ?>
            <p><b>⏳ Pending Approval:</b></p>
            <ul>
                <?php foreach ($pending_requests as $request): ?>
                    <li>
                        <?php echo htmlspecialchars($request['course_name']); ?>
                        <span class="badge badge-warning">Waiting for Approval</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    
    <section id="schedule">
        <h3>Session Schedule</h3>
        <?php if (count($enrolled_courses) > 0): ?>
            <table border="1" cellpadding="5" class="schedule-table">
                <tr>
                    <th>Course</th>
                    <th>Day</th>
                    <th>Time</th>
                    <th>Type</th>
                </tr>
                <?php foreach ($enrolled_courses as $course): ?>
                    <?php if ($course['day'] && $course['time']): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['day']); ?></td>
                            <td><?php echo htmlspecialchars($course['time']); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($course['enrollment_type'])); ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No schedule available. Enroll in courses to see your schedule.</p>
        <?php endif; ?>
    </section>

    
    <section id="available">
        <h3>Available Courses</h3>
        <p><b>Option to join new courses:</b></p>
        
        <?php if (count($available_courses) > 0): ?>
            <?php foreach ($available_courses as $course): ?>
                <div class="course-card" style="border-left-color: #17a2b8;">
                    <h4>
                        <?php echo htmlspecialchars($course['course_name']); ?>
                        <span class="badge badge-info"><?php echo htmlspecialchars($course['course_code']); ?></span>
                    </h4>
                    
                    <?php if ($course['description']): ?>
                        <p><?php echo htmlspecialchars($course['description']); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($course['day'] && $course['time']): ?>
                        <p><b>📅 Schedule:</b> <?php echo htmlspecialchars($course['day'] . ' at ' . $course['time']); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($course['faculty_first_name']): ?>
                        <p><b>👨‍🏫 Faculty:</b> <?php echo htmlspecialchars($course['faculty_first_name'] . ' ' . $course['faculty_last_name']); ?></p>
                    <?php endif; ?>
                    
                    <button type="button" onclick="joinCourse(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['course_name'], ENT_QUOTES); ?>', 'auditor')" style="margin-right: 10px;">
                        Join as <b>Auditor</b>
                    </button>
                    <button type="button" onclick="joinCourse(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['course_name'], ENT_QUOTES); ?>', 'observer')">
                        Join as <b>Observer</b>
                    </button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">✅</div>
                <p>No more courses available at this time.</p>
            </div>
        <?php endif; ?>
    </section>

    
    <section id="grades">
        <h3>Grades / Reports</h3>
        <?php if (count($enrolled_courses) > 0): ?>
            <ul>
                <?php foreach ($enrolled_courses as $course): ?>
                    <li>
                        <?php echo htmlspecialchars($course['course_name']); ?> — 
                        <span style="color: #666; font-style: italic;">Grade pending</span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p><em>Note: Grades will be updated by faculty members.</em></p>
        <?php else: ?>
            <p>No enrolled courses. Enroll in courses to see your grades.</p>
        <?php endif; ?>
    </section>

    <script>
        
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            alertContainer.appendChild(alert);
            
            
            setTimeout(() => alert.remove(), 5000);
        }

        
        function joinCourse(courseId, courseName, enrollmentType) {
            
            const confirmMsg = `Do you want to join "${courseName}" as ${enrollmentType.toUpperCase()}?`;
            if (!confirm(confirmMsg)) {
                return;
            }

            
            const message = prompt('Add a message for the faculty (optional):');

            
            const formData = new URLSearchParams();
            formData.append('action', 'request_enrollment');
            formData.append('course_id', courseId);
            formData.append('enrollment_type', enrollmentType);
            formData.append('message', message || '');

            
            fetch('student_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('✅ ' + data.message, 'success');
                    
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert('❌ ' + data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('❌ Error sending request. Please try again.', 'error');
                console.error('Error:', error);
            });
        }

        
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>