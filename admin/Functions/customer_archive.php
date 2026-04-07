<?php
// ../Functions/customer_archive.php
session_start();
include_once('../include/connection.php');
include_once('../include/activity_logger.php');

// Check if it's an AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// If it's AJAX, set JSON header
if ($is_ajax) {
    header('Content-Type: application/json');
}

// Check if user is logged in
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    if ($is_ajax) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
        exit();
    } else {
        header("Location: ../index.php");
        exit();
    }
}

// Get customer ID (from GET or POST)
if (isset($_GET['id'])) {
    $customer_id = mysqli_real_escape_string($con, $_GET['id']);
} elseif (isset($_POST['id'])) {
    $customer_id = mysqli_real_escape_string($con, $_POST['id']);
} else {
    if ($is_ajax) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid customer ID']);
        exit();
    } else {
        $_SESSION['error'] = "Invalid customer ID";
        header("Location: ../Pages/customers.php");
        exit();
    }
}

// Get customer name for logging
$customer_query = mysqli_query($con, "SELECT first_name, last_name FROM customers WHERE customer_id = '$customer_id'");
if (mysqli_num_rows($customer_query) == 0) {
    if ($is_ajax) {
        echo json_encode(['status' => 'error', 'message' => 'Customer not found']);
        exit();
    } else {
        $_SESSION['error'] = "Customer not found";
        header("Location: ../Pages/customers.php");
        exit();
    }
}

$customer = mysqli_fetch_assoc($customer_query);
$customer_name = $customer['first_name'] . ' ' . $customer['last_name'];

// Check if status column exists, if not add it
$check_column = mysqli_query($con, "SHOW COLUMNS FROM customers LIKE 'status'");
if (mysqli_num_rows($check_column) == 0) {
    mysqli_query($con, "ALTER TABLE customers ADD COLUMN status enum('active','archived') DEFAULT 'active'");
}

// Archive the customer
$archive = mysqli_query($con, "UPDATE customers SET status = 'archived' WHERE customer_id = '$customer_id'");

if ($archive) {
    // Log the activity
    logActivity("Archived customer", "Customer: $customer_name, ID: $customer_id");
    
    if ($is_ajax) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Customer archived successfully',
            'customer_id' => $customer_id,
            'customer_name' => $customer_name
        ]);
        exit();
    } else {
        $_SESSION['success'] = "Customer archived successfully!";
        header("Location: ../Pages/customers.php");
        exit();
    }
} else {
    if ($is_ajax) {
        echo json_encode(['status' => 'error', 'message' => 'Error archiving customer: ' . mysqli_error($con)]);
        exit();
    } else {
        $_SESSION['error'] = "Error archiving customer: " . mysqli_error($con);
        header("Location: ../Pages/customers.php");
        exit();
    }
}
?>