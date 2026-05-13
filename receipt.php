<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if(!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: account.php");
    exit;
}

$user_id  = $_SESSION['user_id'];
$order_id = (int)$_GET['order_id'];

$order_q = mysqli_query($conn,
    "SELECT * FROM orders WHERE order_id = '$order_id' AND user_id = '$user_id'"
);

if(mysqli_num_rows($order_q) === 0) {
    header("Location: account.php");
    exit;
}

$order = mysqli_fetch_assoc($order_q);
$user_q = mysqli_query($conn,
    "SELECT full_name FROM users WHERE user_id = '$user_id' LIMIT 1"
);

$user = mysqli_fetch_assoc($user_q);
$customer_name = $user ? $user['full_name'] : ($_SESSION['name'] ?? 'Customer');
$items_q = mysqli_query($conn,
    "SELECT oi.quantity, oi.unit_price, oi.addons,
            p.product_name,
            ps.size_name
     FROM order_items oi
     JOIN products p       ON oi.product_id = p.product_id
     JOIN product_sizes ps ON oi.size_id    = ps.size_id
     WHERE oi.order_id = '$order_id'"
);

$order_items = [];
while($row = mysqli_fetch_assoc($items_q)) {
    $order_items[] = $row;
}

date_default_timezone_set('Asia/Manila');

$pickup_value = trim($order['pickup_time']);
$created_at = !empty($order['created_at']) ? strtotime($order['created_at']) : time();

if($pickup_value === 'asap') {
    $start_time = date('g:i A', strtotime('+15 minutes', $created_at));
    $end_time   = date('g:i A', strtotime('+30 minutes', $created_at));

    $pickup_display = "ASAP ({$start_time} - {$end_time})";
} else {
    $pickup_labels = [
        'in-15-min'   => 'In 15 minutes',
        'in-30-min'   => 'In 30 minutes',
        'in-45-min'   => 'In 45 minutes',
        'in-1-hour'   => 'In 1 hour',
        'in-1-5-hour' => 'In 1 hour 30 minutes',
    ];

    $pickup_display = $pickup_labels[$pickup_value] ?? $pickup_value;
}?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BigBrew | Receipt #<?php echo $order_id; ?></title>
    <link rel="stylesheet" href="css/receipt.css" />
    <link rel="stylesheet" href="css/global.css">
    <link rel="shortcut icon" href="assets/logo/logo-black.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

</head>
<body>

    <!-- TOP BAR -->
    <div class="topbar">
        <a href="javascript:history.back()" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i>
</a>
    </div>

    <!-- RECEIPT CARD -->
<div class="receipt-card">
            
<div class="receipt-header">
    <div class="check-circle">
        <i class="fa-solid fa-check"></i>
    </div>

    <?php
    $status = strtolower($order['order_status']);

    if($status === 'pending') {
        $receipt_title = 'Order Confirmed!';
        $receipt_subtitle = 'Your order has been received and is waiting to be prepared.';
    }
    elseif($status === 'preparing') {
        $receipt_title = 'Drink is Being Prepared!';
        $receipt_subtitle = 'Our staff is currently preparing your beverages.';
    }
    elseif($status === 'completed') {
        $receipt_title = 'Ready for Pickup!';
        $receipt_subtitle = 'Your order is ready. Please proceed to the store for pickup.';
    }
    elseif($status === 'cancelled') {
        $receipt_title = 'Order Cancelled';
        $receipt_subtitle = 'This order has been cancelled.';
    }
    else {
        $receipt_title = 'Order Updated';
        $receipt_subtitle = 'Your order status has been updated.';
    }
    ?>

    <div>
        <h2 class="receipt-title">
            <?php echo $receipt_title; ?>
        </h2>
        <p class="receipt-subtitle">
            <?php echo $receipt_subtitle; ?>
        </p>
    </div>
</div>            

    <div class="receipt-main-box">
        <div class="receipt-id-row">
            <span>Order ID</span>
            <strong># <?php echo $order_id; ?></strong>
        </div>

        <p class="section-label">Customer Details</p>

        <div class="detail-line">
            <span>Name:</span>
            <strong><?php echo htmlspecialchars($customer_name); ?></strong>
        </div>

        <div class="detail-line">
            <span>Mode of Payment:</span>
            <strong>Pay on Pickup</strong>
        </div>

        <div class="detail-line">
            <span>Self Pick-up:</span>
            <strong><?php echo htmlspecialchars($pickup_display ?? 'ASAP'); ?></strong>
        </div>

        <?php if(!empty($order['notes'])): ?>
        <div class="detail-line">
            <span>Notes:</span>
            <strong><?php echo htmlspecialchars($order['notes']); ?></strong>
        </div>
        <?php endif; ?>

        <hr>

        <p class="section-label">Items</p>

        <?php foreach($order_items as $item): ?>
        <div class="receipt-item-row">
            <span>
                <?php echo htmlspecialchars($item['product_name']); ?>
                x <?php echo $item['quantity']; ?>
            </span>

            <strong>
                P <?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?>
            </strong>
        </div>

        <?php if(!empty($item['addons'])): ?>
            <p class="item-addons">
                Add-ons: <?php echo htmlspecialchars($item['addons']); ?>
            </p>
        <?php endif; ?>
        <?php endforeach; ?>

        <div class="total-row">
            <span>Total</span>
            <strong>P <?php echo number_format($order['total_amount'], 2); ?></strong>
        </div>
    </div>

    <div class="pickup-box">
        <h4>Pickup Instructions</h4>

        <ol>
            <li>
                <span>Pick up your order at <?php echo htmlspecialchars($pickup_display ?? 'ASAP'); ?></span>
                <p>
                    Please claim your order within 30 minutes. Should you arrive late,
                    beverages may not be remade.
                </p>
            </li>

            <li>
                <span>Show your order ID at the counter</span>
            </li>

            <li>
                <span>Enjoy your drinks!</span>
            </li>
        </ol>
    </div>

    <?php if($order['order_status'] === 'pending'): ?>
        <form method="POST" action="cancel_order.php" onsubmit="return confirm('Are you sure you want to cancel this order?');">
            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
            <button type="submit" class="btn-cancel-order">Cancel Order</button>
        </form>
    <?php else: ?>
        <p class="order-status-note">
            Order status: <?php echo htmlspecialchars(ucfirst($order['order_status'])); ?>
        </p>
    <?php endif; ?>


</div>
</body>
</html>