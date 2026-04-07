<?php
include_once('../include/connection.php');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<p class="text-danger">Invalid supplier ID.</p>';
    exit();
}

$supplier_id = mysqli_real_escape_string($con, $_GET['id']);

// Fetch supplier data
$query = mysqli_query($con, "SELECT * FROM suppliers WHERE supplier_id = '$supplier_id'");
if (mysqli_num_rows($query) == 0) {
    echo '<p class="text-danger">Supplier not found.</p>';
    exit();
}

$supplier = mysqli_fetch_assoc($query);
?>

<input type="hidden" name="supplier_id" value="<?= $supplier['supplier_id']; ?>">

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label><i class="fas fa-building"></i> Company Name <span class="text-danger">*</span></label>
            <input type="text" name="company_name" class="form-control" 
                   value="<?= htmlspecialchars($supplier['company_name']); ?>" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label><i class="fas fa-user"></i> Contact Person <span class="text-danger">*</span></label>
            <input type="text" name="contact_person" class="form-control" 
                   value="<?= htmlspecialchars($supplier['contact_person']); ?>" required>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label><i class="fas fa-phone"></i> Phone Number <span class="text-danger">*</span></label>
            <input type="text" name="phone" class="form-control" 
                   value="<?= htmlspecialchars($supplier['phone']); ?>" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label><i class="fas fa-envelope"></i> Email Address</label>
            <input type="email" name="email" class="form-control" 
                   value="<?= htmlspecialchars($supplier['email']); ?>">
        </div>
    </div>
</div>

<div class="form-group">
    <label><i class="fas fa-map-marker-alt"></i> Address</label>
    <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($supplier['address']); ?></textarea>
</div>

<div class="form-group">
    <label><i class="fas fa-toggle-on"></i> Status</label>
    <select name="status" class="form-control">
        <option value="active" <?= $supplier['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
        <option value="inactive" <?= $supplier['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
        <option value="archived" <?= $supplier['status'] == 'archived' ? 'selected' : ''; ?>>Archived</option>
    </select>
</div>