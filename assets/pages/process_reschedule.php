<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please log in first.']);
    exit();
}

$root_path = $_SERVER['DOCUMENT_ROOT'] . '/RWELL/';
include_once($root_path . 'admin/include/connection.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

$client_id = (int)$_SESSION['client_id'];
$appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
$new_date = isset($_POST['appointment_date']) ? trim($_POST['appointment_date']) : '';
$new_time_display = isset($_POST['appointment_time']) ? trim($_POST['appointment_time']) : '';
$employee_id = isset($_POST['employee_id']) && $_POST['employee_id'] !== '' ? (int)$_POST['employee_id'] : null;
$purpose = isset($_POST['purpose']) ? mysqli_real_escape_string($con, trim($_POST['purpose'])) : '';

if ($appointment_id <= 0 || empty($new_date) || empty($new_time_display)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit();
}

$new_time = date('H:i:s', strtotime($new_time_display));
if (!$new_time || $new_time === '00:00:00' && stripos($new_time_display, '12:') === false) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid time selected.']);
    exit();
}

/*
|--------------------------------------------------------------------------
| Get linked customer_id
|--------------------------------------------------------------------------
*/
$customer_id = null;

if (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) {
    $customer_id = (int)$_SESSION['customer_id'];
} else {
    $cust_query = mysqli_query($con, "SELECT customer_id FROM tbl_client_accounts WHERE client_id = $client_id LIMIT 1");
    if ($cust_query && mysqli_num_rows($cust_query) > 0) {
        $cust = mysqli_fetch_assoc($cust_query);
        if (!empty($cust['customer_id'])) {
            $customer_id = (int)$cust['customer_id'];
            $_SESSION['customer_id'] = $customer_id;
        }
    }
}

if (!$customer_id) {
    echo json_encode(['status' => 'error', 'message' => 'Customer link not found.']);
    exit();
}

/*
|--------------------------------------------------------------------------
| Confirm appointment belongs to this client
|--------------------------------------------------------------------------
*/
$check_query = mysqli_query($con, "
    SELECT appointment_id, status, appointment_date
    FROM appointments
    WHERE appointment_id = $appointment_id
      AND customer_id = $customer_id
    LIMIT 1
");

if (!$check_query || mysqli_num_rows($check_query) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Appointment not found or access denied.']);
    exit();
}

$appointment = mysqli_fetch_assoc($check_query);
$allowed_statuses = ['pending', 'approved', 'confirmed'];

if (!in_array(strtolower($appointment['status']), $allowed_statuses)) {
    echo json_encode(['status' => 'error', 'message' => 'This appointment can no longer be rescheduled.']);
    exit();
}

if (strtotime($appointment['appointment_date']) < strtotime(date('Y-m-d'))) {
    echo json_encode(['status' => 'error', 'message' => 'Past appointments cannot be rescheduled.']);
    exit();
}

$original_date = $appointment['appointment_date'];

if (strtotime($new_date) <= strtotime($original_date)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'You can only reschedule to a date later than your original appointment date.'
    ]);
    exit();
}

/*
|--------------------------------------------------------------------------
| Check conflict for selected employee/no preference
|--------------------------------------------------------------------------
*/
if ($employee_id) {
    $conflict_sql = "
        SELECT appointment_id
        FROM appointments
        WHERE appointment_id != $appointment_id
          AND employee_id = $employee_id
          AND appointment_date = '$new_date'
          AND appointment_time = '$new_time'
          AND status NOT IN ('cancelled', 'completed')
        LIMIT 1
    ";
} else {
    $conflict_sql = "
        SELECT appointment_id
        FROM appointments
        WHERE appointment_id != $appointment_id
          AND employee_id IS NULL
          AND appointment_date = '$new_date'
          AND appointment_time = '$new_time'
          AND status NOT IN ('cancelled', 'completed')
        LIMIT 1
    ";
}

$conflict = mysqli_query($con, $conflict_sql);

if ($conflict && mysqli_num_rows($conflict) > 0) {
    echo json_encode(['status' => 'error', 'message' => 'That time slot is already taken.']);
    exit();
}

/*
|--------------------------------------------------------------------------
| Update appointment
|--------------------------------------------------------------------------
*/
$employee_value = $employee_id ? "'$employee_id'" : "NULL";
$purpose_value = $purpose !== '' ? "'$purpose'" : "NULL";

$update_sql = "
    UPDATE appointments
    SET employee_id = $employee_value,
        appointment_date = '$new_date',
        appointment_time = '$new_time',
        purpose = $purpose_value,
        status = 'pending'
    WHERE appointment_id = $appointment_id
      AND customer_id = $customer_id
    LIMIT 1
";

if (!mysqli_query($con, $update_sql)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to reschedule appointment: ' . mysqli_error($con)
    ]);
    exit();
}

echo json_encode([
    'status' => 'success',
    'message' => 'Your appointment has been rescheduled successfully.'
]);