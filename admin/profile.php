<?php
/**
 * Admin Profile - Kopi Dakas
 * Mengubah username dan password akun admin yang sedang login.
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$adminId   = (int)($_SESSION['admin_id'] ?? 0);
$adminUser = $_SESSION['admin_username'] ?? '';

$message    = '';
$messageType = '';

// Ambil data admin terkini dari database
$stmt = $pdo->prepare("SELECT id, username FROM admins WHERE id = ? LIMIT 1");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

if (!$admin) {
    $_SESSION = [];
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// ===================== UBAH USERNAME =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_username') {
    $newUsername = trim($_POST['username'] ?? '');

    if ($newUsername === '') {
        $message    = 'Username tidak boleh kosong.';
        $messageType = 'danger';
    } elseif (strlen($newUsername) < 3 || strlen($newUsername) > 50) {
        $message    = 'Username harus antara 3 hingga 50 karakter.';
        $messageType = 'danger';
    } else {
        // Cek apakah username sudah digunakan admin lain
        $chk = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ? LIMIT 1");
        $chk->execute([$newUsername, $adminId]);
        if ($chk->fetch()) {
            $message    = 'Username "' . htmlspecialchars($newUsername) . '" sudah digunakan.';
            $messageType = 'danger';
        } else {
            $pdo->prepare("UPDATE admins SET username = ? WHERE id = ?")
                ->execute([$newUsername, $adminId]);
            $_SESSION['admin_username'] = $newUsername;
            $adminUser = $newUsername;
            $message    = 'Username berhasil diperbarui.';
            $messageType = 'success';
        }
    }
}

// ===================== UBAH PASSWORD =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $message    = 'Semua kolom password harus diisi.';
        $messageType = 'danger';
    } elseif (strlen($newPassword) < 6) {
        $message    = 'Password baru minimal 6 karakter.';
        $messageType = 'danger';
    } elseif ($newPassword !== $confirmPassword) {
        $message    = 'Password baru dan konfirmasi tidak cocok.';
        $messageType = 'danger';
    } else {
        // Verifikasi password lama
        $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ? LIMIT 1");
        $stmt->execute([$adminId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($currentPassword, $row['password'])) {
            $message    = 'Password saat ini salah.';
            $messageType = 'danger';
        } elseif ($currentPassword === $newPassword) {
            $message    = 'Password baru harus berbeda dari password saat ini.';
            $messageType = 'warning';
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?")
                ->execute([$hash, $adminId]);
            $message    = 'Password berhasil diperbarui.';
            $messageType = 'success';
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile - Admin Kopi Dakas</title>
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

<?php $currentPage = 'profile.php'; require_once __DIR__ . '/includes/sidebar.php'; ?>

<!-- Top Header -->
<header class="top-header fixed top-0 left-[260px] right-0 h-[70px] bg-white border-b border-gray-100 z-900 flex items-center justify-between px-6">
    <div class="search-box relative w-[320px]">
        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="text" placeholder="Cari..." class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition-all placeholder-gray-400" disabled>
    </div>
    <div class="header-actions flex items-center gap-3">
        <a href="logout.php" class="text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-brand-red rounded-md px-3.5 py-1.5 transition-colors whitespace-nowrap">Keluar</a>
    </div>
</header>

<!-- Main Content -->
<main class="main-content ml-[260px] mt-[70px] p-6 min-h-screen">
    <div class="page-header flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <h1 class="flex items-center gap-2.5 font-bold text-gray-800 text-xl leading-none">
                <i class="bi bi-person text-brand-blue text-xl"></i>
                Profile Saya
            </h1>
            <p class="text-sm text-gray-500 mt-1">Kelola data akun admin Anda.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="dashboard.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm text-gray-700 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 transition-colors">
                <i class="bi bi-speedometer2 w-4 h-4"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="mb-6 p-4 rounded-lg flex items-start gap-3 <?= $messageType === 'success' ? 'bg-green-50 border border-green-100 text-green-800' : ($messageType === 'warning' ? 'bg-amber-50 border border-amber-100 text-amber-800' : 'bg-brand-red/10 border border-brand-red/200 text-brand-red-dark') ?>">
        <i class="bi bi-<?= $messageType === 'success' ? 'check-circle-fill' : ($messageType === 'warning' ? 'exclamation-triangle-fill' : 'exclamation-circle-fill') ?> text-[1.1rem] mt-0.5 flex-shrink-0"></i>
        <div class="text-sm font-medium"><?= htmlspecialchars($message) ?></div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-2xl">
        <!-- Form Ubah Username -->
        <div class="bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <h5 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                    <i class="bi bi-person-badge text-brand-blue"></i>
                    Ubah Username
                </h5>
            </div>
            <div class="p-5">
                <form method="POST" action="profile.php" class="space-y-4">
                    <input type="hidden" name="action" value="update_username">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Username Baru</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($adminUser) ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition-colors" placeholder="Masukkan username baru" required>
                        <p class="text-[0.7rem] text-gray-500 mt-1">Minimal 3 karakter, maksimal 50 karakter.</p>
                    </div>
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-brand-blue rounded-lg shadow-sm hover:bg-brand-blue-dark transition-colors">
                        <i class="bi bi-save w-4 h-4"></i> Simpan Username
                    </button>
                </form>
            </div>
        </div>

        <!-- Form Ubah Password -->
        <div class="bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <h5 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                    <i class="bi bi-key text-brand-blue"></i>
                    Ubah Password
                </h5>
            </div>
            <div class="p-5">
                <form method="POST" action="profile.php" class="space-y-4">
                    <input type="hidden" name="action" value="update_password">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Password Saat Ini</label>
                        <input type="password" name="current_password" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition-colors" placeholder="Masukkan password saat ini" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Password Baru</label>
                        <input type="password" name="new_password" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition-colors" placeholder="Masukkan password baru" required>
                        <p class="text-[0.7rem] text-gray-500 mt-1">Minimal 6 karakter.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition-colors" placeholder="Ketik ulang password baru" required>
                    </div>
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-brand-blue rounded-lg shadow-sm hover:bg-brand-blue-dark transition-colors">
                        <i class="bi bi-key w-4 h-4"></i> Simpan Password
                    </button>
                </form>
            </div>
        </div>

        <!-- Info Akun -->
        <div class="lg:col-span-2 bg-white border border-gray-100 rounded-xl shadow-sm overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <h5 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                    <i class="bi bi-info-circle text-brand-blue"></i>
                    Informasi Akun
                </h5>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">ID Admin</p>
                        <p class="text-sm font-medium text-gray-900">#<?= (int)$admin['id'] ?></p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Username Saat Ini</p>
                        <p class="text-sm font-medium text-gray-900 break-all"><?= htmlspecialchars($adminUser) ?></p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Role</p>
                        <p class="text-sm font-medium text-gray-900">Super Admin</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Sistem</p>
                        <p class="text-sm font-medium text-gray-900">Sistem Penjualan Kopi v1.0.0</p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-xs text-gray-400">Keselamatan akun: Sebaiknya ganti password secara rutin dan jangan berbagi password dengan orang lain.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
