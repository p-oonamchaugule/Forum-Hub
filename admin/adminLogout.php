<?php
session_start();

// Check if the user is logged in as an admin
if (isset($_SESSION['admin_id']) && $_SESSION['admin_id']) {
    // Destroy the session to log out the admin
    session_destroy(); // Destroy all session data
    header("Location: ./admin-login.php"); // Redirect to the index page
    exit();
}
?>

