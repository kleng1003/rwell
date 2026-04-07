<?php
include_once('../include/connection.php');

if (isset($_POST['add_product'])) {

    $product_name = $_POST['product_name'];
    $supplier_id  = $_POST['supplier_id'];
    $category     = $_POST['category'];
    $price        = $_POST['price'];
    $stock        = $_POST['stock'];
    $status       = $_POST['status'];

    $sql = "INSERT INTO products 
        (supplier_id, product_name, category, price, stock, status) 
        VALUES 
        (?, ?, ?, ?, ?, ?)";

    $stmt = $con->prepare($sql);
    $stmt->bind_param(
        "issdis",
        $supplier_id,
        $product_name,
        $category,
        $price,
        $stock,
        $status
    );

    if ($stmt->execute()) {
        header("Location: ../Pages/products.php?success=1");
    } else {
        header("Location: ../Pages/products.php?error=1");
    }
}
