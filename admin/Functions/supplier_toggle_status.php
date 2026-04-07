<?php
include_once('../include/connection.php');

$id = intval($_POST['id']);
$current = $_POST['status'];

$newStatus = ($current === 'active') ? 'inactive' : 'active';

$con->query("
    UPDATE suppliers 
    SET status='$newStatus' 
    WHERE supplier_id='$id'
");

echo json_encode(['status'=>'success']);
