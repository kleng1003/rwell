<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$root_path = $_SERVER['DOCUMENT_ROOT'] . '/RWELL/';
include_once($root_path . 'admin/include/connection.php');

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
    $purpose = isset($_POST['purpose']) ? mysqli_real_escape_string($con, $_POST['purpose']) : '';
    
    // Get multiple service IDs
    $service_ids = isset($_POST['service_ids']) ? $_POST['service_ids'] : [];
    if (is_string($service_ids)) {
        $service_ids = json_decode($service_ids, true);
    }

    // Validate required fields
    if (empty($first_name) || empty($phone) || empty($date) || empty($time)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields']);
        exit();
    }

    if (empty($service_ids) || count($service_ids) == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Please select at least one service']);
        exit();
    }

    // Start transaction
    mysqli_begin_transaction($con);

    // Check if customer exists by phone number
    $customer_id = null;
    if (isset($_SESSION['customer_id'])) {
        $customer_id = $_SESSION['customer_id'];
    }
    
    if (mysqli_num_rows($customer_query) > 0) {
        // Customer exists - update their info
        $customer = mysqli_fetch_assoc($customer_query);
        $customer_id = $customer['customer_id'];
        
        // Build update fields
        $update_fields = [];
        if (!empty($first_name)) $update_fields[] = "first_name = '$first_name'";
        if (!empty($last_name)) $update_fields[] = "last_name = '$last_name'";
        if (!empty($email)) $update_fields[] = "email = '$email'";
        if (!empty($address)) $update_fields[] = "address = '$address'";
        
        if (!empty($update_fields)) {
            $update_sql = "UPDATE customers SET " . implode(", ", $update_fields) . " WHERE customer_id = $customer_id";
            mysqli_query($con, $update_sql);
        }
    } else {
        // Create new customer with first_name and last_name
        $insert_customer = "INSERT INTO customers (first_name, last_name, phone, email, address, status, created_at) 
                           VALUES ('$first_name', '$last_name', '$phone', " . 
                           (!empty($email) ? "'$email'" : "NULL") . ", " . 
                           (!empty($address) ? "'$address'" : "NULL") . ", 'active', NOW())";
        
        if (!mysqli_query($con, $insert_customer)) {
            throw new Exception("Failed to add customer: " . mysqli_error($con));
        }
        $customer_id = mysqli_insert_id($con);
    }
    
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

    // After getting $customer_id, check if this booking is from a logged-in client
    if (isset($_SESSION['client_id'])) {
        $client_id = $_SESSION['client_id'];
        
        // Update the client account with the customer_id if not already set
        $update_client =    "UPDATE tbl_client_accounts 
                            SET customer_id = $customer_id 
                            WHERE client_id = $client_id";
        mysqli_query($con, $update_client);
        
        // Also store in session
        $_SESSION['customer_id'] = $customer_id;
    }
    
    // Insert appointment
    $employee_id_value = $employee_id ? "'$employee_id'" : "NULL";
    $purpose_value = !empty($purpose) ? "'$purpose'" : "NULL";
    
    $insert_appointment = "INSERT INTO appointments (customer_id, employee_id, appointment_date, appointment_time, purpose, status, created_at) 
                          VALUES ('$customer_id', $employee_id_value, '$date', '$time', $purpose_value, 'pending', NOW())";
    
    if (!mysqli_query($con, $insert_appointment)) {
        throw new Exception("Failed to book appointment: " . mysqli_error($con));
    }
    
    $appointment_id = mysqli_insert_id($con);
    
    // Insert multiple services
    foreach ($service_ids as $service_id) {
        $service_id = (int)$service_id;
        $insert_service = "INSERT INTO customer_services (customer_id, service_id, appointment_id) 
                          VALUES ('$customer_id', '$service_id', '$appointment_id')";
        
        if (!mysqli_query($con, $insert_service)) {
            throw new Exception("Failed to add service: " . mysqli_error($con));
        }
    }
    
    // Commit transaction
    mysqli_commit($con);
    
    // Get customer name for response
    $customer_name_query = mysqli_query($con, "SELECT first_name, last_name FROM customers WHERE customer_id = $customer_id");
    $customer_data = mysqli_fetch_assoc($customer_name_query);
    $customer_full_name = trim($customer_data['first_name'] . ' ' . $customer_data['last_name']);
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'Appointment booked successfully!',
        'appointment_id' => $appointment_id,
        'customer_id' => $customer_id,
        'customer_name' => $customer_full_name
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($con);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>