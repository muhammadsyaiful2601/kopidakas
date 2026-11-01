# Kopi Dakas — Sistem Penjualan Kopi (PHP Native)

Sistem pemesanan dan manajemen penjualan kopi sederhana berbasis **PHP Native (PDO + MySQL)** dengan antarmuka admin modern (Tailwind CSS + Bootstrap Icons). Cocok untuk kopi/kafe kecil yang ingin mengelola pesanan, stok, dan tiket pelanggan dari satu tempat.

## 🚀 Fitur Utama

### Pelanggan (Landing Page)
- Katalog produk kopi dengan nama, harga, gambar, dan indikator stok.
- Produk yang stoknya habis ditandai "Habis" dan tidak bisa dipesan.
- Form pemesanan dengan validasi stok real-time sebelum disimpan.
- Pesanan disimpan dengan status `pending` (menunggu konfirmasi admin).

### Admin (Halaman Admin)
- **Dashboard**: ringkasan pesanan hari ini, pending, selesai, dan peringatan stok menipis.
- **Kelola Pesanan**: tandai pesanan sebagai *Selesai* (stok dikurangi) atau *Batalkan* (stok dikembalikan jika sudah selesai).
- **Tiket Pelanggan**: lihat, balas, dan ubah status tiket pelanggan (Baru / Diproses / Dibalas / Tutup).
- **Kelola Produk**: tambah, edit, dan hapus produk kopi.
- **Kelola Stok**: catat pergerakan stok masuk & keluar dengan history.
- **Keuntungan**: laporan pendapatan dan produk laris berdasarkan pesanan yang sudah selesai.
- **Pengaturan Sistem** dan **Profile** admin.

## 🛠️ Teknologi
- PHP 8+ (Native, tanpa framework)
- PDO + MySQL (Prepared Statements untuk keamanan)
- Tailwind CSS (via CDN)
- Bootstrap Icons (via CDN)
- Vanilla JavaScript

## 📁 Struktur Folder
```
dakas/
├── index.php               # Landing page pelanggan & form pemesanan
├── login.php               # Login admin
├── reset_password.php      # Reset password admin
├── .htaccess               # Konfigurasi Apache
├── api/
│   └── realtime.php        # Data real-time untuk dashboard
├── admin/
│   ├── dashboard.php       # Dashboard admin
│   ├── orders.php          # Kelola pesanan (selesai / batalkan)
│   ├── tickets.php         # Tiket pelanggan (baru)
│   ├── products.php        # Kelola produk
│   ├── stock.php           # Kelola stok & pergerakan
│   ├── profit.php          # Laporan keuntungan
│   ├── profile.php         # Profile admin
│   ├── settings.php        # Pengaturan sistem
│   ├── logout.php
│   └── includes/
│       ├── auth.php        # Guard autentikasi admin
│       └── sidebar.php     # Navigasi sidebar admin
├── includes/
│   ├── db.php              # Koneksi database PDO
│   └── settings.php        # Fungsi pengaturan sistem
├── database/
│   ├── schema.sql          # Skema database lengkap
│   └── migrate.php         # Skrip migrasi untuk hosting (non-Laravel)
├── assets/
│   ├── css/
│   ├── js/
│   └── logo/
└── README.md
```

## ⚙️ Instalasi

### 1. Siapkan Database
Buat database dan impor skema:
```sql
-- Jalankan di phpMyAdmin atau MySQL CLI
SOURCE database/schema.sql;
```
Atau impor file `database/schema.sql` langsung melalui phpMyAdmin.

### 2. Konfigurasi Koneksi
Buka `includes/db.php` dan sesuaikan dengan kredensial hosting Anda:
```php
$host     = 'localhost';
$dbname   = 'kopi_penjualan';
$username = 'root';
$password = ''; // sesuaikan dengan password database Anda
```

### 3. Upload ke Hosting
Upload semua file ke folder public HTML (mis. `public_html` atau `www`).

### 4. Migrasi (Opsional untuk Database yang Sudah Ada)
Jika database sudah ada dari versi lama dan ingin menambahkan fitur baru (status `cancelled` dan tabel `tickets`) tanpa menghapus data lama, jalankan skrip migrasi:
1. Upload `database/migrate.php`.
2. Buka di browser: `https://domainkamu.com/database/migrate.php`
3. **Hapus file `database/migrate.php`** setelah selesai untuk alasan keamanan.

> Catatan: Skrip migrasi menggunakan `CREATE TABLE IF NOT EXISTS` dan `ALTER TABLE ... MODIFY COLUMN`, sehingga **aman dan tidak mengganggu data existing** (tidak seperti `php artisan migrate:fresh` di Laravel).

### 5. Login Admin
- Username: `admin`
- Password: `admin123`
(Seluruhnya diatur di `database/schema.sql`; segera ganti password setelah login pertama kali.)

## 📋 Alur Status Pesanan

| Status | Keterangan | Pengaruh Stok |
|--------|-----------|---------------|
| `pending` | Pesanan baru diterima, menunggu admin | Stok belum dikurangi |
| `completed` | Admin tandai selesai | Stok dikurangi & dicatat barang keluar |
| `cancelled` | Pesanan dibatalkan admin | Jika sebelumnya `completed`, stok dikembalikan & dicatat barang masuk |

## 🎫 Sistem Tiket Pelanggan
Tiket pelanggan digunakan untuk keluhan, pertanyaan, atau permintaan khusus dari pelanggan. Admin dapat:
- Melihat daftar semua tiket dengan statusnya.
- Membalas tiket langsung dari halaman admin.
- Mengubah status tiket: `Baru` → `Diproses` → `Dibalas` → `Tutup`.

## 🔐 Keamanan
- Semua query menggunakan **PDO Prepared Statements** (terlindungi dari SQL Injection).
- Halaman admin dilindungi oleh session guard (`auth.php` → `requireLogin()`).
- Password admin di-hash menggunakan `password_hash()`.

## 🤝 Kontribusi
Pull request dan saran sangat diterima. Silakan buat issue terlebih dahulu untuk perubahan besar.

## 📄 Lisensi
Proyek ini dikembangkan untuk kebutuhan pengelolaan penjualan kopi lokal.

---
Dikembangkan oleh **muhammadsyaiful2601** — [kopidakas](https://github.com/muhammadsyaiful2601/kopidakas.git)