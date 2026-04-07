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
    $supplier_id = mysqli_real_escape_string($con, $_POST['id']);
    
    // Get supplier name for logging
    $supplier_query = mysqli_query($con, "SELECT company_name FROM suppliers WHERE supplier_id = '$supplier_id'");
    $supplier = mysqli_fetch_assoc($supplier_query);
    $company_name = $supplier['company_name'];
    
    // Restore supplier
    $restore = mysqli_query($con, "UPDATE suppliers SET status = 'active' WHERE supplier_id = '$supplier_id'");
    
    if ($restore) {
        logActivity("Restored supplier", "Supplier: $company_name, ID: $supplier_id");
        echo json_encode(['status' => 'success', 'message' => 'Supplier restored successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error restoring supplier: ' . mysqli_error($con)]);
    }
}
?>