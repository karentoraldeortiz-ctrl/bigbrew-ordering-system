<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if(!isset($_POST['order_id']) || !is_numeric($_POST['order_id'])) {
    header("Location: account.php");
    exit;
}

$user_id  = $_SESSION['user_id'];
$order_id = (int)$_POST['order_id'];

// Verify na sa user talaga yung order
$order_check = mysqli_query($conn,
    "SELECT order_id FROM orders 
     WHERE order_id = '$order_id' AND user_id = '$user_id'"
);

if(mysqli_num_rows($order_check) === 0) {
    header("Location: account.php");
    exit;
}

// Kunin lahat ng items ng order
$items_q = mysqli_query($conn,
    "SELECT oi.product_id, oi.size_id, oi.quantity, oi.unit_price, oi.addons
     FROM order_items oi
     WHERE oi.order_id = '$order_id'"
);

if(mysqli_num_rows($items_q) === 0) {
    header("Location: cart.php");
    exit;
}

// Get or create cart
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

// I-add lahat ng items sa cart
while($item = mysqli_fetch_assoc($items_q)) {
    $product_id = intval($item['product_id']);
    $size_id    = intval($item['size_id']);
    $quantity   = intval($item['quantity']);
    $unit_price = floatval($item['unit_price']);
    $addons     = mysqli_real_escape_string($conn, $item['addons']);

    // Check kung available pa yung product at size
    $avail_check = mysqli_query($conn,
        "SELECT ps.size_id FROM product_sizes ps
         JOIN products p ON ps.product_id = p.product_id
         WHERE ps.size_id = '$size_id' AND p.is_available = 1"
    );

    if(mysqli_num_rows($avail_check) === 0) {
        continue; // Skip unavailable products
    }

    // Check kung existing na sa cart
    $item_check = mysqli_query($conn,
        "SELECT cart_item_id, quantity FROM cart_items
         WHERE cart_id    = '$cart_id'
           AND product_id = '$product_id'
           AND size_id    = '$size_id'
           AND addons     = '$addons'"
    );

    if(mysqli_num_rows($item_check) > 0) {
        // Update quantity na lang
        $existing = mysqli_fetch_assoc($item_check);
        $new_qty  = $existing['quantity'] + $quantity;
        mysqli_query($conn,
            "UPDATE cart_items 
             SET quantity = '$new_qty'
             WHERE cart_item_id = '{$existing['cart_item_id']}'"
        );
    } else {
        // Insert new
        mysqli_query($conn,
            "INSERT INTO cart_items 
             (cart_id, product_id, size_id, addons, quantity, unit_price)
             VALUES 
             ('$cart_id', '$product_id', '$size_id', '$addons', '$quantity', '$unit_price')"
        );
    }
}
$_SESSION['buy_again_order'] = $order_id;
header("Location: cart.php");
exit;
?>