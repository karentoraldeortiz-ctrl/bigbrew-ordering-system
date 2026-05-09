<?php
session_start();
if (!isset($_SESSION['staff_logged_in'])) {
    http_response_code(401);
    exit;
}
include "../db.php";

$q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders WHERE order_status = 'pending'");
$row = mysqli_fetch_assoc($q);

header('Content-Type: application/json');
echo json_encode(['new_orders' => (int)$row['cnt']]);
?>