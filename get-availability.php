<?php
include "db.php";

$result = mysqli_query($conn, "SELECT product_id, is_available FROM products");
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[$row['product_id']] = (int) $row['is_available'];
}

header('Content-Type: application/json');
echo json_encode($data);