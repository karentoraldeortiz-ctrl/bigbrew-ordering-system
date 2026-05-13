<?php
session_start();
?>


<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- GLOBAL METADATA -->
    <meta
      name="description"
      content="Big Brew Maysan - Big in Taste, Bit in Price. "
    />
    <meta
      name="keywords"
      content="BigBrew Maysan, Maysan, Online Order, Milktea"
    />
    <meta name="author" content="Allyana Flores, Karen Ortiz" />
    <title>BigBrew - Maysan</title>
    <link rel="stylesheet" href="css/global.css" />
    <link rel="stylesheet" href="css/main.css" />
    <link
      rel="shortcut icon"
      href="assets/logo/logo-black.png"
      type="image/x-icon"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    />
    <!-- END GLOBAL METADATA -->

    
  </head>
  <body>
    <header>
      <nav class="navbar">
        <div class="navlogo">
          <a href="index.php">
            <img src="assets/logo/bb-maysan-logo-1.png" alt="" />
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
                <li><a href="account.php"> <img src="assets/icons/icons8-profile-24.png" alt=""></a></li>
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

<section class="home-hero-slider">

  <!-- SLIDE 1 -->
  <div class="home-hero-card hero-slide active hero-price-slide-dark">

    <div class="price-side left">
      <h3>MEDIO</h3>
      <h2>29</h2>
      <p>16oz</p>
    </div>

    <div class="hero-center-text">
      <img src="assets/logo/logo-white.png" alt="BigBrew Logo">
      <h1>BIG IN TASTE.<br>BIT IN PRICE.</h1>
    </div>

    <div class="price-side right">
      <h3>GRANDE</h3>
      <h2>39</h2>
      <p>22oz</p>
    </div>

  </div>

  <!-- SLIDE 2 -->
  <div class="home-hero-card hero-slide  hero-main-slide">

    <img 
      src="assets/pictures/milktea-hd.png" 
      alt="BigBrew Drink" 
      class="hero-drink hero-drink-left"
    />

    <div class="home-hero-text">
      <h1>Sip Your Favorites<br />Without the Wait.</h1>

      <a href="menu.php" class="hero-order-btn">Order Now</a>
    </div>

    <img 
      src="assets/pictures/milktea-hd.png" 
      alt="BigBrew Drink" 
      class="hero-drink hero-drink-right"
    />
  </div>

  <!-- SLIDE 3 -->
  <div class="home-hero-card hero-slide  hero-price-slide-light">

    <div class="price-side left">
      <h3>MEDIO</h3>
      <h2>29</h2>
      <p>16oz</p>
    </div>

    <div class="hero-center-text">
      <img src="assets/logo/logo-black.png" alt="BigBrew Logo">
      <h1>BIG IN TASTE.<br>BIT IN PRICE.</h1>
    </div>

    <div class="price-side right">
      <h3>GRANDE</h3>
      <h2>39</h2>
      <p>22oz</p>
    </div>

  </div>

</section>
          <section class="home-menu-preview">
            <div class="home-section-header">
              <h2>What are you craving for?</h2>
              <p>Quench your thirst!</p>
            </div>

            <div class="category-preview-list">
              <a href="menu.php#milk-tea" class="category-preview-card">
                <img src="assets/pictures/milktea.png" alt="Milk Tea">
                <span>Milk Tea</span>
              </a>
              
              <a href="menu.php#praf" class="category-preview-card">
                <img src="assets/pictures/praf.png" alt="Praf">
                <span>Praf</span>
              </a>

              <a href="menu.php#fruit-tea" class="category-preview-card">
                <img src="assets/pictures/fruittea.png" alt="Fruit Tea">
                <span>Fruit Tea</span>
              </a>

              <a href="menu.php#coffee" class="category-preview-card">
                <img src="assets/pictures/coffee.png" alt="Coffee">
                <span>Coffee</span>
              </a>

              <a href="menu.php#brosty" class="category-preview-card">
                <img src="assets/pictures/brosty.png" alt="Brosty">
                <span>Brosty</span>
              </a>
            </div>
          </section>

        <section class="best-seller">
          <div class="best-seller-header">
            <h2>Famous Drinks</h2>
            <p>Treat yourself with our best sellers!</p>
          </div>        
          <div class="products">
          <div class="arrow arrow-left" onclick="moveCarousel(-1)">
            <img src="assets/icons/icons8-arrow-24-left.png" alt="Previous" />
          </div>

          <div class="carousel-wrapper">
            <div class="carousel-track" id="carouselTrack">

              <div class="card">
                <div class="card-inner">
                  <div class="card-front">
                    <button class="flip-btn">↻</button>
                    <img src="assets/products/okinawa.jpg" />
                    <h3>Okinawa</h3>
                  </div>
                  <div class="card-back">
                    <button class="flip-btn">↻</button>
                    <h4>Okinawa</h4>
                    <p>Buttery caramel notes infused in classic milk tea.</p>
                  </div>
                </div>
              </div>

              <div class="card">
                <div class="card-inner">
                  <div class="card-front">
                    <button class="flip-btn">↻</button>
                    <img src="assets/products/wintermelon.jpg" />
                    <h3>Wintermelon</h3>
                  </div>
                  <div class="card-back">
                    <button class="flip-btn">↻</button>
                    <h4>Wintermelon</h4>
                    <p>Light, sweet, and refreshing wintermelon flavor.</p>
                  </div>  
                </div>
              </div>

              <div class="card">
                <div class="card-inner">
                  <div class="card-front">
                    <button class="flip-btn">↻</button>
                    <img src="assets/products/no-img-product.png" />
                    <h3>Kiwi</h3>
                  </div>
                  <div class="card-back">
                    <button class="flip-btn">↻</button>
                    <h4>Kiwi</h4>
                    <p>no description available</p>
                  </div>
                </div>
              </div>

              <div class="card">
                <div class="card-inner">
                  <div class="card-front">
                    <button class="flip-btn">↻</button>
                    <img src="assets/products/no-img-product.png" />
                    <h3>Kape Brusko</h3>
                  </div>
                  <div class="card-back">
                    <button class="flip-btn">↻</button>
                    <h4>Kape Brusko</h4>
                    <p>no description available</p>
                  </div>
                </div>
              </div>

              <div class="card">
                <div class="card-inner">
                  <div class="card-front">
                    <button class="flip-btn">↻</button>
                    <img src="assets/products/dark-choco.jpg" />
                    <h3>Dark Choco</h3>
                  </div>
                  <div class="card-back">
                    <button class="flip-btn">↻</button>
                    <h4>Dark Choco</h4>
                    <p>Bold and indulgent, this rich dark chocolate blend meets smooth milk tea.</p>
                  </div>
                </div>
              </div>

            </div><!-- end carousel-track -->
          </div><!-- end carousel-wrapper -->

          <div class="arrow arrow-right" onclick="moveCarousel(1)">
            <img src="assets/icons/icons8-arrow-24.png" alt="Next" />
          </div>
        </div>

        <div class="explore-btn">
          <button><a href="menu.php">View Menu</a></button>
        </div>
      </section>
    <section class="specials">
      <h2>What's New</h2>
      <p>
        Stay up to date with our latest offerings and exciting updates! Discover
        special products and limited-time offers.
      </p>
      <div class="special-list">
        <div class="special-card">
          <div class="box1">
            <img src="assets/pictures/pistachio.png" alt="a new milktea product" />
            <div class="box-text1">
                <h3>Dubai Pistachio Praf</h3>
                <p>
                  Indulge in the rich, nutty flavor of pistachios blended with our
                  signature milk tea. Layers of creamy milk tea, crunchy pistachio bits, and a hint of sweetness create a delightful symphony of textures and flavors in every sip.
                </p>
                <div><button><a href="menu.php">Explore</a></button></div>
            </div>
          </div>
        </div>
        <div class="special-card">
            <div class="box2">
              <div class="box-text2">
                <h3>Overload Strawberry Praf</h3>
                <p>
                  Experience a burst of fruity sweetness with our Overload Strawberry Praf. This refreshing blend combines the natural sweetness of ripe strawberries with our signature milk tea, creating a delightful balance of flavors. Topped with fresh strawberry pieces and a drizzle of strawberry syrup, it's a perfect treat for strawberry lovers. 
                </p>
                <div><button><a href="menu.php">Explore</a></button></div>
            </div>
              <img src="assets/pictures/strawberry.png " alt="a new milktea product" />
        </div>
        </div>
        <div class="special-card">
            <div class="box3">
              <img src="assets/pictures/kape-karavan.png" alt="a new milktea product" />
              <div class="box-text3">
                <h3>Kape Karavan</h3>
                <p>
                  A decadent journey in a cup: layers of bold iced coffee infused with salted caramel and vanilla, crowned with airy 
                  cream puff and velvety cheesecake. Finished with coffee jelly, crushed Oreo, 
                  and an optional extra shot for those who crave a stronger kick. 
                </p>
                <div><button><a href="menu.php">Explore</a></button></div>
            </div>
          
      </div>
    </section>

    <section class="how-to-order">
      <h2>How to Order</h2>
      <p>
        Ordering your favorite BigBrew drinks is as easy as 1-2-3! Just follow
        these simple steps:
      </p>
      <div class="order-steps">

        <div class="step">
          <div class="step-inner">
              <!-- CLOSE -->
              <div class="step-close">
               <div class="text">
                <h1>01</h1>
                <h3>Browse the Menu</h3>
               </div>
            
                <img class="toggle-btn" src="assets/icons/icons8-add-24.png" alt="" />
              </div>  
              <!-- OPEN -->
              <div class="step-open">
                <p>
                  Explore our menu and discover your new favorite drink. From classic milk teas to fruity blends, we have something for everyone.
                </p>
              </div>
          </div>
        </div>
        
        <div class="step">
          <div class="step-inner">
              <!-- CLOSE -->
              <div class="step-close">
               <div class="text">
                <h1>02</h1>
                <h3>Customize Your Drink</h3>
               </div>
            
                <img class="toggle-btn" src="assets/icons/icons8-add-24.png" alt="" />
              </div>  
              <!-- OPEN -->
              <div class="step-open">
                <p>
                  Customize your drink to your liking. Choose from a variety of flavors, milk options, and add-ons to create your perfect cup.
                </p>
              </div>
          </div>
        </div>
        <div class="step">
          <div class="step-inner">
              <!-- CLOSE -->
              <div class="step-close">
               <div class="text">
                <h1>03</h1>
                <h3>Place Your Order</h3>
               </div>
            
                <img class="toggle-btn" src="assets/icons/icons8-add-24.png" alt="" />
              </div>  
              <!-- OPEN --> 
              <div class="step-open">
                <p>
                  Place your order online for quick and easy pickup. No more waiting in line – just grab your drink and go!
                </p>
              </div>
          </div>
        </div>
          <div class="step">
            <div class="step-inner">
                <!-- CLOSE -->
                <div class="step-close">
                 <div class="text">
                  <h1>04</h1>
                  <h3>Enjoy Your Drink</h3>
                 </div>
              
                  <img class="toggle-btn" src="assets/icons/icons8-add-24.png" alt="" /> 
                </div> 
                <!-- OPEN -->
                <div class="step-open">
                  <p>
                    Savor every sip of your delicious BigBrew drink. Whether you're enjoying it on the go or taking a moment to relax, we hope it brings a smile to your face.
                  </p>
                </div>
            </div>
            
    </section>

  </body>

  <footer class="main-footer">
    <div class="footer-container">
      <div class="footer-brand">
        <div class="footer-logo">
          <img
            src="assets/logo/bb-maysan-logo-1.png"
            alt="Big Brew Maysan Logo"
          />
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
          <a
            href="https://www.facebook.com/p/BigBrew-Maysan-Rd-Valenzuela-100085267776562/"
            ><i class="fab fa-facebook"></i
          ></a>
          <a href="https://www.instagram.com/bigbrew.maysan/"
            ><i class="fab fa-instagram"></i
          ></a>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; 2026. BigBrew Maysan. All Rights Reserved.</p>
    </div>
  </footer>
  <script>
  window.IS_LOGGED_IN = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
</script>
  <script src="js/main.js"></script>
  <script src="js/global.js"></script>
</html>
