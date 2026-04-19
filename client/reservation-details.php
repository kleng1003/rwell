<?php
session_start();

if (!isset($_SESSION['client_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../admin/include/connection.php';

$client_id = (int) $_SESSION['client_id'];
$appointment_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($appointment_id <= 0) {
    die("Invalid reservation ID.");
}

/*
|--------------------------------------------------------------------------
| Get customer_id from session or tbl_client_accounts
|--------------------------------------------------------------------------
*/
$customer_id = null;

if (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) {
    $customer_id = (int) $_SESSION['customer_id'];
} else {
    $cust_query = mysqli_query($con, "SELECT customer_id FROM tbl_client_accounts WHERE client_id = $client_id LIMIT 1");

    if ($cust_query && mysqli_num_rows($cust_query) > 0) {
        $cust = mysqli_fetch_assoc($cust_query);

        if (!empty($cust['customer_id'])) {
            $customer_id = (int) $cust['customer_id'];
            $_SESSION['customer_id'] = $customer_id;
        }
    }
}

if (!$customer_id) {
    die("Your client account is not linked to a customer record.");
}

/*
|--------------------------------------------------------------------------
| Get appointment details and make sure it belongs to this client
|--------------------------------------------------------------------------
*/
$details_query = "
    SELECT
        a.appointment_id,
        a.customer_id,
        a.employee_id,
        a.appointment_date,
        a.appointment_time,
        a.purpose,
        a.status,
        a.created_at,

        c.first_name AS customer_first_name,
        c.last_name AS customer_last_name,
        c.phone AS customer_phone,
        c.email AS customer_email,
        c.address AS customer_address,

        e.first_name AS employee_first_name,
        e.last_name AS employee_last_name,
        e.position AS employee_position
    FROM appointments a
    LEFT JOIN customers c ON a.customer_id = c.customer_id
    LEFT JOIN employees e ON a.employee_id = e.employee_id
    WHERE a.appointment_id = $appointment_id
      AND a.customer_id = $customer_id
    LIMIT 1
";

$details_result = mysqli_query($con, $details_query);

if (!$details_result || mysqli_num_rows($details_result) === 0) {
    die("Reservation not found or access denied.");
}

$reservation = mysqli_fetch_assoc($details_result);

/*
|--------------------------------------------------------------------------
| Get all services for this appointment
|--------------------------------------------------------------------------
*/
$services = [];
$services_query = "
    SELECT
        s.service_id,
        s.service_name,
        s.price,
        s.duration
    FROM customer_services cs
    INNER JOIN services s ON cs.service_id = s.service_id
    WHERE cs.appointment_id = $appointment_id
    ORDER BY s.service_name ASC
";

$services_result = mysqli_query($con, $services_query);

$total_amount = 0;
$total_duration = 0;

if ($services_result) {
    while ($row = mysqli_fetch_assoc($services_result)) {
        $services[] = $row;
        $total_amount += (float) $row['price'];
        $total_duration += (int) $row['duration'];
    }
}

function formatDateValue($date) {
    return date('F j, Y', strtotime($date));
}

function formatTimeValue($time) {
    return date('g:i A', strtotime($time));
}

function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'pending':
            return 'bg-warning text-dark';
        case 'approved':
        case 'confirmed':
            return 'bg-success';
        case 'completed':
            return 'bg-info text-dark';
        case 'cancelled':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

$status = strtolower($reservation['status']);
$can_reschedule = in_array($status, ['pending', 'approved', 'confirmed']) &&
                  strtotime($reservation['appointment_date']) >= strtotime(date('Y-m-d'));
$can_cancel = in_array($status, ['pending', 'approved', 'confirmed']) &&
              strtotime($reservation['appointment_date']) >= strtotime(date('Y-m-d'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Details - R-Well Salon & Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
        }
        .page-header {
            background: linear-gradient(135deg, #e91e63, #ff6b6b);
            color: white;
            padding: 35px 0;
            margin-bottom: 30px;
        }
        .details-card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 6px 24px rgba(0,0,0,0.06);
            margin-bottom: 24px;
        }
        .details-card .card-header {
            background: #fff;
            border-bottom: 1px solid #f1f1f1;
            font-weight: 700;
            padding: 18px 22px;
        }
        .details-card .card-body {
            padding: 22px;
        }
        .detail-row {
            display: flex;
            gap: 12px;
            margin-bottom: 14px;
            align-items: flex-start;
        }
        .detail-row i {
            color: #e91e63;
            width: 22px;
            margin-top: 2px;
        }
        .service-item {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #f1f1f1;
        }
        .service-item:last-child {
            border-bottom: none;
        }
        .summary-box {
            background: #fff5f8;
            border-radius: 12px;
            padding: 16px;
        }
        .action-btn {
            border-radius: 25px;
            padding: 10px 20px;
        }
    </style>
</head>
<body>

<div class="page-header">
    <div class="container">
        <h2 class="mb-1"><i class="bi bi-receipt-cutoff me-2"></i>Reservation Details</h2>
        <p class="mb-0">View complete information about your appointment</p>
    </div>
</div>

<div class="container mb-5">
    <div class="mb-3 d-flex flex-wrap gap-2">
        <a href="my-reservations.php" class="btn btn-outline-secondary action-btn">
            <i class="bi bi-arrow-left"></i> Back to My Reservations
        </a>

        <?php if ($can_reschedule): ?>
            <a href="../assets/pages/reschedule.php?id=<?php echo $reservation['appointment_id']; ?>" class="btn btn-outline-primary action-btn">
                <i class="bi bi-pencil-square"></i> Reschedule
            </a>
        <?php endif; ?>

        <?php if ($can_cancel): ?>
            <button class="btn btn-outline-danger action-btn" onclick="cancelAppointment(<?php echo $reservation['appointment_id']; ?>)">
                <i class="bi bi-x-circle"></i> Cancel Reservation
            </button>
        <?php endif; ?>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card details-card">
                <div class="card-header">
                    Appointment Information
                </div>
                <div class="card-body">
                    <div class="detail-row">
                        <i class="bi bi-hash"></i>
                        <div>
                            <strong>Reservation ID:</strong><br>
                            #<?php echo str_pad($reservation['appointment_id'], 5, '0', STR_PAD_LEFT); ?>
                        </div>
                    </div>

                    <div class="detail-row">
                        <i class="bi bi-calendar-event"></i>
                        <div>
                            <strong>Appointment Date:</strong><br>
                            <?php echo formatDateValue($reservation['appointment_date']); ?>
                        </div>
                    </div>

                    <div class="detail-row">
                        <i class="bi bi-clock"></i>
                        <div>
                            <strong>Appointment Time:</strong><br>
                            <?php echo formatTimeValue($reservation['appointment_time']); ?>
                        </div>
                    </div>

                    <div class="detail-row">
                        <i class="bi bi-patch-check"></i>
                        <div>
                            <strong>Status:</strong><br>
                            <span class="badge <?php echo getStatusBadgeClass($reservation['status']); ?>">
                                <?php echo ucfirst($reservation['status']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="detail-row">
                        <i class="bi bi-chat-dots"></i>
                        <div>
                            <strong>Special Request / Note:</strong><br>
                            <?php echo !empty($reservation['purpose']) ? nl2br(htmlspecialchars($reservation['purpose'])) : '<span class="text-muted">No notes provided.</span>'; ?>
                        </div>
                    </div>

                    <div class="detail-row">
                        <i class="bi bi-clock-history"></i>
                        <div>
                            <strong>Date Booked:</strong><br>
                            <?php echo date('F j, Y g:i A', strtotime($reservation['created_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card details-card">
                <div class="card-header">
                    Selected Services
                </div>
                <div class="card-body">
                    <?php if (!empty($services)): ?>
                        <?php foreach ($services as $service): ?>
                            <div class="service-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($service['service_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo (int)$service['duration']; ?> minutes</small>
                                </div>
                                <div class="text-end">
                                    ₱<?php echo number_format((float)$service['price'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">No services found for this appointment.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card details-card">
                <div class="card-header">
                    Client Details
                </div>
                <div class="card-body">
                    <div class="detail-row">
                        <i class="bi bi-person"></i>
                        <div>
                            <strong>Name:</strong><br>
                            <?php echo htmlspecialchars(trim(($reservation['customer_first_name'] ?? '') . ' ' . ($reservation['customer_last_name'] ?? ''))); ?>
                        </div>
                    </div>

                    <div class="detail-row">
                        <i class="bi bi-telephone"></i>
                        <div>
                            <strong>Phone:</strong><br>
                            <?php echo htmlspecialchars($reservation['customer_phone'] ?? ''); ?>
                        </div>
                    </div>

                    <div class="detail-row">
                        <i class="bi bi-envelope"></i>
                        <div>
                            <strong>Email:</strong><br>
                            <?php echo !empty($reservation['customer_email']) ? htmlspecialchars($reservation['customer_email']) : '<span class="text-muted">Not provided</span>'; ?>
                        </div>
                    </div>

                    <div class="detail-row">
                        <i class="bi bi-geo-alt"></i>
                        <div>
                            <strong>Address:</strong><br>
                            <?php echo !empty($reservation['customer_address']) ? htmlspecialchars($reservation['customer_address']) : '<span class="text-muted">Not provided</span>'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card details-card">
                <div class="card-header">
                    Staff & Summary
                </div>
                <div class="card-body">
                    <div class="detail-row">
                        <i class="bi bi-person-badge"></i>
                        <div>
                            <strong>Assigned Staff:</strong><br>
                            <?php
                            if (!empty($reservation['employee_first_name'])) {
                                echo htmlspecialchars($reservation['employee_first_name'] . ' ' . $reservation['employee_last_name']);
                                if (!empty($reservation['employee_position'])) {
                                    echo '<br><small class="text-muted">' . htmlspecialchars($reservation['employee_position']) . '</small>';
                                }
                            } else {
                                echo '<span class="text-muted">No preferred staff selected.</span>';
                            }
                            ?>
                        </div>
                    </div>

                    <div class="summary-box mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Services</span>
                            <strong><?php echo count($services); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Duration</span>
                            <strong><?php echo $total_duration; ?> min</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Total Amount</span>
                            <strong>₱<?php echo number_format($total_amount, 2); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cancelAppointment(appointmentId) {
    if (!confirm('Are you sure you want to cancel this reservation?')) {
        return;
    }

    fetch('cancel-appointment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'appointment_id=' + encodeURIComponent(appointmentId)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Reservation cancelled successfully.');
            window.location.href = 'my-reservations.php';
        } else {
            alert(data.message || 'Failed to cancel reservation.');
        }
    })
    .catch(() => {
        alert('An unexpected error occurred.');
    });
}
</script>

</body>
</html>