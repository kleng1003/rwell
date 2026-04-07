<?php
include_once('../include/connection.php');
header('Content-Type: application/json');

$response = [];

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

// Required fields
if (
    empty($_POST['first_name']) ||
    empty($_POST['last_name']) ||
    empty($_POST['phone'])
) {
    echo json_encode([
        'status' => 'error',
        'message' => 'First Name, Last Name, and Phone are required.'
    ]);
    exit;
}

// Assign values
$first_name = $_POST['first_name'];
$last_name  = $_POST['last_name'];
$phone      = $_POST['phone'];
$email      = $_POST['email'] ?? '';
$address    = $_POST['address'] ?? '';
$services   = $_POST['services'] ?? [];

// Insert customer (prepared statement)
$stmt = $con->prepare("
    INSERT INTO customers (first_name, last_name, phone, email, address)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param("sssss", $first_name, $last_name, $phone, $email, $address);

if (!$stmt->execute()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to save customer.'
    ]);
    exit;
}

$customer_id = $stmt->insert_id;

// Insert selected services
if (!empty($services)) {
    $serviceStmt = $con->prepare("
        INSERT INTO customer_services (customer_id, service_id)
        VALUES (?, ?)
    ");
    foreach ($services as $service_id) {
        $serviceStmt->bind_param("ii", $customer_id, $service_id);
        $serviceStmt->execute();
    }
}

// Success response
echo json_encode([
    'status' => 'success',
    'data' => [
        'customer_id' => $customer_id,
        'fullName' => $first_name . ' ' . $last_name,
        'phone' => $phone,
        'email' => $email,
        'address' => $address
    ]
]);
exit;
