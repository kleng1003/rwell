<?php
// ../Functions/appointment_add_ajax.php
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
    // Get and sanitize inputs
    $customer_id = mysqli_real_escape_string($con, $_POST['customer_id']);
    $employee_id = !empty($_POST['employee_id']) ? mysqli_real_escape_string($con, $_POST['employee_id']) : 'NULL';
    $appointment_date = mysqli_real_escape_string($con, $_POST['appointment_date']);
    $appointment_time = mysqli_real_escape_string($con, $_POST['appointment_time']);
    $purpose = mysqli_real_escape_string($con, $_POST['purpose']);
    $status = 'pending';
    
    // Quick validation
    if (empty($customer_id) || empty($appointment_date) || empty($appointment_time)) {
        echo json_encode(['status' => 'error', 'message' => 'Required fields are missing']);
        exit();
    }
    
    // Check for double booking - OPTIMIZED QUERY
    $check_query = "SELECT appointment_id FROM appointments 
                    WHERE employee_id = '$employee_id' 
                    AND appointment_date = '$appointment_date'
                    AND appointment_time = '$appointment_time'
                    AND status NOT IN ('cancelled')
                    LIMIT 1"; // Added LIMIT for performance
    
    $check_result = mysqli_query($con, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'This time slot is already booked. Please choose another time.']);
        exit();
    }
    
    // OPTIMIZED: Single insert query without additional selects
    $insert = "INSERT INTO appointments (customer_id, employee_id, appointment_date, appointment_time, purpose, status, created_at) 
               VALUES ('$customer_id', " . ($employee_id != 'NULL' ? "'$employee_id'" : "NULL") . ", '$appointment_date', '$appointment_time', '$purpose', '$status', NOW())";
    
    if (mysqli_query($con, $insert)) {
        $appointment_id = mysqli_insert_id($con);
        
        // OPTIMIZED: Get customer name in a single query with JOIN
        $info_query = "SELECT 
                        CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
                        CONCAT(e.first_name, ' ', e.last_name) AS employee_name
                       FROM customers c
                       LEFT JOIN employees e ON e.employee_id = '$employee_id'
                       WHERE c.customer_id = '$customer_id'";
        $info_result = mysqli_query($con, $info_query);
        $info = mysqli_fetch_assoc($info_result);
        
        // Log activity (this should be fast)
        $log_message = "Customer: {$info['customer_name']}, Date: $appointment_date, Time: $appointment_time";
        if ($employee_id != 'NULL' && isset($info['employee_name'])) {
            $log_message .= ", Employee: {$info['employee_name']}";
        }
        logActivity("Added new appointment", $log_message);
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Appointment added successfully',
            'appointment_id' => $appointment_id
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
    }
}
?>