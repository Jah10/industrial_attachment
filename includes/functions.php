<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user type
function isUserType($type) {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == $type;
}

// Redirect user if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /industrial_attachment_system/login.php");
        exit();
    }
}

// Redirect user if not the correct user type
function requireUserType($type) {
    requireLogin();
    if (!isUserType($type)) {
        header("Location: /industrial_attachment_system/index.php?error=unauthorized");
        exit();
    }
}

// Clean input data
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Get user details
function getUserDetails($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get student details
function getStudentDetails($conn, $user_id) {
    $stmt = $conn->prepare("SELECT s.* FROM students s JOIN users u ON s.user_id = u.user_id WHERE u.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get organization details
function getOrganizationDetails($conn, $user_id) {
    $stmt = $conn->prepare("SELECT o.* FROM organizations o JOIN users u ON o.user_id = u.user_id WHERE u.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get supervisor details
function getSupervisorDetails($conn, $user_id) {
    $stmt = $conn->prepare("SELECT s.* FROM supervisors s JOIN users u ON s.user_id = u.user_id WHERE u.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get all active reminders for a user type
function getReminders($conn, $user_type) {
    $stmt = $conn->prepare("SELECT * FROM reminders WHERE (user_type = ? OR user_type = 'all') AND is_active = 1 AND due_date >= CURDATE() ORDER BY due_date ASC");
    $stmt->bind_param("s", $user_type);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Display error message
function showError($message) {
    return '<div class="alert alert-danger">' . $message . '</div>';
}

// Display success message
function showSuccess($message) {
    return '<div class="alert alert-success">' . $message . '</div>';
}
?>