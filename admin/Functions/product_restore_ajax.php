<?php
session_start();
include_once('../include/connection.php');
include_once('../include/activity_logger.php');

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['userid']) || empty($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if (isset($_POST['id'])) {
    $product_id = mysqli_real_escape_string($con, $_POST['id']);
    
    // Get product name for logging
    $product_query = mysqli_query($con, "SELECT product_name FROM products WHERE product_id = '$product_id'");
    $product = mysqli_fetch_assoc($product_query);
    $product_name = $product['product_name'];
    
    // Restore product
    $restore = mysqli_query($con, "UPDATE products SET status = 'available' WHERE product_id = '$product_id'");
    
    if ($restore) {
        logActivity("Restored product", "Product: $product_name, ID: $product_id");
        echo json_encode(['status' => 'success', 'message' => 'Product restored successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error restoring product: ' . mysqli_error($con)]);
    }
}
?>