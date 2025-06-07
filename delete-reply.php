<?php
session_start();
require("config.php");

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must log in to delete a reply.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reply_id = intval($_POST['reply_id']);
    $user_id = $_SESSION['user_id'];

    // Check if the reply belongs to the logged-in user
    $sql = "SELECT user_id FROM replies WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reply_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reply = $result->fetch_assoc();

    if ($reply && $reply['user_id'] === $user_id) {
        // Delete the reply
        $delete_sql = "DELETE FROM replies WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $reply_id);
        $delete_stmt->execute();
        header("Location: post-detail.php?id=" . $_POST['post_id']);
    } else {
        die("Unauthorized access.");
    }
}
?>
