-- ============================================================
-- MIGRASI: Ganti sistem role menjadi 3 level
-- Jalankan di phpMyAdmin sebelum deploy kode baru
-- ============================================================

-- ── 1. Ubah kolom role di tabel users ───────────────────────
ALTER TABLE users
    MODIFY COLUMN role ENUM('admin', 'manajer_pengadaan', 'staf_pengadaan')
    NOT NULL DEFAULT 'staf_pengadaan';

-- ── 2. Migrasi role lama → baru ─────────────────────────────
-- 'admin' tetap 'admin'
-- 'user'  → 'staf_pengadaan' (role default)
UPDATE users SET role = 'staf_pengadaan' WHERE role = 'user';

-- ── 3. Tetapkan manajer (sesuaikan id dengan data asli) ──────
-- Contoh: user dengan id=2 dijadikan manajer_pengadaan
-- UPDATE users SET role = 'manajer_pengadaan' WHERE id = 2;

-- ── 4. Dummy users untuk testing (opsional) ─────────────────
-- Password: 'password123' di-hash bcrypt
-- Hash di bawah valid untuk: $2y$10$ + salt
-- Untuk generate hash baru: php -r "echo password_hash('password123', PASSWORD_BCRYPT);"

INSERT INTO users (nama_lengkap, username, password, role, created_at)
VALUES
    ('Eko Daeng Wibowo',   'manajer',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manajer_pengadaan', NOW()),
    ('Sari Dewi Rahayu',   'staf1',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staf_pengadaan',    NOW()),
    ('Budi Santoso',       'staf2',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staf_pengadaan',    NOW());

-- ── 5. Verifikasi ────────────────────────────────────────────
SELECT id, nama_lengkap, username, role FROM users ORDER BY role, id;