<?php
session_start();
include_once('../include/connection.php');
include_once('../include/activity_logger.php');

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = mysqli_real_escape_string($con, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($con, $_POST['last_name']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $address = mysqli_real_escape_string($con, $_POST['address']);
    $services = isset($_POST['services']) ? $_POST['services'] : [];
    
    // Validate
    if (empty($first_name) || empty($last_name) || empty($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'Required fields are missing']);
        exit();
    }
    
    // Insert customer
    $insert = "INSERT INTO customers (first_name, last_name, phone, email, address, created_at) 
               VALUES ('$first_name', '$last_name', '$phone', '$email', '$address', NOW())";
    
    if (mysqli_query($con, $insert)) {
        $customer_id = mysqli_insert_id($con);
        
        // Insert services
        if (!empty($services)) {
            foreach ($services as $service_id) {
                $service_id = mysqli_real_escape_string($con, $service_id);
                mysqli_query($con, "INSERT INTO customer_services (customer_id, service_id) VALUES ('$customer_id', '$service_id')");
            }
        }
        
        // Log activity
        logActivity("Added new customer", "Customer: $first_name $last_name, ID: $customer_id");
        
        // Get the newly created customer data
        $customer_query = mysqli_query($con, "
            SELECT c.*, 
                   GROUP_CONCAT(s.service_name SEPARATOR ', ') as services
            FROM customers c
            LEFT JOIN customer_services cs ON c.customer_id = cs.customer_id
            LEFT JOIN services s ON cs.service_id = s.service_id
            WHERE c.customer_id = '$customer_id'
            GROUP BY c.customer_id
        ");
        $customer = mysqli_fetch_assoc($customer_query);
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Customer added successfully',
            'customer' => $customer
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
    }
}
?>