<?php
// ../admin/Functions/check_username.php
session_start();
include_once('../../include/connection.php');

header('Content-Type: application/json');

if (!isset($_POST['username'])) {
    echo json_encode(['exists' => false]);
    exit();
}

$username = mysqli_real_escape_string($con, trim($_POST['username']));

if (strlen($username) < 3) {
    echo json_encode(['exists' => false]);
    exit();
}

$query = mysqli_query($con, "SELECT user_id FROM users WHERE username = '$username'");

if (mysqli_num_rows($query) > 0) {
    echo json_encode(['exists' => true]);
} else {
    echo json_encode(['exists' => false]);
}
?>