<?php
session_start();
if (!isset($_SESSION['staff_logged_in'])) {
    header("Location: login.php");
    exit;
}
include "../db.php";

if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: orders.php");
    exit;
}

$order_id = (int) $_GET['order_id'];

// FETCH ORDER
$order_q = mysqli_query($conn,
    "SELECT o.*, u.full_name, u.email, u.phone_num
     FROM orders o
     JOIN users u ON o.user_id = u.user_id
     WHERE o.order_id = '$order_id'"
);

if (mysqli_num_rows($order_q) === 0) {
    header("Location: orders.php");
    exit;
}

$order = mysqli_fetch_assoc($order_q);

// FETCH ITEMS
$items_q = mysqli_query($conn,
    "SELECT oi.quantity, oi.unit_price, oi.addons,
     COALESCE(p.product_name, 'Unknown') as product_name,
     COALESCE(p.image, '') as image,
     COALESCE(p.category, '—') as category,
     COALESCE(ps.size_name, 'N/A') as size_name,
     COALESCE(ps.price, 0) as size_price
     FROM order_items oi
     LEFT JOIN products p ON oi.product_id = p.product_id
     LEFT JOIN product_sizes ps ON oi.size_id = ps.size_id
     WHERE oi.order_id = '$order_id'"
);

$items = [];
$subtotal = 0;
while ($row = mysqli_fetch_assoc($items_q)) {
    $items[] = $row;
    $subtotal += $row['unit_price'] * $row['quantity'];
}

date_default_timezone_set('Asia/Manila');

$pickup_value = trim($order['pickup_time']);
$created_at_time = !empty($order['created_at']) ? strtotime($order['created_at']) : time();

if($pickup_value === 'asap') {
    $start_time = date('g:i A', strtotime('+15 minutes', $created_at_time));
    $end_time   = date('g:i A', strtotime('+30 minutes', $created_at_time));

    $pickup_display = "ASAP ({$start_time} - {$end_time})";
} else {
    $pickup_labels = [
        'in-30-min'   => 'In 30 minutes',
        'in-45-min'   => 'In 45 minutes',
        'in-1-hour'   => 'In 1 hour',
        'in-1-5-hour' => 'In 1 hour 30 minutes',
        'in-2-hours' => 'In 2 hours',
    ];

    $pickup_display = $pickup_labels[$pickup_value] ?? $pickup_value;
}

// $pickup_display = $pickup_labels[$order['pickup_time']] ?? $order['pickup_time'];
$order_date = date('m/d/Y, · g:i A', strtotime($order['created_at']));

// UPDATE STATUS
if (isset($_POST['update_status'])) {
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE orders SET order_status = '$status' WHERE order_id = '$order_id'");
    header("Location: order-details.php?order_id=$order_id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details | Staff</title>
    <link rel="shortcut icon" href="../assets/logo/logo-black.png" type="image/png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="staff.css">
</head>
<body>
    <aside class="sidebar">
        <div class="logo">
            <img src="../assets/logo/bbmaysan.png" alt="">
        </div>
        <hr>
        <div class="main-menu">
            <h6>MAIN MENU</h6>
            <div class="dash-tab">
                <a href="dashboard.php"><h3><i class="fa fa-dashboard"></i> Dashboard</h3></a>
            </div>
            <div class="orders-tab active">
                <a href="orders.php"><h3><i class="fa fa-shopping-cart"></i> Orders</h3></a>
            </div>
        </div>
        <hr>
        <div class="acc">
            <h6>ACCOUNT</h6>
            <button class="logout" onclick="window.location.href='logout.php'">
                <h3><i class="fa fa-sign-out"></i> Logout</h3>
            </button>
        </div>
        <hr>
        <div class="staff-acc">
            <i class="fa fa-user"></i>
            <div>
                <h5><?php echo htmlspecialchars($_SESSION['staff_name']); ?></h5>
                <p>admin@bigbrew.com</p>
            </div>
        </div>
    </aside>

    <main class="od-main">
        <div class="od-topbar">
            <a href="orders.php" class="od-back"><i class="fa fa-arrow-left"></i></a>
            <h3>Order Details</h3>
        </div>

        <div class="od-content">
            <!-- ORDER HEADER CARD -->
            <div class="od-card">
                <div class="od-header-row">
                    <div>
                        <h2>ORDER ID: ORD-<?php echo str_pad($order_id, 3, '0', STR_PAD_LEFT); ?></h2>
                        <p class="od-meta">Date of Order: <?php echo $order_date; ?></p>
                        <p class="od-meta"><i class="fa fa-clock" style="color:var(--pop-color);margin-right:6px;"></i>Pickup: <?php echo htmlspecialchars($pickup_display); ?></p>
                    </div>
                    <form method="POST">
                        <select name="status" onchange="this.form.submit()" class="od-status-select"
                            style="background:<?php
                                echo $order['order_status'] === 'completed' ? 'rgba(180,180,180,0.35)' : 
                                    ($order['order_status'] === 'ready_for_pickup' ? 'rgba(136,214,108,0.5)' :
                                    ($order['order_status'] === 'cancelled' ? 'rgba(255,100,100,0.3)' :
                                    ($order['order_status'] === 'preparing' ? 'rgba(100,150,255,0.3)' : 'rgba(255,220,100,0.5)')));
                            ?>">
                            <option value="pending"   <?php echo $order['order_status'] === 'pending'   ? 'selected' : ''; ?>>Pending</option>
                            <option value="preparing" <?php echo $order['order_status'] === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                            <option value="ready_for_pickup" <?php echo $order['order_status'] === 'ready_for_pickup' ? 'selected' : ''; ?>>Ready for Pickup</option>
                            <option value="completed"<?php echo $order['order_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <input type="hidden" name="update_status" value="1">
                    </form>
                </div>

                <?php if (!empty($order['notes'])): ?>
                <div class="od-notes">
                    <i class="fa fa-comment" style="margin-right:8px;"></i>
                    Note to Barista: <em><?php echo htmlspecialchars($order['notes']); ?></em>
                </div>
                <?php endif; ?>

                <hr class="od-divider">

                <!-- CUSTOMER INFO -->
                <h4 class="od-section-title">Customer Information</h4>
                <div class="od-customer-grid">
                    <div>
                        <span class="od-label">Name:</span>
                        <span class="od-value"><?php echo htmlspecialchars($order['full_name']); ?></span>
                    </div>
                    <div>
                        <span class="od-label">Phone Number:</span>
                        <span class="od-value"><?php echo htmlspecialchars($order['phone_num'] ?? 'N/A'); ?></span>
                    </div>
                    <div>
                        <span class="od-label">Email:</span>
                        <span class="od-value"><?php echo htmlspecialchars($order['email']); ?></span>
                    </div>
                </div>
            </div>

            <!-- ORDER ITEMS CARD -->
            <div class="od-card">
                <h4 class="od-section-title">Order Items</h4>
                <?php foreach ($items as $item): ?>
                <div class="od-item-row">
                    <div class="od-item-img">
                        <?php if (!empty($item['image'])): ?>
                            <img src="../assets/products/<?php echo htmlspecialchars($item['image']); ?>" alt="">
                        <?php else: ?>
                            <div class="od-item-img-placeholder"><i class="fa fa-coffee"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="od-item-info">
                        <span class="od-item-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
                        <span class="od-item-meta">Category: <?php echo htmlspecialchars($item['category']); ?></span>
                        <span class="od-item-meta">Size: <?php echo htmlspecialchars($item['size_name']); ?></span>
                        <span class="od-item-meta">Qty: <?php echo $item['quantity']; ?></span>
                        <?php if (!empty($item['addons'])): ?>
                        <span class="od-item-meta">Add-ons: <?php echo htmlspecialchars($item['addons']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="od-item-price">
                        P<?php echo number_format($item['unit_price'] * $item['quantity'], 0); ?>
                        <?php if (!empty($item['addons'])): ?>
                        <span class="od-addon-price">+P<?php echo number_format($item['unit_price'] - $item['size_price'], 0); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ORDER SUMMARY CARD -->
            <div class="od-card">
                <h4 class="od-section-title">Order Summary</h4>
                <div class="od-summary-row">
                    <span>SubTotal:</span>
                    <span>P<?php echo number_format($subtotal, 0); ?></span>
                </div>
                <div class="od-summary-row od-total">
                    <span>Total:</span>
                    <span>P<?php echo number_format($order['total_amount'], 0); ?></span>
                </div>
            </div>
        </div>
    </main>

    <nav class="bottom-nav">
        <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fa fa-dashboard"></i>
            <span>Dashboard</span>
        </a>
        <a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' || basename($_SERVER['PHP_SELF']) === 'order-details.php' ? 'active' : ''; ?>">
            <i class="fa fa-shopping-cart"></i>
            <span>Orders</span>
                        </a>
        <a href="logout.php">
            <i class="fa fa-sign-out"></i>
            <span>Logout</span>
        </a>
    </nav>
</body>
</html>