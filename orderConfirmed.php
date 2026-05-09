<?php
session_start();
include "db.php";

// GUARD: kailangan naka-login
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// GUARD: kailangan may order_id sa URL
if(!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: menu.php");
    exit;
}

$user_id  = $_SESSION['user_id'];
$order_id = (int)$_GET['order_id'];

// Fetch order — make sure it belongs to this user
$order_q = mysqli_query($conn,
    "SELECT * FROM orders WHERE order_id = '$order_id' AND user_id = '$user_id'"
);

if(mysqli_num_rows($order_q) === 0) {
    header("Location: menu.php");
    exit;
}

$order = mysqli_fetch_assoc($order_q);

// Fetch order items
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

// Format pickup time for display
$pickup_labels = [
    'asap'        => 'ASAP',
    'in-15-min'   => 'In 15 minutes',
    'in-30-min'   => 'In 30 minutes',
    'in-45-min'   => 'In 45 minutes',
    'in-1-hour'   => 'In 1 hour',
    'in-1-5-hour' => 'In 1 hour 30 minutes',
];
$pickup_display = $pickup_labels[$order['pickup_time']] ?? $order['pickup_time'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BigBrew | Order Confirmation</title>
    <link rel="stylesheet" href="css/global.css" />
    <link rel="stylesheet" href="css/order.css" />
    <link rel="shortcut icon" href="assets/logo/logo-black.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body>
<!-- NAV BAR -->
<header>
    <nav class="navbar">
        <div class="navlogo">
            <a href="index.php">
                <img src="assets/logo/bb-maysan-logo-1.png" alt="" />
            </a>
        </div>
        <div class="nav-links" id="navlinks">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="menu.php">Our Menu</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="cart.php"><img src="assets/icons/icons8-cart-24.png" alt="" /></a></li>
                <li><a href="account.php"><img src="assets/icons/icons8-profile-24.png" alt="" /></a></li>
            </ul>
        </div>
        <div class="hamburger" id="hamburger">
            <span></span><span></span><span></span>
        </div>
    </nav>
</header>

<div class="success-page-body">
    <div class="success-container">
        <div class="receipt-card">
            <div class="check-icon">
                <i class="fa-regular fa-circle-check"></i>
            </div>
            <h1>Order Confirmed!</h1>
            <p>Thank you for your order, <?php echo htmlspecialchars($_SESSION['name']); ?>! Your beverages are being prepared.</p>

            <div class="order-info-box">
                <div class="info-row line-bottom">
                    <span>Order ID</span>
                    <strong>#<?php echo $order_id; ?></strong>
                </div>
                <div class="info-row line-bottom">
                    <span>Pickup Time</span>
                    <strong class="highlight-text"><?php echo htmlspecialchars($pickup_display); ?></strong>
                </div>

                <div class="items-header">Items</div>
                <?php foreach($order_items as $item): ?>
                <div class="info-row">
                    <span>
                        <?php echo htmlspecialchars($item['product_name']); ?>
                        (<?php echo htmlspecialchars($item['size_name']); ?>)
                        <?php if(!empty($item['addons'])): ?>
                            <br><small style="color:#aaa;">+ <?php echo htmlspecialchars($item['addons']); ?></small>
                        <?php endif; ?>
                        x <?php echo $item['quantity']; ?>
                    </span>
                    <span>P <?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></span>
                </div>
                <?php endforeach; ?>

                <div class="info-row total-section">
                    <span class="total-label">Total</span>
                    <span class="total-amount">P <?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>

            <div class="next-steps-box">
                <h4>Next steps:</h4>
                <ol>
                    <li>We'll start preparing your order shortly.</li>
                    <li>Pick up your order at your selected time.</li>
                    <li>Show your order ID at the counter.</li>
                    <li>Enjoy your fresh beverage!</li>
                </ol>
            </div>

            <!-- OPEN RECEIPT button → goes to receipt.php -->
            <a href="receipt.php?order_id=<?php echo $order_id; ?>" class="btn-open-receipt">
                🧾 OPEN RECEIPT
            </a>

            <button onclick="location.href='menu.php'" class="btn-order-again">
                Order Again
            </button>
            <button onclick="location.href='account.php'" class="btn-history">
                View Order History →
            </button>

            <div class="feedback-card">
                <h3>Enjoyed our service? Let us know!</h3>
                <p>Your feedback helps us improve our service.</p>

                <div class="star-rating">
                    <span class="star" data-value="1">★</span>
                    <span class="star" data-value="2">★</span>
                    <span class="star" data-value="3">★</span>
                    <span class="star" data-value="4">★</span>
                    <span class="star" data-value="5">★</span>
                </div>

                <textarea class="feedback-input" placeholder="Write your feedback here."></textarea>
                <button class="btn-submit-feedback">Submit</button>
            </div>
        </div>
    </div>
</div>

<script src="js/global.js"></script>
<script>
// Star rating logic
document.querySelectorAll('.star').forEach(star => {
    star.addEventListener('click', function() {
        const val = this.dataset.value;
        document.querySelectorAll('.star').forEach(s => {
            s.classList.toggle('selected', s.dataset.value <= val);
        });
    });
});
</script>
</body>
</html>