<?php
// Mengatur keranjang di session, bukan di memori JS
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Pastikan session sudah dimulai (sudah ada di config.php)
// session_start();

// Inisialisasi cart jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

 $method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $product_id = $input['product_id'] ?? 0;
    $quantity = $input['quantity'] ?? 1;

    if ($action === 'add') {
        // Ambil data produk dari DB untuk memastikan info terkini
        $stmt = $conn->prepare("SELECT id, name, price, stock, image FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();

        if ($product && $product['stock'] >= $quantity) {
            if (isset($_SESSION['cart'][$product_id])) {
                // Cek stok lagi jika produk sudah ada di cart
                if ($_SESSION['cart'][$product_id]['quantity'] + $quantity <= $product['stock']) {
                    $_SESSION['cart'][$product_id]['quantity'] += $quantity;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi!']);
                    exit;
                }
            } else {
                $_SESSION['cart'][$product_id] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'image' => $product['image'],
                    'quantity' => $quantity
                ];
            }
            echo json_encode(['success' => true, 'cart' => array_values($_SESSION['cart'])]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan atau stok habis.']);
        }
    } elseif ($action === 'update') {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$product_id]);
            }
        }
        echo json_encode(['success' => true, 'cart' => array_values($_SESSION['cart'])]);
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
        echo json_encode(['success' => true, 'cart' => []]);
    }
} elseif ($method === 'GET') {
    echo json_encode(['success' => true, 'cart' => array_values($_SESSION['cart'])]);
}
?>