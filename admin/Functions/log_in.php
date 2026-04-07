<?php
// ../Functions/login_process.php
session_start();
include_once('../include/connection.php');
include_once('../include/activity_logger.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($con, $_POST['username']);
    $password = $_POST['password'];
    
    $query = "SELECT u.*, e.employee_id, e.first_name, e.last_name, e.position 
              FROM users u 
              LEFT JOIN employees e ON u.employee_id = e.employee_id 
              WHERE u.username = '$username' AND u.status = 'active'";
    $result = mysqli_query($con, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['password'])) {
            // Set all session variables
            $_SESSION['userid'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['status'] = $user['status'];
            
            // Set employee session variables
            if ($user['employee_id']) {
                $_SESSION['employee_id'] = $user['employee_id'];
                $_SESSION['employee_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['employee_position'] = $user['position'];
                $_SESSION['employee_first_name'] = $user['first_name'];
                $_SESSION['employee_last_name'] = $user['last_name'];
            } else {
                // For admin users who might not be linked to employee record
                $_SESSION['employee_id'] = null;
                $_SESSION['employee_name'] = $user['username'];
                $_SESSION['employee_position'] = 'Administrator';
            }
            
            // Update last login
            $ip = $_SERVER['REMOTE_ADDR'];
            mysqli_query($con, "UPDATE users SET last_login = NOW(), last_ip = '$ip' WHERE user_id = '{$user['user_id']}'");
            
            // Log the login
            logActivity("User logged in", "Username: $username, Role: {$user['role']}");
            
            // Redirect based on role
            if ($user['role'] == 'admin') {
                header("Location: ../Pages/index.php");
            } else {
                header("Location: ../Pages/employee_dashboard.php");
            }
            exit();
        }
    }
    
    logActivity("Failed login attempt", "Username: $username");
    $_SESSION['error'] = "Invalid username or password";
    header("Location: ../index.php");
    exit();
}
?>