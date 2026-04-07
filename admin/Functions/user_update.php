<?php
// user_update.php
session_start();
include_once('../include/connection.php');
include_once('../include/activity_logger.php');

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../Pages/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = mysqli_real_escape_string($con, $_POST['user_id']);
    $username = mysqli_real_escape_string($con, $_POST['username']);
    $role = mysqli_real_escape_string($con, $_POST['role']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    $new_password = $_POST['new_password'];
    
    // Check if username already exists (excluding current user)
    $check = mysqli_query($con, "SELECT user_id FROM users WHERE username = '$username' AND user_id != '$user_id'");
    if (mysqli_num_rows($check) > 0) {
        $_SESSION['error'] = "Username already exists!";
        header("Location: ../Pages/admin-account.php");
        exit();
    }
    
    // Build update query
    $update_fields = [];
    $update_fields[] = "username = '$username'";
    $update_fields[] = "role = '$role'";
    $update_fields[] = "status = '$status'";
    
    // Update password if provided
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_fields[] = "password = '$hashed_password'";
    }
    
    $update_query = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE user_id = '$user_id'";
    
    if (mysqli_query($con, $update_query)) {
        // Log the action
        logActivity("Updated user account", "User ID: $user_id, New status: $status, Role: $role");
        
        $_SESSION['success'] = "User account updated successfully!";
        
        // If updating own account, update session username
        if ($user_id == $_SESSION['userid']) {
            $_SESSION['username'] = $username;
        }
    } else {
        $_SESSION['error'] = "Error updating user: " . mysqli_error($con);
    }
    
    header("Location: ../Pages/admin-account.php");
    exit();
}
?>