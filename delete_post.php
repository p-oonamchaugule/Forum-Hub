<?php
require("config.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Check if post ID is set and user is trying to delete
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $post_id = intval($_GET['delete_id']);
    $user_id = $_SESSION['user_id'];
    
    // Query to delete the post where the user_id matches
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $post_id, $user_id);

    if ($stmt->execute()) {
        // Success message with alert
        echo "<script>
                alert('Post deleted successfully!');
                window.location.href = 'Postcreation.php';
              </script>";
        exit();
    } else {
        $error_message = "Failed to delete post.";
    }

    $stmt->close();
}
?>
