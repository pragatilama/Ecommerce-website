<?php
session_start();
include ("partial-front/header.php");

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

if (isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);

    $stmt = $conn->prepare("DELETE FROM wish_list WHERE customer_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $customer_id, $product_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: wishlist-view.php");
exit();
?>