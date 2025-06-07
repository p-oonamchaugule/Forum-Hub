<?php
session_start();
require("config.php");

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must log in to delete a comment.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment_id = intval($_POST['comment_id']);
    $user_id = $_SESSION['user_id'];

    // Check if the comment belongs to the logged-in user
    $sql = "SELECT user_id FROM comments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comment = $result->fetch_assoc();

    if ($comment && $comment['user_id'] === $user_id) {
        // Delete the comment
        $delete_sql = "DELETE FROM comments WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $comment_id);
        $delete_stmt->execute();
        header("Location: post-detail.php?id=" . $_POST['post_id']);
    } else {
        die("Unauthorized access.");
    }
}
?>
