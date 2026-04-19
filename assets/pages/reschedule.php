<?php
session_start();

if (!isset($_SESSION['client_id'])) {
    header("Location: ../../index.php");
    exit();
}

$root_path = $_SERVER['DOCUMENT_ROOT'] . '/RWELL/';
include_once($root_path . 'admin/include/connection.php');

$client_id = (int)$_SESSION['client_id'];
$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($appointment_id <= 0) {
    die("Invalid appointment ID.");
}

/*
|--------------------------------------------------------------------------
| Get logged-in client's customer_id
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
    die("Customer account is not linked properly.");
}

/*
|--------------------------------------------------------------------------
| Get appointment and make sure it belongs to logged-in client
|--------------------------------------------------------------------------
*/
$query = "
    SELECT 
        a.appointment_id,
        a.customer_id,
        a.employee_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.purpose,
        c.first_name,
        c.last_name,
        c.phone,
        c.email,
        c.address
    FROM appointments a
    LEFT JOIN customers c ON a.customer_id = c.customer_id
    WHERE a.appointment_id = $appointment_id
      AND a.customer_id = $customer_id
    LIMIT 1
";

$result = mysqli_query($con, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Appointment not found or access denied.");
}

$appointment = mysqli_fetch_assoc($result);

/*
|--------------------------------------------------------------------------
| Only allow reschedule for upcoming pending/approved/confirmed
|--------------------------------------------------------------------------
*/
$allowed_statuses = ['pending', 'approved', 'confirmed'];

if (!in_array(strtolower($appointment['status']), $allowed_statuses)) {
    die("Only pending, approved, or confirmed appointments can be rescheduled.");
}

if (strtotime($appointment['appointment_date']) < strtotime(date('Y-m-d'))) {
    die("Past appointments can no longer be rescheduled.");
}

/*
|--------------------------------------------------------------------------
| Load active employees
|--------------------------------------------------------------------------
*/
$employees_query = mysqli_query($con, "
    SELECT employee_id, first_name, last_name, position
    FROM employees
    WHERE status = 'active'
    ORDER BY first_name ASC
");

/*
|--------------------------------------------------------------------------
| Load services attached to this appointment
|--------------------------------------------------------------------------
*/
$services_query = mysqli_query($con, "
    SELECT s.service_name
    FROM customer_services cs
    INNER JOIN services s ON cs.service_id = s.service_id
    WHERE cs.appointment_id = $appointment_id
    ORDER BY s.service_name ASC
");

$service_names = [];
while ($srv = mysqli_fetch_assoc($services_query)) {
    $service_names[] = $srv['service_name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Appointment - RWell Salon & Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
        }
        .wrapper {
            max-width: 900px;
            margin: 40px auto;
        }
        .card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        .card-header {
            background: linear-gradient(135deg, #e91e63, #ff6b6b);
            color: white;
            border-radius: 18px 18px 0 0 !important;
            padding: 20px;
        }
        .time-slots {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            min-height: 80px;
        }
        .time-slot {
            background: #fff;
            border: 2px solid #dee2e6;
            border-radius: 25px;
            padding: 10px 18px;
            cursor: pointer;
            transition: 0.2s ease;
            font-weight: 500;
        }
        .time-slot:hover {
            border-color: #e91e63;
            background: #fff5f8;
        }
        .time-slot.selected {
            background: #e91e63;
            color: #fff;
            border-color: #e91e63;
        }
    </style>
</head>
<body>
<div class="container wrapper">
    <div class="mb-3">
        <a href="../../client/my-reservations.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to My Reservations
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="mb-1"><i class="bi bi-calendar2-week"></i> Reschedule Appointment</h3>
            <small>Choose a new date and time for your reservation.</small>
        </div>
        <div class="card-body p-4">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6>Client</h6>
                    <p class="mb-1"><?php echo htmlspecialchars(trim(($appointment['first_name'] ?? '') . ' ' . ($appointment['last_name'] ?? ''))); ?></p>
                    <p class="mb-1"><?php echo htmlspecialchars($appointment['phone'] ?? ''); ?></p>
                    <p class="mb-0"><?php echo htmlspecialchars($appointment['email'] ?? ''); ?></p>
                </div>
                <div class="col-md-6">
                    <h6>Current Schedule</h6>
                    <p class="mb-1">
                        <strong>Date:</strong> <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?>
                    </p>
                    <p class="mb-1">
                        <strong>Time:</strong> <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?>
                    </p>
                    <p class="mb-0">
                        <strong>Services:</strong> <?php echo htmlspecialchars(implode(', ', $service_names)); ?>
                    </p>
                </div>
            </div>

            <form id="rescheduleForm">
                <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                <input type="hidden" name="appointment_time" id="appointment_time">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Preferred Staff</label>
                        <select class="form-select" name="employee_id" id="employee_id">
                            <option value="">-- No Preference --</option>
                            <?php while ($emp = mysqli_fetch_assoc($employees_query)): ?>
                                <option value="<?php echo $emp['employee_id']; ?>" <?php echo ((int)$appointment['employee_id'] === (int)$emp['employee_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">New Appointment Date</label>
                        <?php
                        $original_date = $appointment['appointment_date'];
                        $min_reschedule_date = date('Y-m-d', strtotime($original_date . ' +1 day'));
                        ?>

                        <input type="date" class="form-control" name="appointment_date" id="appointment_date"
                            min="<?php echo $min_reschedule_date; ?>" required>
                        <small class="text-muted">
                            You can only choose a date after <?php echo date('F j, Y', strtotime($original_date)); ?>.
                        </small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Available Time Slots</label>
                    <div id="timeSlots" class="time-slots">
                        <div class="text-muted">Select a date first to load available slots.</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Reason / Note</label>
                    <textarea class="form-control" name="purpose" rows="3"><?php echo htmlspecialchars($appointment['purpose'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-lg text-white w-100" style="background: linear-gradient(135deg, #e91e63, #ff6b6b); border: none;">
                    <i class="bi bi-check2-circle"></i> Confirm Reschedule
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function loadTimeSlots() {
    const date = document.getElementById('appointment_date').value;
    const employeeId = document.getElementById('employee_id').value;
    const timeSlots = document.getElementById('timeSlots');
    const appointmentTime = document.getElementById('appointment_time');

    appointmentTime.value = '';
    timeSlots.innerHTML = '<div class="text-muted">Loading available slots...</div>';

    if (!date) {
        timeSlots.innerHTML = '<div class="text-muted">Select a date first to load available slots.</div>';
        return;
    }

    const formData = new FormData();
    formData.append('date', date);
    formData.append('employee_id', employeeId);

    fetch('../pages/get_time_slots.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        timeSlots.innerHTML = '';

        if (data.error) {
            timeSlots.innerHTML = `<div class="text-danger">${data.error}</div>`;
            return;
        }

        if (!data.slots || data.slots.length === 0) {
            timeSlots.innerHTML = '<div class="text-muted">No available slots for this date.</div>';
            return;
        }

        data.slots.forEach(slot => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'time-slot';
            btn.textContent = slot;

            btn.addEventListener('click', function() {
                document.querySelectorAll('.time-slot').forEach(el => el.classList.remove('selected'));
                btn.classList.add('selected');
                appointmentTime.value = slot;
            });

            timeSlots.appendChild(btn);
        });
    })
    .catch(() => {
        timeSlots.innerHTML = '<div class="text-danger">Failed to load time slots.</div>';
    });
}

document.getElementById('appointment_date').addEventListener('change', loadTimeSlots);
document.getElementById('employee_id').addEventListener('change', loadTimeSlots);

document.getElementById('rescheduleForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const appointmentTime = document.getElementById('appointment_time').value;
    if (!appointmentTime) {
        alert('Please select a new time slot.');
        return;
    }

    const formData = new FormData(this);

    fetch('process_reschedule.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            window.location.href = '../../client/my-reservations.php';
        } else {
            alert(data.message || 'Failed to reschedule appointment.');
        }
    })
    .catch(() => {
        alert('An unexpected error occurred.');
    });
});
</script>
</body>
</html>