<?php

function checkEmployeeAvailability($employee_id, $appointment_date, $appointment_time) {
    global $con;
    
    // Get day of week (0=Sunday, 1=Monday, etc.)
    $day_of_week = date('w', strtotime($appointment_date));
    
    // Check if employee is on leave
    $leave_check = mysqli_query($con, "
        SELECT * FROM employee_time_off 
        WHERE employee_id = '$employee_id' 
        AND status = 'approved'
        AND '$appointment_date' BETWEEN start_date AND end_date
    ");
    
    if (mysqli_num_rows($leave_check) > 0) {
        return false; // Employee is on leave
    }
    
    // Get work schedule for that day
    $schedule_query = mysqli_query($con, "
        SELECT * FROM employee_work_schedule 
        WHERE employee_id = '$employee_id' 
        AND day_of_week = '$day_of_week'
    ");
    
    if (mysqli_num_rows($schedule_query) == 0) {
        return false; // No schedule found
    }
    
    $schedule = mysqli_fetch_assoc($schedule_query);
    
    // Check if it's a day off
    if ($schedule['is_day_off'] == 1) {
        return false;
    }
    
    // Check if appointment time is within working hours
    $appointment_timestamp = strtotime($appointment_time);
    $start_timestamp = strtotime($schedule['start_time']);
    $end_timestamp = strtotime($schedule['end_time']);
    
    if ($appointment_timestamp >= $start_timestamp && $appointment_timestamp <= $end_timestamp) {
        return true;
    }
    
    return false;
}

function getEmployeeAvailableHours($employee_id, $date) {
    global $con;
    
    $day_of_week = date('w', strtotime($date));
    
    $schedule_query = mysqli_query($con, "
        SELECT * FROM employee_work_schedule 
        WHERE employee_id = '$employee_id' 
        AND day_of_week = '$day_of_week'
    ");
    
    if (mysqli_num_rows($schedule_query) == 0) {
        return null;
    }
    
    $schedule = mysqli_fetch_assoc($schedule_query);
    
    if ($schedule['is_day_off'] == 1) {
        return null;
    }
    
    return [
        'start' => $schedule['start_time'],
        'end' => $schedule['end_time']
    ];
}
?>