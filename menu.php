<?php

session_start();
include "db.php";
include "ban-check.php";

$isLoggedIn = isset($_SESSION['user_id']);

$products_result = mysqli_query($conn,
    "SELECT p.product_id, p.product_name, p.description, p.category, p.image,
            p.is_available,
            ps.size_id, ps.size_name, ps.price
     FROM products p
     JOIN product_sizes ps ON p.product_id = ps.product_id
     WHERE p.is_archived = 0
     ORDER BY p.category, p.product_name, ps.price ASC"
);

$products = [];
while($row = mysqli_fetch_assoc($products_result)) {
    $pid = $row['product_id'];
    if(!isset($products[$pid])) {
        $products[$pid] = [
            'product_id'   => $pid,
            'product_name' => $row['product_name'],
            'description'  => $row['description'],
            'category'     => $row['category'],
            'image'        => $row['image'],
            'is_available' => $row['is_available'],
            'sizes'        => []
        ];
    }
    
    $products[$pid]['sizes'][] = [
        'size_id'   => $row['size_id'],
        'size_name' => $row['size_name'],
        'price'     => $row['price']
    ];
}

$addons_result = mysqli_query($conn, "SELECT * FROM addons ORDER BY addon_name");
$addons = [];
while($row = mysqli_fetch_assoc($addons_result)) {
    $addons[] = $row;
}
?>

<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Big Brew Maysan - Big in Taste, Bit in Price.">
    <meta name="keywords" content="BigBrew Maysan, Maysan, Online Order, Milktea">
    <meta name="author" content="Allyana Flores, Karen Ortiz">
    <title>BigBrew | Our Menu</title>    
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/order.css">
    <link rel="shortcut icon" href="assets/logo/logo-black.png" type="image/x-icon">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

  <!-- NAV BAR -->
  <header>
    <nav class="navbar">
      <div class="navlogo">
        <a href="index.php">
          <img src="assets/logo/bb-maysan-logo-1.png" alt="">
        </a>
      </div>
      <div class="nav-links" id="navlinks">
        <ul>
          <li><a href="index.php">Home</a></li>
          <li><a href="menu.php">Our Menu</a></li>
          <li><a href="about.php">About Us</a></li> 
          <li>
            <a href="cart.php" class="cart-link">
              <img src="assets/icons/icons8-cart-24.png" alt="">
              <span id="cartBadge" class="cart-badge">0</span>
            </a>
          </li>
          <li><a href="account.php"><img src="assets/icons/icons8-profile-24.png" alt=""></a></li>
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

  <!-- <div class="scallop"></div> -->

  <!-- Menu Section -->
  <section class="menu-section" id="menu-section">

    <div class="tabs">
      <h1>MENU</h1>
      <button class="tab" data-category="all"><span>all</span></button>
      <button class="tab" id="milk-tea" data-category="milk-tea"><span>milk tea</span></button>
      <button class="tab" id="coffee" data-category="coffee"><span>coffee</span></button>
      <button class="tab" id="fruit-tea" data-category="fruit-tea"><span>fruit tea</span></button>
      <button class="tab" id="praf" data-category="praf"><span>praf</span></button>
      <button class="tab" id="brosty" data-category="brosty"><span>brosty</span></button>
    </div>

    <div id="no-results" style="display: none; text-align: center; padding: 50px; color: #231916;">
      <p>No products found in this category.</p>
    </div>

    <div class="products">
      <?php if(empty($products)): ?>
        <p style="text-align:center; padding:40px;">
          No products yet. Please add products to the database.
        </p>
      <?php else: ?>
        <?php foreach($products as $product): ?>
          <div class="card <?php echo htmlspecialchars($product['category']); ?> <?php echo !$product['is_available'] ? 'not-available' : ''; ?>">
            <div class="card-inner">
              <!-- FRONT -->
              <div class="card-front">
                <button class="flip-btn">↻</button>
                <img src="assets/products/<?php echo htmlspecialchars($product['image']); ?>"
                     alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                <p class="product-category"><?php echo ucwords(str_replace('-', ' ', $product['category'])); ?></p>
                <?php if($product['is_available']): ?>
                  <button class="add-btn"
                    data-id="<?php echo $product['product_id']; ?>"
                    data-name="<?php echo htmlspecialchars($product['product_name']); ?>"
                    data-img="<?php echo htmlspecialchars($product['image']); ?>"
                    data-category="<?php echo htmlspecialchars($product['category']); ?>"
                    data-sizes='<?php echo json_encode($product["sizes"]); ?>'>
                    +
                  </button>
                <?php else: ?>
                  <div class="unavailable-badge">Not Available</div>
                <?php endif; ?>
              </div>
              <!-- BACK -->
              <div class="card-back">
                <button class="flip-btn">↻</button>
                <h4><?php echo htmlspecialchars($product['product_name']); ?></h4>
                <p><?php echo htmlspecialchars($product['description']); ?></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </section>

  <footer class="main-footer">
    <div class="footer-container">
      <div class="footer-brand">
        <div class="footer-logo">
          <img src="assets/logo/bb-maysan-logo-1.png" alt="Big Brew Maysan Logo">
        </div>
        <p>BIGBREW - Maysan, Valenzuela City</p>
      </div>

      <div class="footer-column">
        <h3>Contact</h3>
        <ul>
          <li>0929 563 4350</li>
          <li>094 Maysan Rd, Valenzuela, 1442 Metro Manila</li>
        </ul>
      </div>

      <div class="footer-column">
        <h3>Quick Links</h3>
        <div class="links-grid">
          <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="menu.php">Menu</a></li>
            <li><a href="about.php">About</a></li>
          </ul>
          <ul>
            <li><a href="reviews.php">Reviews</a></li>
            <li><a href="terms.php">Terms & Conditions</a></li>
            <li><a href="privacy.php">Privacy Policy</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-column">
        <h3>Follow us on</h3>
        <div class="social-icons">
          <a href="https://www.facebook.com/p/BigBrew-Maysan-Rd-Valenzuela-100085267776562/">
            <i class="fab fa-facebook"></i></a>
          <a href="https://www.instagram.com/bigbrew.maysan/"><i class="fab fa-instagram"></i></a>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; 2026. BigBrew Maysan. All Rights Reserved.</p>
    </div>
  </footer>

  <!-- ========= Cart PopUp Window ===========-->
  <div id="productModal" class="modal-overlay1" style="display: none;">
    <div class="modal-card">
      <span class="close-modal">&times;</span>
      
      <div class="modal-content-wrapper">
        <div class="modal-left">
          <img id="modalProductImg" src="" alt="Selected Product">
        </div>

        <div class="modal-right">
          <h2 id="modalProductName">Product Name</h2>
          <p id="modalProductCategory" class="product-category"></p>

          <p class="option-label">choose size</p>
          <div class="size-container"></div>

          <p class="option-label">add ons</p>
          <div class="addons-grid">
             <p id="noAddonsMsg" style="display:none; color:#888; font-size:0.85rem; grid-column: 1/-1; margin: 0;">
        No add ons available for this product.
    </p>
            <?php foreach($addons as $addon): ?>
              <label>
                <input type="checkbox" 
                  class="addon-check"
                  data-addon-id="<?php echo $addon['addon_id']; ?>"
                  data-price="<?php echo $addon['price']; ?>" 
                  value="<?php echo htmlspecialchars($addon['addon_name']); ?>">
                <span><?php echo htmlspecialchars($addon['addon_name']); ?></span>
                <span class="addon-price">₱<?php echo $addon['price']; ?></span>
              </label>
            <?php endforeach; ?>
          </div>

          <div class="modal-footer">
            <div class="price-summary">
              <span class="total-display">P <span id="displayPrice">0</span></span>
              <p id="selectionText">Please select a size</p>
            </div>
            <div class="qty-stepper">
              <button id="btnMinus">-</button>
              <span id="qtyVal">1</span>
              <button id="btnPlus">+</button>
            </div>
          </div>

          <button id="btnAddToCart" class="main-add-btn" disabled>add to cart</button>
        </div>
      </div>
    </div>
  </div>
<?php $ban_check_render = true; include "ban-check.php"; ?>  <script>
    window.IS_LOGGED_IN = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
  </script>
  <script src="js/order.js"></script>
  <script src="js/global.js"></script>
</body>
</html>