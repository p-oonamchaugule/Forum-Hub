<?php
session_start();
require("config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        die("Unauthorized: You must be logged in to reply.");
    }

    $post_id = intval($_POST['post_id']);
    $comment_id = intval($_POST['comment_id']);
    $user_id = $_SESSION['user_id'];
    $reply = trim($_POST['reply']);

    if (empty($reply)) {
        die("Reply cannot be empty.");
    }

    // Insert the reply into the replies table
    $sql = "INSERT INTO replies (comment_id, user_id, reply, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);

    // Bind the parameters
    $stmt->bind_param("iis", $comment_id, $user_id, $reply);

    if ($stmt->execute()) {
        header("Location: post-detail.php?id=$post_id");
        exit;
    } else {
        die("Error adding reply: " . $conn->error);
    }
} else {
    die("Invalid request method.");
}
