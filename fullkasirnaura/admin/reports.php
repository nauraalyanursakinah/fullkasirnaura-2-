<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkRole('admin');

// Get filter parameters
 $start_date = $_GET['start_date'] ?? date('Y-m-01');
 $end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get report data
 $stmt = $conn->prepare("
    SELECT 
        DATE(t.created_at) as date,
        COUNT(*) as transaction_count,
        SUM(t.total_amount) as total_sales,
        SUM(CASE WHEN t.payment_method = 'cash' THEN t.total_amount ELSE 0 END) as cash_sales,
        SUM(CASE WHEN t.payment_method = 'qris' THEN t.total_amount ELSE 0 END) as qris_sales
    FROM transactions t
    WHERE DATE(t.created_at) BETWEEN ? AND ?
    GROUP BY DATE(t.created_at)
    ORDER BY date DESC
");
 $stmt->bind_param("ss", $start_date, $end_date);
 $stmt->execute();
 $daily_report = $stmt->get_result();

// Get top products
 $stmt = $conn->prepare("
    SELECT 
        p.name,
        SUM(ti.quantity) as total_quantity,
        SUM(ti.quantity * ti.price) as total_revenue
    FROM transaction_items ti
    JOIN products p ON ti.product_id = p.id
    JOIN transactions t ON ti.transaction_id = t.id
    WHERE DATE(t.created_at) BETWEEN ? AND ?
    GROUP BY p.id, p.name
    ORDER BY total_quantity DESC
    LIMIT 10
");
 $stmt->bind_param("ss", $start_date, $end_date);
 $stmt->execute();
 $top_products = $stmt->get_result();

// Get summary
 $stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_transactions,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as avg_transaction
    FROM transactions
    WHERE DATE(created_at) BETWEEN ? AND ?
");
 $stmt->bind_param("ss", $start_date, $end_date);
 $stmt->execute();
 $summary = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Naura Cofe Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="users.php" class="admin-nav px-4 py-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-users mr-2"></i>Users
                </a>
                <a href="reports.php" class="admin-nav px-4 py-2 rounded-lg bg-naura-blue text-white">
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
        <!-- Date Filter -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <button type="submit" class="bg-naura-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                <a href="reports.php?export=excel&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                   class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-file-excel mr-2"></i>Export Excel
                </a>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-full">
                        <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total Transaksi</p>
                        <p class="text-2xl font-bold"><?php echo $summary['total_transactions']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-full">
                        <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total Pendapatan</p>
                        <p class="text-2xl font-bold">Rp <?php echo number_format($summary['total_revenue'] ?? 0, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-full">
                        <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Rata-rata Transaksi</p>
                        <p class="text-2xl font-bold">Rp <?php echo number_format($summary['avg_transaction'] ?? 0, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-bold mb-4">Grafik Penjualan Harian</h3>
                <canvas id="salesChart"></canvas>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-bold mb-4">Metode Pembayaran</h3>
                <canvas id="paymentChart"></canvas>
            </div>
        </div>

        <!-- Tables -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-sm">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-bold">Penjualan Harian</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left py-3 px-4">Tanggal</th>
                                <th class="text-left py-3 px-4">Transaksi</th>
                                <th class="text-left py-3 px-4">Penjualan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $daily_report->fetch_assoc()): ?>
                                <tr class="border-b">
                                    <td class="py-3 px-4"><?php echo date('d/m/Y', strtotime($row['date'])); ?></td>
                                    <td class="py-3 px-4"><?php echo $row['transaction_count']; ?></td>
                                    <td class="py-3 px-4">Rp <?php echo number_format($row['total_sales'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-bold">Produk Terlaris</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left py-3 px-4">Produk</th>
                                <th class="text-left py-3 px-4">Qty</th>
                                <th class="text-left py-3 px-4">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $top_products->fetch_assoc()): ?>
                                <tr class="border-b">
                                    <td class="py-3 px-4"><?php echo $row['name']; ?></td>
                                    <td class="py-3 px-4"><?php echo $row['total_quantity']; ?></td>
                                    <td class="py-3 px-4">Rp <?php echo number_format($row['total_revenue'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php 
                    $data = [];
                    $daily_report->data_seek(0);
                    while ($row = $daily_report->fetch_assoc()) {
                        $data[] = date('d/m', strtotime($row['date']));
                    }
                    echo json_encode($data);
                ?>,
                datasets: [{
                    label: 'Penjualan',
                    data: <?php 
                        $data = [];
                        $daily_report->data_seek(0);
                        while ($row = $daily_report->fetch_assoc()) {
                            $data[] = $row['total_sales'];
                        }
                        echo json_encode($data);
                    ?>,
                    borderColor: '#4F46E5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });

        // Payment Chart
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        const paymentChart = new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: ['Tunai', 'QRIS'],
                datasets: [{
                    data: [
                        <?php echo $summary['total_revenue'] ?? 0; ?>,
                        0
                    ],
                    backgroundColor: ['#10B981', '#3B82F6']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>