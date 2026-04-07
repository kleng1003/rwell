<?php
session_start();
include_once('../include/connection.php');

// This is for testing/debugging only
if (isset($_GET['employee_id'])) {
    $employee_id = $_GET['employee_id'];
    $query = "SELECT u.*, e.first_name, e.last_name, e.position 
              FROM users u 
              LEFT JOIN employees e ON u.employee_id = e.employee_id 
              WHERE u.user_id = '{$_SESSION['userid']}'";
    $result = mysqli_query($con, $query);
    $user = mysqli_fetch_assoc($result);
    
    $_SESSION['employee_id'] = $user['employee_id'];
    $_SESSION['employee_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['employee_position'] = $user['position'];
    
    header("Location: ../Pages/employee_dashboard.php");
}
?>