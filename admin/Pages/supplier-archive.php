<?php
// ../Pages/supplier-archive.php
include_once('../include/template.php');
include_once('../include/connection.php');

// Get only archived suppliers
$sql = "SELECT * FROM suppliers 
        WHERE status = 'archived' 
        ORDER BY company_name ASC";
$result = $con->query($sql);

// Count archived suppliers
$count_query = mysqli_query($con, "SELECT COUNT(*) as total FROM suppliers WHERE status = 'archived'");
$count_data = mysqli_fetch_assoc($count_query);
$archived_count = $count_data['total'];

// Get product counts for archived suppliers
$products_query = mysqli_query($con, "
    SELECT supplier_id, COUNT(*) as product_count 
    FROM products 
    WHERE supplier_id IN (SELECT supplier_id FROM suppliers WHERE status = 'archived')
    GROUP BY supplier_id
");
$product_counts = [];
while ($row = mysqli_fetch_assoc($products_query)) {
    $product_counts[$row['supplier_id']] = $row['product_count'];
}
?>

<style>
    /* Archive page specific styles */
    .archive-header {
        background: #6c757d;
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .archive-header h4 {
        margin: 0;
        font-weight: 600;
    }
    
    .archive-header i {
        margin-right: 10px;
    }
    
    .badge-archived {
        background: #dc3545;
        color: white;
        padding: 5px 15px;
        border-radius: 50px;
        font-size: 14px;
        font-weight: 600;
    }
    
    .restore-btn {
        background: #28a745;
        color: white;
        border: none;
        padding: 5px 12px;
        border-radius: 4px;
        transition: all 0.3s;
    }
    
    .restore-btn:hover {
        background: #218838;
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    .delete-permanent-btn {
        background: #dc3545;
        color: white;
        border: none;
        padding: 5px 12px;
        border-radius: 4px;
        transition: all 0.3s;
    }
    
    .delete-permanent-btn:hover {
        background: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    .info-box {
        background: #e7f3ff;
        border-left: 4px solid #17a2b8;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .info-box i {
        color: #17a2b8;
        margin-right: 10px;
        font-size: 18px;
    }
    
    /* Status badge for archived */
    .status-archived {
        background: #f8d7da;
        color: #721c24;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
    
    /* Company name style */
    .company-name {
        font-weight: 600;
        color: #464660;
    }
    
    .company-name i {
        color: #6c757d;
        margin-right: 5px;
    }
    
    /* Product badge */
    .product-badge {
        background: #e3f2fd;
        color: #0d47a1;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }
    
    /* Action buttons */
    .action-btn {
        border-radius: 4px;
        padding: 5px 10px;
        margin: 0 3px;
        transition: all 0.3s;
    }
    
    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 50px;
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
        font-size: 16px;
        margin-bottom: 20px;
    }
    
    .empty-state .btn {
        padding: 10px 25px;
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header" style="font-weight: 600; color: #464660;">
            <i class="fas fa-archive"></i> Supplier Archive
        </h1>
    </div>
</div>

<!-- Archive Header -->
<div class="archive-header">
    <h4>
        <i class="fas fa-database"></i> 
        Archived Suppliers
    </h4>
    <div>
        <span class="badge-archived">
            <i class="fas fa-archive"></i> Total: <?= $archived_count; ?>
        </span>
        <a href="suppliers.php" class="btn btn-light" style="background: white; color: #6c757d; margin-left: 15px;">
            <i class="fas fa-arrow-left"></i> Back to Suppliers
        </a>
    </div>
</div>

<?php if ($archived_count > 0): ?>
    <!-- Info Box -->
    <div class="info-box">
        <i class="fas fa-info-circle"></i>
        <strong>Note:</strong> These suppliers are currently archived. You can restore them to make them active again, or permanently delete them.
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong><i class="fas fa-list"></i> Archived Supplier List</strong>
                <div class="pull-right">
                    <span class="status-archived">
                        <i class="fas fa-archive"></i> <?= $archived_count; ?> archived
                    </span>
                </div>
            </div>

            <div class="panel-body">
                <?php if ($result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table id="archiveTable" class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Company Name</th>
                                    <th>Contact Person</th>
                                    <th>Contact Info</th>
                                    <th>Address</th>
                                    <th>Products</th>
                                    <th>Status</th>
                                    <th width="200">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): 
                                    $product_count = $product_counts[$row['supplier_id']] ?? 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="company-name">
                                            <i class="fas fa-building"></i>
                                            <?= htmlspecialchars($row['company_name']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="fas fa-user" style="color: #6c757d; margin-right: 5px;"></i>
                                        <?= htmlspecialchars($row['contact_person'] ?: 'N/A'); ?>
                                    </td>
                                    <td>
                                        <div class="contact-info">
                                            <i class="fas fa-phone"></i> <?= htmlspecialchars($row['phone']); ?><br>
                                            <?php if (!empty($row['email'])): ?>
                                                <small><i class="fas fa-envelope"></i> <?= htmlspecialchars($row['email']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['address'])): ?>
                                            <i class="fas fa-map-marker-alt" style="color: #6c757d; margin-right: 5px;"></i>
                                            <?= htmlspecialchars(substr($row['address'], 0, 30)) . (strlen($row['address'] ?? '') > 30 ? '...' : ''); ?>
                                        <?php else: ?>
                                            <span class="text-muted"><i class="fas fa-map-marker-alt"></i> No address</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="product-badge">
                                            <i class="fas fa-box"></i> <?= $product_count; ?> products
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-archived">
                                            <i class="fas fa-archive"></i> Archived
                                        </span>
                                    </td>
                                    <td>
                                        <!-- Restore Button -->
                                        <button class="btn btn-success btn-sm action-btn restoreSupplierBtn" 
                                                data-id="<?= $row['supplier_id']; ?>" 
                                                data-toggle="tooltip" 
                                                title="Restore Supplier">
                                            <i class="fas fa-undo"></i> Restore
                                        </button>
                                        
                                        <!-- View Button -->
                                        <a href="supplier-view.php?id=<?= $row['supplier_id']; ?>" 
                                           class="btn btn-info btn-sm action-btn" 
                                           data-toggle="tooltip" 
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <!-- Permanent Delete Button (Optional) -->
                                        <button class="btn btn-danger btn-sm action-btn deletePermanentBtn" 
                                                data-id="<?= $row['supplier_id']; ?>" 
                                                data-toggle="tooltip" 
                                                title="Permanently Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-archive"></i>
                        <p>No archived suppliers found.</p>
                        <a href="suppliers.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Suppliers
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Restore Confirmation Modal (Optional) -->
<div class="modal fade" id="restoreConfirmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background: #28a745; color: white;">
                <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                <h4 class="modal-title">
                    <i class="fas fa-undo"></i> Restore Supplier
                </h4>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-question-circle" style="font-size: 48px; color: #28a745; margin-bottom: 15px;"></i>
                <p>Are you sure you want to restore this supplier?</p>
                <p class="text-muted">They will become active again.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmRestoreBtn">
                    <i class="fas fa-undo"></i> Restore
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#archiveTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100],
        order: [[0, 'asc']],
        language: {
            emptyTable: "No archived suppliers found"
        }
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Restore Supplier
    $('.restoreSupplierBtn').click(function(){
        var id = $(this).data('id');
        var btn = $(this);
        
        Swal.fire({
            title: 'Restore Supplier?',
            text: 'This supplier will be restored to active status.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, restore it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading on button
                btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
                
                $.ajax({
                    url: '../Functions/supplier_restore.php',
                    type: 'POST',
                    data: {id: id},
                    dataType: 'json',
                    success: function(res){
                        if(res.status === 'success'){
                            Swal.fire({
                                icon: 'success',
                                title: 'Restored!',
                                text: 'Supplier has been restored successfully',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: res.message || 'Failed to restore supplier'
                            });
                            btn.html('<i class="fas fa-undo"></i> Restore').prop('disabled', false);
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to restore supplier. Please try again.'
                        });
                        btn.html('<i class="fas fa-undo"></i> Restore').prop('disabled', false);
                    }
                });
            }
        });
    });

    // Permanent Delete (Optional - with warning)
    $('.deletePermanentBtn').click(function(){
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Permanently Delete?',
            text: 'WARNING: This action cannot be undone! All supplier data will be permanently removed.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete permanently',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../Functions/supplier_permanent_delete.php',
                    type: 'POST',
                    data: {id: id},
                    dataType: 'json',
                    success: function(res){
                        if(res.status === 'success'){
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'Supplier has been permanently deleted',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: res.message || 'Failed to delete supplier'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to delete supplier. Please try again.'
                        });
                    }
                });
            }
        });
    });
});
</script>