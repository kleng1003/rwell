<?php
include_once('../include/template.php');
include_once('../include/connection.php');

// Get the active tab/filter
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';

// Build SQL based on tab
$where_clause = "";
if ($active_tab == 'available') {
    $where_clause = "WHERE p.status = 'available'";
} elseif ($active_tab == 'unavailable') {
    $where_clause = "WHERE p.status = 'unavailable'";
} elseif ($active_tab == 'expired') {
    $where_clause = "WHERE p.expiration_date IS NOT NULL AND p.expiration_date <= CURDATE() AND p.status != 'unavailable'";
} elseif ($active_tab == 'expiring') {
    $where_clause = "WHERE p.expiration_date IS NOT NULL AND p.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND p.status = 'available'";
}

// Fetch products with supplier info
$sql = "SELECT p.*, s.company_name AS supplier_name,
        CASE 
            WHEN p.expiration_date IS NOT NULL AND p.expiration_date <= CURDATE() THEN 'expired'
            WHEN p.expiration_date IS NOT NULL AND p.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring'
            ELSE 'ok'
        END as expiration_status,
        DATEDIFF(p.expiration_date, CURDATE()) as days_until_expiry
        FROM products p
        LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
        $where_clause
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

$expired_count = mysqli_query($con, "SELECT COUNT(*) as total FROM products WHERE expiration_date IS NOT NULL AND expiration_date <= CURDATE() AND status = 'available'");
$expired_count = mysqli_fetch_assoc($expired_count)['total'];

$expiring_count = mysqli_query($con, "SELECT COUNT(*) as total FROM products WHERE expiration_date IS NOT NULL AND expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status = 'available'");
$expiring_count = mysqli_fetch_assoc($expiring_count)['total'];

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

    .expiration-badge {
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
        white-space: nowrap;
    }

    .expiration-expired {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .expiration-expiring {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }

    .expiration-ok {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .expiration-na {
        color: #6c757d;
        font-size: 12px;
    }

    .nav-tabs-custom {
        border-bottom: 2px solid #dee2e6;
        margin-bottom: 20px;
    }

    .nav-tabs-custom > li > a {
        color: #6c757d;
        font-weight: 500;
        padding: 10px 20px;
        border: none;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
    }

    .nav-tabs-custom > li > a:hover {
        background: none;
        color: #464660;
        border-bottom-color: #64648c;
    }

    .nav-tabs-custom > li.active > a {
        color: #464660;
        border: none;
        border-bottom: 3px solid #464660;
        font-weight: 700;
    }

    .nav-tabs-custom .badge {
        margin-left: 5px;
        font-size: 10px;
    }

    .badge-danger-custom {
        background: #dc3545;
        color: white;
    }

    .badge-warning-custom {
        background: #ffc107;
        color: #191919;
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
            <div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <!-- Navigation Tabs -->
                <ul class="nav nav-tabs-custom" style="margin-bottom: 15px; padding: 0;">
                    <li class="<?= $active_tab == 'all' ? 'active' : ''; ?>">
                        <a href="?tab=all">
                            <i class="fas fa-boxes"></i> All Products
                            <span class="badge"><?= $total_products; ?></span>
                        </a>
                    </li>
                    <li class="<?= $active_tab == 'available' ? 'active' : ''; ?>">
                        <a href="?tab=available">
                            <i class="fas fa-check-circle text-success"></i> Available
                            <span class="badge"><?= $available_count; ?></span>
                        </a>
                    </li>
                    <li class="<?= $active_tab == 'expiring' ? 'active' : ''; ?>">
                        <a href="?tab=expiring">
                            <i class="fas fa-exclamation-triangle text-warning"></i> Expiring Soon
                            <?php if ($expiring_count > 0): ?>
                                <span class="badge badge-warning-custom"><?= $expiring_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="<?= $active_tab == 'expired' ? 'active' : ''; ?>">
                        <a href="?tab=expired">
                            <i class="fas fa-skull-crossbones text-danger"></i> Expired
                            <?php if ($expired_count > 0): ?>
                                <span class="badge badge-danger-custom"><?= $expired_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="<?= $active_tab == 'unavailable' ? 'active' : ''; ?>">
                        <a href="?tab=unavailable">
                            <i class="fas fa-times-circle text-muted"></i> Unavailable
                            <span class="badge"><?= $unavailable_count; ?></span>
                        </a>
                    </li>
                </ul>

                <div class="row">
                    <div class="col-md-6">
                        <strong>
                            <i class="fas fa-list"></i> 
                            <?php 
                            $tab_labels = [
                                'all' => 'All Products',
                                'available' => 'Available Products',
                                'unavailable' => 'Unavailable Products',
                                'expired' => 'Expired Products',
                                'expiring' => 'Expiring Soon'
                            ];
                            echo $tab_labels[$active_tab] ?? 'Product Inventory';
                            ?>
                        </strong>
                        <?php if ($active_tab == 'expired'): ?>
                            <span class="label label-danger" style="margin-left: 10px;">
                                <i class="fas fa-exclamation-circle"></i> Action Required
                            </span>
                        <?php elseif ($active_tab == 'expiring'): ?>
                            <span class="label label-warning" style="margin-left: 10px;">
                                <i class="fas fa-clock"></i> Take Action Soon
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 text-right">
                        <select class="filter-select" id="categoryFilter">
                            <option value="">All Categories</option>
                            <?php 
                            $categories->data_seek(0); // Reset pointer
                            while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($cat['category']); ?>">
                                    <?= htmlspecialchars($cat['category']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        
                        <!-- <?php if ($active_tab != 'expired' && $active_tab != 'unavailable'): ?>
                            <button class="filter-btn active" data-filter="all">All</button>
                            <button class="filter-btn" data-filter="low">Low Stock</button>
                            <button class="filter-btn" data-filter="normal">Normal</button>
                        <?php endif; ?> -->
                        
                        <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addProductModal" style="margin-left: 10px;">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                        
                        <?php if ($active_tab == 'expired'): ?>
                            <a href="expired-products.php" class="btn btn-danger btn-sm" style="margin-left: 5px;">
                                <i class="fas fa-print"></i> Print Report
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($active_tab == 'expired'): ?>
                <div class="alert alert-danger" style="margin: 0; border-radius: 0;">
                    <i class="fas fa-skull-crossbones"></i>
                    <strong>Warning:</strong> These products have passed their expiration date. 
                    Please review and take appropriate action (dispose, return to supplier, or update expiration date).
                </div>
            <?php elseif ($active_tab == 'expiring' && $expiring_count > 0): ?>
                <div class="alert alert-warning" style="margin: 0; border-radius: 0;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Attention:</strong> <?= $expiring_count; ?> product(s) will expire within 30 days. 
                    Consider running promotions or discounts to sell them before expiration.
                </div>
            <?php endif; ?>

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
                                <th>Expiration</th>
                                <th>Status</th>
                                <th width="140">Actions</th>
                            </tr>
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
                                
                                // Determine expiration display
                                $expiration_display = '';
                                $expiration_class = '';
                                
                                if (!empty($row['expiration_date'])) {
                                    $exp_date = $row['expiration_date'];
                                    $today = date('Y-m-d');
                                    
                                    if ($exp_date < $today) {
                                        // Expired
                                        $days = abs(floor((strtotime($today) - strtotime($exp_date)) / 86400));
                                        $expiration_display = '<span class="expiration-badge expiration-expired" title="Expired ' . $days . ' days ago">
                                            <i class="fas fa-skull-crossbones"></i> Expired ' . $days . 'd ago
                                        </span>';
                                    } elseif ($exp_date <= date('Y-m-d', strtotime('+7 days'))) {
                                        // Critical - within 7 days
                                        $days = floor((strtotime($exp_date) - strtotime($today)) / 86400);
                                        $expiration_display = '<span class="expiration-badge expiration-expired" title="Expires in ' . $days . ' days">
                                            <i class="fas fa-exclamation-circle"></i> ' . $days . ' days left
                                        </span>';
                                    } elseif ($exp_date <= date('Y-m-d', strtotime('+30 days'))) {
                                        // Warning - within 30 days
                                        $days = floor((strtotime($exp_date) - strtotime($today)) / 86400);
                                        $expiration_display = '<span class="expiration-badge expiration-expiring" title="Expires in ' . $days . ' days">
                                            <i class="fas fa-exclamation-triangle"></i> ' . date('M d', strtotime($exp_date)) . ' (' . $days . 'd)
                                        </span>';
                                    } else {
                                        // OK
                                        $expiration_display = '<span class="expiration-badge expiration-ok">
                                            <i class="fas fa-calendar-check"></i> ' . date('M d, Y', strtotime($exp_date)) . '
                                        </span>';
                                    }
                                } else {
                                    $expiration_display = '<span class="expiration-na">—</span>';
                                }
                            ?>
                                <tr class="product-row" data-category="<?= htmlspecialchars($row['category']); ?>" data-stock="<?= $row['stock']; ?>" data-id="<?= $row['product_id']; ?>">
                                    <td>
                                        <a href="product-view.php?id=<?= $row['product_id']; ?>" class="text-primary">
                                            <i class="fas fa-box"></i> <?= htmlspecialchars($row['product_name']); ?>
                                        </a>
                                        <?php if (!empty($row['expiration_date']) && $row['expiration_date'] < date('Y-m-d')): ?>
                                            <i class="fas fa-exclamation-circle text-danger" title="Expired product"></i>
                                        <?php endif; ?>
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
                                        <?= $expiration_display; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $row['status']; ?>">
                                            <i class="fas fa-<?= $row['status'] == 'available' ? 'check-circle' : 'times-circle'; ?>"></i>
                                            <?= ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-warning btn-sm action-btn editProductBtn" 
                                                data-id="<?= $row['product_id']; ?>" 
                                                data-toggle="tooltip" 
                                                title="Edit Product">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <a href="product-view.php?id=<?= $row['product_id']; ?>" 
                                        class="btn btn-info btn-sm action-btn" 
                                        data-toggle="tooltip" 
                                        title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($active_tab == 'expired'): ?>
                                            <button class="btn btn-success btn-sm action-btn updateExpiryBtn"
                                                    data-id="<?= $row['product_id']; ?>"
                                                    data-name="<?= htmlspecialchars($row['product_name']); ?>"
                                                    title="Update Expiration Date">
                                                <i class="fas fa-calendar-plus"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    <i class="fas fa-box-open"></i> 
                                    <?php 
                                    $empty_messages = [
                                        'all' => 'No products found.',
                                        'available' => 'No available products.',
                                        'unavailable' => 'No unavailable products.',
                                        'expired' => '<span class="text-success">No expired products. Great job!</span>',
                                        'expiring' => 'No products expiring soon.'
                                    ];
                                    echo $empty_messages[$active_tab] ?? 'No products found.';
                                    ?>
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
                                <label><i class="fas fa-dollar-sign"></i> Cost Price</label>
                                <input type="number" step="0.01" name="cost_price" class="form-control" value="0.00" min="0">
                                <small class="text-muted">Purchase cost</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-dollar-sign"></i> Selling Price <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="price" class="form-control" required min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-cubes"></i> Initial Stock</label>
                                <input type="number" name="stock" class="form-control" value="0" min="0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-plus"></i> Manufacturing Date</label>
                                <input type="date" name="manufacturing_date" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-times"></i> Expiration Date</label>
                                <input type="date" name="expiration_date" class="form-control">
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
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                $('#editProductContent').html('<p class="text-danger">Failed to load product data. Error: ' + error + '</p>');
            }
        });
    });

    // Update Product
    $(document).on('submit', '#editProductForm', function(e) {
        e.preventDefault();
        
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
        
        // Get form data
        var formData = $(this).serialize();
        console.log('Form data:', formData); // For debugging
        
        $.ajax({
            url: '../Functions/product_update_ajax.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                console.log('Response:', res); // For debugging
                
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
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: res.message || 'Unknown error occurred'
                    });
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to update product. Please check console for details.'
                });
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