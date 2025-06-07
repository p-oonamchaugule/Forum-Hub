<?php
session_start();  // Start the session

if (isset($_SESSION['user_id'])) {
  header("Location: ./index.php"); // Redirect to the index page
  exit();
}

require("config.php");  // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
  $password = $_POST['password'];

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_message = "Invalid email format.";
  } elseif (empty($password)) {
    $error_message = "Password cannot be empty.";
  } else {
    if ($stmt = $conn->prepare("SELECT * FROM users WHERE email = ?")) {
      $stmt->bind_param("s", $email);
      $stmt->execute();

      $result = $stmt->get_result();

      if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $row['password'])) {
          // Successful login, retrieve user_id
          $user_id = $row['user_id'];

          // Set session for user
          $_SESSION['user_id'] = $user_id;

          // Redirect to post creation page, passing the user_id as a query parameter
          header("Location: Postcreation.php?user_id=" . $user_id);
          exit();
        } else {
          $error_message = "Incorrect password. Please try again.";
        }
      } else {
        $error_message = "No account found with this email. Please check if the email is correct or sign up.";
      }
      $stmt->close();
    } else {
      $error_message = "Database error. Please try again later.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Page</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- External CSS -->
  <!-- <link rel="stylesheet" href="css/homepage.css"> -->
  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">
</head>

<body>
  <header id="header" class="header d-flex align-items-center fixed-top" style='background:linear-gradient(45deg, #6243E4 0%, color-mix(in srgb, #823ABE, transparent 10%) 100%), url("../assets/img/hero-bg.jpg") center center no-repeat;'>
    <div class="container-fluid container-xl position-relative d-flex align-items-center">
      <div class="container-fluid container-xl position-relative d-flex align-items-center">

        <a href="index.html" class="logo d-flex align-items-center me-auto">
          <img src="assets/img/forum_fusion_logo-removebg-preview.png" alt="">
          <h1 class="sitename d-none d-sm-inline-block">ForumHub</h1>
        </a>

        <nav id="navmenu" class="navmenu d-flex justify-content-center align-items-center">
          <ul>
            <li><a href="index.php#hero">Home</a></li>
            <li><a href="index.php#about">About</a></li>
            <li><a href="index.php#ALLPost">Posts</a></li>
            <li><a href="index.php#feedback">Feedback</a></li>
            <?php
            if (!isset($_SESSION['user_id'])) {
              echo '<li><a class="" id="button" href="signup.php">Sign Up</a></li>';
            }
            ?>

          </ul>

          <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>


      </div>
  </header>
  <div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card p-4 shadow-lg" style="width: 100%; max-width: 400px;">
      <h2 class="text-center mb-4">Login</h2>
      <form method="POST" action="">
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Login</button>
      </form>
      <p class="text-center mt-3">Don't have an account? <a href="signup.php">Sign up here</a>
        <br><a href="./admin/admin-login.php">admin</a>
      </p>

      <?php
      if (isset($error_message)) {
        echo '<div class="text-danger text-center mt-3">' . $error_message . '</div>';
      }
      ?>
    </div>
  </div>
  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <!-- Font Awesome for Icons -->
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>
  <!-- Bootstrap Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>