<?php
session_start();
require("config.php");

if (isset($_GET['delete_post_id'])) {
    $post_id = intval($_GET['delete_post_id']);
    // Delete the post
    $delete_query = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $delete_query->bind_param("i", $post_id);
    $delete_query->execute();
    
    if ($delete_query->affected_rows > 0) {
        header("Location: admin-panel.php?message=Post deleted successfully");
    } else {
        header("Location: admin-panel.php?error=Failed to delete post");
    }
    exit;
}

if (isset($_GET['delete_comment_id'])) {
    $comment_id = intval($_GET['delete_comment_id']);
    // Delete the comment
    $delete_query = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $delete_query->bind_param("i", $comment_id);
    $delete_query->execute();
    
    if ($delete_query->affected_rows > 0) {
        header("Location: admin-panel.php?message=Comment deleted successfully");
    } else {
        header("Location: admin-panel.php?error=Failed to delete comment");
    }
    exit;
}

if (isset($_GET['delete_user_id'])) {
    $user_id = intval($_GET['delete_user_id']);
    // Delete the user
    $delete_query = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $delete_query->bind_param("i", $user_id);
    $delete_query->execute();
    
    if ($delete_query->affected_rows > 0) {
        header("Location: admin-panel.php?message=User deleted successfully");
    } else {
        header("Location: admin-panel.php?error=Failed to delete user");
    }
    exit;
}
?>
