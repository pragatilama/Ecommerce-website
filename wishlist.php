<?php
session_start();
include ("partial-front/header.php"); // your DB connection file

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

if (isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);

    // Prevent duplicates
    $check = $conn->prepare("SELECT * FROM wish_list WHERE customer_id = ? AND product_id = ?");
    $check->bind_param("ii", $customer_id, $product_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO wish_list (customer_id, product_id, added_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $customer_id, $product_id);
        $stmt->execute();
        $stmt->close();
    }

    $check->close();
}

// Redirect to wishlist view
header("Location: wishlist-view.php");
exit();
?>