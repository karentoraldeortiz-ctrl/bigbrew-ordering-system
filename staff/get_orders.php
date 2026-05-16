<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

$user_id  = (int) $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

if (!$order_id) {
    echo json_encode(['error' => 'invalid_order']);
    exit;
}

$q = mysqli_query($conn,
    "SELECT order_status, gcash_receipt_status, gcash_rejection_reason, gcash_downpayment, total_amount
     FROM orders
     WHERE order_id = '$order_id' AND user_id = '$user_id'
     LIMIT 1"
);

if (mysqli_num_rows($q) === 0) {
    echo json_encode(['error' => 'not_found']);
    exit;
}

$row = mysqli_fetch_assoc($q);
echo json_encode([
    'order_status'          => $row['order_status'],
    'gcash_receipt_status'  => $row['gcash_receipt_status'],
    'gcash_rejection_reason'=> $row['gcash_rejection_reason'],
    'gcash_downpayment'     => $row['gcash_downpayment'],
    'total_amount'          => $row['total_amount'],
]);
?>