<?php
header('Content-Type: application/json');
include_once('../../admin/include/connection.php');

if (!isset($_POST['date'])) {
    echo json_encode(['error' => 'Missing date', 'slots' => []]);
    exit();
}

$date = mysqli_real_escape_string($con, $_POST['date']);
$employee_id = isset($_POST['employee_id']) && !empty($_POST['employee_id']) ? mysqli_real_escape_string($con, $_POST['employee_id']) : null;

$day_of_week = date('w', strtotime($date));

if ($employee_id) {
    $schedule_query = mysqli_query($con, "
        SELECT start_time, end_time, is_day_off 
        FROM employee_work_schedule 
        WHERE employee_id = '$employee_id' AND day_of_week = '$day_of_week'
        LIMIT 1
    ");
} else {
    $schedule_query = null;
}

$start_time = '09:00:00';
$end_time = '18:00:00';
$is_day_off = 0;

if ($employee_id && mysqli_num_rows($schedule_query) > 0) {
    $schedule = mysqli_fetch_assoc($schedule_query);
    $start_time = $schedule['start_time'];
    $end_time = $schedule['end_time'];
    $is_day_off = $schedule['is_day_off'];
}

if ($is_day_off == 1) {
    echo json_encode(['error' => 'Staff is off on this day', 'slots' => []]);
    exit();
}

$booked_times = [];
if ($employee_id) {
    $appointments_query = mysqli_query($con, "
        SELECT appointment_time 
        FROM appointments 
        WHERE employee_id = '$employee_id' 
        AND appointment_date = '$date'
        AND status NOT IN ('cancelled')
    ");
} else {
    $appointments_query = mysqli_query($con, "
        SELECT appointment_time 
        FROM appointments 
        WHERE appointment_date = '$date'
        AND status NOT IN ('cancelled')
    ");
}

while ($apt = mysqli_fetch_assoc($appointments_query)) {
    $booked_times[] = $apt['appointment_time'];
}

$available_slots = [];
$start_timestamp = strtotime($start_time);
$end_timestamp = strtotime($end_time);
$slot_duration = 30 * 60; // 30-minute slots

for ($current = $start_timestamp; $current < $end_timestamp; $current += $slot_duration) {
    $time_slot = date('H:i:s', $current);
    $time_12h = date('h:i A', $current);
    
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