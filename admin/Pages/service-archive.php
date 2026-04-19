<?php
include_once('../include/template.php');
include_once('../include/connection.php');

// Handle Restore Service
if (isset($_GET['restore']) && is_numeric($_GET['restore'])) {
    $service_id = intval($_GET['restore']);
    $update = mysqli_query($con, "UPDATE services SET status = 'active' WHERE service_id = $service_id");
    
    if ($update) {
        $_SESSION['success'] = "Service restored successfully!";
    } else {
        $_SESSION['error'] = "Failed to restore service: " . mysqli_error($con);
    }
    // header("Location: service-archive.php");
    // exit();
}

// Handle Permanent Delete
if (isset($_GET['delete_permanent']) && is_numeric($_GET['delete_permanent'])) {
    $service_id = intval($_GET['delete_permanent']);
    
    // Check if service is used in appointments before deleting
    $check_appointments = mysqli_query($con, "SELECT COUNT(*) as count FROM appointments WHERE service_id = $service_id");
    $appointment_count = mysqli_fetch_assoc($check_appointments)['count'];
    
    if ($appointment_count > 0) {
        $_SESSION['error'] = "Cannot delete this service because it is linked to $appointment_count appointment(s). Keep it archived instead.";
    } else {
        $delete = mysqli_query($con, "DELETE FROM services WHERE service_id = $service_id");
        
        if ($delete) {
            $_SESSION['success'] = "Service permanently deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete service: " . mysqli_error($con);
        }
    }
    header("Location: service-archive.php");
    exit();
}

// Fetch only archived services
$sql = "SELECT * FROM services WHERE status = 'inactive' ORDER BY created_at DESC";
$result = $con->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Service Archive - RWELL Salon</title>
    <style>
        .archive-header {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-archived {
            background: #fff3cd;
            color: #856404;
        }
        .empty-archive {
            text-align: center;
            padding: 50px;
            background: #f8f9fa;
            border-radius: 10px;
        }
    </style>
</head>
<body>

    <div class="archive-header">
        <div class="row">
            <div class="col-md-8">
                <h1><i class="fas fa-archive"></i> Service Archive</h1>
                <p>Manage archived services. Restore or permanently delete records.</p>
            </div>
            <div class="col-md-4 text-right">
                <a href="services.php" class="btn btn-default" style="background: white; color: #333;">
                    <i class="fas fa-arrow-left"></i> Back to Active Services
                </a>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade in">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade in">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <strong><i class="fas fa-archive"></i> Archived Service Records</strong>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table id="archiveTable" class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="20%">Service Name</th>
                                    <th width="15%">Category</th>
                                    <th width="35%">Description</th>
                                    <th width="10%">Price</th>
                                    <th width="10%">Duration</th>
                                    <th width="20%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?= $row['service_id']; ?></td>
                                            <td><strong><?= htmlspecialchars($row['service_name']); ?></strong></td>
                                            <td><?= htmlspecialchars($row['category']); ?></td>
                                            <td><?= htmlspecialchars(substr($row['description'], 0, 100)) . (strlen($row['description']) > 100 ? '...' : ''); ?></td>
                                            <td>₱<?= number_format($row['price'], 2); ?></td>
                                            <td><?= $row['duration']; ?> mins</td>
                                            <td>
                                                <a href="?restore=<?= $row['service_id']; ?>" 
                                                   class="btn btn-success btn-sm" 
                                                   onclick="return confirm('Restore this service?')">
                                                    <i class="fas fa-undo"></i> Restore
                                                </a>
                                                <a href="?delete_permanent=<?= $row['service_id']; ?>" 
                                                   class="btn btn-danger btn-sm" 
                                                   onclick="return confirm('PERMANENT DELETE: This action cannot be undone! Are you sure?')">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-archive">
                                                <i class="fas fa-archive fa-3x"></i>
                                                <h3>Archive is Empty</h3>
                                                <p>No archived services found.</p>
                                            </div>
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
<script src="../js/bootstrap.min.js"></script>
<script src="../js/dataTables/jquery.dataTables.min.js"></script>
<script src="../js/dataTables/dataTables.bootstrap.min.js"></script>
<script>
    $(document).ready(function() {
        $('#archiveTable').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[0, 'desc']]
        });
    });
</script>
</body>
</html>