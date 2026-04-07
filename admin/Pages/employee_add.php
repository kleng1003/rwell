<?php
include_once('../include/template.php');
include_once('../include/connection.php');

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Check if required columns exist
$check_columns = mysqli_query($con, "SHOW COLUMNS FROM employees LIKE 'has_user_account'");
if (mysqli_num_rows($check_columns) == 0) {
    // Add the column if it doesn't exist
    mysqli_query($con, "ALTER TABLE employees ADD COLUMN has_user_account tinyint(1) DEFAULT 0 AFTER status");
    mysqli_query($con, "ALTER TABLE employees ADD COLUMN user_id int(11) NULL AFTER has_user_account");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Start transaction
    mysqli_begin_transaction($con);
    
    try {
        // Get employee data
        $first_name = mysqli_real_escape_string($con, $_POST['first_name']);
        $last_name = mysqli_real_escape_string($con, $_POST['last_name']);
        $phone = mysqli_real_escape_string($con, $_POST['phone']);
        $email = mysqli_real_escape_string($con, $_POST['email']);
        $position = mysqli_real_escape_string($con, $_POST['position']);
        $hire_date = mysqli_real_escape_string($con, $_POST['hire_date']);
        $status = mysqli_real_escape_string($con, $_POST['status']);
        
        // Insert employee (without has_user_account initially)
        $insert_employee = "INSERT INTO employees (first_name, last_name, phone, email, position, hire_date, status) 
                            VALUES ('$first_name', '$last_name', '$phone', '$email', '$position', '$hire_date', '$status')";
        
        if (!mysqli_query($con, $insert_employee)) {
            throw new Exception("Failed to add employee: " . mysqli_error($con));
        }
        
        $employee_id = mysqli_insert_id($con);
        
        // Insert work schedule
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $day_map = ['monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6, 'sunday' => 0];
        
        foreach ($days as $day) {
            $is_day_off = isset($_POST[$day . '_off']) ? 1 : 0;
            
            if ($is_day_off) {
                // Day off
                $insert_schedule = "INSERT INTO employee_work_schedule (employee_id, day_of_week, start_time, end_time, is_day_off) 
                                   VALUES ('$employee_id', '{$day_map[$day]}', '00:00:00', '00:00:00', 1)";
                if (!mysqli_query($con, $insert_schedule)) {
                    throw new Exception("Failed to add schedule for $day: " . mysqli_error($con));
                }
            } else {
                $start_time = mysqli_real_escape_string($con, $_POST[$day . '_start']);
                $end_time = mysqli_real_escape_string($con, $_POST[$day . '_end']);
                
                if (!empty($start_time) && !empty($end_time)) {
                    $insert_schedule = "INSERT INTO employee_work_schedule (employee_id, day_of_week, start_time, end_time, is_day_off) 
                                       VALUES ('$employee_id', '{$day_map[$day]}', '$start_time', '$end_time', 0)";
                    if (!mysqli_query($con, $insert_schedule)) {
                        throw new Exception("Failed to add schedule for $day: " . mysqli_error($con));
                    }
                }
            }
        }
        
        // Handle user account creation if requested
        $create_account = isset($_POST['create_account']) ? 1 : 0;
        
        if ($create_account) {
            $username = mysqli_real_escape_string($con, $_POST['username']);
            $password = $_POST['password'];
            
            // Check if username exists
            $check_username = mysqli_query($con, "SELECT user_id FROM users WHERE username = '$username'");
            if (mysqli_num_rows($check_username) > 0) {
                throw new Exception("Username already exists. Please choose a different username.");
            }
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user account with pending status
            $insert_user = "INSERT INTO users (username, password, role, employee_id, status, created_at) 
                           VALUES ('$username', '$hashed_password', 'employee', '$employee_id', 'pending', NOW())";
            
            if (!mysqli_query($con, $insert_user)) {
                throw new Exception("Failed to create user account: " . mysqli_error($con));
            }
            
            $user_id = mysqli_insert_id($con);
            
            // Update employee with user_id and has_user_account
            mysqli_query($con, "UPDATE employees SET user_id = '$user_id', has_user_account = 1 WHERE employee_id = '$employee_id'");
        }
        
        // Commit transaction
        mysqli_commit($con);
        
        $success = "Employee added successfully!";
        if ($create_account) {
            $success .= " User account created with 'Pending' status. The employee will be able to log in after admin approval.";
        }
        
        // Clear form
        $_POST = array();
        
    } catch (Exception $e) {
        mysqli_rollback($con);
        $error = $e->getMessage();
    }
}
?>

<style>
    .form-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        border-left: 4px solid #464660;
    }
    
    .form-section-title {
        font-size: 18px;
        font-weight: 600;
        color: #464660;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e9ecef;
    }
    
    .form-section-title i {
        margin-right: 8px;
    }
    
    .schedule-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .schedule-table th {
        background: #e9ecef;
        padding: 10px;
        text-align: left;
        font-weight: 600;
        color: #464660;
    }
    
    .schedule-table td {
        padding: 10px;
        border-bottom: 1px solid #dee2e6;
    }
    
    .time-input {
        width: 120px;
        padding: 8px;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }
    
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }
    
    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 24px;
    }
    
    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    
    input:checked + .toggle-slider {
        background-color: #28a745;
    }
    
    input:checked + .toggle-slider:before {
        transform: translateX(26px);
    }
    
    .account-fields {
        display: none;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px dashed #dee2e6;
    }
    
    .account-fields.show {
        display: block;
    }
    
    .info-box {
        background: #e7f3ff;
        border-left: 4px solid #17a2b8;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .info-box i {
        color: #17a2b8;
        margin-right: 8px;
    }
    
    .schedule-row.disabled {
        background: #f8f9fa;
        opacity: 0.7;
    }
    
    .alert {
        border-radius: 8px;
        margin-bottom: 20px;
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header" style="font-weight:600; color:#464660;">
            <i class="fas fa-user-plus"></i> Add New Employee
        </h1>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fas fa-exclamation-circle"></i> <?= $error; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fas fa-check-circle"></i> <?= $success; ?>
        <br>
        <a href="employees.php" class="btn btn-sm btn-success mt-2">View Employees</a>
        <a href="employee_add.php" class="btn btn-sm btn-info mt-2">Add Another Employee</a>
    </div>
<?php endif; ?>

<form method="POST" class="form-horizontal">
    <!-- Employee Information Section -->
    <div class="form-section">
        <div class="form-section-title">
            <i class="fas fa-user-tie"></i> Employee Information
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" 
                           value="<?= isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" 
                           required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control" 
                           value="<?= isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" 
                           required>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone Number <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" 
                           value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                           required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label><i class="fas fa-briefcase"></i> Position <span class="text-danger">*</span></label>
                    <input type="text" name="position" class="form-control" 
                           value="<?= isset($_POST['position']) ? htmlspecialchars($_POST['position']) : ''; ?>" 
                           required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Hire Date</label>
                    <input type="date" name="hire_date" class="form-control" 
                           value="<?= isset($_POST['hire_date']) ? $_POST['hire_date'] : date('Y-m-d'); ?>">
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label><i class="fas fa-toggle-on"></i> Employment Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?= (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?= (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Work Schedule Section -->
    <div class="form-section">
        <div class="form-section-title">
            <i class="fas fa-calendar-week"></i> Work Schedule
        </div>
        
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>Set working hours for each day.</strong> This information helps customers check employee availability when booking appointments.
        </div>
        
        <table class="schedule-table">
            <thead>
                <tr>
                    <th width="150">Day</th>
                    <th width="100">Day Off?</th>
                    <th>Working Hours</th>
                </thead>
            <tbody>
                <?php
                $days = [
                    'monday' => 'Monday',
                    'tuesday' => 'Tuesday', 
                    'wednesday' => 'Wednesday',
                    'thursday' => 'Thursday',
                    'friday' => 'Friday',
                    'saturday' => 'Saturday',
                    'sunday' => 'Sunday'
                ];
                
                $default_start = '09:00';
                $default_end = '18:00';
                
                foreach ($days as $key => $day_name):
                ?>
                <tr class="schedule-row" id="row_<?= $key; ?>">
                     <td>
                        <strong><?= $day_name; ?></strong>
                     </td>
                     <td>
                        <label class="toggle-switch">
                            <input type="checkbox" name="<?= $key; ?>_off" id="<?= $key; ?>_off" 
                                   onchange="toggleDay('<?= $key; ?>')"
                                   <?= (isset($_POST[$key . '_off']) && $_POST[$key . '_off'] == 'on') ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                     </td>
                     <td>
                        <div id="<?= $key; ?>_schedule">
                            <input type="time" name="<?= $key; ?>_start" class="time-input" 
                                   value="<?= isset($_POST[$key . '_start']) ? $_POST[$key . '_start'] : $default_start; ?>"
                                   <?= (isset($_POST[$key . '_off']) && $_POST[$key . '_off'] == 'on') ? 'disabled' : ''; ?>>
                            <span> to </span>
                            <input type="time" name="<?= $key; ?>_end" class="time-input" 
                                   value="<?= isset($_POST[$key . '_end']) ? $_POST[$key . '_end'] : $default_end; ?>"
                                   <?= (isset($_POST[$key . '_off']) && $_POST[$key . '_off'] == 'on') ? 'disabled' : ''; ?>>
                        </div>
                     </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- System Access Section -->
    <div class="form-section">
        <div class="form-section-title">
            <i class="fas fa-laptop"></i> System Access (Optional)
        </div>
        
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>Optional:</strong> Create a login account for this employee. 
            The account will be created with <strong>"Pending"</strong> status and will require admin approval before they can log in.
        </div>
        
        <div class="form-group">
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="create_account" id="create_account" 
                           <?= isset($_POST['create_account']) ? 'checked' : ''; ?>>
                    <strong><i class="fas fa-key"></i> Create system login account for this employee</strong>
                </label>
            </div>
        </div>
        
        <div id="account_fields" class="account-fields <?= isset($_POST['create_account']) ? 'show' : ''; ?>">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-user-circle"></i> Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" 
                               value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                               placeholder="Enter username">
                        <small class="text-muted">Username must be unique</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" 
                               placeholder="Enter password">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-warning">
                <i class="fas fa-clock"></i>
                <strong>Note:</strong> The account will be created with <strong>PENDING</strong> status. 
                The employee will not be able to log in until you approve the account.
            </div>
        </div>
    </div>
    
    <!-- Form Actions -->
    <div class="form-group text-right">
        <a href="employees.php" class="btn btn-default">
            <i class="fas fa-times"></i> Cancel
        </a>
        <button type="submit" class="btn btn-success">
            <i class="fas fa-save"></i> Add Employee
        </button>
    </div>
</form>

<script>
function toggleDay(day) {
    const isDayOff = document.getElementById(day + '_off').checked;
    const scheduleDiv = document.getElementById(day + '_schedule');
    const inputs = scheduleDiv.querySelectorAll('input');
    
    if (isDayOff) {
        inputs.forEach(input => input.disabled = true);
        document.getElementById('row_' + day).classList.add('disabled');
    } else {
        inputs.forEach(input => input.disabled = false);
        document.getElementById('row_' + day).classList.remove('disabled');
    }
}

$(document).ready(function() {
    $('#create_account').change(function() {
        if ($(this).is(':checked')) {
            $('#account_fields').addClass('show');
        } else {
            $('#account_fields').removeClass('show');
        }
    });
    
    // Validate at least one working day
    $('form').submit(function(e) {
        if ($('#create_account').is(':checked')) {
            var username = $('input[name="username"]').val().trim();
            var password = $('input[name="password"]').val();
            
            if (!username) {
                alert('Please enter a username');
                e.preventDefault();
                return false;
            }
            
            if (!password || password.length < 6) {
                alert('Password must be at least 6 characters long');
                e.preventDefault();
                return false;
            }
        }
        
        // Validate at least one working day
        let hasWorkingDay = false;
        const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        for (let day of days) {
            const isDayOff = document.getElementById(day + '_off').checked;
            if (!isDayOff) {
                const startTime = document.querySelector(`input[name="${day}_start"]`).value;
                const endTime = document.querySelector(`input[name="${day}_end"]`).value;
                if (startTime && endTime) {
                    hasWorkingDay = true;
                    break;
                }
            }
        }
        
        if (!hasWorkingDay) {
            alert('Please set at least one working day for the employee');
            e.preventDefault();
            return false;
        }
    });
});
</script>