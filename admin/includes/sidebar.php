<?php
/**
 * Sidebar Admin - Kopi Dakas
 * Digunakan bersama di semua halaman admin.
 *
 * Parameter yang diterima (opsional, dengan default):
 *   $currentPage - nama file halaman yang sedang aktif (contoh: 'dashboard.php')
 *                  digunakan untuk menandai item navigasi yang aktif.
 */
$currentPage = $currentPage ?? 'dashboard.php';

$isActive = function (string $page) use ($currentPage): string {
    return $page === $currentPage
        ? 'text-brand-blue bg-brand-blue/5 border-l-3 border-l-brand-blue'
        : 'text-gray-600 hover:bg-gray-50 hover:text-brand-blue transition-colors border-l-3 border-l-transparent hover:border-l-brand-blue';
};
?>
<link rel="stylesheet" href="../assets/css/admin-responsive.css">
<button type="button" class="admin-menu-toggle" aria-label="Buka menu navigasi" aria-controls="adminSidebar" aria-expanded="false">
    <i class="bi bi-list" aria-hidden="true"></i>
</button>
<div class="sidebar-overlay" aria-hidden="true"></div>
<!-- Sidebar -->
<nav id="adminSidebar" class="sidebar fixed top-0 left-0 h-screen w-[260px] bg-white border-r border-gray-100 z-1000 flex flex-col" aria-label="Navigasi admin">
    <div class="sidebar-header flex items-center gap-3 px-6 py-5 border-b border-gray-100 bg-white">
        <img src="../assets/logo/logo.jpeg" alt="Logo Kopi Dakas" class="w-10 h-10 rounded-lg object-cover">
        <span class="font-bold text-xl tracking-tight text-gray-900">Kopi Dakas</span>
    </div>
    <ul class="nav-list flex-1 overflow-y-auto p-0 m-0 list-none">
        <li>
            <a href="dashboard.php" class="nav-item flex items-center gap-3 px-6 py-3.5 text-sm font-semibold <?= $isActive('dashboard.php') ?>">
                <i class="bi bi-speedometer2 w-5"></i>
                Dashboard
            </a>
        </li>
        <li>
            <a href="products.php" class="nav-item flex items-center gap-3 px-6 py-3.5 text-sm font-semibold <?= $isActive('products.php') ?>">
                <i class="bi bi-box w-5"></i>
                Kelola Produk
            </a>
        </li>
        <li>
            <a href="orders.php" class="nav-item flex items-center gap-3 px-6 py-3.5 text-sm font-semibold <?= $isActive('orders.php') ?>">
                <i class="bi bi-cart w-5"></i>
                Kelola Pesanan
            </a>
        </li>
        <li>
            <a href="stock.php" class="nav-item flex items-center gap-3 px-6 py-3.5 text-sm font-semibold <?= $isActive('stock.php') ?>">
                <i class="bi bi-box-seam w-5"></i>
                Kelola Stok
            </a>
        </li>
        <li>
            <a href="profit.php" class="nav-item flex items-center gap-3 px-6 py-3.5 text-sm font-semibold <?= $isActive('profit.php') ?>">
                <i class="bi bi-currency-dollar w-5"></i>
                Keuntungan
            </a>
        </li>
        <li>
            <a href="profile.php" class="nav-item flex items-center gap-3 px-6 py-3.5 text-sm font-semibold <?= $isActive('profile.php') ?>">
                <i class="bi bi-person w-5"></i>
                Profile
            </a>
        </li>
        <li>
            <a href="settings.php" class="nav-item flex items-center gap-3 px-6 py-3.5 text-sm font-semibold <?= $isActive('settings.php') ?>">
                <i class="bi bi-gear w-5"></i>
                Pengaturan Sistem
            </a>
        </li>
        <li>
            <a href="logout.php" class="nav-item flex items-center gap-3 px-6 py-3.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 hover:text-brand-red transition-colors border-l-3 border-l-transparent hover:border-l-brand-red">
                <i class="bi bi-box-arrow-right w-5"></i>
                Keluar
            </a>
        </li>
    </ul>
    <div class="sidebar-footer px-6 py-5 border-t border-gray-100">
        <p class="text-[0.75rem] text-gray-400 mb-1">Versi 1.0.0</p>
        <p class="text-[0.75rem] text-gray-400">Sistem Penjualan Kopi</p>
    </div>
</nav>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('adminSidebar');
        const toggle = document.querySelector('.admin-menu-toggle');
        const overlay = document.querySelector('.sidebar-overlay');
        if (!sidebar || !toggle || !overlay) return;

        function closeSidebar() {
            sidebar.classList.remove('is-open');
            document.body.classList.remove('sidebar-open');
            toggle.setAttribute('aria-expanded', 'false');
        }

        toggle.addEventListener('click', function () {
            const isOpen = sidebar.classList.toggle('is-open');
            document.body.classList.toggle('sidebar-open', isOpen);
            toggle.setAttribute('aria-expanded', String(isOpen));
        });
        overlay.addEventListener('click', closeSidebar);
        sidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeSidebar);
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeSidebar();
        });
    });
</script>
