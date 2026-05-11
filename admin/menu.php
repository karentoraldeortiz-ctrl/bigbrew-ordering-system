<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../login.php");
    exit;
}
include "../db.php";

// ── AJAX: toggle availability ───────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'toggle_availability') {
    $product_id = (int) $_POST['product_id'];
    $current    = (int) $_POST['current'];
    $new_status = $current ? 0 : 1;
    mysqli_query($conn, "UPDATE products SET is_available = $new_status WHERE product_id = $product_id");
    echo json_encode(['success' => true, 'new_status' => $new_status]);
    exit;
}

// ── AJAX: save edited product ───────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'edit_product') {
    $product_id   = (int)   $_POST['product_id'];
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $category     = mysqli_real_escape_string($conn, $_POST['category']);
    $description  = mysqli_real_escape_string($conn, $_POST['description']);
    $price_medio  = (float) $_POST['price_medio'];
    $price_grande = (float) $_POST['price_grande'];
    $is_available = (int)   $_POST['is_available'];

    // Handle image upload
    $image_sql = "";
    if (!empty($_FILES['product_image']['name'])) {
        $upload_dir = "../assets/products/";
        $ext        = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $filename   = "product_" . $product_id . "_" . time() . "." . $ext;
        move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_dir . $filename);
        $image_sql = ", product_image = '$filename'";
    }

    mysqli_query($conn, "UPDATE products SET
        product_name  = '$product_name',
        category      = '$category',
        description   = '$description',
        price_medio   = $price_medio,
        price_grande  = $price_grande,
        is_available  = $is_available
        $image_sql
        WHERE product_id = $product_id");

    // Update add-ons
    if (isset($_POST['addon_ids'])) {
        foreach ($_POST['addon_ids'] as $i => $addon_id) {
            $addon_name  = mysqli_real_escape_string($conn, $_POST['addon_names'][$i]);
            $addon_price = (float) $_POST['addon_prices'][$i];
            if ($addon_id) {
                mysqli_query($conn, "UPDATE add_ons SET addon_name='$addon_name', price=$addon_price WHERE addon_id=$addon_id AND product_id=$product_id");
            } else {
                mysqli_query($conn, "INSERT INTO add_ons (product_id, addon_name, price) VALUES ($product_id,'$addon_name',$addon_price)");
            }
        }
    }
    if (isset($_POST['delete_addon_ids'])) {
        foreach ($_POST['delete_addon_ids'] as $del_id) {
            $del_id = (int) $del_id;
            mysqli_query($conn, "DELETE FROM add_ons WHERE addon_id=$del_id AND product_id=$product_id");
        }
    }

    echo json_encode(['success' => true]);
    exit;
}

// ── AJAX: add new product ───────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'add_product') {
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $category     = mysqli_real_escape_string($conn, $_POST['category']);
    $description  = mysqli_real_escape_string($conn, $_POST['description']);
    $price_medio  = (float) $_POST['price_medio'];
    $price_grande = (float) $_POST['price_grande'];
    $is_available = (int)   $_POST['is_available'];

    mysqli_query($conn, "INSERT INTO products (product_name, category, description, price_medio, price_grande, is_available)
        VALUES ('$product_name','$category','$description',$price_medio,$price_grande,$is_available)");
    $new_id = mysqli_insert_id($conn);

    if (!empty($_FILES['product_image']['name'])) {
        $upload_dir = "../assets/products/";
        $ext        = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $filename   = "product_" . $new_id . "_" . time() . "." . $ext;
        move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_dir . $filename);
        mysqli_query($conn, "UPDATE products SET product_image='$filename' WHERE product_id=$new_id");
    }

    echo json_encode(['success' => true, 'product_id' => $new_id]);
    exit;
}

// ── AJAX: delete product ────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'delete_product') {
    $product_id = (int) $_POST['product_id'];
    mysqli_query($conn, "DELETE FROM add_ons WHERE product_id=$product_id");
    mysqli_query($conn, "DELETE FROM products WHERE product_id=$product_id");
    echo json_encode(['success' => true]);
    exit;
}

// ── FETCH: get product for edit modal ───────────────────────────────────────
if (isset($_GET['get_product'])) {
    $product_id = (int) $_GET['get_product'];
    $p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE product_id=$product_id"));
    $addons_q = mysqli_query($conn, "SELECT * FROM add_ons WHERE product_id=$product_id");
    $addons = [];
    while ($a = mysqli_fetch_assoc($addons_q)) $addons[] = $a;
    $p['addons'] = $addons;
    echo json_encode($p);
    exit;
}

// ── PAGE: filters ───────────────────────────────────────────────────────────
$view     = isset($_GET['view'])     ? $_GET['view']     : 'management'; // management | availability
$search   = isset($_GET['search'])   ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sort     = isset($_GET['sort'])     ? $_GET['sort']     : 'all';
$category = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : 'All';

$where = [];
if ($search)   $where[] = "product_name LIKE '%$search%'";
if ($sort === 'available')     $where[] = "is_available = 1";
if ($sort === 'not-available') $where[] = "is_available = 0";
if ($category !== 'All')       $where[] = "category = '$category'";
$where_sql  = count($where) ? "WHERE " . implode(" AND ", $where) : "";
$products_q = mysqli_query($conn, "SELECT * FROM products $where_sql ORDER BY product_name ASC");

$cat_q = mysqli_query($conn, "SELECT DISTINCT category FROM products ORDER BY category ASC");
$categories = ['All'];
while ($row = mysqli_fetch_assoc($cat_q)) {
    if ($row['category']) $categories[] = $row['category'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Menu Management | BigBrew Admin</title>
<link rel="shortcut icon" href="../assets/logo/logo-black.png" type="image/png"/>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
<style>
/* ── CSS Variables ── */
:root {
    --sidebar-bg:   #2C1A0E;
    --sidebar-text: #F5E6D3;
    --active-bg:    #C8651A;
    --active-text:  #fff;
    --pop-color:    #C8651A;
    --body-bg:      #F5ECD7;
    --card-bg:      #fff;
    --text-dark:    #2C1A0E;
    --text-muted:   #9A7B5C;
    --border:       #E8D5BC;
    --green-bg:     #DCFCE7;
    --green-text:   #166534;
    --red-bg:       #FEE2E2;
    --red-text:     #991B1B;
    --sidebar-w:    240px;
}

* { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family: 'Poppins', sans-serif;
    background: var(--body-bg);
    color: var(--text-dark);
    display: flex;
    min-height: 100vh;
}

/* ── Sidebar ── */
.sidebar {
    width: var(--sidebar-w);
    background: var(--sidebar-bg);
    color: var(--sidebar-text);
    display: flex;
    flex-direction: column;
    padding: 20px 0 0;
    position: fixed;
    top: 0; left: 0;
    height: 100vh;
    z-index: 100;
    overflow-y: auto;
}

.sidebar .logo {
    padding: 0 20px 16px;
    text-align: center;
}
.sidebar .logo img {
    max-width: 140px;
    height: auto;
    filter: brightness(0) invert(1);
}

.sidebar hr {
    border: none;
    border-top: 1px solid rgba(255,255,255,0.1);
    margin: 4px 16px;
}

.sidebar h6 {
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 1.5px;
    color: rgba(245,230,211,0.5);
    padding: 14px 20px 6px;
    text-transform: uppercase;
}

.sidebar a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    color: var(--sidebar-text);
    text-decoration: none;
    font-size: 13.5px;
    font-weight: 500;
    border-radius: 8px;
    margin: 2px 10px;
    transition: background .2s, color .2s;
}
.sidebar a i { width: 18px; text-align: center; font-size: 14px; }
.sidebar a:hover { background: rgba(200,101,26,0.25); }
.sidebar a.active { background: var(--active-bg); color: var(--active-text); }

/* Sub-menu for Menu */
.sidebar .sub-menu { display: none; flex-direction: column; }
.sidebar .sub-menu.open { display: flex; }
.sidebar .sub-menu a {
    padding: 8px 20px 8px 46px;
    font-size: 12.5px;
    margin: 1px 10px;
}
.sidebar .menu-parent { cursor: pointer; }
.sidebar .menu-parent .arrow {
    margin-left: auto;
    font-size: 11px;
    transition: transform .2s;
}
.sidebar .menu-parent.open .arrow { transform: rotate(180deg); }

.sidebar .logout-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    color: var(--sidebar-text);
    font-size: 13.5px;
    font-weight: 500;
    border-radius: 8px;
    margin: 2px 10px;
    background: none;
    border: none;
    cursor: pointer;
    font-family: inherit;
    transition: background .2s;
    width: calc(100% - 20px);
    text-align: left;
}
.sidebar .logout-btn:hover { background: rgba(200,101,26,0.25); }
.sidebar .logout-btn i { width: 18px; text-align: center; }

.sidebar-footer {
    margin-top: auto;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-top: 1px solid rgba(255,255,255,0.1);
}
.sidebar-footer .avatar {
    width: 36px; height: 36px;
    background: var(--active-bg);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; color: #fff; flex-shrink: 0;
}
.sidebar-footer .info h5 { font-size: 12px; font-weight: 600; color: #fff; }
.sidebar-footer .info p  { font-size: 10px; color: rgba(245,230,211,0.6); }

/* ── Main Content ── */
.main-content {
    margin-left: var(--sidebar-w);
    flex: 1;
    padding: 32px 36px;
    min-height: 100vh;
}

/* ── Page Header ── */
.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}
.page-header h2 {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-dark);
}
.page-header p { font-size: 13px; color: var(--text-muted); margin-top: 2px; }

.btn-add {
    display: flex;
    align-items: center;
    gap: 7px;
    background: var(--pop-color);
    color: #fff;
    border: none;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: background .2s, transform .1s;
    white-space: nowrap;
}
.btn-add:hover { background: #a8520f; transform: translateY(-1px); }

/* ── View Tabs ── */
.view-tabs {
    display: flex;
    gap: 4px;
    background: #EAD9C0;
    border-radius: 10px;
    padding: 4px;
    margin-bottom: 22px;
    width: fit-content;
}
.view-tab {
    padding: 8px 20px;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    color: var(--text-muted);
    transition: all .2s;
    text-decoration: none;
    border: none;
    background: none;
    font-family: inherit;
}
.view-tab.active {
    background: #fff;
    color: var(--text-dark);
    font-weight: 600;
    box-shadow: 0 1px 4px rgba(0,0,0,0.1);
}

/* ── Filter Bar ── */
.filter-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}
.search-wrap {
    display: flex;
    align-items: center;
    background: #fff;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    padding: 0 12px;
    gap: 8px;
    flex: 1;
    min-width: 200px;
    max-width: 320px;
}
.search-wrap i { color: var(--text-muted); font-size: 13px; }
.search-wrap input {
    border: none; outline: none;
    font-size: 13px; font-family: inherit;
    padding: 9px 0; flex: 1;
    background: transparent;
}

.filter-select {
    padding: 9px 14px;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    font-family: inherit;
    font-size: 13px;
    background: #fff;
    color: var(--text-dark);
    cursor: pointer;
    outline: none;
}

/* ── Category Tabs ── */
.cat-tabs {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 22px;
}
.cat-tab {
    padding: 6px 16px;
    border-radius: 50px;
    font-size: 12.5px;
    font-weight: 500;
    cursor: pointer;
    border: 1.5px solid var(--border);
    background: #fff;
    color: var(--text-muted);
    transition: all .2s;
    text-decoration: none;
}
.cat-tab.active, .cat-tab:hover {
    background: var(--pop-color);
    color: #fff;
    border-color: var(--pop-color);
}

/* ── Product Grid (Management View) ── */
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 18px;
}

.product-card {
    background: var(--card-bg);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(44,26,14,0.07);
    transition: transform .2s, box-shadow .2s;
    position: relative;
}
.product-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(44,26,14,0.13);
}

.product-card .card-img {
    width: 100%;
    height: 160px;
    object-fit: cover;
    background: #F5ECD7;
    display: block;
}
.product-card .card-img-placeholder {
    width: 100%;
    height: 160px;
    background: #F5ECD7;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    font-size: 36px;
}

.unavail-badge {
    position: absolute;
    top: 8px; right: 8px;
    background: rgba(153,27,27,0.85);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 50px;
    letter-spacing: .5px;
}

.product-card .card-body {
    padding: 12px 14px;
}
.product-card .card-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.product-card .card-cat {
    font-size: 11px;
    color: var(--text-muted);
    margin-bottom: 8px;
}
.product-card .card-actions {
    display: flex;
    gap: 6px;
}
.btn-edit {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    border: 1.5px solid var(--pop-color);
    background: transparent;
    color: var(--pop-color);
    font-family: inherit;
    transition: all .15s;
    flex: 1;
    justify-content: center;
}
.btn-edit:hover { background: var(--pop-color); color: #fff; }

.btn-del {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px; height: 32px;
    border-radius: 7px;
    border: 1.5px solid var(--red-bg);
    background: var(--red-bg);
    color: var(--red-text);
    cursor: pointer;
    font-size: 13px;
    transition: all .15s;
    flex-shrink: 0;
}
.btn-del:hover { background: var(--red-text); color: #fff; border-color: var(--red-text); }

/* ── Availability Table ── */
.avail-table-wrap {
    background: var(--card-bg);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(44,26,14,0.07);
}
table { width: 100%; border-collapse: collapse; }
thead { background: #FAF3E8; }
thead th {
    padding: 14px 18px;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--text-muted);
    text-align: left;
    text-transform: uppercase;
    letter-spacing: .6px;
}
tbody td {
    padding: 13px 18px;
    font-size: 13px;
    border-top: 1px solid var(--border);
}
tbody tr:hover { background: #FDFAF5; }

.status-badge {
    display: inline-block;
    padding: 3px 12px;
    border-radius: 50px;
    font-size: 11.5px;
    font-weight: 600;
}
.status-badge.avail  { background: var(--green-bg); color: var(--green-text); }
.status-badge.unavail{ background: var(--red-bg);   color: var(--red-text); }

.btn-toggle {
    padding: 5px 14px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-family: inherit;
    font-size: 12px;
    font-weight: 600;
    transition: all .15s;
}
.btn-toggle.make-unavail { background: var(--red-bg); color: var(--red-text); }
.btn-toggle.make-avail   { background: var(--green-bg); color: var(--green-text); }
.btn-toggle:hover { filter: brightness(.92); }

/* ── Modal Overlay ── */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(44,26,14,0.45);
    z-index: 999;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.modal-overlay.open { display: flex; }

.modal {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 760px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 28px;
    position: relative;
    animation: modalIn .2s ease;
}
@keyframes modalIn {
    from { transform: translateY(20px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}

.modal-close {
    position: absolute;
    top: 18px; right: 20px;
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: var(--text-muted);
    line-height: 1;
    transition: color .15s;
}
.modal-close:hover { color: var(--text-dark); }

.modal h3 {
    font-size: 17px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 22px;
}

/* ── Modal Grid ── */
.modal-grid {
    display: grid;
    grid-template-columns: 180px 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}
@media (max-width: 600px) {
    .modal-grid { grid-template-columns: 1fr; }
}

/* Image Upload */
.img-upload-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}
.img-preview {
    width: 150px; height: 150px;
    border-radius: 12px;
    object-fit: cover;
    border: 2px dashed var(--border);
    background: #FAF3E8;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    cursor: pointer;
    transition: border-color .2s;
}
.img-preview:hover { border-color: var(--pop-color); }
.img-preview img { width: 100%; height: 100%; object-fit: cover; }
.img-preview .placeholder { color: var(--text-muted); font-size: 32px; }
.img-upload-label {
    font-size: 12px;
    color: var(--pop-color);
    font-weight: 600;
    cursor: pointer;
    display: flex; align-items: center; gap: 4px;
}

/* Form Fields */
.field-group { display: flex; flex-direction: column; gap: 4px; }
.field-group label {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: .5px;
}
.field-group input,
.field-group select,
.field-group textarea {
    padding: 9px 12px;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    font-family: inherit;
    font-size: 13px;
    color: var(--text-dark);
    outline: none;
    transition: border-color .15s;
    background: #fff;
}
.field-group input:focus,
.field-group select:focus,
.field-group textarea:focus { border-color: var(--pop-color); }
.field-group textarea { resize: vertical; min-height: 70px; }

/* Availability Toggle */
.avail-toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #FAF3E8;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 18px;
}
.avail-toggle-row .label-wrap span:first-child {
    font-size: 13px; font-weight: 600; display: block;
}
.avail-toggle-row .label-wrap span:last-child {
    font-size: 11.5px; color: var(--text-muted);
}

/* Toggle Switch */
.toggle-switch { position: relative; width: 48px; height: 26px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
    position: absolute;
    inset: 0;
    background: #ddd;
    border-radius: 26px;
    cursor: pointer;
    transition: .3s;
}
.toggle-slider:before {
    content: '';
    position: absolute;
    width: 20px; height: 20px;
    left: 3px; top: 3px;
    background: #fff;
    border-radius: 50%;
    transition: .3s;
}
.toggle-switch input:checked + .toggle-slider { background: var(--pop-color); }
.toggle-switch input:checked + .toggle-slider:before { transform: translateX(22px); }

/* Pricing Row */
.pricing-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 18px;
}

/* Section Label */
.section-label {
    font-size: 11.5px;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: .6px;
    margin-bottom: 10px;
}

/* Add-ons Table */
.addons-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
.addons-table th {
    font-size: 11px; font-weight: 600; color: var(--text-muted);
    text-transform: uppercase; letter-spacing: .5px;
    padding: 6px 8px; text-align: left;
    border-bottom: 1px solid var(--border);
}
.addons-table td { padding: 6px 8px; }
.addons-table input {
    padding: 6px 10px;
    border: 1.5px solid var(--border);
    border-radius: 7px;
    font-family: inherit;
    font-size: 12.5px;
    width: 100%;
    outline: none;
}
.addons-table input:focus { border-color: var(--pop-color); }
.btn-icon {
    background: none; border: none;
    cursor: pointer; font-size: 14px;
    padding: 4px; border-radius: 5px;
    transition: background .15s;
}
.btn-icon:hover { background: #f5f5f5; }

.btn-add-addon {
    font-size: 12px; color: var(--pop-color);
    background: none; border: none;
    cursor: pointer; font-family: inherit;
    font-weight: 600; padding: 4px 0;
    display: flex; align-items: center; gap: 5px;
}

/* Audit Info */
.audit-box {
    background: #FAF3E8;
    border-radius: 10px;
    padding: 12px 16px;
    margin-top: 16px;
    font-size: 11.5px;
    color: var(--text-muted);
    line-height: 1.7;
}
.audit-box strong { color: var(--text-dark); }

/* Modal Footer */
.modal-footer {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid var(--border);
    flex-wrap: wrap;
}
.btn-save {
    padding: 9px 22px;
    background: var(--pop-color);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: background .15s;
}
.btn-save:hover { background: #a8520f; }
.btn-cancel {
    padding: 9px 18px;
    background: none;
    color: var(--text-muted);
    border: 1.5px solid var(--border);
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: all .15s;
}
.btn-cancel:hover { background: #f5f5f5; }
.btn-delete-item {
    padding: 9px 18px;
    background: var(--red-bg);
    color: var(--red-text);
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: all .15s;
    margin-right: auto;
}
.btn-delete-item:hover { background: var(--red-text); color: #fff; }

/* ── Toast ── */
.toast {
    position: fixed;
    bottom: 24px; right: 24px;
    background: var(--text-dark);
    color: #fff;
    padding: 12px 20px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    z-index: 9999;
    transform: translateY(80px);
    opacity: 0;
    transition: all .3s;
    pointer-events: none;
}
.toast.show { transform: translateY(0); opacity: 1; }
.toast.success { background: #15803d; }
.toast.error   { background: #b91c1c; }

/* ── Empty State ── */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-muted);
}
.empty-state i { font-size: 40px; margin-bottom: 12px; opacity: .4; }
.empty-state p { font-size: 14px; }

/* ── Confirm Modal ── */
.confirm-modal {
    max-width: 380px;
    text-align: center;
}
.confirm-modal .confirm-icon {
    font-size: 42px; margin-bottom: 12px;
}
.confirm-modal p {
    font-size: 14px; color: var(--text-muted); margin-bottom: 20px;
}
</style>
</head>
<body>

<!-- ══════════════ SIDEBAR ══════════════ -->
<aside class="sidebar">
    <div class="logo">
        <img src="../assets/logo/bbmaysan.png" alt="BigBrew"/>
    </div>
    <hr/>
    <h6>Main Menu</h6>
    <a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a>

    <!-- Menu Parent (expandable) -->
    <div class="menu-parent <?php echo in_array(basename($_SERVER['PHP_SELF']), ['menu_management.php','menu_availability.php']) ? 'open' : ''; ?>"
         onclick="toggleMenu(this)">
        <a style="pointer-events:none;">
            <i class="fa fa-utensils"></i> Menu
            <i class="fa fa-chevron-down arrow"></i>
        </a>
    </div>
    <div class="sub-menu <?php echo in_array(basename($_SERVER['PHP_SELF']), ['menu_management.php']) ? 'open' : ''; ?>">
        <a href="menu_management.php?view=management"
           class="<?php echo ($view === 'management') ? 'active' : ''; ?>">
            <i class="fa fa-list"></i> Menu Management
        </a>
        <a href="menu_management.php?view=availability"
           class="<?php echo ($view === 'availability') ? 'active' : ''; ?>">
            <i class="fa fa-toggle-on"></i> Menu Availability
        </a>
    </div>

    <a href="sales_reports.php"><i class="fa fa-chart-bar"></i> Sales Reports</a>
    <a href="staff_accounts.php"><i class="fa fa-users"></i> Staff Accounts</a>
    <a href="reviews.php"><i class="fa fa-star"></i> Reviews</a>

    <hr/>
    <h6>Account</h6>
    <button class="logout-btn" onclick="window.location.href='logout.php'">
        <i class="fa fa-sign-out"></i> Logout
    </button>

    <div class="sidebar-footer">
        <div class="avatar"><i class="fa fa-user-circle"></i></div>
        <div class="info">
            <h5><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin User'); ?></h5>
            <p>admin@bigbrew.com</p>
        </div>
    </div>
</aside>

<!-- ══════════════ MAIN CONTENT ══════════════ -->
<div class="main-content">

    <div class="page-header">
        <div>
            <h2><?php echo $view === 'management' ? 'Menu Management' : 'Menu Availability'; ?></h2>
            <p><?php echo $view === 'management'
                ? 'Add, edit, or remove products from your menu'
                : 'Toggle the availability of today\'s products'; ?></p>
        </div>
        <?php if ($view === 'management'): ?>
        <button class="btn-add" onclick="openAddModal()">
            <i class="fa fa-plus"></i> Add New Product
        </button>
        <?php endif; ?>
    </div>

    <!-- View Tabs -->
    <div class="view-tabs">
        <a href="menu_management.php?view=management"
           class="view-tab <?php echo $view === 'management' ? 'active' : ''; ?>">
            <i class="fa fa-th" style="margin-right:6px;font-size:11px;"></i>Menu Management
        </a>
        <a href="menu_management.php?view=availability"
           class="view-tab <?php echo $view === 'availability' ? 'active' : ''; ?>">
            <i class="fa fa-toggle-on" style="margin-right:6px;font-size:11px;"></i>Menu Availability
        </a>
    </div>

    <!-- Filter Bar -->
    <form method="GET" id="filterForm">
        <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>"/>
        <input type="hidden" name="category" id="catInput" value="<?php echo htmlspecialchars($category); ?>"/>
        <div class="filter-bar">
            <div class="search-wrap">
                <i class="fa fa-search"></i>
                <input type="text" name="search" id="searchInput"
                       value="<?php echo htmlspecialchars($search); ?>"
                       placeholder="Search product..."
                       oninput="liveSearch()"/>
            </div>
            <?php if ($view === 'availability'): ?>
            <select name="sort" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="all"           <?php echo $sort==='all'?'selected':'';?>>All</option>
                <option value="available"     <?php echo $sort==='available'?'selected':'';?>>Available</option>
                <option value="not-available" <?php echo $sort==='not-available'?'selected':'';?>>Not Available</option>
            </select>
            <?php endif; ?>
            <button type="submit" style="display:none;"></button>
        </div>
    </form>

    <!-- Category Tabs -->
    <div class="cat-tabs">
        <?php foreach ($categories as $cat): ?>
        <a href="menu_management.php?view=<?php echo $view; ?>&category=<?php echo urlencode($cat); ?>&sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>"
           class="cat-tab <?php echo $category === $cat ? 'active' : ''; ?>">
            <?php echo htmlspecialchars($cat); ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- ── MANAGEMENT VIEW: Grid ── -->
    <?php if ($view === 'management'): ?>
    <div class="product-grid" id="productGrid">
        <?php if (mysqli_num_rows($products_q) === 0): ?>
        <div class="empty-state" style="grid-column:1/-1;">
            <i class="fa fa-coffee"></i>
            <p>No products found.</p>
        </div>
        <?php else: ?>
        <?php while ($product = mysqli_fetch_assoc($products_q)): ?>
        <div class="product-card" data-name="<?php echo strtolower(htmlspecialchars($product['product_name'])); ?>">
            <?php if (!$product['is_available']): ?>
            <span class="unavail-badge">Unavailable</span>
            <?php endif; ?>

            <?php if (!empty($product['product_image'])): ?>
            <img src="../assets/products/<?php echo htmlspecialchars($product['product_image']); ?>"
                 class="card-img" alt="<?php echo htmlspecialchars($product['product_name']); ?>"/>
            <?php else: ?>
            <div class="card-img-placeholder"><i class="fa fa-image"></i></div>
            <?php endif; ?>

            <div class="card-body">
                <div class="card-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                <div class="card-cat"><?php echo htmlspecialchars($product['category'] ?? '—'); ?></div>
                <div class="card-actions">
                    <button class="btn-edit" onclick="openEditModal(<?php echo $product['product_id']; ?>)">
                        <i class="fa fa-edit"></i> Edit
                    </button>
                    <button class="btn-del" onclick="confirmDelete(<?php echo $product['product_id']; ?>, '<?php echo addslashes($product['product_name']); ?>')">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- ── AVAILABILITY VIEW: Table ── -->
    <?php else: ?>
    <div class="avail-table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="availBody">
                <?php if (mysqli_num_rows($products_q) === 0): ?>
                <tr><td colspan="4"><div class="empty-state"><i class="fa fa-coffee"></i><p>No products found.</p></div></td></tr>
                <?php else: ?>
                <?php while ($product = mysqli_fetch_assoc($products_q)): ?>
                <tr data-name="<?php echo strtolower(htmlspecialchars($product['product_name'])); ?>">
                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($product['category'] ?? '—'); ?></td>
                    <td>
                        <span class="status-badge <?php echo $product['is_available'] ? 'avail' : 'unavail'; ?>">
                            <?php echo $product['is_available'] ? 'Available' : 'Not Available'; ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn-toggle <?php echo $product['is_available'] ? 'make-unavail' : 'make-avail'; ?>"
                                onclick="toggleAvail(this, <?php echo $product['product_id']; ?>, <?php echo $product['is_available']; ?>)">
                            <?php echo $product['is_available'] ? 'Mark Unavailable' : 'Mark Available'; ?>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ══════════════ EDIT / ADD MODAL ══════════════ -->
<div class="modal-overlay" id="editModalOverlay">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('editModalOverlay')">×</button>
        <h3 id="modalTitle">Edit Menu Item</h3>

        <form id="editForm" enctype="multipart/form-data">
            <input type="hidden" id="editProductId" name="product_id"/>
            <input type="hidden" id="editAction"    name="action" value="edit_product"/>
            <input type="hidden" id="deleteAddonIds" name="delete_addon_ids[]"/>

            <!-- Availability Toggle -->
            <div class="avail-toggle-row">
                <div class="label-wrap">
                    <span>Availability</span>
                    <span id="availStatusText">Available</span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="availToggle" name="is_available" value="1"
                           onchange="updateAvailLabel(this)"/>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <!-- Main Grid -->
            <div class="modal-grid">
                <!-- Image -->
                <div class="img-upload-box">
                    <label for="imgInput">
                        <div class="img-preview" id="imgPreview">
                            <span class="placeholder"><i class="fa fa-image"></i></span>
                        </div>
                    </label>
                    <label class="img-upload-label" for="imgInput">
                        <i class="fa fa-camera"></i> Replace Image
                    </label>
                    <input type="file" id="imgInput" name="product_image"
                           accept="image/*" style="display:none;" onchange="previewImg(this)"/>
                </div>

                <!-- Name, Category, Description -->
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <div class="field-group">
                        <label>Name</label>
                        <input type="text" id="editName" name="product_name" placeholder="Product name" required/>
                    </div>
                    <div class="field-group">
                        <label>Category</label>
                        <select id="editCategory" name="category">
                            <?php
                            // Re-query categories
                            $cat_q2 = mysqli_query($conn, "SELECT DISTINCT category FROM products ORDER BY category ASC");
                            while ($cr = mysqli_fetch_assoc($cat_q2)):
                                if ($cr['category']):
                            ?>
                            <option value="<?php echo htmlspecialchars($cr['category']); ?>">
                                <?php echo htmlspecialchars($cr['category']); ?>
                            </option>
                            <?php endif; endwhile; ?>
                        </select>
                    </div>
                    <div class="field-group">
                        <label>Description</label>
                        <textarea id="editDesc" name="description" placeholder="Short description..."></textarea>
                    </div>
                </div>

                <!-- Pricing -->
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <div class="section-label" style="margin-bottom:0;">Pricing — Size Variants</div>
                    <div class="field-group">
                        <label>Medio (₱)</label>
                        <input type="number" id="editMedio" name="price_medio" step="0.01" min="0" placeholder="0.00"/>
                    </div>
                    <div class="field-group">
                        <label>Grande (₱)</label>
                        <input type="number" id="editGrande" name="price_grande" step="0.01" min="0" placeholder="0.00"/>
                    </div>
                </div>
            </div>

            <!-- Add-ons -->
            <div class="section-label">Customization Options — Add-ons</div>
            <table class="addons-table" id="addonsTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Price (₱)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="addonRows"></tbody>
            </table>
            <button type="button" class="btn-add-addon" onclick="addAddonRow()">
                <i class="fa fa-plus-circle"></i> Add New Topping
            </button>

            <!-- Audit Info -->
            <div class="audit-box" id="auditBox" style="display:none;">
                <strong>Audit / System Info</strong><br/>
                Last Updated By: <span id="auditUser"></span> &nbsp;|&nbsp;
                Date: <span id="auditDate"></span>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-delete-item" id="btnDeleteItem"
                        onclick="confirmDeleteFromModal()" style="display:none;">
                    Delete Item
                </button>
                <button type="button" class="btn-cancel" onclick="closeModal('editModalOverlay')">Cancel</button>
                <button type="button" class="btn-save" onclick="saveProduct()">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════ CONFIRM DELETE MODAL ══════════════ -->
<div class="modal-overlay" id="confirmOverlay">
    <div class="modal confirm-modal">
        <div class="confirm-icon">🗑️</div>
        <h3 id="confirmTitle">Delete Product?</h3>
        <p id="confirmMsg">This action cannot be undone.</p>
        <div class="modal-footer" style="justify-content:center;">
            <button class="btn-cancel" onclick="closeModal('confirmOverlay')">Cancel</button>
            <button class="btn-delete-item" id="btnConfirmDelete" style="margin-right:0;">Delete</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
// ── Sidebar toggle ──────────────────────────────────────────────────────────
function toggleMenu(el) {
    el.classList.toggle('open');
    el.nextElementSibling.classList.toggle('open');
}
// Auto-open if active
document.querySelectorAll('.menu-parent').forEach(el => {
    if (el.classList.contains('open')) {
        el.nextElementSibling.classList.add('open');
    }
});

// ── Live search ─────────────────────────────────────────────────────────────
function liveSearch() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const items = document.querySelectorAll('[data-name]');
    items.forEach(el => {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
}

// ── Modal helpers ───────────────────────────────────────────────────────────
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

// ── Toast ───────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast ' + type + ' show';
    setTimeout(() => t.classList.remove('show'), 3000);
}

// ── Availability toggle (table row) ─────────────────────────────────────────
function toggleAvail(btn, productId, current) {
    const fd = new FormData();
    fd.append('action', 'toggle_availability');
    fd.append('product_id', productId);
    fd.append('current', current);
    fetch('menu_management.php', { method:'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const newStatus = data.new_status;
            const row = btn.closest('tr');
            const badge = row.querySelector('.status-badge');
            badge.className = 'status-badge ' + (newStatus ? 'avail' : 'unavail');
            badge.textContent = newStatus ? 'Available' : 'Not Available';
            btn.className = 'btn-toggle ' + (newStatus ? 'make-unavail' : 'make-avail');
            btn.textContent = newStatus ? 'Mark Unavailable' : 'Mark Available';
            btn.setAttribute('onclick', `toggleAvail(this, ${productId}, ${newStatus})`);
            showToast(newStatus ? '✅ Marked Available' : '❌ Marked Unavailable');
        });
}

// ── Availability toggle label (in modal) ─────────────────────────────────────
function updateAvailLabel(chk) {
    document.getElementById('availStatusText').textContent = chk.checked ? 'Available' : 'Not Available';
    document.getElementById('availStatusText').style.color = chk.checked ? '#166534' : '#991B1B';
}

// ── Image preview ───────────────────────────────────────────────────────────
function previewImg(input) {
    if (!input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('imgPreview');
        preview.innerHTML = `<img src="${e.target.result}" alt="preview"/>`;
    };
    reader.readAsDataURL(input.files[0]);
}

// ── Add-on rows ─────────────────────────────────────────────────────────────
let deletedAddonIds = [];

function addAddonRow(id = '', name = '', price = '') {
    const tbody = document.getElementById('addonRows');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="addon_names[]" value="${name}" placeholder="Topping name" required/></td>
        <td><input type="number" name="addon_prices[]" value="${price}" step="0.01" min="0" placeholder="0.00" style="max-width:90px;"/></td>
        <td><input type="hidden" name="addon_ids[]" value="${id}"/>
            <button type="button" class="btn-icon" style="color:#991B1B;" onclick="removeAddonRow(this, '${id}')">
                <i class="fa fa-trash"></i>
            </button>
        </td>`;
    tbody.appendChild(tr);
}

function removeAddonRow(btn, id) {
    if (id) deletedAddonIds.push(id);
    btn.closest('tr').remove();
}

// ── Open Edit Modal ──────────────────────────────────────────────────────────
function openEditModal(productId) {
    deletedAddonIds = [];
    document.getElementById('addonRows').innerHTML = '';

    fetch(`menu_management.php?get_product=${productId}`)
        .then(r => r.json())
        .then(p => {
            document.getElementById('modalTitle').textContent = 'Edit Menu Item';
            document.getElementById('editAction').value = 'edit_product';
            document.getElementById('editProductId').value = p.product_id;
            document.getElementById('editName').value      = p.product_name || '';
            document.getElementById('editDesc').value      = p.description  || '';
            document.getElementById('editMedio').value     = p.price_medio  || '';
            document.getElementById('editGrande').value    = p.price_grande || '';

            // Category
            const sel = document.getElementById('editCategory');
            for (let opt of sel.options) {
                if (opt.value === p.category) { opt.selected = true; break; }
            }

            // Availability toggle
            const chk = document.getElementById('availToggle');
            chk.checked = p.is_available == 1;
            updateAvailLabel(chk);

            // Image
            const preview = document.getElementById('imgPreview');
            preview.innerHTML = p.product_image
                ? `<img src="../assets/products/${p.product_image}" alt="${p.product_name}"/>`
                : `<span class="placeholder"><i class="fa fa-image"></i></span>`;
            document.getElementById('imgInput').value = '';

            // Add-ons
            (p.addons || []).forEach(a => addAddonRow(a.addon_id, a.addon_name, a.price));

            // Audit
            if (p.last_updated_by || p.updated_at) {
                document.getElementById('auditBox').style.display  = 'block';
                document.getElementById('auditUser').textContent   = p.last_updated_by || 'admin@bigbrew.com';
                document.getElementById('auditDate').textContent   = p.updated_at || '—';
            } else {
                document.getElementById('auditBox').style.display  = 'none';
            }

            document.getElementById('btnDeleteItem').style.display = 'inline-flex';
            document.getElementById('btnDeleteItem').dataset.pid   = p.product_id;
            document.getElementById('btnDeleteItem').dataset.pname = p.product_name;

            document.getElementById('editModalOverlay').classList.add('open');
        });
}

// ── Open Add Modal ───────────────────────────────────────────────────────────
function openAddModal() {
    deletedAddonIds = [];
    document.getElementById('addonRows').innerHTML = '';
    document.getElementById('editForm').reset();
    document.getElementById('imgPreview').innerHTML = `<span class="placeholder"><i class="fa fa-image"></i></span>`;
    document.getElementById('modalTitle').textContent = 'Add New Product';
    document.getElementById('editAction').value = 'add_product';
    document.getElementById('editProductId').value = '';
    document.getElementById('btnDeleteItem').style.display = 'none';
    document.getElementById('auditBox').style.display = 'none';

    const chk = document.getElementById('availToggle');
    chk.checked = true;
    updateAvailLabel(chk);

    document.getElementById('editModalOverlay').classList.add('open');
}

// ── Save Product ─────────────────────────────────────────────────────────────
function saveProduct() {
    const form = document.getElementById('editForm');
    const fd   = new FormData(form);

    // Collect deleted addon IDs
    deletedAddonIds.forEach(id => fd.append('delete_addon_ids[]', id));

    // Ensure availability value is sent correctly
    const chk = document.getElementById('availToggle');
    fd.set('is_available', chk.checked ? '1' : '0');

    fetch('menu_management.php', { method:'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('✅ Product saved successfully!');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('❌ Something went wrong.', 'error');
            }
        });
}

// ── Confirm Delete (from card) ───────────────────────────────────────────────
function confirmDelete(productId, productName) {
    document.getElementById('confirmTitle').textContent = `Delete "${productName}"?`;
    document.getElementById('confirmMsg').textContent   = 'This will permanently remove the product and all its add-ons.';
    document.getElementById('btnConfirmDelete').onclick = () => deleteProduct(productId);
    document.getElementById('confirmOverlay').classList.add('open');
}

// ── Confirm Delete (from modal) ──────────────────────────────────────────────
function confirmDeleteFromModal() {
    const btn = document.getElementById('btnDeleteItem');
    confirmDelete(btn.dataset.pid, btn.dataset.pname);
}

// ── Delete Product ───────────────────────────────────────────────────────────
function deleteProduct(productId) {
    const fd = new FormData();
    fd.append('action', 'delete_product');
    fd.append('product_id', productId);
    fetch('menu_management.php', { method:'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeModal('confirmOverlay');
                closeModal('editModalOverlay');
                showToast('🗑️ Product deleted.');
                setTimeout(() => location.reload(), 1000);
            }
        });
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) overlay.classList.remove('open');
    });
});
</script>
</body>
</html>