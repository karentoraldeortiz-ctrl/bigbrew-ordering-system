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

$items    = [];
$subtotal = 0;
while ($row = mysqli_fetch_assoc($items_q)) {
    $items[]   = $row;
    $subtotal += $row['unit_price'] * $row['quantity'];
}

date_default_timezone_set('Asia/Manila');

$pickup_value    = trim($order['pickup_time']);
$created_at_time = !empty($order['created_at']) ? strtotime($order['created_at']) : time();

if ($pickup_value === 'asap') {
    $start_time     = date('g:i A', strtotime('+15 minutes', $created_at_time));
    $end_time       = date('g:i A', strtotime('+30 minutes', $created_at_time));
    $pickup_display = "ASAP ({$start_time} - {$end_time})";
} else {
    $pickup_labels  = [
        'in-15-min'   => 'In 15 minutes',
        'in-30-min'   => 'In 30 minutes',
        'in-45-min'   => 'In 45 minutes',
        'in-1-hour'   => 'In 1 hour',
        'in-1-5-hour' => 'In 1 hour 30 minutes',
        'in-2-hours'  => 'In 2 hours',
    ];
    $pickup_display = $pickup_labels[$pickup_value] ?? $pickup_value;
}

$order_date = date('m/d/Y, · g:i A', strtotime($order['created_at']));

// ── HANDLE GCASH RECEIPT VERIFICATION ────────────────────────────────────────
if (isset($_POST['verify_receipt'])) {
    $action      = mysqli_real_escape_string($conn, $_POST['receipt_action']);
    $uid         = $order['user_id'];
    $order_code  = str_pad($order_id, 3, '0', STR_PAD_LEFT);

    if ($action === 'verified') {
        mysqli_query($conn,
            "UPDATE orders
             SET gcash_receipt_status = 'verified',
                 order_status = 'preparing',
                 gcash_rejection_reason = NULL
             WHERE order_id = '$order_id'"
        );
        $notif_title = "GCash Payment Verified ✅";
        $notif_msg   = "Your GCash downpayment for Order #$order_code has been verified! Your order is now being prepared.";
        mysqli_query($conn, "INSERT INTO notifications (user_id, order_id, title, message) VALUES ('$uid', '$order_id', '$notif_title', '$notif_msg')");

    } elseif ($action === 'rejected') {
        $reject_reason = mysqli_real_escape_string($conn, $_POST['reject_reason'] ?? '');
        if (empty($reject_reason)) {
            header("Location: order-details.php?order_id=$order_id&reject_err=1");
            exit;
        }
        mysqli_query($conn,
            "UPDATE orders
             SET gcash_receipt_status = 'rejected',
                 gcash_rejection_reason = '$reject_reason'
             WHERE order_id = '$order_id'"
        );
        $notif_title = "GCash Payment Rejected ❌";
        $notif_msg   = "Your GCash downpayment for Order #$order_code was rejected. Reason: $reject_reason. Please re-upload your receipt.";
        mysqli_query($conn, "INSERT INTO notifications (user_id, order_id, title, message) VALUES ('$uid', '$order_id', '$notif_title', '$notif_msg')");
    }

    header("Location: order-details.php?order_id=$order_id");
    exit;
}

// ── HANDLE ORDER STATUS UPDATE ────────────────────────────────────────────────
if (isset($_POST['update_status'])) {
    if (in_array($order['order_status'], ['completed', 'cancelled'])) {
        header("Location: order-details.php?order_id=$order_id");
        exit;
    }

    if ($order['gcash_receipt_status'] === 'pending_verification') {
        header("Location: order-details.php?order_id=$order_id&receipt_warn=1");
        exit;
    }

    if ($order['gcash_receipt_status'] === 'rejected') {
        header("Location: order-details.php?order_id=$order_id&receipt_warn=2");
        exit;
    }

    $status        = mysqli_real_escape_string($conn, $_POST['status']);
    $cancel_reason = isset($_POST['cancel_reason'])
        ? mysqli_real_escape_string($conn, $_POST['cancel_reason'])
        : null;

    if ($status === 'completed') {
        mysqli_query($conn,
            "UPDATE orders SET order_status = 'completed', completed_at = NOW(),
             cancelled_by = NULL, cancel_reason = NULL WHERE order_id = '$order_id'"
        );
    } elseif ($status === 'cancelled') {
        $reason = $cancel_reason ?? 'other';
        mysqli_query($conn,
            "UPDATE orders SET order_status = 'cancelled', cancelled_by = 'staff',
             cancel_reason = '$reason', completed_at = NULL WHERE order_id = '$order_id'"
        );

        if ($reason === 'no_show') {
            $uid    = $order['user_id'];
            $user_q = mysqli_query($conn, "SELECT no_show_count FROM users WHERE user_id = '$uid'");
            $user   = mysqli_fetch_assoc($user_q);
            $new_count = $user['no_show_count'] + 1;

            if ($new_count === 1) {
                mysqli_query($conn, "UPDATE users SET no_show_count = $new_count WHERE user_id = '$uid'");
            } elseif ($new_count === 2) {
                mysqli_query($conn, "UPDATE users SET no_show_count = $new_count, ban_status = 'temp_banned', ban_until = DATE_ADD(NOW(), INTERVAL 7 DAY) WHERE user_id = '$uid'");
            } else {
                mysqli_query($conn, "UPDATE users SET no_show_count = $new_count, ban_status = 'banned', ban_until = NULL WHERE user_id = '$uid'");
            }
        }
    } else {
        mysqli_query($conn,
            "UPDATE orders SET order_status = '$status', completed_at = NULL,
             cancelled_by = NULL, cancel_reason = NULL WHERE order_id = '$order_id'"
        );
    }

    header("Location: order-details.php?order_id=$order_id");
    exit;
}

// Re-fetch updated order after any POST
$order_q = mysqli_query($conn,
    "SELECT o.*, u.full_name, u.email, u.phone_num
     FROM orders o JOIN users u ON o.user_id = u.user_id
     WHERE o.order_id = '$order_id'"
);
$order = mysqli_fetch_assoc($order_q);

$receipt_status  = $order['gcash_receipt_status'] ?? 'not_required';
$receipt_file    = $order['gcash_receipt'] ?? null;
$has_receipt     = !empty($receipt_file);
$downpayment     = $order['gcash_downpayment'] ?? null;
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

    <style>
        .receipt-card { background: white; border-radius: 16px; padding: 20px; margin-bottom: 16px; }

        .receipt-status-banner {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px; border-radius: 10px; margin-bottom: 16px;
            font-size: 13px; font-weight: 600;
        }
        .receipt-status-banner.pending  { background: rgba(255,193,7,0.15);  color: #ffc107; border: 1px solid rgba(255,193,7,0.3); }
        .receipt-status-banner.verified { background: rgba(40,167,69,0.15);  color: #28a745; border: 1px solid rgba(40,167,69,0.3); }
        .receipt-status-banner.rejected { background: rgba(220,53,69,0.15);  color: #dc3545; border: 1px solid rgba(220,53,69,0.3); }

        .receipt-img-wrap {
            position: relative; border-radius: 12px; overflow: hidden;
            margin-bottom: 14px; background: #111; cursor: pointer;
        }
        .receipt-img-wrap img { width: 100%; max-height: 280px; object-fit: contain; display: block; transition: transform 0.2s; }
        .receipt-img-wrap:hover img { transform: scale(1.02); }
        .receipt-zoom-hint {
            position: absolute; bottom: 8px; right: 10px;
            background: rgba(0,0,0,0.6); color: #fff; font-size: 11px;
            padding: 4px 8px; border-radius: 6px;
        }

        .receipt-info-row {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 14px; font-size: 13px; color: #aaa;
        }
        .receipt-info-row strong { color: #fff; font-size: 15px; }

        .receipt-action-row { display: flex; gap: 10px; }
        .receipt-btn {
            flex: 1; padding: 11px; border-radius: 10px; border: none;
            font-family: 'Poppins', sans-serif; font-size: 13px; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
        }
        .receipt-btn-reject  { background: rgba(220,53,69,0.15); color: #dc3545; border: 1px solid rgba(220,53,69,0.3); }
        .receipt-btn-reject:hover  { background: rgba(220,53,69,0.3); }
        .receipt-btn-verify  { background: #28a745; color: #fff; }
        .receipt-btn-verify:hover  { background: #218838; }

        .od-warn-banner {
            background: rgba(255,193,7,0.12); border: 1px solid rgba(255,193,7,0.35);
            border-radius: 10px; padding: 12px 16px; font-size: 13px; color: #ffc107;
            margin-bottom: 14px; display: flex; gap: 8px; align-items: flex-start;
        }

        .receipt-lightbox {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.9);
            z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(4px);
        }
        .receipt-lightbox.active { display: flex; }
        .receipt-lightbox img { max-width: 92vw; max-height: 90vh; object-fit: contain; border-radius: 12px; }
        .receipt-lightbox-close {
            position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.15);
            border: none; color: #fff; font-size: 22px; width: 40px; height: 40px;
            border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;
        }

        /* ── Reject Reason Modal ── */
        .reject-modal-overlay {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6);
            z-index: 99999; align-items: center; justify-content: center;
        }
        .reject-modal-overlay.active { display: flex; }
        .reject-modal {
            background: #1e1e1e; border-radius: 16px; padding: 28px 24px;
            width: 90%; max-width: 400px; font-family: Poppins;
            box-shadow: 0 8px 32px rgba(0,0,0,0.5);
        }
        .reject-modal h3 { margin: 0 0 6px; font-size: 16px; color: #fff; }
        .reject-modal p  { margin: 0 0 16px; font-size: 13px; color: #aaa; }

        .reject-reason-options { display: flex; flex-direction: column; gap: 8px; margin-bottom: 14px; }
        .reject-reason-option {
            display: flex; align-items: center; gap: 10px; cursor: pointer;
            background: #2a2a2a; border-radius: 10px; padding: 12px 14px;
        }
        .reject-reason-option span { font-size: 13px; color: #fff; }

        .reject-custom-input {
            width: 100%; padding: 10px 12px; background: #2a2a2a; border: 1px solid #444;
            border-radius: 10px; color: #fff; font-family: Poppins; font-size: 13px;
            margin-bottom: 16px; resize: none; display: none;
        }
        .reject-custom-input:focus { outline: none; border-color: #dc3545; }

        .reject-modal-actions { display: flex; gap: 10px; }
        .reject-modal-actions button {
            flex: 1; padding: 11px; border-radius: 10px; font-family: Poppins;
            font-size: 13px; font-weight: 600; cursor: pointer; border: none;
        }
        .btn-reject-cancel { background: transparent; border: 1px solid #444 !important; color: #aaa; }
        .btn-reject-confirm { background: #dc3545; color: #fff; }
        .btn-reject-confirm:hover { background: #c82333; }

        .reject-err { color: #dc3545; font-size: 12px; margin-bottom: 10px; display: none; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo"><img src="../assets/logo/bbmaysan.png" alt=""></div>
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

            <?php if (isset($_GET['receipt_warn'])): ?>
            <div class="od-warn-banner">
                <span>⚠️</span>
                <span>
                    <?php if ($_GET['receipt_warn'] == 1): ?>
                        Cannot update order status — GCash receipt is still <strong>pending verification</strong>. Please verify the receipt first.
                    <?php elseif ($_GET['receipt_warn'] == 2): ?>
                        Cannot update order status — GCash receipt was <strong>rejected</strong>. The customer needs to re-upload their receipt.
                    <?php endif; ?>
                </span>
            </div>
            <?php endif; ?>

            <!-- ORDER HEADER CARD -->
            <div class="od-card">
                <div class="od-header-row">
                    <div>
                        <h2>ORDER ID: ORD-<?php echo str_pad($order_id, 3, '0', STR_PAD_LEFT); ?></h2>
                        <p class="od-meta">Date of Order: <?php echo $order_date; ?></p>
                        <p class="od-meta"><i class="fa fa-clock" style="color:var(--pop-color);margin-right:6px;"></i>Pickup: <?php echo htmlspecialchars($pickup_display); ?></p>
                        <?php if ($order['order_status'] === 'completed' && !empty($order['completed_at'])): ?>
                        <p class="od-meta" style="color:#4caf50; margin-top:4px;">
                            <i class="fa fa-check-circle" style="margin-right:6px;"></i>Picked up: <?php echo date('m/d/Y, · g:i A', strtotime($order['completed_at'])); ?>
                        </p>
                        <?php endif; ?>
                    </div>

                    <form method="POST" id="odStatusForm">
                        <?php
                            $status_disabled = in_array($order['order_status'], ['completed', 'cancelled'])
                                || $receipt_status === 'pending_verification'
                                || $receipt_status === 'rejected';
                        ?>
                        <select name="status" id="statusSelect"
                            <?php if ($status_disabled): echo 'disabled'; endif; ?>
                            onchange="handleOdStatusChange(this)"
                            class="od-status-select"
                            style="background:<?php
                                echo $order['order_status'] === 'completed'        ? 'rgba(180,180,180,0.35)' :
                                    ($order['order_status'] === 'ready_for_pickup' ? 'rgba(136,214,108,0.5)'  :
                                    ($order['order_status'] === 'cancelled'        ? 'rgba(255,100,100,0.3)'  :
                                    ($order['order_status'] === 'preparing'        ? 'rgba(100,150,255,0.3)'  : 'rgba(255,220,100,0.5)')));
                            ?>;
                            <?php if ($receipt_status === 'pending_verification' || $receipt_status === 'rejected'): ?>
                                opacity: 0.5; cursor: not-allowed;
                            <?php endif; ?>">
                            <option value="pending"          <?php echo $order['order_status'] === 'pending'          ? 'selected' : ''; ?>>Pending</option>
                            <option value="preparing"        <?php echo $order['order_status'] === 'preparing'        ? 'selected' : ''; ?>>Preparing</option>
                            <option value="ready_for_pickup" <?php echo $order['order_status'] === 'ready_for_pickup' ? 'selected' : ''; ?>>Ready for Pickup</option>
                            <option value="completed"        <?php echo $order['order_status'] === 'completed'        ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled"        <?php echo $order['order_status'] === 'cancelled'        ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <input type="hidden" name="status" id="odHiddenStatus">
                        <input type="hidden" name="cancel_reason" id="odHiddenReason">
                        <input type="hidden" name="update_status" value="1">
                    </form>
                </div>

                <?php if ($receipt_status === 'pending_verification'): ?>
                    <p style="font-size:11px; color:#ffc107; margin-top:6px; text-align:right;">
                        ⚠️ Verify receipt before updating status
                    </p>
                <?php elseif ($receipt_status === 'rejected'): ?>
                    <p style="font-size:11px; color:#dc3545; margin-top:6px; text-align:right;">
                        ❌ Receipt rejected — waiting for customer to re-upload
                    </p>
                <?php endif; ?>

                <?php if (!empty($order['notes'])): ?>
                <div class="od-notes">
                    <i class="fa fa-comment" style="margin-right:8px;"></i>
                    Note to Barista: <em><?php echo htmlspecialchars($order['notes']); ?></em>
                </div>
                <?php endif; ?>

                <hr class="od-divider">

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

            <!-- GCASH RECEIPT CARD -->
            <?php if ($receipt_status !== 'not_required'): ?>
            <div class="od-card receipt-card">
                <h4 class="od-section-title" style="margin-bottom:14px;">
                    💙 GCash Downpayment Receipt
                </h4>

                <?php
                    $banner_class = $receipt_status === 'verified' ? 'verified' :
                                   ($receipt_status === 'rejected' ? 'rejected' : 'pending');
                    $banner_icon  = $receipt_status === 'verified' ? '✅' :
                                   ($receipt_status === 'rejected' ? '❌' : '⏳');
                    $banner_text  = $receipt_status === 'verified' ? 'Receipt Verified' :
                                   ($receipt_status === 'rejected' ? 'Receipt Rejected — Waiting for re-upload' : 'Pending Verification');
                ?>
                <div class="receipt-status-banner <?php echo $banner_class; ?>">
                    <span><?php echo $banner_icon; ?></span>
                    <span><?php echo $banner_text; ?></span>
                </div>

                <!-- Show rejection reason if rejected -->
                <?php if ($receipt_status === 'rejected' && !empty($order['gcash_rejection_reason'])): ?>
                <div style="background:rgba(220,53,69,0.1); border:1px solid rgba(220,53,69,0.3); border-radius:10px; padding:10px 14px; margin-bottom:14px; font-size:13px; color:#dc3545;">
                    <strong>Rejection Reason:</strong> <?php echo htmlspecialchars($order['gcash_rejection_reason']); ?>
                </div>
                <?php endif; ?>

                <?php if ($downpayment): ?>
                <div class="receipt-info-row">
                    <span>Required Downpayment (50%)</span>
                    <strong>₱<?php echo number_format($downpayment, 2); ?></strong>
                </div>
                <?php endif; ?>

                <?php if ($has_receipt): ?>
                <div class="receipt-img-wrap" onclick="openReceiptLightbox()">
                    <img src="../uploads/receipts/<?php echo htmlspecialchars($receipt_file); ?>" alt="GCash Receipt">
                    <div class="receipt-zoom-hint">🔍 Tap to zoom</div>
                </div>
                <?php else: ?>
                <div style="text-align:center; padding:24px; color:#555; font-size:13px;">
                    📭 No receipt uploaded yet
                </div>
                <?php endif; ?>

                <?php if ($receipt_status === 'pending_verification' && $has_receipt): ?>
                <form method="POST" id="receiptVerifyForm">
                    <input type="hidden" name="verify_receipt" value="1">
                    <input type="hidden" name="receipt_action" id="receiptActionInput" value="">
                    <input type="hidden" name="reject_reason" id="rejectReasonInput" value="">
                    <div class="receipt-action-row">
                        <button type="button" class="receipt-btn receipt-btn-reject"
                            onclick="openRejectModal()">
                            ✕ Reject
                        </button>
                        <button type="button" class="receipt-btn receipt-btn-verify"
                            onclick="submitReceiptAction('verified')">
                            ✓ Verify Receipt
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>

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
                <?php if ($downpayment): ?>
                <div class="od-summary-row">
                    <span>GCash Downpayment (50%):</span>
                    <span style="color:#28a745;">₱<?php echo number_format($downpayment, 2); ?></span>
                </div>
                <div class="od-summary-row">
                    <span>Remaining upon Pickup:</span>
                    <span>₱<?php echo number_format($order['total_amount'] - $downpayment, 2); ?></span>
                </div>
                <?php endif; ?>
                <div class="od-summary-row od-total">
                    <span>Total:</span>
                    <span>P<?php echo number_format($order['total_amount'], 0); ?></span>
                </div>
            </div>
        </div>

        <!-- CANCEL REASON MODAL -->
        <div id="odCancelModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5);
             z-index:9999; align-items:center; justify-content:center;">
            <div style="background:#1e1e1e; border-radius:16px; padding:28px 24px; width:90%; max-width:380px;
                        font-family:Poppins; box-shadow:0 8px 32px rgba(0,0,0,0.4);">
                <h3 style="margin:0 0 6px; font-size:16px; color:#fff;">Cancel Order</h3>
                <p style="margin:0 0 18px; font-size:13px; color:#aaa;">Select a reason for cancellation:</p>
                <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:20px;">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer; background:#2a2a2a; border-radius:10px; padding:12px 14px;">
                        <input type="radio" name="od_reason" value="no_show">
                        <span style="font-size:13px; color:#fff;">🕐 No-show — Customer did not pick up</span>
                    </label>
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer; background:#2a2a2a; border-radius:10px; padding:12px 14px;">
                        <input type="radio" name="od_reason" value="out_of_stock">
                        <span style="font-size:13px; color:#fff;">📦 Out of Stock</span>
                    </label>
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer; background:#2a2a2a; border-radius:10px; padding:12px 14px;">
                        <input type="radio" name="od_reason" value="other">
                        <span style="font-size:13px; color:#fff;">✏️ Other</span>
                    </label>
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="button" onclick="closeOdCancelModal()"
                        style="flex:1; padding:10px; border:1px solid #444; background:transparent; color:#aaa; border-radius:10px; cursor:pointer; font-family:Poppins; font-size:13px;">
                        Go Back
                    </button>
                    <button type="button" onclick="submitOdCancel()"
                        style="flex:1; padding:10px; border:none; background:#e74c3c; color:#fff; border-radius:10px; cursor:pointer; font-family:Poppins; font-size:13px; font-weight:600;">
                        Confirm Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- REJECT REASON MODAL -->
        <div class="reject-modal-overlay" id="rejectModal">
            <div class="reject-modal">
                <h3>Reject Receipt</h3>
                <p>Select or type a reason for rejection:</p>

                <div class="reject-reason-options">
                    <label class="reject-reason-option">
                        <input type="radio" name="reject_reason_radio" value="Amount paid does not match the required downpayment.">
                        <span>💰 Wrong amount paid</span>
                    </label>
                    <label class="reject-reason-option">
                        <input type="radio" name="reject_reason_radio" value="Receipt is blurry or unreadable.">
                        <span>📷 Blurry or unreadable receipt</span>
                    </label>
                    <label class="reject-reason-option">
                        <input type="radio" name="reject_reason_radio" value="Receipt appears to be invalid or edited.">
                        <span>🚫 Invalid or edited receipt</span>
                    </label>
                    <label class="reject-reason-option">
                        <input type="radio" name="reject_reason_radio" value="other">
                        <span>✏️ Other (type below)</span>
                    </label>
                </div>

                <textarea class="reject-custom-input" id="rejectCustomText"
                    placeholder="Type your reason here..." rows="3"></textarea>

                <p class="reject-err" id="rejectErr">⚠️ Please select or enter a reason.</p>

                <div class="reject-modal-actions">
                    <button class="btn-reject-cancel" onclick="closeRejectModal()">Go Back</button>
                    <button class="btn-reject-confirm" onclick="confirmReject()">Confirm Reject</button>
                </div>
            </div>
        </div>
    </main>

    <!-- Receipt Lightbox -->
    <div class="receipt-lightbox" id="receiptLightbox" onclick="closeReceiptLightbox()">
        <button class="receipt-lightbox-close" onclick="closeReceiptLightbox()">✕</button>
        <?php if ($has_receipt): ?>
        <img src="../uploads/receipts/<?php echo htmlspecialchars($receipt_file); ?>" alt="GCash Receipt">
        <?php endif; ?>
    </div>

    <nav class="bottom-nav">
        <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fa fa-dashboard"></i><span>Dashboard</span>
        </a>
        <a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' || basename($_SERVER['PHP_SELF']) === 'order-details.php' ? 'active' : ''; ?>">
            <i class="fa fa-shopping-cart"></i><span>Orders</span>
        </a>
        <a href="logout.php">
            <i class="fa fa-sign-out"></i><span>Logout</span>
        </a>
    </nav>

<script>
// ── Order status change ───────────────────────────────────────────────────────
function handleOdStatusChange(select) {
    if (select.value === 'cancelled') {
        document.getElementById('odCancelModal').style.display = 'flex';
        select.value = '<?php echo $order['order_status']; ?>';
    } else {
        document.getElementById('odHiddenStatus').value = select.value;
        document.getElementById('odStatusForm').submit();
    }
}

function closeOdCancelModal() {
    document.getElementById('odCancelModal').style.display = 'none';
    document.querySelectorAll('input[name="od_reason"]').forEach(r => r.checked = false);
}

function submitOdCancel() {
    const selected = document.querySelector('input[name="od_reason"]:checked');
    if (!selected) { alert('Please select a reason.'); return; }
    document.getElementById('odHiddenStatus').value = 'cancelled';
    document.getElementById('odHiddenReason').value = selected.value;
    document.getElementById('odStatusForm').submit();
}

document.getElementById('odCancelModal').addEventListener('click', function(e) {
    if (e.target === this) closeOdCancelModal();
});

// ── Receipt verification ──────────────────────────────────────────────────────
function submitReceiptAction(action) {
    if (!confirm('Verify this GCash receipt and allow the order to proceed?')) return;
    document.getElementById('receiptActionInput').value = action;
    document.getElementById('receiptVerifyForm').submit();
}

// ── Reject Modal ──────────────────────────────────────────────────────────────
function openRejectModal() {
    document.getElementById('rejectModal').classList.add('active');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.remove('active');
    document.querySelectorAll('input[name="reject_reason_radio"]').forEach(r => r.checked = false);
    document.getElementById('rejectCustomText').value = '';
    document.getElementById('rejectCustomText').style.display = 'none';
    document.getElementById('rejectErr').style.display = 'none';
}

// Show/hide custom text area
document.querySelectorAll('input[name="reject_reason_radio"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const customInput = document.getElementById('rejectCustomText');
        customInput.style.display = this.value === 'other' ? 'block' : 'none';
    });
});

function confirmReject() {
    const selected = document.querySelector('input[name="reject_reason_radio"]:checked');
    const errEl    = document.getElementById('rejectErr');

    if (!selected) {
        errEl.style.display = 'block';
        return;
    }

    let reason = selected.value;

    if (reason === 'other') {
        reason = document.getElementById('rejectCustomText').value.trim();
        if (!reason) {
            errEl.style.display = 'block';
            errEl.textContent   = '⚠️ Please type a reason.';
            return;
        }
    }

    errEl.style.display = 'none';
    document.getElementById('rejectReasonInput').value  = reason;
    document.getElementById('receiptActionInput').value = 'rejected';
    document.getElementById('receiptVerifyForm').submit();
}

document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
});

// ── Lightbox ──────────────────────────────────────────────────────────────────
function openReceiptLightbox() {
    document.getElementById('receiptLightbox').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeReceiptLightbox() {
    document.getElementById('receiptLightbox').classList.remove('active');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeReceiptLightbox();
        closeRejectModal();
    }
});
</script>
</body>
</html>