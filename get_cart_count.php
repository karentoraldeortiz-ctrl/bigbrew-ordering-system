<?php
session_start();
include "db.php";

// GUEST — count from session
if (!isset($_SESSION['user_id'])) {
    $count = 0;
    if (isset($_SESSION['guest_cart']) && is_array($_SESSION['guest_cart'])) {
        foreach ($_SESSION['guest_cart'] as $item) {
            $count += (int)($item['quantity'] ?? 1);
        }
    }
    echo json_encode(['count' => $count]);
    exit;
}

// LOGGED IN — count from DB
$user_id = $_SESSION['user_id'];
$result = mysqli_query($conn,
    "SELECT SUM(ci.quantity) as total 
     FROM cart_items ci
     JOIN cart c ON ci.cart_id = c.cart_id
     WHERE c.user_id = $user_id"
);
$row = mysqli_fetch_assoc($result);
echo json_encode(['count' => (int)($row['total'] ?? 0)]);
?>