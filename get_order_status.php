<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$order_id = (int)$_GET['order_id'];
$user_id  = $_SESSION['user_id'];

// ── FIX: Added gcash_receipt_status to SELECT
$q = mysqli_query($conn,
    "SELECT order_status, gcash_receipt_status, completed_at, pickup_time, created_at
     FROM orders 
     WHERE order_id = '$order_id' AND user_id = '$user_id'
     LIMIT 1"
);

if(mysqli_num_rows($q) === 0) {
    echo json_encode(['error' => 'not found']);
    exit;
}

$order = mysqli_fetch_assoc($q);
date_default_timezone_set('Asia/Manila');

$status       = strtolower($order['order_status']);
$pickup_value = trim($order['pickup_time']);
$created_at   = !empty($order['created_at']) ? strtotime($order['created_at']) : time();

// Pickup display
if($status === 'completed') {
    $pickup_display = !empty($order['completed_at'])
        ? date('g:i A', strtotime($order['completed_at']))
        : date('g:i A', $created_at);
} elseif($pickup_value === 'asap') {
    $start = date('g:i A', strtotime('+15 minutes', $created_at));
    $end   = date('g:i A', strtotime('+30 minutes', $created_at));
    $pickup_display = "ASAP ({$start} - {$end})";
} else {
    $labels = [
        'in-30-min'   => 'In 30 minutes',
        'in-45-min'   => 'In 45 minutes',
        'in-1-hour'   => 'In 1 hour',
        'in-1-5-hour' => 'In 1 hour 30 minutes',
        'in-2-hours'  => 'In 2 hours',
    ];
    $pickup_display = $labels[$pickup_value] ?? $pickup_value;
}

// Titles
$titles = [
    'pending'          => ['title' => 'Order Confirmed!',         'subtitle' => 'Your order has been received and is waiting to be prepared.'],
    'preparing'        => ['title' => 'Drink is Being Prepared!', 'subtitle' => 'Our staff is currently preparing your beverages.'],
    'ready_for_pickup' => ['title' => 'Ready for Pickup!',        'subtitle' => 'Your order is ready. Please proceed to the store for pickup.'],
    'completed'        => ['title' => 'Order Completed',          'subtitle' => 'Thank you, Brew! Buy again soon.'],
    'cancelled'        => ['title' => 'Order Cancelled',          'subtitle' => 'This order has been cancelled.'],
];

$info = $titles[$status] ?? ['title' => 'Order Updated', 'subtitle' => 'Your order status has been updated.'];

// ── FIX: Return order_status and gcash_receipt_status as proper field names
echo json_encode([
    'order_status'         => $status,
    'gcash_receipt_status' => $order['gcash_receipt_status'] ?? 'not_required',
    'title'                => $info['title'],
    'subtitle'             => $info['subtitle'],
    'pickup_display'       => $pickup_display,
]);