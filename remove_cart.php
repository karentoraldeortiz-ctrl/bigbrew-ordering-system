<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['cart_item_id'])) {
    $cart_item_id = (int) $_GET['cart_item_id'];
    $user_id      = $_SESSION['user_id'];

    mysqli_query($conn,
        "DELETE ci FROM cart_items ci
         JOIN cart c ON ci.cart_id = c.cart_id
         WHERE ci.cart_item_id='$cart_item_id'
           AND c.user_id='$user_id'"
    );
}

header("Location: cart.php");
exit;
?>