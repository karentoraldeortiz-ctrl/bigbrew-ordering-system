<?php
// ============================================================
// add_to_cart.php
// Walang HTML dito — pure logic lang
// Tinatawag ito ng JavaScript (fetch/AJAX) galing sa modal
// Nagre-return ng JSON response para malaman ng JS kung okay
// ============================================================
session_start();
include "db.php";

// Header para malaman ng JS na JSON ang ibabalik natin
header('Content-Type: application/json');

// GUARD: kailangan naka-login
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// GUARD: kailangan POST request
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// ============================================================
// Kunin ang data galing sa modal form (pinapadala ng JS)
// ============================================================
$user_id    = $_SESSION['user_id'];
$product_id = intval($_POST['product_id']);
$size_id    = intval($_POST['size_id']);
$quantity   = intval($_POST['quantity']);
$addons_json = $_POST['addons'] ?? '[]';  // array ng addon names
$unit_price = floatval($_POST['unit_price']); // total price ng isang item (size + addons)

// Basic validation
if(!$product_id || !$size_id || $quantity < 1 || $unit_price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// I-convert ang addons array sa string para sa DB
// Example: ["pearl", "coffee jelly"] → "pearl, coffee jelly"
$addons_array = json_decode($addons_json, true);

if(!is_array($addons_array)) {
    $addons_array = [];
}

$addons_str = implode(', ', $addons_array);
// ============================================================
// STEP 1: Hanapin kung may cart na ang user
// Bawat user ay may 1 cart lang sa database
// ============================================================
$cart_check = mysqli_query($conn,
    "SELECT cart_id FROM cart WHERE user_id = '$user_id'"
);

if(mysqli_num_rows($cart_check) > 0) {
    // May cart na — kunin ang cart_id
    $cart = mysqli_fetch_assoc($cart_check);
    $cart_id = $cart['cart_id'];
} else {
    // Wala pang cart — gumawa ng bago
    mysqli_query($conn, "INSERT INTO cart (user_id) VALUES ('$user_id')");
    $cart_id = mysqli_insert_id($conn);
    // mysqli_insert_id() = ID ng pinakabagong na-insert na row
}

// ============================================================
// STEP 2: I-check kung nandoon na ang exact same item
// (same product + same size + same addons)
// Kung oo, dagdagan na lang ang quantity
// ============================================================
$item_check = mysqli_query($conn,
    "SELECT cart_item_id, quantity FROM cart_items
     WHERE cart_id    = '$cart_id'
       AND product_id = '$product_id'
       AND size_id    = '$size_id'
       AND addons     = '$addons_str'"
);

if(mysqli_num_rows($item_check) > 0) {
    // Nandoon na — dagdagan ang quantity
    $existing = mysqli_fetch_assoc($item_check);
    $new_qty  = $existing['quantity'] + $quantity;
    mysqli_query($conn,
        "UPDATE cart_items
         SET quantity = '$new_qty'
         WHERE cart_item_id = '{$existing['cart_item_id']}'"
    );
} else {
    // Bago — mag-insert ng bagong row
    mysqli_query($conn,
        "INSERT INTO cart_items (cart_id, product_id, size_id, addons, quantity, unit_price)
         VALUES ('$cart_id', '$product_id', '$size_id', '$addons_str', '$quantity', '$unit_price')"
    );
}

// Ibalik ang success response sa JavaScript
echo json_encode([
    'success' => true,
    'message' => 'Item added to cart!'
]);
exit;
?>