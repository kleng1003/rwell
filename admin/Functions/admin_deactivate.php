<?php
// ../Functions/admin_deactivate.php
session_start();
include_once('../include/connection.php');

// Check if user is logged in
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

// Don't allow users to deactivate themselves
if ($admin_id == $_SESSION['userid']) {
    $_SESSION['error'] = "You cannot deactivate your own account.";
    header("Location: ../Pages/admin-account.php");
    exit();
}

// Check if the target user exists and is active
$check_query = mysqli_query($con, "SELECT status FROM users WHERE user_id = '$admin_id'");
if (mysqli_num_rows($check_query) == 0) {
    $_SESSION['error'] = "Administrator not found.";
    header("Location: ../Pages/admin-account.php");
    exit();
}

$user = mysqli_fetch_assoc($check_query);
if ($user['status'] !== 'active') {
    $_SESSION['error'] = "This account is already deactivated.";
    header("Location: ../Pages/admin-account.php");
    exit();
}

// Update the user status to inactive
$update_query = "UPDATE users SET status = 'inactive' WHERE user_id = '$admin_id'";

if (mysqli_query($con, $update_query)) {
    $_SESSION['success'] = "Administrator account has been deactivated.";
} else {
    $_SESSION['error'] = "Error deactivating account: " . mysqli_error($con);
}

header("Location: ../Pages/admin-account.php");
exit();
?>  