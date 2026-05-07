<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT 
            ci.cart_item_id,
            ci.product_id,
            ci.quantity,
            ci.addons,
            ci.unit_price,
            ci.size_id,
            ps.size_name,
            ps.price AS size_price,
            p.product_name,
            p.image
        FROM cart_items ci
        JOIN cart c ON ci.cart_id = c.cart_id
        JOIN products p ON ci.product_id = p.product_id
        JOIN product_sizes ps ON ci.size_id = ps.size_id
        WHERE c.user_id = $user_id";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    exit;
}

$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
}

echo json_encode(['success' => true, 'items' => $items]);
mysqli_close($conn);
?>