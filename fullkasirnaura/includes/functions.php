<?php
require_once 'config.php';

// Clean input
function clean($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function checkRole($role) {
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
    
    if ($_SESSION['user_role'] !== $role) {
        header("Location: index.php");
        exit();
    }
}

// Get user data
function getUser($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Get all products
function getProducts($category = null) {
    global $conn;
    
    if ($category && $category !== 'all') {
        $stmt = $conn->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            WHERE c.name = ?
        ");
        $stmt->bind_param("s", $category);
    } else {
        $stmt = $conn->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            JOIN categories c ON p.category_id = c.id
        ");
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get all categories
function getCategories() {
    global $conn;
    $result = $conn->query("SELECT * FROM categories");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get transactions
function getTransactions($limit = null) {
    global $conn;
    
    $query = "
        SELECT t.*, u.username 
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
        ORDER BY t.created_at DESC
    ";
    
    if ($limit) {
        $query .= " LIMIT $limit";
    }
    
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Generate transaction code
function generateTransactionCode() {
    return 'TRX' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Show notification
function showNotification($message, $type = 'info') {
    $_SESSION['notification'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Display notification
function displayNotification() {
    if (isset($_SESSION['notification'])) {
        $notif = $_SESSION['notification'];
        $alertClass = $notif['type'] === 'success' ? 'bg-green-500' : 
                     ($notif['type'] === 'error' ? 'bg-red-500' : 'bg-blue-500');
        
        echo "
        <div class='fixed top-4 right-4 px-6 py-3 rounded-lg text-white fade-in z-50 $alertClass'>
            <div class='flex items-center'>
                <i class='fas fa-" . ($notif['type'] === 'success' ? 'check-circle' : 
                ($notif['type'] === 'error' ? 'exclamation-circle' : 'info-circle')) . " mr-2'></i>
                {$notif['message']}
            </div>
        </div>";
        
        unset($_SESSION['notification']);
    }
}
?>