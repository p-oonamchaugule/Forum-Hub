<?php
require("config.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data
$user_data = [];
$stmt_user = $conn->prepare("SELECT full_name, email, username, created_at FROM users WHERE user_id = ?");
$stmt_user->bind_param("i", $_SESSION['user_id']);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user->num_rows > 0) {
    $user_data = $result_user->fetch_assoc();
}
$stmt_user->close();

// Initialize variables
$error_message = $success_message = null;

// Handle new post creation with file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_post'])) {
    $title = htmlspecialchars(trim($_POST['title']));
    $content = htmlspecialchars(trim($_POST['content']));
    $user_id = $_SESSION['user_id'];
    $category = htmlspecialchars(trim($_POST['category']));
    $privacy = htmlspecialchars(trim($_POST['privacy'])); // Add privacy field

    // Handle custom category
    if ($category === 'Custom') {
        $custom_category = htmlspecialchars(trim($_POST['custom_category']));
        if (empty($custom_category)) {
            $error_message = "Custom category cannot be empty.";
        } else {
            $category = $custom_category;
        }
    }

    // Initialize file variables
    $image = null;
    $video = null;

    // Handling image file upload
    if ($_FILES['image']['name']) {
        $image = basename($_FILES['image']['name']);
        $imageTmpName = $_FILES['image']['tmp_name'];
        $imageExtension = strtolower(pathinfo($image, PATHINFO_EXTENSION));
        $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($imageExtension, $allowedImageTypes)) {
            $image = uniqid("img_") . "." . $imageExtension; // Rename to avoid conflicts
            $imageDestination = 'uploads/' . $image;
            if (!move_uploaded_file($imageTmpName, $imageDestination)) {
                $error_message = "Failed to upload the image.";
            }
        } else {
            $error_message = "Invalid image file type. Allowed types: jpg, jpeg, png, gif.";
        }
    }

    // Handling video file upload
    if ($_FILES['video']['name']) {
        $video = basename($_FILES['video']['name']);
        $videoTmpName = $_FILES['video']['tmp_name'];
        $videoExtension = strtolower(pathinfo($video, PATHINFO_EXTENSION));
        $allowedVideoTypes = ['mp4', 'avi', 'mkv'];

        if (in_array($videoExtension, $allowedVideoTypes)) {
            $video = uniqid("vid_") . "." . $videoExtension; // Rename to avoid conflicts
            $videoDestination = 'uploads/' . $video;
            if (!move_uploaded_file($videoTmpName, $videoDestination)) {
                $error_message = "Failed to upload the video.";
            }
        } else {
            $error_message = "Invalid video file type. Allowed types: mp4, avi, mkv.";
        }
    }

    // Insert post into the database
    if (!isset($error_message)) {
        $stmt = $conn->prepare("INSERT INTO posts (user_id, title, content, image, video, category, privacy) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $user_id, $title, $content, $image, $video, $category, $privacy);

        if ($stmt->execute()) {
            $success_message = "Post created successfully!";
        } else {
            $error_message = "Failed to create post.";
        }

        $stmt->close();
    }
}
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT posts.*, users.full_name 
    FROM posts 
    JOIN users ON posts.user_id = users.user_id 
    WHERE posts.user_id = ? 
    ORDER BY posts.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_posts = $stmt->get_result();
// Get user's post statistics
$user_id = $_SESSION['user_id'];
// Get all post IDs for the user
$post_ids = [];
$stmt_posts = $conn->prepare("SELECT id FROM posts WHERE user_id = ?");
if ($stmt_posts) {
    $stmt_posts->bind_param("i", $user_id);
    $stmt_posts->execute();
    $result_posts = $stmt_posts->get_result();
    while ($row = $result_posts->fetch_assoc()) {
        $post_ids[] = $row['id'];
    }
    $stmt_posts->close();
} else {
    // Handle SQL error
    die("Error preparing SQL statement: " . $conn->error);
}

// Initialize variables
$total_comments = 0;
$total_replies = 0;
$total_comments_including_replies = 0;
$total_likes = 0;
$total_dislikes = 0;

// Calculate total interactions only if there are posts
if (!empty($post_ids)) {
    $post_ids_str = implode(',', $post_ids);

    // Count comments
    $query_comments = "SELECT COUNT(*) AS count FROM comments WHERE post_id IN ($post_ids_str)";
    $result_comments = $conn->query($query_comments);
    if ($result_comments) {
        $total_comments = $result_comments->fetch_assoc()['count'];
    } else {
        // Handle SQL error
        die("Error counting comments: " . $conn->error);
    }

    // Count replies
    $query_replies = "SELECT COUNT(*) AS count FROM replies WHERE comment_id IN (SELECT id FROM comments WHERE post_id IN ($post_ids_str))";
    $result_replies = $conn->query($query_replies);
    if ($result_replies) {
        $total_replies = $result_replies->fetch_assoc()['count'];
    } else {
        // Handle SQL error
        die("Error counting replies: " . $conn->error);
    }

    // Total comments including replies
    $total_comments_including_replies = $total_comments + $total_replies;

    // Calculate reactions
    $query_reactions = "SELECT 
        SUM(reaction = 'like') AS likes,
        SUM(reaction = 'dislike') AS dislikes
        FROM reactions 
        WHERE post_id IN ($post_ids_str)";
    $result_reactions = $conn->query($query_reactions);
    if ($result_reactions) {
        $row_reactions = $result_reactions->fetch_assoc();
        $total_likes = $row_reactions['likes'] ?? 0;
        $total_dislikes = $row_reactions['dislikes'] ?? 0;
    } else {
        // Handle SQL error
        die("Error calculating reactions: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/postcreationP.css">
    <link rel="stylesheet" href="css/postcreationUSER.css">
    <!-- Vendor CSS Files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Main CSS File -->
    <link href="assets/css/main.css" rel="stylesheet">

    <style>
        /* Buttons */
        .btn-outline-light {
            color: #6844E4;
            border: 1px solid #6844E4;
            background-color: transparent;
            transition: background-color 0.3s ease-in-out, color 0.3s ease-in-out;
        }

        .btn-outline-light:hover {
            background-color: #6844E4;
            color: #fff;
        }

        .btn-primary {
            background-color: #6844E4;
            color: white;
            border-radius: 20px;
            transition: background-color 0.3s ease-in-out, transform 0.2s ease-in-out;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .navbar-nav .nav-item .nav-link {
                padding: 8px 12px;
            }

            .navbar-brand .logo-text {
                font-size: 22px;
            }
        }


        /* Dropdown Styling */
        .nav-link.dropdown-toggle {
            display: flex;
            align-items: center;
            color: #555;
            /* Light grey for a clean look */
            font-size: 1rem;
            font-weight: 500;
            padding: 10px 15px;
            /* Adjust height of nav items */
            transition: color 0.3s ease-in-out, background-color 0.3s ease-in-out;
            border-radius: 5px;
        }

        .nav-link.dropdown-toggle:hover {
            background-color: #e6f7ff;
            /* Subtle hover effect */
            color: #6844E4;
        }

        .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .dropdown-item {
            color: #555;
            /* Light grey for a clean look */
            font-size: 1rem;
            font-weight: 500;
            padding: 10px 15px;
            /* Adjust height of nav items */
            transition: color 0.3s ease-in-out, background-color 0.3s ease-in-out;
        }

        .dropdown-item:hover {
            background-color: #e6f7ff;
            /* Subtle hover effect */
            color: #6844E4;
        }

        /* Footer Styling */
    </style>
</head>

<body>


    <header id="header" class="header d-flex align-items-center fixed-top" style='background:linear-gradient(45deg, #6243E4 0%, color-mix(in srgb, #823ABE, transparent 10%) 100%), url("../assets/img/hero-bg.jpg") center center no-repeat;'>
        <div class="container-fluid container-xl position-relative d-flex align-items-center">

            <a href="index.html" class="logo d-flex align-items-center me-auto">
                <img src="assets/img/forum_fusion_logo-removebg-preview.png" alt="">
                <h1 class="sitename">ForumHub</h1>
            </a>

            <nav id="navmenu" class="navmenu d-flex justify-content-center align-items-center">
                <ul>
                    <li><a href="index.php" class="">Home</a></li>
                    <li><a href="index.php#about">About</a></li>
                    <li><a href="index.php#feedback">Feedback</a></li>
                    <?php
                    if (isset($_SESSION['user_id'])) {
                        echo '<li><a href="#create-post" class="sidebar-nav-link"  data-section="create-post">Create Post</a></li><li>
                        <a href="#profile" class="sidebar-nav-link" data-section="profile">
                            Profile
                        </a>
                    </li>
                    <li>
                        <a href="#posts" class="sidebar-nav-link" data-section="posts">
                            Your Posts
                        </a>
                    </li>';
                    }
                    ?>
                    <li>
                        <a class="d-xl-none d-md-inline-block" href="./logout.php" aria-label="Logout">
                            Logout <i class="fa-solid fa-right-from-bracket"></i>
                        </a>
                    </li>
                </ul>

                <?php
                if (!isset($_SESSION['user_id'])) {
                    echo '<a class="me-2" href="login.php">Login</a>
          <a class="" id="button" href="signup.php">Sign Up</a>';
                } else {
                    echo '<a class="btn-getstarted d-none d-sm-block d-xl-inline-block" href="./logout.php" aria-label="Logout">Logout <i class="ms-2 fa-solid fa-right-from-bracket"></i></a>';
                }
                ?>


                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>


        </div>
    </header>

    <div class="dashboard-container">


        <!-- Main Content -->
        <main class="dashboard-main">
            <!-- Profile Section -->
            <section id="profile-section" class="dashboard-section" style="display: none;">
                <div class="user-profile-card">
                    <!-- Profile Header -->
                    <div class="profile-header text-center mb-5">
                        <h2 class="mb-2"><?php echo htmlspecialchars($user_data['full_name']); ?></h2>
                        <p class="text-muted">@<?php echo htmlspecialchars($user_data['username']); ?></p>
                    </div>

                    <!-- User Information -->
                    <div class="row g-4">
                        <!-- Personal Info -->
                        <div class="col-md-6">
                            <div class="info-card p-4 h-100">
                                <h4 class="mb-4 border-bottom pb-3"><i class="bi bi-person-lines-fill me-2"></i>Personal Information</h4>
                                <div class="info-item mb-3">
                                    <span class="text-muted"><i class="bi bi-envelope me-2"></i>Email:</span>
                                    <p class="mb-0"><?php echo htmlspecialchars($user_data['email']); ?></p>
                                </div>
                                <div class="info-item mb-3">
                                    <span class="text-muted"><i class="bi bi-calendar me-2"></i>Member Since:</span>
                                    <p class="mb-0"><?php echo date('F Y', strtotime($user_data['created_at'])); ?></p>
                                </div>
                                <div class="info-item">
                                    <span class="text-muted"><i class="bi bi-person-badge me-2"></i>Username:</span>
                                    <p class="mb-0">@<?php echo htmlspecialchars($user_data['username']); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics -->
                        <div class="col-md-6">
                            <div class="stats-card p-4 h-100">
                                <h4 class="mb-4 border-bottom pb-3"><i class="bi bi-bar-chart-line-fill me-2"></i>Activity Statistics</h4>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="stat-item text-center p-3">
                                            <div class="stat-value text-primary"><?= count($post_ids) ?></div>
                                            <div class="stat-label">Total Posts</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="stat-item text-center p-3">
                                            <div class="stat-value text-info">
                                                <?= isset($total_comments_including_replies) ? $total_comments_including_replies : 0 ?>
                                            </div>
                                            <div class="stat-label">Total Comments</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="stat-item text-center p-3">
                                            <div class="stat-value text-success"><?= isset($total_likes) ? $total_likes : 0 ?></div>
                                            <div class="stat-label">Total Likes</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="stat-item text-center p-3">
                                            <div class="stat-value text-danger"><?= isset($total_dislikes) ? $total_dislikes : 0 ?></div>
                                            <div class="stat-label">Total Dislikes</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Create Post Section -->
            <section id="create-post-section" class="dashboard-section" style="display: none;">
                <div class="row">
                    <div class="col-lg-6 mx-auto">
                        <div class="post-form-card">
                            <div class="post-form-header">
                                <h2 class="mb-0">Create New Post</h2>
                            </div>
                            <div class="post-form-body">
                                <?php if ($success_message): ?>
                                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                                <?php elseif ($error_message): ?>
                                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                                <?php endif; ?>
                                <form method="POST" enctype="multipart/form-data">
                                    <!-- Keep original form fields exactly as they were -->
                                    <div class="mb-3">
                                        <label for="title">Post Title</label>
                                        <input type="text" id="title" name="title" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="content">Post Content</label>
                                        <textarea id="content" name="content" rows="5" class="form-control"
                                            required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="category">Category</label>
                                        <select id="category" name="category" class="form-control">
                                            <option value="Technology">Technology</option>
                                            <option value="Education">Education</option>
                                            <option value="Entertainment">Entertainment</option>
                                            <option value="Lifestyle">Lifestyle</option>
                                            <option value="Health">Health</option>
                                            <option value="Custom">Custom</option>
                                        </select>
                                        <div id="custom_category_div" style="display: none;" class="mt-2">
                                            <label for="custom_category">Custom Category</label>
                                            <input type="text" id="custom_category" name="custom_category"
                                                class="form-control">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="image">Image (optional)</label>
                                        <input type="file" id="image" name="image" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label for="video">Video (optional)</label>
                                        <input type="file" id="video" name="video" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label for="privacy">Privacy</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="privacy"
                                                    id="privacy_public" value="public" checked>
                                                <label class="form-check-label" for="privacy_public">Public</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="privacy"
                                                    id="privacy_private" value="private">
                                                <label class="form-check-label" for="privacy_private">Private</label>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" name="create_post" class="btn btn-primary w-100">Create
                                        Post</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Posts Section -->
            <!-- Updated Posts Section -->
            <section id="posts-section" class="dashboard-section" style="display: none;">
                <div class="container-fluid">
                    <h3 class="text-center mb-4">Your Posts</h3>
                    <?php if ($user_posts->num_rows > 0): ?>
                        <div class="posts-grid">
                            <?php while ($post = $user_posts->fetch_assoc()): ?>
                                <div class="post-card">
                                    <div class="post-content">
                                        <h5 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                                        <p class="post-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                        <div class="post-media-container">
                                            <?php if (!empty($post['image'])): ?>
                                                <img src="uploads/<?php echo $post['image']; ?>" alt="Post Image"
                                                    class="post-image">
                                            <?php endif; ?>
                                            <?php if (!empty($post['video'])): ?>
                                                <video muted autoplay class="post-video" controls muted>
                                                    <source src="uploads/<?php echo $post['video']; ?>" type="video/mp4">
                                                </video>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="post-meta">
                                        <small class="d-block text-muted">
                                            <i class="bi bi-tag"></i>
                                            <?php echo htmlspecialchars($post['category']); ?>
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="bi bi-lock"></i>
                                            <?php echo ucfirst($post['privacy']); ?>
                                        </small>
                                        <small class="d-block text-muted">
                                            <i class="bi bi-clock"></i>
                                            <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="post-actions mb-2" style="margin-left: 5%;">
                                        <a href="edit_post.php?id=<?php echo $post['id']; ?>"
                                            class="btn btn-outline-warning btn-sm">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="delete_post.php?delete_id=<?php echo $post['id']; ?>"
                                            class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted">You have not created any posts yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>


        </main>
    </div>
    <button class="scroll-to-top">
        <i class="bi bi-arrow-up-short" style="font-size: 1.5rem;"></i>
    </button>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.dashboard-section');
            const navLinks = document.querySelectorAll('.sidebar-nav-link');

            function showSection(sectionId) {
                sections.forEach(section => {
                    section.style.display = 'none';
                });
                document.querySelector(`#${sectionId}-section`).style.display = 'block';

                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.dataset.section === sectionId) {
                        link.classList.add('active');
                    }
                });
            }

            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const sectionId = this.dataset.section;
                    showSection(sectionId);
                });
            });

            let userHash = window.location.hash.substring(1) || 'profile';
            console.log('calling :' + userHash);
            showSection(userHash);
        });
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll to Top Button
            const scrollBtn = document.querySelector('.scroll-to-top');

            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    scrollBtn.classList.add('show');
                } else {
                    scrollBtn.classList.remove('show');
                }
            });

            scrollBtn.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        });
    </script>


    <script>
        document.getElementById("category").addEventListener("change", function() {
            const customDiv = document.getElementById("custom_category_div");
            const customInput = document.getElementById("custom_category");
            if (this.value === "Custom") {
                customDiv.style.display = "block";
                customInput.required = true;
            } else {
                customDiv.style.display = "none";
                customInput.required = false;
                customInput.value = ""; // Clear the custom input value
            }
        });
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
    <!-- Font Awesome for Icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <!-- Main JS File -->
    <script src="assets/js/main.js"></script>

</body>

</html>