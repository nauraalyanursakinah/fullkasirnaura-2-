<?php
// Allow requests from any origin (for development)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../includes/config.php';

// Query untuk mengambil semua data menu
// Sesuaikan nama tabel dan kolom jika berbeda
 $sql = "SELECT m.id, m.name, m.price, m.stock, m.image, c.name as category_name 
        FROM menu m 
        LEFT JOIN categories c ON m.category_id = c.id 
        ORDER BY c.name ASC, m.name ASC";
 $result = $conn->query($sql);

 $menu_items = array();

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $menu_items[] = $row;
    }
}

// Kembalikan data dalam format JSON
echo json_encode($menu_items);

 $conn->close();
?>