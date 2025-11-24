<?php
session_start();
require_once 'config.php';


requireRole('FacultyIntern');

$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Faculty Intern Dashboard</title>
  <link rel="stylesheet" href="fi.css" />
</head>
<body>
  <div style="position: absolute; top: 20px; right: 20px;">
      <a href="logout.php" style="background-color: #E63946; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold;">Logout</a>
  </div>
  
  <h1>Faculty Intern Dashboard</h1>
  <p style="text-align: center; color: #1D3557; margin: 10px 0;">Welcome, <?php echo htmlspecialchars($user_name); ?>!</p>

  <nav>
    <a href="#profile">Profile</a>
    <a href="#courses">Courses</a>
    <a href="#sessions">Sessions</a>
    <a href="#reports">Reports</a>
    <a href="#manage">Manage Students</a>
  </nav>

  <section id="profile">
    <h2>Profile Information</h2>
    <p><strong>Name:</strong> <?php echo htmlspecialchars($user_name); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
    <p><strong>Role:</strong> Faculty Intern</p>
  </section>

  <section id="courses">
    <h2>Course List</h2>
    <ul>
      <li>Systems Analysis & Design</li>
      <li>Leadership 4</li>
    </ul>
    <button>Add New Course</button>
  </section>

  <section id="sessions">
    <h2>Sessions</h2>
    <table>
      <tr><th>Course</th><th>Day</th><th>Time</th></tr>
      <tr>
        <td>Systems Analysis & Design</td>
        <td>Monday</td>
        <td>11:30 AM - 1:00 PM</td>
      </tr>
      <tr>
        <td>Leadership 4</td>
        <td>Wednesday</td>
        <td>3:00 PM - 4:30 PM</td>
      </tr>
    </table>
  </section>

  <section id="reports">
    <h2>Reports</h2>
    <ul>
      <li>Attendance Summary: 95%</li>
      <li>Feedback Received: Good Improvement, keep the good work!</li>
    </ul>
    <button>Download Full Report</button>
  </section>

  <section id="manage">
    <h2>Manage Students</h2>
    <button>View Auditors</button>
    <button>View Observers</button>
  </section>
</body>
</html>