<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$order_success = false;
$order_id      = null;
$message       = "";

if(isset($_POST['place_order'])) {
    $pickup_time = $_POST['pickup_time'];
    $notes       = mysqli_real_escape_string($conn, $_POST['notes']);

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
            $order_success = true;
        }
    }
}

// LOAD CART ITEMS
$cart_q     = mysqli_query($conn, "SELECT cart_id FROM cart WHERE user_id='$user_id'");
$cart_items = [];
$subtotal   = 0;

if(mysqli_num_rows($cart_q) > 0) {
    $cart    = mysqli_fetch_assoc($cart_q);
    $cart_id = $cart['cart_id'];

    $items_q = mysqli_query($conn,
        "SELECT ci.cart_item_id, ci.quantity, ci.addons, ci.unit_price,
                p.product_name, p.image,
                ps.size_name
         FROM cart_items ci
         JOIN products p       ON ci.product_id = p.product_id
         JOIN product_sizes ps ON ci.size_id    = ps.size_id
         WHERE ci.cart_id = '$cart_id'"
    );

    while($row = mysqli_fetch_assoc($items_q)) {
        $cart_items[] = $row;
        $subtotal += $row['unit_price'] * $row['quantity'];
    }
}
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
                <li><a href="about.html">About Us</a></li>
                <li>
                  <a href="#" class="cart-link">
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
            <div>
                <h1>Bigbrew Maysan</h1>
                <div class="store-status"><span>open</span></div>
                <p>094 Maysan Rd, Valenzuela, 1442 Metro Manila</p>
            </div>
            <div><img src="assets/pictures/store-pic.jpg" alt="" /></div>
        </div>

        <?php if($message != ""): ?>
            <p class="error-msg"><?php echo $message; ?></p>
        <?php endif; ?>

        <?php if(!$order_success): ?>
            <?php if(empty($cart_items)): ?>
                <!-- EMPTY CART STATE -->
                <div class="empty-cart">
                    <h3>Your Cart</h3>
                    <p>Your cart is empty.</p>
                    <a href="menu.php">Browse Menu</a>
                </div>
            <?php else: ?>
                <!-- CART ITEMS -->
                <div id="cart-items-container">
                    <h3 id="cart-title">Your Cart</h3>
                    <?php foreach($cart_items as $item): ?>
                    <div class="cart-item"
                         id="cart-card-<?php echo $item['cart_item_id']; ?>"
                         data-cart-item-id="<?php echo $item['cart_item_id']; ?>"
                         data-unit-price="<?php echo $item['unit_price']; ?>">

                        <img src="assets/products/<?php echo htmlspecialchars($item['image']); ?>"
                             alt="<?php echo htmlspecialchars($item['product_name']); ?>">

                        <div class="cart-item-details">
                            <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                            <p><?php echo htmlspecialchars($item['size_name']); ?>
                                <?php if(!empty($item['addons'])): ?>
                                    · <?php echo htmlspecialchars($item['addons']); ?>
                                <?php endif; ?>
                            </p>
                            <div class="qty-stepper">
                                <button onclick="updateQty(<?php echo $item['cart_item_id']; ?>, -1)">-</button>
                                <span id="qty-<?php echo $item['cart_item_id']; ?>">
                                    <?php echo $item['quantity']; ?>
                                </span>
                                <button onclick="updateQty(<?php echo $item['cart_item_id']; ?>, +1)">+</button>
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
        <form method="POST" action="">
            <div class="order-summary-content">
                <h4>Order Summary</h4>
                <div class="display-time">
                    <i class="fa-solid fa-clock"></i> Pickup Time
                </div>
                <select name="pickup_time" id="pick-up-time" class="summary-input">
                    <option value="asap">ASAP</option>
                    <option value="in-15-min">In 15 minutes</option>
                    <option value="in-30-min">In 30 minutes</option>
                    <option value="in-45-min">In 45 minutes</option>
                    <option value="in-1-hour">In 1 hour</option>
                    <option value="in-1-5-hour">In 1 hour 30 minutes</option>
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

                <button type="submit" name="place_order" class="checkout-btn">
                    Checkout
                </button>
            </div>
        </form>
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
                    🧾 View Receipt
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
                <li>info@bigbrew.com</li>
                <li>094 Maysan Rd, Valenzuela, 1442 Metro Manila</li>
            </ul>
        </div>
        <div class="footer-column">
            <h3>Quick Links</h3>
            <div class="links-grid">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="menu.php">Menu</a></li>
                    <li><a href="about.html">About</a></li>
                </ul>
                <ul>
                    <li><a href="reviews.html">Reviews</a></li>
                    <li><a href="terms.html">Terms & Conditions</a></li>
                    <li><a href="privacy.html">Privacy Policy</a></li>
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