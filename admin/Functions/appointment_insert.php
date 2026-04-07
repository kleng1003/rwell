<?php
if (isset($_POST['save'])) {

    require_once('../include/connection.php');

    $customer_id      = $_POST['customer_id'];
    $employee_id      = $_POST['employee_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $purpose          = trim($_POST['purpose']);
    $status           = $_POST['status'];

    if (empty($customer_id) || empty($employee_id) || empty($appointment_date) || empty($appointment_time) || empty($purpose)) {
        header("Location: ../Pages/appointment_add.php?error=emptyfields");
        exit();
    }

    $sql = "INSERT INTO appointments
            (customer_id, employee_id, appointment_date, appointment_time, purpose, status)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_stmt_init($con);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        die("Insert failed");
    }

    mysqli_stmt_bind_param($stmt, "iissss",
        $customer_id,
        $employee_id,
        $appointment_date,
        $appointment_time,
        $purpose,
        $status
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($con);

    header("Location: ../Pages/appointments.php?success=added");
    exit();

} else {
    header("Location: ../Pages/appointments.php");
    exit();
}