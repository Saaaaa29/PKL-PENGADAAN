<?php
session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db    = getDB();
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$jenis = $_GET['jenis'] ?? '';

$where = ["rk.tahun = $tahun"];
if ($jenis && array_key_exists($jenis, LABEL_JENIS)) {
    $where[] = "rk.jenis_pengadaan = '" . $db->real_escape_string($jenis) . "'";
}
$whereStr = 'WHERE ' . implode(' AND ', $where);

$rows = $db->query("
    SELECT rk.nama_kegiatan, rk.jenis_pengadaan, rk.metode_pengadaan, rk.bulan_rencana,
        rk.volume as vol_rencana, rk.satuan, rk.nilai_anggaran as anggaran,
        COALESCE(SUM(rd.nilai_anggaran), 0) as realisasi,
        COALESCE(SUM(rd.volume), 0) as vol_realisasi,
        COUNT(DISTINCT rd.realisasi_id) as jml_realisasi
    FROM rencana_kegiatan rk
    LEFT JOIN realisasi_detail rd ON rd.rencana_id = rk.id
    $whereStr
    GROUP BY rk.id ORDER BY rk.bulan_rencana, rk.id
");

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="laporan_pengadaan_' . $tahun . '.xls"');
echo "\xEF\xBB\xBF";

echo "LAPORAN PERBANDINGAN RENCANA VS REALISASI TAHUN $tahun\n\n";
echo "No.\tNama Kegiatan\tJenis\tMetode\tBulan\tVol Rencana\tSatuan\tAnggaran (Rp)\tVol Realisasi\tRealisasi (Rp)\tSelisih (Rp)\t% Serapan\n";

$no = 1; $totR = 0; $totReal = 0;
while ($r = $rows->fetch_assoc()) {
    $selisih = $r['realisasi'] - $r['anggaran'];
    $persen  = $r['anggaran'] > 0 ? round(($r['realisasi'] / $r['anggaran']) * 100, 1) : 0;
    $totR    += $r['anggaran'];
    $totReal += $r['realisasi'];
    echo implode("\t", [
        $no++,
        $r['nama_kegiatan'],
        getLabelJenis($r['jenis_pengadaan']),
        getLabelMetode($r['metode_pengadaan']),
        formatBulanRencana($r['bulan_rencana'], false),
        number_format($r['vol_rencana'], 2, '.', ''),
        $r['satuan'],
        number_format($r['anggaran'], 0, '.', ''),
        number_format($r['vol_realisasi'], 2, '.', ''),
        number_format($r['realisasi'], 0, '.', ''),
        number_format($selisih, 0, '.', ''),
        $persen . '%',
    ]) . "\n";
}
echo "\t\t\t\t\t\tTotal\t" . number_format($totR,0,'.','') . "\t\t" . number_format($totReal,0,'.','') . "\t" . number_format($totReal-$totR,0,'.','') . "\n";
exit;
