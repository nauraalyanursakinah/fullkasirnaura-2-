<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole('admin');

// Handle user operations
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_user':
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role = $_POST['role'];
            
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $password, $role);
            
            if ($stmt->execute()) {
                showNotification('User berhasil ditambahkan!', 'success');
            } else {
                showNotification('Gagal menambahkan user!', 'error');
            }
            break;
            
        case 'update_user':
            $id = $_POST['id'];
            $username = $_POST['username'];
            $email = $_POST['email'];
            $role = $_POST['role'];
            
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username=?, email=?, password=?, role=? WHERE id=?");
                $stmt->bind_param("ssssi", $username, $email, $password, $role, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?");
                $stmt->bind_param("sssi", $username, $email, $role, $id);
            }
            
            if ($stmt->execute()) {
                showNotification('User berhasil diperbarui!', 'success');
            } else {
                showNotification('Gagal memperbarui user!', 'error');
            }
            break;
            
        case 'delete_user':
            $id = $_POST['id'];
            if ($id == $_SESSION['user_id']) {
                showNotification('Tidak dapat menghapus akun sendiri!', 'error');
            } else {
                $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    showNotification('User berhasil dihapus!', 'success');
                } else {
                    showNotification('Gagal menghapus user!', 'error');
                }
            }
            break;
    }
    
    header("Location: users.php");
    exit();
}

 $users = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Naura Cofe Admin</title>
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
                <a href="categories.php" class="admin-nav px-4 py-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-tags mr-2"></i>Kategori
                </a>
                <a href="users.php" class="admin-nav px-4 py-2 rounded-lg bg-naura-blue text-white">
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
                    <i class="fas fa-users mr-2"></i>Manajemen User
                </h2>
                <button onclick="openAddModal()" class="bg-naura-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-2"></i>Tambah User
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-3 px-4">Username</th>
                            <th class="text-left py-3 px-4">Email</th>
                            <th class="text-left py-3 px-4">Role</th>
                            <th class="text-left py-3 px-4">Tanggal Dibuat</th>
                            <th class="text-left py-3 px-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-3 px-4">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                            <i class="fas fa-user text-purple-600 text-sm"></i>
                                        </div>
                                        <span class="font-semibold"><?php echo $user['username']; ?></span>
                                    </div>
                                </td>
                                <td class="py-3 px-4"><?php echo $user['email']; ?></td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                                        <?php echo $user['role'] === 'admin' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td class="py-3 px-4">
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                                class="text-blue-600 hover:text-blue-800 mr-2">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteUser(<?php echo $user['id']; ?>)" 
                                                class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm">Anda</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-xl font-bold mb-4" id="modalTitle">Tambah User</h3>
            
            <form method="POST" id="userForm">
                <input type="hidden" name="action" id="formAction" value="add_user">
                <input type="hidden" name="id" id="userId">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" name="username" id="userName" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="userEmail" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" id="userPassword" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                               placeholder="Kosongkan jika tidak ingin mengubah">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select name="role" id="userRole" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="kasir">Kasir</option>
                            <option value="admin">Admin</option>
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
            document.getElementById('modalTitle').textContent = 'Tambah User';
            document.getElementById('formAction').value = 'add_user';
            document.getElementById('userForm').reset();
            document.getElementById('userPassword').required = true;
            document.getElementById('userModal').classList.remove('hidden');
        }

        function editUser(user) {
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('formAction').value = 'update_user';
            document.getElementById('userId').value = user.id;
            document.getElementById('userName').value = user.username;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userRole').value = user.role;
            document.getElementById('userPassword').required = false;
            document.getElementById('userPassword').placeholder = 'Kosongkan jika tidak ingin mengubah';
            document.getElementById('userModal').classList.remove('hidden');
        }

        function deleteUser(id) {
            if (confirm('Apakah Anda yakin ingin menghapus user ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function closeModal() {
            document.getElementById('userModal').classList.add('hidden');
        }
    </script>
</body>
</html>