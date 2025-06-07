<?php

session_start();
require("config.php");

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$post_id = $_GET['id'];

// Fetch the post data from the database
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $post_id, $_SESSION['user_id']);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    header("Location: Postcreation.php");
    exit();
}

$error_message = $success_message = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_post'])) {
    $title = htmlspecialchars(trim($_POST['title']));
    $content = htmlspecialchars(trim($_POST['content']));
    $category = htmlspecialchars(trim($_POST['category']));
    $custom_category = htmlspecialchars(trim($_POST['custom_category'] ?? ''));
    $privacy = htmlspecialchars(trim($_POST['privacy'])); // Get privacy setting

    // Validate category
    if ($category === 'Custom' && empty($custom_category)) {
        $error_message = "Custom category cannot be empty.";
    } else {
        $category = $category === 'Custom' ? $custom_category : $category;
    }

    // Handle image upload
    $image = $post['image'];
    if (!empty($_FILES['image']['name'])) {
        $imageExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($imageExtension, $allowedImageTypes)) {
            $image = uniqid("img_") . ".$imageExtension";
            if (!move_uploaded_file($_FILES['image']['tmp_name'], "uploads/$image")) {
                $error_message = "Failed to upload the image.";
            }
        } else {
            $error_message = "Invalid image file type. Allowed types: jpg, jpeg, png, gif.";
        }
    }

    // Handle video upload
    $video = $post['video'];
    if (!empty($_FILES['video']['name'])) {
        $videoExtension = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
        $allowedVideoTypes = ['mp4', 'avi', 'mkv'];

        if (in_array($videoExtension, $allowedVideoTypes)) {
            $video = uniqid("vid_") . ".$videoExtension";
            if (!move_uploaded_file($_FILES['video']['tmp_name'], "uploads/$video")) {
                $error_message = "Failed to upload the video.";
            }
        } else {
            $error_message = "Invalid video file type. Allowed types: mp4, avi, mkv.";
        }
    }

    // Update post in the database
    if (!$error_message) {
        $stmt = $conn->prepare("UPDATE posts SET title = ?, content = ?, image = ?, video = ?, category = ?, privacy = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssssssii", $title, $content, $image, $video, $category, $privacy, $post_id, $_SESSION['user_id']);

        if ($stmt->execute()) {
            $success_message = "Post updated successfully!";
        } else {
            $error_message = "Failed to update post.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .navbar-brand .logo-container {
            display: flex;
            align-items: center;
            gap: 10px;
            transition: transform 0.3s ease-in-out;
        }
       /* Add these styles to your existing CSS */
.navbar-toggler {
    border-color: rgba(0,0,0,0.5); /* Black border */
}

.navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(0, 0, 0, 0.8)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}

/* Darken the icon on hover/focus */
.navbar-toggler:hover .navbar-toggler-icon,
.navbar-toggler:focus .navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(0, 0, 0, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}
        .navbar-brand .logo-img {
            border-radius: 50%;
            border: 2px solid #00aaff;
        }

        .navbar-brand .logo-text {
            color: #007bff;
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 1px;
            background: linear-gradient(90deg, #6ec1e4, #00aaff, #80d4ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradient 5s linear infinite;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .navbar-brand .logo-container:hover {
            transform: scale(1.05);
        }

        .navbar-brand .logo-container:hover .logo-text {
            text-shadow: 0px 0px 5px #80d4ff;
        }

        .navbar-nav .nav-item .nav-link {
            color: #555;
            font-size: 1rem;
            font-weight: 500;
            padding: 10px 15px;
            transition: color 0.3s ease-in-out, background-color 0.3s ease-in-out;
            border-radius: 5px;
        }

        .navbar-nav .nav-item .nav-link:hover {
            background-color: #e6f7ff;
            color: #007bff;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark ">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <div class="logo-container">
        <img src="./assets/logo2.jpg" alt="ForumHub Logo" class="logo-img" height ="60px" >
        <span class="logo-text">ForumHub</span>
      </div>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation" style="color: black;">
                <span class="navbar-toggler-icon"style="color: black;"></span>
            </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link active" href="index.php">Home</a>
        </li>
       
      </ul>
   
      <a class="btn btn-outline-primary me-2" href="login.php">logout</a>
    
    </div>
  </div>
</nav>

<div class="container" >
    <div class="row">
        <div class="col-lg-6 mx-auto">
            <h2 class="text-center">Edit Post</h2>
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
                <script>
                    alert("Post updated successfully!");
                    setTimeout(() => window.location.href = 'Postcreation.php');
                </script>
            <?php elseif ($error_message): ?>
                <div class="alert alert-danger"> <?php echo $error_message; ?> </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="card p-4 shadow-sm">
                <div class="mb-3">
                    <label for="title">Post Title</label>
                    <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($post['title']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="content">Post Content</label>
                    <textarea id="content" name="content" rows="5" class="form-control" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="category">Category</label>
                    <select id="category" name="category" class="form-control" required onchange="toggleCustomCategory()">
                        <option value="Technology" <?php echo $post['category'] === 'Technology' ? 'selected' : ''; ?>>Technology</option>
                        <option value="Education" <?php echo $post['category'] === 'Education' ? 'selected' : ''; ?>>Education</option>
                        <option value="Entertainment" <?php echo $post['category'] === 'Entertainment' ? 'selected' : ''; ?>>Entertainment</option>
                        <option value="Lifestyle" <?php echo $post['category'] === 'Lifestyle' ? 'selected' : ''; ?>>Lifestyle</option>
                        <option value="Health" <?php echo $post['category'] === 'Health' ? 'selected' : ''; ?>>Health</option>
                        <option value="Custom" <?php echo $post['category'] === 'Custom' ? 'selected' : ''; ?>>Custom</option>
                    </select>
                    <div id="custom_category_div" style="display: <?php echo $post['category'] === 'Custom' ? 'block' : 'none'; ?>;" class="mt-2">
                        <label for="custom_category">Custom Category</label>
                        <input type="text" id="custom_category" name="custom_category" class="form-control" value="<?php echo htmlspecialchars($post['custom_category'] ?? ''); ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="image">Image (optional)</label>
                    <input type="file" id="image" name="image" class="form-control">
                    <?php if (!empty($post['image'])): ?>
                        <img src="uploads/<?php echo $post['image']; ?>" alt="Current Post Image" class="img-fluid mt-2">
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label for="video">Video (optional)</label>
                    <input type="file" id="video" name="video" class="form-control">
                    <?php if (!empty($post['video'])): ?>
                        <video muted autoplay class="img-fluid mt-2" controls>
                            <source src="uploads/<?php echo $post['video']; ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label for="privacy">Privacy</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="privacy" id="privacy_public" value="public" <?php echo ($post['privacy'] === 'public') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="privacy_public">Public</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="privacy" id="privacy_private" value="private" <?php echo ($post['privacy'] === 'private') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="privacy_private">Private</label>
                        </div>
                    </div>
                </div>
                <button type="submit" name="edit_post" class="btn btn-primary w-100">Update Post</button>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleCustomCategory() {
        const categorySelect = document.getElementById('category');
        const customCategoryDiv = document.getElementById('custom_category_div');
        customCategoryDiv.style.display = categorySelect.value === 'Custom' ? 'block' : 'none';
    }
</script>
</body>
</html>