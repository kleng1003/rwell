<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['client_id'])) {
    header('Location: ../index.php');
    exit();
}

// Include database connection
require_once '../admin/include/connection.php';

$client_id = $_SESSION['client_id'];
$client_name = isset($_SESSION['client_name']) ? $_SESSION['client_name'] : 'Client';

$success_message = '';
$error_message = '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';

// Fetch current client information
$client_query = "SELECT * FROM tbl_client_accounts WHERE client_id = ?";
$stmt = $con->prepare($client_query);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$client_result = $stmt->get_result();
$client = $client_result->fetch_assoc();
$stmt->close();

// Build display name from first_name and last_name
$display_name = trim(($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? ''));
if (empty($display_name) && isset($client['fullname'])) {
    $display_name = $client['fullname'];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim(mysqli_real_escape_string($con, $_POST['first_name']));
    $last_name = trim(mysqli_real_escape_string($con, $_POST['last_name']));
    $email = trim(mysqli_real_escape_string($con, $_POST['email']));
    $contact_no = trim(mysqli_real_escape_string($con, $_POST['contact_no']));
    
    $errors = [];
    
    // Validation
    if (empty($first_name)) {
        $errors[] = "First name is required.";
    } elseif (strlen($first_name) < 2) {
        $errors[] = "First name must be at least 2 characters long.";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } else {
        // Check if email already exists for another account
        $check_email = mysqli_query($con, "SELECT client_id FROM tbl_client_accounts WHERE email = '$email' AND client_id != $client_id");
        if (mysqli_num_rows($check_email) > 0) {
            $errors[] = "Email already registered with another account.";
        }
    }
    
    if (empty($contact_no)) {
        $errors[] = "Contact number is required.";
    } elseif (!preg_match('/^[0-9]{10,15}$/', $contact_no)) {
        $errors[] = "Please enter a valid contact number (10-15 digits).";
    }
    
    if (empty($errors)) {
        $fullname = trim($first_name . ' ' . $last_name);
        
        // Check which columns exist in the table
        $columns_result = mysqli_query($con, "SHOW COLUMNS FROM tbl_client_accounts");
        $existing_columns = [];
        while ($col = mysqli_fetch_assoc($columns_result)) {
            $existing_columns[] = $col['Field'];
        }
        
        // Build update query based on existing columns
        $update_parts = [];
        
        if (in_array('first_name', $existing_columns)) {
            $update_parts[] = "first_name = '$first_name'";
        }
        if (in_array('last_name', $existing_columns)) {
            $update_parts[] = "last_name = '$last_name'";
        }
        if (in_array('fullname', $existing_columns)) {
            $update_parts[] = "fullname = '$fullname'";
        }
        
        $update_parts[] = "email = '$email'";
        $update_parts[] = "contact_no = '$contact_no'";
        
        $update_query = "UPDATE tbl_client_accounts SET " . implode(', ', $update_parts) . " WHERE client_id = $client_id";
        
        if (mysqli_query($con, $update_query)) {
            $_SESSION['client_name'] = $fullname;
            $_SESSION['client_first_name'] = $first_name;
            $_SESSION['client_last_name'] = $last_name;
            $_SESSION['client_email'] = $email;
            $_SESSION['client_contact'] = $contact_no;
            
            $success_message = "Profile updated successfully!";
            
            // Refresh client data
            if (in_array('first_name', $existing_columns)) {
                $client['first_name'] = $first_name;
            }
            if (in_array('last_name', $existing_columns)) {
                $client['last_name'] = $last_name;
            }
            if (in_array('fullname', $existing_columns)) {
                $client['fullname'] = $fullname;
            }
            $client['email'] = $email;
            $client['contact_no'] = $contact_no;
            $display_name = $fullname;
        } else {
            $error_message = "Failed to update profile. Please try again.";
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Verify current password
    if (!password_verify($current_password, $client['password'])) {
        $errors[] = "Current password is incorrect.";
    }
    
    // Validate new password
    if (empty($new_password)) {
        $errors[] = "New password is required.";
    } elseif (strlen($new_password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $new_password)) {
        $errors[] = "Password must contain at least one uppercase letter, one lowercase letter, and one number.";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    if ($current_password === $new_password) {
        $errors[] = "New password must be different from current password.";
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_password = "UPDATE tbl_client_accounts SET password = '$hashed_password' WHERE client_id = $client_id";
        
        if (mysqli_query($con, $update_password)) {
            $success_message = "Password changed successfully!";
            $active_tab = 'security';
        } else {
            $error_message = "Failed to change password. Please try again.";
        }
    } else {
        $error_message = implode('<br>', $errors);
        $active_tab = 'security';
    }
}

// Handle account deletion request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_account'])) {
    $confirm_delete = $_POST['confirm_delete'];
    $password = $_POST['delete_password'];
    
    if ($confirm_delete !== 'DELETE') {
        $error_message = "Please type DELETE to confirm account deletion.";
        $active_tab = 'security';
    } elseif (!password_verify($password, $client['password'])) {
        $error_message = "Incorrect password.";
        $active_tab = 'security';
    } else {
        // Delete the account
        $delete_query = "DELETE FROM tbl_client_accounts WHERE client_id = $client_id";
        
        if (mysqli_query($con, $delete_query)) {
            // Log the deletion
            if (function_exists('logActivity')) {
                $log_name = $display_name ?: $client['username'];
                logActivity("Client account deleted", "Client: {$log_name}, ID: $client_id");
            }
            
            // Destroy session and redirect
            session_destroy();
            header('Location: ../index.php?account_deleted=1');
            exit();
        } else {
            $error_message = "Failed to delete account. Please contact support.";
            $active_tab = 'security';
        }
    }
}

// Get linked customer_id first
$customer_id = null;

if (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) {
    $customer_id = (int) $_SESSION['customer_id'];
} else {
    $cust_query = mysqli_query($con, "SELECT customer_id FROM tbl_client_accounts WHERE client_id = $client_id LIMIT 1");
    if ($cust_query && mysqli_num_rows($cust_query) > 0) {
        $cust = mysqli_fetch_assoc($cust_query);
        if (!empty($cust['customer_id'])) {
            $customer_id = (int) $cust['customer_id'];
            $_SESSION['customer_id'] = $customer_id;
        }
    }
}

// Default stats
$stats = [
    'total_appointments' => 0,
    'completed_appointments' => 0,
    'upcoming_appointments' => 0,
    'cancelled_appointments' => 0
];

if ($customer_id) {
    $stats_query = "
        SELECT 
            COUNT(*) as total_appointments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
            SUM(CASE WHEN status IN ('pending', 'approved', 'confirmed') AND appointment_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_appointments,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments
        FROM appointments
        WHERE customer_id = $customer_id
    ";

    $stats_result = mysqli_query($con, $stats_query);
    if ($stats_result) {
        $stats = mysqli_fetch_assoc($stats_result);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - R-Well Salon & Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #fff5f7 0%, #ffe6ea 100%);
            min-height: 100vh;
        }
        
        .navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .settings-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .settings-header {
            margin-bottom: 30px;
        }
        
        .settings-header h1 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .settings-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .settings-sidebar {
            background: #f8f9fa;
            padding: 30px 0;
            height: 100%;
            border-right: 1px solid #e0e0e0;
        }
        
        .settings-sidebar .nav-link {
            color: #666;
            padding: 15px 25px;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .settings-sidebar .nav-link i {
            margin-right: 12px;
            width: 20px;
        }
        
        .settings-sidebar .nav-link:hover {
            color: #e91e63;
            background: rgba(233, 30, 99, 0.05);
        }
        
        .settings-sidebar .nav-link.active {
            color: #e91e63;
            background: rgba(233, 30, 99, 0.1);
            border-left-color: #e91e63;
        }
        
        .settings-content {
            padding: 30px 40px;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .section-subtitle {
            font-size: 1.1rem;
            font-weight: 600;
            color: #555;
            margin: 25px 0 15px 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #e91e63;
            box-shadow: 0 0 0 0.2rem rgba(233, 30, 99, 0.15);
        }
        
        .input-group-text {
            background: transparent;
            border: 1px solid #e0e0e0;
            border-right: none;
            border-radius: 10px 0 0 10px;
            color: #e91e63;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #e91e63, #ff6b6b);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(233, 30, 99, 0.3);
        }
        
        .btn-outline-danger {
            border: 2px solid #dc3545;
            color: #dc3545;
            border-radius: 10px;
            padding: 10px 25px;
            transition: all 0.3s ease;
        }
        
        .btn-outline-danger:hover {
            background: #dc3545;
            color: white;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stats-card.green {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
        }
        
        .stats-card.orange {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .password-strength {
            margin-top: 8px;
            height: 5px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
            border-radius: 10px;
        }
        
        .strength-weak { width: 33.33%; background: #dc3545; }
        .strength-medium { width: 66.66%; background: #ffc107; }
        .strength-strong { width: 100%; background: #28a745; }
        
        .delete-zone {
            border: 2px dashed #dc3545;
            border-radius: 15px;
            padding: 25px;
            background: #fff5f5;
            margin-top: 30px;
        }
        
        .delete-zone h5 {
            color: #dc3545;
            margin-bottom: 15px;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #e91e63, #ff6b6b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .user-avatar i {
            font-size: 40px;
            color: white;
        }
        
        .profile-summary {
            text-align: center;
            padding: 20px;
        }
        
        .profile-summary h4 {
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .profile-summary p {
            color: #888;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php">✨ R-Well Salon & Spa</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div id="navbarNav" class="collapse navbar-collapse justify-content-end">
            <ul class="navbar-nav align-items-center">
                <li class="nav-item">
                    <a class="nav-link" href="../index.php">
                        <i class="bi bi-house"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="./my-reservations.php">
                        <i class="bi bi-calendar-check"></i> My Appointments
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <?php echo htmlspecialchars($display_name ?: $client_name); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item active" href="profile.php">
                                <i class="bi bi-gear"></i> Settings
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="../index.php?logout=1">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="settings-container">
    <div class="settings-header">
        <h1><i class="bi bi-gear-fill me-2" style="color: #e91e63;"></i>Account Settings</h1>
        <p class="text-muted">Manage your account preferences and security settings</p>
    </div>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="settings-card">
        <div class="row g-0">
            <!-- Sidebar -->
            <div class="col-md-4">
                <div class="settings-sidebar">
                    <div class="profile-summary">
                        <div class="user-avatar">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <h4><?php echo htmlspecialchars($display_name ?: $client['username']); ?></h4>
                        <p><i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($client['email']); ?></p>
                        <p><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($client['contact_no']); ?></p>
                        <p class="small text-muted mt-2">
                            <i class="bi bi-calendar3"></i> Member since: <?php echo date('M Y', strtotime($client['created_at'])); ?>
                        </p>
                    </div>
                    
                    <hr class="my-3">
                    
                    <nav class="nav flex-column">
                        <a class="nav-link <?php echo $active_tab == 'profile' ? 'active' : ''; ?>" href="?tab=profile">
                            <i class="bi bi-person"></i> Profile Information
                        </a>
                        <a class="nav-link <?php echo $active_tab == 'security' ? 'active' : ''; ?>" href="?tab=security">
                            <i class="bi bi-shield-lock"></i> Security
                        </a>
                    </nav>
                    
                    <hr class="my-3">
                    
                    <!-- Quick Stats -->
                    <div class="px-3">
                        <h6 class="text-muted mb-3">Appointment Summary</h6>
                        <div class="stats-card">
                            <div class="stats-number"><?php echo (int)($stats['total_appointments'] ?? 0); ?></div>
                            <div class="stats-label">Total Appointments</div>
                        </div>
                        <div class="stats-card green">
                            <div class="stats-number"><?php echo (int)($stats['upcoming_appointments'] ?? 0); ?></div>
                            <div class="stats-label">Upcoming</div>
                        </div>
                        <div class="stats-card orange">
                            <div class="stats-number"><?php echo (int)($stats['completed_appointments'] ?? 0); ?></div>
                            <div class="stats-label">Completed</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-8">
                <div class="settings-content">
                    <?php if ($active_tab == 'profile'): ?>
                        <!-- Profile Information Tab -->
                        <h3 class="section-title">Profile Information</h3>
                        
                        <form method="POST" action="?tab=profile">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">First Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                                            <input type="text" class="form-control" name="first_name" 
                                                   value="<?php echo htmlspecialchars($client['first_name'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Last Name (Optional)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                                            <input type="text" class="form-control" name="last_name" 
                                                   value="<?php echo htmlspecialchars($client['last_name'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-at"></i></span>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($client['username']); ?>" disabled>
                                </div>
                                <small class="text-muted">Username cannot be changed</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo htmlspecialchars($client['email']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Contact Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="tel" class="form-control" name="contact_no" 
                                           value="<?php echo htmlspecialchars($client['contact_no']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Account Created</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                    <input type="text" class="form-control" 
                                           value="<?php echo date('F j, Y \a\t g:i A', strtotime($client['created_at'])); ?>" disabled>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary mt-3">
                                <i class="bi bi-check-lg me-2"></i>Save Changes
                            </button>
                        </form>
                        
                    <?php elseif ($active_tab == 'security'): ?>
                        <!-- Security Tab -->
                        <h3 class="section-title">Security Settings</h3>
                        
                        <!-- Change Password -->
                        <h5 class="section-subtitle">Change Password</h5>
                        <form method="POST" action="?tab=security" id="passwordForm">
                            <input type="hidden" name="change_password" value="1">
                            
                            <div class="form-group">
                                <label class="form-label">Current Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" name="current_password" id="currentPassword" required>
                                    <span class="input-group-text toggle-password" style="cursor: pointer;">
                                        <i class="bi bi-eye-slash"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                    <input type="password" class="form-control" name="new_password" id="newPassword" required>
                                    <span class="input-group-text toggle-password" style="cursor: pointer;">
                                        <i class="bi bi-eye-slash"></i>
                                    </span>
                                </div>
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                </div>
                                <small class="text-muted" id="passwordFeedback"></small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                    <input type="password" class="form-control" name="confirm_password" id="confirmPassword" required>
                                    <span class="input-group-text toggle-password" style="cursor: pointer;">
                                        <i class="bi bi-eye-slash"></i>
                                    </span>
                                </div>
                                <div id="confirmPasswordFeedback" class="form-text"></div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-shield-check me-2"></i>Update Password
                            </button>
                        </form>
                        
                        
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Password visibility toggle
    document.querySelectorAll('.toggle-password').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.closest('.input-group').querySelector('input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        });
    });
    
    // Password strength checker
    const newPassword = document.getElementById('newPassword');
    if (newPassword) {
        newPassword.addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrengthBar');
            const feedback = document.getElementById('passwordFeedback');
            
            strengthBar.className = 'password-strength-bar';
            
            const hasLength = password.length >= 8;
            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /\d/.test(password);
            
            let strength = 0;
            if (hasLength) strength++;
            if (hasUpper) strength++;
            if (hasLower) strength++;
            if (hasNumber) strength++;
            
            if (password.length === 0) {
                strengthBar.style.width = '0';
                feedback.textContent = '';
            } else if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
                feedback.textContent = 'Weak password';
                feedback.style.color = '#dc3545';
            } else if (strength === 3) {
                strengthBar.classList.add('strength-medium');
                feedback.textContent = 'Medium password';
                feedback.style.color = '#ffc107';
            } else {
                strengthBar.classList.add('strength-strong');
                feedback.textContent = 'Strong password';
                feedback.style.color = '#28a745';
            }
        });
    }
    
    // Confirm password validation
    const confirmPassword = document.getElementById('confirmPassword');
    if (confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            const password = document.getElementById('newPassword').value;
            const confirm = this.value;
            const feedback = document.getElementById('confirmPasswordFeedback');
            
            if (confirm === '') {
                feedback.textContent = '';
                this.classList.remove('is-valid', 'is-invalid');
            } else if (password === confirm) {
                feedback.textContent = '✓ Passwords match';
                feedback.style.color = '#28a745';
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            } else {
                feedback.textContent = '✗ Passwords do not match';
                feedback.style.color = '#dc3545';
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            }
        });
    }
</script>
</body>
</html>