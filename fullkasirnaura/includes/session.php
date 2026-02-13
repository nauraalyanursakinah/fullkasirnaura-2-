<?php
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function checkRole($required_role) {
    if (!isLoggedIn()) {
        header("Location: ../index.php");
        exit();
    }
    
    if ($_SESSION['role'] !== $required_role && $_SESSION['role'] !== 'admin') {
        // Admin can access all pages
        if ($_SESSION['role'] !== 'admin') {
            header("Location: ../index.php");
            exit();
        }
    }
}

// Get current user data
function getCurrentUser() {
    if (isLoggedIn()) {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    return null;
}
?>