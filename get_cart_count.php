<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

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