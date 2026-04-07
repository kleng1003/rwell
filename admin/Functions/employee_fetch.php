<?php
require_once('../include/connection.php');

if (!isset($_GET['id'])) {
    exit("Invalid request");
}

$id = $_GET['id'];

$sql = "SELECT e.*, u.username, u.status as user_status, u.last_login, u.created_at as account_created
        FROM employees e 
        LEFT JOIN users u ON e.employee_id = u.employee_id 
        WHERE e.employee_id = ?";
$stmt = mysqli_stmt_init($con);

if (!mysqli_stmt_prepare($stmt, $sql)) {
    exit("Query failed");
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result->num_rows == 0) {
    exit("Employee not found.");
}

$row = mysqli_fetch_assoc($result);
$fullName = $row['first_name'] . ' ' . $row['last_name'];

// Fetch work schedule
$schedule_query = mysqli_query($con, "
    SELECT * FROM employee_work_schedule 
    WHERE employee_id = '$id' 
    ORDER BY day_of_week
");

// Fetch appointment statistics
$total_appointments = mysqli_query($con, "SELECT COUNT(*) as total FROM appointments WHERE employee_id = '$id'");
$total_appointments = mysqli_fetch_assoc($total_appointments)['total'];

$completed_appointments = mysqli_query($con, "SELECT COUNT(*) as total FROM appointments WHERE employee_id = '$id' AND status = 'completed'");
$completed_appointments = mysqli_fetch_assoc($completed_appointments)['total'];

$pending_appointments = mysqli_query($con, "SELECT COUNT(*) as total FROM appointments WHERE employee_id = '$id' AND status = 'pending'");
$pending_appointments = mysqli_fetch_assoc($pending_appointments)['total'];

$today_appointments = mysqli_query($con, "SELECT COUNT(*) as total FROM appointments WHERE employee_id = '$id' AND appointment_date = CURDATE()");
$today_appointments = mysqli_fetch_assoc($today_appointments)['total'];

// Fetch recent appointments
$recent_appointments = mysqli_query($con, "
    SELECT a.*, 
           CONCAT(c.first_name, ' ', c.last_name) AS customer_name
    FROM appointments a
    LEFT JOIN customers c ON a.customer_id = c.customer_id
    WHERE a.employee_id = '$id'
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 5
");
?>

<style>
    .employee-modal-container {
        padding: 10px;
    }
    
    .detail-section {
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .detail-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .section-title {
        font-size: 16px;
        font-weight: 700;
        color: #464660;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 2px solid #464660;
    }
    
    .section-title i {
        margin-right: 8px;
        color: #64648c;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .info-item {
        background: #f8f9fa;
        padding: 12px 15px;
        border-radius: 8px;
        transition: all 0.3s;
    }
    
    .info-item:hover {
        background: #e9ecef;
        transform: translateX(5px);
    }
    
    .info-label {
        font-size: 11px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }
    
    .info-value {
        font-size: 15px;
        font-weight: 600;
        color: #464660;
        word-break: break-word;
    }
    
    .info-value i {
        margin-right: 8px;
        color: #64648c;
        width: 20px;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-inactive {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-archived {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .stats-mini {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 10px;
    }
    
    .stat-card {
        text-align: center;
        padding: 12px;
        background: #f8f9fa;
        border-radius: 8px;
        transition: all 0.3s;
    }
    
    .stat-card:hover {
        background: #e9ecef;
        transform: translateY(-3px);
    }
    
    .stat-number {
        font-size: 24px;
        font-weight: 800;
        color: #464660;
        line-height: 1.2;
    }
    
    .stat-label {
        font-size: 11px;
        color: #6c757d;
        text-transform: uppercase;
        margin-top: 5px;
    }
    
    .schedule-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    
    .schedule-table th {
        background: #f8f9fa;
        padding: 10px;
        text-align: left;
        font-weight: 600;
        color: #464660;
        border-bottom: 2px solid #e9ecef;
    }
    
    .schedule-table td {
        padding: 10px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .schedule-table tr:hover td {
        background: #f8f9fa;
    }
    
    .day-off {
        color: #dc3545;
        font-weight: 500;
    }
    
    .working-hours {
        color: #28a745;
        font-weight: 500;
    }
    
    .appointment-list {
        max-height: 250px;
        overflow-y: auto;
    }
    
    .appointment-item {
        background: #f8f9fa;
        padding: 12px;
        margin-bottom: 10px;
        border-radius: 8px;
        border-left: 3px solid #464660;
        transition: all 0.3s;
    }
    
    .appointment-item:hover {
        background: #e9ecef;
        transform: translateX(5px);
    }
    
    .appointment-date {
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 5px;
    }
    
    .appointment-customer {
        font-weight: 600;
        color: #464660;
        margin-bottom: 5px;
    }
    
    .appointment-customer i {
        margin-right: 5px;
    }
    
    .user-info-box {
        background: #e7f3ff;
        border-radius: 8px;
        padding: 12px;
        margin-top: 10px;
    }
    
    .user-info-box i {
        color: #17a2b8;
        margin-right: 8px;
    }
    
    .empty-message {
        text-align: center;
        padding: 30px;
        color: #6c757d;
        font-style: italic;
    }
    
    .empty-message i {
        font-size: 40px;
        margin-bottom: 10px;
        color: #adb5bd;
    }
    
    hr {
        margin: 15px 0;
        border-top: 1px solid #e9ecef;
    }
</style>

<div class="employee-modal-container">
    <!-- Personal Information Section -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-user-circle"></i> Personal Information
        </div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Full Name</div>
                <div class="info-value">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($fullName); ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Employee ID</div>
                <div class="info-value">
                    <i class="fas fa-id-card"></i> EMP-<?= str_pad($row['employee_id'], 4, '0', STR_PAD_LEFT); ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Position</div>
                <div class="info-value">
                    <i class="fas fa-briefcase"></i> <?= htmlspecialchars($row['position']); ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Hire Date</div>
                <div class="info-value">
                    <i class="fas fa-calendar-alt"></i> <?= date('F d, Y', strtotime($row['hire_date'])); ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Phone Number</div>
                <div class="info-value">
                    <i class="fas fa-phone"></i> <?= htmlspecialchars($row['phone']); ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Email Address</div>
                <div class="info-value">
                    <i class="fas fa-envelope"></i> <?= htmlspecialchars($row['email'] ?: 'Not provided'); ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Employment Status</div>
                <div class="info-value">
                    <span class="status-badge status-<?= $row['status']; ?>">
                        <i class="fas fa-<?= $row['status'] == 'active' ? 'check-circle' : 'ban'; ?>"></i>
                        <?= ucfirst($row['status']); ?>
                    </span>
                </div>
            </div>
            <?php if (!empty($row['address'])): ?>
            <div class="info-item">
                <div class="info-label">Address</div>
                <div class="info-value">
                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($row['address']); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- System Access Section -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-laptop"></i> System Access
        </div>
        <?php if ($row['username']): ?>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Username</div>
                    <div class="info-value">
                        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($row['username']); ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Account Status</div>
                    <div class="info-value">
                        <span class="status-badge status-<?= $row['user_status']; ?>">
                            <i class="fas fa-<?= $row['user_status'] == 'active' ? 'check-circle' : ($row['user_status'] == 'pending' ? 'clock' : 'ban'); ?>"></i>
                            <?= ucfirst($row['user_status']); ?>
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Last Login</div>
                    <div class="info-value">
                        <i class="fas fa-clock"></i> 
                        <?= $row['last_login'] ? date('F d, Y h:i A', strtotime($row['last_login'])) : 'Never logged in'; ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Account Created</div>
                    <div class="info-value">
                        <i class="fas fa-calendar-plus"></i> 
                        <?= date('F d, Y', strtotime($row['account_created'])); ?>
                    </div>
                </div>
            </div>
            <?php if ($row['user_status'] == 'pending'): ?>
                <div class="user-info-box">
                    <i class="fas fa-clock"></i>
                    <strong>Pending Approval:</strong> This employee cannot log in until the account is activated by an administrator.
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="user-info-box">
                <i class="fas fa-info-circle"></i>
                No system access account created for this employee.
            </div>
        <?php endif; ?>
    </div>

    <!-- Work Schedule Section -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-calendar-week"></i> Work Schedule
        </div>
        <?php if ($schedule_query && mysqli_num_rows($schedule_query) > 0): ?>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Status</th>
                        <th>Working Hours</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $days_map = [
                        0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
                        4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'
                    ];
                    while ($schedule = mysqli_fetch_assoc($schedule_query)): 
                    ?>
                    <tr>
                        <td><strong><?= $days_map[$schedule['day_of_week']]; ?></strong></td>
                        <td>
                            <?php if ($schedule['is_day_off'] == 1): ?>
                                <span class="day-off"><i class="fas fa-ban"></i> Day Off</span>
                            <?php else: ?>
                                <span class="working-hours"><i class="fas fa-check-circle"></i> Working</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($schedule['is_day_off'] == 0): ?>
                                <i class="fas fa-clock"></i> 
                                <?= date('h:i A', strtotime($schedule['start_time'])); ?> - 
                                <?= date('h:i A', strtotime($schedule['end_time'])); ?>
                            <?php else: ?>
                                <span class="text-muted">Not working</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-message">
                <i class="fas fa-calendar-times"></i>
                <p>No work schedule set for this employee.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Appointment Statistics Section -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-chart-line"></i> Appointment Statistics
        </div>
        <div class="stats-mini">
            <div class="stat-card">
                <div class="stat-number"><?= $total_appointments; ?></div>
                <div class="stat-label">Total Appointments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $completed_appointments; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $pending_appointments; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $today_appointments; ?></div>
                <div class="stat-label">Today's Schedule</div>
            </div>
        </div>
    </div>

    <!-- Recent Appointments Section -->
    <div class="detail-section">
        <div class="section-title">
            <i class="fas fa-history"></i> Recent Appointments
            <span class="pull-right">
                <a href="appointments.php?employee=<?= $id; ?>" class="btn btn-xs btn-info" target="_blank">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </span>
        </div>
        <div class="appointment-list">
            <?php if ($recent_appointments && mysqli_num_rows($recent_appointments) > 0): ?>
                <?php while ($apt = mysqli_fetch_assoc($recent_appointments)): ?>
                    <div class="appointment-item">
                        <div class="appointment-date">
                            <i class="fas fa-calendar-alt"></i> <?= date('M d, Y', strtotime($apt['appointment_date'])); ?>
                            <span class="pull-right">
                                <i class="fas fa-clock"></i> <?= date('h:i A', strtotime($apt['appointment_time'])); ?>
                            </span>
                        </div>
                        <div class="appointment-customer">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($apt['customer_name']); ?>
                        </div>
                        <?php if (!empty($apt['purpose'])): ?>
                            <div class="appointment-purpose">
                                <i class="fas fa-info-circle"></i> <?= htmlspecialchars($apt['purpose']); ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <span class="status-badge status-<?= $apt['status']; ?>">
                                <i class="fas fa-<?= $apt['status'] == 'completed' ? 'check-circle' : ($apt['status'] == 'pending' ? 'clock' : 'times-circle'); ?>"></i>
                                <?= ucfirst($apt['status']); ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-message">
                    <i class="fas fa-calendar-times"></i>
                    <p>No appointments found for this employee.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>