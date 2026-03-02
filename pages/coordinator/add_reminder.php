<?php
// add_reminder.php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Check if user is coordinator
requireUserType('coordinator');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = cleanInput($_POST['title']);
    $description = cleanInput($_POST['description']);
    $due_date = $_POST['due_date'];
    $user_type = cleanInput($_POST['user_type']);
    
    // Insert reminder
    $insert_query = "INSERT INTO reminders (title, description, due_date, user_type) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssss", $title, $description, $due_date, $user_type);
    
    if ($stmt->execute()) {
        header("Location: dashboard.php?success=Reminder+added+successfully");
        exit();
    } else {
        header("Location: dashboard.php?error=Error+adding+reminder:+" . urlencode($conn->error));
        exit();
    }
} else {
    // Invalid request
    header("Location: dashboard.php");
    exit();
}
?>