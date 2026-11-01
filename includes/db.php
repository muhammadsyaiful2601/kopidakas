<?php
/**
 * Konfigurasi dan koneksi database menggunakan PDO (PHP Data Objects)
 * Menggunakan Prepared Statements untuk mencegah SQL Injection (PRD Bagian 4)
 */
$host     = 'localhost';
$dbname   = 'kopi_penjualan';
$username = 'root';
$password = '';
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,  // Gunakan prepared statement asli MySQL
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    http_response_code(500);
    die('Koneksi database gagal: ' . htmlspecialchars($e->getMessage()));
}