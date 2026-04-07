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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($con, $_POST['username']);
    $password = $_POST['password'];
    $confirm = $_POST['cPassword'];
    
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        // Check if username exists
        $check = mysqli_query($con, "SELECT user_id FROM users WHERE username = '$username'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Username already exists";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert = "INSERT INTO users (username, password, role, status, created_at) 
                       VALUES ('$username', '$hashed_password', 'admin', 'active', NOW())";
            
            if (mysqli_query($con, $insert)) {
                $success = "Admin account created successfully!";
            } else {
                $error = "Database error: " . mysqli_error($con);
            }
        }
    }
}
?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header" style="font-weight:600;color:#464660;">
            Add New Admin (Test Version)
        </h1>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= $success; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">Admin Registration</div>
            <div class="panel-body">
                <form method="POST">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="cPassword" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Create Admin</button>
                    <a href="admin-account.php" class="btn btn-default">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>