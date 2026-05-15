<?php
session_start();
if (!isset($_SESSION['staff_logged_in'])) { http_response_code(401); exit; }
include "../db.php";

$filter = isset($_GET['filter']) && $_GET['filter'] === 'pending' ? "WHERE o.order_status = 'pending'" : "";
$orders_q = mysqli_query($conn,
    "SELECT o.order_id, o.order_status
     FROM orders o
     $filter
     ORDER BY o.created_at DESC"
);

$orders = [];
while ($row = mysqli_fetch_assoc($orders_q)) {
    $orders[] = $row;
}

header('Content-Type: application/json');
echo json_encode($orders);