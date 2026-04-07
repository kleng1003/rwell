<?php
if (isset($_POST['save'])) {

    require_once('../include/connection.php');

    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $phone      = trim($_POST['phone']);
    $email      = trim($_POST['email']);
    $position   = trim($_POST['position']);
    $hire_date  = $_POST['hire_date'];
    $status     = $_POST['status'];

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($position) || empty($hire_date)) {
        header("Location: ../Pages/employee_add.php?error=emptyfields");
        exit();
    }

    $sql = "INSERT INTO employees 
            (first_name, last_name, phone, email, position, hire_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_stmt_init($con);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        header("Location: ../Pages/employee_add.php?error=queryfailed");
        exit();
    }

    mysqli_stmt_bind_param($stmt, "sssssss",
        $first_name,
        $last_name,
        $phone,
        $email,
        $position,
        $hire_date,
        $status
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($con);

    header("Location: ../Pages/employees.php?success=added");
    exit();

} else {
    header("Location: ../Pages/employee_add.php");
    exit();
}