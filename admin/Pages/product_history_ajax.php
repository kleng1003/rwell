<?php
// Pages/product_history_ajax.php
session_start();
include_once('../include/connection.php');

header('Content-Type: application/json');

// For debugging - remove after testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check database connection
if (!$con) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

if (isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    
    // Simple query without prepared statement for debugging
    $query = "SELECT h.*, u.username 
              FROM product_history h
              LEFT JOIN users u ON h.changed_by = u.user_id
              WHERE h.product_id = $product_id
              ORDER BY h.changed_at DESC";
    
    $result = mysqli_query($con, $query);
    
    if (!$result) {
        echo json_encode(['status' => 'error', 'message' => 'Query failed: ' . mysqli_error($con)]);
        exit();
    }
    
    $history = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $history[] = [
            'history_id' => $row['history_id'],
            'product_id' => $row['product_id'],
            'field_name' => $row['field_name'],
            'old_value' => $row['old_value'],
            'new_value' => $row['new_value'],
            'changed_by' => $row['changed_by'],
            'changed_at' => $row['changed_at'],
            'username' => $row['username'] ?? 'System'
        ];
    }
    
    echo json_encode(['status' => 'success', 'history' => $history]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Product ID required']);
}
?>