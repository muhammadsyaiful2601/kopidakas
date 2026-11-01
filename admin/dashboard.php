<?php
/**
 * Admin Dashboard - Kopi Dakas
 * Ringkasan total pesanan hari ini, pending, dan peringatan stok menipis.
 * Refactor UI: Tailwind CSS, light-mode, minimalis & modern.
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$todayOrders   = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$completedOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn();
$lowStock      = $pdo->query("SELECT id, name, stock, price FROM products WHERE stock <= 3 ORDER BY stock ASC, name ASC")->fetchAll();
$allProducts   = $pdo->query("SELECT id, name, stock, price FROM products ORDER BY name ASC")->fetchAll();
$recentOrders  = $pdo->query("
    SELECT o.id, o.customer_name, p.name AS product_name, o.quantity, o.status, o.created_at
    FROM orders o JOIN products p ON o.product_id = p.id
    ORDER BY o.created_at DESC LIMIT 8
")->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Admin Kopi Dakas</title>
    <!-- Font Sans-Serif Modern: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
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
        body {
            background-color: #f5f7fa;
            overflow-x: hidden;
        }
        .sidebar {
            transition: transform 0.3s ease;
        }
        .top-header {
            transition: left 0.3s ease;
        }
        .main-content {
            transition: margin-left 0.3s ease;
        }
        /* Custom scrollbar thin */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="antialiased" data-realtime-url="../api/realtime.php?scope=all">

<?php $currentPage = 'dashboard.php'; require_once __DIR__ . '/includes/sidebar.php'; ?>

<!-- Top Header -->
<header class="top-header fixed top-0 left-[260px] right-0 h-[70px] bg-white border-b border-gray-100 z-900 flex items-center justify-between px-6">
    <div class="search-box relative w-[320px]">
        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="text" placeholder="Cari produk atau pesanan..." class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition-all placeholder-gray-400">
    </div>
    <div class="header-actions flex items-center gap-3">
        <div class="icon-btn relative w-10 h-10 rounded-lg border border-gray-200 bg-gray-50 text-gray-600 flex items-center justify-center cursor-pointer hover:bg-white hover:border-gray-300 hover:text-gray-900 transition-all">
            <i class="bi bi-bell"></i>
            <span class="badge-notif absolute top-[2px] right-[2px] w-2 h-2 bg-brand-red rounded-full border-2 border-white"></span>
        </div>
        <div class="icon-btn w-10 h-10 rounded-lg border border-gray-200 bg-gray-50 text-gray-600 flex items-center justify-center cursor-pointer hover:bg-white hover:border-gray-300 hover:text-gray-900 transition-all">
            <i class="bi bi-printer"></i>
        </div>
        <div class="icon-btn w-10 h-10 rounded-lg border border-gray-200 bg-gray-50 text-gray-600 flex items-center justify-center cursor-pointer hover:bg-white hover:border-gray-300 hover:text-gray-900 transition-all">
            <i class="bi bi-gear"></i>
        </div>
        <div class="user-profile flex items-center gap-3 px-3.5 py-1.5 rounded-lg border border-gray-200 bg-gray-50 cursor-pointer hover:bg-gray-100 transition-colors">
            <div class="user-avatar w-8 h-8 rounded-full bg-brand-blue/10 text-brand-blue flex items-center justify-center font-bold text-[0.8rem]">AC</div>
            <div class="user-info hidden sm:flex flex-col">
                <h4 class="text-sm font-bold text-gray-900 leading-none">Admin Kopi</h4>
                <p class="text-[0.75rem] text-gray-500 leading-none">Super Admin</p>
            </div>
        </div>
    </div>
</header>

<!-- Main Content -->
<main class="main-content ml-[260px] mt-[70px] p-6 min-h-screen">
    <div class="page-header flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 leading-none">Dashboard</h1>
            <p class="text-sm text-gray-500 mt-1">Selamat datang kembali. Berikut ringkasan bisnis kopi Anda hari ini.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="products.php" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-brand-blue rounded-lg hover:bg-brand-blue-dark transition-colors shadow-sm">
                <i class="bi bi-box w-4 h-4"></i>
                Kelola Produk
            </a>
            <a href="orders.php" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-brand-blue rounded-lg hover:bg-brand-blue-dark transition-colors shadow-sm">
                <i class="bi bi-cart w-4 h-4"></i>
                Kelola Pesanan
            </a>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="stats-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="stat-card bg-white border border-gray-100 rounded-xl p-5 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="stat-icon w-12 h-12 rounded-lg bg-brand-blue/10 text-brand-blue flex items-center justify-center">
                <i class="bi bi-cart-fill text-lg"></i>
            </div>
            <div class="stat-info">
                <h3 class="text-2xl font-bold text-gray-900 leading-none"><?= $todayOrders ?></h3>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Pesanan Hari Ini</p>
            </div>
        </div>
        <div class="stat-card bg-white border border-gray-100 rounded-xl p-5 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="stat-icon w-12 h-12 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                <i class="bi bi-clock text-lg"></i>
            </div>
            <div class="stat-info">
                <h3 class="text-2xl font-bold text-gray-900 leading-none"><?= $pendingOrders ?></h3>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Pending (Belum Diproses)</p>
            </div>
        </div>
        <div class="stat-card bg-white border border-gray-100 rounded-xl p-5 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="stat-icon w-12 h-12 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i class="bi bi-check-circle text-lg"></i>
            </div>
            <div class="stat-info">
                <h3 class="text-2xl font-bold text-gray-900 leading-none"><?= $completedOrders ?></h3>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Selesai Diproses</p>
            </div>
        </div>
        <div class="stat-card bg-white border border-gray-100 rounded-xl p-5 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="stat-icon w-12 h-12 rounded-lg bg-brand-red/10 text-brand-red flex items-center justify-center">
                <i class="bi bi-exclamation-triangle text-lg"></i>
            </div>
            <div class="stat-info">
                <h3 class="text-2xl font-bold text-gray-900 leading-none"><?= count($lowStock) ?></h3>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Stok Menipis (≤ 3)</p>
            </div>
        </div>
    </div>

    <!-- Stok Menipis Alert -->
    <?php if ($lowStock): ?>
    <div class="alert-stock bg-brand-red/10 border border-brand-red/200 text-brand-red-dark rounded-xl p-5 mb-8 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 mt-0.5">
                <i class="bi bi-exclamation-triangle-fill text-xl text-brand-red"></i>
            </div>
            <div>
                <h5 class="font-bold text-brand-red-dark text-sm leading-none mb-1">Peringatan Stok Menipis</h5>
                <p class="text-brand-red/80 text-xs leading-relaxed mb-0">Produk berikut stoknya menipis atau habis, segera tambahkan stok:</p>
            </div>
        </div>
        <div class="w-full sm:w-auto mt-4 sm:mt-0 overflow-x-auto">
            <table class="stock-table w-full text-left text-sm border-collapse">
                <thead>
                    <tr>
                        <th class="px-3 py-2.5 text-xs font-semibold text-brand-red/70 uppercase tracking-wider border-b border-brand-red/200 w-2/5">Produk</th>
                        <th class="px-3 py-2.5 text-xs font-semibold text-brand-red/70 uppercase tracking-wider border-b border-brand-red/200 w-1/5">Stok</th>
                        <th class="px-3 py-2.5 text-xs font-semibold text-brand-red/70 uppercase tracking-wider border-b border-brand-red/200 w-1/4">Harga</th>
                        <th class="px-3 py-2.5 text-xs font-semibold text-brand-red/70 uppercase tracking-wider border-b border-brand-red/200 w-1/6">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-red/100">
                    <?php foreach ($lowStock as $p): 
                        $stock = (int)$p['stock'];
                        $isOut = $stock <= 0;
                    ?>
                    <tr>
                        <td class="px-3 py-3 text-gray-900 font-medium"><?= htmlspecialchars($p['name']) ?></td>
                        <td class="px-3 py-3 text-brand-red-dark font-bold"><?= $stock ?></td>
                        <td class="px-3 py-3 text-gray-900">Rp <?= number_format((float)$p['price'], 0, ',', '.') ?></td>
                        <td class="px-3 py-3">
                            <span class="stock-badge inline-flex items-center gap-1.5 <?= $isOut ? 'bg-brand-red/10 text-brand-red-dark' : 'bg-amber-100 text-amber-700' ?> rounded-full px-2 py-1 text-xs font-semibold uppercase">
                                <i class="bi bi-<?= $isOut ? 'x-circle' : 'exclamation-circle' ?> [&>i]:text-[0.7rem]"></i>
                                <?= $isOut ? 'Habis' : 'Menipis' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="alert-success bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-5 mb-8 flex items-center gap-3">
        <div class="flex-shrink-0">
            <i class="bi bi-check-circle-fill text-xl text-emerald-600"></i>
        </div>
        <div>
            <h5 class="font-bold text-emerald-800 text-sm leading-none mb-1">Semua Stok Aman</h5>
            <p class="text-emerald-700/80 text-xs leading-none mb-0">Semua produk memiliki stok yang mencukupi.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Catatan Section -->
    <div class="catatan-card bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden mb-8">
        <div class="catatan-header flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-gray-50">
            <h5 class="flex items-center gap-2 font-bold text-gray-900 text-sm">
                <i class="bi bi-chat-text text-brand-blue"></i>
                Catatan Terbaru
            </h5>
            <a href="orders.php" class="text-xs font-semibold text-brand-blue hover:text-brand-blue-dark transition-colors">Lihat Semua</a>
        </div>
        <div class="catatan-body p-5">
            <?php if ($recentOrders): ?>
                <?php foreach ($recentOrders as $o): ?>
                <div class="catatan-item flex gap-3 py-3 border-b border-gray-50 last:border-0">
                    <div class="catatan-icon flex-shrink-0 w-10 h-10 rounded-lg bg-brand-blue/10 text-brand-blue flex items-center justify-center">
                        <i class="bi bi-person text-[0.9rem]"></i>
                    </div>
                    <div class="catatan-content flex-1 min-w-0">
                        <h6 class="font-bold text-gray-900 text-sm leading-none mb-0.5"><?= htmlspecialchars($o['customer_name'] ?? '-') ?></h6>
                        <p class="text-xs text-gray-500 leading-relaxed mb-1">Memesan <?= htmlspecialchars($o['product_name']) ?> x<?= (int)$o['quantity'] ?></p>
                        <span class="catatan-time text-[0.7rem] text-gray-400"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="text-center py-10 text-gray-400">
                <i class="bi bi-inbox text-2xl mb-2 opacity-50"></i>
                <p class="text-sm font-medium">Belum ada catatan pesanan.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="orders-card bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden">
        <div class="orders-header flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-gray-50">
            <h5 class="flex items-center gap-2 font-bold text-gray-900 text-sm">
                <i class="bi bi-clock-history text-brand-blue"></i>
                Pesanan Terbaru
            </h5>
            <a href="orders.php" class="text-xs font-semibold text-gray-500 hover:text-gray-900 transition-colors">Lihat Semua Pesanan</a>
        </div>
        <?php if ($recentOrders): ?>
        <table class="orders-table w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase tracking-wider border-b border-gray-100 w-[8%]">#</th>
                    <th class="px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase tracking-wider border-b border-gray-100 w-[22%]">Pelanggan</th>
                    <th class="px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase tracking-wider border-b border-gray-100 w-[22%]">Produk</th>
                    <th class="px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase tracking-wider border-b border-gray-100 w-[8%]">Qty</th>
                    <th class="px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase tracking-wider border-b border-gray-100 w-[17%]">Status</th>
                    <th class="px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase tracking-wider border-b border-gray-100 w-[23%]">Waktu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($recentOrders as $o): 
                    $isCompleted = $o['status'] === 'completed';
                ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-3.5 text-gray-900 font-medium text-sm">#<?= (int)$o['id'] ?></td>
                    <td class="px-5 py-3.5 text-gray-900 text-sm"><?= htmlspecialchars($o['customer_name'] ?? '-') ?></td>
                    <td class="px-5 py-3.5 text-gray-900 text-sm"><?= htmlspecialchars($o['product_name']) ?></td>
                    <td class="px-5 py-3.5 text-gray-900 font-medium text-sm"><?= (int)$o['quantity'] ?></td>
                    <td class="px-5 py-3.5">
                        <span class="status-badge inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[0.7rem] font-semibold uppercase
                            <?= $isCompleted ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' ?>">
                            <i class="bi bi-<?= $isCompleted ? 'check-circle' : 'clock' ?> [&>i]:text-[0.65rem]"></i>
                            <?= ucfirst($o['status']) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-gray-500 text-sm"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="text-center py-10 text-gray-400">
            <i class="bi bi-cart-x text-3xl mb-3 opacity-40"></i>
            <p class="text-sm font-medium">Belum ada pesanan.</p>
            <p class="text-xs text-gray-400 mt-1">Mulai terima pesanan pertama Anda.</p>
        </div>
        <?php endif; ?>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/realtime.js"></script>
</body>
</html>
