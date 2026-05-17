<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id'])) {
    header("Location: account.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = intval($_POST['order_id']);

$order_q = mysqli_query($conn,
    "SELECT order_status FROM orders 
     WHERE order_id = '$order_id' 
     AND user_id = '$user_id'
     LIMIT 1"
);

if(mysqli_num_rows($order_q) === 0) {
    header("Location: account.php");
    exit;
}

$order = mysqli_fetch_assoc($order_q);

if($order['order_status'] === 'pending') {
    mysqli_query($conn, "UPDATE orders SET 
        order_status = 'cancelled',
        cancelled_by = 'customer',
        cancel_reason_customer = '$cancel_reason'
        WHERE order_id = '$order_id' AND user_id = '$user_id'"
    );
}

header("Location: receipt.php?order_id=" . $order_id);
exit;
?>