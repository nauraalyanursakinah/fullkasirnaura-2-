<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

 $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;

 $sql = "SELECT p.id, p.name, p.price, p.stock, p.image, c.name as category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id";

if ($category_id) {
    $sql .= " WHERE p.category_id = ?";
}

 $stmt = $conn->prepare($sql);

if ($category_id) {
    $stmt->bind_param("i", $category_id);
}

 $stmt->execute();
 $result = $stmt->get_result();

 $products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);
?>