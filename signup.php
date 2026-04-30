<?php
session_start();

// if(isset($_SESSION['user_id'])){
//   header("Location: index.php");
//   exit;
// }

include "db.php";
$message = "";

if(isset($_POST['signup'])){
var_dump($_POST);
$name = $_POST['name'];
$birthday = $_POST['birthday'];
$phone = $_POST['phone'];
$email = $_POST['email'];
$password = $_POST['password'];
$confirm_pass = $_POST['confirm_password'];


$check = mysqli_query($conn,"SELECT * FROM users WHERE email='$email'");

if(mysqli_num_rows($check) > 0){
$message = "Email already exists!";
}
else if(strlen($password) < 6){
    $message = "Weak password!";
}
// else if(!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&]).{8,}$/',$password)){
// $message = "Weak password!";
// }
else{
//   echo "REACHED INSERT PART";
// exit;
    $result = mysqli_query($conn,
    "INSERT INTO users(full_name, email, password, phone_num, birthday)
    VALUES('$name', '$email', '$password', '$phone', '$birthday')");

    if(!$result){
        die("Database Error: " . mysqli_error($conn));
    } else {
        // $message = "Registered successfully!";
        header("Location: login.php");
        exit;
    }

}
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Big Brew Maysan - Big in Taste, Big in Price." />
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
          <p><?php echo $message; ?></p>
        <?php endif; ?> -

        <form id="signForm" method="POST" action="">

          <div class="first">
            <div class="outer-prac1">
              <div class="info-grp">
                <label>Name*</label><br />
                <input
                  type="text"
                  id="name"
                  name="name"
                  minlength="3"
                />
                <span class="error-text" id="nameError">This field is required.</span>
              </div>
            </div>

            <div class="outer-prac1">
              <div class="info-grp">
                <label>Birthday</label><br />
                <input type="date" name="birthday"
                />
              </div>
            </div>

            <div class="prac partner">
              <label>Phone No.</label><br />
              <input
                type="text"
                class="partner-no"
                name="phone"
              />
            </div>
          </div>

          <div class="last">
            <div class="info-grp">
              <label>Email Address*</label><br />
              <input
                type="email"
                id="email"
                name="email"
              />
              <span class="error-text" id="emailError">This field is required.</span>
            </div>

            <div class="info-grp">
              <label>Password*</label><br />
              <input type="password" name="password" id="password" minlength="6" />
              <span class="error-text" id="passwordError">This field is required.</span>
            </div>

            <div class="info-grp">
              <label>Confirm Password*</label><br />
              <input type="password" id="confirmPassword" name="confirm_password" />
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
        <p>
          By proceeding you agree to our
          <a href="terms.html">Terms and Conditions</a> and confirm you have
          read and understand our <a href="privacy.html">Privacy Policy</a>.
        </p>
      </div>
    </div>

    <!-- <script src="js/auth.js"></script> -->
  </body>
</html>