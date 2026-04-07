
<?php

file_put_contents('debug.log', date('Y-m-d H:i:s') . ' - Request received: ' . print_r($_POST, true) . "\n", FILE_APPEND);session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include_once('../include/connection.php');

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Get form data
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$role = isset($_POST['role']) ? $_POST['role'] : 'admin';
$status = isset($_POST['status']) ? $_POST['status'] : 'active';

// Validate
if (empty($username)) {
    echo json_encode(['status' => 'error', 'message' => 'Username is required']);
    exit();
}

if (empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Password is required']);
    exit();
}

if (strlen($username) < 3) {
    echo json_encode(['status' => 'error', 'message' => 'Username must be at least 3 characters']);
    exit();
}

if (strlen($password) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters']);
    exit();
}

// Check if username exists
$check_query = "SELECT user_id FROM users WHERE username = '$username'";
$check_result = mysqli_query($con, $check_query);

if (!$check_result) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
    exit();
}

if (mysqli_num_rows($check_result) > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Username already exists']);
    exit();
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$insert_query = "INSERT INTO users (username, password, role, status, created_at) 
                 VALUES ('$username', '$hashed_password', '$role', '$status', NOW())";

if (mysqli_query($con, $insert_query)) {
    $user_id = mysqli_insert_id($con);
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'Admin account created successfully',
        'user_id' => $user_id
    ]);
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error: ' . mysqli_error($con)
    ]);
}
?>