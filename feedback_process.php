<?php
session_start(); // Start the session
require("config.php");

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "You need to log in to submit feedback.";
    $_SESSION['message_type'] = "danger";
    header("Location: login.php"); // Redirect to login page
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $usability = isset($_POST['usability']) ? (int)$_POST['usability'] : 0;
    $design = isset($_POST['design']) ? (int)$_POST['design'] : 0;
    $features = isset($_POST['features']) ? (int)$_POST['features'] : 0;
    $satisfaction = isset($_POST['satisfaction']) ? (int)$_POST['satisfaction'] : 0;
    $comments = isset($_POST['comments']) ? $conn->real_escape_string($_POST['comments']) : '';
    $user_id = $_SESSION['user_id']; // Get user_id from session

    // Validate ratings (ensure they are between 1 and 5)
    if ($usability < 1 || $usability > 5 || $design < 1 || $design > 5 || $features < 1 || $features > 5 || $satisfaction < 1 || $satisfaction > 5) {
        $_SESSION['message'] = "Please provide valid ratings (1 to 5) for all questions.";
        $_SESSION['message_type'] = "danger";
    } else {
        // Calculate the average rating
        $avg_rating = ($usability + $design + $features + $satisfaction) / 4;

        // Insert feedback into the database
        $sql = "INSERT INTO feedback (usability, design, features, satisfaction, avg_rating, comments, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        // Use prepared statements to prevent SQL injection
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("iiiiisi", $usability, $design, $features, $satisfaction, $avg_rating, $comments, $user_id);

            // Execute the query and check for success
            if ($stmt->execute()) {
                $_SESSION['message'] = "Thank you for your feedback! We appreciate your input.";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error: " . $stmt->error;
                $_SESSION['message_type'] = "danger";
            }

            // Close the statement
            $stmt->close();
        } else {
            $_SESSION['message'] = "Error preparing the SQL statement.";
            $_SESSION['message_type'] = "danger";
        }
    }

    // Close the database connection
    $conn->close();

    // Redirect back to the feedback form
    header("Location: index.php"); // Replace with the actual page name
    exit();
} else {
    // Invalid request method
    $_SESSION['message'] = "Invalid request method.";
    $_SESSION['message_type'] = "danger";
    header("Location: index.php"); // Replace with the actual page name
    exit();
}
?>