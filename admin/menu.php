<?php
require_once 'db.php';

// ── API handler ────────────────────────────────────────────────────────────
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];
    $method = $_SERVER['REQUEST_METHOD'];

    // List all products with sizes
    if ($action === 'list' && $method === 'GET') {
        $products = [];
        $res = mysqli_query($conn, "SELECT * FROM products ORDER BY product_id ASC");
        while ($row = mysqli_fetch_assoc($res)) {
            $id    = (int)$row['product_id'];
            $sizes = [];
            $sRes  = mysqli_query($conn, "SELECT * FROM product_sizes WHERE product_id = $id");
            while ($s = mysqli_fetch_assoc($sRes)) $sizes[] = $s;
            $row['sizes'] = $sizes;
            $products[]   = $row;
        }
        echo json_encode($products);
        exit;
    }

    // All addons
    if ($action === 'addons' && $method === 'GET') {
        $addons = [];
        $res = mysqli_query($conn, "SELECT * FROM addons ORDER BY addon_id ASC");
        while ($row = mysqli_fetch_assoc($res)) $addons[] = $row;
        echo json_encode($addons);
        exit;
    }

    // Single product
    if ($action === 'get' && $method === 'GET') {
        $id      = (int)($_GET['id'] ?? 0);
        $res     = mysqli_query($conn, "SELECT * FROM products WHERE product_id = $id");
        $product = mysqli_fetch_assoc($res);
        if (!$product) { echo json_encode(['error' => 'Product not found']); exit; }
        $sizes = [];
        $sRes  = mysqli_query($conn, "SELECT * FROM product_sizes WHERE product_id = $id");
        while ($s = mysqli_fetch_assoc($sRes)) $sizes[] = $s;
        $product['sizes'] = $sizes;
        echo json_encode($product);
        exit;
    }

    // ── FIX #2: Get assigned addons for a product ──────────────────────────
    if ($action === 'get_product_addons' && $method === 'GET') {
        $id  = (int)($_GET['id'] ?? 0);
        $out = [];
        // Check if product_addons table exists first
        $check = mysqli_query($conn, "SHOW TABLES LIKE 'product_addons'");
        if (mysqli_num_rows($check) > 0) {
            $res = mysqli_query($conn, "SELECT addon_id FROM product_addons WHERE product_id = $id");
            while ($r = mysqli_fetch_assoc($res)) $out[] = $r;
        }
        echo json_encode($out);
        exit;
    }

    // ── FIX #3: Save assigned addons for a product ─────────────────────────
    if ($action === 'save_product_addons' && $method === 'POST') {
        $data      = json_decode(file_get_contents('php://input'), true);
        $productId = (int)($data['product_id'] ?? 0);
        $addonIds  = $data['addon_ids'] ?? [];
        if (!$productId) { echo json_encode(['error' => 'Missing product ID']); exit; }

        // Create table if it doesn't exist yet
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS product_addons (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            addon_id   INT NOT NULL,
            UNIQUE KEY unique_pair (product_id, addon_id)
        )");

        mysqli_query($conn, "DELETE FROM product_addons WHERE product_id = $productId");
        foreach ($addonIds as $aid) {
            $aid = (int)$aid;
            if ($aid) mysqli_query($conn, "INSERT IGNORE INTO product_addons (product_id, addon_id) VALUES ($productId, $aid)");
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // Add product
    if ($action === 'add' && $method === 'POST') {
        $data         = json_decode(file_get_contents('php://input'), true);
        $name         = mysqli_real_escape_string($conn, trim($data['product_name'] ?? ''));
        $description  = mysqli_real_escape_string($conn, trim($data['description'] ?? ''));
        $category     = mysqli_real_escape_string($conn, trim($data['category'] ?? ''));
        $image        = mysqli_real_escape_string($conn, trim($data['image'] ?? ''));
        $is_available = isset($data['is_available']) ? (int)$data['is_available'] : 1;
        $sizes        = $data['sizes'] ?? [];

        if (!$name) { echo json_encode(['error' => 'Product name is required']); exit; }

        $sql = "INSERT INTO products (product_name, description, category, image, is_available)
                VALUES ('$name', '$description', '$category', '$image', $is_available)";
        if (!mysqli_query($conn, $sql)) { echo json_encode(['error' => mysqli_error($conn)]); exit; }

        $newId = mysqli_insert_id($conn);
        foreach ($sizes as $size) {
            $sname = mysqli_real_escape_string($conn, trim($size['size_name'] ?? ''));
            $price = (float)($size['price'] ?? 0);
            if ($sname) mysqli_query($conn, "INSERT INTO product_sizes (product_id, size_name, price) VALUES ($newId, '$sname', $price)");
        }
        echo json_encode(['success' => true, 'product_id' => $newId]);
        exit;
    }

    // ── FIX #1: Upload image — save FILENAME only (not full path) ──────────
    if ($action === 'upload_image' && $method === 'POST') {
        $uploadDir = dirname(__DIR__) . '/assets/products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        if (!isset($_FILES['image'])) { echo json_encode(['error' => 'No file uploaded']); exit; }

        $file    = $_FILES['image'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowed)) { echo json_encode(['error' => 'Invalid file type']); exit; }

        $filename = 'product_' . time() . '_' . rand(100, 999) . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            // ✅ FIX: Save filename only — NOT the full path like '../assets/products/...'
            echo json_encode(['success' => true, 'path' => $filename]);
        } else {
            echo json_encode(['error' => 'Upload failed']);
        }
        exit;
    }

    // Update product
    if ($action === 'update' && $method === 'PUT') {
        $data         = json_decode(file_get_contents('php://input'), true);
        $id           = (int)($data['product_id'] ?? 0);
        $name         = mysqli_real_escape_string($conn, trim($data['product_name'] ?? ''));
        $description  = mysqli_real_escape_string($conn, trim($data['description'] ?? ''));
        $category     = mysqli_real_escape_string($conn, trim($data['category'] ?? ''));
        $image        = mysqli_real_escape_string($conn, trim($data['image'] ?? ''));
        $is_available = isset($data['is_available']) ? (int)$data['is_available'] : 1;
        $sizes        = $data['sizes'] ?? [];

        if (!$id || !$name) { echo json_encode(['error' => 'Missing required fields']); exit; }

        $sql = "UPDATE products SET product_name='$name', description='$description',
                category='$category', image='$image', is_available=$is_available
                WHERE product_id=$id";
        if (!mysqli_query($conn, $sql)) { echo json_encode(['error' => mysqli_error($conn)]); exit; }

        mysqli_query($conn, "DELETE FROM product_sizes WHERE product_id = $id");
        foreach ($sizes as $size) {
            $sname = mysqli_real_escape_string($conn, trim($size['size_name'] ?? ''));
            $price = (float)($size['price'] ?? 0);
            if ($sname) mysqli_query($conn, "INSERT INTO product_sizes (product_id, size_name, price) VALUES ($id, '$sname', $price)");
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // Delete product
    if ($action === 'delete' && $method === 'DELETE') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = (int)($data['product_id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'Missing product ID']); exit; }
        mysqli_query($conn, "DELETE FROM product_sizes WHERE product_id = $id");
        // Also clean up product_addons if table exists
        $check = mysqli_query($conn, "SHOW TABLES LIKE 'product_addons'");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "DELETE FROM product_addons WHERE product_id = $id");
        }
        mysqli_query($conn, "DELETE FROM products WHERE product_id = $id");
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['error' => 'Invalid request']);
    exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Menu Management | BigBrew Admin</title>
    <link rel="shortcut icon" href="../assets/logo/logo-black.png" type="image/png" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="admin.css" />
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
        <div class="dash-tab">
          <a href="dashboard.php"><h3><i class="fa fa-dashboard"></i> Dashboard</h3></a>
        </div>
        <div class="menu-tab active">
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

    <!-- ── MAIN ── -->
    <main class="menu-main">
      <div class="menu-page-header">
        <div>
          <h2>Menu Management</h2>
          <p>Add, edit, or remove products from your menu</p>
        </div>
        <div class="menu-header-actions">
          <div class="menu-search-wrap">
            <i class="fa fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search products..." oninput="filterTable()" />
          </div>
          <button class="btn-add-product" onclick="openModal(null)">
            <i class="fa fa-plus"></i> Add New Product
          </button>
        </div>
      </div>

      <!-- Category tabs -->
      <div class="category-tabs" id="categoryTabs">
        <button class="cat-tab active" onclick="filterCategory('all', this)">All</button>
      </div>

      <!-- Toast -->
      <div id="toast" class="toast" style="display:none;"></div>

      <!-- Table -->
      <div class="menu-table-wrap">
        <table class="menu-prod-table">
          <thead>
            <tr>
              <th>Product Name</th>
              <th>Category</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="productTableBody">
            <tr><td colspan="4" class="table-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </main>

    <!-- ── MODAL ── -->
    <div class="modal-overlay" id="modalOverlay" onclick="closeOnBackdrop(event)">
      <div class="modal-box">
        <div class="modal-header">
          <h3 id="modalTitle">ADD MENU ITEM</h3>
          <button class="modal-close-btn" onclick="closeModal()"><i class="fa fa-times"></i></button>
        </div>

        <div class="modal-body">

          <!-- LEFT: Image + Add-ons -->
          <div class="modal-left">
            <div class="modal-section-title">Product Image</div>
            <div class="image-upload-box" id="imageUploadBox"
              onclick="document.getElementById('imageFileInput').click()">
              <div class="upload-placeholder" id="uploadPlaceholder">
                <span class="upload-emoji">🧋</span>
                <span class="replace-image-btn">+ Upload Image</span>
              </div>
              <img id="previewImg" src="" alt="" style="display:none;width:100%;height:100%;object-fit:cover;border-radius:10px;" />
              <div class="img-replace-overlay" id="replaceOverlay" style="display:none;">
                <i class="fa fa-camera"></i>
                <span>Replace Image</span>
              </div>
              <input type="file" id="imageFileInput" accept="image/*"
                style="display:none" onchange="previewImage(event)" />
            </div>
            <input type="hidden" id="editImagePath" value="" />

            <div class="modal-section-title" style="margin-top:14px;">Customization Option</div>
            <p class="addon-label">Add-ons</p>
            <div class="addons-scroll" id="addonsBody">
              <div class="addons-empty">Loading...</div>
            </div>
          </div>

          <!-- MIDDLE: Info + Pricing + Audit -->
          <div class="modal-middle">
            <input type="hidden" id="editProductId" value="" />

            <div class="field">
              <label>Name:</label>
              <input type="text" id="editName" placeholder="Product name" />
            </div>
            <div class="field">
              <label>Category:</label>
              <input type="text" id="editCategory" placeholder="e.g. milk-tea" />
            </div>
            <div class="field">
              <label>Description:</label>
              <textarea id="editDesc" placeholder="Short description..."></textarea>
            </div>

            <div class="availability-row">
              <div>
                <div class="av-label">Availability</div>
                <div class="av-sub" id="availabilityStatus">Product is currently available</div>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" id="availabilityToggle" checked onchange="updateAvailabilityLabel()" />
                <span class="toggle-slider"></span>
              </label>
            </div>

            <div class="modal-section-title" style="margin-top:4px;">Pricing</div>
            <p class="addon-label">Size Variants:</p>
            <div class="size-fields" id="sizeFields"></div>
            <button class="add-topping-btn" type="button" onclick="addSizeRow()">
              <i class="fa fa-plus"></i> Add size
            </button>

            <div class="audit-box">
              <div class="audit-title">Audit/System Info</div>
              Last Updated by: <strong>Admin</strong><br />
              Date: <strong id="auditDate"></strong>
            </div>
          </div>

        </div>

        <div class="modal-footer">
          <button class="btn-delete-item" id="btnDeleteItem" onclick="deleteProductFromModal()" style="display:none;">
            <i class="fa fa-trash"></i> Delete Item
          </button>
          <button class="btn-cancel-modal" onclick="closeModal()">Cancel</button>
          <button class="btn-save-changes" onclick="saveProduct()">
            <i class="fa fa-save"></i> Save Changes
          </button>
        </div>
      </div>
    </div>

    <script src="admin.js"></script>
  </body>
</html>