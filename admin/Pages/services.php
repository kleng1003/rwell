<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../include/connection.php';
require_once '../include/template.php';

$message = '';
$error = '';
$message_type = '';

// Handle Add Service
if (isset($_POST['add_service'])) {
    $service_name = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $duration = intval($_POST['duration']);
    $status = $_POST['status'];

    if (empty($service_name)) {
        $error = "Service name is required!";
    } elseif ($price <= 0) {
        $error = "Price must be greater than 0!";
    } elseif ($duration <= 0) {
        $error = "Duration must be greater than 0!";
    } else {
        $stmt = $con->prepare("INSERT INTO services (service_name, description, price, duration, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdis", $service_name, $description, $price, $duration, $status);

        if ($stmt->execute()) {
            $message = "Service added successfully!";
            $message_type = "success";
        } else {
            $error = "Database error: " . $stmt->error;
            $message_type = "danger";
        }
    }
}

// Handle Edit Service
if (isset($_POST['edit_service'])) {
    $service_id = intval($_POST['service_id']);
    $service_name = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $duration = intval($_POST['duration']);
    $status = $_POST['status'];

    $stmt = $con->prepare("UPDATE services SET service_name=?, description=?, price=?, duration=?, status=? WHERE service_id=?");
    $stmt->bind_param("ssdisi", $service_name, $description, $price, $duration, $status, $service_id);

    if ($stmt->execute()) {
        $message = "Service updated successfully!";
        $message_type = "success";
    } else {
        $error = "Error updating service: " . $stmt->error;
        $message_type = "danger";
    }
}

// Handle Delete Service
if (isset($_GET['delete'])) {
    $service_id = intval($_GET['delete']);

    // Check appointments
    $check = $con->prepare("SELECT COUNT(*) FROM appointments WHERE service_id=?");
    $check->bind_param("i", $service_id);
    $check->execute();
    $check->bind_result($appointment_count);
    $check->fetch();
    $check->close();

    if ($appointment_count > 0) {
        $error = "Cannot delete this service because it has $appointment_count existing appointment(s)!";
        $message_type = "warning";
    } else {
        $stmt = $con->prepare("DELETE FROM services WHERE service_id=?");
        $stmt->bind_param("i", $service_id);

        if ($stmt->execute()) {
            $message = "Service deleted successfully!";
            $message_type = "success";
        } else {
            $error = "Error deleting service: " . $stmt->error;
            $message_type = "danger";
        }
    }
}

// Handle Toggle Status
if (isset($_GET['toggle_status'])) {
    $service_id = intval($_GET['toggle_status']);
    $con->query("UPDATE services SET status = IF(status = 'active', 'inactive', 'active') WHERE service_id = $service_id");
    $message = "Service status toggled successfully!";
    $message_type = "success";
}

// Fetch all services for DataTables
$result = $con->query("SELECT * FROM services ORDER BY created_at DESC");
$services = $result->fetch_all(MYSQLI_ASSOC);

// Stats
$stats = $con->query("
    SELECT 
    COUNT(*) as total,
    SUM(status='active') as active,
    SUM(status='inactive') as inactive,
    AVG(price) as avg_price,
    MIN(price) as min_price,
    MAX(price) as max_price
    FROM services
")->fetch_assoc();
?>

<style>
    /* DataTables Custom Styling */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 5px 12px;
        margin: 0 2px;
        border-radius: 5px;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white !important;
        border: none;
    }
    .dataTables_wrapper .dataTables_filter input {
        border: 2px solid #e0e0e0;
        border-radius: 5px;
        padding: 6px 12px;
        margin-left: 10px;
    }
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #667eea;
        outline: none;
    }
    .dataTables_length select {
        border: 2px solid #e0e0e0;
        border-radius: 5px;
        padding: 5px;
        margin: 0 5px;
    }
    .table-services {
        margin-top: 20px;
    }
    .table-services thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .table-services thead th {
        border: none;
        padding: 12px;
        font-weight: 600;
    }
    .table-services tbody tr {
        transition: background-color 0.2s;
    }
    .table-services tbody tr:hover {
        background-color: #f8f9fa;
    }
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
    .status-active {
        background: #2ecc71;
        color: white;
    }
    .status-inactive {
        background: #e74c3c;
        color: white;
    }
    .duration-badge {
        background: #3498db;
        color: white;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        display: inline-block;
    }
    .price-tag {
        font-weight: bold;
        color: #2c3e50;
        font-size: 16px;
    }
    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }
    .stat-icon {
        font-size: 40px;
        color: #667eea;
        margin-bottom: 10px;
    }
    .stat-number {
        font-size: 32px;
        font-weight: bold;
        margin: 10px 0;
    }
    .modal-header-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .btn-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
    }
    .btn-custom:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        color: white;
    }
    .action-buttons .btn {
        margin: 0 2px;
        padding: 3px 8px;
    }
    .table-responsive {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .service-description {
        max-width: 300px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .btn-sm {
        padding: 4px 8px;
        font-size: 12px;
    }
</style>

<!-- Page Header -->
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">
            <i class="fas fa-spa"></i> Services Management
            <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#addServiceModal">
                <i class="fas fa-plus"></i> Add New Service
            </button>
        </h1>
    </div>
</div>

<!-- Alert Messages -->
<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade in">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade in">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Services DataTable -->
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fas fa-list"></i> Services List
                <div class="pull-right">
                    <button class="btn btn-success btn-xs" onclick="window.location.reload();">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover" id="servicesTable">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="15%">Service Name</th>
                                <th width="30%">Description</th>
                                <th width="8%">Duration</th>
                                <th width="8%">Price</th>
                                <th width="8%">Status</th>
                                <!-- <th width="10%">Created Date</th> -->
                                <th width="15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service): ?>
                            <tr>
                                <td><?php echo $service['service_id']; ?></td>
                                <td>
                                    <i class="fas fa-spa text-primary"></i> 
                                    <strong><?php echo htmlspecialchars($service['service_name']); ?></strong>
                                </td>
                                <td class="service-description" title="<?php echo htmlspecialchars($service['description']); ?>">
                                    <?php 
                                    $desc = htmlspecialchars($service['description']);
                                    echo !empty($desc) ? (strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc) : '<em class="text-muted">No description</em>';
                                    ?>
                                </td>
                                <td>
                                    <span class="duration-badge">
                                        <i class="far fa-clock"></i> <?php echo $service['duration']; ?> min
                                    </span>
                                </td>
                                <td>
                                    <span class="price-tag">
                                        ₱<?php echo number_format($service['price'], 2); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $service['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo ucfirst($service['status']); ?>
                                    </span>
                                </td>
                                <!-- <td>
                                    <i class="far fa-calendar-alt"></i> 
                                    <?php echo date('M d, Y', strtotime($service['created_at'])); ?>
                                </td> -->
                                <td class="action-buttons">
                                    <a href="javascript:void(0);" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editServiceModal<?php echo $service['service_id']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="?toggle_status=<?php echo $service['service_id']; ?>" class="btn btn-info btn-sm" onclick="return confirm('Toggle service status?')">
                                        <i class="fas fa-power-off"></i>
                                    </a>
                                    <a href="?delete=<?php echo $service['service_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this service? This action cannot be undone!')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            
                            <!-- Edit Modal for each service -->
                            <div class="modal fade" id="editServiceModal<?php echo $service['service_id']; ?>" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header modal-header-custom">
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                            <h4 class="modal-title">
                                                <i class="fas fa-edit"></i> Edit Service
                                            </h4>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>">
                                                
                                                <div class="form-group">
                                                    <label>Service Name *</label>
                                                    <input type="text" name="service_name" class="form-control" value="<?php echo htmlspecialchars($service['service_name']); ?>" required>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Description</label>
                                                    <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($service['description']); ?></textarea>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Duration (minutes) *</label>
                                                            <input type="number" name="duration" class="form-control" value="<?php echo $service['duration']; ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Price (₱) *</label>
                                                            <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $service['price']; ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Status</label>
                                                    <select name="status" class="form-control">
                                                        <option value="active" <?php echo $service['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                        <option value="inactive" <?php echo $service['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                                <button type="submit" name="edit_service" class="btn btn-primary">Update Service</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fas fa-plus"></i> Add New Service
                </h4>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Service Name *</label>
                        <input type="text" name="service_name" class="form-control" placeholder="e.g., Haircut & Style" required>
                        <small class="text-muted">Enter the name of the service offered</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Describe the service, what's included, benefits, etc."></textarea>
                        <small class="text-muted">Detailed description helps customers understand the service</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Duration (minutes) *</label>
                                <input type="number" name="duration" class="form-control" placeholder="e.g., 30, 45, 60" required>
                                <small class="text-muted">How long the service takes</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Price (₱) *</label>
                                <input type="number" step="0.01" name="price" class="form-control" placeholder="e.g., 500.00" required>
                                <small class="text-muted">Selling price in Philippine Peso</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active">Active - Available for booking</option>
                            <option value="inactive">Inactive - Temporarily unavailable</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-info" style="margin-top: 15px;">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Note:</strong> Active services will be visible to customers when booking appointments.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_service" class="btn btn-primary">Add Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="../js/jquery.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/metisMenu.min.js"></script>
<script src="../js/dataTables/jquery.dataTables.min.js"></script>
<script src="../js/dataTables/dataTables.bootstrap.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#servicesTable').DataTable({
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "order": [[0, "desc"]], // Sort by ID descending
        "language": {
            "search": "<i class='fas fa-search'></i> Search:",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ services",
            "infoEmpty": "Showing 0 to 0 of 0 services",
            "infoFiltered": "(filtered from _MAX_ total services)",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        },
        "columnDefs": [
            { "orderable": true, "targets": [0, 1, 3, 4, 5, 6] },
            { "orderable": false, "targets": [2, 7] }, // Description and Actions not orderable
            { "searchable": true, "targets": [0, 1, 2] }, // ID, Name, Description searchable
            { "searchable": false, "targets": [3, 4, 5, 6, 7] } // Others not searchable by default
        ]
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $(".alert").fadeOut("slow");
    }, 5000);
    
    // Form validation before submit
    $("form").on("submit", function(e) {
        var price = $(this).find("input[name='price']").val();
        var duration = $(this).find("input[name='duration']").val();
        
        if (price && parseFloat(price) <= 0) {
            alert("Price must be greater than 0!");
            e.preventDefault();
            return false;
        }
        
        if (duration && parseInt(duration) <= 0) {
            alert("Duration must be greater than 0 minutes!");
            e.preventDefault();
            return false;
        }
    });
    
    // Add tooltip to description cells
    $('.service-description').each(function() {
        var title = $(this).attr('title');
        if (title && title !== '') {
            $(this).css('cursor', 'pointer');
        }
    });
});
</script>
