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
    $supplier_id = mysqli_real_escape_string($con, $_POST['supplier_id']);
    $company_name = mysqli_real_escape_string($con, $_POST['company_name']);
    $contact_person = mysqli_real_escape_string($con, $_POST['contact_person']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $address = mysqli_real_escape_string($con, $_POST['address']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    
    // Get old data for logging
    $old_query = mysqli_query($con, "SELECT * FROM suppliers WHERE supplier_id = '$supplier_id'");
    $old_supplier = mysqli_fetch_assoc($old_query);
    
    // Update supplier
    $update = "UPDATE suppliers SET 
                company_name = '$company_name',
                contact_person = '$contact_person',
                phone = '$phone',
                email = '$email',
                address = '$address',
                status = '$status'
                WHERE supplier_id = '$supplier_id'";
    
    if (mysqli_query($con, $update)) {
        // Log activity with changes
        $changes = [];
        if ($old_supplier['company_name'] != $company_name) $changes[] = "Company: {$old_supplier['company_name']} → $company_name";
        if ($old_supplier['contact_person'] != $contact_person) $changes[] = "Contact: {$old_supplier['contact_person']} → $contact_person";
        if ($old_supplier['phone'] != $phone) $changes[] = "Phone: {$old_supplier['phone']} → $phone";
        if ($old_supplier['status'] != $status) $changes[] = "Status: {$old_supplier['status']} → $status";
        
        $log_details = "Supplier ID: $supplier_id. Changes: " . implode(", ", $changes);
        logActivity("Updated supplier", $log_details);
        
        echo json_encode(['status' => 'success', 'message' => 'Supplier updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
    }
}
?>