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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = mysqli_real_escape_string($con, $_POST['product_id']);
    $product_name = mysqli_real_escape_string($con, $_POST['product_name']);
    $supplier_id = !empty($_POST['supplier_id']) ? mysqli_real_escape_string($con, $_POST['supplier_id']) : 'NULL';
    $category = mysqli_real_escape_string($con, $_POST['category']);
    $price = mysqli_real_escape_string($con, $_POST['price']);
    $stock = mysqli_real_escape_string($con, $_POST['stock']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    
    // Get old data for logging
    $old_query = mysqli_query($con, "SELECT * FROM products WHERE product_id = '$product_id'");
    $old_product = mysqli_fetch_assoc($old_query);
    
    // Update product
    $update = "UPDATE products SET 
                product_name = '$product_name',
                supplier_id = " . ($supplier_id != 'NULL' ? "'$supplier_id'" : "NULL") . ",
                category = '$category',
                price = '$price',
                stock = '$stock',
                description = '$description',
                status = '$status'
                WHERE product_id = '$product_id'";
    
    if (mysqli_query($con, $update)) {
        // Log activity with changes
        $changes = [];
        if ($old_product['product_name'] != $product_name) $changes[] = "Name: {$old_product['product_name']} → $product_name";
        if ($old_product['category'] != $category) $changes[] = "Category: {$old_product['category']} → $category";
        if ($old_product['price'] != $price) $changes[] = "Price: ₱{$old_product['price']} → ₱$price";
        if ($old_product['stock'] != $stock) $changes[] = "Stock: {$old_product['stock']} → $stock";
        if ($old_product['status'] != $status) $changes[] = "Status: {$old_product['status']} → $status";
        
        $log_details = "Product ID: $product_id. Changes: " . implode(", ", $changes);
        logActivity("Updated product", $log_details);
        
        echo json_encode(['status' => 'success', 'message' => 'Product updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
    }
}
?>