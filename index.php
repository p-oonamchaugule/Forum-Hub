<?php
session_start();
require("config.php");

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$searchParam = "%" . $search . "%";
$categoryParam = "%" . $category . "%";

// Check if the user is logged in
$user_id = $_SESSION['user_id'] ?? null;

// SQL query with LEFT JOIN to include the author name and filter by category if provided
$sql = "SELECT posts.*, users.full_name 
        FROM posts 
        LEFT JOIN users ON posts.user_id = users.user_id 
        WHERE (posts.title LIKE ? OR posts.content LIKE ?) ";

// Add privacy filter
if ($user_id) {
  // If the user is logged in, show their private posts and all public posts
  $sql .= " AND (posts.privacy = 'public' OR (posts.privacy = 'private' AND posts.user_id = ?)) ";
} else {
  // If the user is not logged in, only show public posts
  $sql .= " AND posts.privacy = 'public' ";
}

// Add category filter if provided
if (!empty($category)) {
  $sql .= " AND posts.category LIKE ? ";
}

$sql .= " ORDER BY posts.created_at DESC";

$stmt = $conn->prepare($sql);

// Bind parameters based on whether a category is provided
if (!empty($category)) {
  if ($user_id) {
    $stmt->bind_param("ssis", $searchParam, $searchParam, $user_id, $categoryParam);
  } else {
    $stmt->bind_param("sss", $searchParam, $searchParam, $categoryParam);
  }
} else {
  if ($user_id) {
    $stmt->bind_param("ssi", $searchParam, $searchParam, $user_id);
  } else {
    $stmt->bind_param("ss", $searchParam, $searchParam);
  }
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome to Forum</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- External CSS -->
  <link rel="stylesheet" href="css/homepage.css">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">
  <!-- Bootstrap CSS -->
  <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    /* Hero Section */
    /* .hero {
  background: linear-gradient(135deg, #ffffff 0%, #f1f8e9 100%);
  padding: 60px 0;
  height: 100vh; 
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: center;
  position: relative;
  overflow: hidden;
} */


    /* Button Styling */
    /* .btn {
      border-radius: 15px;
      transition: background-color 0.3s ease-in-out, color 0.3s ease-in-out;
      padding: 12px 24px;
      font-size: 0.9rem;
      background-color: #6844E4;
      transform: .3s;
    } */

    /* Button Hover Effects */
    /* .btn-primary:hover {
      background-color: rgba(77, 44, 169, 0.9);
      ;
      color: rgb(255, 255, 255);
    } */

    /* filter  */
    #filter-group .list-group-item a {
      width: 100%;
      height: 100%;
    }

    h3{
      font-size: 2rem;
    font-weight: 700;
    }
    /* Custom CSS for Feedback Form */
    .feedback-section {
      background-color: #f8f9fa;
      padding: 60px 0;
      margin-top: 40px;
    }

    .feedback-section h2 {
      font-size: 2.5rem;
      margin-bottom: 20px;
      color: #343a40;
    }

    .feedback-section p {
      font-size: 1.1rem;
      color: #6c757d;
      margin-bottom: 30px;
    }

    .feedback-form {
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .feedback-form .form-group {
      margin-bottom: 25px;
    }

    .feedback-form textarea {
      resize: none;
    }

    /* Rating System Styles */
    .rating {
      direction: rtl;
      display: inline-block;
      font-size: 2rem;
      unicode-bidi: bidi-override;
    }

    .rating input {
      display: none;
    }

    .rating label {
      color: #ccc;
      cursor: pointer !important;
      font-size: 50px !important;
      transition: color 0.2s ease !important;
    }

    /* When a star is checked or hovered over, change color */
    .rating input:checked~label,
    .rating label:hover,
    .rating label:hover~label {
      color: #f5b301;
    }


    /* Alert Message Styling */
    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border: 1px solid transparent;
      border-radius: 4px;
      font-size: 1rem;
      text-align: center;
    }

    .alert-success {
      background-color: #d4edda;
      border-color: #c3e6cb;
      color: #155724;
    }

    .alert-error {
      background-color: #f8d7da;
      border-color: #f5c6cb;
      color: #721c24;
    }
  </style>
</head>

<body>

  <header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center">

      <a href="index.html" class="logo d-flex align-items-center me-auto">
        <img src="assets/img/forum_fusion_logo-removebg-preview.png" alt="">
        <h1 class="sitename d-none d-sm-inline-block">ForumHub</h1>
      </a>

      <nav id="navmenu" class="navmenu d-flex justify-content-center align-items-center">
        <ul>
          <li><a href="#hero" class="active">Home</a></li>
          <li><a href="#about">About</a></li>
          <li><a href="#ALLPost">Posts</a></li>
          <li><a href="#feedback">Feedback</a></li>
          <?php
          if (isset($_SESSION['user_id'])) {
            echo '<li><a href="Postcreation.php#create-post" class="sidebar-nav-link"  data-section="create-post">Create Post</a></li>
                        <li>
                        <a href="Postcreation.php#profile" class="sidebar-nav-link" data-section="profile">
                            Profile
                        </a>
                    </li>
                    <li><a class="d-xl-none d-md-inline-block" href="./logout.php" aria-label="Logout">
              Logout <i class="fa-solid fa-right-from-bracket"></i>
            </a></li>';
          }else{
            if (!isset($_SESSION['user_id'])) {
          echo '<li><a class="me-2" href="login.php">Login</a></li>
          <li><a class="" id="button" href="signup.php">Sign Up</a></li>';
        } 
          }
          ?>
         
        </ul>
        <form class="d-flex me-3" method="GET" action="" style="transition: box-shadow 0.3s ease-in-out; height:40px;">
          <input class="form-control ms-4 me-2" style="border: none; box-shadow: none;" type="search" placeholder="Search posts" name="search" aria-label="Search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
          <button style="background: transparent; border: none; font-size: 25px;" type="submit">
            <i class="fa-solid fa-magnifying-glass text-light"></i>
          </button>
        </form>
          <?php
            if(isset($_SESSION['user_id'])){
              echo '<a class="btn-getstarted d-none d-sm-block d-xl-inline-block" href="./logout.php" aria-label="Logout">Logout <i class="ms-2 fa-solid fa-right-from-bracket"></i></a>';
            }
          ?>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>


    </div>
  </header>


  <!-- Hero Section -->
  <section id="hero" class="hero section accent-background">

    <div class="container">
      <div class="row gy-4">
        <div class="col-lg-6 order-2 order-lg-1 d-flex flex-column justify-content-center">
          <h1>Connect, Share, and Grow with ForumHub</h1>
          <p>A platform where Ideas Connect, Conversations Thrive.</p>
          <div class="d-flex">
            <?php
            if (!isset($_SESSION['user_id'])) {
              echo '<a href="#about" class="btn-get-started" onclick="window.location.href=\'signup.php\'">Get Started</a>';
            }
            ?>

          </div>
        </div>
        <div class="col-lg-6 order-1 order-lg-2 hero-img">
          <img src="assets/img/hero-img.png" class="img-fluid animated" alt="">
        </div>
      </div>
    </div>
    
  </section><!-- /Hero Section -->

  <!-- About Section -->
  <section id="about" class="about section">
    <div class="container">
      <div class="row gy-4">

        <div class="col-lg-6 order-1 order-lg-2" data-aos="fade-up" data-aos-delay="100">
          <img src="assets/img/about.jpg" class="img-fluid" alt="">
        </div>

        <div class="col-lg-6 order-2 order-lg-1 content" data-aos="fade-up" data-aos-delay="200">
          <h3>About Us</h3>
          <p class="fst-italic">
          ForumHub is your go-to platform for all things tech within the IT department. Whether you're troubleshooting a complex issue, brainstorming new ideas, or simply sharing the latest tech trends, ForumHub brings our team together to foster innovation, problem-solving, and collaboration.
          </p>
          <ul>
            <li><i class="bi bi-check-circle"></i> <span><strong>Knowledge Sharing :</strong> Whether it’s a cool new tool, a coding tip, or a resource, ForumHub is the place to share what you know and learn from others</span></li>
            <li><i class="bi bi-check-circle"></i> <span><strong>Collaborative Problem Solving :</strong> Post questions, share solutions, and work together on technical challenges, big or small.</span></li>
            <li><i class="bi bi-check-circle"></i> <span><strong>Threaded Discussions :</strong> Dive deep into any topic with organized threads that allow for easy tracking of ongoing conversations.</span></li>
          </ul>
        </div>
      </div>
    </div>
  </section><!-- /About Section -->




  <!-- Filters and Featured Posts -->
  <section id="ALLPost">
    <div class="container">
      <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3 mb-4">
          <h3>Filter by Category</h3>
          <ul class="list-group" id="filter-group">
            <li class="list-group-item"><a href="?category=Technology#ALLPost">Technology</a></li>
            <li class="list-group-item"><a href="?category=Education#ALLPost">Education</a></li>
            <li class="list-group-item"><a href="?category=Entertainment#ALLPost">Entertainment</a></li>
            <li class="list-group-item"><a href="?category=Lifestyle#ALLPost">Lifestyle</a></li>
            <li class="list-group-item"><a href="?category=Health#ALLPost">Health</a></li>
            <li class="list-group-item"><a href="?#ALLPost">All</a></li>
          </ul>
        </div>

        <!-- Featured Posts -->
        <div class="col-lg-9">
          <h4>
            <?php
            if (!empty($search)) {
              echo "Search Results for: \"" . htmlspecialchars($search) . "\"";
            } elseif (!empty($category)) {
              echo "Posts in Category: \"" . htmlspecialchars($category) . "\"";
            } else {
              echo "<h3>All Posts</h3>";
            }
            ?>
          </h4>
          <div class="row g-4">
            <?php
            if ($result && $result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                echo '
        <div class="col-md-6 col-lg-4">
            <div class="card post-card" data-id="' . $row['id'] . '">
                <div class="card-body">
                    <h5 class="card-title">' . htmlspecialchars($row['title']) . '</h5>
                    <p class="card-text">' . htmlspecialchars($row['content']) . '</p>';

                echo '<div class="media-container">';

                if (!empty($row['image'])) {
                  echo '<img src="uploads/' . htmlspecialchars($row['image']) . '" class="img-fluid post-image" alt="Post Image">';
                }

                if (!empty($row['video'])) {
                  echo '
                        <video muted autoplay class="img-fluid post-video" controls>
                            <source src="uploads/' . htmlspecialchars($row['video']) . '" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>';
                }

                echo '</div>';  // Close media-container

                echo '
                    <small class="text-muted">Category: ' . htmlspecialchars($row['category']) . '</small>
                    <small class="text-muted d-block">Posted by: ' . htmlspecialchars($row['full_name']) . '</small>
                    <small class="text-muted d-block">Posted on: ' . $row['created_at'] . '</small>
                    <small class="text-muted d-block">Privacy: ' . ucfirst($row['privacy']) . '</small>
                    <a href="post-detail.php?id=' . $row['id'] . '" class="btn btn-primary">Read More</a>
                </div>
            </div>
        </div>
        ';
              }
            } else {
              echo '<div class="col-12"><p class="text-center">No posts found.</p></div>';
            }
            ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Feedback Section -->
  <section class="feedback-section" id="feedback">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
          <h2 style="font-weight: 700;">Feedback</h2>
          <p>We value your feedback! Please take a moment to answer a few questions about your experience with ForumHub and provide your ratings. Your input helps us improve!</p>
        </div>
      </div>
      <div class="row justify-content-center">
        <div class="col-lg-6">
          <!-- Display Alert Message -->
          <?php
          if (isset($_SESSION['message'])) {
            $message_type = $_SESSION['message_type'];
            echo '<div class="alert alert-' . $message_type . '">' . $_SESSION['message'] . '</div>';
            unset($_SESSION['message']); // Clear the message after displaying
            unset($_SESSION['message_type']); // Clear the message type after displaying
          }

          // Check if the user is logged in
          if (!isset($_SESSION['user_id'])) {
            echo '<div class="alert alert-warning">You need to <a href="login.php">log in</a> to submit feedback.</div>';
          } else {
            // Display the feedback form for logged-in users
            echo '
            <form class="feedback-form" action="feedback_process.php" method="POST">
              <!-- Question 1: Usability -->
               <input type="hidden" name="user_id" value="' . $_SESSION['user_id'] . '">
              <div class="form-group">
                <label>How would you rate the usability of ForumHub?</label>
                <div class="rating d-flex justify-content-around">
                  <input type="radio" id="usability-5" name="usability" value="5" required>
                  <label for="usability-5" title="5 stars">&#9733;</label>
                  <input type="radio" id="usability-4" name="usability" value="4">
                  <label for="usability-4" title="4 stars">&#9733;</label>
                  <input type="radio" id="usability-3" name="usability" value="3">
                  <label for="usability-3" title="3 stars">&#9733;</label>
                  <input type="radio" id="usability-2" name="usability" value="2">
                  <label for="usability-2" title="2 stars">&#9733;</label>
                  <input type="radio" id="usability-1" name="usability" value="1">
                  <label for="usability-1" title="1 star">&#9733;</label>
                </div>
              </div>

              <!-- Question 2: Design -->
              <div class="form-group">
                <label>How would you rate the design and layout of ForumHub?</label>
                <div class="rating d-flex justify-content-around">
                  <input type="radio" id="design-5" name="design" value="5" required>
                  <label for="design-5">&#9733;</label>
                  <input type="radio" id="design-4" name="design" value="4">
                  <label for="design-4">&#9733;</label>
                  <input type="radio" id="design-3" name="design" value="3">
                  <label for="design-3">&#9733;</label>
                  <input type="radio" id="design-2" name="design" value="2">
                  <label for="design-2">&#9733;</label>
                  <input type="radio" id="design-1" name="design" value="1">
                  <label for="design-1">&#9733;</label>
                </div>
              </div>

              <!-- Question 3: Features -->
              <div class="form-group">
                <label>How would you rate the features and functionality of ForumHub?</label>
                <div class="rating d-flex justify-content-around">
                  <input type="radio" id="features-5" name="features" value="5" required>
                  <label for="features-5">&#9733;</label>
                  <input type="radio" id="features-4" name="features" value="4">
                  <label for="features-4">&#9733;</label>
                  <input type="radio" id="features-3" name="features" value="3">
                  <label for="features-3">&#9733;</label>
                  <input type="radio" id="features-2" name="features" value="2">
                  <label for="features-2">&#9733;</label>
                  <input type="radio" id="features-1" name="features" value="1">
                  <label for="features-1">&#9733;</label>
                </div>
              </div>

              <!-- Question 4: Overall Satisfaction -->
              <div class="form-group">
                <label>How satisfied are you with ForumHub overall?</label>
                <div class="rating d-flex justify-content-around">
                  <input type="radio" id="satisfaction-5" name="satisfaction" value="5" required>
                  <label for="satisfaction-5">&#9733;</label>
                  <input type="radio" id="satisfaction-4" name="satisfaction" value="4">
                  <label for="satisfaction-4">&#9733;</label>
                  <input type="radio" id="satisfaction-3" name="satisfaction" value="3">
                  <label for="satisfaction-3">&#9733;</label>
                  <input type="radio" id="satisfaction-2" name="satisfaction" value="2">
                  <label for="satisfaction-2">&#9733;</label>
                  <input type="radio" id="satisfaction-1" name="satisfaction" value="1">
                  <label for="satisfaction-1">&#9733;</label>
                </div>
              </div>

              <!-- Additional Comments -->
              <div class="form-group">
                <label for="comments">Any additional comments or suggestions?</label>
                <textarea class="form-control" id="comments" name="comments" rows="5" placeholder="Your feedback is valuable to us!"></textarea>
              </div>

              <!-- Submit Button -->
              <button type="submit" class="btn btn-primary">Submit Feedback</button>
            </form>';
          }
          ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer id="footer" class="footer accent-background">

    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-5 col-md-12 footer-about">
          <a href="index.html" class="logo d-flex align-items-center">
            <span class="sitename">ForumHub</span>
          </a>
          <p>Where Conversations Shape Ideas,
And Collaboration Builds Success.</p>
          <div class="social-links d-flex mt-4">
            <a href=""><i class="bi bi-twitter-x"></i></a>
            <a href=""><i class="bi bi-facebook"></i></a>
            <a href=""><i class="bi bi-instagram"></i></a>
            <a href=""><i class="bi bi-linkedin"></i></a>
          </div>
        </div>

        <div class="col-lg-2 col-6 footer-links">
          <h4>Useful Links</h4>
          <ul>
            <li><a href="#">Home</a></li>
            <li><a href="#">About us</a></li>
            <li><a href="#">Terms of service</a></li>
            <li><a href="#">Privacy policy</a></li>
          </ul>
        </div>

        <div class="col-lg-3 col-md-12 footer-contact text-center text-md-start">
          <h4>Contact Us</h4>
          <p>COEP Technological University </p>
          <p>Pune, 411005</p>
          <p>India</p>
          <p class="mt-4"><strong>Phone:</strong> <span>+91 00000 00000</span></p>
          <p><strong>Email:</strong> <span>info@example.com</span></p>
        </div>

      </div>
    </div>

    <div class="container copyright text-center mt-4">
      <p>© <span>Copyright</span> <strong class="px-1 sitename">ForumHub</strong> <span>All Rights Reserved</span></p>
      <div class="credits">
        Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
      </div>
    </div>

  </footer>

  <!-- Bootstrap Icons -->
  <script>
    const stars = document.getElementsByName('usability');
    stars.forEach(star => {
      star.addEventListener('change', () => {
        console.log('Rating selected:', star.value);
      });
    });
  </script>
  <script>
      function aosInit() {
    AOS.init({
      duration: 600,
      easing: 'ease-in-out',
      once: true,
      mirror: false
    });
  }
  window.addEventListener('load', aosInit);
  </script>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>

  <!-- Main JS File -->
  <script src="/assets/js/main.js"></script>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <!-- Font Awesome for Icons -->
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>

</html>