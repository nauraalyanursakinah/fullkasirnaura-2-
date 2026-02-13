<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Hanya admin yang bisa mengakses halaman ini
checkRole('admin');

// Handle aksi form (Tambah, Edit, Hapus)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_menu':
            // Ambil data dari form
            $name = $_POST['name'];
            $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $price = (float)$_POST['price'];
            $stock = (int)$_POST['stock'];
            $image = !empty($_POST['image']) ? $_POST['image'] : null;

            // Query untuk menambahkan menu baru
            $stmt = $conn->prepare("INSERT INTO menu (name, category_id, price, stock, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sidsi", $name, $category_id, $price, $stock, $image);
            
            if ($stmt->execute()) {
                showNotification('Menu berhasil ditambahkan!', 'success');
            } else {
                showNotification('Gagal menambahkan menu: ' . $stmt->error, 'error');
            }
            $stmt->close();
            break;

        case 'update_menu':
            // Ambil data dari form
            $id = (int)$_POST['id'];
            $name = $_POST['name'];
            $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $price = (float)$_POST['price'];
            $stock = (int)$_POST['stock'];
            $image = !empty($_POST['image']) ? $_POST['image'] : null;

            // Query untuk memperbarui menu
            $stmt = $conn->prepare("UPDATE menu SET name=?, category_id=?, price=?, stock=?, image=? WHERE id=?");
            $stmt->bind_param("sidsii", $name, $category_id, $price, $stock, $image, $id);
            
            if ($stmt->execute()) {
                showNotification('Menu berhasil diperbarui!', 'success');
            } else {
                showNotification('Gagal memperbarui menu: ' . $stmt->error, 'error');
            }
            $stmt->close();
            break;

        case 'delete_menu':
            $id = (int)$_POST['id'];
            // Query untuk menghapus menu
            $stmt = $conn->prepare("DELETE FROM menu WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                showNotification('Menu berhasil dihapus!', 'success');
            } else {
                showNotification('Gagal menghapus menu!', 'error');
            }
            $stmt->close();
            break;
    }

    // Redirect ke halaman yang sama untuk mencegah pengiriman ulang form
    header("Location: menu.php");
    exit();
}

// Ambil semua data menu dari database
 $menu_result = $conn->query("
    SELECT m.id, m.name, m.price, m.stock, m.image, m.category_id, c.name as category_name 
    FROM menu m 
    LEFT JOIN categories c ON m.category_id = c.id 
    ORDER BY m.name ASC
");

// Ambil semua data kategori untuk dropdown di form
 $categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Naura Cofe Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .text-naura-blue { color: #4F46E5; }
        .bg-naura-blue { background-color: #4F46E5; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <div class="flex items-center">
                    <i class="fas fa-coffee text-2xl text-purple-600 mr-2"></i>
                    <h1 class="text-xl font-bold text-gray-800">Naura Cofe Admin</h1>
                </div>
                <span class="text-sm text-gray-600">Admin: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <nav class="flex space-x-4">
                <a href="dashboard.php" class="admin-nav px-4 py-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <a href="menu.php" class="admin-nav px-4 py-2 rounded-lg bg-naura-blue text-white">
                    <i class="fas fa-utensils mr-2"></i>Menu
                </a>
                <a href="categories.php" class="admin-nav px-4 py-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-tags mr-2"></i>Kategori
                </a>
                <a href="users.php" class="admin-nav px-4 py-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-users mr-2"></i>Users
                </a>
                <a href="reports.php" class="admin-nav px-4 py-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-chart-bar mr-2"></i>Laporan
                </a>
                <a href="../logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </nav>
        </div>
    </header>

    <?php displayNotification(); ?>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-sm">
            <div class="p-6 border-b flex justify-between items-center">
                <h2 class="text-xl font-bold">
                    <i class="fas fa-utensils mr-2"></i>Manajemen Menu
                </h2>
                <button onclick="openAddModal()" class="bg-naura-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-2"></i>Tambah Menu
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-3 px-4">Gambar</th>
                            <th class="text-left py-3 px-4">Nama Menu</th>
                            <th class="text-left py-3 px-4">Kategori</th>
                            <th class="text-left py-3 px-4">Harga</th>
                            <th class="text-left py-3 px-4">Stok</th>
                            <th class="text-left py-3 px-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($menu_result && $menu_result->num_rows > 0): ?>
                            <?php while ($menu = $menu_result->fetch_assoc()): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <img src="<?php echo !empty($menu['image']) ? htmlspecialchars($menu['image']) : 'https://via.placeholder.com/50'; ?>" 
                                             alt="<?php echo htmlspecialchars($menu['name']); ?>" 
                                             class="w-12 h-12 rounded-lg object-cover">
                                    </td>
                                    <td class="py-3 px-4 font-semibold"><?php echo htmlspecialchars($menu['name']); ?></td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 bg-gray-100 rounded-full text-sm">
                                            <?php echo !empty($menu['category_name']) ? htmlspecialchars($menu['category_name']) : 'Uncategorized'; ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-semibold">Rp <?php echo number_format($menu['price'], 0, ',', '.'); ?></td>
                                    <td class="py-3 px-4">
                                        <span class="<?php echo $menu['stock'] <= 10 ? 'text-red-600 font-bold' : ''; ?>">
                                            <?php echo $menu['stock']; ?> pcs
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <button onclick="editMenu(<?php echo htmlspecialchars(json_encode($menu)); ?>)" 
                                                class="text-blue-600 hover:text-blue-800 mr-2" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteMenu(<?php echo $menu['id']; ?>)" 
                                                class="text-red-600 hover:text-red-800" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-8 text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>Belum ada menu</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit Menu -->
    <div id="menuModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" style="backdrop-filter: blur(5px);">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-xl font-bold mb-4" id="modalTitle">Tambah Menu</h3>
            
            <form method="POST" id="menuForm">
                <input type="hidden" name="action" id="formAction" value="add_menu">
                <input type="hidden" name="id" id="menuId">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Menu *</label>
                        <input type="text" name="name" id="menuName" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                        <select name="category_id" id="menuCategory"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="">-- Tidak Ada Kategori --</option>
                            <?php if ($categories && $categories->num_rows > 0): ?>
                                <?php 
                                $categories->data_seek(0); 
                                while ($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Harga *</label>
                            <input type="number" name="price" id="menuPrice" required min="0" step="100"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stok *</label>
                            <input type="number" name="stock" id="menuStock" required min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL Gambar</label>
                        <input type="text" name="image" id="menuImage" 
                               placeholder="https://example.com/image.jpg"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ada gambar.</p>
                    </div>
                </div>

                <div class="flex space-x-2 mt-6">
                    <button type="button" onclick="closeModal()" 
                            class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg hover:bg-gray-300 transition">
                        Batal
                    </button>
                    <button type="submit" 
                            class="flex-1 bg-naura-blue text-white py-2 rounded-lg hover:bg-blue-700 transition">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Fungsi untuk membuka modal tambah
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Menu';
            document.getElementById('formAction').value = 'add_menu';
            document.getElementById('menuForm').reset();
            document.getElementById('menuModal').style.display = 'flex';
        }

        // Fungsi untuk membuka modal edit
        function editMenu(menu) {
            document.getElementById('modalTitle').textContent = 'Edit Menu';
            document.getElementById('formAction').value = 'update_menu';
            document.getElementById('menuId').value = menu.id;
            document.getElementById('menuName').value = menu.name;
            document.getElementById('menuCategory').value = menu.category_id || '';
            document.getElementById('menuPrice').value = menu.price;
            document.getElementById('menuStock').value = menu.stock;
            document.getElementById('menuImage').value = menu.image || '';
            document.getElementById('menuModal').style.display = 'flex';
        }

        // Fungsi untuk menghapus menu
        function deleteMenu(id) {
            if (confirm('Apakah Anda yakin ingin menghapus menu ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="delete_menu"><input type="hidden" name="id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Fungsi untuk menutup modal
        function closeModal() {
            document.getElementById('menuModal').style.display = 'none';
        }
    </script>
</body>
</html>