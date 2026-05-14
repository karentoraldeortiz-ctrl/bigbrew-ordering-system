<?php
session_start();
if (!isset($_SESSION['staff_logged_in'])) {
    http_response_code(401);
    exit;
}
include "../db.php";

date_default_timezone_set('Asia/Manila');

function getPickupDisplay($pickup_value, $created_at) {
    $pickup_value = trim($pickup_value);
    $created_at_time = !empty($created_at) ? strtotime($created_at) : time();

    if ($pickup_value === 'asap') {
        $start_time = date('g:i A', strtotime('+15 minutes', $created_at_time));
        $end_time   = date('g:i A', strtotime('+30 minutes', $created_at_time));
        return "ASAP ({$start_time} - {$end_time})";
    }

    $pickup_labels = [
        'in-15-min'   => 'In 15 minutes',
        'in-30-min'   => 'In 30 minutes',
        'in-45-min'   => 'In 45 minutes',
        'in-1-hour'   => 'In 1 hour',
        'in-1-5-hour' => 'In 1 hour 30 minutes',
        'in-2-hours'  => 'In 2 hours',
    ];

    return $pickup_labels[$pickup_value] ?? $pickup_value;
}

$filter = isset($_GET['filter']) && $_GET['filter'] === 'pending' ? "WHERE o.order_status = 'pending'" : "";

$orders_q = mysqli_query($conn,
    "SELECT o.order_id, o.order_status, o.total_amount, o.pickup_time, o.notes, o.created_at, u.full_name
     FROM orders o
     JOIN users u ON o.user_id = u.user_id
     $filter
     ORDER BY o.created_at DESC"
);

$orders = [];
while ($order = mysqli_fetch_assoc($orders_q)) {
    $oid = $order['order_id'];

    $items_q = mysqli_query($conn,
        "SELECT p.product_name, ps.size_name, oi.quantity, oi.unit_price, oi.addons
         FROM order_items oi
         LEFT JOIN products p ON oi.product_id = p.product_id
         LEFT JOIN product_sizes ps ON oi.size_id = ps.size_id
         WHERE oi.order_id = '$oid'"
    );

    $items = [];
    while ($item = mysqli_fetch_assoc($items_q)) {
        $items[] = $item;
    }

    $orders[] = [
        'order_id'       => $oid,
        'order_status'   => $order['order_status'],
        'total_amount'   => $order['total_amount'],
        'pickup_display' => getPickupDisplay($order['pickup_time'], $order['created_at']),
        'notes'          => $order['notes'],
        'created_at'     => date('M j, Y · g:i A', strtotime($order['created_at'])),
        'full_name'      => $order['full_name'],
        'items'          => $items,
    ];
}

header('Content-Type: application/json');
echo json_encode($orders);
mysqli_close($conn);
?>