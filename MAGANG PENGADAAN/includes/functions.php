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

/**
 * Format angka menjadi format Rupiah
 * Contoh: 1500000 -> Rp 1.500.000
 */
function formatRupiah($angka, $prefix = 'Rp ') {
    return $prefix . number_format((float)$angka, 0, ',', '.');
}

/**
 * Format angka dengan separator ribuan
 */
function formatAngka($angka) {
    return number_format((float)$angka, 0, ',', '.');
}

// -------------------------------------------------------
// FUNGSI METODE PENGADAAN
// -------------------------------------------------------

/**
 * Tentukan metode pengadaan otomatis berdasarkan nilai anggaran
 * Aturan:
 *   <= 15 juta        -> Pembelian Langsung
 *   <= 600 juta       -> Tender Terbatas
 *   > 600 juta        -> Tender Umum
 *
 * Catatan: E-Purchasing dan Swakelola dipilih manual oleh user
 */
function tentukanMetode($nilai) {
    $nilai = (float)$nilai;
    if ($nilai <= BATAS_PEMBELIAN_LANGSUNG) {
        return 'pembelian_langsung';
    } elseif ($nilai <= BATAS_TENDER_TERBATAS_SPK) {
        return 'tender_terbatas_spk';   // 15jt - 50jt
    } elseif ($nilai <= BATAS_TENDER_TERBATAS_PKP) {
        return 'tender_terbatas_pkp';   // 50jt - 600jt
    } else {
        return 'tender_umum';
    }
}

/**
 * Ambil label metode pengadaan dari key
 */
function getLabelMetode($key) {
    return LABEL_METODE[$key] ?? $key;
}

/**
 * Ambil label jenis pengadaan dari key
 */
function getLabelJenis($key) {
    return LABEL_JENIS[$key] ?? $key;
}

/**
 * Ambil nama bulan dari nomor bulan (satu bulan)
 */
function getNamaBulan($nomor) {
    return NAMA_BULAN[(int)$nomor] ?? '-';
}

/**
 * Format string multi-bulan "1,3,5" menjadi "Jan, Mar, Mei"
 * Atau versi lengkap: "Januari, Maret, Mei"
 */
function formatBulanRencana($bulanStr, $singkat = true) {
    if (empty($bulanStr)) return '-';
    $bulanArr = array_filter(array_map('intval', explode(',', $bulanStr)), fn($b) => $b >= 1 && $b <= 12);
    sort($bulanArr);
    return implode(', ', array_map(function($b) use ($singkat) {
        $nama = NAMA_BULAN[$b] ?? '';
        return $singkat ? substr($nama, 0, 3) : $nama;
    }, $bulanArr));
}

/**
 * Cek apakah bulan tertentu ada dalam string multi-bulan
 * Contoh: bulanAda('1,3,5', 3) → true
 */
function bulanAda($bulanStr, $nomor) {
    $arr = array_map('intval', explode(',', $bulanStr));
    return in_array((int)$nomor, $arr);
}

// -------------------------------------------------------
// FUNGSI KEAMANAN
// -------------------------------------------------------

/**
 * Sanitasi input untuk mencegah XSS
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Cek apakah user sudah login
 * Redirect ke halaman login jika belum
 */
function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/modules/auth/login.php');
        exit;
    }
}

/**
 * Cek apakah user adalah admin
 */
function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        $_SESSION['flash_error'] = 'Akses ditolak. Hanya admin yang dapat mengakses halaman ini.';
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

/**
 * Simpan flash message (pesan satu kali tampil)
 */
function setFlash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Tampilkan dan hapus flash message
 */
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
// FUNGSI DATABASE HELPER
// -------------------------------------------------------

/**
 * Hitung statistik dashboard
 */
function getDashboardStats($tahun = null) {
    $db = getDB();
    $tahun = $tahun ?? date('Y');

    $stats = [];

    // Total anggaran rencana tahun ini
    $q = $db->query("SELECT SUM(nilai_anggaran) as total FROM rencana_kegiatan WHERE tahun = $tahun");
    $stats['total_rencana'] = $q->fetch_assoc()['total'] ?? 0;

    // Total realisasi tahun ini
    $q = $db->query("SELECT SUM(rd.nilai_anggaran) as total 
                     FROM realisasi_detail rd 
                     JOIN realisasi_kegiatan r ON r.id = rd.realisasi_id 
                     WHERE YEAR(r.tanggal_mulai) = $tahun");
    $stats['total_realisasi'] = $q->fetch_assoc()['total'] ?? 0;

    // Persentase serapan
    $stats['persen_serapan'] = $stats['total_rencana'] > 0
        ? round(($stats['total_realisasi'] / $stats['total_rencana']) * 100, 1)
        : 0;

    // Jumlah rencana kegiatan
    $q = $db->query("SELECT COUNT(*) as total FROM rencana_kegiatan WHERE tahun = $tahun");
    $stats['jumlah_rencana'] = $q->fetch_assoc()['total'] ?? 0;

    // Jumlah realisasi
    $q = $db->query("SELECT COUNT(*) as total FROM realisasi_kegiatan WHERE YEAR(tanggal_mulai) = $tahun");
    $stats['jumlah_realisasi'] = $q->fetch_assoc()['total'] ?? 0;

    return $stats;
}