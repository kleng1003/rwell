<?php
// ../Functions/admin_update.php
session_start();
include_once('../include/connection.php');

// Check if user is logged in
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../Pages/admin-account.php");
    exit();
}

$user_id = mysqli_real_escape_string($con, $_POST['user_id']);
$username = mysqli_real_escape_string($con, $_POST['username']);
$role = mysqli_real_escape_string($con, $_POST['role']);
$status = mysqli_real_escape_string($con, $_POST['status']);

// Check if user exists and get current data
$check_query = mysqli_query($con, "SELECT * FROM users WHERE user_id = '$user_id'");
if (mysqli_num_rows($check_query) == 0) {
    $_SESSION['error'] = "User not found.";
    header("Location: ../Pages/admin-account.php");
    exit();
}

$current_user = mysqli_fetch_assoc($check_query);

// Check if username already exists (if changing username)
if ($username !== $current_user['username']) {
    $check_username = mysqli_query($con, "SELECT user_id FROM users WHERE username = '$username' AND user_id != '$user_id'");
    if (mysqli_num_rows($check_username) > 0) {
        $_SESSION['error'] = "Username already exists. Please choose a different username.";
        header("Location: ../Pages/admin-account.php");
        exit();
    }
}

// Build update query
$update_fields = [];
$update_fields[] = "username = '$username'";
$update_fields[] = "role = '$role'";
$update_fields[] = "status = '$status'";

// Check if password needs to be updated
if (!empty($_POST['new_password'])) {
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $update_fields[] = "password = '$new_password'";
}

// Don't allow deactivating yourself
if ($user_id == $_SESSION['userid'] && $status == 'inactive') {
    $_SESSION['error'] = "You cannot deactivate your own account.";
    header("Location: ../Pages/admin-account.php");
    exit();
}

// Don't allow changing role of super admin (if you want to protect certain accounts)
// if ($user_id == 1 && $role != 'admin') {
//     $_SESSION['error'] = "Cannot change role of super administrator.";
//     header("Location: ../Pages/admin-account.php");
//     exit();
// }

// Update the user
$update_query = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE user_id = '$user_id'";

if (mysqli_query($con, $update_query)) {
    $_SESSION['success'] = "Administrator account updated successfully.";
    
    // If updating own account, update session username
    if ($user_id == $_SESSION['userid']) {
        $_SESSION['username'] = $username;
    }
} else {
    $_SESSION['error'] = "Error updating account: " . mysqli_error($con);
}

header("Location: ../Pages/admin-account.php");
exit();
?>