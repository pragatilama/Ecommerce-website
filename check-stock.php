<?php
include('config/constants.php');

$product_id = intval($_GET['product_id'] ?? 0);

if ($product_id <= 0) {
    echo json_encode(['available' => 0]);
    exit;
}

$query = "SELECT remaining_quantity FROM stock WHERE product_id = $product_id";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $stock = mysqli_fetch_assoc($result);
    echo json_encode(['available' => $stock['remaining_quantity']]);
} else {
    echo json_encode(['available' => 0]);
}
?>