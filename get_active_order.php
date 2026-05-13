<?php
session_start();
include "db.php";
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];

$order_q = mysqli_query($conn,
    "SELECT order_id, pickup_time, created_at, order_status
     FROM orders
     WHERE user_id = '$user_id'
     AND order_status IN ('pending', 'preparing', 'ready_for_pickup')
     ORDER BY created_at DESC
     LIMIT 1"
);

if(mysqli_num_rows($order_q) === 0) {
    echo json_encode(['success' => false]);
    exit;
}

$order = mysqli_fetch_assoc($order_q);

$pickup_value = trim($order['pickup_time']);
$created_at_time = !empty($order['created_at']) ? strtotime($order['created_at']) : time();

if($pickup_value === 'asap') {
    $start = date('g:i A', strtotime('+15 minutes', $created_at_time));
    $end   = date('g:i A', strtotime('+30 minutes', $created_at_time));
    $pickup_display = "Today, {$start} - {$end}";
} else {
    $minutes_map = [
        'in-30-min'   => 30,
        'in-45-min'   => 45,
        'in-1-hour'   => 60,
        'in-1-5-hour' => 90,
        'in-2-hours'  => 120
    ];

    if(isset($minutes_map[$pickup_value])) {
        $pickup_display = "Today, " . date('g:i A', strtotime('+' . $minutes_map[$pickup_value] . ' minutes', $created_at_time));
    } else {
        $pickup_display = $pickup_value;
    }
}

echo json_encode([
    'success' => true,
    'order_id' => $order['order_id'],
    'pickup_display' => $pickup_display,
    'status' => $order['order_status']
]);
?>