<?php
session_start();
require("config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        die("Unauthorized: You must be logged in to post a comment.");
    }

    $post_id = intval($_POST['post_id']);
    $parent_id = (!empty($_POST['parent_id']) && $_POST['parent_id'] !== "null") ? intval($_POST['parent_id']) : null;
    $user_id = $_SESSION['user_id'];
    $comment = trim($_POST['comment']);

    if (empty($comment)) {
        die("Comment cannot be empty.");
    }

    // Insert the comment into the database
    $sql = "INSERT INTO comments (post_id, parent_id, user_id, comment, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);

    // Bind the parameters
    $stmt->bind_param("iiis", $post_id, $parent_id, $user_id, $comment);

    if ($stmt->execute()) {
        header("Location: post-detail.php?id=$post_id");
        exit;
    } else {
        die("Error adding comment: " . $conn->error);
    }
} else {
    die("Invalid request method.");
}
