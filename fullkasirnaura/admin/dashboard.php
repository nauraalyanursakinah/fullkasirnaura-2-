<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole('admin');

// Get dashboard data
 $today = date('Y-m-d');
 $stmt = $conn->prepare("SELECT COUNT(*) as count, SUM(total_amount) as total FROM transactions WHERE DATE(created_at) = ?");
 $stmt->bind_param("s", $today);
 $stmt->execute();
 $today_data = $stmt->get_result()->fetch_assoc();

 $total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

 $recent_transactions = getTransactions(5);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Naura Cofe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .text-naura-blue {
            color: #4F46E5;
        }
        .bg-naura-blue {
            background-color: #4F46E5;
        }
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
                <a href="dashboard.php" class="admin-nav px-4 py-2 rounded-lg bg-naura-blue text-white">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <a href="menu.php" class="admin-nav px-4 py-2 rounded-lg hover:bg-gray-100">
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
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-full">
                        <i class="fas fa-dollar-sign text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Penjualan Hari Ini</p>
                        <p class="text-2xl font-bold">Rp <?php echo number_format($today_data['total'] ?? 0, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-full">
                        <i class="fas fa-shopping-cart text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Transaksi Hari Ini</p>
                        <p class="text-2xl font-bold"><?php echo $today_data['count']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-full">
                        <i class="fas fa-users text-purple-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total Users</p>
                        <p class="text-2xl font-bold"><?php echo $total_users; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-orange-100 rounded-full">
                        <i class="fas fa-box text-orange-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total Produk</p>
                        <p class="text-2xl font-bold"><?php echo count(getProducts()); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-bold mb-4">
                <i class="fas fa-history mr-2"></i>Transaksi Terbaru
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Kode</th>
                            <th class="text-left py-2">Tanggal</th>
                            <th class="text-left py-2">Kasir</th>
                            <th class="text-left py-2">Total</th>
                            <th class="text-left py-2">Metode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_transactions as $transaction): ?>
                            <tr class="border-b">
                                <td class="py-2"><?php echo $transaction['transaction_code']; ?></td>
                                <td class="py-2"><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                                <td class="py-2"><?php echo $transaction['username']; ?></td>
                                <td class="py-2">Rp <?php echo number_format($transaction['total_amount'], 0, ',', '.'); ?></td>
                                <td class="py-2">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                                        <?php echo $transaction['payment_method'] === 'cash' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <?php echo ucfirst($transaction['payment_method']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>