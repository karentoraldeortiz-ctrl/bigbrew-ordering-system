<?php
session_start();
include "db.php";

$message = "";

if(isset($_POST['login'])){

  $email = $_POST['email'];
  $password = $_POST['password'];

  // ✅ CHECK ADMIN FIRST
  if($email === 'admin@bigbrew.com' && $password === 'bigbrew2026') {
    $_SESSION['staff_logged_in'] = true;
    $_SESSION['staff_name'] = 'BigBrew Admin';
    header("Location: admin/dashboard.php");
    exit;
  }

    if($email === 'staff@bigbrew.com' && $password === 'staff2026') {
    $_SESSION['staff_logged_in'] = true;
    $_SESSION['staff_name'] = 'BigBrew Staff';
    header("Location: Staff/dashboard.php");
    exit;
  }


  // ✅ CHECK USER SA DATABASE
  $result = mysqli_query($conn,
  "SELECT * FROM users WHERE email='$email'"
);

if(mysqli_num_rows($result) > 0) {
  $user = mysqli_fetch_assoc($result);

  // Tapos i-verify yung password:
  if(!password_verify($password, $user['password'])) {
    $message = "Invalid email or password!";
  } else {

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['name'] = $user['full_name'];

    $user_id = $user['user_id'];

    // MERGE GUEST CART TO USER DATABASE CART
    if(isset($_SESSION['guest_cart']) && !empty($_SESSION['guest_cart'])) {

      // Check if user already has cart
      $cart_check = mysqli_query($conn, "SELECT cart_id FROM cart WHERE user_id = '$user_id'");

      if(mysqli_num_rows($cart_check) > 0) {
        $cart = mysqli_fetch_assoc($cart_check);
        $cart_id = $cart['cart_id'];
      } else {
        mysqli_query($conn, "INSERT INTO cart (user_id) VALUES ('$user_id')");
        $cart_id = mysqli_insert_id($conn);
      }

      foreach($_SESSION['guest_cart'] as $guestItem) {
        $product_id = intval($guestItem['product_id']);
        $size_id    = intval($guestItem['size_id']);
        $quantity   = intval($guestItem['quantity']);
        $unit_price = floatval($guestItem['unit_price']);
        $addons     = mysqli_real_escape_string($conn, $guestItem['addons']);

        // Check if same item already exists
        $item_check = mysqli_query($conn,
          "SELECT cart_item_id, quantity 
          FROM cart_items
          WHERE cart_id = '$cart_id'
          AND product_id = '$product_id'
          AND size_id = '$size_id'
          AND addons = '$addons'"
        );

        if(mysqli_num_rows($item_check) > 0) {
          $existing = mysqli_fetch_assoc($item_check);
          $new_qty = $existing['quantity'] + $quantity;

          mysqli_query($conn,
            "UPDATE cart_items
            SET quantity = '$new_qty'
            WHERE cart_item_id = '{$existing['cart_item_id']}'"
          );
        } else {
           // ✅ I-validate muna na valid yung size_id bago mag-insert
    $size_check = mysqli_query($conn, 
        "SELECT size_id FROM product_sizes WHERE size_id = '$size_id'"
    );
    
    if (mysqli_num_rows($size_check) > 0) {
        mysqli_query($conn,
            "INSERT INTO cart_items 
            (cart_id, product_id, size_id, addons, quantity, unit_price)
            VALUES
            ('$cart_id', '$product_id', '$size_id', '$addons', '$quantity', '$unit_price')"
        );
    }
    // Kung invalid yung size_id, i-skip na lang — hindi na siya ii-insert
}
      }

      unset($_SESSION['guest_cart']);

      header("Location: cart.php");
      exit;
    }

    header("Location: index.php");
    exit;
    } // close ng is_verified else block
  } else {
    $message = "Invalid email or password!";
  }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta
      name="description"
      content="Big Brew Maysan - Big in Taste, Bit in Price. "
    />
    <meta
      name="keywords"
      content="BigBrew Maysan, Maysan, Online Order, Milktea"
    />
    <meta name="author" content="Allyana Flores, Karen Ortiz" />
    <title>BigBrew | Login</title>
    <link rel="stylesheet" href="css/global.css" />
    <link rel="stylesheet" href="css/auth.css" />
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
  </head>
<body>
    <div class="login-box">
      <div class="login-content">
        <div class="header">
          <img src="assets/logo/logo-black.png" alt="" />
          <div><h2>Welcome back, Brew!</h2></div>
        </div>

        <form id="loginForm" method="POST" action="">
          <input type="hidden" name="login" value="1">

          <div class="input-text">
            <input type="email" placeholder="email" id="email" name="email"
              <?php if($message != "") echo 'class="error"'; ?> />
            <span class="error-text" id="emailError">Email is required.</span>
          </div>

          <div class="input-text">
            <div class="pass-wrapper">
              <input type="password" placeholder="password" id="password" name="password"
                <?php if($message != "") echo 'class="error"'; ?> />
              <span class="eye-toggle" data-target="password">
                <i class="fa fa-eye-slash"></i>
              </span>
            </div>
            <span class="error-text" id="passwordError">Password is required.</span>
            
          </div>
                    <?php if($message != ""): ?>
              <span class="error-text" id="serverError" style="display:block;">
                <?php echo htmlspecialchars($message); ?>
              </span>
            <?php endif; ?>

          <div class="login-btn">
            <button type="submit"><h4>Login</h4></button>
          </div>
        </form>

        <div class="forgot-pass"><a href="forgot_password.php">Forgot password?</a></div>
        <div class="divider"><span>or</span></div>
        <div class="create-acc-btn">
          <button>
            <a href="signup.php"><h4>Create Account</h4></a>
          </button>
        </div>
      </div>
    </div>

    <script src="js/auth.js"></script>
</body>
</html>
