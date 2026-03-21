<?php
/**
 * modules/auth/login.php
 * Halaman login pengguna
 * 
 * Mendukung dua format password:
 * 1. PLAIN:namapassword  -> untuk setup awal, akan di-upgrade ke bcrypt otomatis
 * 2. Hash bcrypt         -> format normal setelah upgrade
 */

session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Jika sudah login, redirect ke dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';

// Proses form login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id, username, password, nama_lengkap, role FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $loginOk = false;

        if ($user) {
            $storedPass = $user['password'];

            // --- FORMAT 1: Plain text sementara (prefix PLAIN:) ---
            // Digunakan saat setup awal dari database.sql
            if (strpos($storedPass, 'PLAIN:') === 0) {
                $plainPass = substr($storedPass, 6); // hapus prefix "PLAIN:"
                if ($password === $plainPass) {
                    $loginOk = true;
                    // Upgrade otomatis ke bcrypt
                    $newHash = password_hash($password, PASSWORD_BCRYPT);
                    $upd = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upd->bind_param('si', $newHash, $user['id']);
                    $upd->execute();
                    $upd->close();
                }
            }
            // --- FORMAT 2: Hash bcrypt normal ---
            elseif (password_verify($password, $storedPass)) {
                $loginOk = true;
            }
        }

        if ($loginOk) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_nama'] = $user['nama_lengkap'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['username']  = $user['username'];

            header('Location: ' . BASE_URL . '/index.php');
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(90deg, #214E6F, #0D2A40);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .company-logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.3));
            margin-bottom: 0px;
        }
        .login-card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            max-width: 420px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(90deg, #214E6F, #0D2A40);
            border-radius: 20px 20px 0 0;
        }
        .form-control {
            border-radius: 10px;
            padding: 10px 14px;
            border: 1.5px solid #dee2e6;
        }
        .form-control:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67,97,238,0.15);
        }
        .btn-login {
            background: linear-gradient(90deg, #214E6F, #0D2A40);
            border: none;
            border-radius: 10px;
            padding: 11px;
            font-weight: 600;
        }
        .btn-login:hover { opacity: 0.9; }
        .input-group-text {
            border-radius: 10px 0 0 10px;
            border-right: none;
        }
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
    </style>
</head>
<body>
    <div class="login-card card">
        <div class="login-header p-4 text-white text-center">
            <div class="mb-3">
                <img src="<?= BASE_URL ?>/includes/LOGO.png"alt="Logo PT Air Minum Giri Menang"class="company-logo">
                <p class="mb-0 opacity-75" style="font-size:11px;">PT Air Minum Giri Menang (Perseroda)</p>
            </div>
            <h4 class="fw-bold mb-1"><?= APP_NAME ?></h4>
            <p class="mb-0 opacity-75" style="font-size:13px;"><?= APP_FULLNAME ?></p>
        </div>

        <div class="card-body p-4">
            <h6 class="fw-bold mb-4 text-center text-muted">Silakan masuk untuk melanjutkan</h6>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-person text-muted"></i>
                        </span>
                        <input type="text" name="username" class="form-control border-start-0"
                               placeholder="Masukkan username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               required autofocus autocomplete="username">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-lock text-muted"></i>
                        </span>
                        <input type="password" name="password" id="inputPassword"
                               class="form-control border-start-0 border-end-0"
                               placeholder="Masukkan password"
                               required autocomplete="current-password">
                        <button type="button" class="btn btn-light border"
                                onclick="togglePassword()" title="Tampilkan/sembunyikan password">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-login w-100 text-white">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                </button>
            </form>

            <div class="text-center mt-4 p-3 bg-light rounded-3" style="font-size: 12px;">
                <i class="bi bi-info-circle me-1 text-primary"></i>
                <strong>Login default:</strong><br>
                Username: <code>admin</code> &nbsp;|&nbsp; Password: <code>admin123</code>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function togglePassword() {
        const input   = document.getElementById('inputPassword');
        const icon    = document.getElementById('eyeIcon');
        const isText  = input.type === 'text';
        input.type    = isText ? 'password' : 'text';
        icon.className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
    }
    </script>
</body>
</html>
