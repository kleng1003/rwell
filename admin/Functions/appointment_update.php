<?php
// ../Functions/appointment_update.php
session_start();
include_once('../include/connection.php');
include_once('../include/activity_logger.php');

// Check if user is logged in
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../Pages/appointments.php");
    exit();
}

$appointment_id = intval($_GET['id']);

// Fetch appointment details to check if exists
$check_query = mysqli_query($con, "SELECT * FROM appointments WHERE appointment_id = '$appointment_id'");
if (mysqli_num_rows($check_query) == 0) {
    $_SESSION['error'] = "Appointment not found.";
    header("Location: ../Pages/appointments.php");
    exit();
}

$appointment = mysqli_fetch_assoc($check_query);

// Get customers and employees for the form
$customers = mysqli_query($con, "SELECT customer_id, CONCAT(first_name, ' ', last_name) AS name FROM customers ORDER BY name ASC");
$employees = mysqli_query($con, "SELECT employee_id, CONCAT(first_name, ' ', last_name) AS name FROM employees WHERE status = 'active' ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Appointment - RWELL</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background: #f5f5f5;
        }
        
        .form-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .form-header {
            background: linear-gradient(135deg, #464660 0%, #5a5a7a 100%);
            color: white;
            padding: 20px 25px;
        }
        
        .form-header h3 {
            margin: 0;
            font-weight: 600;
        }
        
        .form-header i {
            margin-right: 10px;
        }
        
        .form-body {
            padding: 30px;
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
        }
        
        .required::after {
            content: " *";
            color: #dc3545;
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
            outline: none;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .btn-custom {
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-primary-custom {
            background: #464660;
            color: white;
            border: none;
        }
        
        .btn-primary-custom:hover {
            background: #5a5a7a;
        }
        
        .btn-secondary-custom {
            background: #6c757d;
            color: white;
            border: none;
        }
        
        .btn-secondary-custom:hover {
            background: #5a6268;
        }
        
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h3><i class="fas fa-edit"></i> Edit Appointment</h3>
        </div>
        
        <div class="form-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fas fa-check-circle"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="appointment_update_process.php">
                <input type="hidden" name="appointment_id" value="<?= $appointment['appointment_id']; ?>">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required"><i class="fas fa-user"></i> Customer</label>
                            <select name="customer_id" class="form-control" required>
                                <?php while($c = mysqli_fetch_assoc($customers)): ?>
                                    <option value="<?= $c['customer_id']; ?>" <?= $c['customer_id'] == $appointment['customer_id'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($c['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-user-tie"></i> Employee</label>
                            <select name="employee_id" class="form-control">
                                <option value="">-- Select Employee (Optional) --</option>
                                <?php while($e = mysqli_fetch_assoc($employees)): ?>
                                    <option value="<?= $e['employee_id']; ?>" <?= $e['employee_id'] == $appointment['employee_id'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($e['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required"><i class="fas fa-calendar-alt"></i> Appointment Date</label>
                            <input type="date" name="appointment_date" class="form-control" 
                                   value="<?= $appointment['appointment_date']; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required"><i class="fas fa-clock"></i> Appointment Time</label>
                            <input type="time" name="appointment_time" class="form-control" 
                                   value="<?= $appointment['appointment_time']; ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-info-circle"></i> Purpose / Notes</label>
                    <textarea name="purpose" class="form-control" rows="3"><?= htmlspecialchars($appointment['purpose']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-toggle-on"></i> Status</label>
                    <select name="status" class="form-control">
                        <option value="pending" <?= $appointment['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?= $appointment['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="completed" <?= $appointment['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?= $appointment['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="text-right">
                    <a href="../Pages/appointments.php" class="btn btn-secondary-custom btn-custom">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" name="update_appointment" class="btn btn-primary-custom btn-custom">
                        <i class="fas fa-save"></i> Update Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../js/jquery.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
</body>
</html>