<?php
if (isset($_POST['update'])) {

    require_once('../include/connection.php');

    $id         = $_POST['employee_id'];
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $phone      = trim($_POST['phone']);
    $email      = trim($_POST['email']);
    $position   = trim($_POST['position']);
    $hire_date  = $_POST['hire_date'];
    $status     = $_POST['status'];

    $sql = "UPDATE employees SET
            first_name=?,
            last_name=?,
            phone=?,
            email=?,
            position=?,
            hire_date=?,
            status=?
            WHERE employee_id=?";

    $stmt = mysqli_stmt_init($con);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        die("Update failed");
    }

    mysqli_stmt_bind_param($stmt, "sssssssi",
        $first_name,
        $last_name,
        $phone,
        $email,
        $position,
        $hire_date,
        $status,
        $id
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    header("Location: ../Pages/employees.php?success=updated");
    exit();

} else {
    header("Location: ../Pages/employees.php");
    exit();
}