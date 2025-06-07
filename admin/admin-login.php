<?php
session_start();
require("../config.php");

if (isset($_SESSION['admin_id'])) {
  header("Location: ./admin-panel.php"); // Redirect to the index page
  exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $email = $_POST['email'];
  $password = $_POST['password'];

  // Prepare the SQL statement to prevent SQL injection
  $stmt = $conn->prepare("SELECT * FROM admin_login WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // If passwords were hashed in PHP using `password_hash`
    if ($password === $user['password']) { // Direct comparison
      $_SESSION['admin_id'] = $user['id'];
      header("Location: admin-panel.php");
      exit();
    } else {
      $error = "Invalid email or password.";
    }
  } else {
    $error = "No admin found with this email.";
  }

  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="../css/admin-log.css">
  <title>Admin Login</title>

  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="../assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="../assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <!-- Main CSS File -->
  <link href="../assets/css/main.css" rel="stylesheet">
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
                        <li><a href="../index.php#hero">Home</a></li>
                        <li><a href="../index.php#about">About</a></li>
                        <li><a href="../index.php#ALLPost">Posts</a></li>
                        <li><a href="../index.php#feedback">Feedback</a></li>
                        
                      </ul>

                    <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
                </nav>


            </div>
    </header>

  <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="login-container">
      <h2>Admin Login</h2>
      <?php if (isset($error)): ?>
        <p class="error-message"><?= htmlspecialchars($error); ?></p>
      <?php endif; ?>

      <form action="" method="POST">
        <div class="form-group">
          <label for="email">Email:</label>
          <input type="email" name="email" required>
        </div>
        <div class="form-group">
          <label for="password">Password:</label>
          <input type="password" name="password" required>
        </div>
        <div class="form-group">
          <button type="submit" class="btn btn-primary">Login</button>
        </div>
      </form>
    </div>
  </div>

  <!-- JavaScript for Dark Mode Toggle -->
  <script>
    const darkModeToggle = document.getElementById('darkModeToggle');
    const body = document.body;

    darkModeToggle.addEventListener('click', () => {
      body.classList.toggle('dark-mode');
    });
  </script>
 <!-- Vendor JS Files -->
 <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/vendor/php-email-form/validate.js"></script>
  <script src="../assets/vendor/aos/aos.js"></script>
  <script src="../assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="../assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="../assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="../assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="../assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <!-- Font Awesome for Icons -->
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

  <!-- Main JS File -->
  <script src="../assets/js/main.js"></script>
</body>

</html>