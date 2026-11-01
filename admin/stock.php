<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pdo->exec("CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    type ENUM('in','out') NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    note VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB");

$message = ''; $messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_movement') {
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
    $qty = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $note = trim($_POST['note'] ?? '');

    if (!$productId || !in_array($type, ['in', 'out']) || !$qty || $qty <= 0) {
        $message = 'Data pergerakan stok tidak valid.'; $messageType = 'danger';
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$productId]);
            $prod = $stmt->fetch();

            if (!$prod) {
                $message = 'Produk tidak ditemukan.'; $messageType = 'danger';
                $pdo->rollBack();
            } else {
                $newStock = (int)$prod['stock'];
                if ($type === 'in') {
                    $newStock += (int)$qty;
                } else {
                    if ($newStock < (int)$qty) {
                        $message = 'Stok tidak mencukupi untuk keluar (sisa: ' . (int)$newStock . ').'; $messageType = 'warning';
                        $pdo->rollBack();
                    } else {
                        $newStock -= (int)$qty;
                    }
                }

                if ($messageType !== 'warning') {
                    $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?")->execute([$newStock, $productId]);
                    $pdo->prepare("INSERT INTO stock_movements (product_id, type, quantity, note) VALUES (?, ?, ?, ?)")
                        ->execute([$productId, $type, (int)$qty, $note]);
                    $pdo->commit();
                    $message = 'Stok berhasil diperbarui.'; $messageType = 'success';
                }
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = 'Gagal: ' . htmlspecialchars($e->getMessage()); $messageType = 'danger';
        }
    }
}

$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalStockIn  = (int)$pdo->query("SELECT COALESCE(SUM(quantity),0) FROM stock_movements WHERE type = 'in'")->fetchColumn();
$totalStockOut = (int)$pdo->query("SELECT COALESCE(SUM(quantity),0) FROM stock_movements WHERE type = 'out'")->fetchColumn();
$totalStockLeft = (int)$pdo->query("SELECT COALESCE(SUM(stock),0) FROM products")->fetchColumn();

$movements = $pdo->query("
    SELECT sm.id, sm.type, sm.quantity, sm.note, sm.created_at, p.name AS product_name
    FROM stock_movements sm
    JOIN products p ON sm.product_id = p.id
    ORDER BY sm.created_at DESC
    LIMIT 20
")->fetchAll();

$allProducts = $pdo->query("SELECT id, name, price, stock FROM products ORDER BY name ASC")->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Stok - Admin Kopi Dakas</title>
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

<?php $currentPage = 'stock.php'; require_once __DIR__ . '/includes/sidebar.php'; ?>

<header class="top-header fixed top-0 left-[260px] right-0 h-[70px] bg-white border-b border-gray-100 z-900 flex items-center justify-between px-6">
    <div class="search-box relative w-[320px]">
        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="text" placeholder="Cari stok..." class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition-all placeholder-gray-400">
    </div>
    <div class="header-actions flex items-center gap-3">
        <a href="logout.php" class="text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-brand-red rounded-md px-3.5 py-1.5 transition-colors whitespace-nowrap">Keluar</a>
    </div>
</header>

<main class="main-content ml-[260px] mt-[70px] p-6 min-h-screen">
    <div class="page-header flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <h1 class="flex items-center gap-2.5 font-bold text-gray-800 text-xl leading-none">
                <i class="bi bi-box-seam text-brand-blue text-xl"></i>
                Kelola Stok
            </h1>
            <p class="text-sm text-gray-500 mt-1">Pantau pergerakan stok produk kopi dari masuk hingga keluar.</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="openMovementModal()" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-brand-blue rounded-lg shadow-sm hover:bg-brand-blue-dark transition-colors">
                <i class="bi bi-plus-lg w-4 h-4"></i>
                Tambah Stok
            </button>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="mb-6 p-4 rounded-lg flex items-start gap-3 <?= $messageType === 'success' ? 'bg-green-50 border border-green-100 text-green-800' : ($messageType === 'warning' ? 'bg-amber-50 border border-amber-100 text-amber-800' : ($messageType === 'info' ? 'bg-blue-50 border border-blue-100 text-blue-800' : 'bg-brand-red/10 border border-brand-red/200 text-brand-red-dark')) ?>">
        <i class="bi bi-<?= $messageType === 'success' ? 'check-circle-fill' : ($messageType === 'warning' ? 'exclamation-triangle-fill' : ($messageType === 'info' ? 'info-circle-fill' : 'exclamation-circle-fill')) ?> text-[1.1rem] mt-0.5 flex-shrink-0"></i>
        <div class="text-sm font-medium"><?= htmlspecialchars($message) ?></div>
    </div>
    <?php endif; ?>

    <div class="stats-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="stat-card bg-white border border-gray-100 rounded-xl p-5 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="stat-icon w-12 h-12 rounded-lg bg-brand-blue/10 text-brand-blue flex items-center justify-center">
                <i class="bi bi-box text-lg"></i>
            </div>
            <div class="stat-info">
                <h3 class="text-2xl font-bold text-gray-900 leading-none"><?= $totalProducts ?></h3>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Barang</p>
            </div>
        </div>
        <div class="stat-card bg-white border border-gray-100 rounded-xl p-5 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="stat-icon w-12 h-12 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i class="bi bi-arrow-down-up text-lg"></i>
            </div>
            <div class="stat-info">
                <h3 class="text-2xl font-bold text-gray-900 leading-none"><?= $totalStockIn ?></h3>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Barang Masuk</p>
            </div>
        </div>
        <div class="stat-card bg-white border border-gray-100 rounded-xl p-5 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="stat-icon w-12 h-12 rounded-lg bg-brand-red/10 text-brand-red flex items-center justify-center">
                <i class="bi bi-arrow-down-up text-lg"></i>
            </div>
            <div class="stat-info">
                <h3 class="text-2xl font-bold text-gray-900 leading-none"><?= $totalStockOut ?></h3>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Barang Keluar</p>
            </div>
        </div>
        <div class="stat-card bg-white border border-gray-100 rounded-xl p-5 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="stat-icon w-12 h-12 rounded-lg bg-gray-50 text-gray-600 flex items-center justify-center">
                <i class="bi bi-box-seam text-lg"></i>
            </div>
            <div class="stat-info">
                <h3 class="text-2xl font-bold text-gray-900 leading-none"><?= $totalStockLeft ?></h3>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Stok Tersisa</p>
            </div>
        </div>
    </div>

    <div class="bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden mb-8">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <h5 class="font-bold text-gray-900 text-sm">Stok per Produk</h5>
            <a href="products.php" class="text-xs font-semibold text-brand-blue hover:text-brand-blue-dark transition-colors">Kelola Produk</a>
        </div>
        <div class="table-responsive w-full overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[30%]">Produk</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[20%]">Stok Saat Ini</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[25%]">Harga Jual</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[25%]">Potensi Pendapatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($allProducts as $p): 
                        $stock = (int)$p['stock'];
                        $price = (float)$p['price'];
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-900 font-medium text-sm"><?= htmlspecialchars($p['name']) ?></td>
                        <td class="px-6 py-4 border-b border-gray-100">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                <?= $stock <= 0 ? 'bg-brand-red/10 text-brand-red-dark' : ($stock <= 3 ? 'bg-amber-100 text-amber-750' : 'bg-green-100 text-green-750') ?>
                                text-xs font-medium uppercase">
                                <i class="bi bi-<?= $stock <= 0 ? 'x-circle' : ($stock <= 3 ? 'exclamation-circle' : 'check-circle') ?> [&>i]:text-[0.65rem]"></i>
                                <?= $stock ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-900 text-sm">Rp <?= number_format($price, 0, ',', '.') ?></td>
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-900 text-sm font-medium">Rp <?= number_format($stock * $price, 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($allProducts)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                            <i class="bi bi-box text-3xl mb-3 opacity-40"></i>
                            <p class="text-sm font-medium">Belum ada produk.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <h5 class="font-bold text-gray-900 text-sm">Riwayat Pergerakan Stok</h5>
        </div>
        <div class="table-responsive w-full overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[20%]">Produk</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[15%]">Tipe</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[12%]">Jumlah</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[25%]">Keterangan</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[28%]">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($movements): foreach ($movements as $m): 
                        $isIn = $m['type'] === 'in';
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-900 text-sm"><?= htmlspecialchars($m['product_name']) ?></td>
                        <td class="px-6 py-4 border-b border-gray-100">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                                <?= $isIn ? 'bg-emerald-50 text-emerald-700' : 'bg-brand-red/10 text-brand-red-dark' ?>
                                text-xs font-medium uppercase">
                                <i class="bi bi-<?= $isIn ? 'arrow-down' : 'arrow-up' ?> [&>i]:text-[0.65rem]"></i>
                                <?= $isIn ? 'Masuk' : 'Keluar' ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-900 font-medium text-sm"><?= (int)$m['quantity'] ?></td>
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-500 text-sm"><?= htmlspecialchars($m['note'] ?? '-') ?></td>
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-500 text-sm"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                            <i class="bi bi-clock-history text-3xl mb-3 opacity-40"></i>
                            <p class="text-sm font-medium">Belum ada riwayat pergerakan stok.</p>
                            <p class="text-xs text-gray-400 mt-1">Mulai mencatat stok masuk dan keluar.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div id="movementModal" class="fixed inset-0 z-[60] hidden" aria-modal="true" role="dialog">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeMovementModal()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white rounded-xl shadow-2xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-bold text-gray-900 text-base">Tambah Pergerakan Stok</h3>
            <button onclick="closeMovementModal()" class="w-7 h-7 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-colors flex items-center justify-center">
                <i class="bi bi-x text-sm"></i>
            </button>
        </div>
        <form method="POST" action="stock.php" class="space-y-4">
            <input type="hidden" name="action" value="add_movement">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Produk</label>
                <select name="product_id" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition-colors">
                    <?php foreach ($allProducts as $p): ?>
                    <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (Stok: <?= (int)$p['stock'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Tipe</label>
                    <select name="type" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition-colors">
                        <option value="in">Masuk</option>
                        <option value="out">Keluar</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Jumlah</label>
                    <input type="number" name="quantity" min="1" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition-colors">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Keterangan</label>
                <input type="text" name="note" placeholder="Contoh: Restok mingguan / Penjualan harian" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition-colors">
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                <button type="button" onclick="closeMovementModal()" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">Batal</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-brand-blue rounded-lg shadow-sm hover:bg-brand-blue-dark transition-colors">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
const movementModal = document.getElementById('movementModal');

function openMovementModal() {
    movementModal.classList.remove('hidden');
}

function closeMovementModal() {
    movementModal.classList.add('hidden');
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !movementModal.classList.contains('hidden')) closeMovementModal();
});
</script>

</body>
</html>
