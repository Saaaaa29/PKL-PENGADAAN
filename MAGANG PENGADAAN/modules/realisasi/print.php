<?php
/**
 * modules/realisasi/print.php
 * Halaman cetak realisasi kegiatan
 */

session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db     = getDB();
$tahun  = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$status = $_GET['status'] ?? '';

$where = ["YEAR(r.tanggal_mulai) = $tahun"];
if ($status) $where[] = "r.status = '" . $db->real_escape_string($status) . "'";
$whereStr = 'WHERE ' . implode(' AND ', $where);

// Ambil header realisasi beserta nama kegiatan dari detail
$result = $db->query("SELECT r.*, u.nama_lengkap,
    (SELECT GROUP_CONCAT(nama_kegiatan ORDER BY id ASC SEPARATOR '\n')
     FROM realisasi_detail WHERE realisasi_id = r.id) as nama_kegiatan_list
    FROM realisasi_kegiatan r
    LEFT JOIN users u ON u.id = r.created_by
    $whereStr
    ORDER BY r.tanggal_mulai ASC");

$totalQ = $db->query("SELECT SUM(r.total_nilai) as total FROM realisasi_kegiatan r $whereStr");
$total  = ($totalQ !== false) ? ($totalQ->fetch_assoc()['total'] ?? 0) : 0;

$rows = [];
while ($row = $result->fetch_assoc()) $rows[] = $row;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Realisasi Pengadaan <?= $tahun ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 10px; }
        h3, h4 { text-align: center; margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        th {
            background: #1e40af; color: white;
            padding: 6px 4px; text-align: center;
            font-size: 10px; border: 1.2px solid #000;
        }
        td { border: 1.2px solid #000; padding: 4px 6px; vertical-align: top; }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .total-row   { font-weight: bold; background: #f0f4ff; }
        .nama-kegiatan-cell { white-space: pre-line; font-size: 10px; }
        .status-proses  { color: #854d0e; font-weight: bold; }
        .status-selesai { color: #166534; font-weight: bold; }
        .status-batal   { color: #991b1b; font-weight: bold; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
            th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="no-print" style="margin-bottom:12px;">
    <button onclick="window.print()">🖨️ Cetak</button>
    <button onclick="window.close()">✕ Tutup</button>
</div>

<h3>REALISASI PENGADAAN KORPORAT</h3>
<h3>PT AIR MINUM GIRI MENANG (PERSERODA)</h3>
<h3>TAHUN <?= $tahun ?></h3>
<?php if ($status): ?>
    <h4 style="font-weight:normal;">Status: <?= ucfirst($status) ?></h4>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th style="width:30px;">No.</th>
            <th style="width:110px;">No. Kontrak</th>
            <th style="width:200px;">Nama Kegiatan</th>
            <th style="width:100px;">Metode</th>
            <th style="width:70px;">Tgl Mulai</th>
            <th style="width:70px;">Tgl Selesai</th>
            <th style="width:100px;">Total Nilai</th>
            <th style="width:55px;">Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
        <tr>
            <td colspan="8" class="text-center" style="padding:20px;">Tidak ada data</td>
        </tr>
        <?php endif; ?>

        <?php foreach ($rows as $no => $row):
            $statusClass = 'status-' . $row['status'];
            // Beri nomor urut tiap kegiatan jika lebih dari 1
            $kegiatanArr = $row['nama_kegiatan_list']
                ? explode("\n", $row['nama_kegiatan_list'])
                : [];
            $kegiatanTxt = '';
            if (count($kegiatanArr) === 1) {
                $kegiatanTxt = trim($kegiatanArr[0]);
            } elseif (count($kegiatanArr) > 1) {
                foreach ($kegiatanArr as $i => $kg) {
                    $kegiatanTxt .= ($i+1) . '. ' . trim($kg) . "\n";
                }
                $kegiatanTxt = rtrim($kegiatanTxt);
            }
        ?>
        <tr>
            <td class="text-center"><?= $no + 1 ?></td>
            <td><?= sanitize($row['nomor_kontrak'] ?: '-') ?></td>
            <td class="nama-kegiatan-cell"><?= sanitize($kegiatanTxt ?: '-') ?></td>
            <td class="text-center"><?= getLabelMetode($row['metode_pengadaan']) ?></td>
            <td class="text-center"><?= date('d/m/Y', strtotime($row['tanggal_mulai'])) ?></td>
            <td class="text-center">
                <?= $row['tanggal_selesai'] ? date('d/m/Y', strtotime($row['tanggal_selesai'])) : '-' ?>
            </td>
            <td class="text-right"><?= formatRupiah($row['total_nilai'], '') ?></td>
            <td class="text-center <?= $statusClass ?>"><?= ucfirst($row['status']) ?></td>
        </tr>
        <?php endforeach; ?>

        <tr class="total-row">
            <td colspan="6" class="text-right">TOTAL REALISASI:</td>
            <td class="text-right"><?= formatRupiah($total, '') ?></td>
            <td></td>
        </tr>
    </tbody>
</table>

<div style="margin-top:16px; font-size:10px; color:#666;">
    Dicetak: <?= date('d F Y H:i') ?>
</div>

<!-- TANDA TANGAN -->
<div style="margin-top:60px;">
    <table style="width:100%; border:none; text-align:center; font-size:11px;">
        <tr>
            <td style="width:34%;">
                <div>Disahkan oleh</div>
                <div>Unsur Direksi Yang Ditetapkan</div>
                <div>Kewenangannya Oleh Direktur Utama</div>
                <div style="height:80px;"></div>
                <div style="border-top:1px solid #000; width:180px; margin:0 auto;">Dadi Rahman</div>
            </td>
            <td style="width:33%;">
                <div>Diketahui oleh</div>
                <div>Manajer Bidang Umum</div>
                <div style="height:80px;"></div>
                <div style="border-top:1px solid #000; width:160px; margin:0 auto;">Wawan Supriyadi</div>
            </td>
            <td style="width:33%;">
                <div>Dibuat oleh</div>
                <div>Asisten Manajer Pengadaan</div>
                <div style="height:80px;"></div>
                <div style="border-top:1px solid #000; width:160px; margin:0 auto;">Eko Daeng W</div>
            </td>
        </tr>
    </table>
</div>

<script>window.onload = function(){ window.print(); }</script>
</body>
</html>