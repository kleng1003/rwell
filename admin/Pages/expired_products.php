<?php
// Pages/expired-products.php
include_once('../include/template.php');
include_once('../include/connection.php');

// Check for auto-expiration on page load
$auto_expire = "UPDATE products 
                SET status = 'expired' 
                WHERE expiration_date IS NOT NULL 
                AND expiration_date <= CURDATE() 
                AND status NOT IN ('expired', 'unavailable')";
mysqli_query($con, $auto_expire);

// Fetch expired products
$sql = "SELECT p.*, s.company_name AS supplier_name,
        DATEDIFF(CURDATE(), p.expiration_date) AS days_expired
        FROM products p
        LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
        WHERE p.status = 'expired'
        ORDER BY p.expiration_date DESC";
$result = $con->query($sql);

// Statistics
$total_expired = mysqli_query($con, "SELECT COUNT(*) as total FROM products WHERE status = 'expired'");
$total_expired = mysqli_fetch_assoc($total_expired)['total'];

$total_value = mysqli_query($con, "SELECT SUM(stock * price) as total FROM products WHERE status = 'expired'");
$total_value = mysqli_fetch_assoc($total_value)['total'];

$oldest_expired = mysqli_query($con, "SELECT DATEDIFF(CURDATE(), MIN(expiration_date)) as days FROM products WHERE status = 'expired' AND expiration_date IS NOT NULL");
$oldest_expired = mysqli_fetch_assoc($oldest_expired)['days'] ?? 0;

$expiring_soon = mysqli_query($con, "SELECT COUNT(*) as total FROM products WHERE expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status != 'expired'");
$expiring_soon = mysqli_fetch_assoc($expiring_soon)['total'];
?>

<style>
    .expired-header {
        background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%);
        color: white;
        padding: 25px 30px;
        border-radius: 8px;
        margin-bottom: 25px;
    }
    
    .expired-title {
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }
    
    .expired-count {
        font-size: 48px;
        font-weight: 700;
    }
    
    .days-expired-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .days-critical {
        background: #f8d7da;
        color: #721c24;
    }
    
    .days-warning {
        background: #fff3cd;
        color: #856404;
    }
    
    .stock-value-expired {
        color: #dc3545;
        font-weight: 600;
    }
    
    .summary-card-expired {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        border-left: 4px solid #dc3545;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .summary-number-expired {
        font-size: 28px;
        font-weight: 700;
        color: #dc3545;
    }
    
    .summary-label-expired {
        font-size: 14px;
        color: #6c757d;
        margin-top: 5px;
    }
    
    .action-btn {
        margin: 0 2px;
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <div class="expired-header">
            <div class="row">
                <div class="col-md-8">
                    <h1 class="expired-title">
                        <i class="fas fa-skull-crossbones"></i> Expired Products
                    </h1>
                    <p style="margin:10px 0 0 0; opacity:0.9;">
                        Products past their expiration date
                    </p>
                </div>
                <div class="col-md-4 text-right">
                    <div class="expired-count"><?= $total_expired; ?></div>
                    <small>EXPIRED PRODUCTS</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="summary-card-expired">
            <div class="summary-number-expired"><?= $total_expired; ?></div>
            <div class="summary-label-expired">Total Expired</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="summary-card-expired">
            <div class="summary-number-expired">₱<?= number_format($total_value, 2); ?></div>
            <div class="summary-label-expired">Lost Value</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="summary-card-expired">
            <div class="summary-number-expired"><?= $oldest_expired; ?></div>
            <div class="summary-label-expired">Oldest (Days)</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="summary-card-expired" style="border-left-color: #ffc107;">
            <div class="summary-number-expired" style="color: #ffc107;"><?= $expiring_soon; ?></div>
            <div class="summary-label-expired">Expiring Within 30 Days</div>
        </div>
    </div>
</div>

<!-- Expiring Soon Alert -->
<?php if ($expiring_soon > 0): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i>
    <strong>Attention:</strong> There are <strong><?= $expiring_soon; ?> product(s)</strong> that will expire within the next 30 days. 
    <a href="products.php?filter=expiring" class="alert-link">View them here</a>.
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <strong><i class="fas fa-list"></i> Expired Products List</strong>
                    </div>
                    <div class="col-md-6 text-right">
                        <a href="products.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-boxes"></i> All Products
                        </a>
                        <button class="btn btn-warning btn-sm" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Supplier</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Expiration Date</th>
                                <th>Days Expired</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): 
                                    $days_class = $row['days_expired'] > 90 ? 'days-critical' : 'days-warning';
                                ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-box text-danger"></i>
                                            <?= htmlspecialchars($row['product_name']); ?>
                                        </td>
                                        <td>
                                            <span class="category-badge">
                                                <?= htmlspecialchars($row['category'] ?: 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($row['supplier_name']): ?>
                                                <a href="supplier-view.php?id=<?= $row['supplier_id']; ?>">
                                                    <?= htmlspecialchars($row['supplier_name']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>₱<?= number_format($row['price'], 2); ?></td>
                                        <td>
                                            <span class="stock-value-expired">
                                                <?= (int)$row['stock']; ?> units
                                            </span>
                                        </td>
                                        <td>
                                            <i class="fas fa-calendar-times text-danger"></i>
                                            <?= date('M d, Y', strtotime($row['expiration_date'])); ?>
                                        </td>
                                        <td>
                                            <span class="days-expired-badge <?= $days_class; ?>">
                                                <i class="fas fa-clock"></i>
                                                <?= $row['days_expired']; ?> days
                                            </span>
                                        </td>
                                        <td>
                                            <a href="product-view.php?id=<?= $row['product_id']; ?>" 
                                               class="btn btn-info btn-sm action-btn">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <button class="btn btn-success btn-sm action-btn restoreProductBtn" 
                                                    data-id="<?= $row['product_id']; ?>"
                                                    data-name="<?= htmlspecialchars($row['product_name']); ?>">
                                                <i class="fas fa-undo"></i> Restore
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">
                                        <i class="fas fa-check-circle text-success fa-2x"></i>
                                        <p style="margin-top:10px;">No expired products found. Good job!</p>
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

<script src="../js/jquery.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // Restore product from expired
    $(document).on('click', '.restoreProductBtn', function() {
        var product_id = $(this).data('id');
        var product_name = $(this).data('name');
        
        Swal.fire({
            title: 'Restore Product?',
            html: `Are you sure you want to restore <strong>${product_name}</strong>?<br>
                  <small class="text-muted">This will change the status back to Available.</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, restore it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../Functions/product_restore_ajax.php',
                    type: 'POST',
                    data: {product_id: product_id},
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Restored!',
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
                        Swal.fire('Error', 'Failed to restore product', 'error');
                    }
                });
            }
        });
    });
});
</script>