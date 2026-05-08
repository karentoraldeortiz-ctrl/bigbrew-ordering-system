<?php
session_start();
include "db.php";

$reviews_q = mysqli_query($conn,
    "SELECT r.rating, r.comment, r.created_at, u.full_name
     FROM reviews r
     JOIN users u ON r.user_id = u.user_id
     ORDER BY r.created_at DESC"
);

$reviews = [];
while ($row = mysqli_fetch_assoc($reviews_q)) {
    $reviews[] = $row;
}

$can_review = false;
$already_reviewed = false;
$is_logged_in = isset($_SESSION['user_id']);

if ($is_logged_in) {
    $user_id = (int) $_SESSION['user_id'];
    $order_check = mysqli_query($conn,
        "SELECT order_id FROM orders WHERE user_id = '$user_id' LIMIT 1"
    );
    if (mysqli_num_rows($order_check) > 0) {
        $rev_check = mysqli_query($conn,
            "SELECT review_id FROM reviews WHERE user_id = '$user_id'"
        );
        $already_reviewed = mysqli_num_rows($rev_check) > 0;
        $can_review = !$already_reviewed;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BigBrew | Reviews</title>
    <link rel="stylesheet" href="css/global.css" />
    <link rel="stylesheet" href="css/main.css" />
    <link rel="shortcut icon" href="assets/logo/logo-black.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="navlogo">
                <a href="index.php"><img src="assets/logo/bb-maysan-logo-1.png" alt="" /></a>
            </div>
            <div class="nav-links" id="navlinks">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="menu.php">Our Menu</a></li>
                    <li><a href="about.html">About Us</a></li>
                    <li><a href="cart.php"><img src="assets/icons/icons8-cart-24.png" alt="" /></a></li>
                    <li><a href="account.php"><img src="assets/icons/icons8-profile-24.png" alt="" /></a></li>
                </ul>
            </div>
            <div class="hamburger" id="hamburger">
                <span></span><span></span><span></span>
            </div>
        </nav>
    </header>

    <main class="review-page">
        <div class="rev-header">
            <h1>Customer Feedback</h1>
            <p>We value every sip and every story. Share your thoughts and be part of our growing BigBrew family!</p>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="no-review"><p>No reviews yet. Be the first to review!</p></div>
        <?php else: ?>
            <section class="review-content">
                <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="stars">
                        <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                    </div>
                    <p class="revcard-text"><?php echo htmlspecialchars($review['comment']); ?></p>
                    <hr>
                    <small><?php echo htmlspecialchars($review['full_name']); ?> · <?php echo date('M j, Y', strtotime($review['created_at'])); ?></small>
                </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <div class="write-box">
            <div class="write-review">
                <h3>Love BigBrew? Share Your Experience!</h3>
                <p>Your feedback helps us serve you better and helps others find great drinks.</p>
                <?php if (!$is_logged_in): ?>
                    <button onclick="window.location.href='login.php'">Login to Review</button>
                <?php elseif ($already_reviewed): ?>
                    <button disabled style="opacity:0.5;cursor:not-allowed;">✅ Already Reviewed</button>
                <?php elseif (!$can_review): ?>
                    <button disabled style="opacity:0.5;cursor:not-allowed;">Order first to leave a review</button>
                <?php else: ?>
                    <button id="openBtn">Write a Review</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="modal" id="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3>Enjoyed our service? Let us know!</h3>
                <p>Your feedback helps us improve our service.</p>
                <div class="star-rating-large">
                    <span class="star" data-value="1">★</span>
                    <span class="star" data-value="2">★</span>
                    <span class="star" data-value="3">★</span>
                    <span class="star" data-value="4">★</span>
                    <span class="star" data-value="5">★</span>
                </div>
                <p id="error-msg"></p>
                <input type="text" id="reviewInput" placeholder="Write your feedback here." /><br/>
                <button id="submitBtn">Submit</button>
            </div>
        </div>
    </main>

    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-brand">
                <div class="footer-logo">
                    <img src="assets/logo/bb-maysan-logo-1.png" alt="Big Brew Maysan Logo" />
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
                        <li><a href="index.php">Home</a></li>
                        <li><a href="menu.php">Menu</a></li>
                        <li><a href="about.html">About</a></li>
                    </ul>
                    <ul>
                        <li><a href="reviews.php">Reviews</a></li>
                        <li><a href="terms.html">Terms & Conditions</a></li>
                        <li><a href="privacy.html">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-column">
                <h3>Follow us on</h3>
                <div class="social-icons">
                    <a href="https://www.facebook.com/p/BigBrew-Maysan-Rd-Valenzuela-100085267776562/"><i class="fab fa-facebook"></i></a>
                    <a href="https://www.instagram.com/bigbrew.maysan/"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026. BigBrew Maysan. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="js/global.js"></script>
    <script src="js/review.js"></script>
</body>
</html>