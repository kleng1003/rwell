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

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';

$where = "WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $where .= " AND (service_name LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

if (!empty($status_filter) && $status_filter != 'all') {
    $where .= " AND status=?";
    $params[] = $status_filter;
    $types .= "s";
}

// Count
$stmt = $con->prepare("SELECT COUNT(*) FROM services $where");
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($total_services);
$stmt->fetch();
$stmt->close();

$total_pages = ceil($total_services / $limit);

// Fetch services
$query = "SELECT * FROM services $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $con->prepare($query);

if (!empty($params)) {
    $params[] = $limit;
    $params[] = $offset;
    $stmt->bind_param($types . "ii", ...$params);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
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
    .card-body {
        flex: 1;
    }
    .row {
        display: flex;
        flex-wrap: wrap;
    }
    .service-card {
        transition: transform 0.3s, box-shadow 0.3s;
        border: none;
        border-radius: 12px;
        box-shadow: 1px 2px 10px 1px rgba(0, 0, 0, 0.35);
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .service-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
    .cardi{
        margin-bottom: 20px;
    }
    .service-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px;
    }
    .price-tag {
        font-size: 28px;
        font-weight: bold;
        color: #2c3e50;
    }
    .price-tag small {
        font-size: 14px;
        font-weight: normal;
        color: #7f8c8d;
    }
    .duration-badge {
        background: #3498db;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 13px;
    }
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-active {
        background: #2ecc71;
        color: white;
    }
    .status-inactive {
        background: #e74c3c;
        color: white;
    }
    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: transform 0.2s;
    }
    .created{
        padding: 10px;
    }
    .stat-card:hover {
        transform: translateY(-3px);
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
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }
    .action-buttons .btn {
        margin: 0 2px;
    }
    .search-box {
        position: relative;
    }
    .search-box i {
        position: absolute;
        left: 12px;
        top: 12px;
        color: #aaa;
    }
    .search-box input {
        padding-left: 35px;
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
        
        <!-- Search and Filter Bar -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <form method="GET" action="" class="form-inline">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="search-box">
                                        <i class="fas fa-search"></i>
                                        <input type="text" name="search" class="form-control" placeholder="Search by name or description..." value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select name="status_filter" class="form-control">
                                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                </div>
                                <div class="col-md-3 text-right">
                                    <a href="services.php" class="btn btn-default">
                                        <i class="fas fa-sync-alt"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong><i class="fas fa-list"></i> Services List</strong>
            </div>

            <div class="panel-body">
                <div class="table-responsive">
                    <table id="servicesTable" class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Description</th>
                                <th>Duration</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th style="width:150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-spa"></i>
                                        <strong><?= htmlspecialchars($service['service_name']); ?></strong>
                                    </td>

                                    <td>
                                        <?php
                                            $desc = htmlspecialchars($service['description']);
                                            echo !empty($desc)
                                                ? (strlen($desc) > 60 ? substr($desc, 0, 60) . '...' : $desc)
                                                : '—';
                                        ?>
                                    </td>

                                    <td>
                                        <span class="duration-badge">
                                            <?= $service['duration']; ?> mins
                                        </span>
                                    </td>

                                    <td>
                                        ₱<?= number_format($service['price'], 2); ?>
                                    </td>

                                    <td>
                                        <span class="status-badge <?= $service['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                            <?= ucfirst($service['status']); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= date('M d, Y', strtotime($service['created_at'])); ?>
                                    </td>

                                    <td>
                                        <button class="btn btn-warning btn-sm"
                                            data-toggle="modal"
                                            data-target="#editServiceModal<?= $service['service_id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <a href="?toggle_status=<?= $service['service_id']; ?>"
                                           class="btn btn-info btn-sm"
                                           onclick="return confirm('Toggle service status?')">
                                            <i class="fas fa-power-off"></i>
                                        </a>

                                        <a href="?delete=<?= $service['service_id']; ?>"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Delete this service?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>

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
<script src="../js/bootstrap.min.js"></script>
<script src="../js/dataTables/jquery.dataTables.min.js"></script>
<script src="../js/dataTables/dataTables.bootstrap.min.js"></script>
<!-- <script src="../js/dataTables/dataTables.responsive.js"></script> -->
<script src="../js/sweetalert2.all.min.js"></script>

<script>
   $(document).ready(function () {

        var table = $('#servicesTable').DataTable({
            responsive: true
        });

        /* ================= ADD SERVICE ================= */
        $('#addServiceModal form').submit(function (e) {
            e.preventDefault();

            let formData = $(this).serialize() + '&action=add';

            $.ajax({
                url: '../Functions/service_ajax.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (res) {

                    if (res.status === 'success') {

                        $('#addServiceModal').modal('hide');

                        Swal.fire({
                            icon: 'success',
                            title: 'Added!',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        });

                        fetchServices(); // 🔥 LIVE UPDATE

                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }
            });
        });


        /* ================= EDIT BUTTON CLICK ================= */
        $(document).on('click', '.editBtn', function () {

            let row = $(this).closest('tr');

            $('#edit_id').val($(this).data('id'));
            $('#edit_name').val(row.find('td:eq(0)').text().trim());
            $('#edit_desc').val(row.find('td:eq(1)').text().trim());
            $('#edit_duration').val(row.find('td:eq(2)').text().replace('mins','').trim());
            $('#edit_price').val(row.find('td:eq(3)').text().replace('₱','').trim());

            $('#editModal').modal('show');
        });


        /* ================= UPDATE SERVICE ================= */
        $('#editForm').submit(function (e) {
            e.preventDefault();

            $.ajax({
                url: '../Functions/service_ajax.php',
                type: 'POST',
                data: $(this).serialize() + '&action=update',
                dataType: 'json',
                success: function (res) {

                    if (res.status === 'success') {

                        $('#editModal').modal('hide');

                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        });

                        fetchServices(); // 🔥 LIVE UPDATE

                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }
            });
        });


        /* ================= DELETE ================= */
        $(document).on('click', '.deleteBtn', function () {

            let id = $(this).data('id');

            Swal.fire({
                title: 'Delete this service?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33'
            }).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({
                        url: '../Functions/service_ajax.php',
                        type: 'POST',
                        data: { action: 'delete', service_id: id },
                        dataType: 'json',
                        success: function (res) {

                            if (res.status === 'success') {

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    timer: 1200,
                                    showConfirmButton: false
                                });

                                fetchServices(); // 🔥 LIVE UPDATE
                            } else {
                                Swal.fire('Error', res.message, 'error');
                            }
                        }
                    });

                }
            });
        });


        /* ================= TOGGLE STATUS ================= */
        $(document).on('click', '.toggleBtn', function () {

            let id = $(this).data('id');

            $.ajax({
                url: '../Functions/service_ajax.php',
                type: 'POST',
                data: { action: 'toggle', service_id: id },
                dataType: 'json',
                success: function (res) {

                    if (res.status === 'success') {

                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            timer: 1200,
                            showConfirmButton: false
                        });

                        fetchServices(); // 🔥 LIVE UPDATE
                    }
                }
            });
        });


        /* ================= 🔥 LIVE TABLE REFRESH ================= */
        function fetchServices() {

            $.ajax({
                url: '../Functions/service_fetch.php',
                type: 'GET',
                success: function (data) {

                    table.clear().destroy();
                    $('#servicesTable tbody').html(data);

                    table = $('#servicesTable').DataTable({
                        responsive: true
                    });
                }
            });
        }

    });
</script>

</body>
</html>