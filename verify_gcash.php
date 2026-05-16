<?php
session_start();
if (!isset($_SESSION['staff_logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
include "../db.php";
header('Content-Type: application/json');

$data     = json_decode(file_get_contents('php://input'), true);
$order_id = isset($data['order_id']) ? (int) $data['order_id'] : 0;
$action   = isset($data['action']) ? $data['action'] : ''; // 'accept' or 'reject'
$reason   = isset($data['reason']) ? mysqli_real_escape_string($conn, $data['reason']) : '';

if (!$order_id || !in_array($action, ['accept', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Fetch order to validate it's pending_verification
$order_q = mysqli_query($conn,
    "SELECT order_id, gcash_receipt_status, gcash_receipt, user_id
     FROM orders WHERE order_id = '$order_id' LIMIT 1"
);

if (mysqli_num_rows($order_q) === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

$order = mysqli_fetch_assoc($order_q);

if ($order['gcash_receipt_status'] !== 'pending_verification') {
    echo json_encode(['success' => false, 'message' => 'Receipt is not pending verification']);
    exit;
}

if ($action === 'accept') {
    mysqli_query($conn,
        "UPDATE orders
         SET gcash_receipt_status = 'verified',
             order_status = 'pending'
         WHERE order_id = '$order_id'"
    );
    echo json_encode(['success' => true, 'message' => 'Receipt accepted. Order is now active.']);

} elseif ($action === 'reject') {
    mysqli_query($conn,
        "UPDATE orders
         SET gcash_receipt_status = 'rejected',
             gcash_rejection_reason = '$reason',
             order_status = 'pending'
         WHERE order_id = '$order_id'"
    );
    echo json_encode(['success' => true, 'message' => 'Receipt rejected. Customer will be notified.']);
}

mysqli_close($conn);
?>