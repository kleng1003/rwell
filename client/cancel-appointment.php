<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['client_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please log in first.'
    ]);
    exit();
}

require_once '../admin/include/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
    exit();
}

$client_id = (int) $_SESSION['client_id'];
$appointment_id = isset($_POST['appointment_id']) ? (int) $_POST['appointment_id'] : 0;

if ($appointment_id <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid appointment ID.'
    ]);
    exit();
}

/*
|--------------------------------------------------------------------------
| Get customer_id of logged-in client
|--------------------------------------------------------------------------
*/
$customer_id = null;

if (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) {
    $customer_id = (int) $_SESSION['customer_id'];
} else {
    $cust_query = mysqli_query(
        $con,
        "SELECT customer_id FROM tbl_client_accounts WHERE client_id = $client_id LIMIT 1"
    );

    if ($cust_query && mysqli_num_rows($cust_query) > 0) {
        $cust = mysqli_fetch_assoc($cust_query);

        if (!empty($cust['customer_id'])) {
            $customer_id = (int) $cust['customer_id'];
            $_SESSION['customer_id'] = $customer_id;
        }
    }
}

if (!$customer_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Your client account is not linked to a customer record.'
    ]);
    exit();
}

/*
|--------------------------------------------------------------------------
| Make sure the appointment belongs to the logged-in client
|--------------------------------------------------------------------------
*/
$check_sql = "
    SELECT appointment_id, customer_id, appointment_date, status
    FROM appointments
    WHERE appointment_id = $appointment_id
      AND customer_id = $customer_id
    LIMIT 1
";

$check_result = mysqli_query($con, $check_sql);

if (!$check_result || mysqli_num_rows($check_result) === 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Reservation not found or access denied.'
    ]);
    exit();
}

$appointment = mysqli_fetch_assoc($check_result);
$current_status = strtolower($appointment['status']);
$appointment_date = $appointment['appointment_date'];

/*
|--------------------------------------------------------------------------
| Only allow cancellation for upcoming pending/approved/confirmed
|--------------------------------------------------------------------------
*/
if (!in_array($current_status, ['pending', 'approved', 'confirmed'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Only pending, approved, or confirmed reservations can be cancelled.'
    ]);
    exit();
}

if (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Past reservations can no longer be cancelled.'
    ]);
    exit();
}

/*
|--------------------------------------------------------------------------
| Cancel reservation
|--------------------------------------------------------------------------
*/
$update_sql = "
    UPDATE appointments
    SET status = 'cancelled'
    WHERE appointment_id = $appointment_id
      AND customer_id = $customer_id
    LIMIT 1
";

if (!mysqli_query($con, $update_sql)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to cancel reservation: ' . mysqli_error($con)
    ]);
    exit();
}

echo json_encode([
    'status' => 'success',
    'message' => 'Your reservation has been cancelled successfully.'
]);