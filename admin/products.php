<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$message = ''; $messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
        $stmt->execute([$id]); $row = $stmt->fetch();
        if ($row && $row['image_url'] && file_exists('../' . $row['image_url'])) { unlink('../' . $row['image_url']); }
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        $message = 'Produk berhasil dihapus.'; $messageType = 'success';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['add','edit'])) {
    $name = trim($_POST['name'] ?? ''); $description = trim($_POST['description'] ?? '');
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT); $image_url = null;
    if (!$name || $price === false || $price <= 0 || $stock === false || $stock < 0) {
        $message = 'Data produk tidak valid.'; $messageType = 'danger';
    } else {
        if (!empty($_FILES['image']['name'])) {
            $uploadDir = '../uploads/'; if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $filename = uniqid('prod_', true) . '.' . $ext; $target = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) { $image_url = 'uploads/' . $filename; }
            else { $message = 'Gagal mengunggah gambar.'; $messageType = 'danger'; }
        }
        if ($messageType !== 'danger') {
            if ($_POST['action'] === 'add') {
                $pdo->prepare("INSERT INTO products (name, description, price, stock, image_url) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$name, $description, $price, $stock, $image_url]);
                $message = 'Produk berhasil ditambahkan.';
            } else {
                $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                $id = $id ?? filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                if ($image_url) {
                    $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ?"); $stmt->execute([$id]); $row = $stmt->fetch();
                    if ($row && $row['image_url'] && file_exists('../' . $row['image_url'])) { unlink('../' . $row['image_url']); }
                }
                $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image_url = COALESCE(?, image_url) WHERE id = ?")
                    ->execute([$name, $description, $price, $stock, $image_url, $id]);
                $message = 'Produk berhasil diperbarui.';
            }
            $messageType = 'success';
        }
    }
}
$editProduct = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?"); $stmt->execute([$editId]); $editProduct = $stmt->fetch();
}
$products = $pdo->query("SELECT * FROM products ORDER BY name ASC")->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Produk - Admin Kopi Dakas</title>
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
        .modal-overlay { transition: opacity 0.2s ease; }
        .modal-panel { transition: transform 0.2s ease, opacity 0.2s ease; }
    </style>
</head>
<body class="antialiased">

<?php $currentPage = 'products.php'; require_once __DIR__ . '/includes/sidebar.php'; ?>

<header class="top-header fixed top-0 left-[260px] right-0 h-[70px] bg-white border-b border-gray-100 z-900 flex items-center justify-between px-6">
    <div class="search-box relative w-[320px]">
        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="text" placeholder="Cari produk..." class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition-all placeholder-gray-400">
    </div>
    <div class="header-actions flex items-center gap-3">
        <a href="logout.php" class="text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-brand-red rounded-md px-3.5 py-1.5 transition-colors whitespace-nowrap">Keluar</a>
    </div>
</header>

<main class="main-content ml-[260px] mt-[70px] p-6 min-h-screen">
    <div class="page-header flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <h1 class="flex items-center gap-2.5 font-bold text-gray-800 text-xl leading-none">
                <i class="bi bi-cup-hot text-brand-blue text-xl"></i>
                Kelola Produk
            </h1>
            <p class="text-sm text-gray-500 mt-1">Kelola daftar produk kopi dan stok tersedia.</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="openModal()" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-brand-blue rounded-lg shadow-sm hover:bg-brand-blue-dark transition-colors">
                <i class="bi bi-plus-lg w-4 h-4"></i> Tambah Produk
            </button>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="mb-6 p-4 rounded-lg flex items-start gap-3 <?= $messageType === 'success' ? 'bg-green-50 border border-green-100 text-green-800' : 'bg-brand-red/10 border border-brand-red/200 text-brand-red-dark' ?>">
        <i class="bi bi-<?= $messageType === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?> text-[1.1rem] mt-0.5 flex-shrink-0"></i>
        <div class="text-sm font-medium"><?= htmlspecialchars($message) ?></div>
    </div>
    <?php endif; ?>

    <div class="bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden">
        <div class="table-responsive w-full overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-4 text-sm font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[12%]">Gambar</th>
                        <th class="px-6 py-4 text-sm font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[28%]">Nama Produk</th>
                        <th class="px-6 py-4 text-sm font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[18%]">Harga</th>
                        <th class="px-6 py-4 text-sm font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[12%]">Stok</th>
                        <th class="px-6 py-4 text-sm font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[30%]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($products as $p): 
                        $stock = (int)$p['stock'];
                        $isOut = $stock <= 0;
                        $isLow = $stock > 0 && $stock <= 3;
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 border-b border-gray-100">
                            <?php if ($p['image_url']): ?>
                                <img src="../<?= htmlspecialchars($p['image_url']) ?>" width="40" height="40" class="rounded-lg object-fit-cover border border-gray-100">
                            <?php else: ?>
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 text-[0.65rem] text-gray-400 bg-gray-50">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-900 font-medium text-sm"><?= htmlspecialchars($p['name']) ?></td>
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-900 text-sm">Rp <?= number_format((float)$p['price'], 0, ',', '.') ?></td>
                        <td class="px-6 py-4 border-b border-gray-100">
                            <span class="inline-flex items-center gap-1.5
                                <?= $isOut ? 'bg-brand-red/10 text-brand-red-dark' : ($isLow ? 'bg-amber-100 text-amber-750' : 'bg-green-100 text-green-750') ?>
                                px-2.5 py-1 rounded-full text-xs font-medium uppercase">
                                <i class="bi bi-<?= $isOut ? 'x-circle' : ($isLow ? 'exclamation-circle' : 'check-circle') ?> [&>i]:text-[0.65rem]"></i>
                                <?= $isOut ? 'Habis' : ($isLow ? 'Rendah' : 'Tersedia') ?> (<?= $stock ?>)
                            </span>
                        </td>
                        <td class="px-6 py-4 border-b border-gray-100">
                            <div class="flex items-center gap-2">
                                <button type="button" 
                                    onclick="openModal(<?= (int)$p['id'] ?>, <?= htmlspecialchars(json_encode($p['name'] ?? ''), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($p['description'] ?? ''), ENT_QUOTES) ?>, <?= (float)$p['price'] ?>, <?= (int)$p['stock'] ?>, <?= htmlspecialchars(json_encode($p['image_url'] ?? ''), ENT_QUOTES) ?>)"
                                    class="w-8 h-8 rounded-full text-gray-400 hover:bg-brand-blue/10 hover:text-brand-blue transition-colors flex items-center justify-center"
                                    aria-label="Edit produk">
                                    <i class="bi bi-pencil text-[0.85rem]"></i>
                                </button>
                                <form method="POST" action="products.php" class="ml-1" onsubmit="return confirm('Yakin ingin menghapus produk ini?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <button type="submit" 
                                        class="w-8 h-8 rounded-full text-gray-400 hover:bg-brand-red/10 hover:text-brand-red transition-colors flex items-center justify-center"
                                        aria-label="Hapus produk">
                                        <i class="bi bi-trash text-[0.85rem]"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                            <i class="bi bi-box text-3xl mb-3 opacity-40"></i>
                            <p class="text-sm font-medium">Belum ada produk.</p>
                            <p class="text-xs text-gray-400 mt-1">Klik tombol "+ Tambah Produk" untuk menambahkan produk pertama.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div id="productModal" class="fixed inset-0 z-[60] hidden" aria-modal="true" role="dialog">
    <div class="modal-overlay absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="modal-panel absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-[500px] bg-white rounded-xl shadow-2xl border border-gray-100 p-6">
        <div class="modal-header flex items-center justify-between mb-5">
            <h3 id="modalProductTitle" class="font-bold text-gray-900 text-base">Tambah Produk</h3>
            <button onclick="closeModal()" class="w-7 h-7 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-colors flex items-center justify-center">
                <i class="bi bi-x text-sm"></i>
            </button>
        </div>
        <form method="POST" action="products.php" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" id="modalAction" value="add">
            <input type="hidden" name="id" id="modalId">
            <input type="hidden" name="existing_image" id="modalExistingImage">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Produk</label>
                <input type="text" name="name" id="modalName" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition-colors">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Deskripsi</label>
                <textarea name="description" id="modalDescription" rows="3" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition-colors"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Harga</label>
                    <input type="number" name="price" id="modalPrice" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition-colors" min="0" step="100" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Stok</label>
                    <input type="number" name="stock" id="modalStock" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition-colors" min="0" required>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Gambar Produk</label>
                <input type="file" name="image" id="modalImage" class="block w-full text-sm text-gray-500 file:mr-3 file:px-3 file:py-1.5 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-brand-blue file:text-white hover:file:bg-brand-blue-dark transition-colors" accept="image/*">
                <div id="modalImagePreview" class="mt-2"></div>
                <div id="modalEditHint" class="text-xs text-gray-500 mt-1.5 hidden">Kosongkan gambar jika tidak ingin mengubah.</div>
            </div>

            <div class="modal-footer flex items-center justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">Batal</button>
                <button type="submit" id="modalSubmitBtn" class="px-4 py-2 text-sm font-medium text-white bg-brand-blue rounded-lg shadow-sm hover:bg-brand-blue-dark transition-colors">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById('productModal');
const imgIn = document.getElementById('modalImage');
const prev = document.getElementById('modalImagePreview');

function openModal(id = null, name = '', description = '', price = '', stock = '', image = '') {
    const actionEl = document.getElementById('modalAction');
    const idEl = document.getElementById('modalId');
    const existingEl = document.getElementById('modalExistingImage');
    const titleEl = document.getElementById('modalProductTitle');
    const submitEl = document.getElementById('modalSubmitBtn');
    const nameEl = document.getElementById('modalName');
    const descEl = document.getElementById('modalDescription');
    const priceEl = document.getElementById('modalPrice');
    const stockEl = document.getElementById('modalStock');
    const hintEl = document.getElementById('modalEditHint');

    nameEl.value = ''; descEl.value = ''; priceEl.value = ''; stockEl.value = '';
    imgIn.value = ''; prev.innerHTML = ''; hintEl.classList.add('hidden');

    if (id) {
        actionEl.value = 'edit';
        idEl.value = id;
        existingEl.value = image;
        titleEl.textContent = 'Edit Produk';
        submitEl.textContent = 'Perbarui';
        nameEl.value = name;
        descEl.value = description;
        priceEl.value = price;
        stockEl.value = stock;
        if (image) {
            hintEl.classList.remove('hidden');
            prev.innerHTML = '<img src="../' + image + '" width="120" class="rounded-lg border border-gray-100 mt-2">';
        }
    } else {
        actionEl.value = 'add';
        idEl.value = '';
        existingEl.value = '';
        titleEl.textContent = 'Tambah Produk';
        submitEl.textContent = 'Simpan';
    }
    modal.classList.remove('hidden');
}

function closeModal() {
    modal.classList.add('hidden');
    prev.innerHTML = '';
}

imgIn.addEventListener('change', function () {
    const f = this.files[0];
    if (f) {
        const r = new FileReader();
        r.onload = function (e) {
            prev.innerHTML = '<img src="' + e.target.result + '" width="120" class="rounded-lg border border-gray-100 mt-2">';
        };
        r.readAsDataURL(f);
    } else {
        prev.innerHTML = '';
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
});
</script>

</body>
</html>
