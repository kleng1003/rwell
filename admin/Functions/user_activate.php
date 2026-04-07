<?php
// user_activate.php
session_start();
include_once('../include/connection.php');
include_once('../include/activity_logger.php');

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../Pages/index.php");
    exit();
}

if (isset($_GET['id'])) {
    $user_id = mysqli_real_escape_string($con, $_GET['id']);
    
    // Don't allow deactivating yourself
    if ($user_id == $_SESSION['userid']) {
        $_SESSION['error'] = "You cannot modify your own account status.";
        header("Location: ../Pages/admin-account.php");
        exit();
    }
    
    // Get current status
    $check = mysqli_query($con, "SELECT status FROM users WHERE user_id = '$user_id'");
    $user = mysqli_fetch_assoc($check);
    
    // Toggle status
    if ($user['status'] == 'active') {
        $new_status = 'inactive';
        $action = "deactivated";
    } elseif ($user['status'] == 'inactive' || $user['status'] == 'pending') {
        $new_status = 'active';
        $action = "activated";
    } else {
        $_SESSION['error'] = "Invalid status";
        header("Location: ../Pages/admin-account.php");
        exit();
    }
    
    $update = mysqli_query($con, "UPDATE users SET status = '$new_status' WHERE user_id = '$user_id'");
    
    if ($update) {
        logActivity("User account $action", "User ID: $user_id, New status: $new_status");
        $_SESSION['success'] = "User account has been " . ($action == "activated" ? "activated" : "deactivated") . " successfully!";
    } else {
        $_SESSION['error'] = "Error updating user status: " . mysqli_error($con);
    }
    
    header("Location: ../Pages/admin-account.php");
    exit();
}
?>