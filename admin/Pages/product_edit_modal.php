<?php
include_once('../include/connection.php');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<p class="text-danger">Invalid product ID.</p>';
    exit();
}

$product_id = mysqli_real_escape_string($con, $_GET['id']);

// Fetch product data
$query = mysqli_query($con, "
    SELECT p.*, s.company_name AS supplier_name 
    FROM products p
    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
    WHERE p.product_id = '$product_id'
");
if (mysqli_num_rows($query) == 0) {
    echo '<p class="text-danger">Product not found.</p>';
    exit();
}

$product = mysqli_fetch_assoc($query);
?>

<input type="hidden" name="product_id" value="<?= $product['product_id']; ?>">

<div class="row">
    <div class="col-md-8">
        <div class="form-group">
            <label><i class="fas fa-box"></i> Product Name <span class="text-danger">*</span></label>
            <input type="text" name="product_name" class="form-control" 
                   value="<?= htmlspecialchars($product['product_name']); ?>" required>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label><i class="fas fa-tag"></i> Category <span class="text-danger">*</span></label>
            <input type="text" name="category" class="form-control" 
                   value="<?= htmlspecialchars($product['category']); ?>" required>
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
                    $selected = ($s['supplier_id'] == $product['supplier_id']) ? 'selected' : '';
                    echo "<option value='{$s['supplier_id']}' $selected>{$s['company_name']}</option>";
                }
                ?>
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label><i class="fas fa-dollar-sign"></i> Price <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="price" class="form-control" 
                   value="<?= $product['price']; ?>" required min="0">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label><i class="fas fa-cubes"></i> Stock</label>
            <input type="number" name="stock" class="form-control" 
                   value="<?= $product['stock']; ?>" min="0">
            <small class="text-muted">Current stock level</small>
        </div>
    </div>
</div>

<div class="form-group">
    <label><i class="fas fa-info-circle"></i> Description</label>
    <textarea name="description" class="form-control" rows="3" 
              placeholder="Product description"><?= htmlspecialchars($product['description']); ?></textarea>
</div>

<div class="form-group">
    <label><i class="fas fa-toggle-on"></i> Status</label>
    <select name="status" class="form-control">
        <option value="available" <?= $product['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
        <option value="unavailable" <?= $product['status'] == 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
    </select>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    <strong>Note:</strong> To add stock, use the "Stock In" feature in the supplier view.
</div>