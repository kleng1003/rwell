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

if (isset($_POST['id'])) {
    $appointment_id = mysqli_real_escape_string($con, $_POST['id']);
    
    // Get appointment details for logging
    $appointment_query = mysqli_query($con, "
        SELECT a.*, 
               CONCAT(c.first_name, ' ', c.last_name) AS customer_name
        FROM appointments a
        LEFT JOIN customers c ON a.customer_id = c.customer_id
        WHERE a.appointment_id = '$appointment_id'
    ");
    $appointment = mysqli_fetch_assoc($appointment_query);
    
    // Cancel the appointment (set status to cancelled)
    $cancel = mysqli_query($con, "UPDATE appointments SET status = 'cancelled' WHERE appointment_id = '$appointment_id'");
    
    if ($cancel) {
        logActivity("Cancelled appointment", "Customer: {$appointment['customer_name']}, Date: {$appointment['appointment_date']}, ID: $appointment_id");
        echo json_encode(['status' => 'success', 'message' => 'Appointment cancelled successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error cancelling appointment: ' . mysqli_error($con)]);
    }
}
?>