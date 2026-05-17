<?php
session_start();
include "db.php";

$message = "";
$success = false;
$token = $_GET['token'] ?? '';

if ($token !== "") {
    $check = mysqli_prepare($conn,
        "SELECT id FROM users 
         WHERE verify_token = ? 
         AND is_verified = 0
         AND created_at >= NOW() - INTERVAL 24 HOUR"
    );
    mysqli_stmt_bind_param($check, "s", $token);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {
        $update = mysqli_prepare($conn,
            "UPDATE users 
             SET is_verified = 1, verify_token = NULL 
             WHERE verify_token = ?"
        );
        mysqli_stmt_bind_param($update, "s", $token);
        mysqli_stmt_execute($update);

        $success = true;
        $message = "Your email has been verified! You can now log in.";
    } else {
        $message = "This verification link is invalid or has already expired.";
    }
} else {
    $message = "No verification token provided.";
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>BigBrew | Email Verification</title>
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
                <div><h2>Email Verification</h2></div>
            </div>

            <span class="error-text" style="display:block; text-align:center; <?php echo $success ? 'color:green;' : ''; ?>">
                <?php echo htmlspecialchars($message); ?>
            </span>

            <div class="forgot-pass" style="text-align:center; margin-top:16px;">
                <?php if ($success): ?>
                    <a href="login.php">Proceed to Login</a>
                <?php else: ?>
                    <a href="signup.php">Back to Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>