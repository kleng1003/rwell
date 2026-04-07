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
    $company_name = mysqli_real_escape_string($con, $_POST['company_name']);
    $contact_person = mysqli_real_escape_string($con, $_POST['contact_person']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $address = mysqli_real_escape_string($con, $_POST['address']);
    
    // Validate
    if (empty($company_name) || empty($contact_person) || empty($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'Required fields are missing']);
        exit();
    }
    
    // Insert supplier
    $insert = "INSERT INTO suppliers (company_name, contact_person, phone, email, address, status, created_at) 
               VALUES ('$company_name', '$contact_person', '$phone', '$email', '$address', 'active', NOW())";
    
    if (mysqli_query($con, $insert)) {
        $supplier_id = mysqli_insert_id($con);
        
        // Log activity
        logActivity("Added new supplier", "Supplier: $company_name, Contact: $contact_person, ID: $supplier_id");
        
        // Get the newly created supplier data
        $supplier_query = mysqli_query($con, "SELECT * FROM suppliers WHERE supplier_id = '$supplier_id'");
        $supplier = mysqli_fetch_assoc($supplier_query);
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Supplier added successfully',
            'supplier' => $supplier
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
    }
}
?>