<?php
/**
 * modules/realisasi/print.php
 * Halaman cetak realisasi kegiatan — flat rows seperti format export
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

// Ambil semua realisasi
$result = $db->query("
    SELECT r.*, u.nama_lengkap
    FROM realisasi_kegiatan r
    LEFT JOIN users u ON u.id = r.created_by
    $whereStr
    ORDER BY r.tanggal_mulai ASC
");
$rows = [];
while ($row = $result->fetch_assoc()) $rows[] = $row;

// Hitung total keseluruhan
$totalQ = $db->query("SELECT SUM(r.total_nilai) as total FROM realisasi_kegiatan r $whereStr");
$total  = ($totalQ !== false) ? (float)($totalQ->fetch_assoc()['total'] ?? 0) : 0;

// Ambil detail item & vendor sekaligus (N+1 free)
$detailMap = []; $vendorMap = [];
if (!empty($rows)) {
    $ids = implode(',', array_column($rows, 'id'));

    $qD = $db->query("SELECT * FROM realisasi_detail
                      WHERE realisasi_id IN ($ids) ORDER BY realisasi_id ASC, id ASC");
    if ($qD) while ($d = $qD->fetch_assoc()) $detailMap[$d['realisasi_id']][] = $d;

    $qV = $db->query("SELECT * FROM realisasi_vendor
                      WHERE realisasi_id IN ($ids) ORDER BY realisasi_id ASC, id ASC");
    if ($qV) while ($v = $qV->fetch_assoc()) $vendorMap[$v['realisasi_id']][] = $v;
}

function rp(float $n): string { return number_format($n, 0, ',', '.'); }
function tgl_fmt(?string $d): string {
    return ($d && $d !== '0000-00-00') ? date('d/m/Y', strtotime($d)) : '-';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Realisasi Pengadaan <?= $tahun ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; margin: 0; padding: 10px; }
        h3, h4 { text-align: center; margin: 3px 0; font-size: 12px; }

        table {
            width: 100%; border-collapse: collapse;
            margin-top: 10px; table-layout: fixed;
        }
        th {
            background: #1e40af; color: #fff;
            padding: 5px 4px; text-align: center;
            font-size: 9px; border: 1px solid #1e3a8a;
            vertical-align: middle; line-height: 1.3;
        }
        td {
            border: 1px solid #94a3b8;
            padding: 3px 5px; vertical-align: top;
            font-size: 9.5px; line-height: 1.4;
        }
        /* Kolom grup realisasi — warna lebih gelap */
        td.col-real { background: #f8fafc; }
        /* Kolom item */
        td.col-item { background: #fff; }
        /* Kolom vendor */
        td.col-vendor { background: #eff6ff; }

        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .total-row td { font-weight: bold; background: #f0f4ff; font-size: 9.5px; }

        .status-proses  { color: #854d0e; font-weight: bold; }
        .status-selesai { color: #166534; font-weight: bold; }
        .status-batal   { color: #991b1b; font-weight: bold; }

        /* Garis pemisah antar realisasi */
        tr.first-row td { border-top: 2px solid #475569 !important; }

        @media print {
            body { padding: 0; }
            .no-print { display: none; }
            th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            td.col-real   { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            td.col-vendor { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<!-- TOOLBAR -->
<div class="no-print" style="display:flex;align-items:center;gap:10px;margin-bottom:12px;
     padding:9px 14px;background:white;border:1px solid #d1d5db;border-radius:8px;
     box-shadow:0 1px 4px rgba(0,0,0,.08);">
    <button onclick="bukaModal()"
            style="padding:6px 14px;border:none;border-radius:6px;font-size:12px;
                   font-weight:600;cursor:pointer;background:#1d4ed8;color:white;">
        🖨️ Isi TTD &amp; Cetak
    </button>
    <button onclick="window.close()"
            style="padding:6px 14px;border:none;border-radius:6px;font-size:12px;
                   font-weight:600;cursor:pointer;background:#e5e7eb;color:#374151;">
        ✕ Tutup
    </button>
    <div style="font-size:10px;color:#6b7280;line-height:1.4;">
        Pastikan <strong style="color:#dc2626;">Grafik Latar Belakang</strong> aktif
        dan <strong style="color:#dc2626;">Header &amp; Footer</strong> dimatikan.
    </div>
</div>

<!-- MODAL TTD -->
<div id="modalTTD" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);
     z-index:999;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:12px;padding:22px 26px;width:490px;
                max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,.25);">
        <h2 style="font-size:14px;color:#1e3a52;margin:0 0 4px;">✍️ Isi Nama Penandatangan</h2>
        <p style="font-size:10.5px;color:#6b7280;margin:0 0 14px;">
            Nama yang diisi akan muncul di bagian tanda tangan dokumen cetak.
        </p>

        <?php
        $ttdFields = [
            ['id'=>'ttdKiri',   'nip'=>'nipKiri',   'label'=>'Disahkan oleh — Unsur Direksi',         'jabatan'=>'Unsur Direksi Yang Ditetapkan Kewenangannya Oleh Direktur Utama'],
            ['id'=>'ttdTengah', 'nip'=>'nipTengah', 'label'=>'Diketahui oleh — Manajer Bidang Umum',  'jabatan'=>'Manajer Bidang Umum'],
            ['id'=>'ttdKanan',  'nip'=>'nipKanan',  'label'=>'Dibuat oleh — Asisten Manajer Pengadaan','jabatan'=>'Asisten Manajer Pengadaan'],
        ];
        foreach ($ttdFields as $i => $f): ?>
        <?php if ($i > 0): ?><hr style="border:none;border-top:1px solid #e5e7eb;margin:12px 0;"><?php endif; ?>
        <div style="margin-bottom:10px;">
            <label style="display:block;font-size:10px;font-weight:700;color:#374151;margin-bottom:3px;">
                <?= $f['label'] ?>
            </label>
            <input type="text" id="<?= $f['id'] ?>" placeholder="Nama lengkap"
                   style="width:100%;padding:6px 9px;border:1px solid #d1d5db;border-radius:5px;
                          font-size:11px;font-family:Arial,sans-serif;margin-bottom:5px;">
        </div>
        <div style="margin-bottom:4px;">
            <label style="display:block;font-size:10px;font-weight:700;color:#374151;margin-bottom:3px;">
                NIP / NRP (opsional)
            </label>
            <input type="text" id="<?= $f['nip'] ?>" placeholder="Kosongkan jika tidak ada"
                   style="width:100%;padding:6px 9px;border:1px solid #d1d5db;border-radius:5px;
                          font-size:11px;font-family:Arial,sans-serif;">
        </div>
        <?php endforeach; ?>

        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
            <button onclick="tutupModal()"
                    style="padding:7px 16px;border-radius:6px;border:none;font-size:11.5px;
                           font-weight:600;cursor:pointer;background:#f3f4f6;color:#374151;">
                Batal
            </button>
            <button onclick="terapkanDanCetak()"
                    style="padding:7px 16px;border-radius:6px;border:none;font-size:11.5px;
                           font-weight:600;cursor:pointer;background:#1d4ed8;color:white;">
                ✓ Terapkan &amp; Cetak
            </button>
        </div>
    </div>
</div>

<h3>REALISASI KEGIATAN PENGADAAN KORPORAT</h3>
<h3>PT AIR MINUM GIRI MENANG (PERSERODA)</h3>
<h3>TAHUN <?= $tahun ?><?= $status ? ' — Status: '.ucfirst($status) : '' ?></h3>

<table>
    <thead>
        <tr>
            <!-- Kolom Realisasi -->
            <th style="width:22px;">No.</th>
            <th style="width:88px;">No. Kontrak</th>
            <th style="width:58px;">Tgl Mulai</th>
            <th style="width:58px;">Tgl Selesai</th>
            <th style="width:80px;">Metode</th>
            <th style="width:42px;">Status</th>
            <!-- Kolom Item -->
            <th style="width:120px;">Nama Kegiatan</th>
            <th style="width:56px;">Jenis</th>
            <th style="width:30px;">Vol</th>
            <th style="width:30px;">Sat</th>
            <th style="width:60px;">Nilai Satuan (Rp)</th>
            <th style="width:62px;">Total Item (Rp)</th>
            <th style="width:40px;">Sumber</th>
            <!-- Kolom Vendor -->
            <th style="width:100px;">Nama Vendor</th>
            <th style="width:62px;">Nilai Vendor (Rp)</th>
            <!-- Catatan -->
            <th style="width:70px;">Catatan</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($rows)): ?>
        <tr><td colspan="18" class="text-center" style="padding:16px;">Tidak ada data</td></tr>
    <?php endif; ?>

    <?php
    $no = 1;
    $totalVendorAll = 0;
    foreach ($rows as $row):
        $items    = $detailMap[$row['id']] ?? [];
        $vendors  = $vendorMap[$row['id']] ?? [];
        $maxBaris = max(count($items), count($vendors), 1);
        $statusCls = 'status-' . $row['status'];

        foreach ($vendors as $v) $totalVendorAll += (float)$v['nilai_kontrak'];

        for ($i = 0; $i < $maxBaris; $i++):
            $item    = $items[$i]   ?? null;
            $vendor  = $vendors[$i] ?? null;
            $isFirst = ($i === 0);
            $rowCls  = $isFirst ? 'first-row' : '';
    ?>
        <tr class="<?= $rowCls ?>">
            <?php if ($isFirst): ?>
            <!-- ── Kolom realisasi — rowspan ── -->
            <td class="text-center col-real" rowspan="<?= $maxBaris ?>"><?= $no ?></td>
            <td class="col-real" rowspan="<?= $maxBaris ?>" style="font-size:8.5px;word-break:break-all;">
                <?= sanitize($row['nomor_kontrak'] ?: '-') ?>
            </td>
            <td class="text-center col-real" rowspan="<?= $maxBaris ?>"><?= tgl_fmt($row['tanggal_mulai']) ?></td>
            <td class="text-center col-real" rowspan="<?= $maxBaris ?>"><?= tgl_fmt($row['tanggal_selesai'] ?: null) ?></td>
            <td class="col-real" rowspan="<?= $maxBaris ?>" style="font-size:8.5px;">
                <?= getLabelMetode($row['metode_pengadaan']) ?>
            </td>
            <td class="text-center col-real <?= $statusCls ?>" rowspan="<?= $maxBaris ?>">
                <?= ucfirst($row['status']) ?>
            </td>
            <?php endif; ?>

            <!-- ── Kolom item ── -->
            <td class="col-item"><?= $item ? sanitize($item['nama_kegiatan']) : '' ?></td>
            <td class="text-center col-item" style="font-size:8.5px;">
                <?= $item ? getLabelJenis($item['jenis_pengadaan']) : '' ?>
            </td>
            <td class="text-center col-item"><?= $item ? sanitize($item['volume']) : '' ?></td>
            <td class="text-center col-item"><?= $item ? sanitize($item['satuan']) : '' ?></td>
            <td class="text-right col-item"><?= $item ? rp((float)$item['nilai_satuan']) : '' ?></td>
            <td class="text-right col-item"><?= $item ? rp((float)$item['nilai_anggaran']) : '' ?></td>
            <td class="text-center col-item" style="font-size:8.5px;">
                <?php if ($item): ?>
                    <?= $item['rencana_id'] ? 'Rencana' : 'Item Baru' ?>
                <?php endif; ?>
            </td>

            <!-- ── Kolom vendor ── -->
            <td class="col-vendor"><?= $vendor ? sanitize($vendor['nama_vendor']) : '' ?></td>
            <td class="text-right col-vendor"><?= $vendor ? rp((float)$vendor['nilai_kontrak']) : '' ?></td>

            <!-- ── Catatan — rowspan ── -->
            <?php if ($isFirst): ?>
            <td rowspan="<?= $maxBaris ?>" style="font-size:8.5px;">
                <?= sanitize($row['catatan'] ?: '') ?>
            </td>
            <?php endif; ?>
        </tr>
    <?php
        endfor;
        $no++;
    endforeach;
    ?>

    <!-- Baris total -->
    <tr class="total-row">
        <td colspan="11" class="text-right">TOTAL REALISASI (Rp):</td>
        <td class="text-right"><?= rp($total) ?></td>
        <td colspan="2" class="text-right">TOTAL VENDOR (Rp):</td>
        <td class="text-right"><?= rp($totalVendorAll) ?></td>
        <td colspan="1"></td>
    </tr>
    </tbody>
</table>

<div style="margin-top:12px; font-size:9px; color:#666;">
    Dicetak: <?= date('d F Y H:i') ?>
</div>

<!-- TANDA TANGAN -->
<div style="display:flex;justify-content:space-around;margin-top:14mm;">
    <div style="width:30%;text-align:center;">
        <div style="font-weight:bold;font-size:9px;">Disahkan oleh,</div>
        <div style="font-size:8.5px;color:#374151;margin-top:1mm;min-height:8mm;line-height:1.4;">
            Unsur Direksi Yang Ditetapkan<br>Kewenangannya Oleh Direktur Utama
        </div>
        <div style="height:16mm;"></div>
        <div style="border-top:.8px solid #000;width:44mm;margin:0 auto 1mm;"></div>
        <div style="font-weight:bold;font-size:9px;min-height:4mm;" id="displayNamaKiri">&nbsp;</div>
        <div style="font-size:8px;color:#555;" id="displayNipKiri"></div>
    </div>
    <div style="width:30%;text-align:center;">
        <div style="font-weight:bold;font-size:9px;">Diketahui oleh,</div>
        <div style="font-size:8.5px;color:#374151;margin-top:1mm;min-height:8mm;line-height:1.4;">
            Manajer Bidang Umum
        </div>
        <div style="height:16mm;"></div>
        <div style="border-top:.8px solid #000;width:44mm;margin:0 auto 1mm;"></div>
        <div style="font-weight:bold;font-size:9px;min-height:4mm;" id="displayNamaTengah">&nbsp;</div>
        <div style="font-size:8px;color:#555;" id="displayNipTengah"></div>
    </div>
    <div style="width:30%;text-align:center;">
        <div style="font-weight:bold;font-size:9px;">Dibuat oleh,</div>
        <div style="font-size:8.5px;color:#374151;margin-top:1mm;min-height:8mm;line-height:1.4;">
            Asisten Manajer Pengadaan
        </div>
        <div style="height:16mm;"></div>
        <div style="border-top:.8px solid #000;width:44mm;margin:0 auto 1mm;"></div>
        <div style="font-weight:bold;font-size:9px;min-height:4mm;" id="displayNamaKanan">&nbsp;</div>
        <div style="font-size:8px;color:#555;" id="displayNipKanan"></div>
    </div>
</div>

<script>
var fields = ['ttdKiri','nipKiri','ttdTengah','nipTengah','ttdKanan','nipKanan'];

function bukaModal() {
    fields.forEach(function(id) {
        var v = sessionStorage.getItem('ttd_real_' + id);
        if (v) document.getElementById(id).value = v;
    });
    var m = document.getElementById('modalTTD');
    m.style.display = 'flex';
    document.getElementById('ttdKiri').focus();
}
function tutupModal() {
    document.getElementById('modalTTD').style.display = 'none';
}
function terapkanDanCetak() {
    fields.forEach(function(id) {
        sessionStorage.setItem('ttd_real_' + id, document.getElementById(id).value);
    });
    function set(elId, inputId) {
        var v = document.getElementById(inputId).value.trim();
        document.getElementById(elId).textContent = v || '\u00a0';
    }
    set('displayNamaKiri',   'ttdKiri');
    set('displayNipKiri',    'nipKiri');
    set('displayNamaTengah', 'ttdTengah');
    set('displayNipTengah',  'nipTengah');
    set('displayNamaKanan',  'ttdKanan');
    set('displayNipKanan',   'nipKanan');
    tutupModal();
    setTimeout(function(){ window.print(); }, 200);
}
// Tutup modal klik overlay
document.getElementById('modalTTD').addEventListener('click', function(e) {
    if (e.target === this) tutupModal();
});
// Enter = langsung cetak
fields.forEach(function(id) {
    document.getElementById(id).addEventListener('keydown', function(e) {
        if (e.key === 'Enter') terapkanDanCetak();
    });
});
window.onload = function() { bukaModal(); };
</script>
</body>
</html>