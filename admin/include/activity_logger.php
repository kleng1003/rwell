<?php
// ../include/activity_logger.php

// Include database connection
include_once('connection.php');

function logActivity($action, $details = null) {
    global $con; // Make sure to use the global connection
    
    // Check if connection exists
    if (!isset($con) || !$con) {
        error_log("Database connection not available for logging");
        return false;
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
        return false;
    }
    
    $user_id = $_SESSION['userid'];
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown';
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : 'unknown';
    $ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    
    // Escape all values to prevent SQL injection
    $action = mysqli_real_escape_string($con, $action);
    $details = mysqli_real_escape_string($con, $details);
    $username = mysqli_real_escape_string($con, $username);
    $role = mysqli_real_escape_string($con, $role);
    $ip_address = mysqli_real_escape_string($con, $ip_address);
    $user_agent = mysqli_real_escape_string($con, $user_agent);
    
    $query = "INSERT INTO activity_logs (user_id, username, role, action, details, ip_address, user_agent, created_at) 
              VALUES ('$user_id', '$username', '$role', '$action', '$details', '$ip_address', '$user_agent', NOW())";
    
    return mysqli_query($con, $query);
}
?>