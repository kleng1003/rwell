<?php
include_once('../include/template.php');
include_once('../include/connection.php');

if (!isset($_GET['id'])) {
    header("Location: employees.php");
    exit();
}

$employee_id = intval($_GET['id']);

// Fetch employee details with user account info
$sql = "SELECT e.*, u.username, u.status as user_status, u.last_login, u.user_id
        FROM employees e
        LEFT JOIN users u ON e.employee_id = u.employee_id
        WHERE e.employee_id = ?";
$stmt = mysqli_stmt_init($con);

mysqli_stmt_prepare($stmt, $sql);
mysqli_stmt_bind_param($stmt, "i", $employee_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result->num_rows == 0) {
    header("Location: employees.php");
    exit();
}

$employee = mysqli_fetch_assoc($result);
$fullName = $employee['first_name'] . " " . $employee['last_name'];

// Fetch employee's appointments count
$appointments_count = mysqli_query($con, "SELECT COUNT(*) as total FROM appointments WHERE employee_id = '$employee_id'");
$appointments_count = mysqli_fetch_assoc($appointments_count)['total'];

// Fetch employee's work schedule
$work_schedule = mysqli_query($con, "
    SELECT * FROM employee_work_schedule 
    WHERE employee_id = '$employee_id' 
    ORDER BY day_of_week
");

// Fetch recent appointments
$recent_appointments = mysqli_query($con, "
    SELECT a.*, 
           CONCAT(c.first_name, ' ', c.last_name) AS customer_name
    FROM appointments a
    LEFT JOIN customers c ON a.customer_id = c.customer_id
    WHERE a.employee_id = '$employee_id'
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 5
");
?>

<style>
    /* Profile Header */
    .profile-header {
        background: linear-gradient(135deg, #464660 0%, #64648c 100%);
        color: white;
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }
    
    .profile-header::before {
        content: '\f0d1';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        position: absolute;
        right: 20px;
        bottom: 20px;
        font-size: 100px;
        opacity: 0.1;
        color: white;
    }
    
    .profile-name {
        font-size: 32px;
        font-weight: 700;
        margin: 0 0 10px 0;
    }
    
    .profile-badge {
        display: inline-block;
        background: rgba(255,255,255,0.2);
        padding: 5px 15px;
        border-radius: 4px;
        font-size: 13px;
        margin-right: 10px;
    }
    
    .profile-badge i {
        margin-right: 5px;
    }
    
    /* Stats Cards */
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
        font-size: 28px;
        font-weight: 700;
        color: #28a745;
    }
    
    .stat-label {
        font-size: 13px;
        color: #6c757d;
        margin-top: 5px;
    }
    
    /* Info Cards */
    .info-card {
        background: white;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .info-title {
        font-size: 18px;
        font-weight: 600;
        color: #464660;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .info-title i {
        margin-right: 8px;
    }
    
    /* Details Grid */
    .details-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .detail-item {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 6px;
    }
    
    .detail-label {
        font-size: 12px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }
    
    .detail-value {
        font-size: 16px;
        font-weight: 600;
        color: #464660;
    }
    
    .detail-value i {
        margin-right: 8px;
        color: #64648c;
        width: 20px;
    }
    
    /* Status Badges */
    .status-badge {
        padding: 5px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
    
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-inactive {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-pending {
        background: #fff3cd;
        color: #856404;
    }
    
    /* Schedule Table */
    .schedule-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .schedule-table th {
        background: #f8f9fa;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #464660;
    }
    
    .schedule-table td {
        padding: 10px 12px;
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
    
    /* Appointments Table */
    .appointments-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .appointments-table th {
        background: #f8f9fa;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #464660;
    }
    
    .appointments-table td {
        padding: 12px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .appointments-table tr:hover td {
        background: #f8f9fa;
    }
    
    /* Action Buttons */
    .action-buttons {
        margin-top: 30px;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .btn-action {
        padding: 10px 25px;
        border-radius: 6px;
        font-weight: 600;
        transition: all 0.3s;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        text-decoration: none;
    }
    
    .btn-primary-action {
        background: #464660;
        color: white;
    }
    
    .btn-primary-action:hover {
        background: #5a5a7a;
        color: white;
    }
    
    .btn-secondary-action {
        background: #6c757d;
        color: white;
    }
    
    .btn-secondary-action:hover {
        background: #5a6268;
        color: white;
    }
    
    .btn-success-action {
        background: #28a745;
        color: white;
    }
    
    .btn-success-action:hover {
        background: #218838;
        color: white;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 40px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .empty-state i {
        font-size: 48px;
        color: #adb5bd;
        margin-bottom: 15px;
    }
    
    .empty-state p {
        color: #6c757d;
        margin-bottom: 0;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .details-grid {
            grid-template-columns: 1fr;
        }
        
        .profile-name {
            font-size: 24px;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .btn-action {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <ol class="breadcrumb" style="background: none; padding: 0 0 15px 0;">
            <li><a href="employees.php" style="color: #464660;">Employees</a></li>
            <li class="active">Employee Details</li>
        </ol>
    </div>
</div>

<!-- Profile Header -->
<div class="profile-header">
    <div class="row">
        <div class="col-md-8">
            <h1 class="profile-name">
                <i class="fas fa-user-tie"></i> <?= htmlspecialchars($fullName); ?>
            </h1>
            <div>
                <span class="profile-badge">
                    <i class="fas fa-hashtag"></i> ID: #EMP-<?= str_pad($employee['employee_id'], 4, '0', STR_PAD_LEFT); ?>
                </span>
                <span class="profile-badge">
                    <i class="fas fa-calendar-alt"></i> Hired: <?= date('M d, Y', strtotime($employee['hire_date'])); ?>
                </span>
                <?php if ($employee['user_id']): ?>
                    <span class="profile-badge">
                        <i class="fas fa-laptop"></i> Has System Access
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-4 text-right">
            <span class="status-badge status-<?= $employee['status']; ?>">
                <i class="fas fa-<?= $employee['status'] == 'active' ? 'check-circle' : 'ban'; ?>"></i>
                <?= ucfirst($employee['status']); ?>
            </span>
            <?php if ($employee['user_id']): ?>
                <br>
                <span class="status-badge status-<?= $employee['user_status']; ?>" style="margin-top: 10px; display: inline-block;">
                    <i class="fas fa-<?= $employee['user_status'] == 'active' ? 'check-circle' : ($employee['user_status'] == 'pending' ? 'clock' : 'ban'); ?>"></i>
                    Account: <?= ucfirst($employee['user_status']); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?= $appointments_count; ?></div>
        <div class="stat-label">Total Appointments</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $recent_appointments->num_rows; ?></div>
        <div class="stat-label">Recent Appointments</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= date('Y', strtotime($employee['hire_date'])); ?></div>
        <div class="stat-label">Year Hired</div>
    </div>
    <?php if ($employee['user_id']): ?>
        <div class="stat-card">
            <div class="stat-value"><?= $employee['last_login'] ? date('M d', strtotime($employee['last_login'])) : 'Never'; ?></div>
            <div class="stat-label">Last Login</div>
        </div>
    <?php endif; ?>
</div>

<div class="row">
    <!-- Personal Information -->
    <div class="col-md-6">
        <div class="info-card">
            <h4 class="info-title">
                <i class="fas fa-user-circle"></i> Personal Information
            </h4>
            
            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Full Name</div>
                    <div class="detail-value">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($fullName); ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Position</div>
                    <div class="detail-value">
                        <i class="fas fa-briefcase"></i> <?= htmlspecialchars($employee['position']); ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Phone Number</div>
                    <div class="detail-value">
                        <i class="fas fa-phone"></i> <?= htmlspecialchars($employee['phone']); ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Email Address</div>
                    <div class="detail-value">
                        <i class="fas fa-envelope"></i> <?= htmlspecialchars($employee['email'] ?: 'Not provided'); ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Hire Date</div>
                    <div class="detail-value">
                        <i class="fas fa-calendar-alt"></i> <?= date('F d, Y', strtotime($employee['hire_date'])); ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Employment Status</div>
                    <div class="detail-value">
                        <span class="status-badge status-<?= $employee['status']; ?>">
                            <?= ucfirst($employee['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- System Access Information -->
    <div class="col-md-6">
        <div class="info-card">
            <h4 class="info-title">
                <i class="fas fa-laptop"></i> System Access
            </h4>
            
            <?php if ($employee['user_id']): ?>
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Username</div>
                        <div class="detail-value">
                            <i class="fas fa-user-circle"></i> <?= htmlspecialchars($employee['username']); ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Account Status</div>
                        <div class="detail-value">
                            <span class="status-badge status-<?= $employee['user_status']; ?>">
                                <?= ucfirst($employee['user_status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Last Login</div>
                        <div class="detail-value">
                            <i class="fas fa-clock"></i> 
                            <?= $employee['last_login'] ? date('F d, Y h:i A', strtotime($employee['last_login'])) : 'Never logged in'; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($employee['user_status'] == 'pending'): ?>
                    <div class="alert alert-warning" style="margin-top: 15px;">
                        <i class="fas fa-clock"></i>
                        <strong>Account Pending Approval:</strong> This employee cannot log in until the account is activated.
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-key"></i>
                    <p>No system access account created for this employee.</p>
                    <button class="btn btn-success btn-sm createAccountBtn" 
                            data-id="<?= $employee['employee_id']; ?>"
                            data-name="<?= htmlspecialchars($fullName); ?>">
                        <i class="fas fa-plus"></i> Create Account
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Work Schedule -->
<div class="row">
    <div class="col-lg-12">
        <div class="info-card">
            <h4 class="info-title">
                <i class="fas fa-calendar-week"></i> Work Schedule
                <span class="pull-right">
                    <a href="employee_schedule.php?id=<?= $employee['employee_id']; ?>" class="btn btn-sm btn-info">
                        <i class="fas fa-edit"></i> Edit Schedule
                    </a>
                </span>
            </h4>
            
            <?php if ($work_schedule && mysqli_num_rows($work_schedule) > 0): ?>
                <div class="table-responsive">
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Status</th>
                                <th>Working Hours</th>
                            </thead>
                            <tbody>
                                <?php 
                                $days_map = [
                                    0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
                                    4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'
                                ];
                                while ($schedule = mysqli_fetch_assoc($work_schedule)): 
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= $days_map[$schedule['day_of_week']]; ?></strong>
                                    </td>
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
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>No work schedule set for this employee.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Appointments -->
<div class="row">
    <div class="col-lg-12">
        <div class="info-card">
            <h4 class="info-title">
                <i class="fas fa-calendar-check"></i> Recent Appointments
                <span class="pull-right">
                    <a href="appointments.php?employee=<?= $employee['employee_id']; ?>" class="btn btn-sm btn-info">
                        <i class="fas fa-eye"></i> View All
                    </a>
                </span>
            </h4>
            
            <?php if ($recent_appointments && mysqli_num_rows($recent_appointments) > 0): ?>
                <div class="table-responsive">
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Customer</th>
                                <th>Purpose</th>
                                <th>Status</th>
                            </thead>
                            <tbody>
                                <?php while ($apt = mysqli_fetch_assoc($recent_appointments)): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($apt['appointment_date'])); ?></td>
                                    <td><?= date('h:i A', strtotime($apt['appointment_time'])); ?></td>
                                    <td><?= htmlspecialchars($apt['customer_name']); ?></td>
                                    <td><?= htmlspecialchars($apt['purpose'] ?: '—'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $apt['status']; ?>">
                                            <?= ucfirst($apt['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>No appointments found for this employee.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="employees.php" class="btn-action btn-secondary-action">
        <i class="fas fa-arrow-left"></i> Back to List
    </a>
    
    <a href="../Functions/employee_update.php?id=<?= $employee['employee_id']; ?>" class="btn-action btn-primary-action">
        <i class="fas fa-edit"></i> Edit Employee
    </a>
    
    <?php if (!$employee['user_id']): ?>
        <button class="btn-action btn-success-action createAccountBtn" 
                data-id="<?= $employee['employee_id']; ?>"
                data-name="<?= htmlspecialchars($fullName); ?>">
            <i class="fas fa-plus"></i> Create System Account
        </button>
    <?php endif; ?>
    
    <?php if ($employee['status'] == 'active'): ?>
        <a href="../Functions/employee_archive.php?id=<?= $employee['employee_id']; ?>" 
           class="btn-action btn-danger-action"
           onclick="return confirm('Archive this employee?')">
            <i class="fas fa-archive"></i> Archive Employee
        </a>
    <?php endif; ?>
</div>

<!-- Create Account Modal -->
<div class="modal fade" id="createAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: #464660; color: white;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fas fa-user-plus"></i> Create System Account
                </h4>
            </div>
            <form action="../Functions/create_employee_account.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="employee_id" id="account_employee_id">
                    
                    <p>Create login account for: <strong id="account_employee_name"></strong></p>
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        The account will be created with <strong>PENDING</strong> status and will require admin approval.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Create account button
    $('.createAccountBtn').click(function() {
        var employeeId = $(this).data('id');
        var employeeName = $(this).data('name');
        
        $('#account_employee_id').val(employeeId);
        $('#account_employee_name').text(employeeName);
        $('#createAccountModal').modal('show');
    });
    
    // Password confirmation validation
    $('form').submit(function(e) {
        var password = $('input[name="password"]').val();
        var confirm = $('input[name="confirm_password"]').val();
        
        if (password !== confirm) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }
        
        if (password.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long!');
            return false;
        }
    });
});
</script>