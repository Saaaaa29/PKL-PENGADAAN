<?php
/**
 * modules/rencana/print.php
 * Cetak rencana — A4 Landscape, margin 4-3-3-3mm, 1 halaman penuh
 */
session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db    = getDB();
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$jenis = $_GET['jenis'] ?? '';

$where = ["tahun = $tahun"];
if ($jenis && array_key_exists($jenis, LABEL_JENIS))
    $where[] = "jenis_pengadaan = '" . $db->real_escape_string($jenis) . "'";
$whereStr = 'WHERE ' . implode(' AND ', $where);

$rows   = $db->query("SELECT * FROM rencana_kegiatan $whereStr ORDER BY nama_kegiatan ASC, id ASC");
$total  = $db->query("SELECT SUM(nilai_anggaran) as t FROM rencana_kegiatan $whereStr")->fetch_assoc()['t'];

// Ambil semua baris ke array agar bisa hitung jumlah
$dataRows = [];
while ($r = $rows->fetch_assoc()) $dataRows[] = $r;
$jumlahBaris = count($dataRows);

// Hitung row-height otomatis agar pas 1 halaman
// Usable height = 203mm, dikurangi judul+header tabel+footer+TTD ≈ 51mm → sisa 152mm
$sisaMm  = 152;
$rowMm   = $jumlahBaris > 0 ? min(8.0, max(4.0, $sisaMm / $jumlahBaris)) : 6.0;
$rowMm   = round($rowMm, 2);

$metodeColors = [
    'pembelian_langsung'   => '#16a34a',   // hijau
    'tender_terbatas_spk'  => '#0891b2',   // biru muda (SPK: 15jt-50jt)
    'tender_terbatas_pkp'  => '#7c3aed',   // ungu     (PKP: 50jt-600jt)
    'tender_terbatas'      => '#0891b2',   // legacy
    'tender_umum'          => '#1d4ed8',   // biru tua
    'e_purchasing'         => '#d97706',   // oranye
    'swakelola'            => '#64748b',   // abu
];

function xe($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rencana Pengadaan <?= $tahun ?></title>
<style>
/* ── RESET ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ── BODY (preview di layar) ── */
body {
    font-family: Arial, sans-serif;
    font-size: 10px;
    background: #e5e7eb;
    padding: 16px;
    color: #111;
}

/* ── TOOLBAR ── */
.toolbar {
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 12px; padding: 9px 14px;
    background: white; border: 1px solid #d1d5db;
    border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.08);
}
.toolbar button {
    padding: 6px 14px; border: none; border-radius: 6px;
    font-size: 12px; font-weight: 600; cursor: pointer;
    font-family: Arial, sans-serif;
}
.btn-cetak { background: #1d4ed8; color: white; }
.btn-tutup { background: #e5e7eb; color: #374151; }
.toolbar-note { font-size: 10px; color: #6b7280; line-height: 1.4; }
.toolbar-note strong { color: #dc2626; }

/* ── MODAL ── */
.modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.5); z-index: 999;
    align-items: center; justify-content: center;
}
.modal-overlay.show { display: flex; }
.modal-box {
    background: white; border-radius: 12px; padding: 22px 26px;
    width: 490px; max-width: 95vw;
    box-shadow: 0 20px 60px rgba(0,0,0,.25);
}
.modal-box h2 { font-size: 14px; color: #1e3a52; margin-bottom: 4px; }
.modal-box p  { font-size: 10.5px; color: #6b7280; margin-bottom: 14px; }
.field-group { margin-bottom: 10px; }
.field-group label {
    display: block; font-size: 10px; font-weight: 700;
    color: #374151; margin-bottom: 3px;
}
.field-group input {
    width: 100%; padding: 6px 9px; border: 1px solid #d1d5db;
    border-radius: 5px; font-size: 11px; font-family: Arial, sans-serif;
}
.field-group input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px #bfdbfe; }
.field-sub { font-size: 9px; color: #9ca3af; margin-top: 2px; }
.modal-hr  { border: none; border-top: 1px solid #e5e7eb; margin: 12px 0; }
.modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 16px; }
.modal-actions button {
    padding: 7px 16px; border-radius: 6px; border: none;
    font-size: 11.5px; font-weight: 600; cursor: pointer;
    font-family: Arial, sans-serif;
}
.btn-ok     { background: #1d4ed8; color: white; }
.btn-cancel { background: #f3f4f6; color: #374151; }

/* ── KERTAS PREVIEW ── */
#print-wrap {
    background: white;
    width: 291mm;          /* 297mm - 3mm - 3mm */
    margin: 0 auto;
    padding: 4mm 3mm 3mm 3mm;
    box-shadow: 0 4px 24px rgba(0,0,0,.18);
}

/* ── JUDUL ── */
.doc-title {
    text-align: center;
    font-size: 10.5px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: .3px;
    margin-bottom: 1mm;
}
.doc-sub {
    text-align: center;
    font-size: 8.5px;
    color: #374151;
    margin-bottom: 3mm;
}

/* ── TABEL RENCANA ── */
.tbl {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 8px;
}

/* Lebar kolom — total 285mm (291mm - 6mm padding kiri-kanan) */
/* No=7, Nama=68, Jenis=32, Metode=36, Vol=22, Ang=30 = 195mm */
/* Bulan: 285-195 = 90mm / 12 = 7.5mm per bulan */
.col-no   { width: 7mm;   }
.col-nama { width: 68mm;  }
.col-jen  { width: 32mm;  }
.col-met  { width: 36mm;  }
.col-vol  { width: 22mm;  }
.col-ang  { width: 30mm;  }
.col-bln  { width: 7.5mm; }

/* Header tabel */
.tbl thead th {
    background: #1e3a8a;
    color: white;
    text-align: center;
    vertical-align: middle;
    font-size: 7.5px;
    font-weight: bold;
    border: .8px solid #1e3a8a;
    padding: 2.5px 1.5px;
    line-height: 1.3;
    word-break: break-word;
}

/* Data cell */
.tbl tbody td {
    border: .8px solid #94a3b8;
    vertical-align: middle;
    padding: 0 2.5px;
    height: <?= $rowMm ?>mm;   /* tinggi dinamis */
    line-height: 1.3;
    word-break: break-word;
    overflow: hidden;
}

/* Zebra stripe */
.tbl tbody tr:nth-child(even) td { background: #f8faff; }

.td-no    { text-align: center; }
.td-nama  { text-align: left; padding-left: 3px; }
.td-ctr   { text-align: center; }
.td-right { text-align: right; padding-right: 3px; }

/* Bulan aktif — warna tercetak */
.bln-on {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* Baris total */
.row-total td {
    background: #eff6ff !important;
    font-weight: bold;
    font-size: 8.5px;
    border-top: 1.2px solid #1e3a8a;
    height: <?= min($rowMm + 0.5, 7) ?>mm;
}

/* ── FOOTER INFO ── */
.footer-info {
    font-size: 7.5px;
    color: #9ca3af;
    margin-top: 2mm;
}

/* ── AREA TTD ── */
.ttd-wrap {
    display: flex;
    justify-content: space-around;
    margin-top: 5mm;
}
.ttd-col {
    width: 30%;
    text-align: center;
}
.ttd-label   { font-weight: bold; font-size: 8.5px; }
.ttd-jabatan { font-size: 8px; color: #374151; margin-top: .5mm; min-height: 8mm; line-height: 1.4; }
.ttd-spacer  { height: 14mm; }
.ttd-line    { border-top: .8px solid #000; width: 44mm; margin: 0 auto 1mm; }
.ttd-nama    { font-weight: bold; font-size: 8.5px; min-height: 4mm; }
.ttd-nip     { font-size: 7.5px; color: #555; }

/* ══════════════════════════════════════
   PRINT — yang paling penting
══════════════════════════════════════ */
@page {
    size: A4 landscape;
    margin: 4mm 3mm 3mm 3mm;
}

@media print {
    /* Sembunyikan toolbar & modal */
    .no-print, .modal-overlay { display: none !important; }

    /* Hilangkan URL footer browser:
       Chrome/Edge: Settings > More settings > Headers and footers → OFF
       Firefox: sudah hilang otomatis kalau margin 0
       Safari: Tidak ada opsi, tapi margin kecil memaksimalkan konten */
    body {
        background: white !important;
        padding: 0 !important;
        margin: 0 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Print-wrap memenuhi seluruh area @page */
    #print-wrap {
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        box-shadow: none !important;
    }

    /* Font cetak sedikit lebih kecil agar muat */
    .tbl           { font-size: 7.5px; }
    .tbl thead th  { font-size: 7px; padding: 2px 1px; }
    .tbl tbody td  { font-size: 7.5px; }
    .row-total td  { font-size: 8px; }
    .doc-title     { font-size: 10px; }
    .doc-sub       { font-size: 8px; }
    .ttd-col       { font-size: 8px; }

    /* Paksa warna bulan dan zebra tercetak */
    .bln-on { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    .tbl tbody tr:nth-child(even) td {
        background: #f8faff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .row-total td {
        background: #eff6ff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
</head>
<body>

<!-- TOOLBAR -->
<div class="toolbar no-print">
    <button class="btn-cetak" onclick="bukaModal()">🖨️ Isi TTD &amp; Cetak</button>
    <button class="btn-tutup" onclick="window.close()">✕ Tutup</button>
    <div class="toolbar-note">
        Setelah dialog cetak terbuka: pastikan <strong>A4 · Landscape</strong>,
        aktifkan <strong>Grafik Latar Belakang</strong>,
        dan <strong>matikan "Header &amp; Footer"</strong> (untuk hilangkan URL pojok kiri bawah).
    </div>
</div>

<!-- MODAL TTD -->
<div class="modal-overlay" id="modalTTD">
    <div class="modal-box">
        <h2>✍️ Isi Nama Penandatangan</h2>
        <p>Nama yang diisi akan muncul di bagian tanda tangan dokumen cetak.</p>

        <div class="field-group">
            <label>Disahkan oleh — Unsur Direksi</label>
            <input type="text" id="ttdKiri" placeholder="Nama lengkap">
            <div class="field-sub">Contoh: Ir. H. Ahmad Fauzi, M.T.</div>
        </div>
        <div class="field-group">
            <label>NIP / NRP (opsional)</label>
            <input type="text" id="nipKiri" placeholder="Kosongkan jika tidak ada">
        </div>
        <hr class="modal-hr">
        <div class="field-group">
            <label>Diketahui oleh — Manajer Bidang Umum</label>
            <input type="text" id="ttdTengah" placeholder="Nama lengkap">
        </div>
        <div class="field-group">
            <label>NIP / NRP (opsional)</label>
            <input type="text" id="nipTengah" placeholder="Kosongkan jika tidak ada">
        </div>
        <hr class="modal-hr">
        <div class="field-group">
            <label>Dibuat oleh — Asisten Manajer Pengadaan</label>
            <input type="text" id="ttdKanan" placeholder="Nama lengkap">
        </div>
        <div class="field-group">
            <label>NIP / NRP (opsional)</label>
            <input type="text" id="nipKanan" placeholder="Kosongkan jika tidak ada">
        </div>

        <div class="modal-actions">
            <button class="btn-cancel" onclick="tutupModal()">Batal</button>
            <button class="btn-ok" onclick="terapkanDanCetak()">✓ Terapkan &amp; Cetak</button>
        </div>
    </div>
</div>

<!-- ══ AREA CETAK ══ -->
<div id="print-wrap">

    <div class="doc-title">Rencana Kegiatan Pengadaan Tahun <?= $tahun ?></div>
    <?php if (defined('APP_FULLNAME')): ?>
    <div class="doc-sub"><?= xe(APP_FULLNAME) ?></div>
    <?php endif; ?>
    <?php if ($jenis): ?>
    <div class="doc-sub" style="font-style:italic;"><?= getLabelJenis($jenis) ?></div>
    <?php endif; ?>

    <table class="tbl">
        <colgroup>
            <col class="col-no">
            <col class="col-nama">
            <col class="col-jen">
            <col class="col-met">
            <col class="col-vol">
            <col class="col-ang">
            <?php for ($i = 0; $i < 12; $i++): ?><col class="col-bln"><?php endfor; ?>
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2">No.</th>
                <th rowspan="2">Nama Kegiatan</th>
                <th rowspan="2">Jenis<br>Pengadaan</th>
                <th rowspan="2">Metode<br>Pengadaan</th>
                <th rowspan="2">Volume &amp;<br>Satuan</th>
                <th rowspan="2">Nilai Anggaran<br>(Rp)</th>
                <th colspan="12" style="letter-spacing:.4px;">Jadwal Pelaksanaan (Bulan)</th>
            </tr>
            <tr>
                <?php foreach (NAMA_BULAN as $b => $nm): ?>
                    <th style="font-size:6.5px; padding:2px 0;"><?= substr($nm,0,3) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php $no = 1; foreach ($dataRows as $row):
            $warna = $metodeColors[$row['metode_pengadaan']] ?? '#94a3b8'; ?>
            <tr>
                <td class="td-no"><?= $no++ ?></td>
                <td class="td-nama"><?= sanitize($row['nama_kegiatan']) ?></td>
                <td class="td-ctr"><?= getLabelJenis($row['jenis_pengadaan']) ?></td>
                <td class="td-ctr"><?= getLabelMetode($row['metode_pengadaan']) ?></td>
                <td class="td-ctr"><?= formatAngka($row['volume']) ?> <?= sanitize($row['satuan']) ?></td>
                <td class="td-right"><?= formatRupiah($row['nilai_anggaran'], '') ?></td>
                <?php for ($b = 1; $b <= 12; $b++):
                    $ada = bulanAda($row['bulan_rencana'], $b); ?>
                    <td class="td-ctr bln-on"
                        style="<?= $ada ? "background:{$warna};" : '' ?>"></td>
                <?php endfor; ?>
            </tr>
        <?php endforeach; ?>
            <tr class="row-total">
                <td colspan="5" class="td-right" style="padding-right:4px; letter-spacing:.3px;">
                    TOTAL ANGGARAN :
                </td>
                <td class="td-right"><?= formatRupiah($total, '') ?></td>
                <td colspan="12"></td>
            </tr>
        </tbody>
    </table>

    <div class="footer-info">Dicetak: <?= date('d F Y, H:i') ?> WIB &nbsp;|&nbsp; <?= $jumlahBaris ?> kegiatan</div>

    <!-- TTD -->
    <div class="ttd-wrap">
        <div class="ttd-col">
            <div class="ttd-label">Disahkan oleh,</div>
            <div class="ttd-jabatan">Unsur Direksi Yang Ditetapkan<br>Kewenangannya Oleh Direktur Utama</div>
            <div class="ttd-spacer"></div>
            <div class="ttd-line"></div>
            <div class="ttd-nama" id="displayNamaKiri">&nbsp;</div>
            <div class="ttd-nip"  id="displayNipKiri"></div>
        </div>
        <div class="ttd-col">
            <div class="ttd-label">Diketahui oleh,</div>
            <div class="ttd-jabatan">Manajer Bidang Umum</div>
            <div class="ttd-spacer"></div>
            <div class="ttd-line"></div>
            <div class="ttd-nama" id="displayNamaTengah">&nbsp;</div>
            <div class="ttd-nip"  id="displayNipTengah"></div>
        </div>
        <div class="ttd-col">
            <div class="ttd-label">Dibuat oleh,</div>
            <div class="ttd-jabatan">Asisten Manajer Pengadaan</div>
            <div class="ttd-spacer"></div>
            <div class="ttd-line"></div>
            <div class="ttd-nama" id="displayNamaKanan">&nbsp;</div>
            <div class="ttd-nip"  id="displayNipKanan"></div>
        </div>
    </div>

</div><!-- end #print-wrap -->

<script>
var fields = ['ttdKiri','nipKiri','ttdTengah','nipTengah','ttdKanan','nipKanan'];

function bukaModal() {
    fields.forEach(function(id) {
        var v = sessionStorage.getItem('ttd_' + id);
        if (v) document.getElementById(id).value = v;
    });
    document.getElementById('modalTTD').classList.add('show');
    document.getElementById('ttdKiri').focus();
}
function tutupModal() {
    document.getElementById('modalTTD').classList.remove('show');
}
function terapkanDanCetak() {
    fields.forEach(function(id) {
        sessionStorage.setItem('ttd_' + id, document.getElementById(id).value);
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
document.getElementById('modalTTD').addEventListener('click', function(e) {
    if (e.target === this) tutupModal();
});
fields.forEach(function(id) {
    document.getElementById(id).addEventListener('keydown', function(e) {
        if (e.key === 'Enter') terapkanDanCetak();
    });
});
window.onload = function() { bukaModal(); };
</script>
</body>
</html>