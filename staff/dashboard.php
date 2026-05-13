<?php
session_start();
if (!isset($_SESSION['staff_logged_in'])) {
    header("Location: login.php");
    exit;
}
include "../db.php";

// SUMMARY COUNTS
$today = date('Y-m-d');

$total_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at) = '$today'");
$total = mysqli_fetch_assoc($total_q)['cnt'];

$pending_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders WHERE order_status = 'pending' AND DATE(created_at) = '$today'");
$pending = mysqli_fetch_assoc($pending_q)['cnt'];

$completed_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders WHERE order_status = 'completed' AND DATE(created_at) = '$today'");
$completed = mysqli_fetch_assoc($completed_q)['cnt'];

$cancelled_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders WHERE order_status = 'cancelled' AND DATE(created_at) = '$today'");
$cancelled = mysqli_fetch_assoc($cancelled_q)['cnt'];

// TOTAL SALES TODAY
$sales_q = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE order_status = 'completed' AND DATE(created_at) = '$today'");
$sales = mysqli_fetch_assoc($sales_q)['total'] ?? 0;

// RECENT ORDERS
$recent_q = mysqli_query($conn,
    "SELECT o.order_id, o.order_status, o.total_amount, o.created_at, u.full_name
     FROM orders o
     JOIN users u ON o.user_id = u.user_id
     ORDER BY o.created_at DESC
     LIMIT 8"
);

// UNAVAILABLE PRODUCTS
$unavail_q = mysqli_query($conn,
    "SELECT product_name FROM products WHERE is_available = 0"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Staff</title>
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
            <div class="dash-tab active">
                <a href="dashboard.php"><h3><i class="fa fa-dashboard"></i> Dashboard</h3></a>
            </div>
            <div class="orders-tab">
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
                <p>staff@bigbrew.com</p>
            </div>
        </div>
    </aside>

    <main class="dash-main-content">
        <header>
            <h2>The Daily Brew Summary</h2>
            <p>Keep it brewing! Here is what's happening right now.</p>
        </header>

        <div class="dash-summary">
            <div class="total-orders">
                <i class="fa fa-list"></i>
                <h1><?php echo $total; ?></h1>
                <h5>Total Orders Today</h5>
            </div>
            <div class="pending-orders">
                <i class="fa fa-clock"></i>
                <h1><?php echo $pending; ?></h1>
                <h5>Pending Orders</h5>
            </div>
            <div class="completed-orders">
                <i class="fa fa-check-circle"></i>
                <h1><?php echo $completed; ?></h1>
                <h5>Completed Orders</h5>
            </div>
            <div class="cancelled-orders">
                <i class="fa fa-times-circle"></i>
                <h1><?php echo $cancelled; ?></h1>
                <h5>Cancelled Orders</h5>
            </div>
        </div>

        <div class="dash-content">
            <div class="container1">
                <div class="order-prev">
                    <h4>Recent Orders</h4>
                    <br>
                    <?php if (mysqli_num_rows($recent_q) === 0): ?>
                        <p style="color:#aaa;font-size:14px;">No orders today.</p>
                    <?php else: ?>
                        <?php while ($row = mysqli_fetch_assoc($recent_q)): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:14px;">
                            <div>
                                <strong>#<?php echo $row['order_id']; ?></strong>
                                <span style="color:#aaa;margin-left:8px;"><?php echo htmlspecialchars($row['full_name']); ?></span>
                            </div>
                            <div style="display:flex;gap:12px;align-items:center;">
                                <span style="color:var(--pop-color);font-weight:600;">P <?php echo number_format($row['total_amount'], 2); ?></span>
                                <span style="
                                    padding:3px 10px;
                                    border-radius:50px;
                                    font-size:12px;
                                    font-weight:600;
                                    background:<?php echo $row['order_status'] === 'completed' ? '#DCFCE7' : ($row['order_status'] === 'cancelled' ? '#FEE2E2' : '#FEF9C2'); ?>;
                                    color:<?php echo $row['order_status'] === 'completed' ? '#166534' : ($row['order_status'] === 'cancelled' ? '#991B1B' : '#A84B00'); ?>;
                                ">
                                    <?php echo ucfirst($row['order_status']); ?>
                                </span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="container2">
                <div class="total-sales-today">
                    <h3>Total Sales Today:</h3>
                    <h2>P <?php echo number_format($sales, 2); ?></h2>
                    <i class="fas fa-money-bill-wave"></i>
                </div>

                <div class="menu-status-prev">
                    <h4>Unavailable Products</h4>
                    <div class="unavailable-table">
                        <table class="status-table">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th class="text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($unavail_q) === 0): ?>
                                    <tr><td colspan="2" style="text-align:center;color:#aaa;padding:20px;">All products available!</td></tr>
                                <?php else: ?>
                                    <?php while ($row = mysqli_fetch_assoc($unavail_q)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                        <td class="text-right" style="color:#991B1B;font-weight:600;">Not Available</td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
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
</nav>git
    <div class="new-order-toast" id="orderToast" onclick="goToOrders()">
    🛎️ New Order Alert!
</div>
<script src="notif.js"></script>
</body>
</html>