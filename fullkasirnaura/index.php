<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username']);
    $password = $_POST['password'];
    $role = clean($_POST['role']);
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
    $stmt->bind_param("ss", $username, $role);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        
        if ($role === 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: cashier/index.php");
        }
        exit();
    } else {
        showNotification('Username, password, atau role salah!', 'error');
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Naura Cofe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full mb-4">
                <i class="fas fa-coffee text-3xl text-purple-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Naura Cofe</h1>
            <p class="text-gray-600 mt-2">Sistem Kasir Modern</p>
        </div>

        <?php displayNotification(); ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <div class="relative">
                    <i class="fas fa-user absolute left-3 top-3 text-gray-400"></i>
                    <input type="text" name="username" required
                           class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                           placeholder="Masukkan username">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative">
                    <i class="fas fa-lock absolute left-3 top-3 text-gray-400"></i>
                    <input type="password" name="password" required
                           class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                           placeholder="Masukkan password">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Login Sebagai</label>
                <div class="relative">
                    <i class="fas fa-user-tag absolute left-3 top-3 text-gray-400"></i>
                    <select name="role" required
                            class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent appearance-none">
                        <option value="">Pilih Role</option>
                        <option value="kasir">Kasir</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>

            <button type="submit" 
                    class="w-full bg-gradient-to-r from-purple-600 to-purple-700 text-white py-2 rounded-lg hover:from-purple-700 hover:to-purple-800 transition duration-200 font-semibold">
                <i class="fas fa-sign-in-alt mr-2"></i>Login
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">Belum punya akun?</p>
            <button onclick="window.location.href='register.php'" 
                    class="text-purple-600 hover:text-purple-700 font-semibold text-sm">
                Daftar sekarang
            </button>
        </div>
    </div>
</body>
</html>