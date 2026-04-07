<?php
// ../Functions/admin_activate.php
session_start();
include_once('../include/connection.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid administrator ID.";
    header("Location: ../Pages/admin-account.php");
    exit();
}

$admin_id = mysqli_real_escape_string($con, $_GET['id']);

// Don't allow deactivated users to activate others
$check_current_query = mysqli_query($con, "SELECT status FROM users WHERE user_id = '{$_SESSION['userid']}'");
$current_user = mysqli_fetch_assoc($check_current_query);

if ($current_user['status'] !== 'active') {
    $_SESSION['error'] = "Your account is deactivated. Cannot perform this action.";
    header("Location: ../Pages/admin-account.php");
    exit();
}

// Update the user status to active
$update_query = "UPDATE users SET status = 'active' WHERE user_id = '$admin_id'";

if (mysqli_query($con, $update_query)) {
    // Log the action (optional)
    $log_action = "Activated admin account ID: " . $admin_id;
    // You can add logging here if you have a logs table
    
    $_SESSION['success'] = "Administrator account has been successfully activated.";
} else {
    $_SESSION['error'] = "Error activating account: " . mysqli_error($con);
}

// Redirect back to admin accounts page
header("Location: ../Pages/admin-account.php");
exit();
?>