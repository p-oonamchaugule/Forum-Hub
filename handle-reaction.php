<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ./index.php"); // Redirect to the index page
    exit();
  }


require("config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'], $_POST['reaction'], $_SESSION['user_id'])) {
    $post_id = intval($_POST['post_id']);
    $reaction = $_POST['reaction'];
    $user_id = $_SESSION['user_id'];

    // Check if the user already reacted to the post
    $check_reaction_sql = "SELECT * FROM reactions WHERE post_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_reaction_sql);
    $check_stmt->bind_param("ii", $post_id, $user_id);
    $check_stmt->execute();
    $existing_reaction = $check_stmt->get_result()->fetch_assoc();

    if ($existing_reaction) {
        // Update existing reaction
        $update_sql = "UPDATE reactions SET reaction = ?, created_at = CURRENT_TIMESTAMP WHERE post_id = ? AND user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sii", $reaction, $post_id, $user_id);
        $update_stmt->execute();
    } else {
        // Insert new reaction
        $insert_sql = "INSERT INTO reactions (post_id, user_id, reaction) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iis", $post_id, $user_id, $reaction);
        $insert_stmt->execute();
    }
}
