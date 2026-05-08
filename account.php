<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];

$query = mysqli_query($conn,
  "SELECT full_name, email, phone_num, birthday
  FROM users
  WHERE user_id = '$user_id'"
);

$user = mysqli_fetch_assoc($query)
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
    <title>BigBrew | Account</title>
    <link rel="stylesheet" href="css/global.css" />
    <!-- <link rel="stylesheet" href="account.css"> -->
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
            <li><a href="index.php">Home</a></li>
            <li><a href="menu.php">Our Menu</a></li>
            <li><a href="about.html">About Us</a></li>
            <li>
              <a href="cart.php">
                <img src="assets/icons/icons8-cart-24.png" alt=""
              /></a>
            </li>
            <li>
              <a href="account.php">
                <img src="assets/icons/icons8-profile-24.png" alt=""
              /></a>
            </li>
          </ul>
        </div>

        <div class="hamburger" id="hamburger">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </nav>
    </header>

    <section class="account-hero">
      <header>
        <h1>Account</h1>
      </header>
      <div class="account-content">
        <div class="account-container1">
          <div class="acc-info">
            <div class="acc-info-header">
              <i class="fa-solid fa-user"></i>
              <div>
                <h2 id="user-name">
                  <?php echo htmlspecialchars($user['full_name']); ?>
                </h2>
                <p class="edit-profile">
                  Edit Profile <i class="fa-solid fa-pen"></i>
                </p>
              </div>
            </div>
            <div class="acc-info-body">
              <div class="info-body-list">
                <div>
                  <i class="fa-solid fa-envelope"></i>
                  <span id="user-email">
                    <?php echo htmlspecialchars($user['email']); ?>
                  </span>
                </div>
                <div>
                  <i class="fa-solid fa-phone"></i>
                  <span id="user-phone">
                    <?php echo htmlspecialchars($user['phone_num']); ?>
                  </span>
                </div>
                <div>
                  <i class="fa-solid fa-calendar"></i>
                  <span id="user-birthday">
                    <?php echo htmlspecialchars($user['birthday']); ?>
                  </span>
                </div>
              </div>
            </div>
            <div class="acc-info-footer">
              <a href="logout.php">
                <button class="logout-btn">Logout</button>
              </a>
            </div>
          </div>
        </div>
        <div class="account-container2">
          <h2>Order History</h2>
          <div id="order-history-list">
            <p class="loading-msg">Loading your orders...</p>
          </div>
        </div>
      </div>

      <!-- EDIT PROFILE POP UP WINDOW  -->
      <div id="editProfileModal" class="modal-overlay" style="display: none">
        <div class="modal-card profile-modal">
          <span class="close-profile-modal">&times;</span>
          <h2>Edit Profile</h2>

          <form id="editProfileForm">
            <div class="input-group">
              <label>Name</label>
              <input type="text" id="editName" required />
            </div>

            <div class="input-group">
              <label>Email</label>
              <input type="email" id="editEmail" required />
            </div>

            <div class="input-group">
              <label>Phone Number</label>
              <input
                type="text"
                id="editPhone"
                minlength="11"
                maxlength="11"
                pattern="[0-9]{11}"
                required
              />
            </div>

            <div class="input-group">
              <label>Birthday</label>
              <input type="date" id="editBirthday" required />
            </div>

            <div class="profile-modal-footer">
              <button type="button" class="btn-cancel" id="closeBtn">
                Cancel
              </button>
              <button type="submit" class="btn-save">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
      <div id="receiptModal" class="modal-overlay" style="display: none">
        <div class="modal-card receipt-card">
          <span
            class="close-modal"
            id="closeReceipt"
            style="
              position: absolute;
              right: 20px;
              top: 15px;
              cursor: pointer;
              font-size: 24px;
            "
            >&times;</span
          >
          <div id="receipt-content-area">
            <!-- Dito papasok ang HTML galing sa JS -->
          </div>
        </div>
      </div>

      <!-- REVIEW MODAL -->
      <div id="reviewModal" class="modal-overlay" style="display:none;">
        <div class="modal-card" style="max-width:420px;width:90%;padding:2rem;border-radius:20px;position:relative;">
          <span onclick="closeReviewModal()" style="position:absolute;right:20px;top:15px;cursor:pointer;font-size:24px;">&times;</span>
          <h3 style="margin-bottom:1rem;color:var(--dark-color);">Leave a Review</h3>
          <input type="hidden" id="review-order-id" />
          <div style="margin-bottom:1rem;">
            <label style="font-size:0.9rem;font-weight:500;display:block;margin-bottom:8px;">Rating</label>
            <div id="star-container" style="display:flex;gap:8px;font-size:2rem;cursor:pointer;">
              <span class="rev-star" data-value="1" style="color:#ccc;">★</span>
              <span class="rev-star" data-value="2" style="color:#ccc;">★</span>
              <span class="rev-star" data-value="3" style="color:#ccc;">★</span>
              <span class="rev-star" data-value="4" style="color:#ccc;">★</span>
              <span class="rev-star" data-value="5" style="color:#ccc;">★</span>
            </div>
          </div>
          <div style="margin-bottom:1.5rem;">
            <label style="font-size:0.9rem;font-weight:500;display:block;margin-bottom:8px;">Comment</label>
            <textarea id="review-comment" rows="4" placeholder="How was your order?" style="width:100%;border:1px solid #eee;border-radius:10px;padding:10px;font-family:inherit;resize:none;box-sizing:border-box;"></textarea>
          </div>
          <button onclick="submitReview()" style="width:100%;background:var(--pop-color);color:white;border:none;padding:12px;border-radius:12px;font-weight:600;font-size:1rem;cursor:pointer;">Submit Review</button>
        </div>
      </div>
    </section>

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
              <li><a href="terms.html">Terms</a></li>
              <li><a href="privacy.html">Privacy</a></li>
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
        <p>© 2026. BigBrew Maysan. All Rights Reserved.</p>
      </div>
    </footer>
    <script src="js/account.js"></script>
    <script src="js/global.js"></script>
  </body>
</html>
