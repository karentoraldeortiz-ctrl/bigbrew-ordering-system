<?php
session_start();
include "db.php";
include "mailer.php";

$message = "";

if(isset($_POST['signup'])){
  $name = $_POST['name'];
  $birthday = $_POST['birthday'];
  $phone = $_POST['phone'];
  $email = $_POST['email'];
  $password = $_POST['password'];
  $confirm_pass = $_POST['confirm_password'];

  if(!preg_match('/^(09|\+639)\d{9}$/', $phone)) {
      $message = "Invalid phone number format (e.g. 09XX-XXX-XXXX)";
  }

  $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

  if(mysqli_num_rows($check) > 0){
    $message = "Email already exists!";
  }
  else if(strlen($password) < 6){
      $message = "Weak password!";
  }
  else {
      // Generate verification token
      $verify_token = bin2hex(random_bytes(32));

      $hashed = password_hash($password, PASSWORD_DEFAULT);
      $result = mysqli_query($conn,
        "INSERT INTO users(full_name, email, password, phone_num, birthday, is_verified, verify_token)
        VALUES('$name', '$email', '$hashed', '$phone', '$birthday', 0, '$verify_token')"
      );

      if(!$result){
          die("Database Error: " . mysqli_error($conn));
      } else {
          // Build verification link
          $verify_link = (isset($_SERVER['HTTPS']) ? 'https' : 'http') .
            '://' . $_SERVER['HTTP_HOST'] .
            '/bigbrew-ordering-system/verify_email.php?token=' . $verify_token;

          // Email body
          $body = "
<!DOCTYPE html>
<html>
<head>
  <meta charset='UTF-8'>
</head>
<body style='margin:0; padding:40px 16px; background-color:#FFF8E4; font-family:Arial, sans-serif;'>

  <table width='100%' cellpadding='0' cellspacing='0'>
    <tr>
      <td align='center'>
        <table width='520' cellpadding='0' cellspacing='0'
          style='
            background-color:#ffffff;
            border-radius:22px;
            overflow:hidden;
            box-shadow:0 8px 28px rgba(45,30,23,0.10);
          '>

          <!-- TOP STRIP -->
          <tr>
            <td style='background:#2D1E17; height:10px;'></td>
          </tr>

          <!-- HEADER -->
          <tr>
            <td align='center' style='padding:42px 40px 20px;'>
              <div style='font-size:34px; font-weight:800; letter-spacing:1px; color:#2D1E17; line-height:1;'>
                BIGBREW
              </div>
              <div style='font-size:12px; letter-spacing:5px; color:#B86D25; margin-top:8px; text-transform:uppercase; font-weight:700;'>
                MAYSAN
              </div>
              <div style='width:54px; height:4px; border-radius:999px; background:#B86D25; margin:22px auto 0;'></div>
            </td>
          </tr>

          <!-- TITLE -->
          <tr>
            <td style='padding:0 42px;'>
              <h2 style='color:#2D1E17; font-size:28px; margin:0 0 16px; text-align:center; font-weight:800;'>
                Verify Your Email
              </h2>
              <p style='color:#5F5148; font-size:15px; line-height:1.8; margin:0 0 30px; text-align:center;'>
                Welcome to BigBrew Maysan! Please verify your email address
                by clicking the button below to activate your account.
              </p>
            </td>
          </tr>

          <!-- BUTTON -->
          <tr>
            <td align='center' style='padding:0 42px 34px;'>
              <a href='$verify_link'
                 style='
                  display:inline-block;
                  background-color:#B86D25;
                  color:#ffffff;
                  padding:15px 38px;
                  border-radius:999px;
                  text-decoration:none;
                  font-size:15px;
                  font-weight:700;
                  letter-spacing:0.5px;
                 '>
                Verify My Email
              </a>
            </td>
          </tr>

          <!-- INFO BOX -->
          <tr>
            <td style='padding:0 42px 28px;'>
              <div style='background:#FFF8E4; border-radius:16px; padding:18px 20px;'>
                <p style='margin:0; color:#5F5148; font-size:13px; line-height:1.7; text-align:center;'>
                  If you did not create an account, you can safely ignore this email.
                  <br><br>
                  This verification link will expire after
                  <strong style='color:#2D1E17;'>24 hours</strong>.
                </p>
              </div>
            </td>
          </tr>

          <!-- FOOTER -->
          <tr>
            <td align='center' style='padding:0 42px 34px;'>
              <p style='color:#8A7B70; font-size:12px; margin:0; line-height:1.6;'>
                &copy; 2026 BigBrew Maysan. All rights reserved.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
";

          $sent = sendMail($email, "Verify your BigBrew Email", $body);

          if($sent) {
              $message = "verify_sent";
          } else {
              $message = "Account created but we could not send the verification email. Please contact support.";
          }
      }
  }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Big Brew Maysan - Big in Taste, Bit in Price." />
    <meta name="keywords" content="BigBrew Maysan, Maysan, Online Order, Milktea" />
    <meta name="author" content="Allyana Flores, Karen Ortiz" />
    <title>BigBrew | Signup</title>
    <link rel="stylesheet" href="css/global.css" />
    <link rel="stylesheet" href="css/auth.css" />
    <link rel="shortcut icon" href="assets/logo/logo-black.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  </head>
  <body>
    <div class="signup-box">
      <div class="signup-content">
        <div class="header">
          <img src="assets/logo/logo-black.png" alt="BigBrew Logo" />
        </div>

        <?php if($message != ""): ?>
        <script>
          document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('errorModal').style.display = 'flex';

            <?php if($message === "verify_sent"): ?>
              document.getElementById('authModalIcon').textContent = "✅";
              document.getElementById('authModalTitle').textContent = "Check Your Email!";
              document.getElementById('authModalMsg').textContent = "We sent a verification link to your email. Please verify your account before logging in.";
              document.querySelector('.auth-modal-actions').innerHTML = `
                <a href="login.php" class="auth-btn-primary">Go to Login</a>
              `;

            <?php elseif($message === "Email already exists!"): ?>
              document.getElementById('authModalIcon').textContent = "✉️";
              document.getElementById('authModalTitle').textContent = "Email Already Exists";
              document.getElementById('authModalMsg').textContent = "An account with this email already exists.";
              document.querySelector('.auth-modal-actions').innerHTML = `
                <button class="auth-btn-secondary" onclick="closeAuthModal()">Try Another Email</button>
                <a href="login.php" class="auth-btn-primary">Go to Login</a>
              `;

            <?php else: ?>
              document.getElementById('authModalIcon').textContent = "⚠️";
              document.getElementById('authModalTitle').textContent = "Something Went Wrong";
              document.getElementById('authModalMsg').textContent = "<?php echo addslashes($message); ?>";
              document.querySelector('.auth-modal-actions').innerHTML = `
                <button class="auth-btn-primary" onclick="closeAuthModal()">Try Again</button>
              `;
            <?php endif; ?>
          });
        </script>
        <?php endif; ?>

        <form id="signForm" method="POST" action="">
          <div class="first">
            <div class="outer-prac1">
              <div class="info-grp">
                <label>Name*</label><br />
                <input type="text" id="name" name="name" minlength="3" />
                <span class="error-text" id="nameError">This field is required.</span>
              </div>
            </div>

            <div class="outer-prac1">
              <div class="info-grp">
                <label>Birthday</label><br />
                <input type="date" name="birthday" />
              </div>
            </div>

            <div class="prac partner">
              <label>Phone No.*</label><br />
              <input type="tel" id="phone" class="partner-no" name="phone" placeholder="09XX-XXX-XXXX" />
              <span class="error-text" id="phoneError">Valid PH number required.</span>
            </div>
          </div>

          <div class="last">
            <div class="info-grp">
              <label>Email Address*</label><br />
              <input type="email" id="email" name="email" />
              <span class="error-text" id="emailError">This field is required.</span>
            </div>

            <div class="info-grp">
              <label>Password*</label><br />
              <div class="pass-wrapper">
                <input type="password" name="password" id="password" minlength="6" />
                <span class="eye-toggle" data-target="password">
                  <i class="fa fa-eye-slash"></i>
                </span>
              </div>
              <span class="error-text" id="passwordError">This field is required.</span>
            </div>

            <div class="info-grp">
              <label>Confirm Password*</label><br />
              <div class="pass-wrapper">
                <input type="password" id="confirmPassword" name="confirm_password" />
                <span class="eye-toggle" data-target="confirmPassword">
                  <i class="fa fa-eye-slash"></i>
                </span>
              </div>
              <span class="error-text" id="confirmError">This field is required.</span>
            </div>
          </div>

          <div class="signup-btn">
            <button type="submit" name="signup"><h4>Sign up</h4></button>
          </div>
        </form>

        <div class="hasAccount">
          Already has account? <a href="login.php">Login here.</a>
        </div>
        <hr />
        <p class="terms">
          By proceeding you agree to our
          <a href="terms.php">Terms and Conditions</a> and confirm you have
          read and understand our <a href="privacy.php">Privacy Policy</a>.
        </p>

        <!-- Modal -->
        <div id="errorModal" class="auth-modal-overlay" style="display:none;">
          <div class="auth-modal-card">
            <div class="auth-modal-icon" id="authModalIcon">✉️</div>
            <h3 id="authModalTitle">Something Went Wrong</h3>
            <p id="authModalMsg" style="color:#5F5148; font-size:14px; text-align:center; margin: 0 0 16px;"></p>
            <div class="auth-modal-actions">
              <button class="auth-btn-primary" onclick="closeAuthModal()">Try Again</button>
            </div>
          </div>
        </div>

      </div>
    </div>

    <script src="js/auth.js"></script>
  </body>
</html>