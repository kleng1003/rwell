<?php
include_once('../include/template.php');
include_once('../include/connection.php');

// Check if status column exists
$check_column = mysqli_query($con, "SHOW COLUMNS FROM customers LIKE 'status'");
$has_status = mysqli_num_rows($check_column) > 0;

// Check if updated_at column exists
$check_updated = mysqli_query($con, "SHOW COLUMNS FROM customers LIKE 'updated_at'");
$has_updated_at = mysqli_num_rows($check_updated) > 0;

// Handle Restore Customer
if (isset($_GET['restore']) && is_numeric($_GET['restore'])) {
    $customer_id = intval($_GET['restore']);
    $update = mysqli_query($con, "UPDATE customers SET status = 'active' WHERE customer_id = $customer_id");
    
    if ($update) {
        $_SESSION['success'] = "Customer restored successfully!";
    } else {
        $_SESSION['error'] = "Failed to restore customer: " . mysqli_error($con);
    }
    header("Location: customer-archive.php");
    exit();
}

// Handle Permanent Delete
if (isset($_GET['delete_permanent']) && is_numeric($_GET['delete_permanent'])) {
    $customer_id = intval($_GET['delete_permanent']);
    
    // Check if customer has appointments before deleting
    $check_appointments = mysqli_query($con, "SELECT COUNT(*) as count FROM appointments WHERE customer_id = $customer_id");
    $appointment_count = mysqli_fetch_assoc($check_appointments)['count'];
    
    if ($appointment_count > 0) {
        $_SESSION['error'] = "Cannot delete this customer because they have $appointment_count appointment(s). Archive instead or delete appointments first.";
    } else {
        // Also delete customer_services records
        mysqli_query($con, "DELETE FROM customer_services WHERE customer_id = $customer_id");
        $delete = mysqli_query($con, "DELETE FROM customers WHERE customer_id = $customer_id");
        
        if ($delete) {
            $_SESSION['success'] = "Customer permanently deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete customer: " . mysqli_error($con);
        }
    }
    header("Location: customer-archive.php");
    exit();
}

// Handle Bulk Restore
if (isset($_POST['bulk_restore']) && isset($_POST['selected_customers']) && is_array($_POST['selected_customers'])) {
    $selected = array_map('intval', $_POST['selected_customers']);
    $ids = implode(',', $selected);
    $update = mysqli_query($con, "UPDATE customers SET status = 'active' WHERE customer_id IN ($ids)");
    
    if ($update) {
        $count = count($selected);
        $_SESSION['success'] = "$count customer(s) restored successfully!";
    } else {
        $_SESSION['error'] = "Failed to restore customers: " . mysqli_error($con);
    }
    header("Location: customer-archive.php");
    exit();
}

// Handle Bulk Permanent Delete
if (isset($_POST['bulk_delete']) && isset($_POST['selected_customers']) && is_array($_POST['selected_customers'])) {
    $selected = array_map('intval', $_POST['selected_customers']);
    $ids = implode(',', $selected);
    
    // Check for appointments first
    $check = mysqli_query($con, "SELECT COUNT(*) as count FROM appointments WHERE customer_id IN ($ids)");
    $appointment_count = mysqli_fetch_assoc($check)['count'];
    
    if ($appointment_count > 0) {
        $_SESSION['error'] = "Cannot delete customers with appointments. Found $appointment_count appointment(s).";
    } else {
        // Delete from customer_services first
        mysqli_query($con, "DELETE FROM customer_services WHERE customer_id IN ($ids)");
        $delete = mysqli_query($con, "DELETE FROM customers WHERE customer_id IN ($ids)");
        
        if ($delete) {
            $count = count($selected);
            $_SESSION['success'] = "$count customer(s) permanently deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete customers: " . mysqli_error($con);
        }
    }
    header("Location: customer-archive.php");
    exit();
}

// Fetch only archived customers - FIXED ORDER BY clause
if ($has_status) {
    // Use created_at instead of updated_at
    $sql = "SELECT c.*, 
                   GROUP_CONCAT(s.service_name SEPARATOR ', ') as services
            FROM customers c
            LEFT JOIN customer_services cs ON c.customer_id = cs.customer_id
            LEFT JOIN services s ON cs.service_id = s.service_id
            WHERE c.status = 'archived'
            GROUP BY c.customer_id
            ORDER BY c.created_at DESC";
} else {
    // If no status column, show no archived customers
    $sql = "SELECT * FROM customers WHERE 1=0";
}
$result = $con->query($sql);

// Get statistics
$total_customers = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as total FROM customers WHERE status = 'active' OR status IS NULL"))['total'];
$archived_count = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as total FROM customers WHERE status = 'archived'"))['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Archive - RWELL Salon</title>
    <style>
        .summary-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            border-left: 4px solid transparent;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        
        .summary-card:hover {
            transform: translateY(-3px);
        }
        
        .summary-card.active-card {
            border-left-color: #28a745;
        }
        
        .summary-card.archived-card {
            border-left-color: #ffc107;
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
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-archived {
            background: #fff3cd;
            color: #856404;
        }
        
        .action-btn {
            margin: 0 2px;
        }
        
        .customer-row {
            transition: background 0.3s;
        }
        
        .customer-row:hover {
            background: #fff8e1;
        }
        
        .bulk-actions {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            display: none;
        }
        
        .bulk-actions.show {
            display: block;
        }
        
        .select-all-checkbox {
            cursor: pointer;
        }
        
        .archive-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .archive-header h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .archive-header p {
            margin: 5px 0 0;
            opacity: 0.9;
        }
        
        .btn-restore {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
        }
        
        .btn-restore:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        
        .btn-delete-permanent {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }
        
        .btn-delete-permanent:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        
        .empty-archive {
            text-align: center;
            padding: 50px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .empty-archive i {
            font-size: 60px;
            color: #ffc107;
            margin-bottom: 20px;
        }
        
        .empty-archive h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .empty-archive p {
            color: #666;
        }
    </style>
</head>
<body>

      
            <!-- Archive Header -->
            <div class="archive-header">
                <div class="row">
                    <div class="col-md-8">
                        <h1>
                            <i class="fas fa-archive"></i> Customer Archive
                        </h1>
                        <p>View and manage archived customers. Restore or permanently delete records.</p>
                    </div>
                    <div class="col-md-4 text-right">
                        <a href="customers.php" class="btn btn-default" style="background: white; color: #333;">
                            <i class="fas fa-arrow-left"></i> Back to Active Customers
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Alert Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade in">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade in">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Summary Cards -->
            <div class="row" style="margin-bottom: 20px;">
                <div class="col-md-6 col-sm-6">
                    <div class="summary-card active-card">
                        <div class="summary-number"><?= $total_customers; ?></div>
                        <div class="summary-label">Active Customers</div>
                        <!-- <a href="customers.php" class="btn btn-sm btn-success mt-2">View Active</a> -->
                    </div>
                </div>
                <div class="col-md-6 col-sm-6">
                    <div class="summary-card archived-card">
                        <div class="summary-number"><?= $archived_count; ?></div>
                        <div class="summary-label">Archived Customers</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong><i class="fas fa-archive"></i> Archived Customer Records</strong>
                                </div>
                                <div class="col-md-6 text-right">
                                    <button type="button" id="showBulkActions" class="btn btn-info btn-sm" style="display: none;">
                                        <i class="fas fa-check-square"></i> Bulk Actions
                                    </button>
                                    <a href="customers.php" class="btn btn-success btn-sm">
                                        <i class="fas fa-users"></i> Active Customers
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="panel-body">
                            <!-- Bulk Actions Bar -->
                            <div id="bulkActionsBar" class="bulk-actions">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong><i class="fas fa-check-circle"></i> <span id="selectedCount">0</span> customer(s) selected</strong>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <button type="button" id="bulkRestoreBtn" class="btn btn-success btn-sm">
                                            <i class="fas fa-undo"></i> Restore Selected
                                        </button>
                                        <button type="button" id="bulkDeleteBtn" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Permanently Delete
                                        </button>
                                        <button type="button" id="cancelBulkActions" class="btn btn-default btn-sm">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <form id="bulkActionForm" method="POST">
                                <div class="table-responsive">
                                    <table id="archiveTable" class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th width="30">
                                                    <input type="checkbox" id="selectAllCheckbox" class="select-all-checkbox">
                                                </th>
                                                <th width="5%">#</th>
                                                <th width="20%">Customer Name</th>
                                                <th width="15%">Phone</th>
                                                <th width="20%">Email</th>
                                                <th width="20%">Address</th>
                                                <th width="15%">Archived Date</th>
                                                <th width="20%">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($result && $result->num_rows > 0): ?>
                                                <?php $counter = 1; ?>
                                                <?php while ($row = mysqli_fetch_assoc($result)): 
                                                    $fullName = $row["first_name"] . " " . $row["last_name"];
                                                    // Use created_at instead of updated_at
                                                    $archived_date = $row["created_at"] ?? date('Y-m-d H:i:s');
                                                ?>
                                                    <tr class="customer-row" data-id="<?= $row['customer_id']; ?>">
                                                        <td class="text-center">
                                                            <input type="checkbox" name="selected_customers[]" value="<?= $row['customer_id']; ?>" class="customer-checkbox">
                                                        </td>
                                                        <td><?= $counter++; ?></td>
                                                        <td>
                                                            <strong><?= htmlspecialchars($fullName); ?></strong>
                                                            <br>
                                                            <small class="text-muted">ID: #<?= $row['customer_id']; ?></small>
                                                         </td>
                                                        <td><?= htmlspecialchars($row["phone"]); ?></td>
                                                        <td><?= htmlspecialchars($row["email"] ?: 'N/A'); ?></td>
                                                        <td><?= htmlspecialchars(substr($row["address"] ?: 'N/A', 0, 40)); ?></td>
                                                        <td>
                                                            <span class="status-badge status-archived">
                                                                <i class="fas fa-archive"></i> Archived
                                                            </span>
                                                            <br>
                                                            <small><?= date('M d, Y', strtotime($archived_date)); ?></small>
                                                         </td>
                                                        <td class="action-buttons">
                                                            <a href="?restore=<?= $row['customer_id']; ?>" 
                                                               class="btn btn-success btn-sm" 
                                                               onclick="return confirm('Restore this customer?')"
                                                               data-toggle="tooltip" 
                                                               title="Restore Customer">
                                                                <i class="fas fa-undo"></i> Restore
                                                            </a>
                                                            <a href="?delete_permanent=<?= $row['customer_id']; ?>" 
                                                               class="btn btn-danger btn-sm" 
                                                               onclick="return confirm('PERMANENT DELETE: This action cannot be undone! Are you sure?')"
                                                               data-toggle="tooltip" 
                                                               title="Permanently Delete">
                                                                <i class="fas fa-trash-alt"></i> Delete
                                                            </a>
                                                            <a href="customer-view.php?id=<?= $row["customer_id"]; ?>" 
                                                               class="btn btn-info btn-sm" 
                                                               data-toggle="tooltip" 
                                                               title="View Details">
                                                                <i class="fas fa-eye"></i> View
                                                            </a>
                                                         </td>
                                                     </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">
                                                        <div class="empty-archive">
                                                            <i class="fas fa-archive"></i>
                                                            <h3>Archive is Empty</h3>
                                                            <p>No archived customers found. When you archive customers from the main customer list, they will appear here.</p>
                                                            <a href="customers.php" class="btn btn-primary mt-3">
                                                                <i class="fas fa-users"></i> Go to Active Customers
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>


<!-- Scripts -->
<script src="../js/jquery.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/metisMenu.min.js"></script>
<script src="../js/dataTables/jquery.dataTables.min.js"></script>
<script src="../js/dataTables/dataTables.bootstrap.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#archiveTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100],
        order: [[1, 'desc']],
        language: {
            emptyTable: "No archived customers found"
        }
    });
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Select All functionality
    $('#selectAllCheckbox').change(function() {
        var isChecked = $(this).prop('checked');
        $('.customer-checkbox').prop('checked', isChecked);
        updateBulkActionsBar();
    });
    
    // Individual checkbox change
    $(document).on('change', '.customer-checkbox', function() {
        updateBulkActionsBar();
        
        // Update select all checkbox
        var totalCheckboxes = $('.customer-checkbox').length;
        var checkedCheckboxes = $('.customer-checkbox:checked').length;
        $('#selectAllCheckbox').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
    
    // Update bulk actions bar visibility and count
    function updateBulkActionsBar() {
        var checkedCount = $('.customer-checkbox:checked').length;
        $('#selectedCount').text(checkedCount);
        
        if (checkedCount > 0) {
            $('#bulkActionsBar').addClass('show');
        } else {
            $('#bulkActionsBar').removeClass('show');
        }
    }
    
    // Show bulk actions button when checkboxes are present
    if ($('.customer-checkbox').length > 0) {
        $('#showBulkActions').show();
    }
    
    // Bulk Restore
    $('#bulkRestoreBtn').click(function(e) {
        e.preventDefault();
        var checkedCount = $('.customer-checkbox:checked').length;
        
        if (checkedCount === 0) {
            Swal.fire('Warning', 'Please select at least one customer to restore', 'warning');
            return;
        }
        
        Swal.fire({
            title: 'Restore Customers?',
            text: `Are you sure you want to restore ${checkedCount} customer(s)?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, restore them'
        }).then((result) => {
            if (result.isConfirmed) {
                // Create a form to submit
                var form = $('#bulkActionForm');
                form.append('<input type="hidden" name="bulk_restore" value="1">');
                form.submit();
            }
        });
    });
    
    // Bulk Permanent Delete
    $('#bulkDeleteBtn').click(function(e) {
        e.preventDefault();
        var checkedCount = $('.customer-checkbox:checked').length;
        
        if (checkedCount === 0) {
            Swal.fire('Warning', 'Please select at least one customer to delete', 'warning');
            return;
        }
        
        Swal.fire({
            title: 'Permanently Delete?',
            text: `You are about to permanently delete ${checkedCount} customer(s). This action cannot be undone!`,
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete permanently'
        }).then((result) => {
            if (result.isConfirmed) {
                // Create a form to submit
                var form = $('#bulkActionForm');
                form.append('<input type="hidden" name="bulk_delete" value="1">');
                form.submit();
            }
        });
    });
    
    // Cancel bulk actions
    $('#cancelBulkActions').click(function() {
        $('.customer-checkbox').prop('checked', false);
        $('#selectAllCheckbox').prop('checked', false);
        updateBulkActionsBar();
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $(".alert").fadeOut("slow");
    }, 5000);
});
</script>

</body>
</html>