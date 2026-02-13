<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Hanya user yang sudah login yang bisa transaksi
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $cart_items = $input['items'] ?? [];
    $payment_method = sanitize($input['payment_method']);
    $cash_amount = (int)($input['cash_amount'] ?? 0);

    if (empty($cart_items)) {
        echo json_encode(['success' => false, 'message' => 'Keranjang belanja kosong.']);
        exit;
    }

    // Hitung total harga
    $total_price = 0;
    foreach ($cart_items as $item) {
        $total_price += $item['price'] * $item['quantity'];
    }

    $change_amount = ($payment_method === 'cash' && $cash_amount > $total_price) ? $cash_amount - $total_price : 0;
    $user_id = $_SESSION['user_id'];

    // Gunakan transaksi database untuk memastikan semua data tersimpan dengan aman
    $conn->begin_transaction();

    try {
        // 1. Insert ke tabel `transactions`
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, total_price, payment_method, cash_amount, change_amount) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisii", $user_id, $total_price, $payment_method, $cash_amount, $change_amount);
        $stmt->execute();
        $transaction_id = $conn->insert_id;

        // 2. Insert ke tabel `transaction_items` dan update stok produk
        $stmt_item = $conn->prepare("INSERT INTO transaction_items (transaction_id, product_id, quantity, price_at_time) VALUES (?, ?, ?, ?)");
        $stmt_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");

        foreach ($cart_items as $item) {
            $product_id = $item['id'];
            $quantity = $item['quantity'];
            $price_at_time = $item['price'];

            // Insert item transaksi
            $stmt_item->bind_param("iiii", $transaction_id, $product_id, $quantity, $price_at_time);
            $stmt_item->execute();

            // Update stok produk
            $stmt_stock->bind_param("iii", $quantity, $product_id, $quantity);
            $stmt_stock->execute();

            // Cek apakah stok benar-benar terkurangi (jika 0 baris terpengaruh, stok tidak cukup)
            if ($stmt_stock->affected_rows === 0) {
                throw new Exception("Stok untuk produk ID {$product_id} tidak mencukupi.");
            }
        }

        // Jika semua berhasil, commit transaksi
        $conn->commit();

        // Kosongkan keranjang di session
        unset($_SESSION['cart']);

        echo json_encode(['success' => true, 'message' => 'Transaksi berhasil!', 'transaction_id' => $transaction_id]);

    } catch (Exception $e) {
        // Jika ada error, rollback semua perubahan
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Transaksi gagal: ' . $e->getMessage()]);
    }
}
?>