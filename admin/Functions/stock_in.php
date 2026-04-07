<?php
// ../Functions/stock_in.php
session_start();
header('Content-Type: application/json');

include_once('../include/connection.php');

// Check if user is logged in
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

// Get form data
$supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
$purchase_date = isset($_POST['purchase_date']) ? mysqli_real_escape_string($con, $_POST['purchase_date']) : '';
$remarks = isset($_POST['remarks']) ? mysqli_real_escape_string($con, $_POST['remarks']) : '';
$product_ids = isset($_POST['product_id']) ? $_POST['product_id'] : [];
$quantities = isset($_POST['qty']) ? $_POST['qty'] : [];
$costs = isset($_POST['cost']) ? $_POST['cost'] : [];

// Validate required fields
if ($supplier_id == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid supplier ID']);
    exit();
}

if (empty($purchase_date)) {
    echo json_encode(['status' => 'error', 'message' => 'Purchase date is required']);
    exit();
}

if (empty($product_ids) || empty($quantities) || empty($costs)) {
    echo json_encode(['status' => 'error', 'message' => 'No products selected']);
    exit();
}

// Start transaction
mysqli_begin_transaction($con);

try {
    // Calculate total amount
    $total_amount = 0;
    $items_to_insert = [];
    
    for ($i = 0; $i < count($product_ids); $i++) {
        $product_id = intval($product_ids[$i]);
        $qty = intval($quantities[$i]);
        $cost = floatval($costs[$i]);
        
        // Skip if quantity is 0
        if ($qty <= 0) continue;
        
        $subtotal = $qty * $cost;
        $total_amount += $subtotal;
        
        $items_to_insert[] = [
            'product_id' => $product_id,
            'qty' => $qty,
            'cost' => $cost,
            'subtotal' => $subtotal
        ];
    }
    
    if (empty($items_to_insert)) {
        echo json_encode(['status' => 'error', 'message' => 'No valid quantities entered']);
        exit();
    }
    
    // Insert into purchases table
    $insert_purchase = "INSERT INTO purchases (supplier_id, purchase_date, total_amount, remarks, status) 
                        VALUES ('$supplier_id', '$purchase_date', '$total_amount', '$remarks', 'completed')";
    
    if (!mysqli_query($con, $insert_purchase)) {
        throw new Exception('Failed to create purchase record: ' . mysqli_error($con));
    }
    
    $purchase_id = mysqli_insert_id($con);
    
    // Insert purchase items and update product stock
    foreach ($items_to_insert as $item) {
        $product_id = $item['product_id'];
        $qty = $item['qty'];
        $cost = $item['cost'];
        $subtotal = $item['subtotal'];
        
        // Insert into purchase_items
        $insert_item = "INSERT INTO purchase_items (purchase_id, product_id, quantity, cost) 
                        VALUES ('$purchase_id', '$product_id', '$qty', '$cost')";
        
        if (!mysqli_query($con, $insert_item)) {
            throw new Exception('Failed to insert purchase item: ' . mysqli_error($con));
        }
        
        // Update product stock
        $update_stock = "UPDATE products SET stock = stock + $qty WHERE product_id = '$product_id'";
        
        if (!mysqli_query($con, $update_stock)) {
            throw new Exception('Failed to update product stock: ' . mysqli_error($con));
        }
    }
    
    // Commit transaction
    mysqli_commit($con);
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'Stock in successfully processed',
        'purchase_id' => $purchase_id,
        'total_amount' => $total_amount
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($con);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>