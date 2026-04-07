<?php
include_once('../include/template.php');
include_once('../include/connection.php');

// Fetch all active products with supplier info
$sql = "SELECT p.*, s.company_name AS supplier_name 
        FROM products p
        LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
        WHERE p.status = 'available'
        ORDER BY p.product_name ASC";
$result = $con->query($sql);

// Get statistics
$total_products = mysqli_query($con, "SELECT COUNT(*) as total FROM products");
$total_products = mysqli_fetch_assoc($total_products)['total'];

$available_count = mysqli_query($con, "SELECT COUNT(*) as total FROM products WHERE status = 'available'");
$available_count = mysqli_fetch_assoc($available_count)['total'];

$unavailable_count = mysqli_query($con, "SELECT COUNT(*) as total FROM products WHERE status = 'unavailable'");
$unavailable_count = mysqli_fetch_assoc($unavailable_count)['total'];

$low_stock_count = mysqli_query($con, "SELECT COUNT(*) as total FROM products WHERE stock < 10 AND status = 'available'");
$low_stock_count = mysqli_fetch_assoc($low_stock_count)['total'];

$total_stock = mysqli_query($con, "SELECT SUM(stock) as total FROM products");
$total_stock = mysqli_fetch_assoc($total_stock)['total'];

$total_value = mysqli_query($con, "SELECT SUM(stock * price) as total FROM products");
$total_value = mysqli_fetch_assoc($total_value)['total'];

// Get categories for filter
$categories = mysqli_query($con, "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
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
    
    .category-badge {
        background: #e3f2fd;
        color: #0d47a1;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }
    
    .status-badge {
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
    
    .status-available {
        background: #d4edda;
        color: #155724;
    }
    
    .price {
        font-weight: 700;
        color: #28a745;
    }
    
    .stock-indicator {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 5px;
    }
    
    .stock-high { background: #28a745; }
    .stock-medium { background: #ffc107; }
    .stock-low { background: #dc3545; }
    
    .stock-value.low { color: #dc3545; font-weight: 600; }
    .stock-value.medium { color: #856404; font-weight: 600; }
    .stock-value.high { color: #28a745; font-weight: 600; }
    
    .filter-select {
        padding: 4px 8px;
        border: 1px solid #ddd;
        border-radius: 3px;
        margin-right: 10px;
        font-size: 12px;
        height: 28px;
    }
    
    .filter-btn {
        padding: 4px 12px;
        margin-right: 3px;
        border: 1px solid #ddd;
        background: white;
        border-radius: 3px;
        cursor: pointer;
        font-size: 12px;
    }
    
    .filter-btn.active {
        background: #464660;
        color: white;
        border-color: #464660;
    }
    
    .action-btn {
        margin: 0 2px;
    }
    
    .product-row {
        transition: background 0.3s;
    }
    
    .product-row:hover {
        background: #f8f9fa;
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header" style="font-weight:600; color:#464660;">
            <i class="fas fa-boxes"></i> Products
        </h1>
    </div>
</div>

<!-- Summary Cards -->
<div class="row">
    <div class="col-md-2 col-sm-4">
        <div class="summary-card" style="border-left-color: #464660;">
            <div class="summary-number"><?= $total_products; ?></div>
            <div class="summary-label">Total Products</div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="summary-card" style="border-left-color: #28a745;">
            <div class="summary-number"><?= $available_count; ?></div>
            <div class="summary-label">Available</div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="summary-card" style="border-left-color: #ffc107;">
            <div class="summary-number"><?= $low_stock_count; ?></div>
            <div class="summary-label">Low Stock</div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="summary-card" style="border-left-color: #17a2b8;">
            <div class="summary-number"><?= number_format($total_stock); ?></div>
            <div class="summary-label">Total Units</div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="summary-card" style="border-left-color: #28a745;">
            <div class="summary-number">₱<?= number_format($total_value, 2); ?></div>
            <div class="summary-label">Inventory Value</div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div class="summary-card" style="border-left-color: #dc3545;">
            <div class="summary-number"><?= $unavailable_count; ?></div>
            <div class="summary-label">Unavailable</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <strong><i class="fas fa-list"></i> Product Inventory</strong>
                    </div>
                    <div class="col-md-6 text-right">
                        <select class="filter-select" id="categoryFilter">
                            <option value="">All Categories</option>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($cat['category']); ?>">
                                    <?= htmlspecialchars($cat['category']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        
                        <button class="filter-btn active" data-filter="all">All</button>
                        <button class="filter-btn" data-filter="low">Low Stock</button>
                        <button class="filter-btn" data-filter="normal">Normal</button>
                        
                        <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#addProductModal" style="margin-left: 10px;">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                        <!-- <a href="../reports/product-list.php" target="_blank" class="btn btn-primary btn-sm">
                            <i class="fas fa-print"></i> Print
                        </a>
                        <a href="product-archive.php" class="btn btn-warning btn-sm">
                            <i class="fas fa-archive"></i> Archive
                        </a> -->
                    </div>
                </div>
            </div>

            <div class="panel-body">
                <div class="table-responsive">
                    <table id="productsTable" class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Supplier</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th width="140">Actions</th>
                            </thead>
                        <tbody id="productsTableBody">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): 
                                $stock_class = 'high';
                                if ($row['stock'] < 10) {
                                    $stock_class = 'low';
                                } elseif ($row['stock'] < 25) {
                                    $stock_class = 'medium';
                                }
                            ?>
                                <tr class="product-row" data-category="<?= htmlspecialchars($row['category']); ?>" data-stock="<?= $row['stock']; ?>" data-id="<?= $row['product_id']; ?>">
                                    <td>
                                        <a href="product-view.php?id=<?= $row['product_id']; ?>" class="text-primary">
                                            <i class="fas fa-box"></i> <?= htmlspecialchars($row['product_name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($row['supplier_name']): ?>
                                            <a href="supplier-view.php?id=<?= $row['supplier_id']; ?>" class="text-muted">
                                                <i class="fas fa-truck"></i> <?= htmlspecialchars($row['supplier_name']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted"><i class="fas fa-truck"></i> No Supplier</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['category'])): ?>
                                            <span class="category-badge">
                                                <i class="fas fa-tag"></i> <?= htmlspecialchars($row['category']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="price">₱<?= number_format($row['price'], 2); ?></td>
                                    <td>
                                        <span class="stock-indicator stock-<?= $stock_class; ?>"></span>
                                        <span class="stock-value <?= $stock_class; ?>">
                                            <?= (int)$row['stock']; ?>
                                        </span>
                                        <?php if ($row['stock'] < 10 && $row['status'] == 'available'): ?>
                                            <i class="fas fa-exclamation-triangle text-danger" style="margin-left: 5px;" title="Low Stock"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $row['status']; ?>">
                                            <i class="fas fa-<?= $row['status'] == 'available' ? 'check-circle' : 'times-circle'; ?>"></i>
                                            <?= $row['status'] == 'available' ? 'Available' : 'Unavailable'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-warning btn-sm action-btn editProductBtn" 
                                                data-id="<?= $row['product_id']; ?>" 
                                                data-toggle="tooltip" 
                                                title="Edit Product">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <!-- <button class="btn btn-danger btn-sm action-btn archiveProductBtn" 
                                                data-id="<?= $row['product_id']; ?>"
                                                data-name="<?= htmlspecialchars($row['product_name']); ?>"
                                                data-toggle="tooltip" 
                                                title="Archive Product">
                                            <i class="fas fa-archive"></i>
                                        </button> -->
                                        
                                        <a href="product-view.php?id=<?= $row['product_id']; ?>" 
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
                                    <i class="fas fa-box-open"></i> No products found.
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

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: #464660; color: white;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Add New Product
                </h4>
            </div>
            <form id="addProductForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label><i class="fas fa-box"></i> Product Name <span class="text-danger">*</span></label>
                                <input type="text" name="product_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-tag"></i> Category <span class="text-danger">*</span></label>
                                <input type="text" name="category" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-truck"></i> Supplier</label>
                                <select name="supplier_id" class="form-control">
                                    <option value="">-- Select Supplier (Optional) --</option>
                                    <?php
                                    $suppliers = mysqli_query($con, "SELECT supplier_id, company_name FROM suppliers WHERE status='active' ORDER BY company_name");
                                    while ($s = mysqli_fetch_assoc($suppliers)) {
                                        echo "<option value='{$s['supplier_id']}'>{$s['company_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-dollar-sign"></i> Price <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="price" class="form-control" required min="0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-cubes"></i> Initial Stock</label>
                                <input type="number" name="stock" class="form-control" value="0" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-info-circle"></i> Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Product description (optional)"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-toggle-on"></i> Status</label>
                        <select name="status" class="form-control">
                            <option value="available">Available</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: #464660; color: white;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fas fa-edit"></i> Edit Product
                </h4>
            </div>
            <form id="editProductForm">
                <div class="modal-body" id="editProductContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x"></i> Loading...
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Product</button>
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
    var table = $('#productsTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100],
        order: [[0, 'asc']],
        language: {
            emptyTable: "No products found"
        }
    });

    // Category Filter
    $('#categoryFilter').on('change', function() {
        var category = $(this).val();
        if (category === '') {
            table.column(2).search('').draw();
        } else {
            table.column(2).search('^' + category + '$', true, false).draw();
        }
    });

    // Stock Level Filter
    $('.filter-btn').click(function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        
        var filter = $(this).data('stock');
        
        if (filter === 'all') {
            table.column(4).search('').draw();
        } else if (filter === 'low') {
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    var stock = parseInt(data[4]) || 0;
                    return stock < 10;
                }
            );
            table.draw();
            $.fn.dataTable.ext.search.pop();
        } else if (filter === 'normal') {
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    var stock = parseInt(data[4]) || 0;
                    return stock >= 10;
                }
            );
            table.draw();
            $.fn.dataTable.ext.search.pop();
        }
    });

    // Add Product
    $('#addProductForm').submit(function(e) {
        e.preventDefault();
        
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
        
        $.ajax({
            url: '../Functions/product_add_ajax.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#addProductModal').modal('hide');
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
                Swal.fire('Error', 'Failed to add product', 'error');
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Edit Product - Load Modal
    $(document).on('click', '.editProductBtn', function() {
        var id = $(this).data('id');
        $('#editProductContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i> Loading...</div>');
        $('#editProductModal').modal('show');
        
        $.ajax({
            url: 'product_edit_modal.php',
            type: 'GET',
            data: {id: id},
            success: function(response) {
                $('#editProductContent').html(response);
            },
            error: function() {
                $('#editProductContent').html('<p class="text-danger">Failed to load product data.</p>');
            }
        });
    });

    // Update Product
    $(document).on('submit', '#editProductForm', function(e) {
        e.preventDefault();
        
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
        
        $.ajax({
            url: '../Functions/product_update_ajax.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#editProductModal').modal('hide');
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
                Swal.fire('Error', 'Failed to update product', 'error');
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Archive Product
    $(document).on('click', '.archiveProductBtn', function() {
        var product_id = $(this).data('id');
        var product_name = $(this).data('name');
        
        Swal.fire({
            title: 'Archive Product?',
            text: `Are you sure you want to archive ${product_name}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, archive it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../Functions/product_archive_ajax.php',
                    type: 'POST',
                    data: {id: product_id},
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
                        Swal.fire('Error', 'Failed to archive product', 'error');
                    }
                });
            }
        });
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>