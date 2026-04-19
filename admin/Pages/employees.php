<?php
include_once('../include/template.php');
include_once('../include/connection.php');

// Get only active employees (not archived)
$sql = "SELECT e.*, u.username 
        FROM employees e 
        LEFT JOIN users u ON e.user_id = u.user_id
        WHERE e.status = 'active'
        ORDER BY e.hire_date DESC";
$result = $con->query($sql);

// Count active employees
$active_count_query = mysqli_query($con, "SELECT COUNT(*) as total FROM employees WHERE status = 'active'");
$active_count = mysqli_fetch_assoc($active_count_query)['total'];

// Count archived employees for badge
$archived_count_query = mysqli_query($con, "SELECT COUNT(*) as total FROM employees WHERE status = 'archived'");
$archived_count = mysqli_fetch_assoc($archived_count_query)['total'];
?>

<style>
    .summary-card:hover {
        transform: translateY(-3px);
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header" style="font-weight: 600; color: #464660;">
            <i class="fas fa-users"></i> Employees
        </h1>
    </div>
</div>

<!-- Summary Cards -->
<div class="row" style="margin-bottom: 20px;">
    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fas fa-user-check fa-4x"></i>
                    </div>
                    <div class="col-xs-9 text-right summary-card">
                        <div style="font-size: 28px; font-weight: 700;"><?php echo $active_count; ?></div>
                        <div>Active Employees</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel panel-warning">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fas fa-archive fa-4x"></i>
                    </div>
                    <div class="col-xs-9 text-right summary-card">
                        <div style="font-size: 28px; font-weight: 700;"><?php echo $archived_count; ?></div>
                        <div>Archived Employees</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel panel-success">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fas fa-calendar fa-4x"></i>
                    </div>
                    <div class="col-xs-9 text-right summary-card">
                        <div style="font-size: 28px; font-weight: 700;"><?php echo date('Y'); ?></div>
                        <div>Current Year</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <strong><i class="fas fa-list"></i> Employee Management</strong>
                    </div>
                    <div class="col-md-6 text-right">
                        <a href="employee_add.php" class="btn btn-success btn-sm">
                            <i class="fas fa-user-plus"></i> Add Employee
                        </a>
                        <!-- <a href="../reports/employee-list.php" target="_blank" class="btn btn-primary btn-sm" id="printTable">
                            <i class="fas fa-print"></i> Print Table
                        </a> -->
                        <a href="employee-archive.php" class="btn btn-danger btn-sm">
                            <i class="fas fa-archive"></i> Archive 
                            <?php if ($archived_count > 0): ?>
                                <span class="badge"><?php echo $archived_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>

            <div class="panel-body">
                <div class="table-responsive">
                    <table id="employeesTable" class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Position</th>
                                <th>Hire Date</th>
                                <th>Status</th>
                                <th width="180">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $statusBadge = '<span class="label label-success">Active</span>';
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($row['employee_id']); ?></td>
                                <td><?= htmlspecialchars($row['first_name']); ?></td>
                                <td><?= htmlspecialchars($row['last_name']); ?></td>
                                <td><?= htmlspecialchars($row['phone']); ?></td>
                                <td><?= htmlspecialchars($row['email']); ?></td>
                                <td><?= htmlspecialchars($row['position']); ?></td>
                                <td><?= date('M d, Y', strtotime($row['hire_date'])); ?></td>
                                <td><?= $statusBadge; ?></td>
                                <td>
                                    <a href="../Functions/employee_update.php?id=<?= $row['employee_id']; ?>"
                                       class="btn btn-warning btn-sm"
                                       data-toggle="tooltip"
                                       title="Update">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <a href="../Functions/employee_archive.php?id=<?= $row['employee_id']; ?>"
                                       class="btn btn-danger btn-sm"
                                       data-toggle="tooltip"
                                       title="Archive"
                                       onclick="return confirm('Archive this employee? They will be moved to the archive and won\'t appear in active lists.');">
                                        <i class="fas fa-archive"></i>
                                    </a>

                                    <button class="btn btn-info btn-sm viewBtn"
                                            data-id="<?= $row['employee_id']; ?>"
                                            data-toggle="tooltip"
                                            title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php
                            }
                        } else {
                            echo '<tr><td colspan="9" class="text-center text-muted">
                                <i class="fas fa-info-circle"></i> No active employees found.
                            </td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Employee View Modal -->
<div class="modal fade" id="employeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#464660;color:white;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fas fa-user"></i> Employee Details
                </h4>
            </div>
            <div class="modal-body" id="employeeDetails">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- DataTables & Tooltips -->
<script src="../js/jquery.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/dataTables/jquery.dataTables.min.js"></script>
<script src="../js/dataTables/dataTables.bootstrap.min.js"></script>
<script src="../js/dataTables/dataTables.responsive.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#employeesTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100],
        order: [[0, 'asc']],
        language: {
            emptyTable: "No active employees found"
        }
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // View Employee Modal
    $('.viewBtn').click(function() {
        var id = $(this).data('id');
        $('#employeeModal').modal('show');
        $('#employeeDetails').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');

        $.ajax({
            url: '../Functions/employee_fetch.php',
            type: 'GET',
            data: { id: id },
            success: function(response) {
                $('#employeeDetails').html(response);
            },
            error: function() {
                $('#employeeDetails').html('<p class="text-danger">Failed to load data.</p>');
            }
        });
    });

    // Print functionality
    $('#printTable').click(function(e) {
        e.preventDefault();
        var printWindow = window.open('../reports/employee-list.php', '_blank');
        printWindow.focus();
    });

    // Search functionality
    $('#searchTable').on('keyup', function() {
        table.search(this.value).draw();
    });
});
</script>