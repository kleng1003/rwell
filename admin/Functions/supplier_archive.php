<?php
include_once('../include/connection.php');

$id = intval($_GET['id']);

$con->query("
    UPDATE suppliers SET status='archived'
    WHERE supplier_id=$id
");

header("Location: ../Pages/suppliers.php");
exit;
