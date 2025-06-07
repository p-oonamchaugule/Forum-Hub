<?php
session_start();
require("config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ./login.php"); // Redirect to the login page
    exit();
  }

// Fetch post details
$post_id = intval($_GET['id']);
$sql = "SELECT p.*, u.full_name FROM posts p JOIN users u ON p.user_id = u.user_id WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    die("Post not found.");
}

// Fetch comments and replies for the post
$comments_sql = "
    SELECT c.*, u.full_name
    FROM comments c
    JOIN users u ON c.user_id = u.user_id
    WHERE c.post_id = ?
    ORDER BY c.parent_id, c.created_at ASC
";
$comments_stmt = $conn->prepare($comments_sql);
$comments_stmt->bind_param("i", $post_id);
$comments_stmt->execute();
$comments = $comments_stmt->get_result();

$comments_by_parent = [];
while ($row = $comments->fetch_assoc()) {
    $comments_by_parent[$row['parent_id']][] = $row;
}

// Fetch total likes and dislikes
$likes_sql = "SELECT COUNT(*) as total_likes FROM reactions WHERE post_id = ? AND reaction = 'like'";
$likes_stmt = $conn->prepare($likes_sql);
$likes_stmt->bind_param("i", $post_id);
$likes_stmt->execute();
$total_likes = $likes_stmt->get_result()->fetch_assoc()['total_likes'];

$dislikes_sql = "SELECT COUNT(*) as total_dislikes FROM reactions WHERE post_id = ? AND reaction = 'dislike'";
$dislikes_stmt = $conn->prepare($dislikes_sql);
$dislikes_stmt->bind_param("i", $post_id);
$dislikes_stmt->execute();
$total_dislikes = $dislikes_stmt->get_result()->fetch_assoc()['total_dislikes'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <link rel="stylesheet" href="css/postdetails.css">

    <style></style>
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
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link active" href="index.php">Home</a>
        </li>
       
      </ul>
   
      <?php
        if(isset($_SESSION['user_id'])){
            echo '<a class="btn btn-secondary me-2" href="./logout.php">Logout</a>';
        }else{
            echo '<a class="btn btn-outline-primary me-2" href="login.php">Login</a>
                    <a class="btn btn-primary" id="button" href="signup.php">Sign Up</a>';
        }
        
      ?>
      
    </div>
  </div>
</nav>

<div class="container mt-5">
    <div class="row">
        <div class="col-12 col-md-8">
            <h1><?php echo htmlspecialchars($post['title']); ?></h1>
            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>

            <div class="row mt-3">
                <div class="col-md-6">
                    <?php if (!empty($post['image'])): ?>
                        <img src="uploads/<?php echo $post['image']; ?>" alt="Post Image" class="img-fluid">
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <?php if (!empty($post['video'])): ?>
                        <video muted autoplay class="img-fluid" controls>
                            <source src="uploads/<?php echo $post['video']; ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="mt-4">
                    <button type="button" class="btn btn-success me-2" id="like-btn">
                        <i class="fas fa-thumbs-up"></i> Like (<?php echo $total_likes; ?>)
                    </button>
                    <button type="button" class="btn btn-danger" id="dislike-btn">
                        <i class="fas fa-thumbs-down"></i> Dislike (<?php echo $total_dislikes; ?>)
                    </button>
                </div>
            <?php else: ?>
                <p class="text-muted mt-3">You must <a href="login.php">log in</a> to like or dislike.</p>
            <?php endif; ?>

 <!-- Comments Section -->
            <h3 class="mt-5">Comments</h3>
            <?php if (!empty($comments_by_parent[null])): ?>
                <ul class="list-group">
                    <?php foreach ($comments_by_parent[null] as $comment): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <strong><?php echo htmlspecialchars($comment['full_name']); ?>:</strong>
                                <small class="text-muted"><?php echo $comment['created_at']; ?></small>
                                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $comment['user_id']): ?>
                                    <form action="delete-comment.php" method="POST" class="d-inline">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <?php echo htmlspecialchars($comment['comment']); ?>

                            <!-- Replies -->
                            <?php
                                $replies_sql = "SELECT r.*, u.full_name FROM replies r JOIN users u ON r.user_id = u.user_id WHERE r.comment_id = ?";
                                $replies_stmt = $conn->prepare($replies_sql);
                                $replies_stmt->bind_param("i", $comment['id']);
                                $replies_stmt->execute();
                                $replies = $replies_stmt->get_result();

                                if ($replies->num_rows > 0): 
                            ?>
                                <ul class="list-group mt-2">
                                    <?php while ($reply = $replies->fetch_assoc()): ?>
                                        <li class="list-group-item">
                                            <div class="d-flex justify-content-between">
                                                <strong><?php echo htmlspecialchars($reply['full_name']); ?>:</strong>
                                                <small class="text-muted"><?php echo $reply['created_at']; ?></small>
                                                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $reply['user_id']): ?>
                                                    <form action="delete-reply.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="reply_id" value="<?php echo $reply['id']; ?>">
                                                        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                            <?php echo htmlspecialchars($reply['reply']); ?>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php endif; ?>

                            <!-- Reply Form -->
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <form method="POST" action="add-reply.php" class="mt-2">
                                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                                    <div class="input-group">
                                        <input type="text" name="reply" class="form-control" placeholder="Reply..." required>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-reply"></i> Post
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <p class="text-muted mt-3">You must <a href="login.php">log in</a> to reply.</p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No comments yet. Be the first to comment!</p>
            <?php endif; ?>

            <!-- Add a top-level comment -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST" action="add-comment.php" class="mt-5">
                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                    <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                    <div class="mb-3">
                        <label for="comment" class="form-label">Add a Comment</label>
                        <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Post Comment</button>
                </form>
            <?php else: ?>
                <p class="text-muted mt-3">You must <a href="login.php">log in</a> to comment.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('like-btn').addEventListener('click', function() {
        handleReaction('like');
    });

    document.getElementById('dislike-btn').addEventListener('click', function() {
        handleReaction('dislike');
    });

    function handleReaction(reaction) {
        fetch('handle-reaction.php', {
            method: 'POST',
            body: new URLSearchParams({
                'post_id': '<?php echo $post_id; ?>',
                'reaction': reaction
            }),
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        })
        .then(response => response.text())
        .then(result => {
            console.log(result); // handle response
            location.reload(); // reload to update the counts
        });
    }
});
</script>
