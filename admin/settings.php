<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/settings.php';
requireLogin();

$message = '';
$messageType = '';
$heroImage = getSystemSetting($pdo, 'hero_image', 'assets/foto/foto landing page.jpeg');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_hero') {
    if (!isset($_FILES['hero_image']) || $_FILES['hero_image']['error'] === UPLOAD_ERR_NO_FILE) {
        $message = 'Pilih foto hero terlebih dahulu.';
        $messageType = 'danger';
    } elseif ($_FILES['hero_image']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Upload foto gagal. Silakan coba lagi.';
        $messageType = 'danger';
    } elseif ((int)$_FILES['hero_image']['size'] > 5 * 1024 * 1024) {
        $message = 'Ukuran foto maksimal 5 MB.';
        $messageType = 'danger';
    } else {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['hero_image']['tmp_name']);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($extensions[$mime]) || @getimagesize($_FILES['hero_image']['tmp_name']) === false) {
            $message = 'Format foto harus JPG, PNG, atau WEBP.';
            $messageType = 'danger';
        } else {
            $uploadDir = dirname(__DIR__) . '/uploads/system/';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                $message = 'Folder upload tidak dapat dibuat.';
                $messageType = 'danger';
            } else {
                $filename = 'hero_' . bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
                $target = $uploadDir . $filename;
                if (!move_uploaded_file($_FILES['hero_image']['tmp_name'], $target)) {
                    $message = 'Foto gagal disimpan.';
                    $messageType = 'danger';
                } else {
                    if (str_starts_with($heroImage, 'uploads/system/')) {
                        $oldPath = dirname(__DIR__) . '/' . $heroImage;
                        if (is_file($oldPath)) unlink($oldPath);
                    }
                    $heroImage = 'uploads/system/' . $filename;
                    saveSystemSetting($pdo, 'hero_image', $heroImage);
                    $message = 'Foto hero berhasil diperbarui.';
                    $messageType = 'success';
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan Sistem - Admin Kopi Dakas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { blue: '#1565C0', 'blue-dark': '#0D47A1', red: '#D62828', 'red-dark': '#B71C1C' } } } } };
    </script>
</head>
<body class="antialiased bg-gray-50 overflow-x-hidden">
<?php $currentPage = 'settings.php'; require_once __DIR__ . '/includes/sidebar.php'; ?>

<header class="top-header fixed top-0 left-[260px] right-0 h-[70px] bg-white border-b border-gray-100 z-900 flex items-center justify-between px-6">
    <div class="text-sm font-semibold text-gray-700">Pengaturan Sistem</div>
    <a href="logout.php" class="text-sm font-medium text-gray-600 hover:text-brand-red">Keluar</a>
</header>

<main class="main-content ml-[260px] mt-[70px] p-6 min-h-screen">
    <div class="page-header mb-8">
        <h1 class="text-xl font-bold text-gray-800">Pengaturan Sistem</h1>
        <p class="text-sm text-gray-500 mt-1">Atur tampilan utama halaman pelanggan.</p>
    </div>

    <?php if ($message): ?>
    <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-50 border border-green-100 text-green-800' : 'bg-red-50 border border-red-100 text-red-800' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <section class="bg-white border border-gray-100 rounded-xl shadow-sm p-5 max-w-3xl">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-brand-blue flex items-center justify-center">
                <i class="bi bi-image"></i>
            </div>
            <div>
                <h2 class="font-bold text-gray-900">Foto Hero Landing Page</h2>
                <p class="text-xs text-gray-500">Foto akan ditampilkan otomatis dalam format landscape 16:9.</p>
            </div>
        </div>
        <img src="../<?= htmlspecialchars($heroImage, ENT_QUOTES) ?>" alt="Preview foto hero" class="w-full aspect-video object-cover rounded-lg bg-gray-100 mb-5">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_hero">
            <label for="hero_image" class="block text-sm font-medium text-gray-700 mb-2">Pilih foto baru</label>
            <input id="hero_image" type="file" name="hero_image" accept="image/jpeg,image/png,image/webp" required
                class="block w-full text-sm text-gray-500 border border-gray-200 rounded-lg p-2 bg-gray-50">
            <p class="text-xs text-gray-400 mt-2">JPG, PNG, atau WEBP. Maksimal 5 MB.</p>
            <button type="submit" class="mt-5 inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-brand-blue rounded-lg shadow-sm hover:bg-brand-blue-dark">
                <i class="bi bi-upload"></i> Simpan Foto Hero
            </button>
        </form>
    </section>
</main>
</body>
</html>
