<?php
// Sertakan file konfigurasi dan fungsi
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Jika user sudah login, redirect ke dashboard
if (isLoggedIn()) {
    redirectBasedOnRole();
}

// Proses pendaftaran jika form dikirim
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = 'user'; // Default role untuk pendaftar baru

    // Validasi input
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = "Semua field harus diisi.";
    } elseif ($password !== $confirm_password) {
        $error = "Password tidak cocok.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } else {
        // Cek apakah username sudah ada di database
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Username sudah digunakan.";
        } else {
            // Hash password untuk keamanan
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user baru ke database
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashed_password, $role);

            if ($stmt->execute()) {
                // Jika berhasil, set notifikasi sukses dan redirect ke halaman login
                $_SESSION['notification'] = [
                    'type' => 'success',
                    'message' => 'Pendaftaran berhasil! Silakan login.'
                ];
                header("Location: login.php"); // Arahkan ke halaman login
                exit();
            } else {
                $error = "Terjadi kesalahan. Silakan coba lagi.";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Naura Cofe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .text-naura-blue { color: #4F46E5; }
        .bg-naura-blue { background-color: #4F46E5; }
        .fade-in { animation: fadeIn 0.5s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-100 to-blue-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-md fade-in">
        <!-- Logo dan Judul -->
        <div class="text-center mb-6">
            <div class="flex justify-center items-center mb-2">
                <i class="fas fa-coffee text-4xl text-purple-600 mr-3"></i>
                <h1 class="text-3xl font-bold text-gray-800">Naura Cofe</h1>
            </div>
            <p class="text-gray-600">Buat akun Anda</p>
        </div>

        <!-- Notifikasi Error -->
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Form Pendaftaran -->
        <form method="POST" action="">
            <input type="hidden" name="register" value="1">
            <div class="mb-4">
                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">
                    <i class="fas fa-user mr-2"></i>Username
                </label>
                <input type="text" id="username" name="username" required
                       class="shadow appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-purple-500"
                       placeholder="Masukkan username">
            </div>
            <div class="mb-4">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">
                    <i class="fas fa-lock mr-2"></i>Password
                </label>
                <input type="password" id="password" name="password" required
                       class="shadow appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-purple-500"
                       placeholder="Masukkan password">
            </div>
            <div class="mb-6">
                <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">
                    <i class="fas fa-lock mr-2"></i>Konfirmasi Password
                </label>
                <input type="password" id="confirm_password" name="confirm_password" required
                       class="shadow appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-purple-500"
                       placeholder="Masukkan kembali password">
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" 
                        class="bg-naura-blue text-white font-bold py-3 px-4 rounded-lg w-full hover:bg-blue-700 transition duration-300">
                    <i class="fas fa-user-plus mr-2"></i>Daftar
                </button>
            </div>
        </form>

        <!-- Link ke Login -->
        <div class="text-center mt-6">
            <p class="text-gray-600">Sudah punya akun?
                <a href="login.php" class="text-naura-blue hover:underline font-semibold">Login di sini</a>
            </p>
        </div>
    </div>
</body>
</html>