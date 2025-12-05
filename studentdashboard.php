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
</head>
<body>
    
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></h2>
            <p><?php echo htmlspecialchars($user_email); ?></p>
        </div>
        
        <ul class="sidebar-menu">
            <li><a href="#" onclick="showSection('dashboard'); return false;" class="menu-link active" data-section="dashboard"> Dashboard</a></li>
            <li><a href="#" onclick="showSection('my-courses'); return false;" class="menu-link" data-section="my-courses">My Courses</a></li>
            <li><a href="#" onclick="showSection('mark-attendance'); return false;" class="menu-link" data-section="mark-attendance"> Mark Attendance</a></li>
            <li><a href="#" onclick="showSection('attendance-report'); return false;" class="menu-link" data-section="attendance-report">Attendance Report</a></li>
            <li><a href="#" onclick="showSection('schedule'); return false;" class="menu-link" data-section="schedule"> Schedule</a></li>
            <li><a href="#" onclick="showSection('available'); return false;" class="menu-link" data-section="available"> Join Courses</a></li>
            <li><a href="#" onclick="showSection('grades'); return false;" class="menu-link" data-section="grades">Grades</a></li>
        </ul>
        
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn">Log Out</a>
        </div>
    </div>

    
    <div class="main-content">
        <h1>ASHESI ATTENDANCE SYSTEM</h1>
        
        <div id="alertContainer"></div>

    
        <div id="dashboard" class="content-section active">
            <section>
                <h3>Welcome to Your Dashboard</h3>
                <p style="font-size: 1.1em; color: #666;">Hello <strong><?php echo htmlspecialchars($user_name); ?></strong>! Here's an overview of your account.</p>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 25px;">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo count($enrolled_courses); ?></div>
                        <p style="margin: 5px 0; color: #666; font-weight: 600;">Enrolled Courses</p>
                    </div>
                    <div class="stats-card">
                        <div class="stats-number"><?php echo count($pending_requests); ?></div>
                        <p style="margin: 5px 0; color: #666; font-weight: 600;">Pending Requests</p>
                    </div>
                    <div class="stats-card">
                        <div class="stats-number" id="overallAttendance">--%</div>
                        <p style="margin: 5px 0; color: #666; font-weight: 600;">Overall Attendance</p>
                    </div>
                </div>
            </section>
        </div>

        
        <div id="my-courses" class="content-section">
            <section>
                <h3>My Enrolled Courses</h3>
                
                <?php if (count($enrolled_courses) > 0): ?>
                    <?php foreach ($enrolled_courses as $course): ?>
                        <div class="course-card">
                            <h4>
                                <?php echo htmlspecialchars($course['course_name']); ?>
                                <span class="badge"><?php echo htmlspecialchars($course['course_code']); ?></span>
                                <span class="badge badge-success"><?php echo ucfirst(htmlspecialchars($course['enrollment_type'])); ?></span>
                            </h4>
                            <?php if ($course['description']): ?>
                                <p><?php echo htmlspecialchars($course['description']); ?></p>
                            <?php endif; ?>
                            <?php if ($course['day'] && $course['time']): ?>
                                <p><strong> Schedule:</strong> <?php echo htmlspecialchars($course['day'] . ' at ' . $course['time']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"></div>
                        <p>You are not enrolled in any courses yet.</p>
                    </div>
                <?php endif; ?>

                <?php if (count($pending_requests) > 0): ?>
                    <h3 style="margin-top: 30px;">⏳ Pending Approval</h3>
                    <?php foreach ($pending_requests as $request): ?>
                        <div class="course-card" style="border-left-color: #ffc107;">
                            <h4>
                                <?php echo htmlspecialchars($request['course_name']); ?>
                                <span class="badge badge-warning">Waiting for Approval</span>
                            </h4>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>

    
        <div id="mark-attendance" class="content-section">
            <section>
                <h3>Mark Your Attendance</h3>
                <p style="color: #666; margin-bottom: 20px;">Enter the 6-digit code provided by your instructor to mark your attendance.</p>
                
                <div style="max-width: 500px; margin: 0 auto;">
                    <div style="background: #f8f9fa; padding: 30px; border-radius: 12px; border-left: 5px solid #E63946;">
                        <label for="attendanceCode" style="display: block; margin-bottom: 10px; font-weight: bold; font-size: 1.1em;">Attendance Code:</label>
                        <input 
                            type="text" 
                            id="attendanceCode" 
                            placeholder="Enter 6-digit code" 
                            maxlength="6"
                            style="width: 100%; padding: 15px; font-size: 1.5em; text-align: center; text-transform: uppercase; border: 2px solid #ddd; border-radius: 8px; letter-spacing: 5px; font-weight: bold;"
                        >
                        <button onclick="markAttendance()" style="width: 100%; margin-top: 20px; padding: 15px; font-size: 1.1em;">
                            Submit Attendance Code
                        </button>
                    </div>

                    <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-left: 5px solid #ffc107; border-radius: 8px;">
                        <h4 style="color: #856404; margin-bottom: 10px;"> Instructions:</h4>
                        <ul style="color: #856404; padding-left: 20px;">
                            <li>Get the code from your instructor during class</li>
                            <li>Enter the 6-digit code exactly as shown</li>
                            <li>Submit before the code expires</li>
                            <li>You can only mark attendance once per session</li>
                        </ul>
                    </div>
                </div>
            </section>
        </div>

        
        <div id="attendance-report" class="content-section">
            <section>
                <h3>My Attendance Report</h3>
                <button onclick="loadAttendanceReport()" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">Refresh Report</button>
                
                
                <div id="attendanceStatsContainer" style="margin-top: 20px;">
                    <p>Loading attendance statistics...</p>
                </div>

                
                <h3 style="margin-top: 30px;"> Attendance by Course</h3>
                <div id="courseAttendanceContainer" style="margin-top: 20px;">
                    <p>Loading course attendance...</p>
                </div>

            
                <h3 style="margin-top: 30px;">Recent Sessions</h3>
                <div id="recentSessionsContainer" style="margin-top: 20px;">
                    <p>Loading recent sessions...</p>
                </div>
            </section>
        </div>

        
        <div id="schedule" class="content-section">
            <section>
                <h3> My Class Schedule</h3>
                <?php if (count($enrolled_courses) > 0): ?>
                    <table class="schedule-table">
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
                    <div class="empty-state">
                        <div class="empty-state-icon"></div>
                        <p>No schedule available. Enroll in courses to see your schedule.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>

    
        <div id="available" class="content-section">
            <section>
                <h3>Join New Courses</h3>
                
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
                                <p><strong> Schedule:</strong> <?php echo htmlspecialchars($course['day'] . ' at ' . $course['time']); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($course['faculty_first_name']): ?>
                                <p><strong>Faculty:</strong> <?php echo htmlspecialchars($course['faculty_first_name'] . ' ' . $course['faculty_last_name']); ?></p>
                            <?php endif; ?>
                            
                            <button type="button" onclick="joinCourse(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['course_name'], ENT_QUOTES); ?>', 'auditor')" style="margin-right: 10px;">
                                Join as <b>Auditor</b>
                            </button>
                            <button type="button" onclick="joinCourse(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['course_name'], ENT_QUOTES); ?>', 'student')">
                                Join as <b>Student</b>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"></div>
                        <p>No more courses available at this time.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        
        <div id="grades" class="content-section">
            <section>
                <h3>Grades / Reports</h3>
                <?php if (count($enrolled_courses) > 0): ?>
                    <ul>
                        <?php foreach ($enrolled_courses as $course): ?>
                            <li>
                                <?php echo htmlspecialchars($course['course_name']); ?> – 
                                <span style="color: #666; font-style: italic;">Grade pending</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p><em>Note: Grades will be updated by faculty members.</em></p>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"></div>
                        <p>No enrolled courses. Enroll in courses to see your grades.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <script>
        
        function showSection(sectionId) {
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            document.querySelectorAll('.menu-link').forEach(link => {
                link.classList.remove('active');
            });
            
            document.getElementById(sectionId).classList.add('active');
            document.querySelector(`[data-section="${sectionId}"]`).classList.add('active');

            
            if (sectionId === 'attendance-report') {
                loadAttendanceReport();
            } else if (sectionId === 'dashboard') {
                loadOverallAttendance();
            }
        }

        
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            alertContainer.appendChild(alert);
            
            setTimeout(() => alert.remove(), 5000);
        }

        
        function markAttendance() {
            const code = document.getElementById('attendanceCode').value.trim().toUpperCase();
            
            if (code.length !== 6) {
                showAlert('Please enter a valid 6-digit code', 'error');
                return;
            }

            const formData = new URLSearchParams();
            formData.append('action', 'mark_attendance');
            formData.append('code', code);

            fetch('studentactions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(' ' + data.message, 'success');
                    document.getElementById('attendanceCode').value = '';
                    
                    setTimeout(() => {
                        loadAttendanceReport();
                        loadOverallAttendance();
                    }, 1000);
                } else {
                    showAlert(' ' + data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Error submitting code. Please try again.', 'error');
                console.error('Error:', error);
            });
        }

        
        function loadAttendanceReport() {
            fetch('studentactions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_attendance_report'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                
                    const statsContainer = document.getElementById('attendanceStatsContainer');
                    statsContainer.innerHTML = `
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                            <div class="stats-card">
                                <div class="stats-number">${data.overall.total_sessions}</div>
                                <p style="margin: 5px 0; color: #666; font-weight: 600;">Total Sessions</p>
                            </div>
                            <div class="stats-card">
                                <div class="stats-number" style="color: #28a745;">${data.overall.attended}</div>
                                <p style="margin: 5px 0; color: #666; font-weight: 600;">Sessions Attended</p>
                            </div>
                            <div class="stats-card">
                                <div class="stats-number" style="color: #dc3545;">${data.overall.missed}</div>
                                <p style="margin: 5px 0; color: #666; font-weight: 600;">Sessions Missed</p>
                            </div>
                            <div class="stats-card">
                                <div class="stats-number">${data.overall.percentage}%</div>
                                <p style="margin: 5px 0; color: #666; font-weight: 600;">Attendance Rate</p>
                            </div>
                        </div>
                    `;

                
                    const courseContainer = document.getElementById('courseAttendanceContainer');
                    if (data.by_course.length > 0) {
                        courseContainer.innerHTML = data.by_course.map(course => `
                            <div class="course-card">
                                <h4>
                                    ${course.course_name}
                                    <span class="badge">${course.course_code}</span>
                                    <span class="badge ${course.percentage >= 75 ? 'badge-success' : 'badge-warning'}">${course.percentage}%</span>
                                </h4>
                                <p><strong>Sessions:</strong> ${course.attended} attended out of ${course.total} sessions</p>
                                <div style="background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden; margin-top: 10px;">
                                    <div style="background: ${course.percentage >= 75 ? '#28a745' : '#ffc107'}; height: 100%; width: ${course.percentage}%; transition: width 0.3s;"></div>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        courseContainer.innerHTML = '<p>No attendance data available yet.</p>';
                    }

                
                    const recentContainer = document.getElementById('recentSessionsContainer');
                    if (data.recent_sessions.length > 0) {
                        recentContainer.innerHTML = `
                            <table>
                                <tr>
                                    <th>Course</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Marked At</th>
                                </tr>
                                ${data.recent_sessions.map(session => `
                                    <tr>
                                        <td>${session.course_name}</td>
                                        <td>${new Date(session.session_date).toLocaleDateString()}</td>
                                        <td>${session.session_time}</td>
                                        <td>
                                            ${session.status === 'present' 
                                                ? '<span class="badge badge-success">Present</span>' 
                                                : '<span class="badge badge-warning">Absent</span>'}
                                        </td>
                                        <td>${session.marked_at ? new Date(session.marked_at).toLocaleString() : '-'}</td>
                                    </tr>
                                `).join('')}
                            </table>
                        `;
                    } else {
                        recentContainer.innerHTML = '<p>No session records yet.</p>';
                    }
                }
            })
            .catch(error => console.error('Error loading attendance report:', error));
        }

        
        function loadOverallAttendance() {
            fetch('studentactions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_attendance_report'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('overallAttendance').textContent = data.overall.percentage + '%';
                }
            })
            .catch(error => console.error('Error loading overall attendance:', error));
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

            fetch('studentactions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(' ' + data.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert(' ' + data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Error sending request. Please try again.', 'error');
                console.error('Error:', error);
            });
        }

        
        document.getElementById('attendanceCode').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });

        
        document.getElementById('attendanceCode').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                markAttendance();
            }
        });

        
        window.onload = function() {
            loadOverallAttendance();
        };
    </script>
</body>
</html>