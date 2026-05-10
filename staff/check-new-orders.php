<?php
session_start();
if (!isset($_SESSION['staff_logged_in'])) {
    http_response_code(401);
    exit;
}
include "../db.php";

$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

$q = mysqli_query($conn, "SELECT MAX(order_id) as new_order_id FROM orders WHERE order_id > '$last_id' AND order_status = 'pending'");
$row = mysqli_fetch_assoc($q);

header('Content-Type: application/json');
echo json_encode([
    'new_order_id' => $row['new_order_id'] ? (int)$row['new_order_id'] : null
]);
?>