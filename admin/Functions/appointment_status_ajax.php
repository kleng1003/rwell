<?php
session_start();
include_once('../include/connection.php');
include_once('../include/activity_logger.php');

header('Content-Type: application/json');

if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

$appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
$new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';

$allowed_statuses = ['pending', 'approved', 'completed', 'cancelled'];

if ($appointment_id <= 0 || !in_array($new_status, $allowed_statuses)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid appointment or status']);
    exit();
}

$old_query = mysqli_query($con, "
    SELECT a.*, CONCAT(c.first_name, ' ', c.last_name) AS customer_name
    FROM appointments a
    LEFT JOIN customers c ON a.customer_id = c.customer_id
    WHERE a.appointment_id = '$appointment_id'
");

if (!$old_query || mysqli_num_rows($old_query) == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Appointment not found']);
    exit();
}

$old_appointment = mysqli_fetch_assoc($old_query);
$old_status = $old_appointment['status'];

$update = mysqli_query($con, "
    UPDATE appointments
    SET status = '$new_status'
    WHERE appointment_id = '$appointment_id'
");

if ($update) {
    logActivity(
        "Updated appointment status",
        "Appointment ID: $appointment_id, Customer: {$old_appointment['customer_name']}, Status: {$old_status} → {$new_status}"
    );

    echo json_encode([
        'status' => 'success',
        'message' => 'Appointment status updated successfully',
        'new_status' => $new_status,
        'status_badge' => '
            <div class="dropdown">
                <button class="btn btn-sm dropdown-toggle status-btn status-' . $new_status . '" 
                        type="button" 
                        data-toggle="dropdown" 
                        aria-haspopup="true" 
                        aria-expanded="false">
                    ' . ucfirst($new_status) . '
                </button>
                <ul class="dropdown-menu dropdown-menu-right status-menu">
                    <li><a href="#" class="changeStatusBtn" data-id="' . $appointment_id . '" data-status="pending">Pending</a></li>
                    <li><a href="#" class="changeStatusBtn" data-id="' . $appointment_id . '" data-status="approved">Approved</a></li>
                    <li><a href="#" class="changeStatusBtn" data-id="' . $appointment_id . '" data-status="completed">Completed</a></li>
                    <li><a href="#" class="changeStatusBtn" data-id="' . $appointment_id . '" data-status="cancelled">Cancelled</a></li>
                </ul>
            </div>'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update appointment status: ' . mysqli_error($con)
    ]);
}
?>