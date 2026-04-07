<?php
include_once('../include/template.php');
include_once('../include/connection.php');

// Fetch all suppliers
$sql = "SELECT * FROM suppliers WHERE status != 'archived' ORDER BY 
            CASE status
                WHEN 'active' THEN 1
                WHEN 'inactive' THEN 2
                ELSE 3
            END,
            company_name ASC";
$result = $con->query($sql);

// Get statistics
$active_count = mysqli_query($con, "SELECT COUNT(*) as total FROM suppliers WHERE status = 'active'");
$active_count = mysqli_fetch_assoc($active_count)['total'];

$archived_count = mysqli_query($con, "SELECT COUNT(*) as total FROM suppliers WHERE status = 'archived'");
$archived_count = mysqli_fetch_assoc($archived_count)['total'];

$inactive_count = mysqli_query($con, "SELECT COUNT(*) as total FROM suppliers WHERE status = 'inactive'");
$inactive_count = mysqli_fetch_assoc($inactive_count)['total'];

// Get total products from suppliers
$products_query = mysqli_query($con, "
    SELECT supplier_id, COUNT(*) as product_count 
    FROM products 
    GROUP BY supplier_id
");
$product_counts = [];
while ($row = mysqli_fetch_assoc($products_query)) {
    $product_counts[$row['supplier_id']] = $row['product_count'];
}
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
    
    .summary-card.total-card { border-left-color: #464660; }
    .summary-card.active-card { border-left-color: #28a745; }
    .summary-card.inactive-card { border-left-color: #ffc107; }
    .summary-card.archived-card { border-left-color: #dc3545; }
    
    .summary-number {
        font-size: 28px;
        font-weight: 700;
        color: #191919;
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
    
    .status-inactive {
        background: #fff3cd;
        color: #856404;
    }
    
    .product-badge {
        background: #e3f2fd;
        color: #0d47a1;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
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
    
    .action-btn {
        margin: 0 2px;
    }
    
    .supplier-row {
        transition: background 0.3s;
    }
    
    .supplier-row:hover {
        background: #f8f9fa;
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header" style="font-weight:600; color:#464660;">
            <i class="fas fa-truck"></i> Suppliers
        </h1>
    </div>
</div>

<!-- Summary Cards -->
<div class="row" style="margin-bottom: 20px;">
    <div class="col-md-3 col-sm-6">
        <div class="summary-card total-card">
            <div class="summary-number"><?= $active_count + $inactive_count; ?></div>
            <div class="summary-label">Total Suppliers</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="summary-card active-card">
            <div class="summary-number"><?= $active_count; ?></div>
            <div class="summary-label">Active Suppliers</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="summary-card inactive-card">
            <div class="summary-number"><?= $inactive_count; ?></div>
            <div class="summary-label">Inactive Suppliers</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="summary-card archived-card">
            <div class="summary-number"><?= $archived_count; ?></div>
            <div class="summary-label">Archived</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <strong><i class="fas fa-list"></i> Supplier Management</strong>
                    </div>
                    <div class="col-md-6 text-right">
                        <div class="btn-group" role="group" style="margin-right: 10px;">
                            <button class="filter-btn active" data-filter="all">
                                All <span class="badge-count"><?= $active_count + $inactive_count; ?></span>
                            </button>
                            <button class="filter-btn" data-filter="active">
                                Active <span class="badge-count"><?= $active_count; ?></span>
                            </button>
                            <button class="filter-btn" data-filter="inactive">
                                Inactive <span class="badge-count"><?= $inactive_count; ?></span>
                            </button>
                        </div>
                        
                        <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addSupplierModal">
                            <i class="fas fa-plus"></i> Add Supplier
                        </button>
                        <!-- <a href="../reports/supplier-list.php" target="_blank" class="btn btn-primary btn-sm">
                            <i class="fas fa-print"></i> Print
                        </a> -->
                        <a href="supplier-archive.php" class="btn btn-warning btn-sm">
                            <i class="fas fa-archive"></i> Archive 
                            <?php if ($archived_count > 0): ?>
                                <span class="badge" style="background: white; color: #856404;"><?= $archived_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>

            <div class="panel-body">
                <div class="table-responsive">
                    <table id="suppliersTable" class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th>Contact Person</th>
                                <th>Contact Info</th>
                                <th>Address</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th width="180">Actions</th>
                            </thead>
                        <tbody id="suppliersTableBody">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): 
                                $product_count = $product_counts[$row['supplier_id']] ?? 0;
                            ?>
                                <tr class="supplier-row" data-status="<?= $row['status']; ?>" data-id="<?= $row['supplier_id']; ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($row['company_name']); ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($row['contact_person'] ?: 'N/A'); ?></td>
                                    <td>
                                        <div><i class="fas fa-phone"></i> <?= htmlspecialchars($row['phone']); ?></div>
                                        <?php if (!empty($row['email'])): ?>
                                            <small><i class="fas fa-envelope"></i> <?= htmlspecialchars($row['email']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['address'])): ?>
                                            <i class="fas fa-map-marker-alt"></i> 
                                            <?= htmlspecialchars(substr($row['address'], 0, 30)) . (strlen($row['address'] ?? '') > 30 ? '...' : ''); ?>
                                        <?php else: ?>
                                            <span class="text-muted">No address</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="product-badge">
                                            <i class="fas fa-box"></i> <?= $product_count; ?> products
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $row['status']; ?>">
                                            <i class="fas fa-<?= $row['status'] == 'active' ? 'check-circle' : 'pause-circle'; ?>"></i>
                                            <?= ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-warning btn-sm action-btn editSupplierBtn" 
                                                data-id="<?= $row['supplier_id']; ?>" 
                                                data-toggle="tooltip" 
                                                title="Edit Supplier">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <button class="btn btn-danger btn-sm action-btn archiveSupplierBtn" 
                                                data-id="<?= $row['supplier_id']; ?>"
                                                data-name="<?= htmlspecialchars($row['company_name']); ?>"
                                                data-toggle="tooltip" 
                                                title="Archive Supplier">
                                            <i class="fas fa-archive"></i>
                                        </button>
                                        
                                        <a href="supplier-view.php?id=<?= $row['supplier_id']; ?>" 
                                           class="btn btn-info btn-sm action-btn" 
                                           data-toggle="tooltip" 
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    <i class="fas fa-info-circle"></i> No suppliers found.
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

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: #464660; color: white;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Add New Supplier
                </h4>
            </div>
            <form id="addSupplierForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-building"></i> Company Name <span class="text-danger">*</span></label>
                                <input type="text" name="company_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Contact Person <span class="text-danger">*</span></label>
                                <input type="text" name="contact_person" class="form-control" required>
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
                        <textarea name="address" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div class="modal fade" id="editSupplierModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: #464660; color: white;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fas fa-edit"></i> Edit Supplier
                </h4>
            </div>
            <form id="editSupplierForm">
                <div class="modal-body" id="editSupplierContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x"></i> Loading...
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../js/jquery.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/dataTables/jquery.dataTables.min.js"></script>
<script src="../js/dataTables/dataTables.bootstrap.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#suppliersTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100],
        order: [[0, 'asc']],
        language: {
            emptyTable: "No suppliers found"
        }
    });

    // Filter functionality
    $('.filter-btn').click(function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        
        var filter = $(this).data('filter');
        
        if (filter === 'all') {
            table.column(5).search('').draw();
        } else {
            table.column(5).search(filter, true, false).draw();
        }
    });

    // Add Supplier
    $('#addSupplierForm').submit(function(e) {
        e.preventDefault();
        
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
        
        $.ajax({
            url: '../Functions/supplier_add_ajax.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#addSupplierModal').modal('hide');
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
                Swal.fire('Error', 'Failed to add supplier', 'error');
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Edit Supplier - Load Modal
    $(document).on('click', '.editSupplierBtn', function() {
        var id = $(this).data('id');
        $('#editSupplierContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i> Loading...</div>');
        $('#editSupplierModal').modal('show');
        
        $.ajax({
            url: 'supplier_edit_modal.php',
            type: 'GET',
            data: {id: id},
            success: function(response) {
                $('#editSupplierContent').html(response);
            },
            error: function() {
                $('#editSupplierContent').html('<p class="text-danger">Failed to load supplier data.</p>');
            }
        });
    });

    // Update Supplier
    $(document).on('submit', '#editSupplierForm', function(e) {
        e.preventDefault();
        
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
        
        $.ajax({
            url: '../Functions/supplier_update_ajax.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#editSupplierModal').modal('hide');
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
                Swal.fire('Error', 'Failed to update supplier', 'error');
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Archive Supplier
    $(document).on('click', '.archiveSupplierBtn', function() {
        var supplier_id = $(this).data('id');
        var supplier_name = $(this).data('name');
        
        Swal.fire({
            title: 'Archive Supplier?',
            text: `Are you sure you want to archive ${supplier_name}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, archive it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../Functions/supplier_archive_ajax.php',
                    type: 'POST',
                    data: {id: supplier_id},
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
                        Swal.fire('Error', 'Failed to archive supplier', 'error');
                    }
                });
            }
        });
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>