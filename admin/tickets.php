<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$message   = '';
$messageType = '';

// Proses balasan admin ke tiket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply') {
    $ticketId = filter_input(INPUT_POST, 'ticket_id', FILTER_VALIDATE_INT);
    $reply    = trim($_POST['admin_reply'] ?? '');
    $status   = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    if (!$ticketId || !$reply || !in_array($status, ['open','in_progress','replied','closed'], true)) {
        $message    = 'Data balasan tiket tidak valid.';
        $messageType = 'danger';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT id FROM tickets WHERE id = ? FOR UPDATE");
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch();
            if (!$ticket) {
                $message    = 'Tiket tidak ditemukan.';
                $messageType = 'danger';
                $pdo->rollBack();
            } else {
                $adminName = $_SESSION['admin_name'] ?? 'Admin';
                $pdo->prepare("
                    UPDATE tickets
                    SET admin_reply = ?, replied_by = ?, replied_at = NOW(), status = ?, updated_at = NOW()
                    WHERE id = ?
                ")->execute([$reply, $adminName, $status, $ticketId]);
                $pdo->commit();
                $message    = 'Balasan tiket #' . (int)$ticketId . ' tersimpan.';
                $messageType = 'success';
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message    = 'Gagal: ' . htmlspecialchars($e->getMessage());
            $messageType = 'danger';
        }
    }
}

// Proses ubah status tiket (tanpa balasan)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'status') {
    $ticketId = filter_input(INPUT_POST, 'ticket_id', FILTER_VALIDATE_INT);
    $statusRaw = $_POST['status'] ?? null;
    $status = filter_var($statusRaw, FILTER_SANITIZE_STRING);
    if ($ticketId && in_array($status, ['open','in_progress','replied','closed'], true)) {
        try {
            $pdo->prepare("UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$status, $ticketId]);
            $message    = 'Status tiket #' . (int)$ticketId . ' diperbarui.';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message    = 'Gagal: ' . htmlspecialchars($e->getMessage());
            $messageType = 'danger';
        }
    }
}

$tickets = $pdo->query("
    SELECT t.id, t.customer_name, t.customer_email, t.subject, t.message, t.status,
           t.admin_reply, t.replied_by, t.replied_at, t.created_at, t.updated_at
    FROM tickets t
    ORDER BY t.created_at DESC
")->fetchAll();

$statusLabel = [
    'open'        => 'Baru',
    'in_progress' => 'Diproses',
    'replied'     => 'Dibalas',
    'closed'      => 'Tutup',
];
$statusBadge = [
    'open'        => 'bg-blue-50 text-blue-700',
    'in_progress' => 'bg-amber-50 text-amber-700',
    'replied'     => 'bg-emerald-50 text-emerald-700',
    'closed'      => 'bg-gray-50 text-gray-700',
];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tiket Pelanggan - Admin Kopi Dakas</title>
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
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="antialiased" data-realtime-url="../api/realtime.php?scope=all">

<?php $currentPage = 'tickets.php'; require_once __DIR__ . '/includes/sidebar.php'; ?>

<header class="top-header fixed top-0 left-[260px] right-0 h-[70px] bg-white border-b border-gray-100 z-900 flex items-center justify-between px-6">
    <div class="search-box relative w-[320px]">
        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="text" placeholder="Cari tiket..." class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:ring-2 focus:ring-brand-blue focus:border-brand-blue outline-none transition-all placeholder-gray-400">
    </div>
    <div class="header-actions flex items-center gap-3">
        <a href="logout.php" class="text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-brand-red rounded-md px-3.5 py-1.5 transition-colors whitespace-nowrap">Keluar</a>
    </div>
</header>

<main class="main-content ml-[260px] mt-[70px] p-6 min-h-screen">
    <div class="page-header flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <h1 class="flex items-center gap-2.5 font-bold text-gray-800 text-xl leading-none">
                <i class="bi bi-chat-text text-brand-blue text-xl"></i>
                Tiket Pelanggan
            </h1>
            <p class="text-sm text-gray-500 mt-1">Kelola dan balas pertanyaan / keluhan pelanggan.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="orders.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm text-gray-700 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 transition-colors">
                <i class="bi bi-speedometer2 w-4 h-4"></i> Kembali ke Pesanan
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
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[8%]">#</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[18%]">Pelanggan</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[20%]">Subjek</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[12%]">Status</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[18%]">Waktu Dibuat</th>
                        <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100 w-[24%]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($tickets): foreach ($tickets as $t):
                        $label     = $statusLabel[$t['status']] ?? ucfirst($t['status']);
                        $badge     = $statusBadge[$t['status']] ?? 'bg-gray-50 text-gray-700';
                        $isReplied = !empty($t['admin_reply']);
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-900 font-medium text-sm">#<?= (int)$t['id'] ?></td>
                        <td class="px-6 py-4 border-b border-gray-100">
                            <div class="text-gray-900 font-medium text-sm"><?= htmlspecialchars($t['customer_name']) ?></div>
                            <?php if ($t['customer_email']): ?>
                            <div class="text-[0.7rem] text-gray-500"><?= htmlspecialchars($t['customer_email']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 border-b border-gray-100">
                            <div class="text-gray-900 font-medium text-sm"><?= htmlspecialchars($t['subject']) ?></div>
                            <div class="text-[0.7rem] text-gray-500 mt-1 max-w-xs truncate"><?= nl2br(htmlspecialchars($t['message'])) ?></div>
                        </td>
                        <td class="px-6 py-4 border-b border-gray-100">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[0.7rem] font-semibold uppercase <?= $badge ?>">
                                <i class="bi bi-<?= $t['status'] === 'open' ? 'chat' : ($t['status'] === 'in_progress' ? 'gear' : ($t['status'] === 'replied' ? 'check2' : 'x')) ?> [&>i]:text-[0.65rem]"></i>
                                <?= htmlspecialchars($label) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 border-b border-gray-100 text-gray-500 text-sm"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                        <td class="px-6 py-4 border-b border-gray-100">
                            <!-- Form balasan admin -->
                            <form method="POST" action="tickets.php" class="space-y-2" onsubmit="return confirm('Simpan balasan untuk tiket ini?');">
                                <input type="hidden" name="action" value="reply">
                                <input type="hidden" name="ticket_id" value="<?= (int)$t['id'] ?>">
                                <div class="flex items-center gap-2">
                                    <textarea name="admin_reply" rows="2" class="flex-1 px-2 py-1.5 text-xs border border-gray-200 rounded bg-gray-50 focus:ring-2 focus:ring-brand-blue outline-none" placeholder="Ketik balasan..."><?= htmlspecialchars($t['admin_reply'] ?? '') ?></textarea>
                                    <select name="status" class="px-2 py-1.5 text-xs border border-gray-200 rounded bg-white focus:ring-2 focus:ring-brand-blue outline-none">
                                        <?php foreach (['open','in_progress','replied','closed'] as $s): ?>
                                        <option value="<?= $s ?>" <?= $t['status'] === $s ? 'selected' : '' ?>><?= $statusLabel[$s] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="px-2 py-1.5 text-xs font-medium text-white bg-brand-blue rounded hover:bg-brand-blue-dark transition-colors">
                                        <i class="bi bi-send"></i>
                                    </button>
                                </div>
                                <?php if ($isReplied): ?>
                                <div class="text-xs text-gray-500">
                                    Dibalas oleh <b><?= htmlspecialchars($t['replied_by'] ?? '-') ?></b> pada <?= date('d/m/Y H:i', strtotime($t['replied_at'])) ?>
                                </div>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center text-gray-400">
                            <i class="bi bi-inbox text-3xl mb-3 opacity-40"></i>
                            <p class="text-sm font-medium">Belum ada tiket pelanggan.</p>
                            <p class="text-xs text-gray-400 mt-1">Tiket akan muncul di sini setelah pelanggan mengirimkan pesan.</p>
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