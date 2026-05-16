<?php
session_start();
include "db.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$uid = (int) $_SESSION['user_id'];
mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = '$uid'");
echo json_encode(['success' => true]);