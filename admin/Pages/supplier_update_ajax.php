<?php
include_once('../include/connection.php');
header('Content-Type: application/json');

$stmt = $con->prepare("
UPDATE suppliers SET
company_name=?, contact_person=?, phone=?, email=?, address=?
WHERE supplier_id=?
");

$stmt->bind_param(
    "sssssi",
    $_POST['company_name'],
    $_POST['contact_person'],
    $_POST['phone'],
    $_POST['email'],
    $_POST['address'],
    $_POST['supplier_id']
);

echo json_encode(
    $stmt->execute()
    ? ['status'=>'success']
    : ['status'=>'error','message'=>'Update failed']
);
