<?php
session_start(); // Start the session
if (!isset($_SESSION['user_id'])) {
    header("Location: ./login.php"); // Redirect to the login page
    exit();
}
require("config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize it
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);

    // Validate input
    if (empty($name) || empty($email) || empty($message)) {
        $_SESSION['message'] = "Please fill all the fields.";
        $_SESSION['message_type'] = "error"; // Error message type
        header("Location: index.php#contact");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Invalid email format.";
        $_SESSION['message_type'] = "error"; // Error message type
        header("Location: index.php#contact");
        exit();
    }

    // Prepare and bind the SQL statement
    $stmt = $conn->prepare("INSERT INTO contacts (name, email, message) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $message);

    // Execute the statement
    if ($stmt->execute()) {
        $_SESSION['message'] = "Message sent successfully!";
        $_SESSION['message_type'] = "success"; // Success message type
    } else {
        $_SESSION['message'] = "Error: " . $stmt->error;
        $_SESSION['message_type'] = "error"; // Error message type
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();

    // Redirect back to the contact form
    header("Location: index.php#contact");
    exit();
} else {
    // Redirect to the contact form if the form is not submitted
    header("Location: index.php#contact");
    exit();
}
?>