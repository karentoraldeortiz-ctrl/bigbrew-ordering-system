<?php
session_start();
include "db.php";
include "mailer.php";

$message = "";
$valid_token = false;
$token = $_GET['token'] ?? '';

// I-validate yung token — expire after 1 hour
if($token != "") {
    $check = mysqli_query($conn, 
        "SELECT * FROM password_resets 
         WHERE token='$token' 
         AND created_at >= NOW() - INTERVAL 1 HOUR"
    );

    if(mysqli_num_rows($check) > 0) {
        $valid_token = true;
        $reset_data = mysqli_fetch_assoc($check);
    } else {
        $message = "Invalid or expired reset link.";
    }
}

if(isset($_POST['submit']) && $valid_token) {
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if($new_password !== $confirm_password) {
        $message = "Passwords do not match!";
    } elseif(strlen($new_password) < 6) {
        $message = "Password must be at least 6 characters.";
    } else {
        $email = $reset_data['email'];

        // I-update yung password
        mysqli_query($conn, 
            "UPDATE users SET password='$new_password' WHERE email='$email'"
        );

        // I-delete na yung token
        mysqli_query($conn, 
            "DELETE FROM password_resets WHERE token='$token'"
        );

        $message = "Password reset successful! You can now login.";
        $valid_token = false;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>BigBrew | Reset Password</title>
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
                <img src="assets/logo/logo-black.png" alt=""/>
                <div><h2>Reset Password</h2></div>
            </div>

            <?php if($message != ""): ?>
                <span class="error-text" style="display:block;">
                    <?php echo htmlspecialchars($message); ?>
                </span>
                <?php if(strpos($message, 'successful') !== false): ?>
                    <div class="forgot-pass">
                        <a href="login.php">Back to Login</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if($valid_token): ?>
            <form method="POST" action="">
                <div class="input-text">
                    <div class="pass-wrapper">
                        <input type="password" placeholder="New Password" name="password" id="password" required/>
                        <span class="eye-toggle" data-target="password">
                            <i class="fa fa-eye-slash"></i>
                        </span>
                    </div>
                </div>
                <div class="input-text">
                    <div class="pass-wrapper">
                        <input type="password" placeholder="Confirm Password" name="confirm_password" id="confirm_password" required/>
                        <span class="eye-toggle" data-target="confirm_password">
                            <i class="fa fa-eye-slash"></i>
                        </span>
                    </div>
                </div>
                <div class="login-btn">
                    <button type="submit" name="submit"><h4>Reset Password</h4></button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <script src="js/auth.js"></script>
</body>
</html>