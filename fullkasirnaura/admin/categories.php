<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole('admin');

// Handle category operations
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_category':
            $name = $_POST['name'];
            $icon = $_POST['icon'];
            
            $stmt = $conn->prepare("INSERT INTO categories (name, icon) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $icon);
            
            if ($stmt->execute()) {
                showNotification('Kategori berhasil ditambahkan!', 'success');
            } else {
                showNotification('Gagal menambahkan kategori!', 'error');
            }
            break;
            
        case 'update_category':
            $id = $_POST['id'];
            $name = $_POST['name'];
            $icon = $_POST['icon'];
            
            $stmt = $conn->prepare("UPDATE categories SET name=?, icon=? WHERE id=?");
            $stmt->bind_param("ssi", $name, $icon, $id);
            
            if ($stmt->execute()) {
                showNotification('Kategori berhasil diperbarui!', 'success');
            } else {
                showNotification('Gagal memperbarui kategori!', 'error');
            }
            break;
            
        case 'delete_category':
            $id = $_POST['id'];
            // Check if category has products
            $check = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
            $check->bind_param("i", $id);
            $check->execute();
            $result = $check->get_result()->fetch_assoc();
            
            if ($result['count'] > 0) {
                showNotification('Tidak dapat menghapus kategori yang masih memiliki produk!', 'error');
            } else {
                $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    showNotification('Kategori berhasil dihapus!', 'success');
                } else {
                    showNotification('Gagal menghapus kategori!', 'error');
                }
            }
            break;
    }
    
    header("Location: categories.php");
    exit();
}

 $categories = getCategories();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kategori - Naura Cofe Admin</title>
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
                <span class="text-sm text-gray-600">Admin: <?php echo $_SESSION['username']; ?></span>
            </div>
            <nav class="flex space-x-4">
                <a href="dashboard.php" class="admin-nav px-4 py-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <a href="menu.php" class="admin-nav px-4 py-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-utensils mr-2"></i>Menu
                </a>
                <a href="categories.php" class="admin-nav px-4 py-2 rounded-lg bg-naura-blue text-white">
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
                    <i class="fas fa-tags mr-2"></i>Manajemen Kategori
                </h2>
                <button onclick="openAddModal()" class="bg-naura-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-2"></i>Tambah Kategori
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
                <?php foreach ($categories as $category): ?>
                    <div class="border rounded-lg p-4 hover:shadow-lg transition">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center">
                                <i class="fas <?php echo $category['icon']; ?> text-2xl text-purple-600 mr-3"></i>
                                <div>
                                    <h3 class="font-semibold text-lg"><?php echo ucfirst($category['name']); ?></h3>
                                    <p class="text-sm text-gray-600">
                                        <?php 
                                        $count = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
                                        $count->bind_param("i", $category['id']);
                                        $count->execute();
                                        $product_count = $count->get_result()->fetch_assoc()['count'];
                                        echo $product_count . ' produk';
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex space-x-1">
                                <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)" 
                                        class="text-blue-600 hover:text-blue-800 p-1">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteCategory(<?php echo $category['id']; ?>)" 
                                        class="text-red-600 hover:text-red-800 p-1">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Category Modal -->
    <div id="categoryModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-xl font-bold mb-4" id="modalTitle">Tambah Kategori</h3>
            
            <form method="POST" id="categoryForm">
                <input type="hidden" name="action" id="formAction" value="add_category">
                <input type="hidden" name="id" id="categoryId">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kategori</label>
                        <input type="text" name="name" id="categoryName" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Icon</label>
                        <select name="icon" id="categoryIcon" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="fa-coffee">Kopi</option>
                            <option value="fa-mug-hot">Minuman Panas</option>
                            <option value="fa-glass-water">Minuman Dingin</option>
                            <option value="fa-cookie">Makanan</option>
                            <option value="fa-bread-slice">Roti</option>
                            <option value="fa-ice-cream">Es Krim</option>
                            <option value="fa-cake">Kue</option>
                            <option value="fa-pizza-slice">Pizza</option>
                            <option value="fa-hamburger">Burger</option>
                            <option value="fa-drumstick-bite">Ayam</option>
                        </select>
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
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Kategori';
            document.getElementById('formAction').value = 'add_category';
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryModal').classList.remove('hidden');
        }

        function editCategory(category) {
            document.getElementById('modalTitle').textContent = 'Edit Kategori';
            document.getElementById('formAction').value = 'update_category';
            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryName').value = category.name;
            document.getElementById('categoryIcon').value = category.icon;
            document.getElementById('categoryModal').classList.remove('hidden');
        }

        function deleteCategory(id) {
            if (confirm('Apakah Anda yakin ingin menghapus kategori ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function closeModal() {
            document.getElementById('categoryModal').classList.add('hidden');
        }
    </script>
</body>
</html>