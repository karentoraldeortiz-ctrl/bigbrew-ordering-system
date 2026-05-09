<?php
session_start();
if (!isset($_SESSION['staff_logged_in'])) {
    http_response_code(401);
    exit;
}
include "../db.php";
 
$since = isset($_GET['since']) ? mysqli_real_escape_string($conn, $_GET['since']) : date('Y-m-d H:i:s');
 
$q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders WHERE created_at > '$since' AND order_status = 'pending'");
$row = mysqli_fetch_assoc($q);
 
header('Content-Type: application/json');
echo json_encode(['new_orders' => (int)$row['cnt']]);
?>