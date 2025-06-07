<?php
session_start();
require("config.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: ./admin-login.php"); // Redirect to the index page
    exit();
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

// Check if the query executed successfully
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

// Handle delete actions
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/admindash.css">
    <style>
        /* Ensure the chart container is responsive */
        .chart-container {
            position: relative;
            height: 300px;
            /* Fixed height for the chart */
            width: 100%;
        }

        /* Adjust chart height for mobile devices */
        @media (max-width: 768px) {
            .chart-container {
                height: 250px;
                /* Slightly smaller height for mobile */
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="main-navbar navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <div class="logo-container">
                    <img src="../assets/logo2.jpg" class="logo-img" alt="Logo">
                    <span class="logo-text">ForumHub Admin</span>
                </div>
            </a>

            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler" type="button" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>

            <!-- Desktop Logout -->
            <div class="desktop-logout">
                <a class="btn btn-outline-danger" href="./adminLogout.php">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <h5 class="sidebar-header">Admin Dashboard</h5>
        <div class="list-group">
            <a href="#posts-section" class="list-group-item list-group-item-action" onclick="showSection('posts-section')">Posts</a>
            <a href="#comments-section" class="list-group-item list-group-item-action" onclick="showSection('comments-section')">Comments</a>
            <a href="#users-section" class="list-group-item list-group-item-action" onclick="showSection('users-section')">Users</a>
            <a href="#user-stats-section" class="list-group-item list-group-item-action" onclick="showSection('user-stats-section')">User Statistics</a>
            <a href="#feedback-section" class="list-group-item list-group-item-action" onclick="showSection('feedback-section')">Feedback</a>

            <!-- Mobile Logout in Sidebar -->
            <div class="mobile-logout">
                <a class="btn btn-outline-danger btn-block" href="./adminLogout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
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
                                            <a href="?delete_post_id=<?= $post['id'] ?>" class="btn btn-danger btn-sm">
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
                                            <a href="?delete_comment_id=<?= $comment['id'] ?>" class="btn btn-danger btn-sm">
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
                                            <a href="?delete_user_id=<?= $user['user_id'] ?>" class="btn btn-danger btn-sm">
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
                                    <th style="width: 15%;">User</th>
                                    <th style="width: 10%;">Usability</th>
                                    <th style="width: 10%;">Design</th>
                                    <th style="width: 10%;">Features</th>
                                    <th style="width: 10%;">Satisfaction</th>
                                    <th style="width: 10%;">Avg Rating</th>
                                    <th style="width: 20%;">Comments</th>
                                    <th style="width: 10%;">Date</th>
                                    <th style="width: 15%;">Actions</th>
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
                                            <a href="?delete_feedback_id=<?= $feedback['id'] ?>" class="btn btn-danger btn-sm">
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

    <!-- Scroll to Top Button -->
    <button class="scroll-to-top">
        <i class="bi bi-arrow-up-short" style="font-size: 1.5rem;"></i>
    </button>

    <script>
        // Pass PHP data to JavaScript
        const userStatsLabels = <?= json_encode($user_stats_labels) ?>;
        const userStatsPostCount = <?= json_encode($user_stats_post_count) ?>;
        const userStatsLikes = <?= json_encode($user_stats_likes) ?>;
        const userStatsDislikes = <?= json_encode($user_stats_dislikes) ?>;

        console.log("User Stats Labels:", userStatsLabels);
        console.log("User Stats Post Count:", userStatsPostCount);
        console.log("User Stats Likes:", userStatsLikes);
        console.log("User Stats Dislikes:", userStatsDislikes);

        // Initialize the bar chart
        const userStatsCtx = document.getElementById('userStatsChart').getContext('2d');
        const userStatsChart = new Chart(userStatsCtx, {
            type: 'bar',
            data: {
                labels: userStatsLabels,
                datasets: [{
                        label: 'Post Count',
                        data: userStatsPostCount,
                        backgroundColor: 'rgba(54, 162, 235, 0.8)', // Darker blue
                        borderColor: 'rgba(54, 162, 235, 1)', // Solid blue
                        borderWidth: 1
                    },
                    {
                        label: 'Total Likes',
                        data: userStatsLikes,
                        backgroundColor: 'rgba(75, 192, 192, 0.8)', // Darker teal
                        borderColor: 'rgba(75, 192, 192, 1)', // Solid teal
                        borderWidth: 1
                    },
                    {
                        label: 'Total Dislikes',
                        data: userStatsDislikes,
                        backgroundColor: 'rgba(255, 99, 132, 0.8)', // Darker pink
                        borderColor: 'rgba(255, 99, 132, 1)', // Solid pink
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true, // Make the chart responsive
                maintainAspectRatio: false, // Allow the chart to scale
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'User Activity Based on Posts, Likes, and Dislikes'
                    }
                }
            }
        });

        // Function to toggle sidebar
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('mobile-show');
        }

        // Function to show only the selected section
        function showSection(sectionId) {
            console.log("Showing section:", sectionId); // Debugging line
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.style.display = 'none';
            });

            // Show the selected section
            const selectedSection = document.getElementById(sectionId);
            if (selectedSection) {
                selectedSection.style.display = 'block';
            } else {
                console.error("Section not found:", sectionId); // Debugging line
            }
        }

        // Scroll to Top Functionality
        window.addEventListener('scroll', () => {
            const scrollBtn = document.querySelector('.scroll-to-top');
            scrollBtn.classList.toggle('show', window.scrollY > 300);
        });

        document.querySelector('.scroll-to-top').addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Smooth scroll for sidebar navigation
        document.querySelectorAll('.list-group-item').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            });
        });
    </script>
</body>

</html>