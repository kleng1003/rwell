<?php
// Functions/product_add_ajax.php (Updated)
session_start();
include_once('../include/connection.php');
include_once('../include/activity_logger.php');

header('Content-Type: application/json');

if (!isset($_SESSION['userid']) || empty($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = mysqli_real_escape_string($con, $_POST['product_name']);
    $supplier_id = !empty($_POST['supplier_id']) ? mysqli_real_escape_string($con, $_POST['supplier_id']) : 'NULL';
    $category = mysqli_real_escape_string($con, $_POST['category']);
    $price = mysqli_real_escape_string($con, $_POST['price']);
    $cost_price = !empty($_POST['cost_price']) ? mysqli_real_escape_string($con, $_POST['cost_price']) : '0.00';
    $stock = mysqli_real_escape_string($con, $_POST['stock']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    $manufacturing_date = !empty($_POST['manufacturing_date']) ? "'" . mysqli_real_escape_string($con, $_POST['manufacturing_date']) . "'" : 'NULL';
    $expiration_date = !empty($_POST['expiration_date']) ? "'" . mysqli_real_escape_string($con, $_POST['expiration_date']) . "'" : 'NULL';
    
    // Validate
    if (empty($product_name) || empty($category) || empty($price)) {
        echo json_encode(['status' => 'error', 'message' => 'Required fields are missing']);
        exit();
    }
    
    // Validate dates
    if (!empty($_POST['manufacturing_date']) && !empty($_POST['expiration_date'])) {
        if ($_POST['expiration_date'] < $_POST['manufacturing_date']) {
            echo json_encode(['status' => 'error', 'message' => 'Expiration date cannot be before manufacturing date']);
            exit();
        }
    }
    
    // Insert product
    $insert = "INSERT INTO products (product_name, supplier_id, category, price, cost_price, stock, description, status, manufacturing_date, expiration_date, created_at) 
               VALUES ('$product_name', " . ($supplier_id != 'NULL' ? "'$supplier_id'" : "NULL") . ", '$category', '$price', '$cost_price', '$stock', '$description', '$status', $manufacturing_date, $expiration_date, NOW())";
    
    if (mysqli_query($con, $insert)) {
        $product_id = mysqli_insert_id($con);
        
        // Get supplier name for logging
        $supplier_name = '';
        if ($supplier_id != 'NULL') {
            $supplier_query = mysqli_query($con, "SELECT company_name FROM suppliers WHERE supplier_id = '$supplier_id'");
            if ($supplier = mysqli_fetch_assoc($supplier_query)) {
                $supplier_name = " Supplier: " . $supplier['company_name'];
            }
        }
        
        // Log activity
        logActivity("Added new product", "Product: $product_name, Category: $category, Price: ₱$price, Cost: ₱$cost_price, Stock: $stock$supplier_name, ID: $product_id");
        
        // Get the newly created product data
        $product_query = mysqli_query($con, "
            SELECT p.*, s.company_name AS supplier_name 
            FROM products p
            LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
            WHERE p.product_id = '$product_id'
        ");
        $product = mysqli_fetch_assoc($product_query);
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Product added successfully',
            'product' => $product
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
    }
}
?>