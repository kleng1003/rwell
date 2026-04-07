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
    $customer_id = mysqli_real_escape_string($con, $_POST['customer_id']);
    $first_name = mysqli_real_escape_string($con, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($con, $_POST['last_name']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $address = mysqli_real_escape_string($con, $_POST['address']);
    $services = isset($_POST['services']) ? $_POST['services'] : [];
    
    // Get old data for logging
    $old_query = mysqli_query($con, "SELECT * FROM customers WHERE customer_id = '$customer_id'");
    $old_customer = mysqli_fetch_assoc($old_query);
    
    // Update customer
    $update = "UPDATE customers SET 
                first_name = '$first_name',
                last_name = '$last_name',
                phone = '$phone',
                email = '$email',
                address = '$address'
                WHERE customer_id = '$customer_id'";
    
    if (mysqli_query($con, $update)) {
        // Update services - delete old and insert new
        mysqli_query($con, "DELETE FROM customer_services WHERE customer_id = '$customer_id'");
        
        if (!empty($services)) {
            foreach ($services as $service_id) {
                $service_id = mysqli_real_escape_string($con, $service_id);
                mysqli_query($con, "INSERT INTO customer_services (customer_id, service_id) VALUES ('$customer_id', '$service_id')");
            }
        }
        
        // Log activity with changes
        $changes = [];
        if ($old_customer['first_name'] != $first_name) $changes[] = "Name: {$old_customer['first_name']} {$old_customer['last_name']} → $first_name $last_name";
        if ($old_customer['phone'] != $phone) $changes[] = "Phone: {$old_customer['phone']} → $phone";
        if ($old_customer['email'] != $email) $changes[] = "Email: {$old_customer['email']} → $email";
        
        $log_details = "Customer ID: $customer_id. Changes: " . implode(", ", $changes);
        logActivity("Updated customer", $log_details);
        
        echo json_encode(['status' => 'success', 'message' => 'Customer updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
    }
}
?>