<?php
session_start();
include "db.php";
include "ban-check.php";

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

// Check if already reviewed — gawin AFTER $user_id is defined
$already_reviewed = false;
$rev_check = mysqli_query($conn,
    "SELECT review_id FROM reviews WHERE user_id = '$user_id' LIMIT 1"
);
if (mysqli_num_rows($rev_check) > 0) {
    $already_reviewed = true;
}

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

$status = strtolower($order['order_status']);

$pickup_value = trim($order['pickup_time']);
$created_at   = !empty($order['created_at']) ? strtotime($order['created_at']) : time();

if($status === 'completed') {
    if(!empty($order['completed_at'])) {
        $pickup_display = date('g:i A', strtotime($order['completed_at']));
    } else {
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
    $receipt_subtitle = 'Thank you, Brew! Buy again.';
} elseif($status === 'cancelled') {
    $receipt_title    = 'Order Cancelled';
    if (!empty($order['cancelled_by']) && $order['cancelled_by'] === 'staff' && $order['cancel_reason'] === 'no_show') {
        $ns_q    = mysqli_query($conn, "SELECT no_show_count FROM users WHERE user_id = '$user_id'");
        $ns_row  = mysqli_fetch_assoc($ns_q);
        $ns_count = (int)($ns_row['no_show_count'] ?? 0);
        if ($ns_count === 1) {
            $receipt_subtitle = '⚠️ Your order was cancelled because you did not pick it up in time. This is your <strong>1st warning</strong> — a 2nd no-show will result in a 7-day suspension.';
        } elseif ($ns_count === 2) {
            $receipt_subtitle = '🚫 Your order was cancelled due to no-show. Your account has been <strong>suspended for 7 days</strong> due to repeated no-shows.';
        } else {
            $receipt_subtitle = '🚫 Your order was cancelled due to no-show. Your account has been <strong>permanently suspended</strong>. Please contact us to appeal.';
        }
    } elseif (!empty($order['cancelled_by']) && $order['cancelled_by'] === 'staff') {
        $receipt_subtitle = 'Your order was cancelled by the store. This may be due to an out-of-stock item or other reasons. You may try ordering again or contact us for details.';
    } else {
        $receipt_subtitle = 'You cancelled this order.';
    }
} else {
    $receipt_title    = 'Order Updated';
    $receipt_subtitle = 'Your order status has been updated.';
}

// GCash fields
$gcash_receipt_status   = $order['gcash_receipt_status']   ?? 'not_required';
$gcash_rejection_reason = $order['gcash_rejection_reason'] ?? '';
$gcash_downpayment      = $order['gcash_downpayment']      ?? 0;
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

    <style>
        /* ── GCash Status Box ─────────────────────────────────────────────── */
        .gcash-status-box {
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            text-align: center;
            font-family: 'Poppins', sans-serif;
        }
        .gcash-status-box.pending_verification {
            background: #fffbea;
            border: 1.5px solid #ffe082;
        }
        .gcash-status-box.verified {
            background: #f0fff4;
            border: 1.5px solid #81c784;
        }
        .gcash-status-box.rejected {
            background: #fff5f5;
            border: 1.5px solid #e57373;
        }
        .gcash-status-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }
        .gcash-status-box h4 {
            font-size: 15px;
            font-weight: 700;
            margin: 0 0 6px;
            color: #1a1a1a;
        }
        .gcash-status-box p {
            font-size: 13px;
            color: #555;
            margin: 0 0 12px;
            line-height: 1.5;
        }
        .gcash-dp-pill {
            display: inline-block;
            background: #fff3cd;
            border: 1px solid #ffe082;
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 13px;
            color: #6d4c00;
        }
        .gcash-dp-pill.verified {
            background: #e8f5e9;
            border-color: #81c784;
            color: #2e7d32;
        }
        .gcash-reupload-btn {
            display: inline-block;
            margin-top: 8px;
            padding: 10px 20px;
            background: #e53935;
            color: #fff;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
        }
        .gcash-reupload-btn:hover { background: #c62828; }
    </style>
</head>
<body>

    <!-- TOP BAR -->
    <div class="topbar">
        <a href="javascript:history.back()" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        </a>
    </div>

    <!-- RECEIPT CARD -->
    <div class="receipt-card">

        <div class="receipt-header">
            <div class="check-circle <?php echo $status; ?>">
                <?php
                if($status === 'pending')              echo '<i class="fa-solid fa-clock"></i>';
                elseif($status === 'preparing')        echo '<i class="fa-solid fa-blender"></i>';
                elseif($status === 'ready_for_pickup') echo '<i class="fa-solid fa-bell"></i>';
                elseif($status === 'completed')        echo '<i class="fa-solid fa-circle-check"></i>';
                elseif($status === 'cancelled')        echo '<i class="fa-solid fa-circle-xmark"></i>';
                else                                   echo '<i class="fa-solid fa-check"></i>';
                ?>
            </div>
    <div class="receipt-card">

        <div class="receipt-header">
            <div class="check-circle <?php echo $status; ?>">
                <?php
                if($status === 'pending')              echo '<i class="fa-solid fa-clock"></i>';
                elseif($status === 'preparing')        echo '<i class="fa-solid fa-blender"></i>';
                elseif($status === 'ready_for_pickup') echo '<i class="fa-solid fa-bell"></i>';
                elseif($status === 'completed')        echo '<i class="fa-solid fa-circle-check"></i>';
                elseif($status === 'cancelled')        echo '<i class="fa-solid fa-circle-xmark"></i>';
                else                                   echo '<i class="fa-solid fa-check"></i>';
                ?>
            </div>

            <div>
                <h2 class="receipt-title"><?php echo $receipt_title; ?></h2>

                <?php if($status === 'preparing' || $status === 'ready_for_pickup'): ?>
                    <p class="receipt-pickup-time">
                        <i class="fa-solid fa-clock"></i>
                        Pick-up at: <?php echo htmlspecialchars($pickup_display ?? 'ASAP'); ?>
                    </p>
                <?php endif; ?>
                <?php if($status === 'preparing' || $status === 'ready_for_pickup'): ?>
                    <p class="receipt-pickup-time">
                        <i class="fa-solid fa-clock"></i>
                        Pick-up at: <?php echo htmlspecialchars($pickup_display ?? 'ASAP'); ?>
                    </p>
                <?php endif; ?>

                <p class="receipt-subtitle"><?php echo $receipt_subtitle; ?></p>
            </div>
        </div>

        <div class="receipt-main-box">
            <div class="receipt-id-row">
                <span>Order ID</span>
                <strong># <?php echo $order_id; ?></strong>
            </div>
        <div class="receipt-main-box">
            <div class="receipt-id-row">
                <span>Order ID</span>
                <strong># <?php echo $order_id; ?></strong>
            </div>

            <p class="section-label">Customer Details</p>
            <p class="section-label">Customer Details</p>

            <div class="detail-line">
                <span>Name:</span>
                <strong><?php echo htmlspecialchars($customer_name); ?></strong>
            </div>
            <div class="detail-line">
                <span>Name:</span>
                <strong><?php echo htmlspecialchars($customer_name); ?></strong>
            </div>

            <div class="detail-line">
                <span>Mode of Payment:</span>
                <strong>Pay upon Pickup</strong>
            </div>
            <div class="detail-line">
                <span>Mode of Payment:</span>
                <strong>Pay upon Pickup</strong>
            </div>

            <div class="detail-line">
                <span><?php echo $status === 'completed' ? 'Picked up at:' : 'Self Pick-up:'; ?></span>
                <strong><?php echo htmlspecialchars($pickup_display ?? 'ASAP'); ?></strong>
            </div>

            <?php if(!empty($order['notes'])): ?>
            <div class="detail-line">
                <span>Notes:</span>
                <strong><?php echo htmlspecialchars($order['notes']); ?></strong>
            </div>
            <?php endif; ?>

            <hr>
            <hr>

            <p class="section-label">Items</p>
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
                    <strong>P <?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></strong>
                </div>
                <?php if(!empty($item['addons'])): ?>
                    <p class="item-addons">Add-ons: <?php echo htmlspecialchars($item['addons']); ?></p>
                <?php endif; ?>
            <?php endforeach; ?>

            <div class="total-row">
                <span>Total</span>
                <strong>P <?php echo number_format($order['total_amount'], 2); ?></strong>
            </div>
        </div>

        <!-- ── GCash Receipt Status ──────────────────────────────────────── -->
        <?php if ($gcash_receipt_status !== 'not_required'): ?>
        <div class="gcash-status-box <?php echo htmlspecialchars($gcash_receipt_status); ?>">

            <?php if ($gcash_receipt_status === 'pending_verification'): ?>
                <div class="gcash-status-icon">⏳</div>
                <h4>GCash Receipt Pending</h4>
                <p>Your GCash downpayment receipt is being reviewed by our staff. We'll update you once verified.</p>
                <?php if ($gcash_downpayment): ?>
                    <div class="gcash-dp-pill">Downpayment: <strong>₱<?php echo number_format($gcash_downpayment, 2); ?></strong></div>
                <?php endif; ?>

            <?php elseif ($gcash_receipt_status === 'verified'): ?>
                <div class="gcash-status-icon">✅</div>
                <h4>GCash Receipt Verified</h4>
                <p>Your downpayment has been confirmed. Your order is being processed!</p>
                <?php if ($gcash_downpayment): ?>
                    <div class="gcash-dp-pill verified">Downpayment: <strong>₱<?php echo number_format($gcash_downpayment, 2); ?></strong></div>
                <?php endif; ?>

            <?php elseif ($gcash_receipt_status === 'rejected'): ?>
                <div class="gcash-status-icon">❌</div>
                <h4>GCash Receipt Rejected</h4>
                <?php if (!empty($gcash_rejection_reason)): ?>
                    <p>Reason: <strong><?php echo htmlspecialchars($gcash_rejection_reason); ?></strong></p>
                <?php else: ?>
                    <p>Your receipt was not accepted. Please contact the store for assistance.</p>
                <?php endif; ?>
                <a href="cart.php" class="gcash-reupload-btn">Re-upload Receipt</a>
            <?php endif; ?>

        </div>
        <?php endif; ?>

        <div class="pickup-box">
            <h4>Pickup Instructions</h4>
            <ol>
                <li>
                    <span>Pick up your order at <?php echo htmlspecialchars($pickup_display ?? 'ASAP'); ?></span>
                    <p>Please claim your order within 30 minutes. Should you arrive late, beverages may not be remade.</p>
                </li>
                <li><span>Show your order ID at the counter</span></li>
                <li><span>Enjoy your drinks!</span></li>
            </ol>
        </div>

        <div class="receipt-actions">
            <?php if($status === 'pending'): ?>
                <button class="btn-cancel-order btn-full" onclick="showCancelModal()">Cancel Order</button>

                <div id="cancelModal" class="auth-modal-overlay" style="display:none;">
                    <div class="auth-modal-card">
                        <div class="auth-modal-icon">🗑️</div>
                        <h3>Cancel Order?</h3>
                        <p>Are you sure you want to cancel this order? This cannot be undone.</p>
                        <div class="auth-modal-actions">
                            <button class="auth-btn-secondary" onclick="closeCancelModal()">Go Back</button>
                            <form method="POST" action="cancel_order.php" style="width:100%;">
                                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                <button type="submit" class="auth-btn-danger">Yes, Cancel Order</button>
                            </form>
                        </div>
                    </div>
                </div>

            <?php elseif($status === 'preparing' || $status === 'ready_for_pickup'): ?>
                <button class="btn-cancel-order btn-full btn-disabled-cancel" disabled
                        title="Order cannot be cancelled once preparation has started.">
                    Cancel Order
                </button>


            <?php elseif($status === 'completed'): ?>
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
                    <button class="btn-cancel-order btn-disabled-cancel" disabled
                            title="Completed orders cannot be cancelled.">
                        Cancel Order
                    </button>
                    <form method="POST" action="buy_again.php" style="flex:1;">
                        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                        <button type="submit" class="btn-buy-again">Buy Again</button>
                    </form>
                </div>

            <?php elseif($status === 'cancelled'): ?>
                <div class="receipt-btn-row">
                    <form method="POST" action="buy_again.php" style="flex:1;">
                        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                        <button type="submit" class="btn-buy-again">Buy Again</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

    </div><!-- /.receipt-card -->

<script>
const ORDER_ID    = <?php echo $order_id; ?>;
const ORDER_ID    = <?php echo $order_id; ?>;
const CURR_STATUS = '<?php echo $status; ?>';
const CURR_GCASH_STATUS = '<?php echo $gcash_receipt_status; ?>';

// Poll kung hindi pa completed o cancelled
if (CURR_STATUS !== 'completed' && CURR_STATUS !== 'cancelled') {
    let lastStatus      = CURR_STATUS;
    let lastGcashStatus = CURR_GCASH_STATUS;

    function pollStatus() {
        fetch(`get_orders.php?order_id=${ORDER_ID}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) return;
                // Reload kung nagbago ang order status O gcash receipt status
                if (data.order_status !== lastStatus || data.gcash_receipt_status !== lastGcashStatus) {
                    location.reload();
                }
            })
            .catch(err => console.warn('Poll failed:', err));
    }

    setInterval(pollStatus, 3000);
}

function showCancelModal() {
    document.getElementById('cancelModal').style.display = 'flex';
}
function closeCancelModal() {
    document.getElementById('cancelModal').style.display = 'none';
}
document.getElementById('cancelModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeCancelModal();
});
</script>

<?php $ban_check_render = true; include "ban-check.php"; ?>
</body>

<?php $ban_check_render = true; include "ban-check.php"; ?>
</body>
</html>