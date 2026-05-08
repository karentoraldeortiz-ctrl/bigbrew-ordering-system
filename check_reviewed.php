<?php
session_start();
include "db.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['has_orders' => false, 'already_reviewed' => false]);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$order_check = mysqli_query($conn,
    "SELECT order_id FROM orders WHERE user_id = '$user_id' LIMIT 1"
);
$has_orders = mysqli_num_rows($order_check) > 0;

$rev_check = mysqli_query($conn,
    "SELECT review_id FROM reviews WHERE user_id = '$user_id' LIMIT 1"
);
$already_reviewed = mysqli_num_rows($rev_check) > 0;

echo json_encode([
    'has_orders' => $has_orders,
    'already_reviewed' => $already_reviewed
]);
mysqli_close($conn);
?>