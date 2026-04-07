<?php
// ../Functions/supplier_restore.php
session_start();
header('Content-Type: application/json');

include_once('../include/connection.php');

// Check if user is logged in
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Check if ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid supplier ID']);
    exit();
}

$supplier_id = mysqli_real_escape_string($con, $_POST['id']);

// Check if supplier exists
$check_query = mysqli_query($con, "SELECT * FROM suppliers WHERE supplier_id = '$supplier_id'");
if (mysqli_num_rows($check_query) == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Supplier not found']);
    exit();
}

// Restore the supplier (set status back to active)
$restore_query = "UPDATE suppliers SET status = 'active' WHERE supplier_id = '$supplier_id'";

if (mysqli_query($con, $restore_query)) {
    echo json_encode(['status' => 'success', 'message' => 'Supplier restored successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
}
?>