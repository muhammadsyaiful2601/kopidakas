<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$totalRevenue   = (float)$pdo->query("SELECT COALESCE(SUM(o.quantity * p.price), 0) FROM orders o JOIN products p ON o.product_id = p.id WHERE o.status = 'completed'")->fetchColumn();
$totalOrders    = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn();
$totalItemsSold = (int)$pdo->query("SELECT COALESCE(SUM(o.quantity), 0) FROM orders o WHERE o.status = 'completed'")->fetchColumn();
$pendingOrders  = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

$topProducts = $pdo->query("
    SELECT p.name, p.price, SUM(o.quantity) AS total_qty, SUM(o.quantity * p.price) AS total_rev
    FROM orders o JOIN products p ON o.product_id = p.id
    WHERE o.status = 'completed'
    GROUP BY p.id, p.name, p.price
    ORDER BY total_qty DESC
    LIMIT 10
")->fetchAll();

$dailyRevenue = $pdo->query("
    SELECT DATE(o.created_at) AS tgl, SUM(o.quantity * p.price) AS rev
    FROM orders o JOIN products p ON o.product_id = p.id
    WHERE o.status = 'completed' AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(o.created_at)
    ORDER BY tgl ASC
")->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Keuntungan - Admin Kopi Dakas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        brand: {
                            blue: '#1565C0',
                            'blue-dark': '#0D47A1',
                            red: '#D62828',
                            'red-dark': '#B71C1C',
                        },
                    },
                },
            },
        }
    </script>
    <style>
        body { background-color: #f5f7fa; overflow-x: hidden; }
        .sidebar { transition: transform 0.3s ease; }
        .top-header { transition: left 0.3s ease; }
        .main-content { transition: margin-left 0.3s ease; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="antialiased">

<?php $currentPage = 'profit.php'; require_once __DIR__ . '/includes/sidebar.php'; ?>

<header class="top-header fixed top-0 left-[260px] right-0 h-[70px] bg-white border-b border-gray-100 z-900 flex items-center justify-between px-6">
    <div class="search-box relative w-[320px]">
        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="text" placeholder="Cari laporan..." class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition-all placeholder-gray-400">
    </div>
    <div class="header-actions flex items-center gap-3">
        <a href="logout.php" class="text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-brand-red rounded-md px-3.5 py-1.5 transition-colors whitespace-nowrap">Keluar</a>
    </div>
</header>

<main class="main-content ml-[260px] mt-[70px] p-6 min-h-screen">
    <div class="page-header flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <h1 class="flex items-center gap-2.5 font-bold text-gray-800 text-xl leading-none">
                <i class="bi bi-currency-dollar text-brand-blue text-xl"></i>
                Keuntungan
            </h1>
            <p class="text-sm text-gray-500 mt-1">Laporan pendapatan dari pesanan yang sudah selesai diproses.</p>
        </div>
        <div class="flex items-center gap-3"></div>
    </div>

    <div class="stats-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="stat-card bg-white border border-gray-100 rounded-xl p-5 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="stat-icon w-12 h-12 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i class="bi bi-currency-dollar text-lg"></i>
            </div>
            <div class="stat-info">
                <h3 class="text-2xl font-bold text-gray-900 leading-none">Rp <?= number_format($totalRevenue, 0, ',', '.') ?></h3>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Pendapatan</p>
            </div>
        </div>
        <div class="stat-card bg-white border border-gray-100 rounded-xl p-5 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="stat-icon w-12 h-12 rounded-lg bg-brand-blue/10 text-brand-blue flex items-center justify-center">
                <i class="bi bi-check-circle text-lg"></i>
            </div>
            <div class="stat-info">
                <h3 class="text-2xl font-bold text-gray-900 leading-none"><?= $totalOrders ?></h3>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Pesanan Selesai</p>
            </div>
        </div>
        <div class="stat-card bg-white border border-gray-100 rounded-xl p-5 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="stat-icon w-12 h-12 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                <i class="bi bi-box text-lg"></i>
            </div>
            <div class="stat-info">
                <h3 class="text-2xl font-bold text-gray-900 leading-none"><?= $totalItemsSold ?></h3>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Item Terjual</p>
            </div>
        </div>
        <div class="stat-card bg-white border border-gray-100 rounded-xl p-5 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="stat-icon w-12 h-12 rounded-lg bg-gray-50 text-gray-600 flex items-center justify-center">
                <i class="bi bi-clock text-lg"></i>
            </div>
            <div class="stat-info">
                <h3 class="text-2xl font-bold text-gray-900 leading-none"><?= $pendingOrders ?></h3>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Pending</p>
            </div>
        </div>
    </div>

    <div class="bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden mb-8">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <h5 class="font-bold text-gray-900 text-sm">Produk Terlaris</h5>
        </div>
        <div class="table-responsive w-full overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[6%]">#</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[34%]">Produk</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[18%]">Harga Satuan</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[18%]">Terjual</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[24%]">Pendapatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($topProducts): $no = 1; foreach ($topProducts as $tp): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-900 font-medium text-sm"><?= $no++ ?></td>
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-900 text-sm"><?= htmlspecialchars($tp['name']) ?></td>
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-900 text-sm">Rp <?= number_format((float)$tp['price'], 0, ',', '.') ?></td>
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-900 font-medium text-sm"><?= (int)$tp['total_qty'] ?></td>
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-900 font-medium text-sm">Rp <?= number_format((float)$tp['total_rev'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                            <i class="bi bi-graph-down text-3xl mb-3 opacity-40"></i>
                            <p class="text-sm font-medium">Belum ada data penjualan.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <h5 class="font-bold text-gray-900 text-sm">Pendapatan Harian (7 Hari Terakhir)</h5>
        </div>
        <div class="p-5">
            <?php if ($dailyRevenue): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-3">
                <?php foreach ($dailyRevenue as $d): ?>
                <div class="bg-gray-50 rounded-lg p-4 text-center border border-gray-100">
                    <p class="text-[0.7rem] text-gray-500 uppercase tracking-wider mb-1"><?= date('D', strtotime($d['tgl'])) ?></p>
                    <p class="text-xs text-gray-900 font-medium"><?= date('d/m', strtotime($d['tgl'])) ?></p>
                    <p class="text-sm font-bold text-brand-blue mt-1">Rp <?= number_format((float)$d['rev'], 0, ',', '.') ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-12 text-gray-400">
                <i class="bi bi-calendar-x text-3xl mb-3 opacity-40"></i>
                <p class="text-sm font-medium">Belum ada data pendapatan harian.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
