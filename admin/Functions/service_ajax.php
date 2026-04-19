<?php
session_start();
include_once('../include/connection.php');

header('Content-Type: application/json');

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? '';

/* ================= ADD ================= */
if ($action == 'add') {

   $name = $_POST['service_name'];
    $category = $_POST['category'];
    $desc = $_POST['description'];
    $price = floatval($_POST['price']);
    $duration = intval($_POST['duration']);
    $status = $_POST['status'];

    $stmt = $con->prepare("INSERT INTO services (service_name, category, description, price, duration, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdis", $name, $category, $desc, $price, $duration, $status);

    if ($stmt->execute()) {
        echo json_encode(['status'=>'success','message'=>'Service added']);
    } else {
        echo json_encode(['status'=>'error','message'=>$stmt->error]);
    }
}

/* ================= UPDATE ================= */
if ($action == 'update') {

    $id = $_POST['service_id'];
    $name = $_POST['service_name'];
    $category = $_POST['category'];
    $desc = $_POST['description'];
    $price = floatval($_POST['price']);
    $duration = intval($_POST['duration']);
    $status = $_POST['status'];

    $stmt = $con->prepare("UPDATE services SET service_name=?, category=?, description=?, price=?, duration=?, status=? WHERE service_id=?");
    $stmt->bind_param("sssdisi", $name, $category, $desc, $price, $duration, $status, $id);

    if ($stmt->execute()) {
        echo json_encode(['status'=>'success','message'=>'Service updated']);
    } else {
        echo json_encode(['status'=>'error','message'=>$stmt->error]);
    }
}

/* ================= DELETE ================= */
if ($action == 'delete') {

    $id = $_POST['service_id'];

    $check = mysqli_query($con, "SELECT COUNT(*) as total FROM appointments WHERE service_id = '$id'");
    $row = mysqli_fetch_assoc($check);

    if ($row['total'] > 0) {
        echo json_encode(['status'=>'error','message'=>'Service has appointments']);
        exit();
    }

    mysqli_query($con, "DELETE FROM services WHERE service_id='$id'");
    echo json_encode(['status'=>'success','message'=>'Deleted']);
}

/* ================= TOGGLE ================= */
if ($action == 'toggle') {

    $id = $_POST['service_id'];

    mysqli_query($con, "
        UPDATE services 
        SET status = IF(status='active','inactive','active') 
        WHERE service_id='$id'
    ");

    echo json_encode(['status'=>'success','message'=>'Status updated']);
}