<?php
// Functions/product_update_ajax.php
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
    // Validate required fields
    if (empty($_POST['product_id']) || empty($_POST['product_name']) || empty($_POST['category']) || !isset($_POST['price'])) {
        echo json_encode(['status' => 'error', 'message' => 'Required fields are missing']);
        exit();
    }
    
    $product_id = mysqli_real_escape_string($con, $_POST['product_id']);
    $product_name = mysqli_real_escape_string($con, $_POST['product_name']);
    $supplier_id = !empty($_POST['supplier_id']) ? "'" . mysqli_real_escape_string($con, $_POST['supplier_id']) . "'" : 'NULL';
    $category = mysqli_real_escape_string($con, $_POST['category']);
    $price = floatval($_POST['price']);
    $cost_price = !empty($_POST['cost_price']) ? floatval($_POST['cost_price']) : 0.00;
    $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
    $description = mysqli_real_escape_string($con, $_POST['description'] ?? '');
    $status = mysqli_real_escape_string($con, $_POST['status'] ?? 'available');
    $manufacturing_date = !empty($_POST['manufacturing_date']) ? "'" . mysqli_real_escape_string($con, $_POST['manufacturing_date']) . "'" : 'NULL';
    $expiration_date = !empty($_POST['expiration_date']) ? "'" . mysqli_real_escape_string($con, $_POST['expiration_date']) . "'" : 'NULL';
    
    // Validate dates
    if (!empty($_POST['manufacturing_date']) && !empty($_POST['expiration_date'])) {
        if ($_POST['expiration_date'] < $_POST['manufacturing_date']) {
            echo json_encode(['status' => 'error', 'message' => 'Expiration date cannot be before manufacturing date']);
            exit();
        }
    }
    
    // Check if product exists
    $check_query = mysqli_query($con, "SELECT * FROM products WHERE product_id = '$product_id'");
    if (mysqli_num_rows($check_query) == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Product not found']);
        exit();
    }
    
    // Get old data for logging and history
    $old_product = mysqli_fetch_assoc($check_query);
    
    // Build update query
    $update = "UPDATE products SET 
                product_name = '$product_name',
                supplier_id = $supplier_id,
                category = '$category',
                price = '$price',
                cost_price = '$cost_price',
                stock = '$stock',
                description = '$description',
                status = '$status',
                manufacturing_date = $manufacturing_date,
                expiration_date = $expiration_date
                WHERE product_id = '$product_id'";
    
    // Execute update
    if (mysqli_query($con, $update)) {
        // Track changes for history
        $changes = [];
        
        // Map of fields to track
        $fields_to_track = [
            'product_name' => 'Name',
            'category' => 'Category', 
            'price' => 'Price',
            'cost_price' => 'Cost Price',
            'stock' => 'Stock',
            'status' => 'Status',
            'description' => 'Description',
            'manufacturing_date' => 'Manufacturing Date',
            'expiration_date' => 'Expiration Date'
        ];
        
        foreach ($fields_to_track as $field => $label) {
            $old_val = $old_product[$field] ?? '';
            
            // Get new value
            $new_val = '';
            switch($field) {
                case 'product_name': $new_val = $product_name; break;
                case 'category': $new_val = $category; break;
                case 'price': $new_val = $price; break;
                case 'cost_price': $new_val = $cost_price; break;
                case 'stock': $new_val = $stock; break;
                case 'status': $new_val = $status; break;
                case 'description': $new_val = $description; break;
                case 'manufacturing_date': $new_val = str_replace("'", "", $manufacturing_date); break;
                case 'expiration_date': $new_val = str_replace("'", "", $expiration_date); break;
            }
            
            // Convert NULL to empty string for comparison
            if ($new_val === 'NULL') $new_val = '';
            
            if ((string)$old_val != (string)$new_val) {
                $changes[] = "$label: $old_val → $new_val";
                
                // Insert into product_history table
                $old_val_escaped = mysqli_real_escape_string($con, (string)$old_val);
                $new_val_escaped = mysqli_real_escape_string($con, (string)$new_val);
                
                $history_sql = "INSERT INTO product_history (product_id, field_name, old_value, new_value, changed_by, changed_at) 
                               VALUES ('$product_id', '$field', '$old_val_escaped', '$new_val_escaped', '{$_SESSION['userid']}', NOW())";
                mysqli_query($con, $history_sql);
            }
        }
        
        // Log activity
        $log_details = "Product ID: $product_id. Changes: " . (empty($changes) ? 'No changes' : implode(", ", $changes));
        logActivity("Updated product", $log_details);
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Product updated successfully!'
        ]);
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Database error: ' . mysqli_error($con)
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid request method'
    ]);
}
?>