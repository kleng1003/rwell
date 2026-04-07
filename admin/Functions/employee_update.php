<?php
include_once('../include/template.php');
include_once('../include/connection.php');

if (!isset($_GET['id'])) {
    header("Location: ../Pages/employees.php");
    exit();
}

$id = $_GET['id'];

$sql = "SELECT * FROM employees WHERE employee_id = ?";
$stmt = mysqli_stmt_init($con);

if (!mysqli_stmt_prepare($stmt, $sql)) {
    die("Query failed");
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result->num_rows == 0) {
    header("Location: ../Pages/employees.php");
    exit();
}

$row = mysqli_fetch_assoc($result);
?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header" style="font-weight:600;color:#464660;">
            Update Employee
        </h1>
    </div>
</div>

<div class="row">
<div class="col-lg-8 col-lg-offset-2">
<div class="panel panel-default">
    <div class="panel-heading">
        <i class="fas fa-edit"></i> Edit Employee Information
    </div>

    <div class="panel-body">
        <form method="POST" action="employee_update_process.php">

            <input type="hidden" name="employee_id" value="<?= $row['employee_id']; ?>">

            <div class="form-group">
                <label>First Name *</label>
                <input type="text" name="first_name" class="form-control"
                       value="<?= htmlspecialchars($row['first_name']); ?>" required>
            </div>

            <div class="form-group">
                <label>Last Name *</label>
                <input type="text" name="last_name" class="form-control"
                       value="<?= htmlspecialchars($row['last_name']); ?>" required>
            </div>

            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control"
                       value="<?= htmlspecialchars($row['phone']); ?>">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($row['email']); ?>">
            </div>

            <div class="form-group">
                <label>Position *</label>
                <input type="text" name="position" class="form-control"
                       value="<?= htmlspecialchars($row['position']); ?>" required>
            </div>

            <div class="form-group">
                <label>Hire Date *</label>
                <input type="date" name="hire_date" class="form-control"
                       value="<?= $row['hire_date']; ?>" required>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active" <?= $row['status']=='active'?'selected':''; ?>>Active</option>
                    <option value="inactive" <?= $row['status']=='inactive'?'selected':''; ?>>Inactive</option>
                </select>
            </div>

            <div class="form-group text-right">
                <a href="../Pages/employees.php" class="btn btn-default">
                    Cancel
                </a>
                <button type="submit" name="update" class="btn btn-success">
                    Update Employee
                </button>
            </div>

        </form>
    </div>
</div>
</div>
</div>