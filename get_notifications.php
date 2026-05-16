<?php
session_start();
include "db.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0, 'notifications' => []]);
    exit;
}

$uid = (int) $_SESSION['user_id'];

$result = mysqli_query($conn,
    "SELECT * FROM notifications WHERE user_id = '$uid' ORDER BY created_at DESC LIMIT 20"
);

$notifs = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notifs[] = $row;
}

$unread = mysqli_query($conn,
    "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = '$uid' AND is_read = 0"
);
$unread_count = mysqli_fetch_assoc($unread)['cnt'];

echo json_encode(['count' => $unread_count, 'notifications' => $notifs]);