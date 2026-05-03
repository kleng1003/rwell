<?php
// Functions/product_history_ajax.php
session_start();
include_once('../include/connection.php');

header('Content-Type: application/json');

if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if (isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    
    $query = "SELECT h.*, u.username 
              FROM product_history h
              LEFT JOIN users u ON h.changed_by = u.user_id
              WHERE h.product_id = ?
              ORDER BY h.changed_at DESC";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    
    echo json_encode(['status' => 'success', 'history' => $history]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Product ID required']);
}
?>