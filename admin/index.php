<?php
// ../index.php
session_start();
include_once('include/connection.php');
include_once('include/activity_logger.php');

// Check if already logged in
if (isset($_SESSION['userid'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: ./Pages/index.php");
    } else {
        header("Location: ./Pages/employee_dashboard.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($con, $_POST['username']);
    $password = $_POST['password'];
    
    // Check if user exists
    $query = "SELECT u.*, e.first_name, e.last_name, e.position 
              FROM users u 
              LEFT JOIN employees e ON u.employee_id = e.employee_id 
              WHERE u.username = '$username'";
    $result = mysqli_query($con, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['password'])) {
            // Check status
            if ($user['status'] == 'active') {
                // Successful login
                $_SESSION['userid'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['status'] = $user['status'];
                
                // Set employee session variables if applicable
                if ($user['role'] == 'employee' && $user['employee_id']) {
                    $_SESSION['employee_id'] = $user['employee_id'];
                    $_SESSION['employee_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['employee_position'] = $user['position'];
                }
                
                // Update last login
                $ip = $_SERVER['REMOTE_ADDR'];
                mysqli_query($con, "UPDATE users SET last_login = NOW(), last_ip = '$ip' WHERE user_id = '{$user['user_id']}'");
                
                // Log the login
                logActivity("User logged in", "Username: $username, Role: {$user['role']}");
                
                // Redirect based on role
                if ($user['role'] == 'admin') {
                    header("Location: ./Pages/index.php");
                } else {
                    header("Location: ./Pages/employee_dashboard.php");
                }
                exit();
                
            } elseif ($user['status'] == 'pending') {
                $error = "Your account is pending approval. Please wait for the administrator to activate your account.";
                logActivity("Failed login attempt - Pending account", "Username: $username");
                
            } elseif ($user['status'] == 'inactive') {
                $error = "Your account has been deactivated. Please contact the administrator.";
                logActivity("Failed login attempt - Inactive account", "Username: $username");
            }
        } else {
            $error = "Invalid username or password.";
            logActivity("Failed login attempt - Wrong password", "Username: $username");
        }
    } else {
        $error = "Invalid username or password.";
        logActivity("Failed login attempt - User not found", "Username: $username");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>RWELL - Login</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated Background */
        .bg-bubbles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }
        
        .bg-bubbles li {
            position: absolute;
            list-style: none;
            display: block;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            bottom: -150px;
            animation: square 25s infinite;
            transition-timing-function: linear;
            border-radius: 50%;
        }
        
        .bg-bubbles li:nth-child(1) { left: 10%; width: 80px; height: 80px; animation-delay: 0s; }
        .bg-bubbles li:nth-child(2) { left: 20%; width: 120px; height: 120px; animation-delay: 2s; animation-duration: 17s; }
        .bg-bubbles li:nth-child(3) { left: 25%; width: 40px; height: 40px; animation-delay: 4s; }
        .bg-bubbles li:nth-child(4) { left: 40%; width: 60px; height: 60px; animation-duration: 22s; background: rgba(255,255,255,0.05); }
        .bg-bubbles li:nth-child(5) { left: 70%; width: 100px; height: 100px; animation-delay: 0s; }
        .bg-bubbles li:nth-child(6) { left: 80%; width: 150px; height: 150px; animation-delay: 3s; background: rgba(255,255,255,0.08); }
        .bg-bubbles li:nth-child(7) { left: 32%; width: 90px; height: 90px; animation-delay: 7s; }
        .bg-bubbles li:nth-child(8) { left: 55%; width: 50px; height: 50px; animation-delay: 15s; animation-duration: 40s; }
        .bg-bubbles li:nth-child(9) { left: 85%; width: 70px; height: 70px; animation-delay: 2s; animation-duration: 35s; }
        .bg-bubbles li:nth-child(10) { left: 90%; width: 110px; height: 110px; animation-delay: 11s; }
        
        @keyframes square {
            0% { transform: translateY(0) rotate(0deg); opacity: 1; border-radius: 50%; }
            100% { transform: translateY(-1000px) rotate(720deg); opacity: 0; border-radius: 50%; }
        }
        
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            margin: 0 auto;
        }
        
        .login-box {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .login-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.3);
        }
        
        /* Header */
        .login-header {
            background: linear-gradient(135deg, #464660 0%, #5a5a7a 100%);
            padding: 40px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '\f2b5';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: -20px;
            bottom: -20px;
            font-size: 120px;
            opacity: 0.1;
            color: white;
        }
        
        .logo-wrapper {
            display: inline-block;
            background: rgba(255, 255, 255, 0.15);
            padding: 15px;
            border-radius: 50%;
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }
        
        .logo-wrapper:hover {
            transform: scale(1.05);
        }
        
        .logo-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }
        
        .login-header h2 {
            color: #fff;
            margin: 10px 0 5px;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .login-header p {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
            margin: 0;
            font-weight: 400;
        }
        
        /* Body */
        .login-body {
            padding: 35px 30px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
            font-size: 14px;
        }
        
        .form-group label i {
            margin-right: 8px;
            color: #764ba2;
            width: 18px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 14px;
            pointer-events: none;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: #f9fafb;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #764ba2;
            background: white;
            box-shadow: 0 0 0 3px rgba(118, 75, 162, 0.1);
        }
        
        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #9ca3af;
            z-index: 2;
            transition: color 0.2s;
        }
        
        .password-toggle:hover {
            color: #764ba2;
        }
        
        /* Options */
        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 13px;
        }
        
        .checkbox {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .checkbox input {
            width: 16px;
            height: 16px;
            margin-right: 8px;
            cursor: pointer;
            accent-color: #764ba2;
        }
        
        .checkbox span {
            color: #6b7280;
        }
        
        .forgot-link {
            color: #764ba2;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .forgot-link:hover {
            color: #464660;
            text-decoration: underline;
        }
        
        /* Button */
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #464660 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(118, 75, 162, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login i {
            margin-right: 8px;
        }
        
        /* Alert */
        .alert {
            padding: 12px 15px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 13px;
            border: none;
            display: flex;
            align-items: center;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border-left: 4px solid #16a34a;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 16px;
        }
        
        .close-alert {
            margin-left: auto;
            cursor: pointer;
            opacity: 0.6;
            transition: opacity 0.2s;
        }
        
        .close-alert:hover {
            opacity: 1;
        }
        
        /* Footer */
        .login-footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }
        
        .login-footer a {
            color: #6b7280;
            text-decoration: none;
            font-size: 12px;
            margin: 0 12px;
            transition: color 0.2s;
        }
        
        .login-footer a:hover {
            color: #764ba2;
        }
        
        .login-footer i {
            margin-right: 5px;
        }
        
        /* Demo Credentials */
        .demo-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px;
            margin-top: 20px;
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        
        .demo-box:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        
        .demo-box p {
            margin: 0 0 5px;
            font-size: 12px;
            color: #475569;
            font-weight: 500;
        }
        
        .demo-box .creds {
            font-size: 11px;
            color: #64748b;
        }
        
        .demo-box strong {
            color: #464660;
            font-weight: 600;
        }
        
        /* Loading */
        .btn-login.loading {
            pointer-events: none;
            opacity: 0.8;
        }
        
        .btn-login.loading i {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .login-header {
                padding: 30px 20px;
            }
            
            .logo-img {
                width: 65px;
                height: 65px;
            }
            
            .login-header h2 {
                font-size: 24px;
            }
            
            .login-body {
                padding: 25px 20px;
            }
            
            .login-options {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <ul class="bg-bubbles">
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
        <li></li>
    </ul>
    
    <div class="login-container">
        <div class="login-box">
            <!-- Header -->
            <div class="login-header">
                <div class="logo-wrapper">
                    <img src="images/logo.png" alt="RWELL" class="logo-img" onerror="this.src='https://placehold.co/80x80?text=R'">
                </div>
                <h2>RWELL</h2>
                <p>Beauty & Wellness Center</p>
            </div>
            
            <!-- Body -->
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= $error; ?></span>
                        <i class="fas fa-times close-alert"></i>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="loginForm">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Username</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="username" class="form-control" 
                                   placeholder="Enter your username" required autofocus>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="password" id="password" class="form-control" 
                                   placeholder="Enter your password" required>
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>
                    </div>
                    
                    <div class="login-options">
                        <label class="checkbox">
                            <input type="checkbox" id="rememberMe"> 
                            <span>Remember me</span>
                        </label>
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="btn-login" id="loginBtn">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>
                
                <!-- <div class="login-footer">
                    <a href="#"><i class="fas fa-question-circle"></i> Help</a>
                    <a href="#"><i class="fas fa-envelope"></i> Support</a>
                </div> -->
                
                <!-- Demo Credentials -->
                <!-- <div class="demo-box">
                    <p><i class="fas fa-info-circle"></i> <strong>Demo Accounts:</strong></p>
                    <div class="creds">
                        Admin: <strong>admin</strong> / <strong>admin123</strong> | 
                        Employee: <strong>test</strong> / <strong>test123</strong>
                    </div>
                </div> -->
            </div>
        </div>
    </div>
    
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Password visibility toggle
            $('#togglePassword').click(function() {
                const passwordInput = $('#password');
                const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
                passwordInput.attr('type', type);
                $(this).toggleClass('fa-eye fa-eye-slash');
            });
            
            // Close alert manually
            $('.close-alert').click(function() {
                $(this).closest('.alert').fadeOut(300);
            });
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut(300);
            }, 5000);
            
            // Form submission with loading state
            $('#loginForm').submit(function() {
                const btn = $('#loginBtn');
                if (!btn.hasClass('loading')) {
                    btn.addClass('loading');
                    btn.html('<i class="fas fa-spinner fa-spin"></i> Signing in...');
                }
                return true;
            });
            
            // Remember me functionality
            if (localStorage.getItem('rememberedUser')) {
                $('input[name="username"]').val(localStorage.getItem('rememberedUser'));
                $('#rememberMe').prop('checked', true);
            }
            
            $('#rememberMe').change(function() {
                if ($(this).is(':checked')) {
                    localStorage.setItem('rememberedUser', $('input[name="username"]').val());
                } else {
                    localStorage.removeItem('rememberedUser');
                }
            });
            
            // Save username when typing if remember me is checked
            $('input[name="username"]').on('input', function() {
                if ($('#rememberMe').is(':checked')) {
                    localStorage.setItem('rememberedUser', $(this).val());
                }
            });
        });
    </script>
</body>
</html>