<?php
session_start();
include_once('../include/connection.php');
include_once('../include/activity_logger.php');

ob_clean(); // 🔥 clears unwanted output
header('Content-Type: application/json');

// Check login first
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $appointment_id = mysqli_real_escape_string($con, $_POST['appointment_id']);
    $customer_id = mysqli_real_escape_string($con, $_POST['customer_id']);
    $employee_id = !empty($_POST['employee_id']) ? mysqli_real_escape_string($con, $_POST['employee_id']) : NULL;
    $appointment_date = mysqli_real_escape_string($con, $_POST['appointment_date']);
    $appointment_time = mysqli_real_escape_string($con, $_POST['appointment_time']);
    $purpose = mysqli_real_escape_string($con, $_POST['purpose']);
    $status = mysqli_real_escape_string($con, $_POST['status']);

    // ✅ NOW variables exist → safe to check
    $check_query = mysqli_query($con, "
        SELECT appointment_id FROM appointments 
        WHERE employee_id = '$employee_id' 
        AND appointment_date = '$appointment_date'
        AND appointment_time = '$appointment_time'
        AND appointment_id != '$appointment_id'
        AND status NOT IN ('cancelled')
    ");

    if (mysqli_num_rows($check_query) > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'This time slot is already booked. Please choose another time.'
        ]);
        exit();
    }

    // Get old data
    $old_query = mysqli_query($con, "
        SELECT a.*, 
               CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
               CONCAT(e.first_name, ' ', e.last_name) AS employee_name
        FROM appointments a
        LEFT JOIN customers c ON a.customer_id = c.customer_id
        LEFT JOIN employees e ON a.employee_id = e.employee_id
        WHERE a.appointment_id = '$appointment_id'
    ");
    $old_appointment = mysqli_fetch_assoc($old_query);

    // Update
    $update = "UPDATE appointments SET 
                customer_id = '$customer_id',
                employee_id = " . ($employee_id ? "'$employee_id'" : "NULL") . ",
                appointment_date = '$appointment_date',
                appointment_time = '$appointment_time',
                purpose = '$purpose',
                status = '$status'
                WHERE appointment_id = '$appointment_id'";

    if (mysqli_query($con, $update)) {

        $changes = [];

        if ($old_appointment['appointment_date'] != $appointment_date)
            $changes[] = "Date: {$old_appointment['appointment_date']} → $appointment_date";

        if ($old_appointment['appointment_time'] != $appointment_time)
            $changes[] = "Time: {$old_appointment['appointment_time']} → $appointment_time";

        if ($old_appointment['status'] != $status)
            $changes[] = "Status: {$old_appointment['status']} → $status";

        $log_details = "Appointment ID: $appointment_id. Changes: " . implode(", ", $changes);
        logActivity("Updated appointment", $log_details);

        echo json_encode([
            'status' => 'success',
            'message' => 'Appointment updated successfully',
            'data' => [
                'appointment_id' => $appointment_id,
                'date' => date('M d, Y', strtotime($appointment_date)),
                'time' => date('h:i A', strtotime($appointment_time)),
                'purpose' => htmlspecialchars($purpose),
                'status' => $status,
                'status_badge' => '<span class="status-badge status-' . $status . '">' . ucfirst($status) . '</span>'
            ]
        ]);
        exit();

    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . mysqli_error($con)
        ]);
    }

    exit();
}
?>