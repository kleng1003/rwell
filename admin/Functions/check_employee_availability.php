<?php
session_start();
include_once('../include/connection.php');

header('Content-Type: application/json');

if (!isset($_POST['employee_id']) || !isset($_POST['date']) || !isset($_POST['time'])) {
    echo json_encode(['available' => false]);
    exit();
}

$employee_id = mysqli_real_escape_string($con, $_POST['employee_id']);
$date = mysqli_real_escape_string($con, $_POST['date']);
$time = mysqli_real_escape_string($con, $_POST['time']);

// Get day of week
$day_of_week = date('w', strtotime($date));

// Check work schedule
$schedule_query = mysqli_query($con, "
    SELECT * FROM employee_work_schedule 
    WHERE employee_id = '$employee_id' 
    AND day_of_week = '$day_of_week'
");

if (mysqli_num_rows($schedule_query) == 0) {
    echo json_encode(['available' => false, 'message' => 'No schedule found for this day']);
    exit();
}

$schedule = mysqli_fetch_assoc($schedule_query);

// Check if day off
if ($schedule['is_day_off'] == 1) {
    echo json_encode(['available' => false, 'message' => 'Employee is off on this day']);
    exit();
}

// Check time
$time_timestamp = strtotime($time);
$start_timestamp = strtotime($schedule['start_time']);
$end_timestamp = strtotime($schedule['end_time']);

if ($time_timestamp >= $start_timestamp && $time_timestamp <= $end_timestamp) {
    echo json_encode(['available' => true]);
} else {
    echo json_encode(['available' => false, 'message' => 'Outside working hours']);
}
?>