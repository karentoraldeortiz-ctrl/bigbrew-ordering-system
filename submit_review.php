<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();
include "db.php";
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$rating  = (int) ($data['rating'] ?? 0);
$comment = mysqli_real_escape_string($conn, $data['comment'] ?? '');

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating']);
    exit;
}

// Check kung nag-review na ang user (one review per user)
$check = mysqli_query($conn,
    "SELECT review_id FROM reviews WHERE user_id = '$user_id'"
);
if (mysqli_num_rows($check) > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already submitted a review!']);
    exit;
}

$q = mysqli_query($conn,
    "INSERT INTO reviews (user_id, rating, comment)
     VALUES ('$user_id', '$rating', '$comment')"
);

if (!$q) {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    exit;
}

echo json_encode(['success' => true]);
mysqli_close($conn);
?>