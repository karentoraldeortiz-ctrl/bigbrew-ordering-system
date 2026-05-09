<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data        = json_decode(file_get_contents('php://input'), true);
$user_id     = $_SESSION['user_id'];
$pickup_time = mysqli_real_escape_string($conn, $data['pickup_time']);
$total       = floatval($data['total']);
$order_id    = rand(100000000, 999999999);

// Get user full_name
$userResult = mysqli_query($conn, "SELECT full_name FROM users WHERE user_id = $user_id LIMIT 1");
$user       = mysqli_fetch_assoc($userResult);
$full_name  = $user ? $user['full_name'] : 'Brew';

// Get cart_id
$cartResult = mysqli_query($conn, "SELECT cart_id FROM cart WHERE user_id = $user_id LIMIT 1");
if (mysqli_num_rows($cartResult) === 0) {
    echo json_encode(['success' => false, 'message' => 'Cart not found']);
    exit;
}
$cart_id = mysqli_fetch_assoc($cartResult)['cart_id'];

// Get cart items before clearing
$itemsResult = mysqli_query($conn,
    "SELECT 
        ci.cart_item_id,
        ci.quantity,
        ci.addons,
        ci.unit_price,
        ps.size_name,
        p.product_name,
        p.image
     FROM cart_items ci
     JOIN products p ON ci.product_id = p.product_id
     JOIN product_sizes ps ON ci.size_id = ps.size_id
     WHERE ci.cart_id = $cart_id"
);

$items = [];
while ($row = mysqli_fetch_assoc($itemsResult)) {
    $items[] = $row;
}

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

// Save to orders table
$sql = "INSERT INTO orders (order_id, user_id, total_amount, pickup_time, order_status)
        VALUES ($order_id, $user_id, $total, '$pickup_time', 'pending')";

if (!mysqli_query($conn, $sql)) {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    exit;
}

// Save items to session para ma-display sa receipt.php
$_SESSION['last_order_items'] = $items;

// Clear cart items after checkout
mysqli_query($conn, "DELETE FROM cart_items WHERE cart_id = $cart_id");

echo json_encode([
    'success'   => true,
    'order_id'  => $order_id,
    'full_name' => $full_name,
    'items'     => $items,
    'total'     => $total,
    'pickup'    => $pickup_time
]);

mysqli_close($conn);
?>