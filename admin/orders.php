<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$message = ''; $messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete') {
    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    if ($orderId) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT o.id, o.product_id, o.quantity, o.status, p.stock, p.name FROM orders o JOIN products p ON o.product_id = p.id WHERE o.id = ? FOR UPDATE");
            $stmt->execute([$orderId]); $order = $stmt->fetch();
            if (!$order) { $message = 'Pesanan tidak ditemukan.'; $messageType = 'danger'; $pdo->rollBack(); }
            elseif ($order['status'] === 'completed') { $message = 'Pesanan sudah ditandai selesai.'; $messageType = 'info'; $pdo->rollBack(); }
            elseif ((int)$order['stock'] < (int)$order['quantity']) { $message = 'Stok "' . htmlspecialchars($order['name']) . '" tidak mencukupi (sisa: ' . (int)$order['stock'] . ').'; $messageType = 'warning'; $pdo->rollBack(); }
            else {
                $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")->execute([(int)$order['quantity'], (int)$order['product_id']]);
                $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ?")->execute([$orderId]);
                $pdo->prepare("INSERT INTO stock_movements (product_id, type, quantity, note) VALUES (?, 'out', ?, ?)")
                    ->execute([(int)$order['product_id'], (int)$order['quantity'], 'Penjualan pesanan #' . (int)$orderId]);
                $message = 'Pesanan #' . (int)$orderId . ' selesai. Stok dikurangi & tercatat sebagai barang keluar.'; $messageType = 'success'; $pdo->commit();
            }
        } catch (PDOException $e) { $pdo->rollBack(); $message = 'Gagal: ' . htmlspecialchars($e->getMessage()); $messageType = 'danger'; }
    }
}
$orders = $pdo->query("SELECT o.id, o.customer_name, p.name AS product_name, o.quantity, o.status, o.created_at FROM orders o JOIN products p ON o.product_id = p.id ORDER BY o.created_at DESC")->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Pesanan - Admin Kopi Dakas</title>
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
<body class="antialiased" data-realtime-url="../api/realtime.php?scope=orders">

<?php $currentPage = 'orders.php'; require_once __DIR__ . '/includes/sidebar.php'; ?>

<header class="top-header fixed top-0 left-[260px] right-0 h-[70px] bg-white border-b border-gray-100 z-900 flex items-center justify-between px-6">
    <div class="search-box relative w-[320px]">
        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="text" placeholder="Cari pesanan..." class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition-all placeholder-gray-400">
    </div>
    <div class="header-actions flex items-center gap-3">
        <a href="logout.php" class="text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-brand-red rounded-md px-3.5 py-1.5 transition-colors whitespace-nowrap">Keluar</a>
    </div>
</header>

<main class="main-content ml-[260px] mt-[70px] p-6 min-h-screen">
    <div class="page-header flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <h1 class="flex items-center gap-2.5 font-bold text-gray-800 text-xl leading-none">
                <i class="bi bi-cart-check text-brand-blue text-xl"></i>
                Kelola Pesanan
            </h1>
            <p class="text-sm text-gray-500 mt-1">Kelola dan perbarui status pesanan pelanggan.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="dashboard.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm text-gray-700 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 transition-colors">
                <i class="bi bi-speedometer2 w-4 h-4"></i> Dashboard
            </a>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="mb-6 p-4 rounded-lg flex items-start gap-3 <?= $messageType === 'success' ? 'bg-green-50 border border-green-100 text-green-800' : ($messageType === 'warning' ? 'bg-amber-50 border border-amber-100 text-amber-800' : ($messageType === 'info' ? 'bg-blue-50 border border-blue-100 text-blue-800' : 'bg-brand-red/10 border border-brand-red/200 text-brand-red-dark')) ?>">
        <i class="bi bi-<?= $messageType === 'success' ? 'check-circle-fill' : ($messageType === 'warning' ? 'exclamation-triangle-fill' : ($messageType === 'info' ? 'info-circle-fill' : 'exclamation-circle-fill')) ?> text-[1.1rem] mt-0.5 flex-shrink-0"></i>
        <div class="text-sm font-medium"><?= htmlspecialchars($message) ?></div>
    </div>
    <?php endif; ?>

    <div class="bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden">
        <div class="table-responsive w-full overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[8%]">#</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[22%]">Pelanggan</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[22%]">Produk</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[8%]">Qty</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[17%]">Status</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[23%]">Waktu</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[12%]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($orders): foreach ($orders as $o): 
                        $isCompleted = $o['status'] === 'completed';
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-900 font-medium text-sm">#<?= (int)$o['id'] ?></td>
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-900 text-sm"><?= htmlspecialchars($o['customer_name'] ?? '-') ?></td>
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-900 text-sm"><?= htmlspecialchars($o['product_name']) ?></td>
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-900 font-medium text-sm"><?= (int)$o['quantity'] ?></td>
                        <td class="px-6 py-4 border-b border-gray-100">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[0.7rem] font-semibold uppercase
                                <?= $isCompleted ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' ?>">
                                <i class="bi bi-<?= $isCompleted ? 'check-circle' : 'clock' ?> [&>i]:text-[0.65rem]"></i>
                                <?= ucfirst($o['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-500 text-sm"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                        <td class="px-6 py-4 border-b border-gray-100">
                            <?php if (!$isCompleted): ?>
                            <form method="POST" action="orders.php" class="flex items-center gap-2" onsubmit="return confirm('Tandai selesai? Stok akan dikurangi.');">
                                <input type="hidden" name="action" value="complete">
                                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                                <button type="submit" class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-white bg-brand-blue rounded-lg shadow-sm hover:bg-brand-blue-dark transition-colors">
                                    <i class="bi bi-check-lg"></i> Selesai
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 rounded-lg">
                                <i class="bi bi-check-circle"></i> Selesai
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center text-gray-400">
                            <i class="bi bi-inbox text-3xl mb-3 opacity-40"></i>
                            <p class="text-sm font-medium">Belum ada pesanan.</p>
                            <p class="text-xs text-gray-400 mt-1">Mulai terima pesanan pertama dari toko.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/realtime.js"></script>
</body>
</html>
