<?php
include_once('../include/template.php');
include_once('../include/connection.php');

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin-account.php");
    exit();
}

$user_id = intval($_GET['id']);

// Fetch user details
$query = "SELECT u.*, 
          e.first_name, e.last_name, e.position, e.phone as employee_phone, e.email as employee_email,
          e.employee_id
          FROM users u
          LEFT JOIN employees e ON u.employee_id = e.employee_id
          WHERE u.user_id = '$user_id'";
$result = mysqli_query($con, $query);

if (mysqli_num_rows($result) == 0) {
    header("Location: admin-account.php");
    exit();
}

$user = mysqli_fetch_assoc($result);

// Get login history (last 5 logins)
$login_history = mysqli_query($con, "
    SELECT * FROM activity_logs 
    WHERE user_id = '$user_id' AND action LIKE '%login%'
    ORDER BY created_at DESC 
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
        content: '\f007';
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
        border-left: 4px solid #464660;
        text-align: center;
    }
    
    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #464660;
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
    
    /* Activity Log */
    .log-item {
        padding: 12px;
        border-bottom: 1px solid #e9ecef;
        transition: background 0.3s;
    }
    
    .log-item:hover {
        background: #f8f9fa;
    }
    
    .log-date {
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 5px;
    }
    
    .log-date i {
        margin-right: 5px;
    }
    
    .log-action {
        font-weight: 500;
        color: #464660;
    }
    
    .log-details {
        font-size: 13px;
        color: #6c757d;
        margin-top: 5px;
    }
    
    /* Action Buttons */
    .action-buttons {
        margin: 5px 0 30px 0;

        display: flex;
        gap: 15px;
    }
    
    .btn-action {
        padding: 10px 25px;
        border-radius: 6px;
        font-weight: 600;
        transition: all 0.3s;
        border: none;
    }
    
    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    .btn-primary-action {
        background: #464660;
        color: white;
    }
    
    .btn-primary-action:hover {
        background: #5a5a7a;
    }
    
    .btn-secondary-action {
        background: #6c757d;
        color: white;
    }
    
    .btn-secondary-action:hover {
        background: #5a6268;
    }
    
    .btn-danger-action {
        background: #dc3545;
        color: white;
    }
    
    .btn-danger-action:hover {
        background: #c82333;
    }
    
    .btn-warning-action {
        background: #ffc107;
        color: #191919;
    }
    
    .btn-warning-action:hover {
        background: #e0a800;
        color: #191919;
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
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <ol class="breadcrumb" style="background: none; padding: 0 0 15px 0;">
            <li><a href="admin-account.php" style="color: #464660;">Admin Accounts</a></li>
            <li class="active">Admin Details</li>
        </ol>
    </div>
</div>

<!-- Profile Header -->
<div class="profile-header">
    <div class="row">
        <div class="col-md-8">
            <h1 class="profile-name">
                <i class="fas fa-user-shield"></i> <?= htmlspecialchars($user['username']); ?>
            </h1>
            <div>
                <span class="profile-badge">
                    <i class="fas fa-hashtag"></i> ID: #ADMIN-<?= str_pad($user['user_id'], 4, '0', STR_PAD_LEFT); ?>
                </span>
                <span class="profile-badge">
                    <i class="fas fa-calendar-alt"></i> Created: <?= date('M d, Y', strtotime($user['created_at'])); ?>
                </span>
                <span class="profile-badge">
                    <i class="fas fa-clock"></i> Last Login: <?= $user['last_login'] ? date('M d, Y h:i A', strtotime($user['last_login'])) : 'Never'; ?>
                </span>
            </div>
        </div>
        <div class="col-md-4 text-right">
            <span class="status-badge status-<?= $user['status']; ?>">
                <i class="fas fa-<?= $user['status'] == 'active' ? 'check-circle' : ($user['status'] == 'pending' ? 'clock' : 'ban'); ?>"></i>
                <?= ucfirst($user['status']); ?>
            </span>
            <br>
            <span class="profile-badge" style="margin-top: 10px; display: inline-block;">
                <i class="fas fa-<?= $user['role'] == 'admin' ? 'user-shield' : 'user-tie'; ?>"></i> 
                <?= ucfirst($user['role']); ?>
            </span>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?= date('Y', strtotime($user['created_at'])); ?></div>
        <div class="stat-label">Year Joined</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= date('M', strtotime($user['created_at'])); ?></div>
        <div class="stat-label">Join Month</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $login_history->num_rows; ?></div>
        <div class="stat-label">Recent Logins</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= ucfirst($user['role']); ?></div>
        <div class="stat-label">User Role</div>
    </div>
</div>

<div class="row">
    <!-- Account Information -->
    <div class="col-md-6">
        <div class="info-card">
            <h4 class="info-title">
                <i class="fas fa-info-circle"></i> Account Information
            </h4>
            
            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Username</div>
                    <div class="detail-value">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($user['username']); ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Role</div>
                    <div class="detail-value">
                        <i class="fas fa-<?= $user['role'] == 'admin' ? 'user-shield' : 'user-tie'; ?>"></i> 
                        <?= ucfirst($user['role']); ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <span class="status-badge status-<?= $user['status']; ?>">
                            <?= ucfirst($user['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Created Date</div>
                    <div class="detail-value">
                        <i class="fas fa-calendar-alt"></i> 
                        <?= date('F d, Y h:i A', strtotime($user['created_at'])); ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Last Login IP</div>
                    <div class="detail-value">
                        <i class="fas fa-network-wired"></i> 
                        <?= $user['last_ip'] ?: 'Not recorded'; ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Last Login Date</div>
                    <div class="detail-value">
                        <i class="fas fa-clock"></i> 
                        <?= $user['last_login'] ? date('F d, Y h:i A', strtotime($user['last_login'])) : 'Never logged in'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Employee Information (if linked) -->
    <div class="col-md-6">
        <div class="info-card">
            <h4 class="info-title">
                <i class="fas fa-user-tie"></i> Employee Information
            </h4>
            
            <?php if ($user['employee_id']): ?>
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Employee Name</div>
                        <div class="detail-value">
                            <i class="fas fa-user"></i> 
                            <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Position</div>
                        <div class="detail-value">
                            <i class="fas fa-briefcase"></i> 
                            <?= htmlspecialchars($user['position']); ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Phone</div>
                        <div class="detail-value">
                            <i class="fas fa-phone"></i> 
                            <?= htmlspecialchars($user['employee_phone'] ?: 'Not provided'); ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value">
                            <i class="fas fa-envelope"></i> 
                            <?= htmlspecialchars($user['employee_email'] ?: 'Not provided'); ?>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 15px;">
                    <a href="employee-view.php?id=<?= $user['employee_id']; ?>" class="btn btn-sm btn-info">
                        <i class="fas fa-eye"></i> View Employee Details
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    This admin account is not linked to any employee record.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Login History -->
<div class="row">
    <div class="col-lg-12">
        <div class="info-card">
            <h4 class="info-title">
                <i class="fas fa-history"></i> Recent Login History
                <span class="pull-right">
                    <span class="label label-info">Last 5 logins</span>
                </span>
            </h4>
            
            <?php if ($login_history && $login_history->num_rows > 0): ?>
                <?php while ($log = $login_history->fetch_assoc()): ?>
                    <div class="log-item">
                        <div class="log-date">
                            <i class="fas fa-clock"></i> 
                            <?= date('F d, Y h:i A', strtotime($log['created_at'])); ?>
                            <span class="pull-right">
                                <i class="fas fa-network-wired"></i> IP: <?= $log['ip_address']; ?>
                            </span>
                        </div>
                        <div class="log-action">
                            <i class="fas fa-sign-in-alt"></i> <?= htmlspecialchars($log['action']); ?>
                        </div>
                        <?php if (!empty($log['details'])): ?>
                            <div class="log-details">
                                <i class="fas fa-info-circle"></i> <?= htmlspecialchars($log['details']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center text-muted" style="padding: 30px;">
                    <i class="fas fa-history fa-3x mb-3"></i>
                    <p>No login history found for this user.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="admin-account.php" class="btn-action btn-secondary-action">
        <i class="fas fa-arrow-left"></i> Back to List
    </a>
    
    <?php if ($user['user_id'] != $_SESSION['userid']): ?>
        <?php if ($user['status'] == 'active'): ?>
            <a href="../Functions/user_deactivate.php?id=<?= $user['user_id']; ?>" 
               class="btn-action btn-danger-action"
               onclick="return confirm('Deactivate this admin account?')">
                <i class="fas fa-ban"></i> Deactivate Account
            </a>
        <?php elseif ($user['status'] == 'inactive' || $user['status'] == 'pending'): ?>
            <a href="../Functions/user_activate.php?id=<?= $user['user_id']; ?>" 
               class="btn-action btn-primary-action"
               onclick="return confirm('Activate this admin account?')">
                <i class="fas fa-check-circle"></i> Activate Account
            </a>
        <?php endif; ?>
    <?php endif; ?>
    
    <button class="btn-action btn-warning-action editAdminBtn" 
            data-id="<?= $user['user_id']; ?>"
            data-username="<?= htmlspecialchars($user['username']); ?>"
            data-role="<?= $user['role']; ?>"
            data-status="<?= $user['status']; ?>">
        <i class="fas fa-edit"></i> Edit Account
    </button>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: #464660; color: white;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fas fa-edit"></i> Edit Admin Account
                </h4>
            </div>
            <form action="../Functions/user_update.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Username</label>
                        <input type="text" name="username" id="edit_username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-user-tag"></i> Role</label>
                        <select name="role" id="edit_role" class="form-control">
                            <option value="admin">Administrator</option>
                            <option value="employee">Employee</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-toggle-on"></i> Status</label>
                        <select name="status" id="edit_status" class="form-control">
                            <option value="active">Active</option>
                            <option value="pending">Pending Approval</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> New Password <small class="text-muted">(Leave blank to keep current)</small></label>
                        <input type="password" name="new_password" class="form-control" placeholder="Enter new password">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password">
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> Changing status to "Pending" will prevent the user from logging in until approved.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Edit button functionality
    $('.editAdminBtn').click(function() {
        $('#edit_user_id').val($(this).data('id'));
        $('#edit_username').val($(this).data('username'));
        $('#edit_role').val($(this).data('role'));
        $('#edit_status').val($(this).data('status'));
        $('#editAdminModal').modal('show');
    });
    
    // Password confirmation validation
    $('form').submit(function(e) {
        var password = $('input[name="new_password"]').val();
        var confirm = $('input[name="confirm_password"]').val();
        
        if (password && password !== confirm) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }
        
        if (password && password.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long!');
            return false;
        }
    });
});
</script>