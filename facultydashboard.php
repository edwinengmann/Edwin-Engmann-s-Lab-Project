<?php
session_start();
require_once 'config.php';


requireRole('Faculty');

$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Faculty Dashboard</title>
  <link rel="stylesheet" href="faculty.css">
  <style>
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    .modal-content {
        background-color: #fff;
        margin: 5% auto;
        padding: 30px;
        border-radius: 10px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    .close:hover { color: #000; }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .form-group input, .form-group select, .form-group textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
    }
    .alert {
        padding: 12px;
        margin: 10px 0;
        border-radius: 5px;
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
    .request-card {
        background: #f8f9fa;
        padding: 15px;
        margin: 10px 0;
        border-radius: 8px;
        border-left: 4px solid #E63946;
    }
    .request-actions button {
        margin-right: 10px;
        margin-top: 10px;
    }
    .btn-approve {
        background: #28a745;
    }
    .btn-approve:hover {
        background: #218838;
    }
    .btn-reject {
        background: #dc3545;
    }
    .btn-reject:hover {
        background: #c82333;
    }
    .course-item {
        background: #f8f9fa;
        padding: 15px;
        margin: 10px 0;
        border-radius: 8px;
    }
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.85em;
        font-weight: bold;
        background: #E63946;
        color: white;
    }
  </style>
</head>
<body>
  <div class="container">
    <div style="position: absolute; top: 20px; right: 20px;">
        <a href="logout.php" style="background-color: #E63946; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold;">Logout</a>
    </div>
    
    <header>
      <h1>Faculty Dashboard</h1>
      <p style="text-align: center; color: #1D3557; margin: 10px 0;">Welcome, <?php echo htmlspecialchars($user_name); ?>!</p>
    </header>

    <div id="alertContainer"></div>

    <nav>
      <a href="#profile">Profile</a>
      <a href="#course">My Courses</a>
      <a href="#requests">Student Requests</a>
      <a href="#report">Reports</a>
    </nav>

    <section id="profile">
      <h2>Profile Information</h2>
      <p><strong>Name:</strong> <?php echo htmlspecialchars($user_name); ?></p>
      <p><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
      <p><strong>Role:</strong> Faculty</p>
    </section>

    <section id="course">
      <h2>My Courses</h2>
      <button onclick="openCreateModal()">Create New Course</button>
      <div id="coursesContainer" style="margin-top: 20px;">
          <p>Loading courses...</p>
      </div>
    </section>

    <section id="requests">
      <h2>Student Join Requests</h2>
      <button onclick="loadRequests()">Refresh Requests</button>
      <div id="requestsContainer" style="margin-top: 20px;">
          <p>Loading requests...</p>
      </div>
    </section>

    <section id="report">
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

  <script>
  
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

    window.onclick = (event) => {
        if (event.target == createModal) closeCreateModal();
    };

    
    document.getElementById('createCourseForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'create_course');

        fetch('faculty_actions.php', {
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

    
    function loadCourses() {
        fetch('faculty_actions.php', {
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

    
    function loadRequests() {
        fetch('faculty_actions.php', {
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
        
        fetch('faculty_actions.php', {
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
        
        fetch('faculty_actions.php', {
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
        
        fetch('faculty_actions.php', {
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