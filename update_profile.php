<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];

$name     = mysqli_real_escape_string($conn, $data['name']);
$email    = mysqli_real_escape_string($conn, $data['email']);
$phone    = mysqli_real_escape_string($conn, $data['phone']);
$birthday = mysqli_real_escape_string($conn, $data['birthday']);

$query = mysqli_query($conn,
    "UPDATE users SET 
        full_name = '$name',
        email = '$email',
        phone_num = '$phone',
        birthday = '$birthday'
     WHERE user_id = '$user_id'"
);

if ($query) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>