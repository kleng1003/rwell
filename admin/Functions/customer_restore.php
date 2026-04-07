<?php
session_start();
include_once('../include/connection.php');
include_once('../include/activity_logger.php');

// Check if it's an AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($is_ajax) {
    header('Content-Type: application/json');
}

if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    if ($is_ajax) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
        exit();
    } else {
        header("Location: ../index.php");
        exit();
    }
}

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
        header("Location: ../Pages/customer-archive.php");
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
        header("Location: ../Pages/customer-archive.php");
        exit();
    }
}

$customer = mysqli_fetch_assoc($customer_query);
$customer_name = $customer['first_name'] . ' ' . $customer['last_name'];

// Restore customer
$restore = mysqli_query($con, "UPDATE customers SET status = 'active' WHERE customer_id = '$customer_id'");

if ($restore) {
    logActivity("Restored customer", "Customer: $customer_name, ID: $customer_id");
    
    if ($is_ajax) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Customer restored successfully',
            'customer_id' => $customer_id,
            'customer_name' => $customer_name
        ]);
        exit();
    } else {
        $_SESSION['success'] = "Customer restored successfully!";
        header("Location: ../Pages/customer-archive.php");
        exit();
    }
} else {
    if ($is_ajax) {
        echo json_encode(['status' => 'error', 'message' => 'Error restoring customer: ' . mysqli_error($con)]);
        exit();
    } else {
        $_SESSION['error'] = "Error restoring customer: " . mysqli_error($con);
        header("Location: ../Pages/customer-archive.php");
        exit();
    }
}
?>