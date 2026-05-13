<?php
session_start();
include "db.php";
date_default_timezone_set('Asia/Manila');

$currentTime = date('H:i');
$openingTime = '11:00';
$closingTime = '23:59';

$isStoreOpen = ($currentTime >= $openingTime && $currentTime < $closingTime);
$storeStatusText = $isStoreOpen ? 'Open' : 'Closed';
$storeStatusClass = $isStoreOpen ? 'open' : 'closed';
$now = time();
$closingTimestamp = strtotime(date('Y-m-d') . ' ' . $closingTime);

$pickup_options = [
    'asap' => [
        'label' => 'ASAP',
        'minutes' => 30
    ],
    'in-30-min' => [
        'label' => 'In 30 minutes',
        'minutes' => 30
    ],
    'in-45-min' => [
        'label' => 'In 45 minutes',
        'minutes' => 45
    ],
    'in-1-hour' => [
        'label' => 'In 1 hour',
        'minutes' => 60
    ],
    'in-1-5-hour' => [
        'label' => 'In 1 hour 30 minutes',
        'minutes' => 90
    ],
    'in-2-hours' => [
        'label' => 'In 2 hours',
        'minutes' => 120
    ],
];
$now = time();

$asap_start = date('g:i A', strtotime('+15 minutes', $now));
$asap_end   = date('g:i A', strtotime('+30 minutes', $now));

$time_15 = date('g:i A', strtotime('+15 minutes', $now));
$time_30 = date('g:i A', strtotime('+30 minutes', $now));
$time_45 = date('g:i A', strtotime('+45 minutes', $now));
$time_60 = date('g:i A', strtotime('+1 hour', $now));
$time_90 = date('g:i A', strtotime('+1 hour 30 minutes', $now));

$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;

$order_success = false;
$order_id      = null;
$message       = "";

if(isset($_SESSION['order_success'])) {
    $order_success = true;
    $order_id = $_SESSION['order_id'];

    unset($_SESSION['order_success']);
    unset($_SESSION['order_id']);
}

if(isset($_POST['place_order']) && $isLoggedIn) {
    $pickup_time = $_POST['pickup_time'];
    $notes       = mysqli_real_escape_string($conn, $_POST['notes']);
    if(!$isStoreOpen) {
        $message = "Sorry, we're currently closed. Our store hours are 11:00 AM - 9:00 PM.";
    } else {
    $unavail_q = mysqli_query($conn,
        "SELECT p.product_name FROM cart_items ci
         JOIN cart c ON ci.cart_id = c.cart_id
         JOIN products p ON ci.product_id = p.product_id
         WHERE c.user_id = '$user_id' AND p.is_available = 0"
    );
    if(mysqli_num_rows($unavail_q) > 0) {
        $unavail_names = [];
        while($row = mysqli_fetch_assoc($unavail_q)) {
            $unavail_names[] = $row['product_name'];
        }
        $message = "Some items are no longer available: " . implode(', ', $unavail_names) . ". Please remove them before checking out.";
    } else {
    
    $cart_q = mysqli_query($conn, "SELECT cart_id FROM cart WHERE user_id='$user_id'");

    if(mysqli_num_rows($cart_q) === 0) {
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

        if(mysqli_num_rows($items_q) === 0) {
            $message = "Your cart is empty!";
        } else {
            $total = 0;
            $items_array = [];
            while($item = mysqli_fetch_assoc($items_q)) {
                $total += $item['unit_price'] * $item['quantity'];
                $items_array[] = $item;
            }

            mysqli_query($conn,
                "INSERT INTO orders (user_id, total_amount, pickup_time, notes, order_status, created_at)
                 VALUES ('$user_id', '$total', '$pickup_time', '$notes', 'pending', NOW())"
            );
            $order_id = mysqli_insert_id($conn);

            foreach($items_array as $item) {
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

// ✅ SAVE TO SESSION
$_SESSION['order_success'] = true;
$_SESSION['order_id'] = $order_id;

// ✅ REDIRECT (VERY IMPORTANT)
header("Location: cart.php");
exit();
        }
    }
}
}
}

// LOAD CART ITEMS
$cart_items = [];
$subtotal   = 0;

if($isLoggedIn) {
    // LOGGED IN CART FROM DATABASE
    $cart_q = mysqli_query($conn, "SELECT cart_id FROM cart WHERE user_id='$user_id'");

    if(mysqli_num_rows($cart_q) > 0) {
        $cart    = mysqli_fetch_assoc($cart_q);
        $cart_id = $cart['cart_id'];

        $items_q = mysqli_query($conn,
    "SELECT ci.cart_item_id, ci.product_id, ci.quantity, ci.addons, ci.unit_price,
            p.product_name, p.image, p.category, p.is_available,
            COALESCE(ps.size_name, 'Unknown Size') as size_name
     FROM cart_items ci
     JOIN products p        ON ci.product_id = p.product_id
     LEFT JOIN product_sizes ps ON ci.size_id = ps.size_id
     WHERE ci.cart_id = '$cart_id'"
);

        while($row = mysqli_fetch_assoc($items_q)) {
            $cart_items[] = $row;
            $subtotal += $row['unit_price'] * $row['quantity'];
        }
    }
} else {
    // GUEST CART FROM SESSION
    if(isset($_SESSION['guest_cart']) && !empty($_SESSION['guest_cart'])) {
        foreach($_SESSION['guest_cart'] as $key => $guestItem) {
            $product_id = intval($guestItem['product_id']);
            $size_id    = intval($guestItem['size_id']);

            $item_q = mysqli_query($conn,
                "SELECT p.product_name, p.image, p.category, p.is_available, ps.size_name
                 FROM products p
                 JOIN product_sizes ps ON p.product_id = ps.product_id
                 WHERE p.product_id = '$product_id'
                 AND ps.size_id = '$size_id'
                 LIMIT 1"
            );

            if(mysqli_num_rows($item_q) > 0) {
                $info = mysqli_fetch_assoc($item_q);

                $cart_items[] = [
                    'cart_item_id' => $key,
                    'quantity'    => $guestItem['quantity'],
                    'addons'      => $guestItem['addons'],
                    'unit_price'  => $guestItem['unit_price'],
                    'product_name'=> $info['product_name'],
                    'image'       => $info['image'],
                    'category'    => $info['category'],
                    'size_name'   => $info['size_name'],
                    'is_available' => $info['is_available'],
                    'product_id'   => $product_id, 
                    'is_guest'    => true
                ];

                $subtotal += $guestItem['unit_price'] * $guestItem['quantity'];
            }
        }
    }
}?>
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
                <li>
                  <a href="cart.php" class="cart-link">
                    <img src="assets/icons/icons8-cart-24.png" alt="">
                    <span id="cartBadge" class="cart-badge">0</span>
                  </a>
                </li>
                <li><a href="account.php"> <img src="assets/icons/icons8-profile-24.png" alt=""></a></li>
                </ul>
            </div>

            <div class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
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


<?php if(!$order_success): ?>

    <?php if(empty($cart_items)): ?>
        <!-- EMPTY CART STATE -->
        <div class="empty-cart">
            <h3>Your Cart</h3>

            <?php if(!$isLoggedIn): ?>
                <p>Your cart is empty. Browse the menu and add your favorite drinks.</p>
            <?php else: ?>
                <p>Your cart is empty.</p>
            <?php endif; ?>

            <a href="menu.php">Browse Menu</a>
        </div>

    <?php else: ?>

        <!-- CART ITEMS -->
        <div id="cart-items-container">
            <div class="cart-subheader">
                <h3 id="cart-title">Your Cart</h3>
                <a href="menu.php#menu-section"><p> + add item </p></a>
            </div>

            <?php foreach($cart_items as $item): ?>
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
                        <?php if(!$item['is_available']): ?>
                            <span class="unavailable-tag">⚠️ No longer available</span>
                        <?php endif; ?>
                        <p class="cart-product-category">
                            <?php echo ucwords(str_replace('-', ' ', htmlspecialchars($item['category']))); ?>
                        </p>

                        <p>
                            <?php echo htmlspecialchars($item['size_name']); ?>

                            <?php if(!empty($item['addons'])): ?>
                                · <?php echo htmlspecialchars($item['addons']); ?>
                            <?php endif; ?>
                        </p>

                        <div class="qty-Stepper">
                                <button type="button" onclick="updateQty('<?php echo $item['cart_item_id']; ?>', -1)">-</button>

                            <span id="qty-<?php echo $item['cart_item_id']; ?>">
                                <?php echo $item['quantity']; ?>
                            </span>

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
    <?php if(!$order_success && !empty($cart_items)): ?>
    <aside>
        <div class="aside-content">
                    <form method="POST" action="">
            <div class="order-summary-content">
                <h4>Order Summary</h4>
                <div class="display-time">
                    <i class="fa-solid fa-clock"></i> Pickup Time
                </div>
            <select name="pickup_time" id="pick-up-time" class="summary-input">
                <?php foreach($pickup_options as $value => $option): ?>
                    <?php
                        if($value === 'asap') {
                            $start = date('g:i A', strtotime('+15 minutes', $now));
                            $end   = date('g:i A', strtotime('+30 minutes', $now));
                            $display = "ASAP ({$start} - {$end})";
                            $optionEndTime = strtotime('+30 minutes', $now);
                        } else {
                            $pickupTime = date('g:i A', strtotime('+' . $option['minutes'] . ' minutes', $now));
                            $display = $option['label'] . " ({$pickupTime})";
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
                <div class="payment-fixed">
                    <span>Pay upon Pickup</span>
                </div>

                <div class="summary-label">Note to Barista</div>
                <textarea name="notes" id="barista-note" class="note-barista"
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

                <?php if($isLoggedIn): ?>
                    <button type="submit" name="place_order" class="checkout-btn"
                        <?php echo !$isStoreOpen ? 'disabled title="Store is currently closed"' : ''; ?>>
                        <?php echo $isStoreOpen ? 'Checkout' : '🔒 Store Closed'; ?>
                    </button>
                    <?php if(!$isStoreOpen): ?>
                        <p class="store-closed-msg">We're closed right now. Come back between 11:00 AM - 9:00 PM!</p>
                    <?php endif; ?>
                <?php else: ?>

                <button type="button" class="checkout-btn login-required-btn">
                    Login to Checkout
                </button>

                <?php endif; ?>
            </div>
        </form>

        </div>
    </aside>
    <?php endif; ?>
</div>

<!-- ORDER PLACED MODAL — lalabas lang kapag nag-success ang order -->
<?php if($order_success): ?>
<div class="order-modal-overlay active" id="orderModal">
    <div class="order-modal-card">

      <div class="order-modal-body">
            <h2>🎉 Order Placed!</h2>
            <p>Your order <strong>#<?php echo $order_id; ?></strong> has been received.</p>
            <p>We'll prepare it for pickup. Thank you, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
            <div class="order-modal-actions">
                <a href="receipt.php?order_id=<?php echo $order_id; ?>" class="btn-open-receipt">
                    🧾 View Order
                </a>
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
<script>
  window.IS_LOGGED_IN = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

window.__initialAvailability = <?php
    $avail_q = mysqli_query($conn, "SELECT product_id, is_available FROM products");
    $avail_data = [];
    while($r = mysqli_fetch_assoc($avail_q)) {
        $avail_data[$r['product_id']] = (int)$r['is_available'];
    }
    echo json_encode($avail_data);
?>;
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
                    <li><a href="terms.php">Terms & Conditions</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-column">
            <h3>Follow us on</h3>
            <div class="social-icons">
                <a href="https://www.facebook.com/p/BigBrew-Maysan-Rd-Valenzuela-100085267776562/">
                    <i class="fab fa-facebook"></i></a>
                <a href="https://www.instagram.com/bigbrew.maysan/">
                    <i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2026. BigBrew Maysan. All Rights Reserved.</p>
    </div>
</footer>
</html>