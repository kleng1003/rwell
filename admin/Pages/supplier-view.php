<?php
include_once('../include/template.php');
include_once('../include/connection.php');

if (!isset($_GET['id'])) {
    header("Location: suppliers.php");
    exit;
}

$supplier_id = intval($_GET['id']);

// Fetch supplier info
$supplierResult = $con->query("SELECT * FROM suppliers WHERE supplier_id = $supplier_id");
if ($supplierResult->num_rows === 0) {
    header("Location: suppliers.php");
    exit;
}
$supplier = $supplierResult->fetch_assoc();

// Fetch products supplied by this supplier
$productsResult = $con->query("
    SELECT product_id, product_name, category, price, stock, status, created_at
    FROM products
    WHERE supplier_id = $supplier_id
    ORDER BY product_name ASC
");

// Calculate statistics
$total_products = $productsResult->num_rows;
$total_stock = 0;
$total_value = 0;
$productsResult->data_seek(0);
while ($product = $productsResult->fetch_assoc()) {
    $total_stock += $product['stock'];
    $total_value += ($product['stock'] * $product['price']);
}
$productsResult->data_seek(0);

// Get purchase history
$purchasesResult = $con->query("
    SELECT p.*, 
           (SELECT COUNT(*) FROM purchase_items WHERE purchase_id = p.purchase_id) as item_count
    FROM purchases p
    WHERE p.supplier_id = $supplier_id
    ORDER BY p.purchase_date DESC
    LIMIT 5
");
?>

<style>
    /* Profile Header */
    .profile-header {
        background: linear-gradient(135deg, #464660 0%, #64648c 100%);
        color: white;
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(70,70,96,0.3);
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
        font-size: 120px;
        opacity: 0.1;
        color: white;
    }
    
    .profile-name {
        font-size: 36px;
        font-weight: 800;
        margin: 0;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
    }
    
    .profile-badge {
        display: inline-block;
        background: rgba(255,255,255,0.2);
        padding: 8px 20px;
        border-radius: 50px;
        font-size: 14px;
        margin-top: 10px;
    }
    
    .profile-badge i {
        margin-right: 8px;
    }
    
    .status-badge {
        display: inline-block;
        padding: 5px 15px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-inactive {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-archived {
        background: #f8d7da;
        color: #721c24;
    }
    
    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        border-left: 4px solid #464660;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .stat-card .stat-icon {
        position: absolute;
        right: 20px;
        top: 20px;
        font-size: 48px;
        opacity: 0.1;
        color: #464660;
    }
    
    .stat-value {
        font-size: 32px;
        font-weight: 800;
        color: #464660;
        line-height: 1.2;
    }
    
    .stat-label {
        font-size: 14px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 5px;
    }
    
    /* Info Cards */
    .info-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }
    
    .info-title {
        font-size: 18px;
        font-weight: 700;
        color: #464660;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .info-title i {
        margin-right: 10px;
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
        border-radius: 10px;
        transition: all 0.3s ease;
    }
    
    .detail-item:hover {
        background: #e9ecef;
    }
    
    .detail-label {
        font-size: 12px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 1px;
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
    
    /* Products Table */
    .products-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
    }
    
    .products-table th {
        text-align: left;
        padding: 12px 15px;
        background: #f8f9fa;
        color: #464660;
        font-weight: 700;
        font-size: 14px;
        border-radius: 10px 10px 0 0;
    }
    
    .products-table td {
        padding: 15px;
        background: white;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .products-table tr:hover td {
        background: #f8f9fa;
    }
    
    .product-name {
        font-weight: 600;
        color: #464660;
    }
    
    .product-category {
        background: #e3f2fd;
        color: #0d47a1;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }
    
    .price {
        font-weight: 700;
        color: #28a745;
    }
    
    .stock {
        font-weight: 600;
    }
    
    .stock-low {
        color: #dc3545;
        font-weight: 700;
    }
    
    .stock-medium {
        color: #ffc107;
        font-weight: 700;
    }
    
    .stock-high {
        color: #28a745;
        font-weight: 700;
    }
    
    /* Action Buttons */
    .action-buttons {
        margin-top: 30px;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .btn-custom {
        padding: 12px 30px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .btn-primary-custom {
        background: #464660;
        color: white;
    }
    
    .btn-primary-custom:hover {
        background: #5a5a7a;
        color: white;
    }
    
    .btn-success-custom {
        background: #28a745;
        color: white;
    }
    
    .btn-success-custom:hover {
        background: #218838;
        color: white;
    }
    
    .btn-info-custom {
        background: #17a2b8;
        color: white;
    }
    
    .btn-info-custom:hover {
        background: #138496;
        color: white;
    }
    
    .btn-warning-custom {
        background: #ffc107;
        color: #191919;
    }
    
    .btn-warning-custom:hover {
        background: #e0a800;
        color: #191919;
    }
    
    /* Modal Styles */
    .modal-header {
        background: #464660;
        color: white;
        border-radius: 15px 15px 0 0;
    }
    
    .modal-header .close {
        color: white;
        opacity: 0.8;
    }
    
    .modal-header .close:hover {
        opacity: 1;
    }
    
    .modal-title {
        font-weight: 700;
    }
    
    .modal-title i {
        margin-right: 8px;
    }
    
    .modal-body {
        padding: 25px;
    }
    
    .modal-footer {
        padding: 20px 25px;
        background: #f8f9fa;
        border-radius: 0 0 15px 15px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        font-weight: 600;
        color: #464660;
        margin-bottom: 8px;
        display: block;
    }
    
    .form-group label i {
        margin-right: 8px;
        color: #64648c;
    }
    
    .form-control {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 10px 15px;
        height: auto;
        transition: all 0.3s;
    }
    
    .form-control:focus {
        border-color: #464660;
        box-shadow: 0 0 0 0.2rem rgba(70,70,96,0.25);
    }
    
    .stock-in-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .stock-in-table th {
        background: #f8f9fa;
        padding: 12px;
        font-weight: 600;
        color: #464660;
    }
    
    .stock-in-table td {
        padding: 10px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .stock-in-table input {
        width: 100%;
        padding: 8px;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }
    
    /* Purchase History */
    .purchase-item {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        border-left: 3px solid #464660;
    }
    
    .purchase-date {
        font-weight: 600;
        color: #464660;
    }
    
    .purchase-amount {
        float: right;
        font-weight: 700;
        color: #28a745;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 40px;
        background: #f8f9fa;
        border-radius: 15px;
    }
    
    .empty-state i {
        font-size: 48px;
        color: #adb5bd;
        margin-bottom: 15px;
    }
    
    .empty-state p {
        color: #6c757d;
        font-size: 16px;
        margin: 0;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .details-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .profile-name {
            font-size: 28px;
        }
    }
</style>

<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="row">
        <div class="col-lg-12">
            <ol class="breadcrumb" style="background: none; padding: 0 0 20px 0;">
                <li><a href="suppliers.php" style="color: #464660;">Suppliers</a></li>
                <li class="active">Supplier Details</li>
            </ol>
        </div>
    </div>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="row">
            <div class="col-md-8">
                <h1 class="profile-name">
                    <i class="fas fa-building"></i> <?= htmlspecialchars($supplier['company_name']); ?>
                </h1>
                <div class="profile-badge">
                    <i class="fas fa-calendar-alt"></i> 
                    Partner since: <?= date('F d, Y', strtotime($supplier['created_at'])); ?>
                </div>
                <div class="profile-badge" style="margin-left: 10px;">
                    <i class="fas fa-id-card"></i> 
                    ID: #SUP-<?= str_pad($supplier['supplier_id'], 4, '0', STR_PAD_LEFT); ?>
                </div>
            </div>
            <div class="col-md-4 text-right">
                <span class="status-badge status-<?= $supplier['status']; ?>" style="background: rgba(255,255,255,0.2); color: white;">
                    <i class="fas fa-<?= $supplier['status'] == 'active' ? 'check-circle' : ($supplier['status'] == 'inactive' ? 'pause-circle' : 'archive'); ?>"></i>
                    <?= ucfirst($supplier['status']); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-boxes stat-icon"></i>
            <div class="stat-value"><?= $total_products; ?></div>
            <div class="stat-label">Total Products</div>
        </div>
        
        <div class="stat-card">
            <i class="fas fa-cubes stat-icon"></i>
            <div class="stat-value"><?= number_format($total_stock); ?></div>
            <div class="stat-label">Total Stock</div>
        </div>
        
        <div class="stat-card">
            <i class="fas fa-peso-sign stat-icon"></i>
            <div class="stat-value">₱<?= number_format($total_value, 2); ?></div>
            <div class="stat-label">Inventory Value</div>
        </div>
        
        <div class="stat-card">
            <i class="fas fa-truck stat-icon"></i>
            <div class="stat-value"><?= $purchasesResult->num_rows; ?></div>
            <div class="stat-label">Recent Purchases</div>
        </div>
    </div>

    <div class="row">
        <!-- Supplier Information -->
        <div class="col-md-6">
            <div class="info-card">
                <h4 class="info-title">
                    <i class="fas fa-info-circle"></i> Contact Information
                </h4>
                
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Contact Person</div>
                        <div class="detail-value">
                            <i class="fas fa-user"></i>
                            <?= htmlspecialchars($supplier['contact_person'] ?: 'N/A'); ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Phone Number</div>
                        <div class="detail-value">
                            <i class="fas fa-phone"></i>
                            <?= htmlspecialchars($supplier['phone']); ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Email Address</div>
                        <div class="detail-value">
                            <i class="fas fa-envelope"></i>
                            <?= !empty($supplier['email']) ? htmlspecialchars($supplier['email']) : '<span class="text-muted">Not provided</span>'; ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Address</div>
                        <div class="detail-value">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= !empty($supplier['address']) ? htmlspecialchars($supplier['address']) : '<span class="text-muted">Not provided</span>'; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #f0f0f0;">
                    <button class="btn btn-custom btn-warning-custom editSupplierBtn" data-id="<?= $supplier['supplier_id']; ?>">
                        <i class="fas fa-edit"></i> Edit Supplier
                    </button>
                    <button class="btn btn-custom btn-success-custom" data-toggle="modal" data-target="#stockInModal">
                        <i class="fas fa-box"></i> Stock In
                    </button>
                    <a href="suppliers.php" class="btn btn-custom btn-primary-custom">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Purchases -->
        <div class="col-md-6">
            <div class="info-card">
                <h4 class="info-title">
                    <i class="fas fa-history"></i> Recent Purchases
                    <span class="badge" style="float: right; background: #464660; padding: 5px 10px;">
                        Last 5 Transactions
                    </span>
                </h4>
                
                <?php if ($purchasesResult->num_rows > 0): ?>
                    <?php while ($purchase = $purchasesResult->fetch_assoc()): ?>
                        <div class="purchase-item">
                            <div>
                                <span class="purchase-date">
                                    <i class="fas fa-calendar-alt"></i> 
                                    <?= date('M d, Y', strtotime($purchase['purchase_date'])); ?>
                                </span>
                                <span class="purchase-amount">
                                    ₱<?= number_format($purchase['total_amount'], 2); ?>
                                </span>
                            </div>
                            <div style="margin-top: 8px; color: #6c757d;">
                                <i class="fas fa-boxes"></i> <?= $purchase['item_count']; ?> items
                                <?php if (!empty($purchase['remarks'])): ?>
                                    <br><small><i class="fas fa-comment"></i> <?= htmlspecialchars($purchase['remarks']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="purchases.php?supplier=<?= $supplier_id; ?>" class="btn btn-sm btn-info-custom">
                            <i class="fas fa-history"></i> View All Purchases
                        </a>
                    </div>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <p>No purchase history found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Products Supplied -->
    <div class="row">
        <div class="col-lg-12">
            <div class="info-card">
                <h4 class="info-title">
                    <i class="fas fa-boxes"></i> Products Supplied
                    <span class="badge" style="float: right; background: #464660; padding: 5px 10px;">
                        Total: <?= $total_products; ?> products
                    </span>
                </h4>

                <?php if ($productsResult->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table id="productsTable" class="products-table">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Date Added</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($product = $productsResult->fetch_assoc()): 
                                    $stock_class = 'stock-high';
                                    if ($product['stock'] < 10) {
                                        $stock_class = 'stock-low';
                                    } elseif ($product['stock'] < 25) {
                                        $stock_class = 'stock-medium';
                                    }
                                ?>
                                    <tr>
                                        <td class="product-name">
                                            <i class="fas fa-box" style="color: #464660; margin-right: 8px;"></i>
                                            <?= htmlspecialchars($product['product_name']); ?>
                                        </td>
                                        <td>
                                            <span class="product-category">
                                                <i class="fas fa-tag"></i> 
                                                <?= htmlspecialchars($product['category'] ?: 'Uncategorized'); ?>
                                            </span>
                                        </td>
                                        <td class="price">₱<?= number_format($product['price'], 2); ?></td>
                                        <td>
                                            <span class="stock <?= $stock_class; ?>">
                                                <?= $product['stock']; ?> units
                                                <?php if ($product['stock'] < 10): ?>
                                                    <i class="fas fa-exclamation-triangle" style="color: #dc3545;" title="Low Stock"></i>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= $product['status']; ?>">
                                                <i class="fas fa-<?= $product['status'] == 'available' ? 'check' : 'times'; ?>"></i>
                                                <?= ucfirst($product['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <i class="fas fa-calendar-alt" style="color: #6c757d;"></i>
                                            <?= date('M d, Y', strtotime($product['created_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <p>No products found for this supplier.</p>
                        <button class="btn btn-sm btn-success-custom" data-toggle="modal" data-target="#stockInModal">
                            <i class="fas fa-plus"></i> Add Products
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Stock In Modal -->
<div class="modal fade" id="stockInModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fas fa-boxes"></i> Stock In Products
                </h4>
            </div>
            <form id="stockInForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="supplier_id" value="<?= $supplier_id ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt"></i> Purchase Date <span class="text-danger">*</span></label>
                                <input type="date" name="purchase_date" class="form-control" required value="<?= date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-tag"></i> Reference Number</label>
                                <input type="text" name="reference_no" class="form-control" placeholder="e.g., INV-2024-001">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-sticky-note"></i> Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered" style="background: white;">
                            <thead>
                                <tr style="background: #464660; color: white;">
                                    <th>Product</th>
                                    <th width="120">Quantity</th>
                                    <th width="150">Cost per Unit (₱)</th>
                                    <th width="150">Subtotal (₱)</th>
                                </tr>
                            </thead>
                            <tbody id="stockItems">
                                <?php
                                // Refresh products query
                                $products = $con->query("SELECT product_id, product_name, price FROM products WHERE supplier_id = $supplier_id AND status='available'");
                                if ($products->num_rows > 0) {
                                    while ($p = $products->fetch_assoc()):
                                ?>
                                    <tr class="stock-row">
                                        <td>
                                            <strong><?= htmlspecialchars($p['product_name']) ?></strong>
                                            <input type="hidden" name="product_id[]" value="<?= $p['product_id'] ?>">
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   name="qty[]" 
                                                   class="form-control qty-input" 
                                                   min="0" 
                                                   value="0" 
                                                   step="1"
                                                   required>
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   name="cost[]" 
                                                   class="form-control cost-input" 
                                                   min="0" 
                                                   value="<?= $p['price']; ?>" 
                                                   step="0.01"
                                                   required>
                                        </td>
                                        <td class="text-right">
                                            <span class="subtotal" style="font-weight: 600; color: #28a745;">₱0.00</span>
                                        </td>
                                    </tr>
                                <?php 
                                    endwhile;
                                } else {
                                    echo '<tr><td colspan="4" class="text-center text-muted">No available products found.</td></tr>';
                                }
                                ?>
                            </tbody>
                            <tfoot style="background: #f8f9fa; font-weight: bold;">
                                <tr>
                                    <td colspan="3" class="text-right">Total Amount:</td>
                                    <td class="text-right"><span id="totalAmount" style="color: #28a745; font-size: 16px;">₱0.00</span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success" id="stockInSubmitBtn">
                        <i class="fas fa-save"></i> Process Stock In
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div class="modal fade" id="editSupplierModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fas fa-edit"></i> Edit Supplier
                </h4>
            </div>
            <div class="modal-body" id="editSupplierContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-3x" style="color: #464660;"></i>
                    <p class="text-muted mt-2">Loading supplier data...</p>
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
<script src="../js/dataTables/dataTables.responsive.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable for products
    $('#productsTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100],
        order: [[0, 'asc']],
        language: {
            emptyTable: "No products found"
        }
    });

    // Calculate subtotals and total
    function calculateTotals() {
        let total = 0;
        $('.stock-row').each(function() {
            const qty = parseFloat($(this).find('.qty-input').val()) || 0;
            const cost = parseFloat($(this).find('.cost-input').val()) || 0;
            const subtotal = qty * cost;
            $(this).find('.subtotal').text('₱' + subtotal.toFixed(2));
            total += subtotal;
        });
        $('#totalAmount').text('₱' + total.toFixed(2));
    }

    // Calculate on input change
    $(document).on('input', '.qty-input, .cost-input', calculateTotals);

    // Validate quantities before submission
    function hasValidQuantities() {
        let hasValid = false;
        $('.qty-input').each(function() {
            if (parseFloat($(this).val()) > 0) {
                hasValid = true;
                return false; // break the loop
            }
        });
        return hasValid;
    }

    // Stock In form submission
    $('#stockInForm').submit(function(e){
        e.preventDefault();
        
        // Check if there are any valid quantities
        if (!hasValidQuantities()) {
            Swal.fire({
                icon: 'warning',
                title: 'No Items',
                text: 'Please enter at least one item with quantity greater than 0.'
            });
            return false;
        }
        
        var submitBtn = $('#stockInSubmitBtn');
        var originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);

        // Show loading alert
        Swal.fire({
            title: 'Processing...',
            text: 'Please wait while we process your stock in.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '../Functions/stock_in.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res){
                Swal.close(); // Close loading alert
                
                if(res.status === 'success'){
                    $('#stockInModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Stock in processed successfully!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: res.message || 'Failed to process stock in'
                    });
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                Swal.close(); // Close loading alert
                console.error('AJAX Error:', error);
                console.error('Response:', xhr.responseText);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to process stock in. Please try again.'
                });
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Reset form when modal is closed
    $('#stockInModal').on('hidden.bs.modal', function () {
        $('#stockInForm')[0].reset();
        $('.qty-input').val('0');
        $('.cost-input').each(function() {
            // Reset to original price if needed
        });
        calculateTotals();
    });

    // Edit Supplier
    $('.editSupplierBtn').click(function(){
        var id = $(this).data('id');
        $('#editSupplierContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x" style="color: #464660;"></i><p class="text-muted mt-2">Loading...</p></div>');
        $('#editSupplierModal').modal('show');
        
        $.ajax({
            url: 'supplier_edit_modal.php',
            type: 'GET',
            data: {id: id},
            success: function(response) {
                $('#editSupplierContent').html(response);
            },
            error: function() {
                $('#editSupplierContent').html('<div class="alert alert-danger">Failed to load supplier data.</div>');
            }
        });
    });

    // Handle edit form submission
    $(document).on('submit', '#editSupplierForm', function(e){
        e.preventDefault();
        
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
        
        $.ajax({
            url: '../Functions/supplier_update_ajax.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res){
                if(res.status === 'success'){
                    $('#editSupplierModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Supplier updated successfully',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
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
});
</script>