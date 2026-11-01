<?php
/**
 * Halaman login admin publik: /login
 */
declare(strict_types=1);
require_once __DIR__ . '/admin/includes/auth.php';

if (isLoggedIn()) {
    header('Location: admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password harus diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $redirect = $_SESSION['redirect_after_login'] ?? 'admin/dashboard.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        }

        $error = 'Username atau password salah.';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - Kopi Dakas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page">
<div class="login-card">
    <div class="login-brand">
        <img src="assets/logo/logo.jpeg" alt="Logo Kopi Dakas">
        <span>Kopi Dakas</span>
    </div>
    <a href="index.php" class="back-arrow" aria-label="Kembali"><i class="bi bi-arrow-left"></i></a>
    <div class="login-header">
        <h1 class="login-title">Selamat datang kembali</h1>
        <p class="login-subtitle">Masuk ke panel admin untuk mengelola menu, stok, dan pesanan Kopi Dakas.</p>
    </div>
    <?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="login.php">
        <label class="login-field">
            <span>Username</span>
            <div class="input-group-icon">
                <i class="input-icon bi bi-person"></i>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
            </div>
        </label>
        <label class="login-field">
            <span>Password</span>
            <div class="input-group-icon">
                <i class="input-icon bi bi-lock"></i>
                <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Kata sandi" required>
                <button type="button" class="toggle-password" id="togglePassword" aria-label="Tampilkan kata sandi">
                    <i class="bi bi-eye-slash"></i>
                </button>
            </div>
        </label>
        <button type="submit" class="btn-login">Masuk</button>
    </form>
    <a href="index.php" class="back-link">← Kembali ke landing page</a>
</div>
<script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('loginPassword');
    const toggleIcon = togglePassword.querySelector('i');
    togglePassword.addEventListener('click', function () {
        const type = passwordInput.type === 'password' ? 'text' : 'password';
        passwordInput.type = type;
        toggleIcon.classList.toggle('bi-eye');
        toggleIcon.classList.toggle('bi-eye-slash');
    });
</script>
</body>
</html>
