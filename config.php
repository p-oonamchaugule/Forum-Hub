<?php
$servername = "localhost";
$username = "root";
$password = "";  // leave it blank for XAMPP default
$dbname = "forum_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
