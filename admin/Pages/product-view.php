<?php
include_once('../include/template.php');
include_once('../include/connection.php');

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$product_id = intval($_GET['id']);

// Fetch product info along with supplier
$sql = "SELECT p.*, s.company_name AS supplier_name, s.supplier_id, s.status as supplier_status
        FROM products p
        LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
        WHERE p.product_id = ?";
$stmt = mysqli_stmt_init($con);

if (!mysqli_stmt_prepare($stmt, $sql)) {
    die("Query failed");
}

mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result->num_rows === 0) {
    echo "<div class='alert alert-danger'>Product not found.</div>";
    exit;
}

$product = $result->fetch_assoc();

// Get recent purchase history for this product
$purchase_history = mysqli_query($con, "
    SELECT pi.*, p.purchase_date, p.remarks 
    FROM purchase_items pi
    JOIN purchases p ON pi.purchase_id = p.purchase_id
    WHERE pi.product_id = $product_id
    ORDER BY p.purchase_date DESC
    LIMIT 5
");

// Get total sold (if you have sales table)
// $total_sold = mysqli_query($con, "
//     SELECT SUM(quantity) as total 
//     FROM sale_items 
//     WHERE product_id = $product_id
// ");
// $total_sold = mysqli_fetch_assoc($total_sold)['total'] ?? 0;
?>

<style>
    /* Product View Specific Styles */
    .profile-header {
        background: linear-gradient(135deg, #464660 0%, #64648c 100%);
        color: white;
        padding: 25px 30px;
        border-radius: 8px;
        margin-bottom: 30px;
        box-shadow: 0 3px 10px rgba(70,70,96,0.3);
        position: relative;
        overflow: hidden;
    }
    
    .profile-header::before {
        content: '\f0d1';
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
        font-size: 28px;
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
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        border-left: 4px solid #464660;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card .stat-icon {
        position: absolute;
        right: 15px;
        top: 15px;
        font-size: 40px;
        opacity: 0.1;
        color: #464660;
    }
    
    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #464660;
        line-height: 1.2;
    }
    
    .stat-label {
        font-size: 13px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 5px;
    }
    
    .stat-card.warning-card {
        border-left-color: #ffc107;
    }
    
    .stat-card.warning-card .stat-value {
        color: #856404;
    }
    
    .stat-card.danger-card {
        border-left-color: #dc3545;
    }
    
    .stat-card.danger-card .stat-value {
        color: #721c24;
    }
    
    .stat-card.success-card {
        border-left-color: #28a745;
    }
    
    .stat-card.success-card .stat-value {
        color: #28a745;
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
        color: #64648c;
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
        transition: all 0.3s ease;
    }
    
    .detail-item:hover {
        background: #e9ecef;
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
        word-break: break-word;
    }
    
    .detail-value i {
        margin-right: 8px;
        color: #64648c;
        width: 20px;
    }
    
    .detail-value a {
        color: #464660;
        text-decoration: none;
    }
    
    .detail-value a:hover {
        color: #64648c;
        text-decoration: underline;
    }
    
    /* Status Badge */
    .status-badge-large {
        padding: 8px 20px;
        border-radius: 50px;
        font-size: 14px;
        font-weight: 600;
        display: inline-block;
    }
    
    .status-available {
        background: #d4edda;
        color: #155724;
    }
    
    .status-unavailable {
        background: #f8d7da;
        color: #721c24;
    }
    
    /* Price Display */
    .price-large {
        font-size: 24px;
        font-weight: 700;
        color: #28a745;
    }
    
    .price-label {
        font-size: 14px;
        color: #6c757d;
        font-weight: normal;
    }
    
    /* Stock Indicator */
    .stock-level {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 10px;
    }
    
    .stock-bar {
        flex: 1;
        height: 10px;
        background: #e9ecef;
        border-radius: 5px;
        overflow: hidden;
    }
    
    .stock-bar-fill {
        height: 100%;
        background: #28a745;
        border-radius: 5px;
    }
    
    .stock-bar-fill.warning {
        background: #ffc107;
    }
    
    .stock-bar-fill.danger {
        background: #dc3545;
    }
    
    /* Purchase History */
    .purchase-item {
        background: #f8f9fa;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 10px;
        border-left: 3px solid #464660;
    }
    
    .purchase-date {
        font-weight: 600;
        color: #464660;
    }
    
    .purchase-qty {
        display: inline-block;
        background: #e3f2fd;
        color: #0d47a1;
        padding: 3px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        margin-left: 10px;
    }
    
    .purchase-cost {
        float: right;
        font-weight: 600;
        color: #28a745;
    }
    
    /* Action Buttons */
    .action-buttons {
        margin-top: 30px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .btn-action {
        padding: 10px 25px;
        border-radius: 6px;
        font-weight: 600;
        transition: all 0.3s;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 3px 8px rgba(0,0,0,0.2);
    }
    
    .btn-primary-action {
        background: #464660;
        color: white;
    }
    
    .btn-primary-action:hover {
        background: #5a5a7a;
        color: white;
    }
    
    .btn-success-action {
        background: #28a745;
        color: white;
    }
    
    .btn-success-action:hover {
        background: #218838;
        color: white;
    }
    
    .btn-warning-action {
        background: #ffc107;
        color: #191919;
    }
    
    .btn-warning-action:hover {
        background: #e0a800;
        color: #191919;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 30px;
        background: #f8f9fa;
        border-radius: 6px;
    }
    
    .empty-state i {
        font-size: 40px;
        color: #adb5bd;
        margin-bottom: 10px;
    }
    
    .empty-state p {
        color: #6c757d;
        margin-bottom: 0;
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

<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="row">
        <div class="col-lg-12">
            <ol class="breadcrumb" style="background: none; padding: 0 0 15px 0;">
                <li><a href="products.php" style="color: #464660;">Products</a></li>
                <li class="active">Product Details</li>
            </ol>
        </div>
    </div>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="row">
            <div class="col-md-8">
                <h1 class="profile-name">
                    <i class="fas fa-box"></i> <?= htmlspecialchars($product['product_name']); ?>
                </h1>
                <div>
                    <span class="profile-badge">
                        <i class="fas fa-hashtag"></i> ID: #PROD-<?= str_pad($product['product_id'], 4, '0', STR_PAD_LEFT); ?>
                    </span>
                    <span class="profile-badge">
                        <i class="fas fa-calendar-alt"></i> Added: <?= date('M d, Y', strtotime($product['created_at'])); ?>
                    </span>
                </div>
            </div>
            <div class="col-md-4 text-right">
                <span class="status-badge-large status-<?= $product['status']; ?>">
                    <i class="fas fa-<?= $product['status'] == 'available' ? 'check-circle' : 'times-circle'; ?>"></i>
                    <?= ucfirst($product['status']); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-tag stat-icon"></i>
            <div class="stat-value"><?= htmlspecialchars($product['category'] ?: 'N/A'); ?></div>
            <div class="stat-label">Category</div>
        </div>
        
        <div class="stat-card success-card">
            <i class="fas fa-dollar-sign stat-icon"></i>
            <div class="stat-value">₱<?= number_format($product['price'], 2); ?></div>
            <div class="stat-label">Selling Price</div>
        </div>
        
        <div class="stat-card <?= $product['stock'] < 10 ? 'danger-card' : ($product['stock'] < 25 ? 'warning-card' : '') ?>">
            <i class="fas fa-cubes stat-icon"></i>
            <div class="stat-value"><?= (int)$product['stock']; ?></div>
            <div class="stat-label">Current Stock</div>
        </div>
        
        <?php if (isset($total_sold)): ?>
        <div class="stat-card">
            <i class="fas fa-shopping-cart stat-icon"></i>
            <div class="stat-value"><?= $total_sold; ?></div>
            <div class="stat-label">Total Sold</div>
        </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <!-- Product Information -->
        <div class="col-md-6">
            <div class="info-card">
                <h4 class="info-title">
                    <i class="fas fa-info-circle"></i> Product Information
                </h4>
                
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Product Name</div>
                        <div class="detail-value">
                            <i class="fas fa-box"></i>
                            <?= htmlspecialchars($product['product_name']); ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Category</div>
                        <div class="detail-value">
                            <i class="fas fa-tag"></i>
                            <?= htmlspecialchars($product['category'] ?: 'N/A'); ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Supplier</div>
                        <div class="detail-value">
                            <i class="fas fa-truck"></i>
                            <?php if ($product['supplier_id']): ?>
                                <a href="supplier-view.php?id=<?= $product['supplier_id']; ?>">
                                    <?= htmlspecialchars($product['supplier_name']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">No supplier assigned</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Status</div>
                        <div class="detail-value">
                            <span class="status-badge-large status-<?= $product['status']; ?>" style="padding: 3px 10px; font-size: 12px;">
                                <i class="fas fa-<?= $product['status'] == 'available' ? 'check-circle' : 'times-circle'; ?>"></i>
                                <?= ucfirst($product['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Stock Level Indicator -->
                <div style="margin-top: 20px;">
                    <div class="detail-label">Stock Level</div>
                    <div class="stock-level">
                        <span class="stock-value <?= $product['stock'] < 10 ? 'low' : ($product['stock'] < 25 ? 'medium' : 'high'); ?>">
                            <strong><?= (int)$product['stock']; ?> units</strong>
                        </span>
                        <div class="stock-bar">
                            <?php
                            $max_stock = 100; // You can set this dynamically
                            $percentage = min(100, ($product['stock'] / $max_stock) * 100);
                            $bar_class = 'success';
                            if ($product['stock'] < 10) $bar_class = 'danger';
                            elseif ($product['stock'] < 25) $bar_class = 'warning';
                            ?>
                            <div class="stock-bar-fill <?= $bar_class; ?>" style="width: <?= $percentage; ?>%;"></div>
                        </div>
                    </div>
                    <?php if ($product['stock'] < 10): ?>
                        <div class="alert alert-danger" style="margin-top: 10px; padding: 8px 12px;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Low Stock Alert!</strong> This product is running low on stock.
                            <?php if ($product['supplier_id']): ?>
                                <a href="supplier-view.php?id=<?= $product['supplier_id']; ?>#stockInModal" class="alert-link">
                                    Stock In Now
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Pricing & Details -->
        <div class="col-md-6">
            <div class="info-card">
                <h4 class="info-title">
                    <i class="fas fa-dollar-sign"></i> Pricing Details
                </h4>
                
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Selling Price</div>
                        <div class="detail-value price-large">₱<?= number_format($product['price'], 2); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Stock Value</div>
                        <div class="detail-value">
                            ₱<?= number_format($product['price'] * $product['stock'], 2); ?>
                            <div class="price-label">Total inventory value</div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($product['description'])): ?>
                    <div style="margin-top: 20px;">
                        <div class="detail-label">Description</div>
                        <div class="well well-sm" style="margin-top: 5px; background: #f8f9fa;">
                            <?= nl2br(htmlspecialchars($product['description'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Purchase History -->
            <div class="info-card">
                <h4 class="info-title">
                    <i class="fas fa-history"></i> Recent Purchase History
                </h4>
                
                <?php if ($purchase_history && $purchase_history->num_rows > 0): ?>
                    <?php while ($purchase = $purchase_history->fetch_assoc()): ?>
                        <div class="purchase-item">
                            <div>
                                <span class="purchase-date">
                                    <i class="fas fa-calendar-alt"></i> 
                                    <?= date('M d, Y', strtotime($purchase['purchase_date'])); ?>
                                </span>
                                <span class="purchase-qty">
                                    <i class="fas fa-cubes"></i> <?= $purchase['quantity']; ?> units
                                </span>
                                <span class="purchase-cost">
                                    ₱<?= number_format($purchase['cost'], 2); ?>/unit
                                </span>
                            </div>
                            <?php if (!empty($purchase['remarks'])): ?>
                                <div style="margin-top: 8px; color: #6c757d; font-size: 12px;">
                                    <i class="fas fa-comment"></i> <?= htmlspecialchars($purchase['remarks']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                    
                    <?php if ($product['supplier_id']): ?>
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="supplier-view.php?id=<?= $product['supplier_id']; ?>" class="btn btn-sm btn-default">
                                <i class="fas fa-truck"></i> View All Purchases from Supplier
                            </a>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <p>No purchase history found for this product.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="products.php" class="btn-action btn-primary-action">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>
        
        <button class="btn-action btn-warning-action editProductBtn" 
                data-id="<?= $product['product_id']; ?>"
                data-name="<?= htmlspecialchars($product['product_name']); ?>"
                data-supplier="<?= $product['supplier_id']; ?>"
                data-category="<?= htmlspecialchars($product['category']); ?>"
                data-price="<?= $product['price']; ?>"
                data-stock="<?= $product['stock']; ?>"
                data-status="<?= $product['status']; ?>"
                data-description="<?= htmlspecialchars($product['description'] ?? ''); ?>">
            <i class="fas fa-edit"></i> Edit Product
        </button>
        
        <?php if ($product['supplier_id']): ?>
            <a href="supplier-view.php?id=<?= $product['supplier_id']; ?>#stockInModal" class="btn-action btn-success-action">
                <i class="fas fa-boxes"></i> Stock In
            </a>
        <?php endif; ?>
        
        <button onclick="window.print()" class="btn-action btn-primary-action" style="background: #6c757d;">
            <i class="fas fa-print"></i> Print Details
        </button>
    </div>
</div>

<!-- Edit Product Modal (reuse from products.php) -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fas fa-edit"></i> Edit Product
                </h4>
            </div>
            <form method="POST" action="../Functions/product_update.php">
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="edit_product_id">

                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="product_name" id="edit_product_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Supplier</label>
                        <select name="supplier_id" id="edit_supplier_id" class="form-control">
                            <option value="">-- Select Supplier --</option>
                            <?php
                            $suppliers = $con->query("SELECT supplier_id, company_name FROM suppliers WHERE status='active' ORDER BY company_name");
                            while ($s = $suppliers->fetch_assoc()) {
                                echo "<option value='{$s['supplier_id']}'>{$s['company_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" name="category" id="edit_category" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Price</label>
                        <input type="number" step="0.01" name="price" id="edit_price" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Stock</label>
                        <input type="number" name="stock" id="edit_stock" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit_status" class="form-control">
                            <option value="available">Available</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_product" class="btn btn-success">
                        <i class="fas fa-save"></i> Update Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Edit Product - Populate Modal
    $('.editProductBtn').on('click', function () {
        $('#edit_product_id').val($(this).data('id'));
        $('#edit_product_name').val($(this).data('name'));
        $('#edit_supplier_id').val($(this).data('supplier'));
        $('#edit_category').val($(this).data('category'));
        $('#edit_price').val($(this).data('price'));
        $('#edit_stock').val($(this).data('stock'));
        $('#edit_status').val($(this).data('status'));
        $('#edit_description').val($(this).data('description') || '');
        
        $('#editProductModal').modal('show');
    });
});
</script>