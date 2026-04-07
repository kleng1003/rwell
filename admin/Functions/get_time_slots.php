<?php
// ../Functions/get_time_slots.php
session_start();
include_once('../include/connection.php');

header('Content-Type: application/json');

if (!isset($_POST['date']) || !isset($_POST['employee_id'])) {
    echo json_encode(['error' => 'Missing parameters', 'slots' => []]);
    exit();
}

$date = mysqli_real_escape_string($con, $_POST['date']);
$employee_id = mysqli_real_escape_string($con, $_POST['employee_id']);

// Get day of week (0=Sunday, 1=Monday, etc.)
$day_of_week = date('w', strtotime($date));

// OPTIMIZED: Single query to get schedule and existing appointments
$schedule_query = mysqli_query($con, "
    SELECT start_time, end_time, is_day_off 
    FROM employee_work_schedule 
    WHERE employee_id = '$employee_id' AND day_of_week = '$day_of_week'
    LIMIT 1
");

// Default business hours
$start_time = '09:00:00';
$end_time = '18:00:00';
$is_day_off = 0;

if (mysqli_num_rows($schedule_query) > 0) {
    $schedule = mysqli_fetch_assoc($schedule_query);
    $start_time = $schedule['start_time'];
    $end_time = $schedule['end_time'];
    $is_day_off = $schedule['is_day_off'];
}

// If day off, return no slots
if ($is_day_off == 1) {
    echo json_encode(['error' => 'Employee is off on this day', 'slots' => []]);
    exit();
}

// OPTIMIZED: Get booked times in a single query
$booked_times = [];
$appointments_query = mysqli_query($con, "
    SELECT appointment_time 
    FROM appointments 
    WHERE employee_id = '$employee_id' 
    AND appointment_date = '$date'
    AND status NOT IN ('cancelled')
");

while ($apt = mysqli_fetch_assoc($appointments_query)) {
    $booked_times[] = $apt['appointment_time'];
}

// Generate available time slots (30-minute intervals for better selection)
$available_slots = [];
$start_timestamp = strtotime($start_time);
$end_timestamp = strtotime($end_time);
$slot_duration = 30 * 60; // 30-minute slots

for ($current = $start_timestamp; $current < $end_timestamp; $current += $slot_duration) {
    $time_slot = date('H:i:s', $current);
    $time_12h = date('h:i A', $current);
    
    // Check if slot is booked
    if (!in_array($time_slot, $booked_times)) {
        $available_slots[] = $time_12h;
    }
}

echo json_encode([
    'slots' => $available_slots,
    'start_time' => date('h:i A', $start_timestamp),
    'end_time' => date('h:i A', $end_timestamp),
    'booked_times' => array_map(function($t) { return date('h:i A', strtotime($t)); }, $booked_times)
]);
?>