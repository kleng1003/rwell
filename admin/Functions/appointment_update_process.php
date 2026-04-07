<?php
// ../Functions/appointment_update_process.php
session_start();
include_once('../include/connection.php');
include_once('../include/activity_logger.php');

// Check if user is logged in
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_appointment'])) {
    $appointment_id = mysqli_real_escape_string($con, $_POST['appointment_id']);
    $customer_id = mysqli_real_escape_string($con, $_POST['customer_id']);
    $employee_id = !empty($_POST['employee_id']) ? mysqli_real_escape_string($con, $_POST['employee_id']) : 'NULL';
    $appointment_date = mysqli_real_escape_string($con, $_POST['appointment_date']);
    $appointment_time = mysqli_real_escape_string($con, $_POST['appointment_time']);
    $purpose = mysqli_real_escape_string($con, $_POST['purpose']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    
    // Get old appointment data for logging
    $old_query = mysqli_query($con, "SELECT * FROM appointments WHERE appointment_id = '$appointment_id'");
    $old_appointment = mysqli_fetch_assoc($old_query);
    
    // Update appointment
    $update = "UPDATE appointments SET 
                customer_id = '$customer_id',
                employee_id = " . ($employee_id != 'NULL' ? "'$employee_id'" : "NULL") . ",
                appointment_date = '$appointment_date',
                appointment_time = '$appointment_time',
                purpose = '$purpose',
                status = '$status'
                WHERE appointment_id = '$appointment_id'";
    
    if (mysqli_query($con, $update)) {
        // Log activity with changes
        $changes = [];
        if ($old_appointment['status'] != $status) $changes[] = "Status: {$old_appointment['status']} → $status";
        if ($old_appointment['appointment_date'] != $appointment_date) $changes[] = "Date: {$old_appointment['appointment_date']} → $appointment_date";
        if ($old_appointment['appointment_time'] != $appointment_time) $changes[] = "Time: {$old_appointment['appointment_time']} → $appointment_time";
        
        // Get customer name for logging
        $customer_query = mysqli_query($con, "SELECT CONCAT(first_name, ' ', last_name) AS name FROM customers WHERE customer_id = '$customer_id'");
        $customer = mysqli_fetch_assoc($customer_query);
        
        $log_details = "Appointment ID: $appointment_id, Customer: {$customer['name']}. Changes: " . implode(", ", $changes);
        logActivity("Updated appointment", $log_details);
        
        $_SESSION['success'] = "Appointment updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating appointment: " . mysqli_error($con);
    }
    
    header("Location: ../Pages/appointments.php");
    exit();
}
?>