<?php
/**
 * Landing Page Pelanggan - Sistem Penjualan Kopi
 * PRD Bagian 3A: Akses QR Code, Landing Page & Katalog, Validasi Stok Real-Time, Checkout
 *
 * - Menampilkan grid produk dengan nama, harga, gambar, indikator stok.
 * - Produk dengan stock <= 0 dirender non-klik dengan label "Habis".
 * - Form pemesanan sederhana mengirim POST ke server, dicatat ke tabel orders dengan status 'pending'.
 * - Validasi stok dilakukan sebelum INSERT (PDO prepared statements + transaksi).
 */
declare(strict_types=1);
require_once 'includes/db.php';
require_once 'includes/settings.php';

$message   = '';
$messageType = '';

// ===================== PROSES PEMESANAN (POST) =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $product_id    = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity      = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if (!$product_id || $product_id <= 0 || !$quantity || $quantity <= 0) {
        $message    = 'Produk atau jumlah pesanan tidak valid.';
        $messageType = 'danger';
    } else {
        try {
            $pdo->beginTransaction();

            // Cek ketersediaan stok (lock baris untuk mencegah race condition)
            $stmt = $pdo->prepare("SELECT stock, name FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if (!$product) {
                $message    = 'Produk tidak ditemukan.';
                $messageType = 'danger';
                $pdo->rollBack();
            } elseif ($product['stock'] < $quantity) {
                $message    = 'Maaf, stok "' . htmlspecialchars($product['name']) . '" tidak mencukupi (sisa: ' . (int)$product['stock'] . ').';
                $messageType = 'warning';
                $pdo->rollBack();
            } else {
                // Catat pesanan dengan status pending
                $stmt = $pdo->prepare(
                    "INSERT INTO orders (customer_name, product_id, quantity, status)
                     VALUES (?, ?, ?, 'pending')"
                );
                $stmt->execute([$customer_name ?: null, $product_id, $quantity]);
                $message = 'Pesanan berhasil diterima! Mohon tunggu konfirmasi dari admin.';
                $messageType = 'success';
                $pdo->commit();
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message    = 'Terjadi kesalahan saat memesan: ' . htmlspecialchars($e->getMessage());
            $messageType = 'danger';
        }
    }
}

// ===================== AMBIL DATA PRODUK =====================
$products = $pdo->query("SELECT * FROM products ORDER BY name ASC")->fetchAll();
$heroImage = getSystemSetting($pdo, 'hero_image', 'assets/foto/foto landing page.jpeg');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pesan Kopi - Kopi Dakas</title>
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body data-realtime-url="api/realtime.php?scope=catalog">

<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="index.php">
            <img src="assets/logo/logo.jpeg" alt="Logo Kopi Dakas" class="brand-logo">
            <span>Kopi Dakas</span>
        </a>
        <nav class="nav-menu" aria-label="Navigasi utama">
            <a href="#menu">Menu</a>
        </nav>
    </div>
</header>

<section class="hero">
    <div class="hero-inner">
        <div class="hero-text">
            <span class="eyebrow">Kopi pilihan, dibuat sepenuh hati</span>
            <h1>Pesan Kopi Favorit Anda</h1>
            <p>Pilih kopi di bawah, tekan Pesan, dan tunggu konfirmasi dari kami. Stok diperbarui secara real-time.</p>
            <a class="btn-shop" href="#menu">Pesan Sekarang <span aria-hidden="true">→</span></a>
        </div>
        <div class="hero-image">
            <img src="<?= htmlspecialchars($heroImage, ENT_QUOTES) ?>" alt="Foto Kopi Dakas" class="hero-img">
        </div>
    </div>
</section>

<?php if ($message): ?>
<div class="alert-custom alert alert-<?= $messageType ?>" role="alert">
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<div class="section-title" id="menu">
    <h2>Menu Populer</h2>
    <div class="divider"></div>
</div>

<section>
    <div class="products-grid">
            <?php foreach ($products as $product): 
                $isOut = (int)$product['stock'] <= 0;
                $isLow = (int)$product['stock'] > 0 && (int)$product['stock'] <= 5;
                $img   = $product['image_url'] ? htmlspecialchars($product['image_url']) : 'https://images.unsplash.com/photo-1510707585785-2c240a7ecf7a?w=400&q=80';
                $badge = $isOut ? 'product-badge--danger' : (($isLow) ? 'product-badge--warning' : '');
                $badgeText = $isOut ? 'Habis' : (($isLow) ? 'Hampir Habis' : 'Tersedia');
            ?>
            <article class="product-card <?= $isOut ? 'stock-out' : '' ?>">
                <div class="product-image-wrap">
                    <?php if ($product['image_url']): ?>
                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <?php else: ?>
                    <div class="product-image-placeholder" aria-label="Gambar produk belum tersedia"></div>
                    <?php endif; ?>
                    <?php if (!$isOut): ?>
                    <span class="product-badge <?= $badge ?>"><?= $badgeText ?></span>
                    <?php endif; ?>
                    
                </div>
                <div class="product-info">
                    <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                    <p class="product-desc"><?= htmlspecialchars($product['description'] ?? '-') ?></p>
                </div>
                <div class="product-footer">
                    <div class="price">Rp <?= number_format((float)$product['price'], 0, ',', '.') ?></div>
                    
                    <?php if ($isOut): ?>
                    <button class="btn-order-card" disabled type="button">Habis</button>
                    <?php else: ?>
                    <button class="btn-order-card" type="button" data-order-product
                            data-product-id="<?= (int)$product['id'] ?>"
                            data-product-name="<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>"
                            data-product-price="<?= (float)$product['price'] ?>"
                            data-product-stock="<?= (int)$product['stock'] ?>"
                            data-product-image="<?= htmlspecialchars($img) ?>">
                        Pesan
                    </button>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>

<!-- Modal Pemesanan -->
<div class="modal" id="orderModal" role="dialog" aria-modal="true" aria-labelledby="orderModalLabel" hidden>
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="index.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderModalLabel">Pesan Kopi</h5>
                    <button type="button" class="btn-close" data-modal-close aria-label="Tutup">×</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="modalProductId">
                    <div class="mb-3">
                        <label class="form-label">Produk</label>
                        <div class="modal-product-preview">
                            <img id="modalProductImage" src="" alt="Foto produk" class="modal-product-img">
                        </div>
                        <input type="text" class="form-control" id="modalProductName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga</label>
                        <input type="text" class="form-control" id="modalProductPrice" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok Tersedia</label>
                        <input type="text" class="form-control" id="modalProductStock" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah</label>
                        <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama / Meja (opsional)</label>
                        <input type="text" name="customer_name" class="form-control" placeholder="Contoh: Budi / Meja 3">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-modal-close>Batal</button>
                    <button type="submit" class="btn-order-card">Konfirmasi Pesan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/main.js"></script>
<script src="assets/js/realtime.js"></script>
</body>
</html>