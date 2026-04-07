<?php
// customer_edit_modal.php
include_once('../include/connection.php');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<p class="text-danger">Invalid customer ID.</p>';
    exit();
}

$customer_id = mysqli_real_escape_string($con, $_GET['id']);

// Fetch customer data
$query = mysqli_query($con, "SELECT * FROM customers WHERE customer_id = '$customer_id'");
if (mysqli_num_rows($query) == 0) {
    echo '<p class="text-danger">Customer not found.</p>';
    exit();
}

$customer = mysqli_fetch_assoc($query);

// Fetch all active services
$servicesResult = mysqli_query($con, "SELECT * FROM services WHERE status='active' ORDER BY service_name ASC");

// Fetch services already availed by this customer
$availedQuery = mysqli_query($con, "SELECT service_id FROM customer_services WHERE customer_id = '$customer_id'");
$availedServices = [];
while ($row = mysqli_fetch_assoc($availedQuery)) {
    $availedServices[] = $row['service_id'];
}
?>

<form method="POST" id="editCustomerForm">
    <input type="hidden" name="customer_id" value="<?= $customer['customer_id']; ?>">
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label><i class="fas fa-user"></i> First Name <span class="text-danger">*</span></label>
                <input type="text" name="first_name" class="form-control" required 
                       value="<?= htmlspecialchars($customer['first_name']); ?>">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Last Name <span class="text-danger">*</span></label>
                <input type="text" name="last_name" class="form-control" required 
                       value="<?= htmlspecialchars($customer['last_name']); ?>">
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label><i class="fas fa-phone"></i> Phone Number <span class="text-danger">*</span></label>
                <input type="text" name="phone" class="form-control" required 
                       value="<?= htmlspecialchars($customer['phone']); ?>">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" name="email" class="form-control" 
                       value="<?= htmlspecialchars($customer['email']); ?>">
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <label><i class="fas fa-map-marker-alt"></i> Address</label>
        <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($customer['address']); ?></textarea>
    </div>
    
    <div class="form-group">
        <label><i class="fas fa-tags"></i> Services Availed</label>
        <div class="row">
            <?php if (mysqli_num_rows($servicesResult) > 0): ?>
                <?php while ($service = mysqli_fetch_assoc($servicesResult)): ?>
                    <div class="col-md-4">
                        <div class="service-checkbox">
                            <label class="form-check-label">
                                <input type="checkbox" name="services[]" value="<?= $service['service_id']; ?>"
                                    <?= in_array($service['service_id'], $availedServices) ? 'checked' : ''; ?>>
                                <?= htmlspecialchars($service['service_name']); ?>
                                <span class="service-price">₱<?= number_format($service['price'],2); ?></span>
                            </label>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-md-12">
                    <p class="text-muted">No services available.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal Footer with Save Button (inside the form) -->
    <div class="modal-footer" style="padding: 20px 0 0 0; border-top: 1px solid #e9ecef; margin-top: 20px;">
        <button type="submit" class="btn btn-save">
            <i class="fas fa-save"></i> Update Customer
        </button>
        <button type="button" class="btn btn-cancel" data-dismiss="modal">
            <i class="fas fa-times"></i> Cancel
        </button>
    </div>
</form>

<style>
/* Add these styles to match the add modal */
.service-checkbox {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 10px;
    border-left: 3px solid #464660;
}

.service-checkbox:hover {
    background: #e9ecef;
}

.service-price {
    float: right;
    color: #28a745;
    font-weight: 600;
}

.btn-save {
    background: #28a745;
    color: white;
    border: none;
    padding: 10px 25px;
    font-weight: 600;
    border-radius: 5px;
    margin-right: 10px;
}

.btn-save:hover {
    background: #218838;
}

.btn-cancel {
    background: #6c757d;
    color: white;
    border: none;
    padding: 10px 25px;
    font-weight: 600;
    border-radius: 5px;
}

.btn-cancel:hover {
    background: #5a6268;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
    display: block;
}

.form-group label i {
    margin-right: 5px;
    color: #464660;
}

.form-control {
    border-radius: 5px;
    border: 1px solid #ced4da;
    padding: 10px 15px;
    height: auto;
}

.form-control:focus {
    border-color: #464660;
    box-shadow: 0 0 0 0.2rem rgba(70,70,96,0.25);
}
</style>