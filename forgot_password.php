<?php
session_start();
include "db.php";
include "mailer.php";

$message = "";
$success = false;

if(isset($_POST['submit'])) {
    $email = $_POST['email'];

    // Check if email exists in users table
    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

    if(mysqli_num_rows($check) > 0) {
        // Generate unique token
        $token = bin2hex(random_bytes(32));

        // Delete existing token for same email (if any)
        mysqli_query($conn, "DELETE FROM password_resets WHERE email='$email'");

        // Save new token
        mysqli_query($conn, "INSERT INTO password_resets (email, token) VALUES ('$email', '$token')");

        // Build reset link (works on both localhost and live hosting)
        $reset_link = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . 
              '://' . $_SERVER['HTTP_HOST'] . 
              '/bigbrew-ordering-system/reset_password.php?token=' . $token;

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

              <div style='
                font-size:34px;
                font-weight:800;
                letter-spacing:1px;
                color:#2D1E17;
                line-height:1;
              '>
                BIGBREW
              </div>

              <div style='
                font-size:12px;
                letter-spacing:5px;
                color:#B86D25;
                margin-top:8px;
                text-transform:uppercase;
                font-weight:700;
              '>
                MAYSAN
              </div>

            

            </td>
          </tr>

          <!-- TITLE -->
          <tr>
            <td style='padding:0 42px;'>

              <h2 style='
                color:#2D1E17;
                font-size:28px;
                margin:0 0 16px;
                text-align:center;
                font-weight:800;
              '>
                Password Reset Request
              </h2>

              <p style='
                color:#5F5148;
                font-size:15px;
                line-height:1.8;
                margin:0 0 30px;
                text-align:center;
              '>
                We received a request to reset the password for your BigBrew account.
                Click the button below to proceed.
              </p>

            </td>
          </tr>

          <!-- BUTTON -->
          <tr>
            <td align='center' style='padding:0 42px 34px;'>

              <a href='$reset_link'
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
                Reset My Password
              </a>

            </td>
          </tr>

          <!-- INFO BOX -->
          <tr>
            <td style='padding:0 42px 28px;'>

              <div style='
                background:#FFF8E4;
                border-radius:16px;
                padding:18px 20px;
              '>

                <p style='
                  margin:0;
                  color:#5F5148;
                  font-size:13px;
                  line-height:1.7;
                  text-align:center;
                '>
                  If you did not request this, you can safely ignore this email.
                  <br><br>
                  This reset link will expire after
                  <strong style='color:#2D1E17;'>1 hour</strong>.
                </p>

              </div>

            </td>
          </tr>

          <!-- FOOTER -->
          <tr>
            <td align='center' style='padding:0 42px 34px;'>

              <p style='
                color:#8A7B70;
                font-size:12px;
                margin:0;
                line-height:1.6;
              '>
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

        $sent = sendMail($email, "Reset your BigBrew Password", $body);

        if($sent) {
            $success = true;
            $message = "Reset link sent! Check your email.";
        } else {
            $message = "There was an error sending the email. Please try again.";
        }
    } else {
        $message = "No account is registered with that email address.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>BigBrew | Forgot Password</title>
    <link rel="stylesheet" href="css/global.css"/>
    <link rel="stylesheet" href="css/auth.css"/>
    <link rel="shortcut icon" href="assets/logo/logo-black.png" type="image/x-icon"/>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
</head>
<body>
    <div class="login-box">
        <div class="login-content">
            <div class="header">
                <img src="assets/logo/logo-black.png" alt="BigBrew Logo"/>
                <div><h2>Forgot Password</h2></div>
            </div>

            <?php if($message != ""): ?>
                <span class="error-text" style="display:block; <?php echo $success ? 'color:green;' : ''; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </span>
            <?php endif; ?>

            <?php if(!$success): ?>
            <form method="POST" action="">
                <div class="input-text">
                    <input type="email" placeholder="Enter your email" name="email" required/>
                </div>
                <div class="login-btn">
                    <button type="submit" name="submit"><h4>Send Reset Link</h4></button>
                </div>
            </form>
            <?php endif; ?>

            <div class="forgot-pass">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>