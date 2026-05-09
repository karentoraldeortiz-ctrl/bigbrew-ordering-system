<?php
session_start();
if (!isset($_SESSION['staff_logged_in'])) {
    header("Location: login.php");
    exit;
}
include "../db.php";

// TOGGLE AVAILABILITY
if (isset($_POST['toggle'])) {
    $product_id = (int) $_POST['product_id'];
    $current    = (int) $_POST['current'];
    $new_status = $current ? 0 : 1;
    mysqli_query($conn, "UPDATE products SET is_available = '$new_status' WHERE product_id = '$product_id'");
    header("Location: menu.php");
    exit;
}

// FETCH PRODUCTS
$search   = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sort     = isset($_GET['sort']) ? $_GET['sort'] : 'all';
$category = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : 'All';

$where = [];
if ($search) $where[] = "product_name LIKE '%$search%'";
if ($sort === 'available') $where[] = "is_available = 1";
if ($sort === 'not-available') $where[] = "is_available = 0";
if ($category !== 'All') $where[] = "category = '$category'";

$where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$products_q = mysqli_query($conn, "SELECT * FROM products $where_sql ORDER BY product_name ASC");

// FETCH CATEGORIES
$cat_q = mysqli_query($conn, "SELECT DISTINCT category FROM products ORDER BY category ASC");
$categories = ['All'];
while ($row = mysqli_fetch_assoc($cat_q)) {
    if ($row['category']) $categories[] = $row['category'];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Menu | Staff</title>
    <link rel="shortcut icon" href="../assets/logo/logo-black.png" type="image/png" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="staff.css" />
</head>
<body>
    <aside class="sidebar">
        <div class="logo">
            <img src="../assets/logo/bbmaysan.png" alt="" />
        </div>
        <hr />
        <div class="main-menu">
            <h6>MAIN MENU</h6>
            <div class="dash-tab">
                <a href="dashboard.php"><h3><i class="fa fa-dashboard"></i> Dashboard</h3></a>
            </div>
            <div class="orders-tab">
                <a href="orders.php"><h3><i class="fa fa-shopping-cart"></i> Orders</h3></a>
            </div>
            <div class="menu-tab active">
                <a href="menu.php"><h3><i class="fa fa-bars"></i> Menu Availability</h3></a>
            </div>
        </div>
        <hr />
        <div class="acc">
            <h6>ACCOUNT</h6>
            <button class="logout" onclick="window.location.href='logout.php'">
                <h3><i class="fa fa-sign-out"></i> Logout</h3>
            </button>
        </div>
        <hr />
        <div class="staff-acc">
            <i class="fa fa-user"></i>
            <div>
                <h5><?php echo htmlspecialchars($_SESSION['staff_name']); ?></h5>
                <p>admin@bigbrew.com</p>
            </div>
        </div>
    </aside>

    <!-- MOBILE WRAPPER: scrollable, replaces the flex main on mobile -->
    <div class="menu-main-content">
        <header>
            <h2>Menu Availability</h2>
            <p>Update availability of today's products!</p>
        </header>

        <div class="menu-filter">
            <form method="GET" style="display:flex;gap:16px;align-items:center;width:100%;">
                <div class="menu-search">
                    <div>
                        <span><i class="fa fa-search"></i></span>
                        <input type="text" name="search" id="searchInput" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search product..." oninput="liveSearch()" />
                    </div>
                </div>
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                <div class="sort-menu">
                    <label>Sort:</label>
                    <select name="sort" onchange="this.form.submit()">
                        <option value="all"           <?php echo $sort === 'all'           ? 'selected' : ''; ?>>All</option>
                        <option value="available"     <?php echo $sort === 'available'     ? 'selected' : ''; ?>>Available</option>
                        <option value="not-available" <?php echo $sort === 'not-available' ? 'selected' : ''; ?>>Not Available</option>
                    </select>
                </div>
                <button type="submit" style="padding:6px 14px;border-radius:8px;border:none;background:var(--pop-color);color:white;cursor:pointer;font-family:inherit;">Search</button>
            </form>
        </div>

        <div class="menu-content">
            <!-- CATEGORY TABS -->
            <div class="menu-category">
                <?php foreach ($categories as $cat): ?>
                <div style="<?php echo $category === $cat ? 'border-bottom:2px solid var(--pop-color);font-weight:600;' : ''; ?>cursor:pointer;"
                    onclick="window.location.href='menu.php?category=<?php echo urlencode($cat); ?>&sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>'">
                    <?php echo htmlspecialchars($cat); ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- MENU TABLE -->
            <div class="menu-table">
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($products_q) === 0): ?>
                            <tr><td colspan="4" style="text-align:center;color:#aaa;padding:20px;">No products found.</td></tr>
                        <?php else: ?>
                            <?php while ($product = mysqli_fetch_assoc($products_q)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category'] ?? '—'); ?></td>
                                <td>
                                    <span style="
                                        padding:3px 12px;
                                        border-radius:50px;
                                        font-size:12px;
                                        font-weight:600;
                                        background:<?php echo $product['is_available'] ? '#DCFCE7' : '#FEE2E2'; ?>;
                                        color:<?php echo $product['is_available'] ? '#166534' : '#991B1B'; ?>;">
                                        <?php echo $product['is_available'] ? 'Available' : 'Not Available'; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                        <input type="hidden" name="current" value="<?php echo $product['is_available']; ?>">
                                        <button type="submit" name="toggle" style="
                                            padding:5px 14px;
                                            border-radius:8px;
                                            border:none;
                                            cursor:pointer;
                                            font-family:inherit;
                                            font-size:12px;
                                            font-weight:600;
                                            background:<?php echo $product['is_available'] ? '#FEE2E2' : '#DCFCE7'; ?>;
                                            color:<?php echo $product['is_available'] ? '#991B1B' : '#166534'; ?>;">
                                            <?php echo $product['is_available'] ? 'Mark Unavailable' : 'Mark Available'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <nav class="bottom-nav">
        <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fa fa-dashboard"></i>
            <span>Dashboard</span>
        </a>
        <a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' || basename($_SERVER['PHP_SELF']) === 'order-details.php' ? 'active' : ''; ?>">
            <i class="fa fa-shopping-cart"></i>
            <span>Orders</span>
        </a>
        <a href="menu.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'menu.php' ? 'active' : ''; ?>">
            <i class="fa fa-bars"></i>
            <span>Menu</span>
        </a>
        <a href="logout.php">
            <i class="fa fa-sign-out"></i>
            <span>Logout</span>
        </a>
    </nav>

    <script>
    function liveSearch() {
        const query = document.getElementById('searchInput').value.toLowerCase();
        const rows = document.querySelectorAll('.menu-table tbody tr');
        rows.forEach(row => {
            const productName = row.querySelector('td:first-child');
            if (!productName) return;
            const text = productName.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    }
    </script>
</body>
</html>