-- ============================================
-- Skema Database: Sistem Penjualan Kopi
-- Sumber: PRD Sistem Penjualan Kopi (Bagian 5)
-- Jalankan di phpMyAdmin, MySQL CLI, atau HeidiSQL
-- ============================================

CREATE DATABASE IF NOT EXISTS kopi_penjualan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kopi_penjualan;

-- Tabel admins (PRD: id, username, password hashed)
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- Tabel products (PRD: id, name, description, price, stock, image_url)
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(12,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image_url VARCHAR(255)
) ENGINE=InnoDB;

-- Tabel orders (PRD: id, customer_name, product_id FK, quantity, status ENUM, created_at)
-- Status: pending = belum diproses, completed = selesai (stok dikurangi), cancelled = dibatalkan (stok dikembalikan jika sudah selesai)
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100),
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    status ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Jalankan perintah ini pada database yang SUDAH ada untuk menambahkan status 'cancelled':
-- ALTER TABLE orders MODIFY COLUMN status ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'pending';

-- Tabel tiket pelanggan (customer support tickets)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel stock_movements (pelacakan stok masuk & keluar)
CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    type ENUM('in','out') NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    note VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Pengaturan sistem (termasuk foto hero landing page)
CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Data admin default (username: admin, password: admin123)
-- Hash dihasilkan dengan PHP password_hash(PASSWORD_DEFAULT) untuk password "admin123"
INSERT INTO admins (username, password) VALUES
('admin', '$2y$12$syPxuKI0Hh8Sw.qYgLB1fe5mtMOayQwJSuqoDCBGcGI1NIHCPnD8a');

-- Data produk contoh (dengan gambar)
INSERT INTO products (name, description, price, stock, image_url) VALUES
('Espresso', 'Kopi hitam pekat dengan crema di atasnya.', 18000, 12, 'https://images.unsplash.com/photo-1510707585785-2c240a7ecf7a?w=400&q=80'),
('Latte', 'Espresso dengan susu steamed dan busa halus.', 25000, 10, 'https://images.unsplash.com/photo-1565803653702-d9dbb1f159c2?w=400&q=80'),
('Cappuccino', 'Espresso, susu steamed, dan busa susu tebal.', 26000, 6, 'https://images.unsplash.com/photo-1534737413270-f529bc84d0f2?w=400&q=80'),
('Americano', 'Espresso dicampur air panas.', 20000, 8, 'https://images.unsplash.com/photo-1541742461819-e56cfb6593d2?w=400&q=80'),
('Mocha', 'Espresso, cokelat, dan susu.', 28000, 0, 'https://images.unsplash.com/photo-1556933993-6c6e6165c274?w=400&q=80');