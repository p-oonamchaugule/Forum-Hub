<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ./index.php"); // Redirect to the index page
    exit();
  }


// Check if the user is logged in as an admin
if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
    // Destroy the session to log out the admin
    session_destroy(); // Destroy all session data
    header("Location: index.php"); // Redirect to the index page
    exit();
}
?>

