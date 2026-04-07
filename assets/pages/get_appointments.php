<?php
header('Content-Type: application/json');
include_once('../../admin/include/connection.php');

$events = [];

$query = mysqli_query($con, "
    SELECT a.*, 
           CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
           s.service_name
    FROM appointments a
    LEFT JOIN customers c ON a.customer_id = c.customer_id
    LEFT JOIN customer_services cs ON a.appointment_id = cs.appointment_id
    LEFT JOIN services s ON cs.service_id = s.service_id
    WHERE a.status NOT IN ('cancelled')
    ORDER BY a.appointment_date ASC
");

while ($row = mysqli_fetch_assoc($query)) {
    $title = $row['customer_name'] . ($row['service_name'] ? ' - ' . $row['service_name'] : '');
    $start = $row['appointment_date'] . 'T' . $row['appointment_time'];
    
    // Determine color based on status
    $color = '#e91e63'; // default pink
    if ($row['status'] == 'completed') $color = '#28a745';
    if ($row['status'] == 'cancelled') $color = '#dc3545';
    if ($row['status'] == 'pending') $color = '#ffc107';
    
    $events[] = [
        'title' => $title,
        'start' => $start,
        'color' => $color,
        'extendedProps' => [
            'status' => $row['status'],
            'purpose' => $row['purpose']
        ]
    ];
}

echo json_encode($events);
?>