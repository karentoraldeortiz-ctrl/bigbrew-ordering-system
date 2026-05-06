<?php

session_start();
include "db.php";

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$products_result = mysqli_query($conn,
    "SELECT p.product_id, p.product_name, p.description, p.category, p.image,
            ps.size_id, ps.size_name, ps.price
     FROM products p
     JOIN product_sizes ps ON p.product_id = ps.product_id
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
    <meta name="description" content="Big Brew Maysan - Big in Taste, Bit in Price. ">
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
                <a href="index.html">
                    <img src="assets/logo/bb-maysan-logo-1.png" alt="">
                </a>
            </div>
            <div class="nav-links" id="navlinks">
                <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="#">Our Menu</a></li>
                <li><a href="about.html">About Us</a></li>
                <li><a href="cart.html"> <img src="assets/icons/icons8-cart-24.png" alt=""></a></li>
                <li><a href="account.html"> <img src="assets/icons/icons8-profile-24.png" alt=""></a></li>
                </ul>
            </div>

            <div class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
   <section class="hero">
  <div class="hero-text">
    <h1>Quality in Every Cup</h1>
    <p>
      Every drink we serve is made with premium ingredients, crafted with
      care, and priced fairly. We understand the student hustle — late-night
      study sessions, early morning classes, and everything in between.
      That's why we're here.
    </p>
  </div>

  <div class="hero-img">
    <img src="assets/products/HERO_MENU.png" alt="hero image">
  </div>
</section>
    </section>
     <div class="scallop"></div>

    <!-- Menu Section -->
    <section class="menu-section">
      <h1>MENU</h1>

      <div class="tabs">
    <button class="tab" data-category="all">all</button>
    <button class="tab" data-category="milk-tea">milk tea</button>
    <button class="tab" data-category="coffee">coffee</button>
    <button class="tab" data-category="fruit-tea">fruit tea</button>
    <button class="tab" data-category="brosty">brosty</button>
</div>

<div id="no-results" style="display: none; text-align: center; padding: 50px; color: #231916;">
    <p>No products found in this category.</p>
</div>

      <div class="products">

  <?php if(empty($products)): ?>
            <!-- Fallback: Kung walang products sa DB pa, ipakita ang static cards -->
            <!-- TANGGALIN ITO pagkatapos mong i-populate ang products table -->
            <p style="text-align:center; padding:40px;">
                No products yet. Please add products to the database.
            </p>
 
        <?php else: ?>
            <!-- Dynamic: galing sa database -->
            <?php foreach($products as $product): ?>
            <div class="card <?php echo htmlspecialchars($product['category']); ?>">
                <div class="card-inner">
                    <!-- FRONT -->
                    <div class="card-front">
                        <button class="flip-btn">↻</button>
                        <img src="<?php echo htmlspecialchars($product['image']); ?>"
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        <!-- ADD BUTTON: nagpapadala ng product info sa modal -->
                        <button class="add-btn"
                            data-id="<?php echo $product['product_id']; ?>"
                            data-name="<?php echo htmlspecialchars($product['product_name']); ?>"
                            data-img="<?php echo htmlspecialchars($product['image']); ?>"
                            data-sizes='<?php echo json_encode($product["sizes"]); ?>'>
                            +
                        </button>
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
        <li>info@bigbrew.com</li>
        <li>094 Maysan Rd, Valenzuela, 1442 Metro Manila</li>
      </ul>
    </div>

    <div class="footer-column">
      <h3>Quick Links</h3>
      <div class="links-grid">
        <ul>
          <li><a href="index.html">Home</a></li>
          <li><a href="menu.html">Menu</a></li>
          <li><a href="about.html">About</a></li>
        </ul>
        <ul>
          <li><a href="reviews.html">Reviews</a></li>
          <li><a href="terms.html">Terms & Conditions</a></li>
          <li><a href="privacy.html">Privacy Policy</a></li>
        </ul>
      </div>
    </div>

    <!-- ========= Cart PopUp Window ===========-->
   <div id="productModal" class="modal-overlay" style="display: none;">
    <div class="modal-card">
        <span class="close-modal">&times;</span>
        
        <div class="modal-content-wrapper">
            <div class="modal-left">
                <img id="modalProductImg" src="" alt="Selected Product">
            </div>

            <div class="modal-right">
                <h2 id="modalProductName">Product Name</h2>
                
                <p class="option-label">choose size</p>
                <div class="size-container">
                    <button class="size-opt" data-price="29" value="medio">medio <span>P 29</span></button>
                    <button class="size-opt" data-price="39" value="grande">grande <span>P 39</span></button>
                </div>

                <p class="option-label">add ons</p>
                <div class="addons-grid">
                    <label><input type="checkbox" class="addon-check" data-price="9" value="pearl"> <span>pearl</span> <span class="addon-price">P 9</span></label>
                    <label><input type="checkbox" class="addon-check" data-price="9" value="cream cheese"> <span>cream cheese</span> <span class="addon-price">P 9</span></label>
                    <label><input type="checkbox" class="addon-check" data-price="9" value="cream puff"> <span>cream puff</span> <span class="addon-price">P 9</span></label>
                    <label><input type="checkbox" class="addon-check" data-price="9" value="crystal"> <span>crystal</span> <span class="addon-price">P 9</span></label>
                    <label><input type="checkbox" class="addon-check" data-price="9" value="whipped cream"> <span>whipped cream</span> <span class="addon-price">P 9</span></label>
                    <label><input type="checkbox" class="addon-check" data-price="9" value="cheesecake"> <span>cheesecake</span> <span class="addon-price">P 9</span></label>
                    <label><input type="checkbox" class="addon-check" data-price="9" value="crushed oreo"> <span>crushed oreo</span> <span class="addon-price">P 9</span></label>
                    <label><input type="checkbox" class="addon-check" data-price="9" value="coffee jelly"> <span>coffee jelly</span> <span class="addon-price">P 9</span></label>
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



  <!-- FOOTER -->
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
    <script src="js/order.js"></script>
     <script src="js/global.js"></script>
</body>
</html>
