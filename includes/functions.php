<?php
/**
 * includes/functions.php
 * Fungsi-fungsi pembantu yang digunakan di seluruh aplikasi
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

// -------------------------------------------------------
// FUNGSI FORMAT
// -------------------------------------------------------

function formatRupiah($angka, $prefix = 'Rp ') {
    return $prefix . number_format((float)$angka, 0, ',', '.');
}

function formatAngka($angka) {
    return number_format((float)$angka, 0, ',', '.');
}

// -------------------------------------------------------
// FUNGSI METODE PENGADAAN
// -------------------------------------------------------

function tentukanMetode($nilai) {
    $nilai = (float)$nilai;
    if ($nilai <= BATAS_PEMBELIAN_LANGSUNG) {
        return 'pembelian_langsung';
    } elseif ($nilai <= BATAS_TENDER_TERBATAS_SPK) {
        return 'tender_terbatas_spk';
    } elseif ($nilai <= BATAS_TENDER_TERBATAS_PKP) {
        return 'tender_terbatas_pkp';
    } else {
        return 'tender_umum';
    }
}

function getLabelMetode($key) {
    return LABEL_METODE[$key] ?? $key;
}

function getLabelJenis($key) {
    return LABEL_JENIS[$key] ?? $key;
}

function getNamaBulan($nomor) {
    return NAMA_BULAN[(int)$nomor] ?? '-';
}

function formatBulanRencana($bulanStr, $singkat = true) {
    if (empty($bulanStr)) return '-';
    $bulanArr = array_filter(
        array_map('intval', explode(',', $bulanStr)),
        fn($b) => $b >= 1 && $b <= 12
    );
    sort($bulanArr);
    return implode(', ', array_map(function($b) use ($singkat) {
        $nama = NAMA_BULAN[$b] ?? '';
        return $singkat ? substr($nama, 0, 3) : $nama;
    }, $bulanArr));
}

function bulanAda($bulanStr, $nomor) {
    $arr = array_map('intval', explode(',', $bulanStr));
    return in_array((int)$nomor, $arr);
}

// -------------------------------------------------------
// FUNGSI KEAMANAN
// -------------------------------------------------------

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/modules/auth/login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        $_SESSION['flash_error'] = 'Akses ditolak. Hanya admin yang dapat mengakses halaman ini.';
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function setFlash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash_' . $type] = $message;
}

function getFlash($type) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash_' . $type])) {
        $msg = $_SESSION['flash_' . $type];
        unset($_SESSION['flash_' . $type]);
        return $msg;
    }
    return null;
}

// -------------------------------------------------------
// FUNGSI DATABASE HELPER — VENDOR
// -------------------------------------------------------

/**
 * Ambil semua vendor untuk satu realisasi
 */
function getVendorByRealisasi($db, $realisasi_id) {
    $id     = (int)$realisasi_id;
    $result = $db->query("
        SELECT * FROM realisasi_vendor
        WHERE realisasi_id = $id
        ORDER BY id ASC
    ");
    if ($result === false) return [];
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    return $rows;
}

/**
 * Simpan vendor — hapus yang lama, insert ulang
 * Baris dengan nama_vendor kosong dilewati secara otomatis
 */
function saveVendors($db, $realisasi_id, $vendors) {
    $id = (int)$realisasi_id;

    // Hapus vendor lama
    $db->query("DELETE FROM realisasi_vendor WHERE realisasi_id = $id");

    // Jika tidak ada vendor, selesai
    if (empty($vendors)) return;

    foreach ($vendors as $v) {
        $nama   = $db->real_escape_string(trim($v['nama_vendor']     ?? ''));
        $nokont = $db->real_escape_string(trim($v['nomor_kontrak']   ?? ''));
        $tgl    = $db->real_escape_string(trim($v['tanggal_kontrak'] ?? ''));
        $nilai  = (float)($v['nilai_kontrak'] ?? 0);

        // Skip baris kosong
        if ($nama === '') continue;

        $tglVal = $tgl ? "'$tgl'" : "NULL";

        $db->query("
            INSERT INTO realisasi_vendor
                (realisasi_id, nama_vendor, nomor_kontrak, tanggal_kontrak, nilai_kontrak)
            VALUES
                ($id, '$nama', '$nokont', $tglVal, $nilai)
        ");
    }
}

// -------------------------------------------------------
// FUNGSI STATISTIK DASHBOARD
// -------------------------------------------------------

function getDashboardStats($tahun = null) {
    $db    = getDB();
    $tahun = $tahun ?? date('Y');
    $stats = [];

    $q = $db->query("SELECT SUM(nilai_anggaran) as total FROM rencana_kegiatan WHERE tahun = $tahun");
    $stats['total_rencana'] = $q->fetch_assoc()['total'] ?? 0;

    $q = $db->query("
        SELECT SUM(rd.nilai_anggaran) as total
        FROM realisasi_detail rd
        JOIN realisasi_kegiatan r ON r.id = rd.realisasi_id
        WHERE YEAR(r.tanggal_mulai) = $tahun
    ");
    $stats['total_realisasi'] = $q->fetch_assoc()['total'] ?? 0;

    $stats['persen_serapan'] = $stats['total_rencana'] > 0
        ? round(($stats['total_realisasi'] / $stats['total_rencana']) * 100, 1)
        : 0;

    $q = $db->query("SELECT COUNT(*) as total FROM rencana_kegiatan WHERE tahun = $tahun");
    $stats['jumlah_rencana'] = $q->fetch_assoc()['total'] ?? 0;

    $q = $db->query("SELECT COUNT(*) as total FROM realisasi_kegiatan WHERE YEAR(tanggal_mulai) = $tahun");
    $stats['jumlah_realisasi'] = $q->fetch_assoc()['total'] ?? 0;

    return $stats;
}

// -------------------------------------------------------
// FUNGSI ROLE HELPER
// -------------------------------------------------------

/**
 * Ambil role user yang sedang login
 */
function getRole(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['user_role'] ?? 'staf_pengadaan';
}

/**
 * Cek apakah user adalah admin
 */
function isAdmin(): bool {
    return getRole() === 'admin';
}

/**
 * Cek apakah user adalah manajer pengadaan
 */
function isManajer(): bool {
    return in_array(getRole(), ['admin', 'manajer_pengadaan']);
}

/**
 * Cek apakah user adalah staf pengadaan
 */
function isStaf(): bool {
    return getRole() === 'staf_pengadaan';
}

/**
 * Require role tertentu — redirect jika tidak punya akses
 * Contoh: requireRole(['admin', 'manajer_pengadaan'])
 */
function requireRole(array $allowedRoles): void {
    requireLogin();
    if (!in_array(getRole(), $allowedRoles)) {
        setFlash('error', 'Akses ditolak. Anda tidak memiliki izin untuk halaman ini.');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

/**
 * Blokir aksi tertentu untuk staf — redirect dengan pesan error
 * Dipakai di halaman form (mode edit) dan hapus
 */
function requireManajer(string $redirect = 'index.php'): void {
    requireLogin();
    if (!isManajer()) {
        setFlash('error', 'Akses ditolak. Hanya Manajer Pengadaan yang dapat melakukan aksi ini.');
        header('Location: ' . $redirect);
        exit;
    }
}

function kirimNotifInputBaru(
    $db,
    int    $realisasiId,
    int    $dariUserId,
    string $noKontrak = '',
    bool   $isEdit    = false
): void {
    $label  = $noKontrak ?: "ID #$realisasiId";
    $aksi   = $isEdit ? 'diperbarui' : 'ditambahkan';
    $pesan  = "Realisasi \"$label\" $aksi oleh staf dan menunggu verifikasi Anda.";
    $tipe   = 'input_baru';

    // Reset status verifikasi ke 'menunggu' agar manajer verif ulang jika diedit
    $db->query("UPDATE realisasi_kegiatan
                SET status_verifikasi='menunggu', catatan_verifikasi=NULL,
                    diverifikasi_oleh=NULL, tgl_verifikasi=NULL
                WHERE id=$realisasiId");

    // Hapus notif lama untuk realisasi yang sama agar tidak duplikat
    $db->query("DELETE FROM notifikasi WHERE realisasi_id=$realisasiId AND tipe='input_baru'");

    // Kirim ke semua manajer dan admin (untuk_user_id NULL = broadcast ke role)
    $stmt = $db->prepare("INSERT INTO notifikasi
        (untuk_role, untuk_user_id, tipe, pesan, realisasi_id, dari_user_id)
        VALUES (?, NULL, ?, ?, ?, ?)");

    foreach (['manajer_pengadaan', 'admin'] as $targetRole) {
        $stmt->bind_param('sssii', $targetRole, $tipe, $pesan, $realisasiId, $dariUserId);
        $stmt->execute();
    }
    $stmt->close();
}

/**
 * Hitung notifikasi belum dibaca untuk user & role tertentu.
 * Digunakan di sidebar dan header bell.
 */
function hitungNotifBelumDibaca($db, int $userId, string $role): int {
    $r = $db->query("
        SELECT COUNT(*) as c FROM notifikasi
        WHERE untuk_role = '$role'
          AND dibaca = 0
          AND (untuk_user_id IS NULL OR untuk_user_id = $userId)
    ");
    return $r ? (int)$r->fetch_assoc()['c'] : 0;
}