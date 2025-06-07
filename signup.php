<?php
require("config.php");  // Database connection

if (isset($_SESSION['user_id'])) {
    header("Location: ./index.php"); // Redirect to the index page
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $username = trim($_POST['username']);  // Remove extra spaces
    $password = $_POST['password'];

    // Basic validation
    $errors = [];

    // Validate full name
    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    } elseif (!preg_match("/^[a-zA-Z ]*$/", $full_name)) {
        $errors[] = 'Full name can only contain letters and spaces.';
    }

    // Validate email
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }

    // Validate username
    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 5) {
        $errors[] = 'Username must be at least 5 characters long.';
    }

    // Validate password
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    if (empty($errors)) {
        // Hash the password before storing it in the database
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare the SQL statement to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, username, password) VALUES (?, ?, ?, ?)");

        if ($stmt) {
            $stmt->bind_param("ssss", $full_name, $email, $username, $hashed_password);

            if ($stmt->execute()) {
                // Successful registration, set session
                session_start();
                $_SESSION['is_logged_in'] = true;
                header("Location: login.php");
                exit();
            } else {
                $error_message = "Registration failed. Please try again.";
            }

            $stmt->close();
        } else {
            $error_message = "Database prepare statement failed.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up Page</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                            echo '<li><a class="" id="button" href="login.php">Login</a></li>';
                        }
                        ?>

                    </ul>

                    <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
                </nav>


            </div>
    </header>

    <div class="container d-flex justify-content-center align-items-center min-vh-100 mt-3">
        <div class="card p-4 shadow-lg" style="width: 100%; max-width: 400px;">
            <h2 class="text-center mb-4">Sign Up</h2>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Enter your full name" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Sign Up</button>
            </form>
            <p class="text-center mt-3">Already have an account? <a href="login.php">Login here</a></p>
            <?php
            if (isset($error_message)) {
                echo '<div class="text-danger text-center mt-3">' . $error_message . '</div>';
            }
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    echo '<div class="text-danger text-center mt-2">' . $error . '</div>';
                }
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