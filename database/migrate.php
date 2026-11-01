<?php
/**
 * Skrip Migrasi Database (PHP Native) - Sistem Penjualan Kopi "Kopi Dakas"
 *
 * Digunakan di hosting yang tidak memiliki php artisan migrate (Laravel).
 * Skrip ini AMAN dan TIDAK MENGANGGU data yang sudah ada:
 *   - Hanya menambah nilai 'cancelled' ke ENUM status orders (MODIFY COLUMN).
 *   - Membuat tabel tickets jika belum ada (CREATE TABLE IF NOT EXISTS).
 *   - Tidak menghapus atau mengubah data existing.
 *
 * Cara pakai di hosting:
 *   1. Upload file ini ke folder database/ (atau root).
 *   2. Buka di browser: https://namadomain.com/database/migrate.php
 *   3. Setelah sukses, sebaiknya hapus file ini untuk alasan keamanan.
 *
 * Atau jalankan via CLI: php database/migrate.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/db.php';

$results = [];

function addResult(string $step, bool $success, string $message): void {
    global $results;
    $results[] = [
        'step'     => $step,
        'success'  => $success,
        'message'  => $message,
    ];
}

// MIGRASI 1: Tambah status 'cancelled' ke tabel orders (AMAN)
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM orders WHERE Field = 'status'");
    $column = $stmt->fetch();

    if ($column) {
        $type = $column['Type'] ?? '';
        if (strpos($type, 'cancelled') !== false) {
            addResult('orders.status', true, 'Status "cancelled" sudah ada pada ENUM. Tidak perlu diubah.');
        } else {
            $pdo->exec("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'pending'");
            addResult('orders.status', true, 'Berhasil menambahkan status "cancelled" ke ENUM orders.status.');
        }
    } else {
        addResult('orders.status', false, 'Tabel orders atau kolom status tidak ditemukan.');
    }
} catch (PDOException $e) {
    addResult('orders.status', false, 'Gagal: ' . htmlspecialchars($e->getMessage()));
}

// MIGRASI 2: Buat tabel tickets jika belum ada (AMAN)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_name VARCHAR(100) NOT NULL,
            customer_email VARCHAR(100) NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('open','in_progress','replied','closed') NOT NULL DEFAULT 'open',
            admin_reply TEXT NULL,
            replied_by VARCHAR(100) NULL,
            replied_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    addResult('tickets table', true, 'Tabel tickets siap (dibuat jika belum ada).');
} catch (PDOException $e) {
    addResult('tickets table', false, 'Gagal: ' . htmlspecialchars($e->getMessage()));
}

// MIGRASI 3: Pastikan tabel stock_movements ada (AMAN)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS stock_movements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            type ENUM('in','out') NOT NULL,
            quantity INT NOT NULL DEFAULT 0,
            note VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    addResult('stock_movements table', true, 'Tabel stock_movements siap (dibuat jika belum ada).');
} catch (PDOException $e) {
    addResult('stock_movements table', false, 'Gagal: ' . htmlspecialchars($e->getMessage()));
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Migrasi Database - Kopi Dakas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card shadow">
        <div class="card-header bg-brand-blue text-white">
            <h5 class="mb-0"><i class="bi bi-database"></i> Hasil Migrasi Database</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:25%;">Langkah</th>
                            <th style="width:10%;">Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['step']) ?></td>
                            <td>
                                <span class="badge bg-<?= $r['success'] ? 'success' : 'danger' ?>">
                                    <?= $r['success'] ? 'BERHASIL' : 'GAGAL' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($r['message']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="alert alert-warning mt-4">
                <i class="bi bi-info-circle"></i>
                <strong>Peringatan keamanan:</strong> Setelah migrasi selesai, sebaiknya
                <strong>hapus file <code>database/migrate.php</code></strong> agar tidak bisa diakses oleh publik.
            </div>

            <a href="../index.php" class="btn btn-outline-secondary">Kembali ke Beranda</a>
            <?php $allSuccess = !empty($results) && !array_filter($results, fn($r) => !$r['success']); ?>
            <?php if ($allSuccess): ?>
            <a href="../admin/tickets.php" class="btn btn-primary ms-2">Kelola Tiket Pelanggan</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    :root{--brand-blue:#1565C0;}
    .bg-brand-blue{background-color:var(--brand-blue)!important;}
</style>
</body>
</html>