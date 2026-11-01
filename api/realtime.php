<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$scope = $_GET['scope'] ?? 'catalog';

try {
    if ($scope === 'orders') {
        $rows = $pdo->query(
            "SELECT id, status, created_at
             FROM orders
             ORDER BY id ASC"
        )->fetchAll();
    } elseif ($scope === 'all') {
        $orders = $pdo->query(
            "SELECT id, status, created_at
             FROM orders
             ORDER BY id ASC"
        )->fetchAll();
        $products = $pdo->query(
            "SELECT id, name, price, stock, image_url
             FROM products
             ORDER BY id ASC"
        )->fetchAll();
        $rows = ['orders' => $orders, 'products' => $products];
    } elseif ($scope === 'catalog') {
        $rows = $pdo->query(
            "SELECT id, name, price, stock, image_url
             FROM products
             ORDER BY id ASC"
        )->fetchAll();
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Scope realtime tidak valid.']);
        exit;
    }

    echo json_encode([
        'signature' => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR)),
    ], JSON_THROW_ON_ERROR);
} catch (JsonException | PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal memeriksa pembaruan data.']);
}
