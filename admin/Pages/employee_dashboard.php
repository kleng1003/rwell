<?php
include_once('../include/template.php');
include_once('../include/connection.php');
include_once('../include/activity_logger.php');

// Check if user is logged in
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}

// Check if user is employee
if ($_SESSION['role'] !== 'employee') {
    header("Location: index.php");
    exit();
}

// Set default employee values if not set
$employee_name = isset($_SESSION['employee_name']) ? $_SESSION['employee_name'] : $_SESSION['username'];
$employee_id = isset($_SESSION['employee_id']) ? $_SESSION['employee_id'] : null;
$employee_position = isset($_SESSION['employee_position']) ? $_SESSION['employee_position'] : 'Staff';

// Log page access
logActivity("Viewed employee dashboard", "Employee: " . $employee_name);

// Get employee's appointments
$employee_id = $_SESSION['employee_id'];
$today_appointments = mysqli_query($con, "
    SELECT a.*, 
           CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
           c.phone AS customer_phone
    FROM appointments a
    LEFT JOIN customers c ON a.customer_id = c.customer_id
    WHERE a.employee_id = '$employee_id' 
    AND a.appointment_date = CURDATE()
    ORDER BY a.appointment_time ASC
");

$upcoming_appointments = mysqli_query($con, "
    SELECT a.*, 
           CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
           c.phone AS customer_phone
    FROM appointments a
    LEFT JOIN customers c ON a.customer_id = c.customer_id
    WHERE a.employee_id = '$employee_id' 
    AND a.appointment_date > CURDATE()
    AND a.status IN ('pending', 'approved')
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 10
");

$appointment_count = mysqli_query($con, "SELECT COUNT(*) as total FROM appointments WHERE employee_id = '$employee_id'");
$appointment_count = mysqli_fetch_assoc($appointment_count)['total'];

$completed_count = mysqli_query($con, "SELECT COUNT(*) as total FROM appointments WHERE employee_id = '$employee_id' AND status = 'completed'");
$completed_count = mysqli_fetch_assoc($completed_count)['total'];
?>

<style>
    .employee-welcome {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        padding: 30px;
        border-radius: 8px;
        margin-bottom: 30px;
    }
    
    .employee-welcome h1 {
        margin: 0 0 10px 0;
        font-weight: 700;
    }
    
    .employee-welcome p {
        margin: 0;
        opacity: 0.9;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        border-left: 4px solid #28a745;
        text-align: center;
    }
    
    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #28a745;
    }
    
    .stat-label {
        font-size: 14px;
        color: #6c757d;
        margin-top: 5px;
    }
    
    .schedule-item {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        border-left: 3px solid #28a745;
        transition: all 0.3s;
    }
    
    .schedule-item:hover {
        transform: translateX(5px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .schedule-time {
        font-weight: 600;
        color: #28a745;
        margin-bottom: 5px;
        font-size: 14px;
    }
    
    .schedule-customer {
        font-weight: 600;
        font-size: 16px;
        margin-bottom: 5px;
    }
    
    .schedule-phone {
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 5px;
    }
    
    .schedule-purpose {
        font-size: 13px;
        color: #495057;
        margin-bottom: 8px;
    }
    
    .status-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        margin-top: 5px;
    }
    
    .status-pending { background: #fff3cd; color: #856404; }
    .status-approved { background: #cce5ff; color: #004085; }
    .status-completed { background: #d4edda; color: #155724; }
    .status-cancelled { background: #f8d7da; color: #721c24; }
    
    .quick-actions {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .quick-actions .btn {
        flex: 1;
        min-width: 150px;
        padding: 12px;
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <div class="employee-welcome">
            <h1>
                <i class="fas fa-user-tie"></i> Welcome, <?= htmlspecialchars($employee_name); ?>!
            </h1>
            <p>
                <i class="fas fa-briefcase"></i> <?= htmlspecialchars($employee_position); ?> 
                | <i class="fas fa-calendar-alt"></i> <?= date('l, F j, Y'); ?>
                | <i class="fas fa-clock"></i> <?= date('h:i A'); ?>
            </p>
        </div>
    </div>
</div>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?= $appointment_count; ?></div>
        <div class="stat-label">Total Appointments</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $completed_count; ?></div>
        <div class="stat-label">Completed Services</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $today_appointments->num_rows; ?></div>
        <div class="stat-label">Today's Schedule</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $upcoming_appointments->num_rows; ?></div>
        <div class="stat-label">Upcoming</div>
    </div>
</div>

<div class="row">
    <!-- Today's Schedule -->
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong><i class="fas fa-calendar-day"></i> Today's Schedule</strong>
                <span class="pull-right">
                    <span class="label label-success"><?= $today_appointments->num_rows; ?> appointments</span>
                </span>
            </div>
            <div class="panel-body">
                <?php if ($today_appointments && $today_appointments->num_rows > 0): ?>
                    <?php while ($apt = $today_appointments->fetch_assoc()): ?>
                        <div class="schedule-item">
                            <div class="schedule-time">
                                <i class="fas fa-clock"></i> 
                                <?= date('h:i A', strtotime($apt['appointment_time'])); ?>
                            </div>
                            <div class="schedule-customer">
                                <i class="fas fa-user"></i> 
                                <?= htmlspecialchars($apt['customer_name']); ?>
                            </div>
                            <?php if (!empty($apt['customer_phone'])): ?>
                                <div class="schedule-phone">
                                    <i class="fas fa-phone"></i> 
                                    <?= htmlspecialchars($apt['customer_phone']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="schedule-purpose">
                                <i class="fas fa-info-circle"></i> 
                                <?= htmlspecialchars($apt['purpose'] ?: 'No purpose specified'); ?>
                            </div>
                            <span class="status-badge status-<?= $apt['status']; ?>">
                                <i class="fas fa-<?= $apt['status'] == 'pending' ? 'hourglass-half' : ($apt['status'] == 'approved' ? 'check-circle' : ($apt['status'] == 'completed' ? 'check-double' : 'times-circle')); ?>"></i>
                                <?= ucfirst($apt['status']); ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center text-muted" style="padding: 40px;">
                        <i class="fas fa-calendar-check fa-3x mb-3"></i>
                        <p>No appointments scheduled for today.</p>
                        <a href="appointment_add.php" class="btn btn-info btn-sm">
                            <i class="fas fa-plus"></i> Create Appointment
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Upcoming Appointments -->
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong><i class="fas fa-calendar-week"></i> Upcoming Appointments</strong>
                <span class="pull-right">
                    <span class="label label-info"><?= $upcoming_appointments->num_rows; ?> upcoming</span>
                </span>
            </div>
            <div class="panel-body">
                <?php if ($upcoming_appointments && $upcoming_appointments->num_rows > 0): ?>
                    <?php while ($apt = $upcoming_appointments->fetch_assoc()): ?>
                        <div class="schedule-item">
                            <div class="schedule-time">
                                <i class="fas fa-calendar-alt"></i> 
                                <?= date('M d, Y', strtotime($apt['appointment_date'])); ?>
                                <span class="pull-right">
                                    <i class="fas fa-clock"></i> 
                                    <?= date('h:i A', strtotime($apt['appointment_time'])); ?>
                                </span>
                            </div>
                            <div class="schedule-customer">
                                <i class="fas fa-user"></i> 
                                <?= htmlspecialchars($apt['customer_name']); ?>
                            </div>
                            <div class="schedule-purpose">
                                <i class="fas fa-info-circle"></i> 
                                <?= htmlspecialchars($apt['purpose'] ?: 'No purpose specified'); ?>
                            </div>
                            <span class="status-badge status-<?= $apt['status']; ?>">
                                <i class="fas fa-<?= $apt['status'] == 'pending' ? 'hourglass-half' : 'check-circle'; ?>"></i>
                                <?= ucfirst($apt['status']); ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center text-muted" style="padding: 40px;">
                        <i class="fas fa-calendar-alt fa-3x mb-3"></i>
                        <p>No upcoming appointments scheduled.</p>
                        <a href="appointment_add.php" class="btn btn-info btn-sm">
                            <i class="fas fa-plus"></i> Create Appointment
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong><i class="fas fa-bolt"></i> Quick Actions</strong>
            </div>
            <div class="panel-body">
                <div class="quick-actions">
                    <a href="appointments.php?my=true" class="btn btn-info">
                        <i class="fas fa-calendar-alt"></i> My Appointments
                    </a>
                    <a href="customers.php" class="btn btn-success">
                        <i class="fas fa-users"></i> View Customers
                    </a>
                    <a href="appointment_add.php" class="btn btn-warning">
                        <i class="fas fa-plus"></i> New Appointment
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity (Optional) -->
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong><i class="fas fa-history"></i> Recent Activity</strong>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent_logs = mysqli_query($con, "
                                SELECT * FROM activity_logs 
                                WHERE user_id = '{$_SESSION['userid']}' 
                                ORDER BY created_at DESC 
                                LIMIT 5
                            ");
                            
                            if ($recent_logs && mysqli_num_rows($recent_logs) > 0):
                                while ($log = mysqli_fetch_assoc($recent_logs)):
                            ?>
                                <tr>
                                    <td><?= date('M d, Y h:i A', strtotime($log['created_at'])); ?></td>
                                    <td><?= htmlspecialchars($log['action']); ?></td>
                                    <td><?= htmlspecialchars($log['details'] ?: '—'); ?></td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No recent activity found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-refresh today's schedule every 30 seconds (optional)
    // setInterval(function() {
    //     location.reload();
    // }, 30000);
});
</script>