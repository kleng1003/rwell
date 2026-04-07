<?php
include_once('../include/template.php');
include_once('../include/connection.php');

// Check if user is logged in
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: appointments.php");
    exit();
}

$appointment_id = intval($_GET['id']);

// Fetch appointment details
$query = "SELECT a.*, 
                 CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
                 c.phone AS customer_phone,
                 c.email AS customer_email,
                 c.address AS customer_address,
                 CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                 e.position AS employee_position,
                 e.phone AS employee_phone
          FROM appointments a
          LEFT JOIN customers c ON a.customer_id = c.customer_id
          LEFT JOIN employees e ON a.employee_id = e.employee_id
          WHERE a.appointment_id = '$appointment_id'";
$result = mysqli_query($con, $query);

if (mysqli_num_rows($result) == 0) {
    header("Location: appointments.php");
    exit();
}

$appointment = mysqli_fetch_assoc($result);

// Get services for this appointment (if any)
$services_query = mysqli_query($con, "
    SELECT s.service_name, s.price, s.duration 
    FROM customer_services cs
    JOIN services s ON cs.service_id = s.service_id
    WHERE cs.customer_id = '{$appointment['customer_id']}'
    AND cs.appointment_id IS NULL
");
?>

<style>
    .profile-header {
        background: linear-gradient(135deg, #464660 0%, #64648c 100%);
        color: white;
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }
    
    .profile-header::before {
        content: '\f073';
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
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        border-left: 4px solid #464660;
        text-align: center;
    }
    
    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #464660;
    }
    
    .stat-label {
        font-size: 13px;
        color: #6c757d;
        margin-top: 5px;
    }
    
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
    }
    
    .details-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .detail-item {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 6px;
    }
    
    .detail-label {
        font-size: 12px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }
    
    .detail-value {
        font-size: 15px;
        font-weight: 600;
        color: #464660;
        word-break: break-word;
    }
    
    .detail-value i {
        margin-right: 8px;
        color: #64648c;
        width: 20px;
    }
    
    .status-badge {
        display: inline-block;
        padding: 6px 15px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
    }
    
    .status-pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-approved {
        background: #cce5ff;
        color: #004085;
    }
    
    .status-completed {
        background: #d4edda;
        color: #155724;
    }
    
    .status-cancelled {
        background: #f8d7da;
        color: #721c24;
    }
    
    .action-buttons {
        margin-top: 30px;
        display: flex;
        gap: 15px;
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
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        text-decoration: none;
    }
    
    .btn-primary-action {
        background: #464660;
        color: white;
    }
    
    .btn-primary-action:hover {
        background: #5a5a7a;
        color: white;
    }
    
    .btn-secondary-action {
        background: #6c757d;
        color: white;
    }
    
    .btn-secondary-action:hover {
        background: #5a6268;
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
    
    .btn-danger-action {
        background: #dc3545;
        color: white;
    }
    
    .btn-danger-action:hover {
        background: #c82333;
        color: white;
    }
    
    .service-item {
        background: #f8f9fa;
        padding: 12px;
        border-radius: 6px;
        margin-bottom: 10px;
        border-left: 3px solid #28a745;
    }
    
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

<div class="row">
    <div class="col-lg-12">
        <ol class="breadcrumb" style="background: none; padding: 0 0 15px 0;">
            <li><a href="appointments.php" style="color: #464660;">Appointments</a></li>
            <li class="active">Appointment Details</li>
        </ol>
    </div>
</div>

<!-- Profile Header -->
<div class="profile-header">
    <div class="row">
        <div class="col-md-8">
            <h1 class="profile-name">
                <i class="fas fa-calendar-check"></i> Appointment Details
            </h1>
            <div>
                <span class="profile-badge">
                    <i class="fas fa-hashtag"></i> ID: #APT-<?= str_pad($appointment['appointment_id'], 4, '0', STR_PAD_LEFT); ?>
                </span>
                <span class="profile-badge">
                    <i class="fas fa-calendar-alt"></i> <?= date('F d, Y', strtotime($appointment['appointment_date'])); ?>
                </span>
                <span class="profile-badge">
                    <i class="fas fa-clock"></i> <?= date('h:i A', strtotime($appointment['appointment_time'])); ?>
                </span>
            </div>
        </div>
        <div class="col-md-4 text-right">
            <span class="status-badge status-<?= $appointment['status']; ?>">
                <i class="fas fa-<?= $appointment['status'] == 'pending' ? 'clock' : ($appointment['status'] == 'approved' ? 'check-circle' : ($appointment['status'] == 'completed' ? 'check-double' : 'times-circle')); ?>"></i>
                <?= ucfirst($appointment['status']); ?>
            </span>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?= date('M d, Y', strtotime($appointment['appointment_date'])); ?></div>
        <div class="stat-label">Appointment Date</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= date('h:i A', strtotime($appointment['appointment_time'])); ?></div>
        <div class="stat-label">Appointment Time</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= date('M d, Y', strtotime($appointment['created_at'])); ?></div>
        <div class="stat-label">Created On</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $appointment['employee_name'] ?: 'Not Assigned'; ?></div>
        <div class="stat-label">Assigned Staff</div>
    </div>
</div>

<div class="row">
    <!-- Customer Information -->
    <div class="col-md-6">
        <div class="info-card">
            <h4 class="info-title">
                <i class="fas fa-user-circle"></i> Customer Information
            </h4>
            
            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Customer Name</div>
                    <div class="detail-value">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($appointment['customer_name']); ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Phone Number</div>
                    <div class="detail-value">
                        <i class="fas fa-phone"></i> <?= htmlspecialchars($appointment['customer_phone']); ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Email Address</div>
                    <div class="detail-value">
                        <i class="fas fa-envelope"></i> <?= htmlspecialchars($appointment['customer_email'] ?: 'Not provided'); ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Address</div>
                    <div class="detail-value">
                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($appointment['customer_address'] ?: 'Not provided'); ?>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 15px;">
                <a href="customer-view.php?id=<?= $appointment['customer_id']; ?>" class="btn btn-sm btn-info">
                    <i class="fas fa-eye"></i> View Customer Profile
                </a>
            </div>
        </div>
    </div>
    
    <!-- Employee Information -->
    <div class="col-md-6">
        <div class="info-card">
            <h4 class="info-title">
                <i class="fas fa-user-tie"></i> Employee / Staff Information
            </h4>
            
            <?php if ($appointment['employee_name']): ?>
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Staff Name</div>
                        <div class="detail-value">
                            <i class="fas fa-user-tie"></i> <?= htmlspecialchars($appointment['employee_name']); ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Position</div>
                        <div class="detail-value">
                            <i class="fas fa-briefcase"></i> <?= htmlspecialchars($appointment['employee_position']); ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Contact Number</div>
                        <div class="detail-value">
                            <i class="fas fa-phone"></i> <?= htmlspecialchars($appointment['employee_phone']); ?>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 15px;">
                    <a href="employee-view.php?id=<?= $appointment['employee_id']; ?>" class="btn btn-sm btn-info">
                        <i class="fas fa-eye"></i> View Staff Profile
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> No staff member assigned to this appointment.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Appointment Details -->
<div class="row">
    <div class="col-lg-12">
        <div class="info-card">
            <h4 class="info-title">
                <i class="fas fa-info-circle"></i> Appointment Details
            </h4>
            
            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Purpose / Services</div>
                    <div class="detail-value">
                        <?php if (!empty($appointment['purpose'])): ?>
                            <?= nl2br(htmlspecialchars($appointment['purpose'])); ?>
                        <?php else: ?>
                            <span class="text-muted">No purpose specified</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <span class="status-badge status-<?= $appointment['status']; ?>">
                            <?= ucfirst($appointment['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Date Created</div>
                    <div class="detail-value">
                        <i class="fas fa-calendar-plus"></i> <?= date('F d, Y h:i A', strtotime($appointment['created_at'])); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Services Section (if any) -->
<?php if (mysqli_num_rows($services_query) > 0): ?>
<div class="row">
    <div class="col-lg-12">
        <div class="info-card">
            <h4 class="info-title">
                <i class="fas fa-tags"></i> Services Requested
            </h4>
            
            <div class="row">
                <?php while($service = mysqli_fetch_assoc($services_query)): ?>
                    <div class="col-md-4">
                        <div class="service-item">
                            <strong><?= htmlspecialchars($service['service_name']); ?></strong>
                            <div class="text-muted small">
                                <i class="fas fa-tag"></i> ₱<?= number_format($service['price'], 2); ?>
                                <?php if (!empty($service['duration'])): ?>
                                    <br><i class="fas fa-clock"></i> <?= $service['duration']; ?> minutes
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="appointments.php" class="btn-action btn-secondary-action">
        <i class="fas fa-arrow-left"></i> Back to List
    </a>
    
    <?php if ($appointment['status'] != 'cancelled' && $appointment['status'] != 'completed'): ?>
        <!-- <a href="../Functions/appointment_update.php?id=<?= $appointment['appointment_id']; ?>" 
           class="btn-action btn-primary-action">
            <i class="fas fa-edit"></i> Edit Appointment
        </a> -->
        
        <button class="btn-action btn-success-action" id="completeBtn" 
                data-id="<?= $appointment['appointment_id']; ?>">
            <i class="fas fa-check-circle"></i> Mark as Completed
        </button>
        
        <button class="btn-action btn-danger-action" id="cancelBtn" 
                data-id="<?= $appointment['appointment_id']; ?>"
                data-customer="<?= htmlspecialchars($appointment['customer_name']); ?>"
                data-date="<?= $appointment['appointment_date']; ?>">
            <i class="fas fa-times-circle"></i> Cancel Appointment
        </button>
    <?php endif; ?>
</div>

<script src="../js/jquery.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // Complete Appointment
    $('#completeBtn').click(function() {
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Complete Appointment?',
            text: 'Mark this appointment as completed?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, mark as completed'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../Functions/appointment_complete.php',
                    type: 'POST',
                    data: {id: id},
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Completed!',
                                text: 'Appointment marked as completed',
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
                        Swal.fire('Error', 'Failed to complete appointment', 'error');
                    }
                });
            }
        });
    });
    
    // Cancel Appointment
    $('#cancelBtn').click(function() {
        var id = $(this).data('id');
        var customer = $(this).data('customer');
        var date = $(this).data('date');
        
        Swal.fire({
            title: 'Cancel Appointment?',
            text: `Cancel appointment for ${customer} on ${date}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, cancel it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../Functions/appointment_cancel_ajax.php',
                    type: 'POST',
                    data: {id: id},
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Cancelled!',
                                text: 'Appointment has been cancelled',
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
                        Swal.fire('Error', 'Failed to cancel appointment', 'error');
                    }
                });
            }
        });
    });
});
</script>