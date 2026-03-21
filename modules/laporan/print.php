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
    SELECT rk.*, COALESCE(SUM(rd.nilai_anggaran),0) as realisasi
    FROM rencana_kegiatan rk
    LEFT JOIN realisasi_detail rd ON rd.rencana_id = rk.id
    $whereStr GROUP BY rk.id ORDER BY rk.bulan_rencana, rk.id
");
$allRows = [];
$totAngg = $totReal = 0;
while ($r = $rows->fetch_assoc()) { $allRows[] = $r; $totAngg += $r['nilai_anggaran']; $totReal += $r['realisasi']; }
$persen = $totAngg > 0 ? round($totReal/$totAngg*100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan <?= $tahun ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 10px; }
        h3, h4 { text-align:center; margin:3px 0; }
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th { background:#1e40af; color:white; padding:5px; font-size:10px; text-align:center; }
        td { border:1px solid #ccc; padding:4px 5px; }
        .text-right { text-align:right; }
        .total-row { font-weight:bold; background:#f0f4ff; }
        .text-danger { color:red; }
        .text-success { color:green; }
        @media print { .no-print { display:none; } }
    </style>
</head>
<body>
<div class="no-print" style="margin-bottom:10px;">
    <button onclick="window.print()">🖨️ Cetak</button>
    <button onclick="window.close()">✕ Tutup</button>
</div>
<h3>LAPORAN PERBANDINGAN RENCANA VS REALISASI</h3>
<h4>TAHUN <?= $tahun ?><?= $jenis ? ' - ' . getLabelJenis($jenis) : '' ?></h4>
<p style="text-align:center; font-size:10px;">Total Anggaran: <?= formatRupiah($totAngg) ?> | Total Realisasi: <?= formatRupiah($totReal) ?> | Serapan: <?= $persen ?>%</p>
<table>
    <thead>
        <tr>
            <th>No.</th>
            <th>Nama Kegiatan</th>
            <th>Jenis</th>
            <th>Metode</th>
            <th>Bulan</th>
            <th>Volume</th>
            <th>Anggaran (Rp)</th>
            <th>Realisasi (Rp)</th>
            <th>Selisih (Rp)</th>
            <th>%</th>
        </tr>
    </thead>
    <tbody>
        <?php $no = 1; foreach ($allRows as $r):
            $selisih = $r['realisasi'] - $r['nilai_anggaran'];
            $pct = $r['nilai_anggaran'] > 0 ? round($r['realisasi']/$r['nilai_anggaran']*100,1) : 0;
        ?>
        <tr>
            <td style="text-align:center"><?= $no++ ?></td>
            <td><?= sanitize($r['nama_kegiatan']) ?></td>
            <td style="text-align:center"><?= getLabelJenis($r['jenis_pengadaan']) ?></td>
            <td style="text-align:center"><?= getLabelMetode($r['metode_pengadaan']) ?></td>
            <td style="text-align:center"><?= formatBulanRencana($r['bulan_rencana'], false) ?></td>
            <td style="text-align:center"><?= formatAngka($r['volume']) ?> <?= $r['satuan'] ?></td>
            <td class="text-right"><?= formatRupiah($r['nilai_anggaran'], '') ?></td>
            <td class="text-right"><?= $r['realisasi'] > 0 ? formatRupiah($r['realisasi'], '') : '-' ?></td>
            <td class="text-right <?= $selisih > 0 ? 'text-danger' : ($selisih < 0 ? 'text-success' : '') ?>">
                <?= $r['realisasi'] > 0 ? ($selisih >= 0 ? '+' : '') . formatRupiah(abs($selisih), '') : '-' ?>
            </td>
            <td style="text-align:center"><?= $pct ?>%</td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row">
            <td colspan="6" class="text-right">TOTAL:</td>
            <td class="text-right"><?= formatRupiah($totAngg, '') ?></td>
            <td class="text-right"><?= formatRupiah($totReal, '') ?></td>
            <td class="text-right <?= ($totReal-$totAngg) >= 0 ? 'text-danger' : 'text-success' ?>">
                <?= ($totReal-$totAngg >= 0 ? '+' : '') . formatRupiah(abs($totReal-$totAngg), '') ?>
            </td>
            <td style="text-align:center"><?= $persen ?>%</td>
        </tr>
    </tbody>
</table>
<div style="margin-top:14px; font-size:10px; color:#666;">Dicetak: <?= date('d F Y H:i') ?></div>
<script>window.onload = function(){ window.print(); }</script>
</body>
</html>
