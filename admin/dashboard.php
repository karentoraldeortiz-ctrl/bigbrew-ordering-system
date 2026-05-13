<?php
session_start();
// Uncomment below when ready to protect this page:
// if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }
require_once 'db.php';

// ── API Handler ──────────────────────────────────────────────────────────
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    // ── Stats cards ──────────────────────────────────────────────────────
    if ($action === 'stats') {
        // Total revenue (completed/delivered orders only)
        $rev = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders
             WHERE order_status NOT IN ('cancelled', 'pending')"
        ));

        // Total orders
        $ord = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) AS total FROM orders"
        ));

        // Total clients (registered users)
        $cli = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) AS total FROM users"
        ));

        // Active products
        $pro = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) AS total FROM products WHERE is_available = 1"
        ));

        echo json_encode([
            'revenue'  => (float) $rev['total'],
            'orders'   => (int)   $ord['total'],
            'clients'  => (int)   $cli['total'],
            'products' => (int)   $pro['total'],
        ]);
        exit;
    }

    // ── Sales chart — last 7 days ─────────────────────────────────────────
    if ($action === 'chart') {
        $rows = [];
        $res  = mysqli_query($conn,
            "SELECT DATE(created_at) AS day, COALESCE(SUM(total_amount), 0) AS total
             FROM orders
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
               AND order_status NOT IN ('cancelled', 'pending')
             GROUP BY DATE(created_at)
             ORDER BY day ASC"
        );
        $map = [];
        while ($r = mysqli_fetch_assoc($res)) $map[$r['day']] = (float)$r['total'];

        // Fill in missing days with 0
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $rows[] = ['date' => $d, 'total' => $map[$d] ?? 0];
        }
        echo json_encode($rows);
        exit;
    }

    // ── Top selling products ──────────────────────────────────────────────
    if ($action === 'top_products') {
        $products = [];
        $res = mysqli_query($conn,
            "SELECT p.product_name, p.image, p.category,
                    SUM(oi.quantity) AS total_sold,
                    SUM(oi.quantity * oi.unit_price) AS total_revenue
             FROM order_items oi
             JOIN products p ON oi.product_id = p.product_id
             JOIN orders o ON oi.order_id = o.order_id
             WHERE o.order_status NOT IN ('cancelled', 'pending')
             GROUP BY oi.product_id
             ORDER BY total_sold DESC
             LIMIT 5"
        );
        while ($r = mysqli_fetch_assoc($res)) $products[] = $r;
        echo json_encode($products);
        exit;
    }

    echo json_encode(['error' => 'Invalid action']);
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard | BigBrew Admin</title>
  <link rel="shortcut icon" href="../assets/logo/logo-black.png" type="image/png" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <link rel="stylesheet" href="admin.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>

  <!-- ── SIDEBAR ── -->
  <aside class="sidebar">
    <div class="logo">
      <img src="../assets/logo/bbmaysan.png" alt="BigBrew"
        onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
      <div class="logo-fallback">
        <i class="fa fa-coffee"></i><span>BIGBREW</span>
      </div>
    </div>
    <hr />
    <div class="main-menu">
      <h6>MAIN MENU</h6>
      <div class="dash-tab active">
        <a href="dashboard.php"><h3><i class="fa fa-dashboard"></i> Dashboard</h3></a>
      </div>
      <div class="menu-tab">
        <a href="menu.php"><h3><i class="fa fa-utensils"></i> Menu Management</h3></a>
      </div>
    </div>
    <hr />
    <div class="acc">
      <h6>ACCOUNT</h6>
      <div class="settings-tab">
        <a href="settings.php"><h3><i class="fa fa-cog"></i> Settings</h3></a>
      </div>
      <div class="logout-tab">
        <a href="logout.php"><h3><i class="fa fa-sign-out"></i> Logout</h3></a>
      </div>
    </div>
    <div class="staff-acc">
      <i class="fa fa-user"></i>
      <div>
        <h5>Admin User</h5>
        <p>Admin@bigbrew.com</p>
      </div>
    </div>
  </aside>

  <!-- ── MAIN CONTENT ── -->
  <main class="dash-main-content">

    <header class="dash-header">
      <h2>Dashboard Overview</h2>
      <p>Welcome back! Here's what's happening today.</p>
    </header>

    <!-- Stat Cards -->
    <div class="dash-summary">
      <div class="stat-card revenue">
        <i class="fa fa-money-bill-wave"></i>
        <div class="stat-info">
          <h3 id="statRevenue"><i class="fa fa-spinner fa-spin"></i></h3>
          <p>Total Revenue</p>
        </div>
      </div>
      <div class="stat-card orders">
        <i class="fa fa-shopping-cart"></i>
        <div class="stat-info">
          <h3 id="statOrders"><i class="fa fa-spinner fa-spin"></i></h3>
          <p>Total Orders</p>
        </div>
      </div>
      <div class="stat-card clients">
        <i class="fa fa-users"></i>
        <div class="stat-info">
          <h3 id="statClients"><i class="fa fa-spinner fa-spin"></i></h3>
          <p>Total Clients</p>
        </div>
      </div>
      <div class="stat-card products">
        <i class="fa fa-box"></i>
        <div class="stat-info">
          <h3 id="statProducts"><i class="fa fa-spinner fa-spin"></i></h3>
          <p>Products Active</p>
        </div>
      </div>
    </div>

    <!-- Bottom Panels -->
    <div class="dash-panels">

      <!-- Sales Chart -->
      <div class="panel">
        <div class="panel-header">
          <h4>Sales Report</h4>
          <span class="panel-sub">Last 7 days</span>
        </div>
        <div class="panel-body chart-body">
          <canvas id="salesChart"></canvas>
          <div id="chartEmpty" class="panel-empty" style="display:none;">
            <i class="fa fa-chart-bar"></i>
            <p>No sales data yet.</p>
          </div>
        </div>
      </div>

      <!-- Top Selling Products -->
      <div class="panel">
        <div class="panel-header">
          <h4>Top Selling Products</h4>
          <span class="panel-sub">All time</span>
        </div>
        <div class="panel-body" id="topProductsBody">
          <div class="panel-loading">
            <i class="fa fa-spinner fa-spin"></i> Loading...
          </div>
        </div>
      </div>

    </div>
  </main>
   <nav class="bottom-nav">
    <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
        <i class="fa fa-dashboard nav-icon"></i><span>Dashboard</span>
    </a>
    </a>
    <a href="menu.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'menu.php' ? 'active' : ''; ?>">
        <i class="fa fa-bars nav-icon"></i><span>Menu</span>
    </a>
    <a href="logout.php">
        <i class="fa fa-sign-out nav-icon"></i><span>Logout</span>
    </a>
</nav>
  <script src="dashboard.js"></script>
</body>
</html>