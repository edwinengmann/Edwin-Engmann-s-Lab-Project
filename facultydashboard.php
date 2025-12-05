<?php
session_start();
require_once 'config.php';

requireRole('Faculty');

$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Faculty Dashboard</title>
  <link rel="stylesheet" href="faculty.css">
</head>
<body>

  <div class="sidebar">
    <div class="sidebar-header">
      <h2> <?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></h2>
      <p><?php echo htmlspecialchars($user_email); ?></p>
    </div>
    
    <ul class="sidebar-menu">
      <li><a href="#" onclick="showSection('profile'); return false;" class="menu-link active" data-section="profile"> Profile</a></li>
      <li><a href="#" onclick="showSection('course'); return false;" class="menu-link" data-section="course">My Courses</a></li>
      <li><a href="#" onclick="showSection('sessions'); return false;" class="menu-link" data-section="sessions"> Sessions</a></li>
      <li><a href="#" onclick="showSection('requests'); return false;" class="menu-link" data-section="requests"> Student Requests</a></li>
      <li><a href="#" onclick="showSection('report'); return false;" class="menu-link" data-section="report"> Reports</a></li>
    </ul>
    
    <div class="sidebar-footer">
      <a href="logout.php" class="logout-btn">Log Out</a>
    </div>
  </div>


  <div class="container">
    <header>
      <h1>FACULTY DASHBOARD</h1>
      <p style="text-align: center; color: #1D3557; margin: 10px 0;">Welcome, <?php echo htmlspecialchars($user_name); ?>!</p>
    </header>

    <div id="alertContainer"></div>


    <section id="profile" class="content-section active">
      <h2>Profile Information</h2>
      <p><strong>Name:</strong> <?php echo htmlspecialchars($user_name); ?></p>
      <p><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
      <p><strong>Role:</strong> Faculty</p>
    </section>

    
    <section id="course" class="content-section">
      <h2>My Courses</h2>
      <button onclick="openCreateModal()">Create New Course</button>
      <div id="coursesContainer" style="margin-top: 20px;">
          <p>Loading courses...</p>
      </div>
    </section>

    
    <section id="sessions" class="content-section">
      <h2>Attendance Sessions</h2>
      <button onclick="openSessionModal()">Create New Session</button>
      <button onclick="loadSessions()" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">Refresh Sessions</button>
      <div id="sessionsContainer" style="margin-top: 20px;">
          <p>Loading sessions...</p>
      </div>
    </section>

    
    <section id="requests" class="content-section">
      <h2>Student Join Requests</h2>
      <button onclick="loadRequests()">Refresh Requests</button>
      <div id="requestsContainer" style="margin-top: 20px;">
          <p>Loading requests...</p>
      </div>
    </section>

    
    <section id="report" class="content-section">
      <h2>Course Statistics</h2>
      <div id="statsContainer">
          <p>Loading statistics...</p>
      </div>
    </section>
  </div>

  
  <div id="createModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeCreateModal()">&times;</span>
      <h2>Create New Course</h2>
      <form id="createCourseForm">
        <div class="form-group">
          <label for="courseName">Course Name*:</label>
          <input type="text" id="courseName" name="course_name" required>
        </div>
        <div class="form-group">
          <label for="courseCode">Course Code*:</label>
          <input type="text" id="courseCode" name="course_code" required>
        </div>
        <div class="form-group">
          <label for="description">Description:</label>
          <textarea id="description" name="description" rows="3"></textarea>
        </div>
        <div class="form-group">
          <label for="day">Day:</label>
          <select id="day" name="day">
            <option value="">Select Day</option>
            <option value="Monday">Monday</option>
            <option value="Tuesday">Tuesday</option>
            <option value="Wednesday">Wednesday</option>
            <option value="Thursday">Thursday</option>
            <option value="Friday">Friday</option>
          </select>
        </div>
        <div class="form-group">
          <label for="time">Time:</label>
          <input type="text" id="time" name="time" placeholder="e.g., 10:00 AM - 11:30 AM">
        </div>
        <button type="submit" style="width: 100%;">Create Course</button>
      </form>
    </div>
  </div>


  <div id="sessionModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeSessionModal()">&times;</span>
      <h2>Create Attendance Session</h2>
      <form id="createSessionForm">
        <div class="form-group">
          <label for="sessionCourse">Select Course*:</label>
          <select id="sessionCourse" name="course_id" required>
            <option value="">Select a course</option>
          </select>
        </div>
        <div class="form-group">
          <label for="sessionDate">Session Date*:</label>
          <input type="date" id="sessionDate" name="session_date" required>
        </div>
        <div class="form-group">
          <label for="sessionTime">Session Time*:</label>
          <input type="text" id="sessionTime" name="session_time" placeholder="e.g., 10:00 AM - 11:30 AM" required>
        </div>
        <div class="form-group">
          <label for="codeExpiry">Code Valid For (minutes):</label>
          <input type="number" id="codeExpiry" name="code_expiry" value="2" min="1" max="20" required>
          <small style="color: #666;">How long should the attendance code be valid?</small>
        </div>
        <button type="submit" style="width: 100%;">Create Session & Generate Code</button>
      </form>
    </div>
  </div>

  
  <div id="attendanceModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
      <span class="close" onclick="closeAttendanceModal()">&times;</span>
      <h2 id="attendanceModalTitle">Session Attendance</h2>
      <div id="attendanceDetails" style="margin: 20px 0;">
        <p><strong>Course:</strong> <span id="modalCourseName"></span></p>
        <p><strong>Date:</strong> <span id="modalSessionDate"></span></p>
        <p><strong>Time:</strong> <span id="modalSessionTime"></span></p>
        <p><strong>Attendance Code:</strong> <span id="modalCode" style="font-size: 1.5em; color: #E63946; font-weight: bold;"></span></p>
      </div>
      <h3>Student Attendance</h3>
      <div id="attendanceList" style="margin-top: 20px;">
          <p>Loading attendance...</p>
      </div>
    </div>
  </div>

  <script>
    let currentSessionId = null;

    
    function showSection(sectionId) {
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active');
        });
        document.querySelectorAll('.menu-link').forEach(link => {
            link.classList.remove('active');
        });
        document.getElementById(sectionId).classList.add('active');
        document.querySelector(`[data-section="${sectionId}"]`).classList.add('active');
        
    
        if (sectionId === 'sessions') {
            loadSessions();
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

    
    const createModal = document.getElementById('createModal');
    
    function openCreateModal() {
        createModal.style.display = 'block';
    }
    
    function closeCreateModal() {
        createModal.style.display = 'none';
        document.getElementById('createCourseForm').reset();
    }


    const sessionModal = document.getElementById('sessionModal');
    
    function openSessionModal() {
        sessionModal.style.display = 'block';
        loadCoursesForSession();
    }
    
    function closeSessionModal() {
        sessionModal.style.display = 'none';
        document.getElementById('createSessionForm').reset();
    }

    const attendanceModal = document.getElementById('attendanceModal');
    
    function openAttendanceModal(sessionId) {
        currentSessionId = sessionId;
        attendanceModal.style.display = 'block';
        loadSessionAttendance(sessionId);
    }
    
    function closeAttendanceModal() {
        attendanceModal.style.display = 'none';
        currentSessionId = null;
    }

    
    window.onclick = (event) => {
        if (event.target == createModal) closeCreateModal();
        if (event.target == sessionModal) closeSessionModal();
        if (event.target == attendanceModal) closeAttendanceModal();
    };

    
    document.getElementById('createCourseForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'create_course');

        fetch('facultyactions.php', {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                closeCreateModal();
                loadCourses();
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => showAlert('Error creating course', 'error'));
    });

    
    document.getElementById('createSessionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'create_session');

        fetch('facultyactions.php', {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Session created! Code: ' + data.code, 'success');
                closeSessionModal();
                loadSessions();
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => showAlert('Error creating session', 'error'));
    });

    
    function loadCourses() {
        fetch('facultyactions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_courses'
        })
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('coursesContainer');
            if (data.success && data.courses.length > 0) {
                container.innerHTML = data.courses.map(course => `
                    <div class="course-item">
                        <h4>${course.course_name} <span class="badge">${course.course_code}</span></h4>
                        ${course.description ? `<p>${course.description}</p>` : ''}
                        ${course.day ? `<p><strong>Schedule:</strong> ${course.day} at ${course.time}</p>` : ''}
                        <p><strong>Created:</strong> ${new Date(course.created_at).toLocaleDateString()}</p>
                        <button onclick="deleteCourse(${course.id}, '${course.course_name}')" class="btn-reject">Delete Course</button>
                    </div>
                `).join('');
                loadStats(data.courses.length);
            } else {
                container.innerHTML = '<p>You have not created any courses yet.</p>';
                loadStats(0);
            }
        })
        .catch(error => showAlert('Error loading courses', 'error'));
    }

    
    function loadCoursesForSession() {
        fetch('facultyactions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_courses'
        })
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('sessionCourse');
            if (data.success && data.courses.length > 0) {
                select.innerHTML = '<option value="">Select a course</option>' + 
                    data.courses.map(course => 
                        `<option value="${course.id}">${course.course_name} (${course.course_code})</option>`
                    ).join('');
            } else {
                select.innerHTML = '<option value="">No courses available</option>';
            }
        });
    }

    function loadSessions() {
        fetch('facultyactions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_sessions'
        })
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('sessionsContainer');
            if (data.success && data.sessions.length > 0) {
                container.innerHTML = data.sessions.map(session => `
                    <div class="course-item">
                        <h4>
                            ${session.course_name} 
                            <span class="badge badge-info">${session.course_code}</span>
                            ${session.status === 'active' ? '<span class="badge badge-success">Active</span>' : '<span class="badge">Completed</span>'}
                        </h4>
                        <p><strong>Date:</strong> ${new Date(session.session_date).toLocaleDateString()}</p>
                        <p><strong>Time:</strong> ${session.session_time}</p>
                        <p><strong>Code:</strong> <span style="font-size: 1.2em; color: #E63946; font-weight: bold;">${session.attendance_code}</span></p>
                        <p><strong>Code Expires:</strong> ${new Date(session.code_expires_at).toLocaleString()}</p>
                        <p><strong>Attendance:</strong> ${session.present_count} / ${session.total_enrolled} students</p>
                        <button onclick="openAttendanceModal(${session.id})">View Attendance</button>
                        ${session.status === 'active' ? `<button onclick="completeSession(${session.id})" style="background: linear-gradient(135deg, #28a745 0%, #218838 100%);">Mark Complete</button>` : ''}
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p>No sessions created yet.</p>';
            }
        })
        .catch(error => showAlert('Error loading sessions', 'error'));
    }

    
    function loadSessionAttendance(sessionId) {
        fetch('facultyactions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=get_session_attendance&session_id=${sessionId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                
                document.getElementById('modalCourseName').textContent = data.session.course_name;
                document.getElementById('modalSessionDate').textContent = new Date(data.session.session_date).toLocaleDateString();
                document.getElementById('modalSessionTime').textContent = data.session.session_time;
                document.getElementById('modalCode').textContent = data.session.attendance_code;

                
                const listContainer = document.getElementById('attendanceList');
                if (data.students.length > 0) {
                    listContainer.innerHTML = `
                        <table>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Marked At</th>
                                <th>Action</th>
                            </tr>
                            ${data.students.map(student => `
                                <tr>
                                    <td>${student.first_name} ${student.last_name}</td>
                                    <td>${student.email}</td>
                                    <td>
                                        ${student.status === 'present' 
                                            ? '<span class="badge badge-success">Present</span>' 
                                            : '<span class="badge badge-warning">Absent</span>'}
                                    </td>
                                    <td>${student.marked_at ? new Date(student.marked_at).toLocaleString() : '-'}</td>
                                    <td>
                                        ${student.status === 'present' 
                                            ? `<button onclick="markAbsent(${sessionId}, ${student.student_id})" class="btn-reject" style="padding: 6px 12px; font-size: 0.9em;">Mark Absent</button>`
                                            : `<button onclick="markPresent(${sessionId}, ${student.student_id})" class="btn-approve" style="padding: 6px 12px; font-size: 0.9em;">Mark Present</button>`}
                                    </td>
                                </tr>
                            `).join('')}
                        </table>
                    `;
                } else {
                    listContainer.innerHTML = '<p>No students enrolled in this course.</p>';
                }
            }
        })
        .catch(error => showAlert('Error loading attendance', 'error'));
    }


    function markPresent(sessionId, studentId) {
        fetch('facultyactions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=mark_attendance_manual&session_id=${sessionId}&student_id=${studentId}&status=present`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                loadSessionAttendance(sessionId);
                loadSessions();
            } else {
                showAlert(data.message, 'error');
            }
        });
    }

    
    function markAbsent(sessionId, studentId) {
        fetch('facultyactions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=mark_attendance_manual&session_id=${sessionId}&student_id=${studentId}&status=absent`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                loadSessionAttendance(sessionId);
                loadSessions();
            } else {
                showAlert(data.message, 'error');
            }
        });
    }


    function completeSession(sessionId) {
        if (!confirm('Mark this session as completed?')) return;
        
        fetch('facultyactions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=complete_session&session_id=${sessionId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                loadSessions();
            } else {
                showAlert(data.message, 'error');
            }
        });
    }

    
    function loadRequests() {
        fetch('facultyactions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_requests'
        })
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('requestsContainer');
            if (data.success && data.requests.length > 0) {
                container.innerHTML = data.requests.map(req => `
                    <div class="request-card">
                        <h4>${req.first_name} ${req.last_name}</h4>
                        <p><strong>Email:</strong> ${req.email}</p>
                        <p><strong>Course:</strong> ${req.course_name} (${req.course_code})</p>
                        <p><strong>Type:</strong> ${req.enrollment_type}</p>
                        ${req.request_message ? `<p><strong>Message:</strong> ${req.request_message}</p>` : ''}
                        <p><strong>Requested:</strong> ${new Date(req.created_at).toLocaleDateString()}</p>
                        <div class="request-actions">
                            <button onclick="approveRequest(${req.id})" class="btn-approve">Approve</button>
                            <button onclick="rejectRequest(${req.id})" class="btn-reject">Reject</button>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p>No pending requests at this time.</p>';
            }
        })
        .catch(error => showAlert('Error loading requests', 'error'));
    }

    
    function approveRequest(requestId) {
        if (!confirm('Are you sure you want to approve this request?')) return;
        
        fetch('facultyactions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=approve_request&request_id=${requestId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                loadRequests();
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => showAlert('Error approving request', 'error'));
    }


    function rejectRequest(requestId) {
        if (!confirm('Are you sure you want to reject this request?')) return;
        
        fetch('facultyactions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=reject_request&request_id=${requestId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                loadRequests();
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => showAlert('Error rejecting request', 'error'));
    }

    
    function deleteCourse(courseId, courseName) {
        if (!confirm(`Are you sure you want to delete "${courseName}"? This will remove all enrollments.`)) return;
        
        fetch('facultyactions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_course&course_id=${courseId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                loadCourses();
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => showAlert('Error deleting course', 'error'));
    }


    function loadStats(courseCount) {
        const container = document.getElementById('statsContainer');
        container.innerHTML = `
            <p><strong>Total Courses Created:</strong> ${courseCount}</p>
            <p><em>More statistics coming soon...</em></p>
        `;
    }

    
    window.onload = function() {
        loadCourses();
        loadRequests();
    };
  </script>
</body>
</html>