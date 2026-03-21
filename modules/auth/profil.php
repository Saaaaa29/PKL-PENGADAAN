<?php
/**
 * modules/auth/profil.php
 * Halaman profil & ganti password — semua role
 */
session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db    = getDB();
$uid   = (int)$_SESSION['user_id'];
$role  = $_SESSION['user_role'] ?? '';

// Ambil data user dari DB
$qUser = $db->query("SELECT * FROM users WHERE id=$uid");
$user  = $qUser ? $qUser->fetch_assoc() : [];

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    // ── Update profil ──────────────────────────────────────────
    if ($aksi === 'profil') {
        $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
        $username    = trim($_POST['username'] ?? '');

        if (!$namaLengkap) $errors[] = 'Nama lengkap wajib diisi.';
        if (!$username)    $errors[] = 'Username wajib diisi.';

        // Cek username duplikat
        if ($username && $username !== $user['username']) {
            $chk = $db->query("SELECT id FROM users WHERE username='".$db->real_escape_string($username)."' AND id!=$uid");
            if ($chk && $chk->num_rows > 0) $errors[] = 'Username sudah dipakai user lain.';
        }

        if (empty($errors)) {
            $stmt = $db->prepare("UPDATE users SET nama_lengkap=?, username=? WHERE id=?");
            $stmt->bind_param('ssi', $namaLengkap, $username, $uid);
            $stmt->execute();
            $stmt->close();
            $_SESSION['user_nama'] = $namaLengkap;
            $user['nama_lengkap']  = $namaLengkap;
            $user['username']      = $username;
            setFlash('success', 'Profil berhasil diperbarui.');
            header('Location: profil.php');
            exit;
        }
    }

    // ── Ganti password ─────────────────────────────────────────
    if ($aksi === 'password') {
        $passLama  = $_POST['password_lama']  ?? '';
        $passBaru  = $_POST['password_baru']  ?? '';
        $passUlang = $_POST['password_ulang'] ?? '';

        if (!$passLama)  $errors[] = 'Password lama wajib diisi.';
        if (strlen($passBaru) < 6) $errors[] = 'Password baru minimal 6 karakter.';
        if ($passBaru !== $passUlang) $errors[] = 'Konfirmasi password tidak cocok.';

        if (empty($errors)) {
            if (!password_verify($passLama, $user['password'])) {
                $errors[] = 'Password lama tidak benar.';
            } else {
                $hash = password_hash($passBaru, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password=? WHERE id=?");
                $stmt->bind_param('si', $hash, $uid);
                $stmt->execute();
                $stmt->close();
                setFlash('success', 'Password berhasil diperbarui. Silakan login ulang.');
                header('Location: ../../modules/auth/logout.php');
                exit;
            }
        }
    }
}

// Label role
$roleLabel = match($role) {
    'admin'             => ['Admin',    'danger'],
    'manajer_pengadaan' => ['Manajer Pengadaan', 'primary'],
    'staf_pengadaan'    => ['Staf Pengadaan', 'secondary'],
    default             => [$role, 'light'],
};

$pageTitle = 'Profil & Password';
include __DIR__ . '/../../includes/header.php';
?>

<div class="row g-4" style="max-width:900px;">

    <!-- ── Kartu Profil ─────────────────────────────────── -->
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header fw-bold">
                <i class="bi bi-person-circle me-2 text-primary"></i>Profil Akun
            </div>
            <div class="card-body">

                <!-- Avatar & info -->
                <div class="text-center mb-4">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"
                         style="width:72px;height:72px;background:linear-gradient(135deg,#3b82f6,#1e40af);font-size:28px;color:white;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div class="fw-bold fs-6"><?= sanitize($user['nama_lengkap'] ?? '') ?></div>
                    <div class="text-muted mb-2" style="font-size:12px;">@<?= sanitize($user['username'] ?? '') ?></div>
                    <span class="badge bg-<?= $roleLabel[1] ?>"><?= $roleLabel[0] ?></span>
                </div>

                <?php if (!empty($errors) && ($_POST['aksi']??'') === 'profil'): ?>
                <div class="alert alert-danger py-2" style="font-size:12px;">
                    <?php foreach ($errors as $e): ?><div>• <?= sanitize($e) ?></div><?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="aksi" value="profil">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">
                            Nama Lengkap <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="nama_lengkap" class="form-control"
                               value="<?= sanitize($user['nama_lengkap'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">
                            Username <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="username" class="form-control"
                               value="<?= sanitize($user['username'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">Role</label>
                        <input type="text" class="form-control bg-light"
                               value="<?= $roleLabel[0] ?>" disabled>
                        <div class="form-text">Role hanya dapat diubah oleh admin.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save me-1"></i>Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ── Kartu Ganti Password ──────────────────────────── -->
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header fw-bold">
                <i class="bi bi-shield-lock me-2 text-warning"></i>Ganti Password
            </div>
            <div class="card-body">

                <div class="alert alert-info py-2 mb-3" style="font-size:12px;">
                    <i class="bi bi-info-circle me-1"></i>
                    Setelah password berhasil diganti, Anda akan <strong>otomatis logout</strong>
                    dan harus login ulang.
                </div>

                <?php if (!empty($errors) && ($_POST['aksi']??'') === 'password'): ?>
                <div class="alert alert-danger py-2" style="font-size:12px;">
                    <?php foreach ($errors as $e): ?><div>• <?= sanitize($e) ?></div><?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="POST" id="formPassword">
                    <input type="hidden" name="aksi" value="password">

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">
                            Password Lama <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" name="password_lama" id="passLama"
                                   class="form-control" placeholder="Masukkan password saat ini">
                            <button type="button" class="btn btn-outline-secondary toggle-pass" data-target="passLama">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">
                            Password Baru <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" name="password_baru" id="passBaru"
                                   class="form-control" placeholder="Minimal 6 karakter"
                                   oninput="cekKekuatan(this.value)">
                            <button type="button" class="btn btn-outline-secondary toggle-pass" data-target="passBaru">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <!-- Kekuatan password -->
                        <div class="mt-2">
                            <div class="progress" style="height:5px;">
                                <div class="progress-bar" id="strengthBar" style="width:0%;transition:all .3s;"></div>
                            </div>
                            <div id="strengthLabel" class="mt-1" style="font-size:11px;color:#94a3b8;"></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size:13px;">
                            Konfirmasi Password Baru <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" name="password_ulang" id="passUlang"
                                   class="form-control" placeholder="Ulangi password baru"
                                   oninput="cekKonfirmasi()">
                            <button type="button" class="btn btn-outline-secondary toggle-pass" data-target="passUlang">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div id="matchLabel" style="font-size:11px;margin-top:4px;"></div>
                    </div>

                    <button type="submit" class="btn btn-warning px-4 fw-semibold">
                        <i class="bi bi-key me-1"></i>Ganti Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle show/hide password
document.querySelectorAll('.toggle-pass').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var inp = document.getElementById(this.dataset.target);
        var icon = this.querySelector('i');
        if (inp.type === 'password') {
            inp.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            inp.type = 'password';
            icon.className = 'bi bi-eye';
        }
    });
});

// Kekuatan password
function cekKekuatan(val) {
    var bar = document.getElementById('strengthBar');
    var lbl = document.getElementById('strengthLabel');
    var score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    var levels = [
        [0,  '#e2e8f0', ''],
        [20, '#ef4444', 'Sangat Lemah'],
        [40, '#f97316', 'Lemah'],
        [60, '#eab308', 'Cukup'],
        [80, '#22c55e', 'Kuat'],
        [100,'#10b981', 'Sangat Kuat'],
    ];
    var l = levels[Math.min(score, 5)];
    bar.style.width  = l[0] + '%';
    bar.style.background = l[1];
    lbl.style.color  = l[1];
    lbl.textContent  = l[2];
    cekKonfirmasi();
}

// Cocokkan konfirmasi
function cekKonfirmasi() {
    var baru  = document.getElementById('passBaru').value;
    var ulang = document.getElementById('passUlang').value;
    var lbl   = document.getElementById('matchLabel');
    if (!ulang) { lbl.textContent = ''; return; }
    if (baru === ulang) {
        lbl.style.color = '#16a34a';
        lbl.textContent = '✓ Password cocok';
    } else {
        lbl.style.color = '#dc2626';
        lbl.textContent = '✗ Password tidak cocok';
    }
}
</script>