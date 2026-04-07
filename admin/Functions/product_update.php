<?php
include_once('../include/connection.php');

if (isset($_POST['update_product'])) {

    $product_id   = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $supplier_id  = $_POST['supplier_id'];
    $category     = $_POST['category'];
    $price        = $_POST['price'];
    $stock        = $_POST['stock'];
    $status       = $_POST['status'];

    $sql = "UPDATE products SET
                product_name = ?,
                supplier_id  = ?,
                category     = ?,
                price        = ?,
                stock        = ?,
                status       = ?
            WHERE product_id = ?";

    $stmt = $con->prepare($sql);
    $stmt->bind_param(
        "sisdisi",
        $product_name,
        $supplier_id,
        $category,
        $price,
        $stock,
        $status,
        $product_id
    );

    if ($stmt->execute()) {
        header("Location: ../Pages/products.php?updated=1");
    } else {
        header("Location: ../Pages/products.php?error=1");
    }
}
