<?php
session_start();

if (isset($_SESSION['user_id'])) {
    $role = strtolower(trim($_SESSION['user_role']));
    if ($role === 'student') {
        header("Location: studentdashboard.php");
    } elseif ($role === 'faculty') {
        header("Location: facultydashboard.php");
    } elseif ($role === 'facultyintern') {
        header("Location: fidashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LOGIN PAGE</title>
<link rel="stylesheet" href="login.css">
</head>

<body>
    <div class="main-title">ASHESI ATTENDANCE MANAGEMENT SYSTEM</div>
    
    <div id="alertContainer" class="alert"></div>
    <div id="loadingContainer" class="loading">
        <div class="spinner"></div>
        <p>Logging in...</p>
    </div>
    
    <form id="loginForm" method="post">
        <div class="login-page">
            <label for="Email_Input">Enter your email: </label>
            <input type="email" name="username" id="Email_Input" placeholder="email" required>
            
            <label for="Password_Input">Enter your password: </label>
            <input type="password" name="password" id="Password_Input" placeholder="password" required>

            <input type="submit" value="LOGIN" id="loginBtn">
        </div>

        <div class="signup-link">
            Don't have an account?
            <a href="signup.php">You can sign up here</a>
        </div>
    </form>

    <script>
        const loginForm = document.getElementById('loginForm');
        const alertContainer = document.getElementById('alertContainer');
        const loadingContainer = document.getElementById('loadingContainer');
        const loginBtn = document.getElementById('loginBtn');

        
        function showAlert(message, type) {
            alertContainer.textContent = message;
            alertContainer.className = `alert alert-${type}`;
            alertContainer.style.display = 'block';
            
            
            setTimeout(() => {
                alertContainer.style.display = 'none';
            }, 5000);
        }

        
        function showLoading(show) {
            if (show) {
                loadingContainer.style.display = 'block';
                loginBtn.disabled = true;
                loginBtn.value = 'LOGGING IN...';
            } else {
                loadingContainer.style.display = 'none';
                loginBtn.disabled = false;
                loginBtn.value = 'LOGIN';
            }
        }

        
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            
            alertContainer.style.display = 'none';
            
            
            showLoading(true);
            
            
            const formData = new FormData(loginForm);
            
            
            fetch('login_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                
                if (data.success) {
                    
                    showAlert(data.message, 'success');
                    
                    
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else {
                
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showLoading(false);
                showAlert('An error occurred. Please try again.', 'error');
                console.error('Error:', error);
            });
        });

        
        document.getElementById('Email_Input').addEventListener('input', () => {
            alertContainer.style.display = 'none';
        });
        
        document.getElementById('Password_Input').addEventListener('input', () => {
            alertContainer.style.display = 'none';
        });
    </script>
</body>
</html>