<?php
// Include database connection
require_once 'include/connection.php';

// Handle form submission
$message = '';
if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role     = $_POST['role'] ?? 'user';
    $status   = $_POST['status'] ?? 'active';

    // Validate fields
    if (empty($username) || empty($password)) {
        $message = "Username and Password are required.";
    } else {
        // Check if username exists
        $stmt = $con->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "Username already exists!";
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $insertStmt = $con->prepare("INSERT INTO users (username, password, role, status, created_at) VALUES (?, ?, ?, ?, NOW())");
            $insertStmt->bind_param("ssss", $username, $hashedPassword, $role, $status);

            if ($insertStmt->execute()) {
                $message = "User registered successfully!";
            } else {
                $message = "Error: " . $con->error;
            }
        }

        $stmt->close();
        $insertStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Registration</title>
    <link rel="stylesheet" href="js/bootstrap.min.css">
</head>
<body>
<div class="container" style="margin-top:50px; max-width:600px;">
    <h2>User Registration</h2>
    <?php if($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="form-group">
            <label>Username *</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Password *</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role" class="form-control">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        <button type="submit" name="register" class="btn btn-success">Register User</button>
    </form>
</div>
</body>
</html>
