<?php
session_start();
include "db.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['already_reviewed' => false]);
  exit;
}

$user_id = (int) $_SESSION['user_id'];
$check = mysqli_query($conn, "SELECT review_id FROM reviews WHERE user_id = '$user_id'");
$already_reviewed = mysqli_num_rows($check) > 0;

echo json_encode(['already_reviewed' => $already_reviewed]);
?>