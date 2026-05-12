<?php
require_once 'db.php'; // uses $conn (mysqli)

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── GET: list all products with sizes ─────────────────────────────────────
if ($method === 'GET' && $action === 'list') {
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

// ── GET: all addons ────────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'addons') {
    $addons = [];
    $res = mysqli_query($conn, "SELECT * FROM addons ORDER BY addon_id ASC");
    while ($row = mysqli_fetch_assoc($res)) $addons[] = $row;
    echo json_encode($addons);
    exit;
}

// ── GET: single product ────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'get') {
    $id  = (int)($_GET['id'] ?? 0);
    $res = mysqli_query($conn, "SELECT * FROM products WHERE product_id = $id");
    $product = mysqli_fetch_assoc($res);

    if (!$product) {
        echo json_encode(['error' => 'Product not found']);
        exit;
    }

    $sizes = [];
    $sRes  = mysqli_query($conn, "SELECT * FROM product_sizes WHERE product_id = $id");
    while ($s = mysqli_fetch_assoc($sRes)) $sizes[] = $s;
    $product['sizes'] = $sizes;

    echo json_encode($product);
    exit;
}

// ── POST: add product ──────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'add') {
    $data         = json_decode(file_get_contents('php://input'), true);
    $name         = mysqli_real_escape_string($conn, trim($data['product_name'] ?? ''));
    $description  = mysqli_real_escape_string($conn, trim($data['description'] ?? ''));
    $category     = mysqli_real_escape_string($conn, trim($data['category'] ?? ''));
    $image        = mysqli_real_escape_string($conn, trim($data['image'] ?? ''));
    $is_available = isset($data['is_available']) ? (int)$data['is_available'] : 1;
    $sizes        = $data['sizes'] ?? [];

    if (!$name) {
        echo json_encode(['error' => 'Product name is required']);
        exit;
    }

    $sql = "INSERT INTO products (product_name, description, category, image, is_available)
            VALUES ('$name', '$description', '$category', '$image', $is_available)";

    if (!mysqli_query($conn, $sql)) {
        echo json_encode(['error' => mysqli_error($conn)]);
        exit;
    }

    $newId = mysqli_insert_id($conn);

    foreach ($sizes as $size) {
        $sname = mysqli_real_escape_string($conn, trim($size['size_name'] ?? ''));
        $price = (float)($size['price'] ?? 0);
        if ($sname) {
            mysqli_query($conn, "INSERT INTO product_sizes (product_id, size_name, price)
                                 VALUES ($newId, '$sname', $price)");
        }
    }

    echo json_encode(['success' => true, 'product_id' => $newId]);
    exit;
}

// ── POST: upload image ─────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'upload_image') {
    $uploadDir = '../assets/products/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if (!isset($_FILES['image'])) {
        echo json_encode(['error' => 'No file uploaded']);
        exit;
    }

    $file    = $_FILES['image'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (!in_array($ext, $allowed)) {
        echo json_encode(['error' => 'Invalid file type']);
        exit;
    }

    $filename = 'product_' . time() . '_' . rand(100, 999) . '.' . $ext;
    $dest     = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(['success' => true, 'path' => '../assets/products/' . $filename]);
    } else {
        echo json_encode(['error' => 'Upload failed']);
    }
    exit;
}

// ── PUT: update product ────────────────────────────────────────────────────
if ($method === 'PUT' && $action === 'update') {
    $data         = json_decode(file_get_contents('php://input'), true);
    $id           = (int)($data['product_id'] ?? 0);
    $name         = mysqli_real_escape_string($conn, trim($data['product_name'] ?? ''));
    $description  = mysqli_real_escape_string($conn, trim($data['description'] ?? ''));
    $category     = mysqli_real_escape_string($conn, trim($data['category'] ?? ''));
    $image        = mysqli_real_escape_string($conn, trim($data['image'] ?? ''));
    $is_available = isset($data['is_available']) ? (int)$data['is_available'] : 1;
    $sizes        = $data['sizes'] ?? [];

    if (!$id || !$name) {
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $sql = "UPDATE products
            SET product_name='$name', description='$description',
                category='$category', image='$image', is_available=$is_available
            WHERE product_id=$id";

    if (!mysqli_query($conn, $sql)) {
        echo json_encode(['error' => mysqli_error($conn)]);
        exit;
    }

    // Replace sizes
    mysqli_query($conn, "DELETE FROM product_sizes WHERE product_id = $id");
    foreach ($sizes as $size) {
        $sname = mysqli_real_escape_string($conn, trim($size['size_name'] ?? ''));
        $price = (float)($size['price'] ?? 0);
        if ($sname) {
            mysqli_query($conn, "INSERT INTO product_sizes (product_id, size_name, price)
                                 VALUES ($id, '$sname', $price)");
        }
    }

    echo json_encode(['success' => true]);
    exit;
}

// ── DELETE: remove product ─────────────────────────────────────────────────
if ($method === 'DELETE' && $action === 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = (int)($data['product_id'] ?? 0);

    if (!$id) {
        echo json_encode(['error' => 'Missing product ID']);
        exit;
    }

    mysqli_query($conn, "DELETE FROM product_sizes WHERE product_id = $id");
    mysqli_query($conn, "DELETE FROM products WHERE product_id = $id");

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Invalid request']);