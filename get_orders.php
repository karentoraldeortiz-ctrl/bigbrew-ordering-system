<?php
// ============================================================
// get_orders.php
// Returns the logged-in user's order history as JSON
// Called via fetch() from account.js
// ============================================================
session_start();
include "db.php";

header('Content-Type: application/json');

// DEBUG: uncomment these lines if you want to see session contents
// error_log(print_r($_SESSION, true));

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in', 'debug' => 'No user_id in session']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Fetch all orders of this user, latest first
$orders_q = mysqli_query($conn,
    "SELECT order_id, total_amount, pickup_time, order_status, created_at, notes
     FROM orders
     WHERE user_id = '$user_id'
     ORDER BY created_at DESC"
);

if (!$orders_q) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . mysqli_error($conn)]);
    exit;
}

$orders = [];

while ($order = mysqli_fetch_assoc($orders_q)) {
    $oid = (int) $order['order_id'];

    // Fetch items for this order
    $items_q = mysqli_query($conn,
        "SELECT p.product_name, p.category, ps.size_name, oi.quantity, oi.unit_price, oi.addons
         FROM order_items oi
         JOIN products p ON oi.product_id = p.product_id
         JOIN product_sizes ps ON oi.size_id = ps.size_id
         WHERE oi.order_id = '$oid'"
    );

    $items = [];
    if ($items_q) {
        while ($item = mysqli_fetch_assoc($items_q)) {
            $items[] = $item;
        }
    }

    $order['items'] = $items;

// Check if already reviewed
// Check if user already reviewed
$rev_q = mysqli_query($conn,
    "SELECT review_id FROM reviews WHERE user_id = '$user_id'"
);
$order['reviewed'] = mysqli_num_rows($rev_q) > 0;

$orders[] = $order;
}

echo json_encode(['success' => true, 'orders' => $orders]);
mysqli_close($conn);
?>