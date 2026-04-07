<?php
include_once('../include/template.php');
include_once('../include/connection.php');

if(!isset($_GET['id'])) {
    echo "Customer ID not provided.";
    exit;
}

$customer_id = intval($_GET['id']);

// Fetch customer info
$customerResult = $con->query("SELECT * FROM customers WHERE customer_id='$customer_id'");
if($customerResult->num_rows == 0) {
    echo "Customer not found.";
    exit;
}
$customer = $customerResult->fetch_assoc();

// Fetch services availed
$servicesResult = $con->query("
    SELECT s.service_name, s.price, cs.created_at 
    FROM services s
    INNER JOIN customer_services cs ON s.service_id = cs.service_id
    WHERE cs.customer_id = '$customer_id'
    ORDER BY cs.created_at DESC
");

// Fetch appointment history
$appointmentsResult = $con->query("
    SELECT a.*, e.first_name as employee_first, e.last_name as employee_last
    FROM appointments a
    LEFT JOIN employees e ON a.employee_id = e.employee_id
    WHERE a.customer_id = '$customer_id'
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 10
");

// Calculate customer statistics
$total_appointments = mysqli_query($con, "SELECT COUNT(*) as total FROM appointments WHERE customer_id='$customer_id'");
$total_appointments = mysqli_fetch_assoc($total_appointments)['total'];

$total_spent = mysqli_query($con, "
    SELECT SUM(s.price) as total 
    FROM customer_services cs 
    JOIN services s ON cs.service_id = s.service_id 
    WHERE cs.customer_id='$customer_id'
");
$total_spent = mysqli_fetch_assoc($total_spent)['total'];

$last_visit = mysqli_query($con, "
    SELECT appointment_date 
    FROM appointments 
    WHERE customer_id='$customer_id' 
    ORDER BY appointment_date DESC 
    LIMIT 1
");
$last_visit = mysqli_fetch_assoc($last_visit);
?>

<style>
    /* Main Container */
    .customer-profile {
        background: #f8f9fa;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
    }
    
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
        content: '\f007';
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
        font-size: 20px;
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
    
    /* Customer Details Grid */
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
    
    /* Services Table */
    .services-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
    }
    
    .services-table th {
        text-align: left;
        padding: 12px 15px;
        background: #f8f9fa;
        color: #464660;
        font-weight: 700;
        font-size: 14px;
        border-radius: 10px 10px 0 0;
    }
    
    .services-table td {
        padding: 15px;
        background: white;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .services-table tr:hover td {
        background: #f8f9fa;
    }
    
    .service-name {
        font-weight: 600;
        color: #464660;
    }
    
    .service-price {
        font-weight: 700;
        color: #28a745;
    }
    
    .service-date {
        font-size: 12px;
        color: #6c757d;
    }
    
    /* Appointments Table */
    .appointments-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
    }
    
    .appointments-table th {
        text-align: left;
        padding: 12px 15px;
        background: #f8f9fa;
        color: #464660;
        font-weight: 700;
        font-size: 14px;
        border-radius: 10px 10px 0 0;
    }
    
    .appointments-table td {
        padding: 15px;
        background: white;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .appointments-table tr:hover td {
        background: #f8f9fa;
    }
    
    .status-badge {
        padding: 5px 10px;
        border-radius: 50px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }
    
    .status-pending { background: #fff3cd; color: #856404; }
    .status-approved { background: #cce5ff; color: #004085; }
    .status-completed { background: #d4edda; color: #155724; }
    .status-cancelled { background: #f8d7da; color: #721c24; }
    
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
                <li><a href="customers.php" style="color: #464660;">Customers</a></li>
                <li class="active">Customer Details</li>
            </ol>
        </div>
    </div>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="row">
            <div class="col-md-12">
                <h1 class="profile-name">
                    <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                </h1>
                <div class="profile-badge">
                    <i class="fas fa-calendar-alt"></i> 
                    Customer since: <?= date('F d, Y', strtotime($customer['created_at'] ?? 'now')); ?>
                </div>
                <div class="profile-badge" style="margin-left: 10px;">
                    <i class="fas fa-id-card"></i> 
                    ID: #CUST-<?= str_pad($customer['customer_id'], 5, '0', STR_PAD_LEFT); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-calendar-check stat-icon"></i>
            <div class="stat-value"><?= $total_appointments; ?></div>
            <div class="stat-label">Total Appointments</div>
        </div>
        
        <div class="stat-card">
            <i class="fas fa-peso-sign stat-icon"></i>
            <div class="stat-value">₱<?= number_format($total_spent ?? 0, 2); ?></div>
            <div class="stat-label">Total Spent</div>
        </div>
        
        <div class="stat-card">
            <i class="fas fa-clock stat-icon"></i>
            <div class="stat-value"><?= $servicesResult->num_rows; ?></div>
            <div class="stat-label">Services Availed</div>
        </div>
        
        <div class="stat-card">
            <i class="fas fa-calendar-day stat-icon"></i>
            <div class="stat-value">
                <?= $last_visit ? date('M d, Y', strtotime($last_visit['appointment_date'])) : 'N/A'; ?>
            </div>
            <div class="stat-label">Last Visit</div>
        </div>
    </div>

    <div class="row">
        <!-- Customer Information -->
        <div class="col-md-6">
            <div class="info-card">
                <h4 class="info-title">
                    <i class="fas fa-user-circle"></i> Personal Information
                </h4>
                
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Full Name</div>
                        <div class="detail-value">
                            <i class="fas fa-user"></i>
                            <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Phone Number</div>
                        <div class="detail-value">
                            <i class="fas fa-phone"></i>
                            <?= htmlspecialchars($customer['phone']); ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Email Address</div>
                        <div class="detail-value">
                            <i class="fas fa-envelope"></i>
                            <?= !empty($customer['email']) ? htmlspecialchars($customer['email']) : '<span class="text-muted">Not provided</span>'; ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Address</div>
                        <div class="detail-value">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= !empty($customer['address']) ? htmlspecialchars($customer['address']) : '<span class="text-muted">Not provided</span>'; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #f0f0f0;">
                    <a href="customers.php" class="btn btn-custom btn-primary-custom">
                        <i class="fas fa-arrow-left"></i> Back to Customers
                    </a>
                    <button class="btn btn-custom btn-success-custom editCustomerBtn" data-id="<?= $customer['customer_id']; ?>">
                        <i class="fas fa-edit"></i> Edit Customer
                    </button>
                    <button class="btn btn-custom btn-info-custom" onclick="scheduleAppointment(<?= $customer['customer_id']; ?>)">
                        <i class="fas fa-calendar-plus"></i> New Appointment
                    </button>
                </div>
            </div>
        </div>

        <!-- Services Availed -->
        <div class="col-md-6">
            <div class="info-card">
                <h4 class="info-title">
                    <i class="fas fa-tags"></i> Services Availed
                    <span class="badge" style="float: right; background: #464660; padding: 5px 10px;">
                        Total: <?= $servicesResult->num_rows; ?>
                    </span>
                </h4>
                
                <?php if($servicesResult->num_rows > 0): ?>
                    <table class="services-table">
                        <thead>
                            <tr>
                                <th>Service Name</th>
                                <th>Price</th>
                                <th>Date Availed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_service_cost = 0;
                            while($service = mysqli_fetch_assoc($servicesResult)): 
                                $total_service_cost += $service['price'];
                            ?>
                                <tr>
                                    <td class="service-name">
                                        <i class="fas fa-check-circle" style="color: #28a745; margin-right: 8px;"></i>
                                        <?= htmlspecialchars($service['service_name']); ?>
                                    </td>
                                    <td class="service-price">₱<?= number_format($service['price'], 2); ?></td>
                                    <td class="service-date">
                                        <?= isset($service['created_at']) ? date('M d, Y', strtotime($service['created_at'])) : 'N/A'; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <tr style="border-top: 2px solid #464660;">
                                <td colspan="2" style="text-align: right; font-weight: 700; color: #464660;">Total:</td>
                                <td style="font-weight: 800; color: #28a745; font-size: 16px;">₱<?= number_format($total_service_cost, 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-tags"></i>
                        <p>No services availed yet.</p>
                        <a href="appointments.php?action=new&customer=<?= $customer['customer_id']; ?>" class="btn btn-sm btn-primary-custom">
                            <i class="fas fa-calendar-plus"></i> Book an Appointment
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Appointment History -->
    <div class="row">
        <div class="col-md-12">
            <div class="info-card">
                <h4 class="info-title">
                    <i class="fas fa-history"></i> Appointment History
                    <span class="badge" style="float: right; background: #464660; padding: 5px 10px;">
                        Last 10 Appointments
                    </span>
                </h4>
                
                <?php if($appointmentsResult->num_rows > 0): ?>
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Employee</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($apt = mysqli_fetch_assoc($appointmentsResult)): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-calendar-alt" style="color: #464660; margin-right: 5px;"></i>
                                        <?= date('M d, Y', strtotime($apt['appointment_date'])); ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-clock" style="color: #464660; margin-right: 5px;"></i>
                                        <?= date('h:i A', strtotime($apt['appointment_time'])); ?>
                                    </td>
                                    <td>
                                        <?php if(!empty($apt['employee_first'])): ?>
                                            <i class="fas fa-user-tie" style="color: #464660; margin-right: 5px;"></i>
                                            <?= htmlspecialchars($apt['employee_first'] . ' ' . $apt['employee_last']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($apt['purpose'] ?: 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $apt['status']; ?>">
                                            <?= ucfirst($apt['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="appointments.php?view=<?= $apt['appointment_id']; ?>" 
                                           class="btn btn-xs btn-info" 
                                           data-toggle="tooltip" 
                                           title="View Appointment">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="appointments.php?customer=<?= $customer['customer_id']; ?>" class="btn btn-sm btn-primary-custom">
                            <i class="fas fa-calendar-alt"></i> View All Appointments
                        </a>
                    </div>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>No appointment history found.</p>
                        <a href="appointments.php?action=new&customer=<?= $customer['customer_id']; ?>" class="btn btn-sm btn-primary-custom">
                            <i class="fas fa-calendar-plus"></i> Schedule First Appointment
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Customer Modal (reuse from customers.php) -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fas fa-edit"></i> Edit Customer
                </h4>
            </div>
            <div class="modal-body" id="editModalContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i> Loading...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Edit customer
    $('.editCustomerBtn').click(function() {
        var customer_id = $(this).data('id');
        $('#editModalContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i> Loading...</div>');
        $('#editCustomerModal').modal('show');
        
        $.ajax({
            url: 'customer_edit_modal.php',
            type: 'GET',
            data: {id: customer_id},
            success: function(response) {
                $('#editModalContent').html(response);
            },
            error: function() {
                $('#editModalContent').html('<p class="text-danger">Failed to load customer data.</p>');
            }
        });
    });
    
    // Handle form submission
    $(document).on('submit', '#editCustomerForm', function(e){
        e.preventDefault();
        
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
        
        $.ajax({
            url: '../Functions/customer_update_ajax.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res){
                if(res.status === 'success'){
                    $('#editCustomerModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Customer updated successfully',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', res.message, 'error');
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(){
                Swal.fire('Error', 'Failed to update customer.', 'error');
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
});

function scheduleAppointment(customerId) {
    window.location.href = 'appointments.php?action=new&customer=' + customerId;
}
</script>

<!-- Add SweetAlert if not already included -->
<script src="../js/sweetalert2.all.min.js"></script>