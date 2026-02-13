<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole('kasir');

// Handle cart operations (tidak berubah)
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_to_cart':
            $product_id = $_POST['product_id'];
            // MODIFIED: Mengambil data produk langsung dari DB untuk memastikan stok terkini
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            
            if ($product['stock'] <= 0) {
                showNotification('Stok habis!', 'error');
            } else {
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                
                $found = false;
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['id'] == $product_id) {
                        if ($item['quantity'] < $product['stock']) {
                            $item['quantity']++;
                        } else {
                            showNotification('Stok tidak mencukupi!', 'error');
                        }
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $_SESSION['cart'][] = [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'stock' => $product['stock'],
                        'quantity' => 1
                    ];
                }
                
                showNotification($product['name'] . ' ditambahkan ke keranjang', 'success');
            }
            break;
            
        case 'update_quantity':
            $product_id = $_POST['product_id'];
            $change = $_POST['change'];
            
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] == $product_id) {
                    $new_quantity = $item['quantity'] + $change;
                    if ($new_quantity > 0 && $new_quantity <= $item['stock']) {
                        $item['quantity'] = $new_quantity;
                    }
                    break;
                }
            }
            break;
            
        case 'remove_from_cart':
            $product_id = $_POST['product_id'];
            $_SESSION['cart'] = array_filter($_SESSION['cart'], fn($item) => $item['id'] != $product_id);
            showNotification('Item dihapus dari keranjang', 'info');
            break;
            
        case 'clear_cart':
            unset($_SESSION['cart']);
            showNotification('Keranjang dikosongkan', 'info');
            break;
            
        case 'process_payment':
            $payment_method = $_POST['payment_method'];
            $cash_amount = $_POST['cash_amount'] ?? 0;
            
            $subtotal = 0;
            foreach ($_SESSION['cart'] as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
            $tax = $subtotal * 0.1;
            $total = $subtotal + $tax;
            
            if ($payment_method === 'cash' && $cash_amount < $total) {
                showNotification('Uang tidak mencukupi!', 'error');
            } else {
                // Create transaction
                $transaction_code = generateTransactionCode();
                $stmt = $conn->prepare("INSERT INTO transactions (transaction_code, user_id, total_amount, payment_method) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sids", $transaction_code, $_SESSION['user_id'], $total, $payment_method);
                $stmt->execute();
                $transaction_id = $stmt->insert_id;
                
                // Add transaction items
                foreach ($_SESSION['cart'] as $item) {
                    $stmt = $conn->prepare("INSERT INTO transaction_items (transaction_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiid", $transaction_id, $item['id'], $item['quantity'], $item['price']);
                    $stmt->execute();
                    
                    // Update stock
                    $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $stmt->bind_param("ii", $item['quantity'], $item['id']);
                    $stmt->execute();
                }
                
                unset($_SESSION['cart']);
                showNotification('Pembayaran berhasil!', 'success');
            }
            break;
    }
    
    header("Location: index.php");
    exit();
}

// MODIFIED: Hapus pemanggilan getProducts() karena akan diambil oleh JS
// $products = getProducts(); 
 $categories = getCategories(); // Kategori tetap di-load PHP
 $cart = $_SESSION['cart'] ?? [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - Naura Cofe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hover-scale { transition: transform 0.2s; }
        .hover-scale:hover { transform: scale(1.05); }
        .text-naura-blue { color: #4F46E5; }
        .bg-naura-blue { background-color: #4F46E5; }
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <div class="flex items-center">
                    <i class="fas fa-coffee text-2xl text-purple-600 mr-2"></i>
                    <h1 class="text-xl font-bold text-gray-800">Naura Cofe</h1>
                </div>
                <span class="text-sm text-gray-600">Kasir: <?php echo $_SESSION['username']; ?></span>
            </div>
            <a href="../logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </a>
        </div>
    </header>

    <?php displayNotification(); ?>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Products Section -->
            <div class="lg:col-span-2">
                <!-- Category Tabs -->
                <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                    <div class="flex flex-wrap gap-2 items-center" id="categoryTabs">
                        <button onclick="filterCategory('all')" class="category-btn px-4 py-2 rounded-lg bg-naura-blue text-white transition">
                            <i class="fas fa-th mr-2"></i>Semua
                        </button>
                        <?php foreach ($categories as $category): ?>
                            <button onclick="filterCategory('<?php echo htmlspecialchars($category['name']); ?>')" 
                                    class="category-btn px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 transition">
                                <i class="fas <?php echo $category['icon']; ?> mr-2"></i><?php echo ucfirst(htmlspecialchars($category['name'])); ?>
                            </button>
                        <?php endforeach; ?>
                        <!-- MODIFIED: Tombol Refresh Menu -->
                        <button onclick="loadMenuItems()" class="ml-auto px-4 py-2 rounded-lg bg-green-500 text-white hover:bg-green-600 transition">
                            <i class="fas fa-sync-alt mr-2"></i>Refresh Menu
                        </button>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <!-- MODIFIED: Grid ini akan diisi oleh JavaScript -->
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4" id="productsGrid">
                        <!-- Isi awal akan ditimpa oleh JavaScript -->
                        <p class="text-center col-span-full text-gray-500">Memuat menu...</p>
                    </div>
                </div>  
            </div>

            <!-- Cart Section (Tidak berubah) -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm p-4 sticky top-4">
                    <h2 class="text-lg font-bold mb-4">
                        <i class="fas fa-shopping-cart mr-2"></i>Keranjang
                    </h2>
                    
                    <div id="cartItems" class="max-h-96 overflow-y-auto mb-4">
                        <?php if (empty($cart)): ?>
                            <p class="text-gray-500 text-center py-4">Keranjang kosong</p>
                        <?php else: ?>
                            <?php foreach ($cart as $item): ?>
                                <div class="flex justify-between items-center mb-2 pb-2 border-b">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-sm"><?php echo htmlspecialchars($item['name']); ?></h4>
                                        <p class="text-sm text-gray-600">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?> x <?php echo $item['quantity']; ?></p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="update_quantity">
                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                            <input type="hidden" name="change" value="-1">
                                            <button type="submit" class="w-6 h-6 bg-gray-200 rounded hover:bg-gray-300">-</button>
                                        </form>
                                        <span class="w-8 text-center"><?php echo $item['quantity']; ?></span>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="update_quantity">
                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                            <input type="hidden" name="change" value="1">
                                            <button type="submit" class="w-6 h-6 bg-gray-200 rounded hover:bg-gray-300">+</button>
                                        </form>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="remove_from_cart">
                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="text-red-500 hover:text-red-700">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Total -->
                    <div class="border-t pt-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span>Subtotal:</span>
                            <span id="subtotal">Rp <?php 
                                $subtotal = 0;
                                foreach ($cart as $item) {
                                    $subtotal += $item['price'] * $item['quantity'];
                                }
                                echo number_format($subtotal, 0, ',', '.');
                            ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span>Pajak (10%):</span>
                            <span id="tax">Rp <?php echo number_format($subtotal * 0.1, 0, ',', '.'); ?></span>
                        </div>
                        <div class="flex justify-between font-bold text-lg">
                            <span>Total:</span>
                            <span id="total" class="text-naura-blue">Rp <?php echo number_format($subtotal * 1.1, 0, ',', '.'); ?></span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4 space-y-2">
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="clear_cart">
                            <button type="submit" class="w-full bg-gray-200 text-gray-700 py-2 rounded-lg hover:bg-gray-300 transition">
                                <i class="fas fa-trash mr-2"></i>Kosongkan
                            </button>
                        </form>
                        <button onclick="openPaymentModal()" 
                                class="w-full bg-naura-blue text-white py-2 rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-credit-card mr-2"></i>Bayar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal (Tidak berubah) -->
    <div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md fade-in">
            <h3 class="text-xl font-bold mb-4">Pembayaran</h3>
            
            <div class="mb-4">
                <p class="text-sm text-gray-600">Total Pembayaran:</p>
                <p class="text-2xl font-bold text-naura-blue" id="paymentTotal">
                    Rp <?php echo number_format($subtotal * 1.1, 0, ',', '.'); ?>
                </p>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="process_payment">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Metode Pembayaran</label>
                    <select name="payment_method" id="paymentMethod" onchange="togglePaymentMethod()" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="cash">Tunai</option>
                        <option value="qris">QRIS</option>
                    </select>
                </div>

                <div id="cashPayment" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah Uang</label>
                    <input type="number" name="cash_amount" id="cashAmount" 
                           oninput="calculateChange()"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                           placeholder="Masukkan jumlah uang">
                    <p class="text-sm text-gray-600 mt-2">Kembalian: <span id="changeAmount" class="font-bold">Rp 0</span></p>
                </div>

                <div id="qrisPayment" class="mb-4 hidden">
                    <div class="bg-gray-100 p-4 rounded-lg text-center">
                        <i class="fas fa-qrcode text-6xl text-gray-400 mb-2"></i>
                        <p class="text-sm text-gray-600">Scan QR Code untuk pembayaran</p>
                    </div>
                </div>

                <div class="flex space-x-2">
                    <button type="button" onclick="closePaymentModal()" 
                            class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg hover:bg-gray-300 transition">
                        Batal
                    </button>
                    <button type="submit" 
                            class="flex-1 bg-naura-blue text-white py-2 rounded-lg hover:bg-blue-700 transition">
                        Bayar Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODIFIED: JavaScript untuk memuat menu secara dinamis -->
    <script>
        // Fungsi untuk memuat item menu dari API
        function loadMenuItems() {
            const menuContainer = document.getElementById('productsGrid');
            
            // Tampilkan indikator loading
            menuContainer.innerHTML = '<p class="text-center col-span-full text-gray-500"><i class="fas fa-spinner fa-spin"></i> Memuat menu...</p>';

            fetch('../api/get_menu.php') // Pastikan path relatif benar
                .then(response => response.json())
                .then(menuItems => {
                    menuContainer.innerHTML = ''; // Kosongkan container

                    if (menuItems.length === 0) {
                        menuContainer.innerHTML = '<p class="text-center col-span-full text-gray-500">Belum ada menu tersedia.</p>';
                        return;
                    }

                    menuItems.forEach(item => {
                        const menuItemElement = createMenuItemElement(item);
                        menuContainer.appendChild(menuItemElement);
                    });
                })
                .catch(error => {
                    console.error('Error fetching menu:', error);
                    menuContainer.innerHTML = '<p class="text-center col-span-full text-red-500">Gagal memuat menu. Silakan coba lagi.</p>';
                });
        }

        // Fungsi untuk membuat elemen HTML untuk setiap item menu
        function createMenuItemElement(item) {
            const div = document.createElement('div');
            div.className = 'product-card bg-gray-50 border rounded-lg p-3 hover:shadow-lg transition cursor-pointer hover-scale';
            div.setAttribute('data-category', item.category_name || 'Uncategorized');
            div.onclick = () => addToCart(item.id);

            div.innerHTML = `
                <img src="${item.image || 'https://via.placeholder.com/150x128'}" alt="${item.name}" 
                     class="w-full h-32 object-cover rounded-lg mb-2">
                <h3 class="font-semibold text-sm">${item.name}</h3>
                <p class="text-naura-blue font-bold">Rp ${Number(item.price).toLocaleString('id-ID')}</p>
                <p class="text-xs text-gray-500">Stok: ${item.stock}</p>
            `;
            return div;
        }

        // Fungsi filter kategori (tidak berubah, tetap berfungsi)
        function filterCategory(category) {
            const cards = document.querySelectorAll('.product-card');
            const buttons = document.querySelectorAll('.category-btn');
            
            buttons.forEach(btn => {
                btn.classList.remove('bg-naura-blue', 'text-white');
                btn.classList.add('bg-gray-200');
            });
            event.target.classList.remove('bg-gray-200');
            event.target.classList.add('bg-naura-blue', 'text-white');
            
            cards.forEach(card => {
                if (category === 'all' || card.dataset.category.toLowerCase() === category.toLowerCase()) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Fungsi lainnya (tidak berubah)
        function addToCart(productId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="action" value="add_to_cart"><input type="hidden" name="product_id" value="${productId}">`;
            document.body.appendChild(form);
            form.submit();
        }

        function openPaymentModal() {
            <?php if (empty($cart)): ?>
                alert('Keranjang kosong!');
            <?php else: ?>
                document.getElementById('paymentModal').classList.remove('hidden');
            <?php endif; ?>
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
            document.getElementById('cashAmount').value = '';
            document.getElementById('changeAmount').textContent = 'Rp 0';
        }

        function togglePaymentMethod() {
            const method = document.getElementById('paymentMethod').value;
            const cashDiv = document.getElementById('cashPayment');
            const qrisDiv = document.getElementById('qrisPayment');
            
            if (method === 'cash') {
                cashDiv.classList.remove('hidden');
                qrisDiv.classList.add('hidden');
            } else {
                cashDiv.classList.add('hidden');
                qrisDiv.classList.remove('hidden');
            }
        }

        function calculateChange() {
            const total = <?php echo $subtotal * 1.1; ?>;
            const cashAmount = parseFloat(document.getElementById('cashAmount').value) || 0;
            const change = cashAmount - total;
            
            document.getElementById('changeAmount').textContent = 
                change >= 0 ? 'Rp ' + change.toLocaleString('id-ID') : 'Rp 0';
        }
        
        // Panggil loadMenuItems saat halaman pertama kali dimuat
        document.addEventListener('DOMContentLoaded', loadMenuItems);
    </script>
</body>
</html>