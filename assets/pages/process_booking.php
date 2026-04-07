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
    $first_name = isset($_POST['first_name']) ? trim(mysqli_real_escape_string($con, $_POST['first_name'])) : '';
    $last_name = isset($_POST['last_name']) ? trim(mysqli_real_escape_string($con, $_POST['last_name'])) : '';
    $phone = isset($_POST['customer_phone']) ? trim(mysqli_real_escape_string($con, $_POST['customer_phone'])) : '';
    $email = isset($_POST['customer_email']) ? trim(mysqli_real_escape_string($con, $_POST['customer_email'])) : '';
    $address = isset($_POST['customer_address']) ? trim(mysqli_real_escape_string($con, $_POST['customer_address'])) : '';
    $employee_id = !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null;
    $date = isset($_POST['appointment_date']) ? mysqli_real_escape_string($con, $_POST['appointment_date']) : '';
    $time = isset($_POST['appointment_time']) ? mysqli_real_escape_string($con, $_POST['appointment_time']) : '';
    $service_id = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null;
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

    // CRITICAL FIX: Check if customer exists by phone number FIRST
    $customer_id = null;
    $customer_exists = false;
    
    // First, try to find by phone
    $customer_query = mysqli_query($con, "SELECT customer_id, first_name, last_name, phone, email, address FROM customers WHERE phone = '$phone'");
    
    if (mysqli_num_rows($customer_query) > 0) {
        // Customer exists with this phone number
        $customer = mysqli_fetch_assoc($customer_query);
        $customer_id = $customer['customer_id'];
        $customer_exists = true;
        
        file_put_contents($log_file, "Existing customer found by phone: ID $customer_id, Name: {$customer['first_name']} {$customer['last_name']}\n", FILE_APPEND);
        
        // OPTIONAL: Check if name matches existing record
        $existing_full_name = strtolower(trim($customer['first_name'] . $customer['last_name']));
        $submitted_full_name = strtolower(trim($first_name . $last_name));
        
        if ($existing_full_name != $submitted_full_name) {
            file_put_contents($log_file, "WARNING: Name mismatch! Existing: {$customer['first_name']} {$customer['last_name']}, Submitted: $first_name $last_name\n", FILE_APPEND);
            // Don't update the name - keep the original customer name
        }
        
        // Only update email and address if they are provided and different
        $update_fields = array();
        if (!empty($email) && $email != $customer['email']) {
            $update_fields[] = "email = '$email'";
        }
        if (!empty($address) && $address != $customer['address']) {
            $update_fields[] = "address = '$address'";
        }
        
        if (!empty($update_fields)) {
            $update_sql = "UPDATE customers SET " . implode(", ", $update_fields) . " WHERE customer_id = $customer_id";
            if (!mysqli_query($con, $update_sql)) {
                throw new Exception("Failed to update customer: " . mysqli_error($con));
            }
            file_put_contents($log_file, "Updated customer contact info: $update_sql\n", FILE_APPEND);
        }
        
    } else {
        // Check by email if phone not found
        if (!empty($email)) {
            $email_query = mysqli_query($con, "SELECT customer_id, first_name, last_name FROM customers WHERE email = '$email'");
            if (mysqli_num_rows($email_query) > 0) {
                $customer = mysqli_fetch_assoc($email_query);
                $customer_id = $customer['customer_id'];
                $customer_exists = true;
                file_put_contents($log_file, "Existing customer found by email: ID $customer_id, Name: {$customer['first_name']} {$customer['last_name']}\n", FILE_APPEND);
            }
        }
        
        // If no customer found, create new one
        if (!$customer_exists) {
            $insert_customer = "INSERT INTO customers (first_name, last_name, phone, email, address, status, created_at) 
                               VALUES ('$first_name', '$last_name', '$phone', " . 
                               (!empty($email) ? "'$email'" : "NULL") . ", " . 
                               (!empty($address) ? "'$address'" : "NULL") . ", 'active', NOW())";
            
            file_put_contents($log_file, "Inserting new customer: $insert_customer\n", FILE_APPEND);
            
            if (!mysqli_query($con, $insert_customer)) {
                throw new Exception("Failed to add customer: " . mysqli_error($con));
            }
            $customer_id = mysqli_insert_id($con);
            file_put_contents($log_file, "New customer created: ID $customer_id, Name: $first_name $last_name\n", FILE_APPEND);
            
            // Log new customer creation
            if (function_exists('logActivity')) {
                logActivity("New customer registered via booking", "Customer: $first_name $last_name, Phone: $phone, ID: $customer_id");
            }
        }
    }
    
    // Verify we have a customer_id
    if (!$customer_id) {
        throw new Exception("Failed to identify or create customer record");
    }
    
    // Check for double booking
    $employee_condition = $employee_id ? "employee_id = $employee_id" : "employee_id IS NULL";
    $check_slot = mysqli_query($con, "
        SELECT appointment_id FROM appointments 
        WHERE $employee_condition
        AND appointment_date = '$date'
        AND appointment_time = '$time'
        AND status NOT IN ('cancelled', 'completed')
    ");
    
    if (mysqli_num_rows($check_slot) > 0) {
        throw new Exception("This time slot is already booked. Please choose another time.");
    }
    
    // Insert appointment
    $employee_id_value = $employee_id ? "'$employee_id'" : "NULL";
    $purpose_value = !empty($purpose) ? "'$purpose'" : "NULL";
    
    $insert_appointment = "INSERT INTO appointments (customer_id, employee_id, appointment_date, appointment_time, purpose, status, created_at) 
                          VALUES ('$customer_id', $employee_id_value, '$date', '$time', $purpose_value, 'pending', NOW())";
    
    file_put_contents($log_file, "Inserting appointment: $insert_appointment\n", FILE_APPEND);
    
    if (!mysqli_query($con, $insert_appointment)) {
        throw new Exception("Failed to book appointment: " . mysqli_error($con));
    }
    
    $appointment_id = mysqli_insert_id($con);
    file_put_contents($log_file, "Appointment created: ID $appointment_id for Customer ID $customer_id\n", FILE_APPEND);
    
    // Insert service if selected (note: your appointments table doesn't have service_id, so this goes to customer_services)
    if ($service_id) {
        // Check if customer_services table exists
        $table_check = mysqli_query($con, "SHOW TABLES LIKE 'customer_services'");
        if (mysqli_num_rows($table_check) > 0) {
            $insert_service = "INSERT INTO customer_services (customer_id, service_id, appointment_id) 
                              VALUES ('$customer_id', '$service_id', '$appointment_id')";
            
            file_put_contents($log_file, "Inserting service: $insert_service\n", FILE_APPEND);
            
            if (!mysqli_query($con, $insert_service)) {
                // Log error but don't fail the booking
                file_put_contents($log_file, "WARNING: Failed to add service: " . mysqli_error($con) . "\n", FILE_APPEND);
            }
        }
    }
    
    // Commit transaction
    mysqli_commit($con);
    
    // Log the booking
    if (function_exists('logActivity')) {
        logActivity("New appointment booked", "Customer ID: $customer_id, Date: $date, Time: $time, Appointment ID: $appointment_id");
    }
    
    // Get customer name for response
    $customer_name_query = mysqli_query($con, "SELECT first_name, last_name FROM customers WHERE customer_id = $customer_id");
    $customer_data = mysqli_fetch_assoc($customer_name_query);
    $customer_full_name = $customer_data['first_name'] . ' ' . $customer_data['last_name'];
    
    file_put_contents($log_file, "SUCCESS: Booking completed for $customer_full_name (ID: $customer_id)\n", FILE_APPEND);
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'Appointment booked successfully! We will contact you shortly to confirm.',
        'appointment_id' => $appointment_id,
        'customer_id' => $customer_id,
        'customer_name' => $customer_full_name
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($con);
    $error_msg = $e->getMessage();
    file_put_contents($log_file, "ERROR: " . $error_msg . "\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => $error_msg]);
}
?>