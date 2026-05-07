<?php
// ============================================================
// update_qty.php
// Tinatawag ng cart.js via fetch (AJAX) — walang HTML dito
// Dalawang action ang kaya nito:
//   1. "update" — dagdagan o bawasan ang quantity
//   2. "remove" — burahin ang item completely
// ============================================================
session_start();
include "db.php";

header('Content-Type: application/json');

// Guard: kailangan naka-login
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id      = $_SESSION['user_id'];
$cart_item_id = intval($_POST['cart_item_id']);
$action       = $_POST['action']; // "update" o "remove"

// ============================================================
// VERIFY: Siguraduhing ang item ay sa current user talaga
// Ito ay pangproteksyon — bawal baguhin ng isa ang cart ng isa
// ============================================================
$verify = mysqli_query($conn,
    "SELECT ci.cart_item_id, ci.quantity
     FROM cart_items ci
     JOIN cart c ON ci.cart_id = c.cart_id
     WHERE ci.cart_item_id = '$cart_item_id'
       AND c.user_id = '$user_id'"
);

if(mysqli_num_rows($verify) === 0) {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    exit;
}

$item = mysqli_fetch_assoc($verify);
$current_qty = $item['quantity'];

// ============================================================
// ACTION: remove — tanggalin ang item completely
// ============================================================
if($action === 'remove') {
    mysqli_query($conn,
        "DELETE FROM cart_items WHERE cart_item_id = '$cart_item_id'"
    );
    echo json_encode(['success' => true, 'action' => 'removed']);
    exit;
}

// ============================================================
// ACTION: update — dagdagan (+1) o bawasan (-1) ang quantity
// ============================================================
if($action === 'update') {
    $change      = intval($_POST['change']); // +1 o -1 lang ang valid
    $new_qty     = $current_qty + $change;

    if($new_qty <= 0) {
        // Naging zero na — tanggalin na ang item
        mysqli_query($conn,
            "DELETE FROM cart_items WHERE cart_item_id = '$cart_item_id'"
        );
        echo json_encode(['success' => true, 'action' => 'removed', 'new_qty' => 0]);
    } else {
        // I-update lang ang quantity
        mysqli_query($conn,
            "UPDATE cart_items SET quantity = '$new_qty'
             WHERE cart_item_id = '$cart_item_id'"
        );
        echo json_encode(['success' => true, 'action' => 'updated', 'new_qty' => $new_qty]);
    }
    exit;
}

// Kung unknown ang action
echo json_encode(['success' => false, 'message' => 'Unknown action']);
exit;
?>