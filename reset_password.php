<?php
/**
 * Skrip utilitas: reset password admin menjadi 'admin123'
 * Jalankan sekali via browser: http://localhost:8002/reset_password.php
 * Lalu hapus file ini setelah digunakan untuk alasan keamanan.
 */
declare(strict_types=1);
require_once 'includes/db.php';

$newPassword = 'admin123';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE username = ?");
$stmt->execute([$hash, 'admin']);

echo "<h3>Reset Password Berhasil</h3>";
echo "<p>Username: <strong>admin</strong></p>";
echo "<p>Password: <strong>admin123</strong></p>";
echo "<p><a href='login.php'>Klik di sini untuk login ke admin</a></p>";
echo "<p><strong>Peringatan:</strong> Hapus file <code>reset_password.php</code> setelah ini untuk keamanan.</p>";