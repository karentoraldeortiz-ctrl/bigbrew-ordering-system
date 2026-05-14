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
            p.product_name, p.category,
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

// DEFINE $status MUNA bago gamitin kahit saan
$status = strtolower($order['order_status']);

$pickup_value = trim($order['pickup_time']);
$created_at   = !empty($order['created_at']) ? strtotime($order['created_at']) : time();

// NGAYON safe na gamitin $status dito
if($status === 'completed') {
    if(!empty($order['completed_at'])) {
        $pickup_display = date('g:i A', strtotime($order['completed_at']));
    } else {
        // Fallback — older orders na wala pang completed_at
        $pickup_display = date('g:i A', strtotime($order['created_at']));
    }
} elseif($pickup_value === 'asap') {
    $start_time = date('g:i A', strtotime('+15 minutes', $created_at));
    $end_time   = date('g:i A', strtotime('+30 minutes', $created_at));
    $pickup_display = "ASAP ({$start_time} - {$end_time})";
} else {
    $pickup_labels = [
        'in-30-min'   => 'In 30 minutes',
        'in-45-min'   => 'In 45 minutes',
        'in-1-hour'   => 'In 1 hour',
        'in-1-5-hour' => 'In 1 hour 30 minutes',
        'in-2-hours'  => 'In 2 hours',
    ];
    $pickup_display = $pickup_labels[$pickup_value] ?? $pickup_value;
}
// RECEIPT TITLE — define dito na rin para malinis
if($status === 'pending') {
    $receipt_title    = 'Order Confirmed!';
    $receipt_subtitle = 'Your order has been received and is waiting to be prepared.';
} elseif($status === 'preparing') {
    $receipt_title    = 'Drink is Being Prepared!';
    $receipt_subtitle = 'Our staff is currently preparing your beverages.';
} elseif($status === 'ready_for_pickup') {
    $receipt_title    = 'Ready for Pickup!';
    $receipt_subtitle = 'Your order is ready. Please proceed to the store for pickup.';
} elseif($status === 'completed') {
    $receipt_title    = 'Order Completed';
    $receipt_subtitle = 'Thank you, Brew! Buy again soon.';
} elseif($status === 'cancelled') {
    $receipt_title    = 'Order Cancelled';
    $receipt_subtitle = 'This order has been cancelled.';
} else {
    $receipt_title    = 'Order Updated';
    $receipt_subtitle = 'Your order status has been updated.';
}
?>
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
<div class="check-circle <?php echo $status; ?>">
    <?php
    if($status === 'pending')           echo '<i class="fa-solid fa-clock"></i>';
    elseif($status === 'preparing')     echo '<i class="fa-solid fa-blender"></i>';
    elseif($status === 'ready_for_pickup') echo '<i class="fa-solid fa-bell"></i>';
    elseif($status === 'completed')     echo '<i class="fa-solid fa-circle-check"></i>';
    elseif($status === 'cancelled')     echo '<i class="fa-solid fa-circle-xmark"></i>';
    else                                echo '<i class="fa-solid fa-check"></i>';
    ?>
</div>

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
            <strong>Pay upon Pickup</strong>
        </div>

        <div class="detail-line">
            <span>
                <?php 
                if($status === 'completed') echo 'Picked up at:';
                else echo 'Self Pick-up:';
                ?>
            </span>            
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
                    <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                    <em class="item-category">
                        <?php echo ucwords(str_replace('-', ' ', $item['category'])); ?> 
                        · <?php echo htmlspecialchars($item['size_name']); ?>
                    </em>
                    x<?php echo $item['quantity']; ?>
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

        <div class="receipt-actions">
            <?php if($status === 'pending'): ?>
                <!-- PENDING: Cancel lang -->
                <form method="POST" action="cancel_order.php" 
                    onsubmit="return confirm('Are you sure you want to cancel this order?');">
                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                    <button type="submit" class="btn-cancel-order btn-full">Cancel Order</button>
                </form>

            <?php elseif($status === 'preparing' || $status === 'ready_for_pickup'): ?>
                <!-- PREPARING / READY: disabled Cancel lang -->
                <button class="btn-cancel-order btn-full btn-disabled-cancel" 
                            disabled
                            title="Order cannot be cancelled once preparation has started.">
                        Cancel Order
                </button>
            <?php elseif($status === 'completed'): ?>
                <!-- COMPLETED: Review box + Buy Again + disabled Cancel -->
                <div class="review-box">
                    <h4>Enjoyed our service? Let us know!</h4>
                    <p>Your feedback helps us improve our service.</p>
                    <div class="star-rating" id="starRating">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <span class="star" data-value="<?php echo $i; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <textarea class="feedback-input" id="feedbackText" 
                            placeholder="Write your feedback here..."></textarea>
                    <button class="btn-submit-review" id="btnSubmitReview">Submit</button>
                </div>

                <div class="receipt-btn-row">
                <button class="btn-cancel-order btn-disabled-cancel" 
                            disabled
                            title="Completed orders cannot be cancelled.">
                        Cancel Order
                </button>
                    <a href="menu.php" class="btn-buy-again">Buy Again</a>
                </div>

            <?php elseif($status === 'cancelled'): ?>
                <!-- CANCELLED: Buy Again lang -->
                <div class="receipt-btn-row">
                    <a href="menu.php" class="btn-buy-again btn-full">Buy Again</a>
                </div>

            <?php endif; ?>
        </div>

</div>
</body>
</html>