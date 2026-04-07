<?php
include_once('../include/connection.php');
header('Content-Type: application/json');

// Initialize response
$response = [];

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

// Get POST data
$supplier_id    = $_POST['supplier_id'] ?? '';
$purchase_date  = $_POST['purchase_date'] ?? '';
$total_amount   = $_POST['total_amount'] ?? '';
$remarks        = $_POST['remarks'] ?? '';
$status         = 'active';

// Validate required fields
if (empty($supplier_id) || empty($purchase_date) || empty($total_amount)) {
    echo json_encode(['status' => 'error', 'message' => 'Supplier, date, and amount are required']);
    exit;
}

// Insert into database using prepared statement
$stmt = $con->prepare("
    INSERT INTO purchases (supplier_id, purchase_date, total_amount, remarks, status, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
") or die($con->error);

$stmt->bind_param("isdss", $supplier_id, $purchase_date, $total_amount, $remarks, $status);


if ($stmt->execute()) {
    $purchase_id = $stmt->insert_id;
    echo json_encode([
        'status' => 'success',
        'message' => 'Purchase saved successfully',
        'data' => [
            'purchase_id'   => $purchase_id,
            'supplier_id'   => $supplier_id,
            'purchase_date' => $purchase_date,
            'total_amount'  => $total_amount,
            'remarks'       => $remarks
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database insert failed: ' . $stmt->error]);
}

exit;
