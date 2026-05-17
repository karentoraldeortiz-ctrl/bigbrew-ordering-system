
<?php
session_start();
if (!isset($_SESSION['staff_logged_in'])) {
    header("Location: login.php");
    exit;
}
include "../db.php";

// UPDATE ORDER STATUS
if (isset($_POST['update_status'])) {
    $order_id = (int) $_POST['order_id'];

    $current_q = mysqli_query($conn, "SELECT o.order_status, o.user_id FROM orders o WHERE o.order_id = '$order_id'");
    $current = mysqli_fetch_assoc($current_q);

    if (!in_array($current['order_status'], ['completed', 'cancelled'])) {
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $cancel_reason = isset($_POST['cancel_reason_staff'])
        ? mysqli_real_escape_string($conn, $_POST['cancel_reason_staff'])
        : 'other';

        if ($status === 'completed') {
            mysqli_query($conn, "UPDATE orders SET order_status = 'completed', completed_at = NOW(), cancelled_by = NULL, cancel_reason_staff = NULL WHERE order_id = '$order_id'");
        } elseif ($status === 'cancelled') {
            mysqli_query($conn, "UPDATE orders SET order_status = 'cancelled', cancelled_by = 'staff', cancel_reason_staff = '$cancel_reason', completed_at = NULL WHERE order_id = '$order_id'");

            if ($cancel_reason === 'no_show') {
                $uid = $current['user_id'];
                $uq = mysqli_query($conn, "SELECT no_show_count FROM users WHERE user_id = '$uid'");
                $urow = mysqli_fetch_assoc($uq);
                $new_count = $urow['no_show_count'] + 1;

                if ($new_count === 1) {
                    mysqli_query($conn, "UPDATE users SET no_show_count = $new_count WHERE user_id = '$uid'");
                } elseif ($new_count === 2) {
                    mysqli_query($conn, "UPDATE users SET no_show_count = $new_count, ban_status = 'temp_banned', ban_until = DATE_ADD(NOW(), INTERVAL 7 DAY) WHERE user_id = '$uid'");
                } else {
                    mysqli_query($conn, "UPDATE users SET no_show_count = $new_count, ban_status = 'banned', ban_until = NULL WHERE user_id = '$uid'");
                }
            }
        } else {
            mysqli_query($conn, "UPDATE orders SET order_status = '$status', completed_at = NULL, cancelled_by = NULL, cancel_reason_staff = NULL WHERE order_id = '$order_id'");
        }
    }
}
// FETCH ORDERS
$filter = isset($_GET['filter']) && $_GET['filter'] === 'pending' ? "WHERE o.order_status = 'pending'" : "";
$orders_q = mysqli_query($conn,
    "SELECT o.order_id, o.order_status, o.total_amount, o.pickup_time, o.notes, o.created_at, u.full_name
     FROM orders o
     JOIN users u ON o.user_id = u.user_id
     $filter
     ORDER BY o.created_at DESC"
);

date_default_timezone_set('Asia/Manila');

function getPickupDisplay($pickup_value, $created_at) {
    $pickup_value = trim($pickup_value);
    $created_at_time = !empty($created_at) ? strtotime($created_at) : time();

    if($pickup_value === 'asap') {
        $start_time = date('g:i A', strtotime('+15 minutes', $created_at_time));
        $end_time   = date('g:i A', strtotime('+30 minutes', $created_at_time));

        return "ASAP ({$start_time} - {$end_time})";
    }

    $pickup_labels = [
        'in-15-min'   => 'In 15 minutes',
        'in-30-min'   => 'In 30 minutes',
        'in-45-min'   => 'In 45 minutes',
        'in-1-hour'   => 'In 1 hour',
        'in-1-5-hour' => 'In 1 hour 30 minutes',
         'in-2-hours' => 'In 2 hours',
    ];

    return $pickup_labels[$pickup_value] ?? $pickup_value;
}?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | Staff</title>
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

    <main class="orders-main-content">
        <header>
            <h2>Orders</h2>
            <p>Manage and track all customer orders</p>
        </header>

        <div class="pending-only">
            <input type="checkbox" id="pending-sort"
                <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'pending') ? 'checked' : ''; ?>
                onchange="window.location.href = this.checked ? 'orders.php?filter=pending' : 'orders.php'">
            <label for="pending-sort">Pending Only</label>
        </div>

        <section class="orders-content">
            <?php if (mysqli_num_rows($orders_q) === 0): ?>
                <p style="text-align:center;color:#aaa;padding:40px;">No orders found.</p>
            <?php else: ?>
                <?php while ($order = mysqli_fetch_assoc($orders_q)):
                    $oid = $order['order_id'];
                    $pickup_display = getPickupDisplay($order['pickup_time'], $order['created_at']);
                    $items_q = mysqli_query($conn,
                        "SELECT p.product_name, ps.size_name, oi.quantity, oi.unit_price, oi.addons
                         FROM order_items oi
                         LEFT JOIN products p ON oi.product_id = p.product_id
                         LEFT JOIN product_sizes ps ON oi.size_id = ps.size_id
                         WHERE oi.order_id = '$oid'"
                    );
                    $items = [];
                    while ($item = mysqli_fetch_assoc($items_q)) {
                        $items[] = $item;
                    }

                     $status_bg = $order['order_status'] === 'completed' ? 'rgba(180,180,180,0.35)' :
                                ($order['order_status'] === 'ready_for_pickup' ? 'rgba(136,214,108,0.5)' :
                                ($order['order_status'] === 'cancelled'  ? 'rgba(255,100,100,0.3)' :
                                ($order['order_status'] === 'preparing'  ? 'rgba(100,150,255,0.3)' : 'rgba(255,220,100,0.5)')));
                ?>

                <div class="order-card" data-order-id="<?php echo $oid; ?>">
                    <div class="order-card-top">

                        <!-- LEFT: Order ID, date, pickup -->
                        <div class="card-left">
                            <h4>ORD-<?php echo str_pad($oid, 3, '0', STR_PAD_LEFT); ?></h4>
                            <div class="meta-row">
                                <i class="fa fa-clock"></i>
                                <?php echo date('M j, Y · g:i A', strtotime($order['created_at'])); ?>
                            </div>
                            <span class="pickup-badge">⏱ <?php echo htmlspecialchars($pickup_display); ?></span>
                        </div>

                        <!-- RIGHT: Status dropdown -->
                        <div class="card-right">
                            <div class="card-status">
                                <form method="POST" onclick="event.stopPropagation()">
                                    <input type="hidden" name="order_id" value="<?php echo $oid; ?>">
                                    <select name="status" onchange="handleOrderStatusChange(this, <?php echo $oid; ?>)"
                                        <?php if (in_array($order['order_status'], ['completed', 'cancelled'])): echo 'disabled'; endif; ?>
                                        style="background:<?php echo $status_bg; ?>">                                        
                                        <option value="pending"   <?php echo $order['order_status'] === 'pending'   ? 'selected' : ''; ?>>Pending</option>
                                        <option value="preparing" <?php echo $order['order_status'] === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                           <option value="ready_for_pickup" <?php echo $order['order_status'] === 'ready_for_pickup' ? 'selected' : ''; ?>>Ready for Pickup</option>
                                        <option value="completed" <?php echo $order['order_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </div>
                        </div>

                    </div>

                    <!-- ITEMS DROPDOWN -->
                    <div class="order-items-dropdown" id="items-<?php echo $oid; ?>">
                        <?php if (empty($items)): ?>
                            <p style="font-size:13px;color:#aaa;">No items found.</p>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <div class="order-item-row">
                                    <div>
                                        <span class="order-item-name">
                                            <?php echo htmlspecialchars($item['product_name'] ?? 'Unknown'); ?>
                                            (<?php echo htmlspecialchars($item['size_name'] ?? 'N/A'); ?>)
                                        </span>
                                        <?php if (!empty($item['addons'])): ?>
                                            <span style="color:#aaa;font-size:12px;"> · <?php echo htmlspecialchars($item['addons']); ?></span>
                                        <?php endif; ?>
                                        <span style="color:#aaa;"> x<?php echo $item['quantity']; ?></span>
                                    </div>
                                    <span class="order-item-price">P <?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if (!empty($order['notes'])): ?>
                            <p class="order-notes">📝 Note: <?php echo htmlspecialchars($order['notes']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php endwhile; ?>
            <?php endif; ?>
        </section>
    </main>

        <!-- CANCEL REASON MODAL -->
        <div id="cancelModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5);
            z-index:9999; align-items:center; justify-content:center;">
            <div style="background:#1e1e1e; border-radius:16px; padding:28px 24px; width:90%; max-width:380px;
                        font-family:Poppins; box-shadow:0 8px 32px rgba(0,0,0,0.4);">
                <h3 style="margin:0 0 6px; font-size:16px; color:#fff;">Cancel Order</h3>
                <p style="margin:0 0 18px; font-size:13px; color:#aaa;">Select a reason for cancellation:</p>

                <form method="POST" id="cancelForm">
                    <input type="hidden" name="order_id" id="cancelOrderId">
                    <input type="hidden" name="status" value="cancelled">
                    <input type="hidden" name="update_status" value="1">

                    <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:20px;">
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;
                                    background:#2a2a2a; border-radius:10px; padding:12px 14px;">
                            <input type="radio" name="cancel_reason_staff" value="no_show" required>
                            <span style="font-size:13px; color:#fff;">🕐 No-show — Customer did not pick up</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;
                                    background:#2a2a2a; border-radius:10px; padding:12px 14px;">
                            <input type="radio" name="cancel_reason_staff" value="out_of_stock">
                            <span style="font-size:13px; color:#fff;">📦 Out of Stock</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;
                                    background:#2a2a2a; border-radius:10px; padding:12px 14px;">
                            <input type="radio" name="cancel_reason_staff" value="other">
                            <span style="font-size:13px; color:#fff;">✏️ Other</span>
                        </label>
                    </div>

                    <div style="display:flex; gap:10px;">
                        <button type="button" onclick="closeCancelModal()"
                            style="flex:1; padding:10px; border:1px solid #444; background:transparent;
                                color:#aaa; border-radius:10px; cursor:pointer; font-family:Poppins; font-size:13px;">
                            Go Back
                        </button>
                        <button type="submit"
                            style="flex:1; padding:10px; border:none; background:#e74c3c;
                                color:#fff; border-radius:10px; cursor:pointer; font-family:Poppins; font-size:13px; font-weight:600;">
                            Confirm Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

    <nav class="bottom-nav">
        <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fa fa-dashboard nav-icon"></i><span>Dashboard</span>
        </a>
        <a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' || basename($_SERVER['PHP_SELF']) === 'order-details.php' ? 'active' : ''; ?>">
            <i class="fa fa-shopping-cart nav-icon"></i><span>Orders</span>
        </a>
        <a href="logout.php">
            <i class="fa fa-sign-out nav-icon"></i><span>Logout</span>
        </a>
    </nav>

    <div class="new-order-toast" id="orderToast" onclick="goToOrders()">
        🛎️ New Order Alert!
    </div>

<script src="notif.js"></script>
<script>
    document.querySelectorAll('.order-card').forEach(card => {
    card.addEventListener('click', function () {
        dismissToast();
        window.location.href = 'order-details.php?order_id=' + this.dataset.orderId;
    });
});

function handleOrderStatusChange(select, orderId) {
    dismissToast();
    if (select.value === 'cancelled') {
        event.stopPropagation();
        document.getElementById('cancelOrderId').value = orderId;
        document.getElementById('cancelModal').style.display = 'flex';
    } else {
        select.form.submit();
    }
}

function closeCancelModal() {
    document.getElementById('cancelModal').style.display = 'none';
    // I-reset ang select sa previous value
    document.querySelectorAll('.order-card select').forEach(s => {
        const card = s.closest('.order-card');
        // Hindi natin alam ang prev value, i-reload na lang
    });
}

// Close kapag nag-click sa overlay
document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) closeCancelModal();
});

    let knownOrderIds = new Set(
        [...document.querySelectorAll('.order-card')].map(c => c.dataset.orderId)
    );

    async function refreshOrders() {
        const activeSelect = document.querySelector('select:focus');
        if (activeSelect) return;

        const filter = new URLSearchParams(window.location.search).get('filter') || '';
        const res = await fetch('fetch-orders.php' + (filter ? '?filter=' + filter : ''));
        const orders = await res.json();

        orders.forEach(order => {
            const card = document.querySelector(`.order-card[data-order-id="${order.order_id}"]`);
            if (!card) return;

            const select = card.querySelector('select');
            if (select && document.activeElement !== select) {
                select.value = order.order_status;

                const colors = {
                    completed:        'rgba(180,180,180,0.35)',
                    ready_for_pickup: 'rgba(136,214,108,0.5)',
                    cancelled:        'rgba(255,100,100,0.3)',
                    preparing:        'rgba(100,150,255,0.3)',
                    pending:          'rgba(255,220,100,0.5)'
                };
                select.style.background = colors[order.order_status] || colors.pending;
                select.disabled = ['completed', 'cancelled'].includes(order.order_status);
            }
        });
    }

    setInterval(refreshOrders, 5000);
</script>
</body>
</html>