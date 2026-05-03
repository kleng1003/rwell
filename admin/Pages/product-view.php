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
                        <div class="detail-label">Cost Price</div>
                        <div class="detail-value">
                            ₱<?= number_format($product['cost_price'] ?? 0, 2); ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Selling Price</div>
                        <div class="detail-value price-large">
                            ₱<?= number_format($product['price'], 2); ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Profit Margin</div>
                        <div class="detail-value">
                            <?php 
                            $cost = $product['cost_price'] ?? 0;
                            $price = $product['price'];
                            $margin = $cost > 0 ? (($price - $cost) / $price) * 100 : 100;
                            ?>
                            <span class="text-<?= $margin >= 30 ? 'success' : ($margin >= 15 ? 'warning' : 'danger'); ?>">
                                <?= number_format($margin, 1); ?>%
                            </span>
                            <div class="price-label">(₱<?= number_format($price - $cost, 2); ?> per unit)</div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Stock Value</div>
                        <div class="detail-value">
                            ₱<?= number_format($product['price'] * $product['stock'], 2); ?>
                            <div class="price-label">Total inventory value</div>
                        </div>
                    </div>
                    
                    <?php if ($product['manufacturing_date']): ?>
                    <div class="detail-item">
                        <div class="detail-label">Manufacturing Date</div>
                        <div class="detail-value">
                            <i class="fas fa-calendar-plus"></i>
                            <?= date('M d, Y', strtotime($product['manufacturing_date'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($product['expiration_date']): ?>
                    <div class="detail-item">
                        <div class="detail-label">Expiration Date</div>
                        <div class="detail-value">
                            <i class="fas fa-calendar-times <?= $product['expiration_date'] <= date('Y-m-d') ? 'text-danger' : ''; ?>"></i>
                            <?= date('M d, Y', strtotime($product['expiration_date'])); ?>
                            <?php if ($product['expiration_date'] <= date('Y-m-d')): ?>
                                <span class="label label-danger">EXPIRED</span>
                            <?php elseif ($product['expiration_date'] <= date('Y-m-d', strtotime('+30 days'))): ?>
                                <span class="label label-warning">EXPIRING SOON</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
    
    <!-- Rest of purchase history remains the same -->
    </div>

    <!-- Product Change History -->
    <div class="info-card">
        <h4 class="info-title">
            <i class="fas fa-history"></i> Product Change History
        </h4>
        
        <div id="productHistoryContent">
            <?php
            // Direct PHP query for history
            $history_sql = "SELECT h.*, u.username 
                        FROM product_history h
                        LEFT JOIN users u ON h.changed_by = u.user_id
                        WHERE h.product_id = $product_id
                        ORDER BY h.changed_at DESC";
            $history_result = mysqli_query($con, $history_sql);
            
            if ($history_result && mysqli_num_rows($history_result) > 0):
                $field_labels = [
                    'product_name' => 'Product Name',
                    'category' => 'Category',
                    'price' => 'Selling Price',
                    'cost_price' => 'Cost Price',
                    'stock' => 'Stock',
                    'status' => 'Status',
                    'description' => 'Description',
                    'manufacturing_date' => 'Manufacturing Date',
                    'expiration_date' => 'Expiration Date'
                ];
            ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr style="background:#f5f5f5;">
                                <th><i class="fas fa-calendar-alt"></i> Date Changed</th>
                                <th><i class="fas fa-edit"></i> Field</th>
                                <th><i class="fas fa-arrow-left"></i> Old Value</th>
                                <th><i class="fas fa-arrow-right"></i> New Value</th>
                                <th><i class="fas fa-user"></i> Changed By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($h = mysqli_fetch_assoc($history_result)): 
                                $field_label = $field_labels[$h['field_name']] ?? ucwords(str_replace('_', ' ', $h['field_name']));
                                $old_value = $h['old_value'] ?: 'N/A';
                                $new_value = $h['new_value'] ?: 'N/A';
                                
                                // Format prices
                                if ($h['field_name'] == 'price' || $h['field_name'] == 'cost_price') {
                                    if ($old_value !== 'N/A' && is_numeric($old_value)) {
                                        $old_value = '₱' . number_format((float)$old_value, 2);
                                    }
                                    if ($new_value !== 'N/A' && is_numeric($new_value)) {
                                        $new_value = '₱' . number_format((float)$new_value, 2);
                                    }
                                }
                                
                                // Format dates
                                if (in_array($h['field_name'], ['manufacturing_date', 'expiration_date'])) {
                                    if ($old_value !== 'N/A' && $old_value !== '0000-00-00') {
                                        $old_value = date('M d, Y', strtotime($old_value));
                                    }
                                    if ($new_value !== 'N/A' && $new_value !== '0000-00-00') {
                                        $new_value = date('M d, Y', strtotime($new_value));
                                    }
                                }
                                
                                // Format stock
                                if ($h['field_name'] == 'stock') {
                                    if ($old_value !== 'N/A') $old_value = number_format((int)$old_value) . ' units';
                                    if ($new_value !== 'N/A') $new_value = number_format((int)$new_value) . ' units';
                                }
                            ?>
                                <tr>
                                    <td style="white-space:nowrap;">
                                        <?= date('M d, Y', strtotime($h['changed_at'])); ?><br>
                                        <small class="text-muted"><?= date('h:i A', strtotime($h['changed_at'])); ?></small>
                                    </td>
                                    <td><strong><?= $field_label; ?></strong></td>
                                    <td style="color:#dc3545;"><?= $old_value; ?></td>
                                    <td style="color:#28a745;"><strong><?= $new_value; ?></strong></td>
                                    <td><i class="fas fa-user-circle"></i> <?= htmlspecialchars($h['username'] ?? 'System'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center text-muted" style="padding: 30px;">
                    <i class="fas fa-info-circle fa-3x" style="margin-bottom:15px;"></i>
                    <h4>No Change History</h4>
                    <p>No changes have been recorded for this product yet.</p>
                    <small>History will appear here when you update product details like price, stock, or status.</small>
                </div>
            <?php endif; ?>
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
        var productId = <?= $product_id; ?>;
        
        // Load product history
        loadProductHistory(productId);
        
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

    function loadProductHistory(productId) {
        console.log('Loading history for product ID:', productId);
        
        $.ajax({
            url: 'product_history_ajax.php',
            type: 'GET',
            data: {product_id: productId},
            dataType: 'json',
            beforeSend: function() {
                $('#productHistoryContent').html(
                    '<div class="text-center" style="padding: 20px;">' +
                    '<i class="fas fa-spinner fa-spin"></i> Loading history...' +
                    '</div>'
                );
            },
            success: function(res) {
                console.log('History response:', res);
                
                if (res.status === 'success') {
                    if (res.history && res.history.length > 0) {
                        displayHistory(res.history);
                    } else {
                        $('#productHistoryContent').html(
                            '<div class="text-center text-muted" style="padding: 30px;">' +
                            '<i class="fas fa-info-circle fa-3x" style="margin-bottom:15px;"></i>' +
                            '<h4>No Change History</h4>' +
                            '<p>No changes have been recorded for this product yet.</p>' +
                            '<small>History will appear here when you update product details like price, stock, or status.</small>' +
                            '</div>'
                        );
                    }
                } else {
                    $('#productHistoryContent').html(
                        '<div class="alert alert-danger">' +
                        '<i class="fas fa-exclamation-triangle"></i> ' +
                        (res.message || 'Failed to load product history') +
                        '</div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                
                $('#productHistoryContent').html(
                    '<div class="alert alert-danger">' +
                    '<i class="fas fa-exclamation-triangle"></i> ' +
                    'Failed to load product history. ' +
                    '<button class="btn btn-sm btn-default" onclick="loadProductHistory(' + productId + ')">' +
                    '<i class="fas fa-sync"></i> Retry' +
                    '</button>' +
                    '</div>'
                );
            }
        });
    }

    function displayHistory(historyData) {
        var html = '<div class="table-responsive">';
        html += '<table class="table table-striped table-bordered table-hover">';
        html += '<thead>';
        html += '<tr style="background:#f5f5f5;">';
        html += '<th><i class="fas fa-calendar-alt"></i> Date Changed</th>';
        html += '<th><i class="fas fa-edit"></i> Field</th>';
        html += '<th><i class="fas fa-arrow-left"></i> Old Value</th>';
        html += '<th><i class="fas fa-arrow-right"></i> New Value</th>';
        html += '<th><i class="fas fa-user"></i> Changed By</th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';
        
        var fieldLabels = {
            'product_name': 'Product Name',
            'category': 'Category',
            'price': 'Selling Price',
            'cost_price': 'Cost Price',
            'stock': 'Stock',
            'status': 'Status',
            'description': 'Description',
            'manufacturing_date': 'Manufacturing Date',
            'expiration_date': 'Expiration Date'
        };
        
        historyData.forEach(function(h) {
            var fieldLabel = fieldLabels[h.field_name] || h.field_name.replace('_', ' ');
            var oldValue = h.old_value || 'N/A';
            var newValue = h.new_value || 'N/A';
            
            // Format prices
            if (h.field_name === 'price' || h.field_name === 'cost_price') {
                if (oldValue !== 'N/A' && oldValue !== '') {
                    oldValue = '₱' + parseFloat(oldValue).toLocaleString('en-US', {minimumFractionDigits: 2});
                }
                if (newValue !== 'N/A' && newValue !== '') {
                    newValue = '₱' + parseFloat(newValue).toLocaleString('en-US', {minimumFractionDigits: 2});
                }
            }
            
            // Format dates
            if (h.field_name === 'manufacturing_date' || h.field_name === 'expiration_date') {
                if (oldValue !== 'N/A' && oldValue !== '' && oldValue !== '0000-00-00') {
                    var d = new Date(oldValue);
                    if (!isNaN(d.getTime())) {
                        oldValue = d.toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'});
                    }
                }
                if (newValue !== 'N/A' && newValue !== '' && newValue !== '0000-00-00') {
                    var d = new Date(newValue);
                    if (!isNaN(d.getTime())) {
                        newValue = d.toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'});
                    }
                }
            }
            
            // Format status
            if (h.field_name === 'status') {
                oldValue = '<span class="label label-default">' + oldValue + '</span>';
                newValue = '<span class="label label-primary">' + newValue + '</span>';
            }
            
            // Format stock
            if (h.field_name === 'stock') {
                if (oldValue !== 'N/A') oldValue = parseInt(oldValue).toLocaleString() + ' units';
                if (newValue !== 'N/A') newValue = parseInt(newValue).toLocaleString() + ' units';
            }
            
            var changeDate = new Date(h.changed_at);
            var formattedDate = changeDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short', 
                day: 'numeric'
            });
            var formattedTime = changeDate.toLocaleTimeString('en-US', {
                hour: '2-digit', 
                minute: '2-digit'
            });
            
            html += '<tr>';
            html += '<td style="white-space:nowrap;">' + formattedDate + '<br><small class="text-muted">' + formattedTime + '</small></td>';
            html += '<td><strong>' + fieldLabel + '</strong></td>';
            html += '<td style="color:#dc3545;">' + oldValue + '</td>';
            html += '<td style="color:#28a745;"><strong>' + newValue + '</strong></td>';
            html += '<td><i class="fas fa-user-circle"></i> ' + (h.username || 'System') + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody>';
        html += '</table>';
        html += '</div>';
        
        $('#productHistoryContent').html(html);
    }
</script>