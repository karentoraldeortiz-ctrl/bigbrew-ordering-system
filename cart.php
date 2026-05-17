<?php
session_start();
include "db.php";
include "ban-check.php";

date_default_timezone_set('Asia/Manila');

$currentTime = date('H:i');
$openingTime = '01:00';
$closingTime = '23:59';

$isStoreOpen = ($currentTime >= $openingTime && $currentTime < $closingTime);
$storeStatusText = $isStoreOpen ? 'Open' : 'Closed';
$storeStatusClass = $isStoreOpen ? 'open' : 'closed';
$now = time();
$closingTimestamp = strtotime(date('Y-m-d') . ' ' . $closingTime);

$pickup_options = [
    'asap'        => ['label' => 'ASAP',                  'minutes' => 30],
    'in-30-min'   => ['label' => 'In 30 minutes',         'minutes' => 30],
    'in-45-min'   => ['label' => 'In 45 minutes',         'minutes' => 45],
    'in-1-hour'   => ['label' => 'In 1 hour',             'minutes' => 60],
    'in-1-5-hour' => ['label' => 'In 1 hour 30 minutes',  'minutes' => 90],
    'in-2-hours'  => ['label' => 'In 2 hours',            'minutes' => 120],
];

$asap_start = date('g:i A', strtotime('+15 minutes', $now));
$asap_end   = date('g:i A', strtotime('+30 minutes', $now));

$isLoggedIn = isset($_SESSION['user_id']);
$user_id    = $isLoggedIn ? $_SESSION['user_id'] : null;

// Ban check
$is_banned   = false;
$ban_message = '';
if ($isLoggedIn) {
    $ban_q    = mysqli_query($conn, "SELECT ban_status, ban_until FROM users WHERE user_id = '$user_id'");
    $ban_info = mysqli_fetch_assoc($ban_q);
    if ($ban_info['ban_status'] === 'temp_banned') {
        $ban_until_ts = strtotime($ban_info['ban_until']);
        if (time() < $ban_until_ts) {
            $is_banned   = true;
            $ban_message = '🚫 Your account is suspended until <strong>' . date('F j, Y', $ban_until_ts) . '</strong> due to repeated no-shows.';
        } else {
            mysqli_query($conn, "UPDATE users SET ban_status = 'active', ban_until = NULL WHERE user_id = '$user_id'");
        }
    } elseif ($ban_info['ban_status'] === 'banned') {
        $is_banned   = true;
        $ban_message = '🚫 Your account has been <strong>permanently suspended</strong> due to repeated no-shows.';
    }
}

$order_success = isset($_GET['order_success']) && isset($_GET['order_id']);
$order_id      = $order_success ? (int)$_GET['order_id'] : null;
$message       = "";

if (isset($_SESSION['order_success'])) {
    $order_success = true;
    $order_id      = $_SESSION['order_id'];
    unset($_SESSION['order_success']);
    unset($_SESSION['order_id']);
}

// ─── PLACE ORDER (with GCash receipt upload) ──────────────────────────────────
if (isset($_POST['place_order']) && $isLoggedIn && !$is_banned) {
    $pickup_time = $_POST['pickup_time'];
    $notes       = mysqli_real_escape_string($conn, $_POST['notes']);

    if (!$isStoreOpen) {
        $message = "Sorry, we're currently closed. Our store hours are 11:00 AM - 9:00 PM.";
    } else {
        // Check for unavailable items
        $unavail_q = mysqli_query($conn,
            "SELECT p.product_name FROM cart_items ci
             JOIN cart c ON ci.cart_id = c.cart_id
             JOIN products p ON ci.product_id = p.product_id
             WHERE c.user_id = '$user_id' 
            AND (p.is_available = 0 OR p.is_archived = 1)"
        );
        if (mysqli_num_rows($unavail_q) > 0) {
            $unavail_names = [];
            while ($row = mysqli_fetch_assoc($unavail_q)) $unavail_names[] = $row['product_name'];
            $message = "Some items are no longer available: " . implode(', ', $unavail_names) . ". Please remove them before checking out.";
        } else {
            $cart_q = mysqli_query($conn, "SELECT cart_id FROM cart WHERE user_id='$user_id'");
            if (mysqli_num_rows($cart_q) === 0) {
                $message = "Your cart is empty!";
            } else {
                $cart    = mysqli_fetch_assoc($cart_q);
                $cart_id = $cart['cart_id'];

                $items_q = mysqli_query($conn,
                    "SELECT ci.*, p.product_name, ps.size_name, ps.price as size_price
                     FROM cart_items ci
                     JOIN products p       ON ci.product_id = p.product_id
                     JOIN product_sizes ps ON ci.size_id    = ps.size_id
                     WHERE ci.cart_id = '$cart_id'"
                );

                if (mysqli_num_rows($items_q) === 0) {
                    $message = "Your cart is empty!";
                } else {
                    $total       = 0;
                    $items_array = [];
                    while ($item = mysqli_fetch_assoc($items_q)) {
                        $total        += $item['unit_price'] * $item['quantity'];
                        $items_array[] = $item;
                    }

                    // ── GCash downpayment logic ───────────────────────────
                    $requires_gcash   = $total >= 100;
                    $downpayment      = $requires_gcash ? round($total * 0.5, 2) : 0;
                    $receipt_status   = 'not_required';
                    $receipt_filename = null;

                    if ($requires_gcash) {
                        if (empty($_FILES['gcash_receipt']['tmp_name'])) {
                            $message = "Please upload your GCash receipt to confirm your downpayment.";
                        } else {
                            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                            $file_type     = mime_content_type($_FILES['gcash_receipt']['tmp_name']);

                            if (!in_array($file_type, $allowed_types)) {
                                $message = "Invalid file type. Please upload a PNG, JPG, or JPEG image.";
                            } elseif ($_FILES['gcash_receipt']['size'] > 5 * 1024 * 1024) {
                                $message = "File too large. Maximum size is 5MB.";
                            } else {
                                $upload_dir = "uploads/receipts/";
                                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                                $ext              = pathinfo($_FILES['gcash_receipt']['name'], PATHINFO_EXTENSION);
                                $receipt_filename  = "receipt_" . time() . "_" . $user_id . "." . $ext;
                                $upload_path      = $upload_dir . $receipt_filename;

                                if (!move_uploaded_file($_FILES['gcash_receipt']['tmp_name'], $upload_path)) {
                                    $message = "Failed to upload receipt. Please try again.";
                                    $receipt_filename = null;
                                } else {
                                    $receipt_status = 'pending_verification';
                                }
                            }
                        }
                    }

                    if (empty($message)) {
                        $receipt_val = $receipt_filename
                            ? "'" . mysqli_real_escape_string($conn, $receipt_filename) . "'"
                            : 'NULL';
                        $downpay_val = $requires_gcash ? $downpayment : 'NULL';

                        mysqli_query($conn,
                            "INSERT INTO orders (user_id, total_amount, pickup_time, notes, order_status,
                                                 gcash_receipt, gcash_receipt_status, gcash_downpayment, created_at)
                             VALUES ('$user_id', '$total', '$pickup_time', '$notes', 'pending',
                                     $receipt_val, '$receipt_status', $downpay_val, NOW())"
                        );
                        $order_id = mysqli_insert_id($conn);

                        foreach ($items_array as $item) {
                            $prod_id    = $item['product_id'];
                            $sz_id      = $item['size_id'];
                            $addons     = mysqli_real_escape_string($conn, $item['addons']);
                            $qty        = $item['quantity'];
                            $unit_price = $item['unit_price'];

                            mysqli_query($conn,
                                "INSERT INTO order_items (order_id, product_id, size_id, addons, quantity, unit_price)
                                 VALUES ('$order_id', '$prod_id', '$sz_id', '$addons', '$qty', '$unit_price')"
                            );
                        }

                        mysqli_query($conn, "DELETE FROM cart_items WHERE cart_id='$cart_id'");

                        $_SESSION['order_success'] = true;
                        $_SESSION['order_id']      = $order_id;

                        // ✅ FIX: Added $order_id to redirect
                        header("Location: cart.php?order_success=1&order_id=" . $order_id);
                        exit();
                    }
                }
            }
        }
    }
}

// ─── LOAD CART ITEMS ──────────────────────────────────────────────────────────
$cart_items = [];
$subtotal   = 0;

if ($isLoggedIn) {
    $cart_q = mysqli_query($conn, "SELECT cart_id FROM cart WHERE user_id='$user_id'");
    if (mysqli_num_rows($cart_q) > 0) {
        $cart    = mysqli_fetch_assoc($cart_q);
        $cart_id = $cart['cart_id'];
        $items_q = mysqli_query($conn,
            "SELECT ci.cart_item_id, ci.product_id, ci.quantity, ci.addons, ci.unit_price,
                    p.product_name, p.image, p.category, p.is_available,
                    COALESCE(ps.size_name, 'Unknown Size') as size_name
             FROM cart_items ci
             JOIN products p            ON ci.product_id = p.product_id
             LEFT JOIN product_sizes ps ON ci.size_id    = ps.size_id
             WHERE ci.cart_id = '$cart_id'"
        );
        while ($row = mysqli_fetch_assoc($items_q)) {
            $cart_items[] = $row;
            $subtotal    += $row['unit_price'] * $row['quantity'];
        }
    }
} else {
    if (isset($_SESSION['guest_cart']) && !empty($_SESSION['guest_cart'])) {
        foreach ($_SESSION['guest_cart'] as $key => $guestItem) {
            $product_id = intval($guestItem['product_id']);
            $size_id    = intval($guestItem['size_id']);
            $item_q     = mysqli_query($conn,
                "SELECT p.product_name, p.image, p.category, p.is_available, ps.size_name
                 FROM products p
                 JOIN product_sizes ps ON p.product_id = ps.product_id
                 WHERE p.product_id = '$product_id' AND ps.size_id = '$size_id' LIMIT 1"
            );
            if (mysqli_num_rows($item_q) > 0) {
                $info         = mysqli_fetch_assoc($item_q);
                $cart_items[] = [
                    'cart_item_id' => $key,
                    'quantity'     => $guestItem['quantity'],
                    'addons'       => $guestItem['addons'],
                    'unit_price'   => $guestItem['unit_price'],
                    'product_name' => $info['product_name'],
                    'image'        => $info['image'],
                    'category'     => $info['category'],
                    'size_name'    => $info['size_name'],
                    'is_available' => $info['is_available'],
                    'product_id'   => $product_id,
                    'is_guest'     => true,
                ];
                $subtotal += $guestItem['unit_price'] * $guestItem['quantity'];
            }
        }
    }
}

$requires_gcash = $subtotal >= 100;
$downpayment    = $requires_gcash ? round($subtotal * 0.5, 2) : 0;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BigBrew | Your Cart</title>
    <link rel="stylesheet" href="css/global.css" />
    <link rel="stylesheet" href="css/order.css" />
    <link rel="shortcut icon" href="assets/logo/logo-black.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

    <style>
        /* ── GCash Modal ───────────────────────────────────────────────── */
        .gcash-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(3px);
        }
        .gcash-modal-overlay.active { display: flex; }

        .gcash-modal {
            background: #fff;
            border-radius: 20px;
            padding: 32px 28px 28px;
            width: 90%;
            max-width: 420px;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            animation: modalIn 0.25s ease;
        }
        @keyframes modalIn {
            from { transform: translateY(20px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        /* Step indicator */
        .gcash-steps {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 24px;
        }
        .gcash-step {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 500;
            color: #aaa;
        }
        .gcash-step .step-num {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
        }
        .gcash-step.active .step-num { background: #007bff; color: #fff; }
        .gcash-step.done .step-num   { background: #28a745; color: #fff; }
        .gcash-step.active { color: #007bff; }
        .gcash-step.done   { color: #28a745; }
        .gcash-step-line {
            width: 40px;
            height: 2px;
            background: #e0e0e0;
            border-radius: 2px;
        }
        .gcash-step-line.done { background: #28a745; }

        /* Step panels */
        .gcash-panel { display: none; text-align: center; }
        .gcash-panel.active { display: block; }

        .gcash-icon { font-size: 36px; margin-bottom: 10px; }
        .gcash-modal h2 {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0 0 8px;
        }
        .gcash-modal p {
            font-size: 13px;
            color: #666;
            margin: 0 0 20px;
            line-height: 1.5;
        }
        .gcash-modal p strong { color: #1a1a1a; }

        /* QR box */
        .gcash-qr-box {
            border: 2px solid #007bff;
            border-radius: 16px;
            padding: 16px 16px 0 16px;
            margin: 0 auto 16px;
            width: fit-content;
            background: #f0f7ff;
            overflow: hidden;
        }
        .gcash-qr-box img {
            width: 160px;
            height: 160px;
            object-fit: contain;
            display: block;
        }

        /* Downpayment amount box */
        .gcash-amount-box {
            background: #f0f7ff;
            border: 1px solid #cce0ff;
            border-radius: 12px;
            padding: 14px 20px;
            margin-bottom: 20px;
        }
        .gcash-amount-label {
            font-size: 11px;
            font-weight: 600;
            color: #007bff;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .gcash-amount-value {
            font-size: 28px;
            font-weight: 700;
            color: #007bff;
        }
        .gcash-amount-sub {
            font-size: 12px;
            color: #888;
            margin-top: 2px;
        }

        /* Upload zone */
        .gcash-upload-zone {
            border: 2px dashed #ccc;
            border-radius: 14px;
            padding: 28px 20px;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 20px;
            background: #fafafa;
            position: relative;
        }
        .gcash-upload-zone:hover,
        .gcash-upload-zone.dragover {
            border-color: #007bff;
            background: #f0f7ff;
        }
        .gcash-upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }
        .gcash-upload-icon { font-size: 32px; margin-bottom: 8px; }
        .gcash-upload-zone h4 {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin: 0 0 4px;
        }
        .gcash-upload-zone p { font-size: 12px; color: #aaa; margin: 0; }

        /* Preview */
        .gcash-preview {
            display: none;
            position: relative;
            margin-bottom: 20px;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid #28a745;
        }
        .gcash-preview img {
            width: 100%;
            max-height: 200px;
            object-fit: contain;
            display: block;
            background: #f5f5f5;
        }
        .gcash-preview-remove {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(0,0,0,0.6);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Buttons */
        .gcash-btn-row { display: flex; gap: 10px; }
        .gcash-btn {
            flex: 1;
            padding: 12px;
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .gcash-btn-outline {
            background: transparent;
            border: 1px solid #ddd;
            color: #666;
        }
        .gcash-btn-outline:hover { background: #f5f5f5; }
        .gcash-btn-primary { background: #007bff; color: #fff; }
        .gcash-btn-primary:hover { background: #0069d9; }
        .gcash-btn-primary:disabled { background: #ccc; cursor: not-allowed; }
        .gcash-btn-success { background: #28a745; color: #fff; }
        .gcash-btn-success:hover { background: #218838; }
        .gcash-btn-success:disabled { background: #ccc; cursor: not-allowed; }

        /* GCash badge in sidebar */
        .gcash-required-badge {
            background: #fff8e1;
            border: 1px solid #ffe082;
            border-radius: 10px;
            padding: 10px 14px;
            margin-bottom: 14px;
            font-size: 12px;
            color: #6d4c00;
            display: <?php echo $requires_gcash ? 'block' : 'none'; ?>;
        }
        .gcash-required-badge strong { display: block; margin-bottom: 2px; font-size: 13px; }

        /* Checkout button override */
        .checkout-btn.gcash { background: #007bff; }
        .checkout-btn.gcash:hover { background: #0069d9; }
    </style>
</head>
<body>

<!-- NAV BAR -->
<header>
    <nav class="navbar">
        <div class="navlogo">
            <a href="index.php"><img src="assets/logo/bb-maysan-logo-1.png" alt="" /></a>
        </div>
        <div class="nav-links" id="navlinks">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="menu.php">Our Menu</a></li>
                <li><a href="about.php">About Us</a></li>
                <li>
                    <a href="cart.php" class="cart-link">
                        <img src="assets/icons/icons8-cart-24.png" alt="">
                        <span id="cartBadge" class="cart-badge">0</span>
                    </a>
                </li>
                <li><a href="account.php"><img src="assets/icons/icons8-profile-24.png" alt=""></a></li>
            </ul>
        </div>
        <div class="hamburger" id="hamburger">
            <span></span><span></span><span></span>
        </div>
        <span id="cartBadgeMobile" class="cart-badge-mobile"></span>
    </nav>
</header>

<div class="cart-container">
    <main>
        <div class="cart-header">
            <div class="store-info">
                <h1>Bigbrew Maysan</h1>
                <div class="store-status <?php echo $storeStatusClass; ?>">
                    <span><?php echo $storeStatusText; ?></span>
                </div>
                <p>094 Maysan Rd, Valenzuela, 1442 Metro Manila</p>
                <p class="store-hours">Business Hours: 11:00 AM - 9:00 PM</p>
            </div>
            <div class="store-image"><img src="assets/pictures/store-pic.jpg" alt="" /></div>
        </div>

        <?php if (!$order_success): ?>
            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <h3>Your Cart</h3>
                    <?php if (!$isLoggedIn): ?>
                        <p>Your cart is empty. Browse the menu and add your favorite drinks.</p>
                    <?php else: ?>
                        <p>Your cart is empty.</p>
                    <?php endif; ?>
                    <a href="menu.php">Browse Menu</a>
                </div>
            <?php else: ?>
                <div id="cart-items-container">
                    <div class="cart-subheader">
                        <h3 id="cart-title">Your Cart</h3>
                        <a href="menu.php#menu-section"><p>+ add item</p></a>
                    </div>

                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item <?php echo !$item['is_available'] ? 'item-unavailable' : ''; ?>"
                            id="cart-card-<?php echo htmlspecialchars($item['cart_item_id']); ?>"
                            data-cart-item-id="<?php echo htmlspecialchars($item['cart_item_id']); ?>"
                            data-product-id="<?php echo htmlspecialchars($item['product_id']); ?>"
                            data-unit-price="<?php echo $item['unit_price']; ?>"
                            data-is-guest="<?php echo !$isLoggedIn ? '1' : '0'; ?>">

                            <img src="assets/products/<?php echo htmlspecialchars($item['image']); ?>"
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>">

                            <div class="cart-item-details">
                                <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                <?php if (!$item['is_available']): ?>
                                    <span class="unavailable-tag">⚠️ No longer available</span>
                                <?php endif; ?>
                                <p class="cart-product-category">
                                    <?php echo ucwords(str_replace('-', ' ', htmlspecialchars($item['category']))); ?>
                                </p>
                                <p>
                                    <?php echo htmlspecialchars($item['size_name']); ?>
                                    <?php if (!empty($item['addons'])): ?> · <?php echo htmlspecialchars($item['addons']); ?><?php endif; ?>
                                </p>
                                <div class="qty-Stepper">
                                    <button type="button" onclick="updateQty('<?php echo $item['cart_item_id']; ?>', -1)">-</button>
                                    <span id="qty-<?php echo $item['cart_item_id']; ?>"><?php echo $item['quantity']; ?></span>
                                    <button type="button" onclick="updateQty('<?php echo $item['cart_item_id']; ?>', 1)">+</button>
                                </div>
                            </div>

                            <div class="cart-item-price">
                                <p id="item-price-<?php echo $item['cart_item_id']; ?>">
                                    P <?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <!-- ORDER SUMMARY SIDEBAR -->
    <?php if (!$order_success && !empty($cart_items)): ?>
    <aside>
        <div class="aside-content">
            <form method="POST" action="" id="orderForm" enctype="multipart/form-data">
                <input type="hidden" name="place_order" value="1">
                <input type="hidden" name="pickup_time" id="hidden_pickup_time" value="asap">
                <input type="hidden" name="notes" id="hidden_notes" value="">
                <input type="file" name="gcash_receipt" id="hiddenReceiptInput" accept="image/*" style="display:none">

                <div class="order-summary-content">
                    <h4>Order Summary</h4>
                    <div class="display-time">
                        <i class="fa-solid fa-clock"></i> Pickup Time
                    </div>
                    <select name="pickup_time_display" id="pick-up-time" class="summary-input">
                        <?php foreach ($pickup_options as $value => $option): ?>
                            <?php
                                if ($value === 'asap') {
                                    $start         = date('g:i A', strtotime('+15 minutes', $now));
                                    $end           = date('g:i A', strtotime('+30 minutes', $now));
                                    $display       = "ASAP ({$start} - {$end})";
                                    $optionEndTime = strtotime('+30 minutes', $now);
                                } else {
                                    $pickupTime    = date('g:i A', strtotime('+' . $option['minutes'] . ' minutes', $now));
                                    $display       = $option['label'] . " ({$pickupTime})";
                                    $optionEndTime = strtotime('+' . $option['minutes'] . ' minutes', $now);
                                }
                                $disabled = $optionEndTime > $closingTimestamp ? 'disabled' : '';
                            ?>
                            <option value="<?php echo $value; ?>" <?php echo $disabled; ?>>
                                <?php echo $display; ?>
                                <?php echo $disabled ? ' - Not Available' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="summary-label">Payment Method</div>
                    <div class="payment-fixed"><span>Pay upon Pickup</span></div>

                    <div class="gcash-required-badge">
                        💙 <strong>GCash Downpayment Required</strong>
                        Min. downpayment: <strong class="gcash-badge-amount">₱<?php echo number_format($downpayment, 2); ?></strong>
                        (50% of <span class="gcash-badge-total">₱<?php echo number_format($subtotal, 2); ?></span> total)
                    </div>

                    <div class="summary-label">Note to Barista</div>
                    <textarea id="barista-note" class="note-barista"
                        placeholder="Let us know if you have any requests."></textarea>

                    <div class="subtotal-container">
                        <div class="subtotal-row">
                            <span>Subtotal</span>
                            <span id="subtotal-amount">P <?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="subtotal-row total-bold">
                            <span>Total</span>
                            <span id="total-amount">P <?php echo number_format($subtotal, 2); ?></span>
                        </div>
                    </div>

                    <?php if ($isLoggedIn): ?>
                        <?php if ($is_banned): ?>
                            <button type="button" class="checkout-btn" disabled>🔒 Account Suspended</button>
                            <p class="store-closed-msg"><?php echo $ban_message; ?></p>
                        <?php else: ?>
                            <?php if ($requires_gcash): ?>
                                <button type="button" class="checkout-btn gcash"
                                    <?php echo !$isStoreOpen ? 'disabled' : ''; ?>
                                    onclick="openGCashModal()">
                                    <?php echo $isStoreOpen ? 'Checkout &amp; Pay GCash' : '🔒 Store Closed'; ?>
                                </button>
                                <?php if (!$isStoreOpen): ?>
                                    <p class="store-closed-msg">We're closed right now. Come back between 11:00 AM - 9:00 PM!</p>
                                <?php else: ?>
                                    <p style="font-size:11px;color:#888;text-align:center;margin-top:8px;">
                                        A GCash QR will appear to complete your downpayment.
                                    </p>
                                <?php endif; ?>
                            <?php else: ?>
                                <button type="submit" name="place_order" class="checkout-btn"
                                    <?php echo !$isStoreOpen ? 'disabled' : ''; ?>>
                                    <?php echo $isStoreOpen ? 'Checkout' : '🔒 Store Closed'; ?>
                                </button>
                                <?php if (!$isStoreOpen): ?>
                                    <p class="store-closed-msg">We're closed right now. Come back between 11:00 AM - 9:00 PM!</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <button type="button" class="checkout-btn login-required-btn">Login to Checkout</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </aside>
    <?php endif; ?>
</div>

<!-- ── GCash Payment Modal ──────────────────────────────────────────────────── -->
<?php if ($isLoggedIn && !$is_banned && !empty($cart_items)): ?>
<div class="gcash-modal-overlay" id="gcashModal">
    <div class="gcash-modal">

        <!-- Step indicator -->
        <div class="gcash-steps">
            <div class="gcash-step active" id="step-indicator-1">
                <div class="step-num" id="step-num-1">1</div>
                <span>Scan QR</span>
            </div>
            <div class="gcash-step-line" id="step-line"></div>
            <div class="gcash-step" id="step-indicator-2">
                <div class="step-num" id="step-num-2">2</div>
                <span>Upload Receipt</span>
            </div>
        </div>

        <!-- STEP 1: QR Code -->
        <div class="gcash-panel active" id="gcash-step-1">
            <div class="gcash-icon">💙</div>
            <h2>GCash Downpayment</h2>
            <p>Orders ₱100+ require at least <strong>50% downpayment</strong> via GCash.</p>

            <div class="gcash-qr-box">
                <img src="assets/pictures/QRCODE.png" alt="GCash QR Code"
                     onerror="this.style.display='none'">
            </div>

            <div class="gcash-amount-box">
                <div class="gcash-amount-label">Minimum Downpayment</div>
                <div class="gcash-amount-value">₱<?php echo number_format($downpayment, 2); ?></div>
                <div class="gcash-amount-sub">out of ₱<?php echo number_format($subtotal, 2); ?> total</div>
            </div>

            <p>Scan using your GCash app, then click <strong>"I've Paid"</strong> to upload your receipt.</p>

            <div class="gcash-btn-row">
                <button class="gcash-btn gcash-btn-outline" onclick="closeGCashModal()">← Cancel</button>
                <button class="gcash-btn gcash-btn-primary" onclick="goToStep2()">I've Paid →</button>
            </div>
        </div>

        <!-- STEP 2: Upload Receipt -->
        <div class="gcash-panel" id="gcash-step-2">
            <div class="gcash-icon">🧾</div>
            <h2>Upload GCash Receipt</h2>
            <p>Upload a screenshot of your GCash transaction to confirm your downpayment of <strong class="gcash-amount-value-inline">₱<?php echo number_format($downpayment, 2); ?></strong>.</p>

            <div class="gcash-upload-zone" id="uploadZone">
                <input type="file" id="receiptFileInput" accept="image/png,image/jpeg,image/jpg"
                       onchange="handleReceiptSelect(this)">
                <div class="gcash-upload-icon">📤</div>
                <h4>Tap to upload or drag &amp; drop</h4>
                <p>PNG, JPG, JPEG supported</p>
            </div>

            <div class="gcash-preview" id="receiptPreview">
                <img id="previewImg" src="" alt="Receipt preview">
                <button class="gcash-preview-remove" onclick="removeReceipt()">✕</button>
            </div>

            <div class="gcash-btn-row">
                <button class="gcash-btn gcash-btn-outline" onclick="goToStep1()">← Back</button>
                <button class="gcash-btn gcash-btn-success" id="confirmOrderBtn" disabled onclick="submitOrderWithReceipt()">
                    Confirm Order ✓
                </button>
            </div>
        </div>

    </div>
</div>
<?php endif; ?>

<!-- ORDER PLACED MODAL -->
<?php if ($order_success): ?>
<div class="order-modal-overlay active" id="orderModal">
    <div class="order-modal-card">
        <div class="order-modal-body">
            <h2>🎉 Order Placed!</h2>
            <p>Your order <strong>#<?php echo $order_id; ?></strong> has been received.</p>
            <?php
                $check_receipt = mysqli_query($conn, "SELECT gcash_receipt_status FROM orders WHERE order_id = '$order_id'");
                $receipt_row   = mysqli_fetch_assoc($check_receipt);
            ?>
            <?php if ($receipt_row && $receipt_row['gcash_receipt_status'] === 'pending_verification'): ?>
                <p style="color:#f39c12; font-size:13px;">
                    ⏳ Your GCash receipt is being reviewed by our staff. Your order will be prepared once verified.
                </p>
            <?php else: ?>
                <p>We'll prepare it for pickup. Thank you, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
            <?php endif; ?>
            <div class="order-modal-actions">
                <a href="receipt.php?order_id=<?php echo $order_id; ?>" class="btn-open-receipt">🧾 View Order</a>
                <a href="menu.php" class="btn-order-again-modal">Order Again</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- REMOVE CONFIRMATION MODAL -->
<div id="remove-modal" class="modal-overlay-remove">
    <div class="modal-card1">
        <p id="remove-modal-msg"></p>
        <div class="modal-buttons">
            <button id="remove-cancel-btn" class="btn-secondary">Keep it</button>
            <button id="remove-confirm-btn" class="btn-danger">Remove</button>
        </div>
    </div>
</div>

<?php $ban_check_render = true; include "ban-check.php"; ?>

<script>
window.IS_LOGGED_IN = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
window.__initialAvailability = <?php
    $avail_q    = mysqli_query($conn, "SELECT product_id, is_available FROM products");
    $avail_data = [];
    while ($r = mysqli_fetch_assoc($avail_q)) $avail_data[$r['product_id']] = (int)$r['is_available'];
    echo json_encode($avail_data);
?>;
window.IS_BUY_AGAIN = <?php
    echo isset($_SESSION['buy_again_order']) ? 'true' : 'false';
    unset($_SESSION['buy_again_order']);
?>;

// ── GCash Modal logic ─────────────────────────────────────────────────────────
let selectedReceiptFile = null;

function openGCashModal() {
    const modal = document.getElementById('gcashModal');
    if (!modal) return;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeGCashModal() {
    const modal = document.getElementById('gcashModal');
    if (!modal) return;
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

function goToStep2() {
    document.getElementById('gcash-step-1').classList.remove('active');
    document.getElementById('gcash-step-2').classList.add('active');
    const s1   = document.getElementById('step-indicator-1');
    const s2   = document.getElementById('step-indicator-2');
    const line = document.getElementById('step-line');
    s1.classList.remove('active');
    s1.classList.add('done');
    document.getElementById('step-num-1').innerHTML = '✓';
    s2.classList.add('active');
    line.classList.add('done');
}

function goToStep1() {
    document.getElementById('gcash-step-2').classList.remove('active');
    document.getElementById('gcash-step-1').classList.add('active');
    const s1   = document.getElementById('step-indicator-1');
    const s2   = document.getElementById('step-indicator-2');
    const line = document.getElementById('step-line');
    s1.classList.add('active');
    s1.classList.remove('done');
    document.getElementById('step-num-1').innerHTML = '1';
    s2.classList.remove('active');
    line.classList.remove('done');
}

function handleReceiptSelect(input) {
    if (!input.files || !input.files[0]) return;
    selectedReceiptFile = input.files[0];
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('receiptPreview').style.display = 'block';
        document.getElementById('uploadZone').style.display = 'none';
        document.getElementById('confirmOrderBtn').disabled = false;
    };
    reader.readAsDataURL(selectedReceiptFile);
}

function removeReceipt() {
    selectedReceiptFile = null;
    document.getElementById('receiptPreview').style.display = 'none';
    document.getElementById('uploadZone').style.display = 'block';
    document.getElementById('confirmOrderBtn').disabled = true;
    document.getElementById('receiptFileInput').value = '';
}

// Drag & drop
const uploadZone = document.getElementById('uploadZone');
if (uploadZone) {
    uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('dragover'); });
    uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragover'));
    uploadZone.addEventListener('drop', e => {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            selectedReceiptFile = file;
            const reader = new FileReader();
            reader.onload = evt => {
                document.getElementById('previewImg').src = evt.target.result;
                document.getElementById('receiptPreview').style.display = 'block';
                document.getElementById('uploadZone').style.display = 'none';
                document.getElementById('confirmOrderBtn').disabled = false;
            };
            reader.readAsDataURL(file);
        }
    });
}

function submitOrderWithReceipt() {
    if (!selectedReceiptFile) return;
    const btn = document.getElementById('confirmOrderBtn');
    btn.disabled = true;
    btn.textContent = 'Placing Order...';

    document.getElementById('hidden_pickup_time').value = document.getElementById('pick-up-time').value;
    document.getElementById('hidden_notes').value = document.getElementById('barista-note').value;

    const dt = new DataTransfer();
    dt.items.add(selectedReceiptFile);
    document.getElementById('hiddenReceiptInput').files = dt.files;

    document.getElementById('orderForm').submit();
}

// Close modal on overlay click
document.getElementById('gcashModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeGCashModal();
});


// Clear order_success params from URL after modal shows
if (window.location.search.includes('order_success=1')) {
    const cleanUrl = window.location.pathname;
    window.history.replaceState({}, document.title, cleanUrl);
}
</script>

<script src="js/global.js"></script>
<script src="js/cart.js"></script>
</body>

<footer class="main-footer">
    <div class="footer-container">
        <div class="footer-brand">
            <div class="footer-logo">
                <img src="assets/logo/bb-maysan-logo-1.png" alt="Big Brew Maysan Logo" />
            </div>
            <p>BIGBREW - Maysan, Valenzuela City</p>
        </div>
        <div class="footer-column">
            <h3>Contact</h3>
            <ul>
                <li>0929 563 4350</li>
                <li>094 Maysan Rd, Valenzuela, 1442 Metro Manila</li>
            </ul>
        </div>
        <div class="footer-column">
            <h3>Quick Links</h3>
            <div class="links-grid">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="menu.php">Menu</a></li>
                    <li><a href="about.php">About</a></li>
                </ul>
                <ul>
                    <li><a href="reviews.php">Reviews</a></li>
                    <li><a href="terms.php">Terms &amp; Conditions</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-column">
            <h3>Follow us on</h3>
            <div class="social-icons">
                <a href="https://www.facebook.com/p/BigBrew-Maysan-Rd-Valenzuela-100085267776562/"><i class="fab fa-facebook"></i></a>
                <a href="https://www.instagram.com/bigbrew.maysan/"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2026. BigBrew Maysan. All Rights Reserved.</p>
    </div>
</footer>
</html>