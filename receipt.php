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

$pickup_labels = [
    'asap'        => 'ASAP',
    'in-15-min'   => 'In 15 minutes',
    'in-30-min'   => 'In 30 minutes',
    'in-45-min'   => 'In 45 minutes',
    'in-1-hour'   => 'In 1 hour',
    'in-1-5-hour' => 'In 1 hour 30 minutes',
];
$pickup_display = $pickup_labels[trim($order['pickup_time'])] ?? $order['pickup_time'];
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
        <span class="topbar-title">Order Receipt</span>
    </div>

    <!-- RECEIPT CARD -->
    <div class="receipt-card">

        <!-- CHECK ICON -->
        <div class="check-circle">
            <i class="fa-solid fa-check"></i>
        </div>
        <h2 class="receipt-title">Order Confirmed!</h2>
        <p class="receipt-subtitle">Thank you for your order, Brew! Your beverages are being prepared.</p>

        <!-- ORDER INFO -->
        <div class="info-box">
            <div class="info-row">
                <span>Order ID</span>
                <strong># <?php echo $order_id; ?></strong>
            </div>
            <div class="info-row">
                <span>Pickup Time</span>
               <strong><?php echo htmlspecialchars($pickup_display ?? 'ASAP'); ?></strong>
            </div>
            <?php if(!empty($order['notes'])): ?>
            <div class="info-row">
                <span>Notes</span>
                <strong><?php echo htmlspecialchars($order['notes']); ?></strong>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span>Payment</span>
                <strong>Pay upon Pickup</strong>
            </div>
        </div>

        <!-- ITEMS -->
        <div class="info-box">
            <p class="items-label">Items</p>
            <?php foreach($order_items as $item): ?>
            <div class="item-row">
                <span>
                    <?php echo htmlspecialchars($item['product_name']); ?> 
                    (<?php echo htmlspecialchars($item['size_name']); ?>)
                    x <?php echo $item['quantity']; ?>
                    <?php if(!empty($item['addons'])): ?>
                        · <?php echo htmlspecialchars($item['addons']); ?>
                    <?php endif; ?>
                </span>
                <span>P <?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></span>
            </div>
            <?php endforeach; ?>

            <div class="total-row">
                <span>Total</span>
                <span>P <?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
        </div>

        <!-- NEXT STEPS -->
        <div class="next-steps">
            <h4>Next steps:</h4>
            <ol>
                <li>We'll start preparing your order shortly</li>
                <li>Pick up your order <?php echo strtolower($pickup_display) === 'asap' ? 'as soon as possible' : $pickup_display; ?></li>
                <li>Show your order ID at the counter</li>
                <li>Enjoy your fresh beverage!</li>
            </ol>
        </div>

        <!-- BUTTONS -->
        <a href="menu.php" class="btn-outline">Order Again</a>
        <a href="account.php" class="btn-solid">View Order History →</a>

    </div>

</body>
</html>