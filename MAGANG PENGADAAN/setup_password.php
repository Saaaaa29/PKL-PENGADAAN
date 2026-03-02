<?php
/**
 * setup_password.php
 * Helper untuk reset password admin
 * 
 * CARA PAKAI:
 * 1. Buka http://localhost/procurement/setup_password.php
 * 2. Akan otomatis reset password admin ke "admin123"
 * 3. HAPUS file ini setelah selesai setup!
 */

require_once __DIR__ . '/config/database.php';

$db = getDB();

// Buat hash baru menggunakan PHP password_hash (pasti kompatibel)
$newPassword = 'admin123';
$newHash     = password_hash($newPassword, PASSWORD_BCRYPT);

// Cek apakah user admin sudah ada
$q = $db->query("SELECT id FROM users WHERE username = 'admin'");

if ($q->num_rows > 0) {
    // Update password admin
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->bind_param('s', $newHash);
    $stmt->execute();
    $stmt->close();
    $action = 'diperbarui';
} else {
    // Insert admin baru
    $stmt = $db->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES ('admin', ?, 'Administrator', 'admin')");
    $stmt->bind_param('s', $newHash);
    $stmt->execute();
    $stmt->close();
    $action = 'dibuat';
}

// Verifikasi hash yang tersimpan
$q2   = $db->query("SELECT password FROM users WHERE username = 'admin'");
$row  = $q2->fetch_assoc();
$valid = password_verify($newPassword, $row['password']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Setup Password - SIPPA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">
<div class="card shadow" style="max-width:500px; width:100%;">
    <div class="card-header bg-<?= $valid ? 'success' : 'danger' ?> text-white">
        <h5 class="mb-0">
            <i class="bi bi-<?= $valid ? 'check-circle' : 'x-circle' ?>"></i>
            Setup Password Admin
        </h5>
    </div>
    <div class="card-body">
        <?php if ($valid): ?>
            <div class="alert alert-success">
                <strong>✅ Berhasil!</strong> Password admin <?= $action ?> dengan sukses.
            </div>
            <table class="table table-bordered">
                <tr><td><strong>Username</strong></td><td><code>admin</code></td></tr>
                <tr><td><strong>Password</strong></td><td><code>admin123</code></td></tr>
                <tr><td><strong>Hash</strong></td><td style="word-break:break-all;font-size:11px;"><?= htmlspecialchars($row['password']) ?></td></tr>
                <tr><td><strong>Verifikasi</strong></td><td><span class="text-success fw-bold">✅ Valid</span></td></tr>
            </table>
            <div class="alert alert-warning mt-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>PENTING:</strong> Hapus file <code>setup_password.php</code> setelah selesai!
            </div>
            <a href="modules/auth/login.php" class="btn btn-primary w-100">
                → Pergi ke halaman Login
            </a>
        <?php else: ?>
            <div class="alert alert-danger">
                <strong>❌ Gagal!</strong> Terjadi kesalahan saat menyimpan password. Periksa koneksi database.
            </div>
        <?php endif; ?>
    </div>
</div>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</body>
</html>
