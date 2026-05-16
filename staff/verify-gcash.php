<?php
session_start();
if (!isset($_SESSION['staff_logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include "../db.php";
header('Content-Type: application/json');

// Parse JSON body
$data     = json_decode(file_get_contents('php://input'), true);
$order_id = isset($data['order_id']) ? (int) $data['order_id'] : 0;
$action   = isset($data['action'])   ? trim($data['action'])   : '';
$reason   = isset($data['reason'])   ? trim($data['reason'])   : '';

if (!$order_id || !in_array($action, ['accept', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// Fetch order — make sure it exists and is pending_verification
$order_q = mysqli_query($conn,
    "SELECT order_id, user_id, gcash_receipt_status, order_status
     FROM orders WHERE order_id = '$order_id'"
);

if (mysqli_num_rows($order_q) === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found.']);
    exit;
}

$order = mysqli_fetch_assoc($order_q);

if ($order['gcash_receipt_status'] !== 'pending_verification') {
    echo json_encode(['success' => false, 'message' => 'Receipt is not pending verification.']);
    exit;
}

$uid        = $order['user_id'];
$order_code = str_pad($order_id, 3, '0', STR_PAD_LEFT);

if ($action === 'accept') {
    $ok = mysqli_query($conn,
        "UPDATE orders
         SET gcash_receipt_status = 'verified',
             gcash_rejection_reason = NULL,
             order_status = 'preparing'
         WHERE order_id = '$order_id'"
    );

    if ($ok) {
        $notif_title = "GCash Payment Verified ✅";
        $notif_msg   = "Your GCash downpayment for Order #$order_code has been verified! Your order is now being prepared.";
        mysqli_query($conn, "INSERT INTO notifications (user_id, order_id, title, message) VALUES ('$uid', '$order_id', '$notif_title', '$notif_msg')");

        echo json_encode(['success' => true, 'message' => 'Receipt verified successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    }

} elseif ($action === 'reject') {
    if (empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Rejection reason is required.']);
        exit;
    }

    $reason_esc = mysqli_real_escape_string($conn, $reason);

    $ok = mysqli_query($conn,
        "UPDATE orders
         SET gcash_receipt_status = 'rejected',
             gcash_rejection_reason = '$reason_esc'
         WHERE order_id = '$order_id'"
    );

    if ($ok) {
        $notif_title = "GCash Payment Rejected ❌";
        $notif_msg   = "Your GCash downpayment for Order #$order_code was rejected. Please re-upload your receipt.";
        mysqli_query($conn, "INSERT INTO notifications (user_id, order_id, title, message) VALUES ('$uid', '$order_id', '$notif_title', '$notif_msg')");

        echo json_encode(['success' => true, 'message' => 'Receipt rejected.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    }
}