<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json');

$root_path = $_SERVER['DOCUMENT_ROOT'] . '/RWELL/';
include_once($root_path . 'admin/include/connection.php');
include_once($root_path . 'admin/include/activity_logger.php');

// Log the request for debugging
$log_file = $root_path . 'booking_debug.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - POST Data: " . print_r($_POST, true) . "\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

try {
    // Get form data
    $first_name = isset($_POST['first_name']) ? mysqli_real_escape_string($con, $_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? mysqli_real_escape_string($con, $_POST['last_name']) : '';
    $phone = isset($_POST['customer_phone']) ? mysqli_real_escape_string($con, $_POST['customer_phone']) : '';
    $email = isset($_POST['customer_email']) ? mysqli_real_escape_string($con, $_POST['customer_email']) : '';
    $address = isset($_POST['customer_address']) ? mysqli_real_escape_string($con, $_POST['customer_address']) : '';
    $employee_id = !empty($_POST['employee_id']) ? mysqli_real_escape_string($con, $_POST['employee_id']) : 'NULL';
    $date = isset($_POST['appointment_date']) ? mysqli_real_escape_string($con, $_POST['appointment_date']) : '';
    $time = isset($_POST['appointment_time']) ? mysqli_real_escape_string($con, $_POST['appointment_time']) : '';
    $service_id = !empty($_POST['service_id']) ? mysqli_real_escape_string($con, $_POST['service_id']) : null;
    $purpose = isset($_POST['purpose']) ? mysqli_real_escape_string($con, $_POST['purpose']) : '';

    // Log the parsed data
    file_put_contents($log_file, "Parsed Data: first_name=$first_name, last_name=$last_name, phone=$phone, date=$date, time=$time\n", FILE_APPEND);

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($phone) || empty($date) || empty($time)) {
        file_put_contents($log_file, "Validation Failed: Missing required fields\n", FILE_APPEND);
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields']);
        exit();
    }

    // Start transaction
    mysqli_begin_transaction($con);

    // Check if customer exists by phone
    $customer_query = mysqli_query($con, "SELECT customer_id FROM customers WHERE phone = '$phone'");
    
    if (mysqli_num_rows($customer_query) > 0) {
        // Customer exists, get the ID
        $customer = mysqli_fetch_assoc($customer_query);
        $customer_id = $customer['customer_id'];
        file_put_contents($log_file, "Existing customer found: ID $customer_id\n", FILE_APPEND);
        
        // Update customer info
        $update_customer = "UPDATE customers SET 
                            first_name = '$first_name',
                            last_name = '$last_name',
                            email = COALESCE(NULLIF('$email', ''), email),
                            address = COALESCE(NULLIF('$address', ''), address)
                            WHERE customer_id = '$customer_id'";
        if (!mysqli_query($con, $update_customer)) {
            throw new Exception("Failed to update customer: " . mysqli_error($con));
        }
        
    } else {
        // Insert new customer
        $insert_customer = "INSERT INTO customers (first_name, last_name, phone, email, address, created_at) 
                           VALUES ('$first_name', '$last_name', '$phone', '$email', '$address', NOW())";
        
        file_put_contents($log_file, "Inserting new customer: $insert_customer\n", FILE_APPEND);
        
        if (!mysqli_query($con, $insert_customer)) {
            throw new Exception("Failed to add customer: " . mysqli_error($con));
        }
        $customer_id = mysqli_insert_id($con);
        file_put_contents($log_file, "New customer created: ID $customer_id\n", FILE_APPEND);
        
        // Log new customer creation
        if (function_exists('logActivity')) {
            logActivity("New customer registered via booking", "Customer: $first_name $last_name, Phone: $phone");
        }
    }
    
    // Check for double booking
    $employee_condition = ($employee_id != 'NULL') ? "employee_id = '$employee_id'" : "employee_id IS NULL";
    $check_slot = mysqli_query($con, "
        SELECT appointment_id FROM appointments 
        WHERE $employee_condition
        AND appointment_date = '$date'
        AND appointment_time = '$time'
        AND status NOT IN ('cancelled')
    ");
    
    if (mysqli_num_rows($check_slot) > 0) {
        throw new Exception("This time slot is already booked. Please choose another time.");
    }
    
    // Insert appointment
    $insert_appointment = "INSERT INTO appointments (customer_id, employee_id, appointment_date, appointment_time, purpose, status, created_at) 
                          VALUES ('$customer_id', " . ($employee_id != 'NULL' ? "'$employee_id'" : "NULL") . ", '$date', '$time', '$purpose', 'pending', NOW())";
    
    file_put_contents($log_file, "Inserting appointment: $insert_appointment\n", FILE_APPEND);
    
    if (!mysqli_query($con, $insert_appointment)) {
        throw new Exception("Failed to book appointment: " . mysqli_error($con));
    }
    
    $appointment_id = mysqli_insert_id($con);
    file_put_contents($log_file, "Appointment created: ID $appointment_id\n", FILE_APPEND);
    
    // Insert service if selected
    if ($service_id) {
        $insert_service = "INSERT INTO customer_services (customer_id, service_id, appointment_id) 
                          VALUES ('$customer_id', '$service_id', '$appointment_id')";
        
        file_put_contents($log_file, "Inserting service: $insert_service\n", FILE_APPEND);
        
        if (!mysqli_query($con, $insert_service)) {
            throw new Exception("Failed to add service: " . mysqli_error($con));
        }
    }
    
    // Commit transaction
    mysqli_commit($con);
    
    // Log the booking
    if (function_exists('logActivity')) {
        logActivity("New appointment booked", "Customer: $first_name $last_name, Date: $date, Time: $time, ID: $appointment_id");
    }
    
    file_put_contents($log_file, "SUCCESS: Booking completed\n", FILE_APPEND);
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'Appointment booked successfully! We will contact you shortly to confirm.',
        'appointment_id' => $appointment_id,
        'customer_id' => $customer_id
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($con);
    $error_msg = $e->getMessage();
    file_put_contents($log_file, "ERROR: " . $error_msg . "\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => $error_msg]);
}
?>