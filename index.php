<?php
session_start();
 if(!isset($_SESSION['user_id']) ){
 header("Location: login.php");
 exit;
}
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
          <a href="index.html">
            <img src="assets/logo/bb-maysan-logo-1.png" alt="" />
          </a>
        </div>
        <div class="nav-links" id="navlinks">
          <ul>
            <li><a href="#">Home</a></li>
            <li><a href="menu.php">Our Menu</a></li>
            <li><a href="about.html">About Us</a></li>
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

    <div class="hero1" class="hero">
      <div class="hero1-text">
        <h1>Sip Your Favorites Without the Wait.</h1>
        <p>
          Discover your favorite BigBrew drinks and order online for quick and
          easy pickup.
        </p>
        <button><a href="menu.html">Order Now</a></button>
      </div>
      <div class="hero1-img">
        <img src="assets/pictures/index-hero.png" alt="BigBrew Hero Image" />
      </div>
    </div>

    <section class="best-seller">
      <h2>Famous Five</h2>
      <p>
        Discover our most popular drinks, made with high-quality tea leaves,
        vibrant fruit flavors, rich creamy textures, and delightful toppings in
        every sip.
      </p>
      <div class="products">
        <div class="arrow">
          <img src="assets/icons/icons8-arrow-24-left.png" alt="" />
        </div>

        <div class="card">
          <div class="card-inner">
            <!-- FRONT -->
            <div class="card-front">
              <button class="flip-btn">↻</button>
              <img src="assets/products/dark-choco.jpg" />
              <h3>Dark Choco</h3>
            </div>

            <!-- BACK -->
            <div class="card-back">
              <button class="flip-btn">↻</button>
              <h4>Dark Choco</h4>
              <p>
                Bold and indulgent, this rich dark chocolate blend meets smooth
                milk tea for a decadent sip in every cup.
              </p>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-inner">
            <!-- FRONT -->
            <div class="card-front">
              <button class="flip-btn">↻</button>
              <img src="assets/products/dark-choco.jpg" />
              <h3>Dark Choco</h3>
            </div>

            <!-- BACK -->
            <div class="card-back">
              <button class="flip-btn">↻</button>
              <h4>Dark Choco</h4>
              <p>
                Bold and indulgent, this rich dark chocolate blend meets smooth
                milk tea for a decadent sip in every cup.
              </p>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-inner">
            <!-- FRONT -->
            <div class="card-front">
              <button class="flip-btn">↻</button>
              <img src="assets/products/dark-choco.jpg" />
              <h3>Dark Choco</h3>
            </div>

            <!-- BACK -->
            <div class="card-back">
              <button class="flip-btn">↻</button>
              <h4>Dark Choco</h4>
              <p>
                Bold and indulgent, this rich dark chocolate blend meets smooth
                milk tea for a decadent sip in every cup.
              </p>
            </div>
          </div>
        </div>

        <div class="arrow">
          <img src="assets/icons/icons8-arrow-24.png" alt="" />
        </div>
      </div>
      <div class="explore-btn">
        <button>
          <a href="menu.html">Explore the Menu</a>
        </button>
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
              <div><button><a href="menu.html">Explore</a></button></div>
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
              <div><button><a href="menu.html">Explore</a></button></div>
          </div>
            <img src="assets/pictures/strawberry.png " alt="a new milktea product" />
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

    <script src="global.js"></script>
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
  <script src="js/main.js"></script>
  <script src="js/global.js"></script>
</html>
