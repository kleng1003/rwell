<?php
// Functions/product_restore_ajax.php
session_start();
include_once('../include/connection.php');
include_once('../include/activity_logger.php');

header('Content-Type: application/json');

if (!isset($_SESSION['userid']) || empty($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    $product_id = mysqli_real_escape_string($con, $_POST['product_id']);
    
    // Get product info
    $product_query = mysqli_query($con, "SELECT * FROM products WHERE product_id = '$product_id'");
    $product = mysqli_fetch_assoc($product_query);
    
    if (!$product) {
        echo json_encode(['status' => 'error', 'message' => 'Product not found']);
        exit();
    }
    
    // Only restore if expired
    if ($product['status'] !== 'expired') {
        echo json_encode(['status' => 'error', 'message' => 'Product is not expired']);
        exit();
    }
    
    // Update status to available and clear expiration
    $update = "UPDATE products SET 
               status = 'available',
               expiration_date = NULL 
               WHERE product_id = '$product_id'";
    
    if (mysqli_query($con, $update)) {
        logActivity("Restored expired product", "Product: {$product['product_name']}, ID: $product_id");
        
        echo json_encode([
            'status' => 'success', 
            'message' => "{$product['product_name']} restored successfully"
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
    }
}
?>