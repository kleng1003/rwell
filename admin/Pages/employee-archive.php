<?php
// ../Pages/employee-archive.php
include_once('../include/template.php');
include_once('../include/connection.php');

// Get only archived employees
$sql = "SELECT e.*, u.username 
        FROM employees e 
        LEFT JOIN users u ON e.user_id = u.user_id
        WHERE e.status = 'archived'
        ORDER BY e.last_name ASC";
$result = $con->query($sql);

// Count archived employees
$count_query = mysqli_query($con, "SELECT COUNT(*) as total FROM employees WHERE status = 'archived'");
$count_data = mysqli_fetch_assoc($count_query);
$archived_count = $count_data['total'];
?>

<style>
    .restore-btn {
        background: #28a745;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        transition: all 0.3s;
    }
    
    .restore-btn:hover {
        background: #218838;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    .delete-permanent-btn {
        background: #dc3545;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        transition: all 0.3s;
    }
    
    .delete-permanent-btn:hover {
        background: #c82333;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    .archive-header {
        background: #6c757d;
        color: white;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .badge-archived {
        background: #6c757d;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
    }
    
    /* Add this to style the archive date column */
    .archive-date {
        font-style: italic;
        color: #6c757d;
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header" style="font-weight: 600; color: #464660;">
            <i class="fas fa-archive"></i> Employee Archive
        </h1>
    </div>
</div>

<div class="archive-header">
    <div class="row">
        <div class="col-md-6">
            <h4 style="margin: 0;"><i class="fas fa-database"></i> Archived Employees: <strong><?php echo $archived_count; ?></strong></h4>
        </div>
        <div class="col-md-6 text-right">
            <a href="employees.php" class="btn btn-light" style="background: white; color: #6c757d;">
                <i class="fas fa-arrow-left"></i> Back to Active Employees
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong><i class="fas fa-list"></i> Archived Employee List</strong>
                <div class="pull-right">
                    <span class="badge-archived">
                        <i class="fas fa-archive"></i> Total: <?php echo $archived_count; ?>
                    </span>
                </div>
            </div>

            <div class="panel-body">
                <?php if ($archived_count > 0): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    These employees are currently archived. You can restore them to make them active again.
                </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table id="archiveTable" class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Position</th>
                                <th>Hire Date</th>
                                <th>Status</th>
                                <th width="200">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($row['employee_id']); ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong>
                                </td>
                                <td><?= htmlspecialchars($row['phone']); ?></td>
                                <td><?= htmlspecialchars($row['email']); ?></td>
                                <td><?= htmlspecialchars($row['position']); ?></td>
                                <td>
                                    <?php 
                                    // Check if hire_date exists and is valid
                                    if (!empty($row['hire_date']) && $row['hire_date'] != '0000-00-00') {
                                        echo date('M d, Y', strtotime($row['hire_date']));
                                    } else {
                                        echo '<span class="text-muted">Not set</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="label label-default">
                                        <i class="fas fa-archive"></i> Archived
                                    </span>
                                </td>
                                <td>
                                    <!-- Restore Button -->
                                    <a href="../Functions/employee_restore.php?id=<?= $row['employee_id']; ?>"
                                       class="btn btn-success btn-sm"
                                       data-toggle="tooltip"
                                       title="Restore Employee"
                                       onclick="return confirm('Restore this employee? They will become active again.');">
                                        <i class="fas fa-undo"></i> Restore
                                    </a>

                                    <!-- View Button -->
                                    <button class="btn btn-warning btn-sm viewBtn"
                                            data-id="<?= $row['employee_id']; ?>"
                                            data-toggle="tooltip"
                                            title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php
                            }
                        } else {
                            echo '<tr><td colspan="8" class="text-center text-muted">
                                <i class="fas fa-archive"></i> No archived employees found.
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

<!-- Employee View Modal (same as in employees.php) -->
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

<script src="../js/jquery.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/dataTables/jquery.dataTables.min.js"></script>
<script src="../js/dataTables/dataTables.bootstrap.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#archiveTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100],
        order: [[1, 'asc']], // Sort by name
        language: {
            emptyTable: "No archived employees found"
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
});
</script>