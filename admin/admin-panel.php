<?php
session_start();
require("config.php");

if (!isset($_SESSION['admin_id'])) {
  header("Location: ./admin-login.php"); // Redirect to the admin login page
  exit();
}

// Prepare the SQL statement to prevent SQL injection
$stmt = $conn->prepare("SELECT * FROM admin_login");
$stmt->execute();
$result = $stmt->get_result();
$admin_email = "temp";

if ($result->num_rows > 0) {
  $user = $result->fetch_assoc();
  $admin_email = $user['email'];
}

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fetch all data for posts (newest first)
$posts_query = "SELECT p.id, p.title, p.content AS content_preview, p.created_at, p.category, u.full_name AS author 
                FROM posts p 
                JOIN users u ON p.user_id = u.user_id
                ORDER BY p.created_at DESC";
$posts_result = $conn->query($posts_query);
if (!$posts_result) {
  die("Posts query failed: " . $conn->error);
}

// Fetch all data for comments with the author username (newest first)
$comments_query = "SELECT c.id, c.comment, c.created_at, 
                          p.title AS post_title, 
                          u.full_name AS author, 
                          u.username AS user_username,
                          COUNT(r.id) AS reply_count
                   FROM comments c 
                   LEFT JOIN replies r ON c.id = r.comment_id
                   JOIN posts p ON c.post_id = p.id 
                   JOIN users u ON c.user_id = u.user_id
                   GROUP BY c.id
                   ORDER BY c.created_at DESC";
$comments_result = $conn->query($comments_query);
if (!$comments_result) {
  die("Comments query failed: " . $conn->error);
}

// Fetch all users (newest first)
$users_query = "SELECT * FROM users ORDER BY created_at DESC";
$users_result = $conn->query($users_query);
if (!$users_result) {
  die("Users query failed: " . $conn->error);
}

// Fetch all feedback data (newest first)
$feedback_query = "SELECT f.*, u.full_name 
                   FROM feedback f
                   JOIN users u ON f.user_id = u.user_id
                   ORDER BY f.created_at DESC";
$feedback_result = $conn->query($feedback_query);
if (!$feedback_result) {
  die("Feedback query failed: " . $conn->error);
}

// Fetch user statistics (post count, likes, dislikes)
$user_stats_query = "SELECT u.user_id, u.full_name, 
                            COUNT(p.id) AS post_count, 
                            SUM(CASE WHEN r.reaction = 'like' THEN 1 ELSE 0 END) AS total_likes, 
                            SUM(CASE WHEN r.reaction = 'dislike' THEN 1 ELSE 0 END) AS total_dislikes
                     FROM users u
                     LEFT JOIN posts p ON u.user_id = p.user_id
                     LEFT JOIN reactions r ON p.id = r.post_id
                     GROUP BY u.user_id
                     ORDER BY post_count DESC, total_likes DESC";
$user_stats_result = $conn->query($user_stats_query);
if (!$user_stats_result) {
  die("User stats query failed: " . $conn->error);
}

// Prepare data for the chart
$user_stats_labels = [];
$user_stats_post_count = [];
$user_stats_likes = [];
$user_stats_dislikes = [];

while ($user_stat = $user_stats_result->fetch_assoc()) {
  $user_stats_labels[] = $user_stat['full_name'];
  $user_stats_post_count[] = $user_stat['post_count'];
  $user_stats_likes[] = $user_stat['total_likes'];
  $user_stats_dislikes[] = $user_stat['total_dislikes'];
}

// Handle delete actions with integer conversion to prevent injection
if (isset($_GET['delete_post_id'])) {
  $post_id = intval($_GET['delete_post_id']);
  $conn->query("DELETE FROM posts WHERE id = $post_id");
  header("Location: admin-panel.php");
  exit;
}

if (isset($_GET['delete_comment_id'])) {
  $comment_id = intval($_GET['delete_comment_id']);
  $conn->query("DELETE FROM comments WHERE id = $comment_id");
  header("Location: admin-panel.php");
  exit;
}

if (isset($_GET['delete_user_id'])) {
  $user_id = intval($_GET['delete_user_id']);
  $conn->query("DELETE FROM users WHERE user_id = $user_id");
  header("Location: admin-panel.php");
  exit;
}

if (isset($_GET['delete_feedback_id'])) {
  $feedback_id = intval($_GET['delete_feedback_id']);
  $conn->query("DELETE FROM feedback WHERE id = $feedback_id");
  header("Location: admin-panel.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Dashboard - ForumAdmin</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="./assets/css/style.css" rel="stylesheet">
  <style>
    .card-header {
      background-color: rgb(11, 98, 226) !important;
    }
    /* Professional Custom Modal Styles */
    #customModal {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      animation: fadeIn 0.3s;
    }
    #customModal .modal-content {
      background: #fff;
      margin: 10% auto;
      padding: 30px;
      border-radius: 8px;
      width: 350px;
      text-align: center;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      animation: slideIn 0.3s;
    }
    #customModal p {
      font-size: 1.1rem;
      margin-bottom: 20px;
    }
    #customModal .modal-buttons button {
      margin: 0 10px;
      min-width: 100px;
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      font-size: 1rem;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }
    #modalConfirm {
      background-color: #dc3545;
      color: #fff;
    }
    #modalConfirm:hover {
      background-color: #c82333;
    }
    #modalCancel {
      background-color: #6c757d;
      color: #fff;
    }
    #modalCancel:hover {
      background-color: #5a6268;
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    @keyframes slideIn {
      from { transform: translateY(-20px); }
      to { transform: translateY(0); }
    }
  </style>
</head>

<body>
  <!-- ======= Header ======= -->
  <header id="header" class="header fixed-top d-flex align-items-center">
    <div class="d-flex align-items-center justify-content-between">
      <a href="../index.php" class="logo d-flex align-items-center">
        <img src="assets/img/logo.png" alt="">
        <span class="d-none d-lg-block">ForumAdmin</span>
      </a>
      <i class="bi bi-list toggle-sidebar-btn"></i>
    </div>
    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center">
        <li class="nav-item dropdown pe-3">
          <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
            <img src="assets/img/profile-img.jpg" alt="Profile" class="rounded-circle">
            <span class="d-none d-md-block dropdown-toggle ps-2"><?php echo $admin_email; ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
            <li class="dropdown-header">
              <h6><?php echo $admin_email; ?></h6>
              <span>Admin</span>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="./adminLogout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign Out</span>
              </a>
            </li>
          </ul>
        </li>
      </ul>
    </nav>
  </header>

  <!-- ======= Sidebar ======= -->
  <aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
      <li class="nav-item">
        <a class="nav-link" href="#" onclick="showSection('posts-section','Dashboard')">
          <i class="ri-layout-grid-line"></i>
          <span>Dashboard</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" onclick="showSection('posts-section','Posts')">
          <i class="ri-questionnaire-line"></i>
          <span>Posts</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" onclick="showSection('comments-section','Comments')">
          <i class="ri-chat-1-line"></i>
          <span>Comments</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" onclick="showSection('users-section','Users')">
          <i class="ri-group-line"></i>
          <span>Users</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" onclick="showSection('user-stats-section','User Statistics')">
          <i class="ri-bar-chart-2-line"></i>
          <span>User Statistics</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" onclick="showSection('feedback-section','Feedback')">
          <i class="ri-feedback-line"></i>
          <span>Feedback</span>
        </a>
      </li>
    </ul>
  </aside>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Dashboard</h1>
      <!-- <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
          <li class="breadcrumb-item active">Dashboard</li>
        </ol>
      </nav> -->
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
      <!-- Posts Section -->
      <section id="posts-section" class="content-section mb-5">
        <div class="card shadow">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Latest Posts</h4>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th style="width: 20%;">Title</th>
                    <th style="width: 25%;">Preview</th>
                    <th style="width: 15%;">Category</th>
                    <th style="width: 15%;">Author</th>
                    <th style="width: 15%;">Date</th>
                    <th style="width: 10%;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($post = $posts_result->fetch_assoc()): ?>
                    <tr>
                      <td><?= htmlspecialchars($post['title']) ?></td>
                      <td><?= htmlspecialchars($post['content_preview']) ?></td>
                      <td><?= htmlspecialchars($post['category']) ?></td>
                      <td><?= htmlspecialchars($post['author']) ?></td>
                      <td><?= date('M d, Y', strtotime($post['created_at'])) ?></td>
                      <td>
                        <a href="?delete_post_id=<?= $post['id'] ?>" class="btn btn-danger btn-sm delete-link" data-message="Are you sure you want to delete this post?">
                          <i class="bi bi-trash"></i> Delete
                        </a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <!-- Comments Section -->
      <section id="comments-section" class="content-section mb-5">
        <div class="card shadow">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Recent Comments</h4>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th style="width: 25%;">Comment</th>
                    <th style="width: 20%;">Post</th>
                    <th style="width: 15%;">Author</th>
                    <th style="width: 10%;">Replies</th>
                    <th style="width: 15%;">Date</th>
                    <th style="width: 15%;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($comment = $comments_result->fetch_assoc()): ?>
                    <tr>
                      <td><?= htmlspecialchars($comment['comment']) ?></td>
                      <td><?= htmlspecialchars($comment['post_title']) ?></td>
                      <td><?= htmlspecialchars($comment['author']) ?></td>
                      <td><?= $comment['reply_count'] ?></td>
                      <td><?= date('M d, Y', strtotime($comment['created_at'])) ?></td>
                      <td>
                        <a href="?delete_comment_id=<?= $comment['id'] ?>" class="btn btn-danger btn-sm delete-link" data-message="Are you sure you want to delete this comment?">
                          <i class="bi bi-trash"></i> Delete
                        </a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <!-- Users Section -->
      <section id="users-section" class="content-section mb-5">
        <div class="card shadow">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Registered Users</h4>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th style="width: 20%;">Name</th>
                    <th style="width: 15%;">Username</th>
                    <th style="width: 25%;">Email</th>
                    <th style="width: 20%;">Join Date</th>
                    <th style="width: 20%;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($user = $users_result->fetch_assoc()): ?>
                    <tr>
                      <td><?= htmlspecialchars($user['full_name']) ?></td>
                      <td><?= htmlspecialchars($user['username']) ?></td>
                      <td><?= htmlspecialchars($user['email']) ?></td>
                      <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                      <td>
                        <a href="?delete_user_id=<?= $user['user_id'] ?>" class="btn btn-danger btn-sm delete-link" data-message="Are you sure you want to delete this user?">
                          <i class="bi bi-trash"></i> Delete
                        </a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <!-- User Statistics Section -->
      <section id="user-stats-section" class="content-section mb-5">
        <div class="card shadow">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">User Statistics</h4>
          </div>
          <div class="card-body">
            <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
              <canvas id="userStatsChart"></canvas>
            </div>
          </div>
        </div>
      </section>

      <!-- Feedback Section -->
      <section id="feedback-section" class="content-section">
        <div class="card shadow">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">User Feedback</h4>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th style="width: 10%;">User</th>
                    <th style="width: 10%;">Usability</th>
                    <th style="width: 10%;">Design</th>
                    <th style="width: 10%;">Features</th>
                    <th style="width: 10%;">Satisfaction</th>
                    <th style="width: 10%;">Avg Rating</th>
                    <th style="width: 20%;">Comments</th>
                    <th style="width: 10%;">Date</th>
                    <th style="width: 20%;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($feedback = $feedback_result->fetch_assoc()): ?>
                    <tr>
                      <td><?= htmlspecialchars($feedback['full_name']) ?></td>
                      <td><?= $feedback['usability'] ?></td>
                      <td><?= $feedback['design'] ?></td>
                      <td><?= $feedback['features'] ?></td>
                      <td><?= $feedback['satisfaction'] ?></td>
                      <td><?= number_format($feedback['avg_rating'], 1) ?></td>
                      <td><?= htmlspecialchars($feedback['comments']) ?></td>
                      <td><?= date('M d, Y', strtotime($feedback['created_at'])) ?></td>
                      <td>
                        <a href="?delete_feedback_id=<?= $feedback['id'] ?>" class="btn btn-danger btn-sm delete-link" data-message="Are you sure you want to delete this feedback?">
                          <i class="bi bi-trash"></i> Delete
                        </a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>
    </div>
  </main>

  <!-- Custom Professional Confirmation Modal -->
  <div id="customModal">
    <div class="modal-content">
      <p id="modalMessage">Are you sure?</p>
      <div class="modal-buttons">
        <button id="modalConfirm">Yes</button>
        <button id="modalCancel">Cancel</button>
      </div>
    </div>
  </div>

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright <strong><span>ForumAdmin</span></strong>. All Rights Reserved
    </div>
    <div class="credits"></div>
  </footer>

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
    <i class="bi bi-arrow-up-short"></i>
  </a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/chart.js/chart.umd.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>
  <script>
    // Prepare PHP data for the chart
    const userStatsLabels = <?= json_encode($user_stats_labels) ?>;
    const userStatsPostCount = <?= json_encode($user_stats_post_count) ?>;
    const userStatsLikes = <?= json_encode($user_stats_likes) ?>;
    const userStatsDislikes = <?= json_encode($user_stats_dislikes) ?>;

    // Initialize the bar chart
    const userStatsCtx = document.getElementById('userStatsChart').getContext('2d');
    const userStatsChart = new Chart(userStatsCtx, {
      type: 'bar',
      data: {
        labels: userStatsLabels,
        datasets: [{
            label: 'Post Count',
            data: userStatsPostCount,
            backgroundColor: 'rgba(54, 162, 235, 0.8)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
          },
          {
            label: 'Total Likes',
            data: userStatsLikes,
            backgroundColor: 'rgba(75, 192, 192, 0.8)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
          },
          {
            label: 'Total Dislikes',
            data: userStatsDislikes,
            backgroundColor: 'rgba(255, 99, 132, 0.8)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true }
        },
        plugins: {
          legend: { position: 'top' },
          title: { display: true, text: 'User Activity Based on Posts, Likes, and Dislikes' }
        }
      }
    });

    // Function to show only the selected section
    function showSection(sectionId, heading) {
      document.querySelector('.pagetitle').firstElementChild.textContent = heading;
    //  document.querySelector('.breadcrumb').children[1].textContent = heading;
      document.querySelectorAll('.content-section').forEach(section => {
        section.style.display = 'none';
      });
      const selectedSection = document.getElementById(sectionId);
      if (selectedSection) {
        selectedSection.style.display = 'block';
      } else {
        console.error("Section not found:", sectionId);
      }
    }

    // Custom professional modal functionality
    let deleteUrl = "";
    // Attach click event to all links with class 'delete-link'
    document.querySelectorAll('.delete-link').forEach(link => {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        deleteUrl = this.href;
        const message = this.getAttribute('data-message') || 'Are you sure?';
        document.getElementById('modalMessage').textContent = message;
        document.getElementById('customModal').style.display = 'block';
      });
    });

    // Confirmation action
    document.getElementById('modalConfirm').addEventListener('click', function () {
      window.location.href = deleteUrl;
    });

    // Cancellation action
    document.getElementById('modalCancel').addEventListener('click', function () {
      document.getElementById('customModal').style.display = 'none';
      deleteUrl = "";
    });

    // Hide modal if clicking outside of the modal content
    window.addEventListener('click', function (event) {
      const modal = document.getElementById('customModal');
      if (event.target == modal) {
        modal.style.display = 'none';
        deleteUrl = "";
      }
    });
  </script>
</body>

</html>
