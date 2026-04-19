<?php
include_once('../include/template.php');
include_once('../include/connection.php');

// Check if status column exists
$check_column = mysqli_query($con, "SHOW COLUMNS FROM customers LIKE 'status'");
$has_status = mysqli_num_rows($check_column) > 0;

// Fetch only active customers (if status column exists, otherwise fetch all)
if ($has_status) {
    $sql = "SELECT c.*, 
                   GROUP_CONCAT(s.service_name SEPARATOR ', ') as services
            FROM customers c
            LEFT JOIN customer_services cs ON c.customer_id = cs.customer_id
            LEFT JOIN services s ON cs.service_id = s.service_id
            WHERE c.status = 'active' OR c.status IS NULL
            GROUP BY c.customer_id
            ORDER BY c.created_at DESC";
} else {
    $sql = "SELECT c.*, 
                   GROUP_CONCAT(s.service_name SEPARATOR ', ') as services
            FROM customers c
            LEFT JOIN customer_services cs ON c.customer_id = cs.customer_id
            LEFT JOIN services s ON cs.service_id = s.service_id
            GROUP BY c.customer_id
            ORDER BY c.created_at DESC";
}
$result = $con->query($sql);

// Fetch all active services for modals
$servicesResult = $con->query("SELECT * FROM services WHERE status='active' ORDER BY service_name ASC");

// Get statistics
if ($has_status) {
    $total_customers = mysqli_query($con, "SELECT COUNT(*) as total FROM customers WHERE status = 'active' OR status IS NULL");
    $archived_count = mysqli_query($con, "SELECT COUNT(*) as total FROM customers WHERE status = 'archived'");
} else {
    $total_customers = mysqli_query($con, "SELECT COUNT(*) as total FROM customers");
    $archived_count = mysqli_query($con, "SELECT COUNT(*) as total FROM customers WHERE 1=0");
}
$total_customers = mysqli_fetch_assoc($total_customers)['total'];
$archived_count = mysqli_fetch_assoc($archived_count)['total'] ?? 0;
?>

<style>
    .summary-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        border-left: 4px solid transparent;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .summary-card.active-card {
        border-left-color: #28a745;
    }
    
    .summary-card.archived-card {
        border-left-color: #dc3545;
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
    
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    
    .action-btn {
        margin: 0 2px;
    }
    
    .customer-row {
        transition: background 0.3s;
    }
    
    .customer-row:hover {
        background: #f8f9fa;
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header" style="font-weight: 600; color: #464660;">
            <i class="fas fa-users"></i> Customer Management
        </h1>
    </div>
</div>

<!-- Summary Cards -->
<div class="row" style="margin-bottom: 20px;">
    <div class="col-md-6 col-sm-6">
        <div class="summary-card active-card">
            <div class="summary-number"><?= $total_customers; ?></div>
            <div class="summary-label">Active Customers</div>
        </div>
    </div>
    <?php if ($has_status): ?>
    <div class="col-md-6 col-sm-6">
        <div class="summary-card archived-card">
            <div class="summary-number"><?= $archived_count; ?></div>
            <div class="summary-label">Archived Customers</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <strong><i class="fas fa-list"></i> Customer Directory</strong>
                    </div>
                    <div class="col-md-6 text-right">
                        <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#addCustomerModal">
                            <i class="fas fa-user-plus"></i> New Customer
                        </button>
                        <!-- <a href="../reports/customer-list.php" target="_blank" class="btn btn-primary btn-sm">
                            <i class="fas fa-print"></i> Print List
                        </a> -->
                        <?php if ($has_status && $archived_count > 0): ?>
                            <a href="customer-archive.php" class="btn btn-danger btn-sm">
                                <i class="fas fa-archive"></i> Archive 
                                <span class="badge" style="background: white; color: #856404;"><?= $archived_count; ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="panel-body">
                <div class="table-responsive">
                    <table id="customersTable" class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Services</th>
                                <th width="120">Actions</th>
                            </thead>
                        <tbody id="customersTableBody">
                            <?php if ($result->num_rows > 0): ?>
                                <?php $counter = 1; ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): 
                                    $fullName = $row["first_name"] . " " . $row["last_name"];
                                ?>
                                    <tr class="customer-row" data-id="<?= $row['customer_id']; ?>">
                                        <td><?= $counter++; ?>
                                        <td>
                                            <strong><?= htmlspecialchars($fullName); ?></strong>
                                         
                                        <td><?= htmlspecialchars($row["phone"]); ?>
                                        <td><?= htmlspecialchars($row["email"] ?: 'N/A'); ?>
                                        <td><?= htmlspecialchars(substr($row["address"] ?: 'N/A', 0, 30)); ?>
                                        <td><?= htmlspecialchars($row["services"] ?: 'No services'); ?>
                                        <td>
                                            <button class="btn btn-warning btn-sm editCustomerBtn" 
                                                    data-id="<?= $row['customer_id']; ?>" 
                                                    data-toggle="tooltip" 
                                                    title="Edit Customer">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($has_status): ?>
                                                <button class="btn btn-danger btn-sm archiveCustomerBtn" 
                                                        data-id="<?= $row['customer_id']; ?>"
                                                        data-name="<?= htmlspecialchars($fullName); ?>"
                                                        data-toggle="tooltip" 
                                                        title="Archive Customer">
                                                    <i class="fas fa-archive"></i>
                                                </button>
                                            <?php endif; ?>
                                            <a href="customer-view.php?id=<?= $row["customer_id"]; ?>" 
                                               class="btn btn-info btn-sm" 
                                               data-toggle="tooltip" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No customers found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: #464660; color: white;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fas fa-user-plus"></i> Add New Customer
                </h4>
            </div>
            <form id="addCustomerForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-phone"></i> Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> Email Address</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-tags"></i> Services Availed</label>
                        <div class="row">
                            <?php 
                            mysqli_data_seek($servicesResult, 0);
                            while ($service = mysqli_fetch_assoc($servicesResult)): 
                            ?>
                                <div class="col-md-4">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="services[]" value="<?= $service['service_id']; ?>">
                                            <?= htmlspecialchars($service['service_name']); ?> 
                                            (₱<?= number_format($service['price'],2); ?>)
                                        </label>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: #464660; color: white;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fas fa-edit"></i> Edit Customer
                </h4>
            </div>
            <div class="modal-body" id="editModalContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i> Loading...
                </div>
            </div>
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
    var table = $('#customersTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100],
        order: [[1, 'asc']],
        language: {
            emptyTable: "No customers found"
        }
    });

    // Add Customer with AJAX
    $('#addCustomerForm').submit(function(e) {
        e.preventDefault();
        
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
        
        $.ajax({
            url: '../Functions/customer_add_ajax.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#addCustomerModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
                submitBtn.html(originalText).prop('disabled', false);
            },
            error: function(xhr, status, error) {
                Swal.fire('Error', 'Failed to add customer: ' + error, 'error');
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Edit Customer - Load Modal
    $(document).on('click', '.editCustomerBtn', function() {
        var customer_id = $(this).data('id');
        $('#editModalContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i> Loading...</div>');
        $('#editCustomerModal').modal('show');
        
        $.ajax({
            url: 'customer_edit_modal.php',
            type: 'GET',
            data: {id: customer_id},
            success: function(response) {
                $('#editModalContent').html(response);
            },
            error: function() {
                $('#editModalContent').html('<p class="text-danger">Failed to load customer data.</p>');
            }
        });
    });
    
    // Save Edit Customer
    $(document).on('submit', '#editCustomerForm', function(e) {
        e.preventDefault();
        
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
        
        $.ajax({
            url: '../Functions/customer_update_ajax.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#editCustomerModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', res.message, 'error');
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function() {
                Swal.fire('Error', 'Failed to update customer', 'error');
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Archive Customer with confirmation
    $(document).on('click', '.archiveCustomerBtn', function() {
        var customer_id = $(this).data('id');
        var customer_name = $(this).data('name');
        
        Swal.fire({
            title: 'Archive Customer?',
            text: `Are you sure you want to archive ${customer_name}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, archive it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../Functions/customer_archive.php',
                    type: 'GET',
                    data: {id: customer_id},
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Archived!',
                                text: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to archive customer', 'error');
                    }
                });
            }
        });
    });
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>