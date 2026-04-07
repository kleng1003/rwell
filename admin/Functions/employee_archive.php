<?php
// ../Functions/employee_archive.php
session_start();
include_once('../include/connection.php');

// Check if user is logged in
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid employee ID.";
    header("Location: ../Pages/employees.php");
    exit();
}

$employee_id = mysqli_real_escape_string($con, $_GET['id']);

// Check if employee exists
$check_query = mysqli_query($con, "SELECT * FROM employees WHERE employee_id = '$employee_id'");
if (mysqli_num_rows($check_query) == 0) {
    $_SESSION['error'] = "Employee not found.";
    header("Location: ../Pages/employees.php");
    exit();
}

$employee = mysqli_fetch_assoc($check_query);

// Archive the employee (set status to 'archived')
$archive_query = "UPDATE employees SET status = 'archived' WHERE employee_id = '$employee_id'";

if (mysqli_query($con, $archive_query)) {
    $_SESSION['success'] = "Employee '" . $employee['first_name'] . " " . $employee['last_name'] . "' has been archived successfully.";
} else {
    $_SESSION['error'] = "Error archiving employee: " . mysqli_error($con);
}

header("Location: ../Pages/employees.php");
exit();
?>