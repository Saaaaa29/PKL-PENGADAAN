<?php
session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $db = getDB();
    // detail akan terhapus otomatis karena ON DELETE CASCADE
    $db->query("DELETE FROM realisasi_kegiatan WHERE id = $id");
    setFlash('success', 'Realisasi berhasil dihapus.');
}
$params = array_filter([
    'tahun'  => isset($_GET['tahun'])  ? (int)$_GET['tahun']       : null,
    'jenis'  => isset($_GET['jenis'])  ? trim($_GET['jenis'])       : null,
    'urutan' => isset($_GET['urutan']) ? trim($_GET['urutan'])      : null,
]);
$qs = $params ? '?' . http_build_query($params) : '';
header('Location: index.php');
exit;
