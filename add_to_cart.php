<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$isLoggedIn = isset($_SESSION['user_id']);

$product_id  = intval($_POST['product_id'] ?? 0);
$size_id     = intval($_POST['size_id'] ?? 0);
$quantity    = intval($_POST['quantity'] ?? 1);
$addons_json = $_POST['addons'] ?? '[]';
$unit_price  = floatval($_POST['unit_price'] ?? 0);

if(!$product_id || !$size_id || $quantity < 1 || $unit_price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$addons_array = json_decode($addons_json, true);

if(!is_array($addons_array)) {
    $addons_array = [];
}

$addons_str = implode(', ', $addons_array);

/* ============================================================
   IF LOGGED IN: SAVE TO DATABASE CART
============================================================ */
if($isLoggedIn) {
    $user_id = $_SESSION['user_id'];

    $cart_check = mysqli_query($conn,
        "SELECT cart_id FROM cart WHERE user_id = '$user_id'"
    );

    if(mysqli_num_rows($cart_check) > 0) {
        $cart = mysqli_fetch_assoc($cart_check);
        $cart_id = $cart['cart_id'];
    } else {
        mysqli_query($conn, "INSERT INTO cart (user_id) VALUES ('$user_id')");
        $cart_id = mysqli_insert_id($conn);
    }

    $safe_addons = mysqli_real_escape_string($conn, $addons_str);

    $item_check = mysqli_query($conn,
        "SELECT cart_item_id, quantity FROM cart_items
         WHERE cart_id    = '$cart_id'
           AND product_id = '$product_id'
           AND size_id    = '$size_id'
           AND addons     = '$safe_addons'"
    );

    if(mysqli_num_rows($item_check) > 0) {
        $existing = mysqli_fetch_assoc($item_check);
        $new_qty  = $existing['quantity'] + $quantity;

        mysqli_query($conn,
            "UPDATE cart_items
             SET quantity = '$new_qty'
             WHERE cart_item_id = '{$existing['cart_item_id']}'"
        );
    } else {
        mysqli_query($conn,
            "INSERT INTO cart_items 
            (cart_id, product_id, size_id, addons, quantity, unit_price)
            VALUES 
            ('$cart_id', '$product_id', '$size_id', '$safe_addons', '$quantity', '$unit_price')"
        );
    }

    echo json_encode([
        'success' => true,
        'message' => 'Item added to cart!'
    ]);
    exit;
}

/* ============================================================
   IF GUEST: SAVE TO SESSION CART
============================================================ */

if(!isset($_SESSION['guest_cart'])) {
    $_SESSION['guest_cart'] = [];
}

$cart_key = $product_id . '_' . $size_id . '_' . md5($addons_str);

if(isset($_SESSION['guest_cart'][$cart_key])) {
    $_SESSION['guest_cart'][$cart_key]['quantity'] += $quantity;
} else {
    $_SESSION['guest_cart'][$cart_key] = [
        'product_id'  => $product_id,
        'size_id'     => $size_id,
        'addons'      => $addons_str,
        'quantity'    => $quantity,
        'unit_price'  => $unit_price
    ];
}

echo json_encode([
    'success' => true,
    'message' => 'Item added to cart!'
]);

exit;
?>