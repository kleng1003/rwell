<?php
// user_deactivate.php
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
        $_SESSION['error'] = "You cannot deactivate your own account.";
        header("Location: ../Pages/admin-account.php");
        exit();
    }
    
    $update = mysqli_query($con, "UPDATE users SET status = 'inactive' WHERE user_id = '$user_id'");
    
    if ($update) {
        logActivity("User account deactivated", "User ID: $user_id");
        $_SESSION['success'] = "User account has been deactivated successfully!";
    } else {
        $_SESSION['error'] = "Error deactivating user: " . mysqli_error($con);
    }
    
    header("Location: ../Pages/admin-account.php");
    exit();
}
?>