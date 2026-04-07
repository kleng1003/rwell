<?php
// ../Pages/appointment_edit_modal.php
include_once('../include/connection.php');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<p class="text-danger">Invalid appointment ID.</p>';
    exit();
}

$appointment_id = mysqli_real_escape_string($con, $_GET['id']);

// Fetch appointment data
$query = mysqli_query($con, "
    SELECT a.*, 
           CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
           c.customer_id,
           CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
           e.employee_id
    FROM appointments a
    LEFT JOIN customers c ON a.customer_id = c.customer_id
    LEFT JOIN employees e ON a.employee_id = e.employee_id
    WHERE a.appointment_id = '$appointment_id'
");
if (mysqli_num_rows($query) == 0) {
    echo '<p class="text-danger">Appointment not found.</p>';
    exit();
}

$appointment = mysqli_fetch_assoc($query);
?>

<input type="hidden" name="appointment_id" value="<?= $appointment['appointment_id']; ?>">

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label><i class="fas fa-user"></i> Customer <span class="text-danger">*</span></label>
            <select name="customer_id" class="form-control" required>
                <option value="<?= $appointment['customer_id']; ?>"><?= htmlspecialchars($appointment['customer_name']); ?></option>
                <?php
                $customers = mysqli_query($con, "SELECT customer_id, CONCAT(first_name, ' ', last_name) AS name FROM customers ORDER BY name");
                while ($c = mysqli_fetch_assoc($customers)) {
                    if ($c['customer_id'] != $appointment['customer_id']) {
                        echo "<option value='{$c['customer_id']}'>{$c['name']}</option>";
                    }
                }
                ?>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label><i class="fas fa-user-tie"></i> Employee</label>
            <select name="employee_id" class="form-control">
                <option value="">-- Select Employee (Optional) --</option>
                <?php
                $employees = mysqli_query($con, "SELECT employee_id, CONCAT(first_name, ' ', last_name) AS name FROM employees WHERE status='active' ORDER BY name");
                while ($e = mysqli_fetch_assoc($employees)) {
                    $selected = ($e['employee_id'] == $appointment['employee_id']) ? 'selected' : '';
                    echo "<option value='{$e['employee_id']}' $selected>{$e['name']}</option>";
                }
                ?>
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label><i class="fas fa-calendar-alt"></i> Date <span class="text-danger">*</span></label>
            <input type="date" name="appointment_date" class="form-control" 
                   value="<?= $appointment['appointment_date']; ?>" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label><i class="fas fa-clock"></i> Time <span class="text-danger">*</span></label>
            <input type="time" name="appointment_time" class="form-control" 
                   value="<?= $appointment['appointment_time']; ?>" required>
        </div>
    </div>
</div>

<div class="form-group">
    <label><i class="fas fa-info-circle"></i> Purpose</label>
    <textarea name="purpose" class="form-control" rows="2"><?= htmlspecialchars($appointment['purpose']); ?></textarea>
</div>

<div class="form-group">
    <label><i class="fas fa-toggle-on"></i> Status</label>
    <select name="status" class="form-control">
        <option value="pending" <?= $appointment['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
        <option value="approved" <?= $appointment['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
        <option value="completed" <?= $appointment['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
        <option value="cancelled" <?= $appointment['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
    </select>
</div>

<script>
// Add employee availability check
$('select[name="employee_id"], input[name="appointment_date"], input[name="appointment_time"]').change(function() {
    var employee_id = $('select[name="employee_id"]').val();
    var date = $('input[name="appointment_date"]').val();
    var time = $('input[name="appointment_time"]').val();
    
    if (employee_id && date && time) {
        $.ajax({
            url: '../Functions/check_employee_availability.php',
            type: 'POST',
            data: {employee_id: employee_id, date: date, time: time},
            dataType: 'json',
            success: function(res) {
                if (!res.available) {
                    alert('Employee is not available at this time!');
                }
            }
        });
    }
});
</script>