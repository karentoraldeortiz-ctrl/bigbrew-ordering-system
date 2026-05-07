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
$pickup_display = $pickup_labels[$order['pickup_time']] ?? $order['pickup_time'];
$order_date = date('F j, Y · g:i A', strtotime($order['created_at']));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BigBrew | Receipt #<?php echo $order_id; ?></title>
    <link rel="stylesheet" href="css/global.css" />
    <link rel="stylesheet" href="css/order.css" />
    <link rel="shortcut icon" href="assets/logo/logo-black.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        /* Hide navbar and footer — receipt page lang */
        header, footer { display: none !important; }

        .receipt-topbar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 20px 20px 0;
            max-width: 450px;
            margin: 0 auto;
        }
        .receipt-back-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background-color: rgba(255,255,255,0.15);
            color: #fff;
            border-radius: 50%;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s;
        }
        .receipt-back-btn:hover { background-color: rgba(255,255,255,0.25); }
        .receipt-topbar-title {
            font-size: 16px;
            font-weight: 600;
            color: #fff;
        }
        .receipt-store-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .receipt-store-header img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-bottom: 8px;
        }
        .receipt-store-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: #231916;
            margin-bottom: 4px;
        }
        .receipt-store-header p {
            font-size: 12px;
            color: #aaa;
            line-height: 1.6;
        }
        .receipt-divider {
            border: none;
            border-top: 1.5px dashed #e0d5c5;
            margin: 16px 0;
        }
        .receipt-items-header {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 12px;
            font-size: 11px;
            font-weight: 600;
            color: #bbb;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e0d5c5;
            margin-bottom: 4px;
        }
        .receipt-items-header span:not(:first-child) { text-align: right; }
        .receipt-item-row {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 12px;
            align-items: start;
            padding: 8px 0;
            border-bottom: 1px solid #f0ebe3;
        }
        .receipt-item-row:last-child { border-bottom: none; }
        .receipt-item-name { display: flex; flex-direction: column; gap: 2px; }
        .receipt-item-name span { font-size: 13px; font-weight: 500; color: #231916; }
        .receipt-item-name small { font-size: 11px; color: #bbb; }
        .receipt-item-qty { font-size: 13px; color: #999; text-align: right; }
        .receipt-item-price { font-size: 13px; font-weight: 600; color: #231916; text-align: right; white-space: nowrap; }
        .receipt-status-section { display: flex; justify-content: center; margin: 8px 0; }
        .receipt-status-badge {
            display: inline-block;
            padding: 6px 20px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-pending  { background: #fff4e0; color: #b07d00; }
        .status-completed { background: #e6f7ee; color: #1a7a3c; }
        .status-cancelled { background: #fdecea; color: #c0392b; }
        .receipt-footer-note {
            text-align: center;
            margin-top: 8px;
        }
        .receipt-footer-note p {
            font-size: 12px;
            color: #aaa;
            line-height: 1.8;
        }
    </style>
</head>
<body class="success-page-body">

    <!-- BACK BUTTON -->
    <div class="receipt-topbar">
        <a href="javascript:history.back()" class="receipt-back-btn">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <span class="receipt-topbar-title">Receipt</span>
    </div>

    <div class="success-container">
        <div class="receipt-card" style="text-align:left;">

            <!-- STORE INFO -->
            <div class="receipt-store-header">
                <img src="assets/logo/bb-maysan-logo-1.png" alt="BigBrew Logo" />
                <h2>Bigbrew Maysan</h2>
                <p>094 Maysan Rd, Valenzuela, 1442 Metro Manila</p>
                <p>0929 563 4350</p>
            </div>

            <div class="receipt-divider"></div>

            <!-- ORDER META -->
            <div class="order-info-box">
                <div class="info-row line-bottom">
                    <span>Order #</span>
                    <strong>#<?php echo $order_id; ?></strong>
                </div>
                <div class="info-row line-bottom">
                    <span>Date</span>
                    <span><?php echo $order_date; ?></span>
                </div>
                <div class="info-row line-bottom">
                    <span>Customer</span>
                    <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                </div>
                <div class="info-row line-bottom">
                    <span>Pickup</span>
                    <span><?php echo htmlspecialchars($pickup_display); ?></span>
                </div>
                <div class="info-row <?php echo !empty($order['notes']) ? 'line-bottom' : ''; ?>">
                    <span>Payment</span>
                    <span>Pay upon Pickup</span>
                </div>
                <?php if(!empty($order['notes'])): ?>
                <div class="info-row">
                    <span>Notes</span>
                    <span><?php echo htmlspecialchars($order['notes']); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="receipt-divider"></div>

            <!-- ITEMS -->
            <div class="order-info-box">
                <div class="receipt-items-header">
                    <span>Item</span>
                    <span>Qty</span>
                    <span>Price</span>
                </div>
                <?php foreach($order_items as $item): ?>
                <div class="receipt-item-row">
                    <div class="receipt-item-name">
                        <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                        <small><?php echo htmlspecialchars($item['size_name']); ?>
                            <?php if(!empty($item['addons'])): ?>
                                · <?php echo htmlspecialchars($item['addons']); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                    <span class="receipt-item-qty">x<?php echo $item['quantity']; ?></span>
                    <span class="receipt-item-price">P <?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></span>
                </div>
                <?php endforeach; ?>

                <!-- TOTAL -->
                <div class="info-row total-section">
                    <span>Subtotal</span>
                    <span>P <?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
                <div class="info-row total-section">
                    <span class="total-label">TOTAL</span>
                    <span class="total-amount">P <?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>

            <div class="receipt-divider"></div>

            <!-- STATUS -->
            <div class="receipt-status-section">
                <span class="receipt-status-badge status-<?php echo strtolower($order['order_status']); ?>">
                    <?php echo ucfirst($order['order_status']); ?>
                </span>
            </div>

            <!-- FOOTER -->
            <div class="receipt-footer-note">
                <p>Thank you for ordering at BigBrew! ☕</p>
                <p>Big in Taste, Bit in Price.</p>
            </div>

        </div>
    </div>

</body>
</html>