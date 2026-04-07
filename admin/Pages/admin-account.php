<?php
include_once('../include/template.php');
include_once('../include/connection.php');

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Get all users (both admin and employee)
$sql = "SELECT u.user_id, u.username, u.role, u.status, u.created_at, u.last_login,
               e.employee_id, e.first_name, e.last_name, e.position
        FROM users u
        LEFT JOIN employees e ON u.employee_id = e.employee_id
        ORDER BY 
            CASE u.role
                WHEN 'admin' THEN 1
                WHEN 'employee' THEN 2
                ELSE 3
            END,
            u.created_at DESC";
$result = $con->query($sql);

// Count statistics
$active_count = mysqli_query($con, "SELECT COUNT(*) as total FROM users WHERE status = 'active'");
$active_count = mysqli_fetch_assoc($active_count)['total'];

$pending_count = mysqli_query($con, "SELECT COUNT(*) as total FROM users WHERE status = 'pending'");
$pending_count = mysqli_fetch_assoc($pending_count)['total'];

$inactive_count = mysqli_query($con, "SELECT COUNT(*) as total FROM users WHERE status = 'inactive'");
$inactive_count = mysqli_fetch_assoc($inactive_count)['total'];

$admin_count = mysqli_query($con, "SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
$admin_count = mysqli_fetch_assoc($admin_count)['total'];

$employee_count = mysqli_query($con, "SELECT COUNT(*) as total FROM users WHERE role = 'employee'");
$employee_count = mysqli_fetch_assoc($employee_count)['total'];
?>

<style>
    .status-badge {
        padding: 5px 10px;
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
    
    .filter-btn {
        border-radius: 4px;
        padding: 6px 15px;
        margin-right: 5px;
        border: 1px solid #dee2e6;
        background: white;
        color: #495057;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 13px;
    }
    
    .filter-btn.active {
        background: #191919;
        color: white;
        border-color: #191919;
    }
    
    .filter-btn:hover {
        background: #f8f9fa;
    }
    
    .badge-count {
        background: #6c757d;
        color: white;
        border-radius: 50px;
        padding: 2px 8px;
        font-size: 11px;
        margin-left: 5px;
    }
    
    .filter-btn.active .badge-count {
        background: rgba(255,255,255,0.3);
    }
    
    .summary-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        border-left: 4px solid transparent;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .summary-number {
        font-size: 28px;
        font-weight: 700;
        color: #191919;
        line-height: 1.2;
    }
    
    .summary-label {
        font-size: 14px;
        color: #6c757d;
        margin-top: 5px;
    }
    
    .role-badge {
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .role-admin {
        background: #dc3545;
        color: white;
    }
    
    .role-employee {
        background: #17a2b8;
        color: white;
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header" style="font-weight:600; color:#464660;">
            <i class="fas fa-users"></i> User Accounts Management
        </h1>
    </div>
</div>

<!-- Summary Cards -->
<div class="row" style="margin-bottom: 20px;">
    <div class="col-md-2 col-sm-4">
        <div class="summary-card" style="border-left-color: #28a745;">
            <div class="summary-number"><?= $active_count; ?></div>
            <div class="summary-label">Active Users</div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="summary-card" style="border-left-color: #ffc107;">
            <div class="summary-number"><?= $pending_count; ?></div>
            <div class="summary-label">Pending Approval</div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="summary-card" style="border-left-color: #dc3545;">
            <div class="summary-number"><?= $inactive_count; ?></div>
            <div class="summary-label">Inactive Users</div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="summary-card" style="border-left-color: #464660;">
            <div class="summary-number"><?= $admin_count; ?></div>
            <div class="summary-label">Administrators</div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="summary-card" style="border-left-color: #17a2b8;">
            <div class="summary-number"><?= $employee_count; ?></div>
            <div class="summary-label">Employees</div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="summary-card" style="border-left-color: #6c757d;">
            <div class="summary-number"><?= $active_count + $pending_count + $inactive_count; ?></div>
            <div class="summary-label">Total Users</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <strong><i class="fas fa-list"></i> User Accounts</strong>
                    </div>
                    <div class="col-md-6 text-right">
                        <!-- Filter Buttons -->
                        <div class="btn-group" role="group">
                            <button class="filter-btn active" data-filter="all">
                                All <span class="badge-count"><?= $active_count + $pending_count + $inactive_count; ?></span>
                            </button>
                            <button class="filter-btn" data-filter="active">
                                Active <span class="badge-count"><?= $active_count; ?></span>
                            </button>
                            <button class="filter-btn" data-filter="pending">
                                Pending <span class="badge-count"><?= $pending_count; ?></span>
                            </button>
                            <button class="filter-btn" data-filter="inactive">
                                Inactive <span class="badge-count"><?= $inactive_count; ?></span>
                            </button>
                        </div>
                        <a href="admin-add.php" class="btn btn-success btn-sm" style="margin-left: 10px;">
                            <i class="fas fa-user-plus"></i> Add User
                        </a>
                    </div>
                </div>
            </div>

            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Type</th>
                                <th>Employee Details</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Date Created</th>
                                <th width="180">Actions</th>
                            </thead>
                        <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr data-status="<?= $row['status']; ?>">
                                    <td><?= $row['user_id']; ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['username']); ?></strong>
                                        <?php if ($row['user_id'] == $_SESSION['userid']): ?>
                                            <span class="label label-info" style="margin-left: 5px;">You</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="role-badge role-<?= $row['role']; ?>">
                                            <i class="fas fa-<?= $row['role'] == 'admin' ? 'user-shield' : 'user-tie'; ?>"></i>
                                            <?= ucfirst($row['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['role'] == 'employee' && $row['employee_id']): ?>
                                            <div>
                                                <strong><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong>
                                                <br>
                                                <!-- <small class="text-muted"><?= htmlspecialchars($row['position']); ?></small> -->
                                            </div>
                                        <?php elseif ($row['role'] == 'employee' && !$row['employee_id']): ?>
                                            <span class="text-muted">No employee record linked</span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $row['status']; ?>">
                                            <i class="fas fa-<?= $row['status'] == 'active' ? 'check-circle' : ($row['status'] == 'pending' ? 'clock' : 'ban'); ?>"></i>
                                            <?= ucfirst($row['status']); ?>
                                            <?php if ($row['status'] == 'pending'): ?>
                                                <br><small class="text-muted">Awaiting approval</small>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $row['last_login'] ? date('M d, Y h:i A', strtotime($row['last_login'])) : '<span class="text-muted">Never</span>'; ?>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <!-- Edit/Update Button -->
                                        <button class="btn btn-warning btn-sm editUserBtn"
                                                data-id="<?= $row['user_id']; ?>"
                                                data-username="<?= htmlspecialchars($row['username']); ?>"
                                                data-role="<?= $row['role']; ?>"
                                                data-status="<?= $row['status']; ?>"
                                                data-toggle="tooltip"
                                                title="Update Account">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <!-- Activate/Deactivate based on status -->
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <a href="../Functions/user_activate.php?id=<?= $row['user_id']; ?>"
                                               class="btn btn-success btn-sm"
                                               data-toggle="tooltip"
                                               title="Approve Account"
                                               onclick="return confirm('Approve this account? The user will be able to log in.');">
                                                <i class="fas fa-check-circle"></i> Approve
                                            </a>
                                        <?php elseif ($row['status'] == 'active'): ?>
                                            <?php if ($row['user_id'] != $_SESSION['userid']): ?>
                                                <a href="../Functions/user_deactivate.php?id=<?= $row['user_id']; ?>"
                                                   class="btn btn-danger btn-sm"
                                                   data-toggle="tooltip"
                                                   title="Deactivate Account"
                                                   onclick="return confirm('Deactivate this account? The user will no longer be able to log in.');">
                                                    <i class="fas fa-ban"></i> Deactivate
                                                </a>
                                            <?php endif; ?>
                                        <?php elseif ($row['status'] == 'inactive'): ?>
                                            <a href="../Functions/user_activate.php?id=<?= $row['user_id']; ?>"
                                               class="btn btn-success btn-sm"
                                               data-toggle="tooltip"
                                               title="Activate Account"
                                               onclick="return confirm('Activate this account? The user will be able to log in again.');">
                                                <i class="fas fa-check-circle"></i> Activate
                                            </a>
                                        <?php endif; ?>
                                        
                                        <!-- View Button -->
                                        <a href="admin-view.php?id=<?= $row['user_id']; ?>"
                                           class="btn btn-info btn-sm"
                                           data-toggle="tooltip"
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                     </tr>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    No user accounts found.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: #464660; color: white;">
                <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                <h4 class="modal-title">
                    <i class="fas fa-edit"></i> Update User Account
                </h4>
            </div>
            <form action="../Functions/user_update.php" method="POST" id="editUserForm">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Username</label>
                        <input type="text" name="username" id="edit_username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-user-tag"></i> Role</label>
                        <select name="role" id="edit_role" class="form-control" required>
                            <option value="admin">Administrator</option>
                            <option value="employee">Employee</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-toggle-on"></i> Status</label>
                        <select name="status" id="edit_status" class="form-control" required>
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
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="../js/jquery.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/dataTables/jquery.dataTables.min.js"></script>
<script src="../js/dataTables/dataTables.bootstrap.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#usersTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100],
        order: [[0, 'asc']],
        language: {
            emptyTable: "No user accounts found"
        }
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Filter functionality
    $('.filter-btn').click(function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        
        var filter = $(this).data('filter');
        
        if (filter === 'all') {
            table.column(4).search('').draw();
        } else {
            table.column(4).search(filter, true, false).draw();
        }
    });

    // Edit User - Populate Modal
    $('.editUserBtn').on('click', function() {
        $('#edit_user_id').val($(this).data('id'));
        $('#edit_username').val($(this).data('username'));
        $('#edit_role').val($(this).data('role'));
        $('#edit_status').val($(this).data('status'));
        $('#editUserModal').modal('show');
    });

    // Form validation
    $('#editUserForm').submit(function(e) {
        var password = $('input[name="new_password"]').val();
        var confirm = $('input[name="confirm_password"]').val();
        
        if (password && password !== confirm) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Password Mismatch',
                text: 'New password and confirm password do not match!'
            });
            return false;
        }
        
        if (password && password.length < 6) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Password Too Short',
                text: 'Password must be at least 6 characters long!'
            });
            return false;
        }
    });
});
</script>