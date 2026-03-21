<?php
/**
 * modules/rencana/hapus.php
 * Hapus rencana kegiatan — redirect kembali ke filter yang sama
 */

session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $db   = getDB();
    $q    = $db->query("SELECT COUNT(*) as c FROM realisasi_detail WHERE rencana_id = $id");
    $used = $q->fetch_assoc()['c'];

    if ($used > 0) {
        setFlash('error', 'Tidak dapat menghapus rencana yang sudah memiliki realisasi. Hapus realisasi terkait terlebih dahulu.');
    } else {
        $db->query("DELETE FROM rencana_kegiatan WHERE id = $id");
        setFlash('success', 'Rencana kegiatan berhasil dihapus.');
    }
}

// Kembali ke index dengan filter yang sama (dikirim via GET dari index.php)
$params = array_filter([
    'tahun'  => isset($_GET['tahun'])  ? (int)$_GET['tahun']       : null,
    'jenis'  => isset($_GET['jenis'])  ? trim($_GET['jenis'])       : null,
    'urutan' => isset($_GET['urutan']) ? trim($_GET['urutan'])      : null,
]);
$qs = $params ? '?' . http_build_query($params) : '';
header('Location: index.php' . $qs);
exit;