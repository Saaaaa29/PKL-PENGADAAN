<?php
session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db    = getDB();
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$status = $_GET['status'] ?? '';

$where = ["YEAR(r.tanggal_mulai) = $tahun"];
if ($status) $where[] = "r.status = '" . $db->real_escape_string($status) . "'";
$whereStr = 'WHERE ' . implode(' AND ', $where);

$rows = $db->query("SELECT r.nomor_kontrak, r.tanggal_mulai, r.tanggal_selesai, r.metode_pengadaan, r.status, r.total_nilai, r.catatan,
    d.nama_kegiatan, d.volume, d.satuan, d.nilai_satuan, d.nilai_anggaran, d.jenis_pengadaan, d.rencana_id
    FROM realisasi_kegiatan r
    JOIN realisasi_detail d ON d.realisasi_id = r.id
    $whereStr ORDER BY r.tanggal_mulai DESC, r.id, d.id");

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="realisasi_kegiatan_' . $tahun . '.xls"');
echo "\xEF\xBB\xBF";

echo "REALISASI KEGIATAN PENGADAAN TAHUN $tahun\n\n";
echo "No. Kontrak\tTgl Mulai\tTgl Selesai\tMetode\tStatus\tNama Kegiatan\tJenis\tVolume\tSatuan\tNilai Satuan\tTotal Item\tSumber\n";

$no = 1;
while ($r = $rows->fetch_assoc()) {
    echo implode("\t", [
        $r['nomor_kontrak'] ?: '-',
        $r['tanggal_mulai'],
        $r['tanggal_selesai'] ?: '-',
        getLabelMetode($r['metode_pengadaan']),
        ucfirst($r['status']),
        $r['nama_kegiatan'],
        getLabelJenis($r['jenis_pengadaan']),
        $r['volume'],
        $r['satuan'],
        number_format($r['nilai_satuan'], 0, '.', ''),
        number_format($r['nilai_anggaran'], 0, '.', ''),
        $r['rencana_id'] ? 'Dari Rencana' : 'Item Baru',
    ]) . "\n";
}
exit;
