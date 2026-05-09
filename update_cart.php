<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data         = json_decode(file_get_contents('php://input'), true);
$cart_item_id = intval($data['cart_item_id']);
$quantity     = intval($data['quantity']);

if ($quantity <= 0) {
    $sql = "DELETE FROM cart_items WHERE cart_item_id = $cart_item_id";
} else {
    $sql = "UPDATE cart_items SET quantity = $quantity WHERE cart_item_id = $cart_item_id";
}

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}

mysqli_close($conn);
?>