<?php
/**
 * modules/rencana/import.php
 * Import rencana kegiatan — format Template SIREKA + format RPK PTAM
 * Semua role bisa import (staf, manajer, admin)
 * Fitur: edit inline baris error di tabel preview sebelum import
 */
session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db    = getDB();
$uid   = (int)($_SESSION['user_id'] ?? 0);
$role  = $_SESSION['user_role'] ?? '';
$isStaf = ($role === 'staf_pengadaan');

$jenisMap = [
    'sipil'                       => 'pekerjaan_konstruksi',
    'barang'                      => 'barang',
    'jasakonsultan'               => 'jasa_konsultansi',
    'jasa konsultan'              => 'jasa_konsultansi',
    'jasa konsultansi'            => 'jasa_konsultansi',
    'jasa konsultansi konstruksi' => 'jasa_konsultansi_konstruksi',
    'jasalainnya'                 => 'jasa_lainnya',
    'jasa lainnya'                => 'jasa_lainnya',
    'pekerjaan konstruksi'        => 'pekerjaan_konstruksi',
    'pekerjaankonstruksi'         => 'pekerjaan_konstruksi',
];
$metodeMap = [
    'tender terbatas'    => 'tender_terbatas',
    'tenderterbatas'     => 'tender_terbatas',
    'pembelian langsung' => 'pembelian_langsung',
    'pembelianlangsung'  => 'pembelian_langsung',
    'tender umum'        => 'tender_umum',
    'tenderumum'         => 'tender_umum',
    'tu'                 => 'tender_umum',
    'tender'             => 'tender_umum',
    'e-purchasing'       => 'e_purchasing',
    'e purchasing'       => 'e_purchasing',
    'epurchasing'        => 'e_purchasing',
    'swakelola'          => 'swakelola',
];

// ── PROSES IMPORT (menerima data final sudah diedit dari JS) ─────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['json_data'])) {
    header('Content-Type: application/json');
    $rows     = json_decode($_POST['json_data'], true);
    $tahunOvr = (int)($_POST['tahun_override'] ?? 0);

    if (!is_array($rows)) {
        echo json_encode(['status'=>'error','message'=>'Data tidak valid.']);
        exit;
    }

    $validJenis  = array_keys(LABEL_JENIS);
    $validMetode = array_keys(LABEL_METODE);
    $imported = 0; $skipped = 0; $errors = [];

    $db->begin_transaction();
    try {
        foreach ($rows as $i => $row) {
            $lineNo = $i + 1;
            // row sudah dalam format normal (dari JS normalizeRow + editan user)
            $nama   = trim($row['nama_kegiatan']    ?? '');
            $jenis  = trim($row['jenis_pengadaan']  ?? '');
            $metode = trim($row['metode_pengadaan'] ?? '');
            $vol    = (float)($row['volume']         ?? 1);
            $satuan = trim($row['satuan']            ?? 'Paket');
            $nilai  = (float)preg_replace('/[^0-9.]/', '', str_replace(',','',(string)($row['nilai_anggaran']??0)));
            $bulan  = trim($row['bulan_rencana']     ?? '1');
            $tahun  = $tahunOvr ?: (int)($row['tahun'] ?? 2026);
            $ket    = trim($row['keterangan']        ?? '');

            if (!$nama || $nilai <= 0) { $skipped++; continue; }

            $rowErr = [];
            if (!in_array($jenis,  $validJenis))  $rowErr[] = "jenis_pengadaan \"$jenis\" tidak dikenal";
            if (!in_array($metode, $validMetode)) $rowErr[] = "metode_pengadaan \"$metode\" tidak dikenal";
            if ($tahun < 2000 || $tahun > 2100)   $rowErr[] = "tahun tidak valid";

            if (!empty($rowErr)) {
                $errors[] = "Baris $lineNo ($nama): " . implode('; ', $rowErr);
                $skipped++;
                continue;
            }

            $stmt = $db->prepare("
                INSERT INTO rencana_kegiatan
                    (nama_kegiatan, jenis_pengadaan, metode_pengadaan,
                     volume, satuan, nilai_anggaran,
                     bulan_rencana, tahun, keterangan, created_by)
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->bind_param('sssdsdsiis',
                $nama, $jenis, $metode,
                $vol, $satuan, $nilai,
                $bulan, $tahun, $ket, $uid
            );
            $stmt->execute();
            $stmt->close();
            $imported++;
        }

        if ($imported === 0 && !empty($errors)) {
            $db->rollback();
            echo json_encode(['status'=>'error','errors'=>$errors]);
        } else {
            $db->commit();
            echo json_encode(['status'=>'success','imported'=>$imported,'skipped'=>$skipped,'errors'=>$errors]);
        }
    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    }
    exit;
}

$pageTitle = 'Import Rencana Kegiatan';
include __DIR__ . '/../../includes/header.php';
?>

<style>
/* ── Drop zone ───────────────────────────────────────────────────── */
.drop-zone {
    border: 2.5px dashed #93c5fd; border-radius: 12px;
    background: #f0f9ff; transition: all .2s; cursor: pointer;
}
.drop-zone:hover, .drop-zone.drag-over { border-color:#2563eb; background:#dbeafe; }
.drop-zone.has-file                    { border-color:#22c55e; background:#f0fdf4; }

/* ── Preview tabel ───────────────────────────────────────────────── */
#previewTable { table-layout: fixed; font-size:.8rem; }
#previewTable thead th {
    background: #1e3a5f; color: white;
    font-size: .7rem; text-transform: uppercase;
    letter-spacing: .04em; white-space: nowrap;
    position: sticky; top: 0; z-index: 2;
}
#previewTable td { vertical-align: middle; padding: 4px 6px; }

/* Baris status */
.row-ok    td { background: #f0fdf4 !important; }
.row-error td { background: #fff1f2 !important; }
.row-fixed td { background: #fefce8 !important; } /* sudah diedit, belum divalidasi ulang */

/* Badge status */
.badge-ok    { background:#d1fae5; color:#065f46; border:1px solid #34d399; font-size:10px; }
.badge-err   { background:#fee2e2; color:#991b1b; border:1px solid #f87171; font-size:10px; }
.badge-fixed { background:#fef9c3; color:#713f12; border:1px solid #fbbf24; font-size:10px; }

/* Edit inline */
.cell-edit {
    display: none;
    width: 100%;
    font-size: .78rem;
    padding: 2px 5px;
    border: 1.5px solid #3b82f6;
    border-radius: 4px;
    background: white;
}
.cell-view { display: block; cursor: default; }
tr.row-error .cell-view  { cursor: pointer; }
tr.row-error .cell-view:hover { text-decoration: underline dotted #3b82f6; color: #1d4ed8; }

.edit-hint {
    font-size: 10px; color: #94a3b8; margin-top: 2px; display: none;
}
tr.row-error .edit-hint { display: block; }

/* Tooltip error */
.err-tooltip {
    font-size: 10px; color: #dc2626;
    margin-top: 2px; line-height: 1.3;
}

/* Format tab */
.format-tab {
    cursor: pointer; border: 2px solid #e2e8f0;
    border-radius: 8px; padding: 10px 14px; transition: all .15s;
}
.format-tab:hover  { border-color: #93c5fd; background: #f0f9ff; }
.format-tab.active { border-color: #2563eb; background: #eff6ff; }
</style>

<!-- ── PAGE HEADER ─────────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0">Import Rencana Kegiatan</h5>
        <small class="text-muted">Mendukung format Template SIREKA dan format RPK PTAM Giri Menang</small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/modules/rencana/templates/template_rencana.xlsx"
           class="btn btn-outline-success btn-sm" download>
            <i class="bi bi-file-earmark-arrow-down me-1"></i>Unduh Template
        </a>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<!-- ── PILIH FORMAT ─────────────────────────────────────────────────────────── -->
<div class="card mb-3 shadow-sm">
    <div class="card-header fw-bold" style="background:#1e3a5f;color:white;">
        <i class="bi bi-layout-text-sidebar me-2"></i>Pilih Format File
    </div>
    <div class="card-body py-3">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="format-tab active" data-format="auto" onclick="setFormat(this)">
                    <div class="d-flex gap-3 align-items-start">
                        <div style="font-size:1.4rem;">🔍</div>
                        <div>
                            <div class="fw-bold mb-0">Deteksi Otomatis</div>
                            <small class="text-muted">Sistem mendeteksi format dari nama kolom secara otomatis</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="format-tab" data-format="rpk" onclick="setFormat(this)">
                    <div class="d-flex gap-3 align-items-start">
                        <div style="font-size:1.4rem;">📋</div>
                        <div>
                            <div class="fw-bold mb-0">Format RPK PTAM</div>
                            <small class="text-muted">File RPK asli PTAM — kolom <code>NAMA KEGIATAN</code>, <code>NILAI PAGU</code>, bulan Jan–Des</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" id="selectedFormat" value="auto">
    </div>
</div>

<!-- ── KETENTUAN (collapsible) ──────────────────────────────────────────────── -->
<div class="card mb-3 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center"
         style="background:#1e3a5f;color:white;cursor:pointer;"
         data-bs-toggle="collapse" data-bs-target="#infoCollapse">
        <span><i class="bi bi-info-circle me-2"></i>Ketentuan & Mapping Nilai</span>
        <i class="bi bi-chevron-down"></i>
    </div>
    <div class="collapse" id="infoCollapse">
        <div class="card-body py-3">
            <div class="alert alert-primary py-2 mb-3" style="font-size:12px;">
                <i class="bi bi-lightbulb me-1"></i>
                <strong>Format RPK PTAM:</strong> Upload file RPK langsung tanpa diubah.
                Nilai <code>Sipil</code> → <code>pekerjaan_konstruksi</code>,
                <code>TU</code> → <code>tender_umum</code>,
                tanda <code>-</code> di kolom bulan = dijadwalkan bulan itu.
                Baris statistik/tanda tangan di footer dilewati otomatis.
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <h6 class="fw-bold text-primary mb-2" style="font-size:12px;">Mapping JENIS PENGADAAN</h6>
                    <table class="table table-sm table-bordered mb-0" style="font-size:11px;">
                        <thead class="table-light"><tr><th>Di File</th><th>Kode Sistem</th></tr></thead>
                        <tbody>
                        <?php foreach (['Sipil'=>'pekerjaan_konstruksi','Barang'=>'barang',
                                        'JasaKonsultan'=>'jasa_konsultansi','JasaLainnya'=>'jasa_lainnya'] as $r=>$k): ?>
                        <tr><td><code><?= $r ?></code></td><td><code><?= $k ?></code></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold text-primary mb-2" style="font-size:12px;">Mapping METODE</h6>
                    <table class="table table-sm table-bordered mb-0" style="font-size:11px;">
                        <thead class="table-light"><tr><th>Di File</th><th>Kode Sistem</th></tr></thead>
                        <tbody>
                        <?php foreach (['Tender Terbatas'=>'tender_terbatas',
                                        'Pembelian Langsung'=>'pembelian_langsung',
                                        'TU / Tender Umum'=>'tender_umum','Swakelola'=>'swakelola'] as $r=>$k): ?>
                        <tr><td><code><?= $r ?></code></td><td><code><?= $k ?></code></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold text-primary mb-2" style="font-size:12px;">Kode Valid Sistem</h6>
                    <div class="mb-2">
                        <div class="fw-semibold" style="font-size:11px;">jenis_pengadaan:</div>
                        <?php foreach (LABEL_JENIS as $k=>$v): ?>
                        <span class="badge bg-light text-dark border me-1 mb-1" style="font-size:10px;"><?= $k ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div>
                        <div class="fw-semibold" style="font-size:11px;">metode_pengadaan:</div>
                        <?php foreach (LABEL_METODE as $k=>$v): ?>
                        <span class="badge bg-secondary me-1 mb-1" style="font-size:10px;"><?= $k ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── UPLOAD ───────────────────────────────────────────────────────────────── -->
<div class="card mb-4 shadow-sm">
    <div class="card-body p-4">
        <div id="dropZone" class="drop-zone text-center py-4 px-3 mb-3">
            <i class="bi bi-cloud-upload fs-1 text-primary mb-2 d-block"></i>
            <div class="fw-bold mb-1">Seret & Lepas file di sini</div>
            <div class="text-muted small mb-3">atau</div>
            <label class="btn btn-primary btn-sm">
                <i class="bi bi-folder2-open me-1"></i>Pilih File
                <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" hidden>
            </label>
            <div class="text-muted small mt-2">Format: .xlsx / .xls / .csv</div>
            <div id="fileNameDisplay" class="mt-2 fw-semibold text-success" style="display:none;"></div>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-2">
                <label class="fw-semibold small text-nowrap">Override Tahun:</label>
                <input type="number" id="tahunOverride" class="form-control form-control-sm text-center"
                       style="width:90px;" placeholder="Dari file" min="2000" max="2100">
                <small class="text-muted">Kosongkan = ikut file</small>
            </div>
            <button id="btnPreview" class="btn btn-primary btn-sm ms-auto" disabled>
                <i class="bi bi-eye me-1"></i>Preview Data
            </button>
        </div>
    </div>
</div>

<!-- ── PREVIEW ──────────────────────────────────────────────────────────────── -->
<div id="previewSection" style="display:none;" class="card shadow-sm">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="fw-bold">Preview Data</span>
                <span id="previewBadge"></span>
                <span id="formatBadge" class="badge bg-info text-dark" style="font-size:10px;"></span>
            </div>
            <div class="d-flex gap-2">
                <button id="btnImport" class="btn btn-success btn-sm" disabled>
                    <i class="bi bi-cloud-upload me-1"></i>Import Sekarang
                    <span id="btnImportCount" class="badge bg-white text-success ms-1"></span>
                </button>
                <button id="btnReset" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-circle me-1"></i>Reset
                </button>
            </div>
        </div>

        <!-- Banner panduan edit inline -->
        <div id="editHintBanner" class="alert alert-warning py-2 mb-0 mt-2" style="display:none;font-size:12px;">
            <i class="bi bi-pencil-square me-1"></i>
            <strong>Ada baris error.</strong>
            Klik nilai yang <u>bergaris bawah biru</u> di kolom <strong>Jenis</strong> atau <strong>Metode</strong>
            untuk memperbaiki langsung. Setelah diedit, baris otomatis divalidasi ulang.
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:500px;overflow-y:auto;">
            <table class="table table-hover mb-0" id="previewTable">
                <colgroup>
                    <col style="width:36px;">
                    <col style="width:200px;">
                    <col style="width:130px;">
                    <col style="width:130px;">
                    <col style="width:55px;">
                    <col style="width:70px;">
                    <col style="width:110px;">
                    <col style="width:70px;">
                    <col style="width:52px;">
                    <col style="width:90px;">
                </colgroup>
                <thead>
                    <tr>
                        <th class="ps-2">#</th>
                        <th>Nama Kegiatan</th>
                        <th>Jenis Pengadaan</th>
                        <th>Metode</th>
                        <th class="text-center">Vol</th>
                        <th>Satuan</th>
                        <th class="text-end">Nilai Anggaran</th>
                        <th class="text-center">Bulan</th>
                        <th class="text-center">Tahun</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody id="previewBody"></tbody>
            </table>
        </div>

        <!-- Ringkasan error di bawah tabel -->
        <div id="errorSummary" style="display:none;" class="px-3 py-2 border-top bg-light">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div id="errorList" style="font-size:11px;"></div>
                <button id="btnFixAll" class="btn btn-warning btn-sm">
                    <i class="bi bi-magic me-1"></i>Coba Perbaiki Semua Error Otomatis
                </button>
            </div>
        </div>
    </div>
</div>

<div id="importResult" style="display:none;" class="mt-3"></div>

<!-- ── MODAL EDIT BARIS ─────────────────────────────────────────────────────── -->
<div class="modal fade" id="modalEditBaris" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#1e3a5f;color:white;">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-pencil-square me-2"></i>Perbaiki Baris
                    <span id="modalBarisNo" class="badge bg-warning text-dark ms-1"></span>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning py-2 mb-3" id="modalErrMsg" style="font-size:12px;"></div>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Nama Kegiatan</label>
                        <input type="text" id="editNama" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Jenis Pengadaan <span class="text-danger">*</span></label>
                        <select id="editJenis" class="form-select form-select-sm">
                            <option value="">— Pilih Jenis —</option>
                            <?php foreach (LABEL_JENIS as $k=>$v): ?>
                            <option value="<?= $k ?>"><?= $v ?> <small class="text-muted">(<?= $k ?>)</small></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Metode Pengadaan <span class="text-danger">*</span></label>
                        <select id="editMetode" class="form-select form-select-sm">
                            <option value="">— Pilih Metode —</option>
                            <?php foreach (LABEL_METODE as $k=>$v): ?>
                            <option value="<?= $k ?>"><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Nilai Anggaran (Rp)</label>
                        <input type="number" id="editNilai" class="form-control form-control-sm" min="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Volume</label>
                        <input type="number" id="editVolume" class="form-control form-control-sm" min="0.01" step="0.01">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Satuan</label>
                        <input type="text" id="editSatuan" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Bulan Rencana</label>
                        <input type="text" id="editBulan" class="form-control form-control-sm"
                               placeholder="1 atau 1,2,3">
                        <small class="text-muted" style="font-size:10px;">Angka 1–12, beberapa pisahkan koma</small>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Tahun</label>
                        <input type="number" id="editTahun" class="form-control form-control-sm" min="2000" max="2100">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="btnSimpanEdit" class="btn btn-primary btn-sm">
                    <i class="bi bi-check2 me-1"></i>Simpan & Validasi Ulang
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
const VALID_JENIS  = <?= json_encode(array_keys(LABEL_JENIS)) ?>;
const VALID_METODE = <?= json_encode(array_keys(LABEL_METODE)) ?>;
const LABEL_JENIS  = <?= json_encode(LABEL_JENIS) ?>;
const LABEL_METODE = <?= json_encode(LABEL_METODE) ?>;
const BULAN_NAMA   = ['januari','februari','maret','april','mei','juni',
                      'juli','agustus','september','oktober','november','desember'];
const JENIS_MAP = {
    'sipil':'pekerjaan_konstruksi','barang':'barang',
    'jasakonsultan':'jasa_konsultansi','jasa konsultan':'jasa_konsultansi',
    'jasa konsultansi':'jasa_konsultansi',
    'jasa konsultansi konstruksi':'jasa_konsultansi_konstruksi',
    'jasalainnya':'jasa_lainnya','jasa lainnya':'jasa_lainnya',
    'pekerjaan konstruksi':'pekerjaan_konstruksi','pekerjaankonstruksi':'pekerjaan_konstruksi',
};
const METODE_MAP = {
    'tender terbatas':'tender_terbatas','tenderterbatas':'tender_terbatas',
    'pembelian langsung':'pembelian_langsung','pembelianlangsung':'pembelian_langsung',
    'tender umum':'tender_umum','tenderumum':'tender_umum',
    'tu':'tender_umum','tender':'tender_umum',
    'e-purchasing':'e_purchasing','e purchasing':'e_purchasing','epurchasing':'e_purchasing',
    'swakelola':'swakelola',
};

// ── State utama ─────────────────────────────────────────────────────────────
let parsedData    = [];   // data mentah dari SheetJS
let previewRows   = [];   // array row sudah dinormalisasi + field errors[]
let detectedFmt   = 'template';
let editingIdx    = -1;   // index previewRows yang sedang diedit

// ── Format tab ──────────────────────────────────────────────────────────────
function setFormat(el) {
    document.querySelectorAll('.format-tab').forEach(t=>t.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('selectedFormat').value = el.dataset.format;
}

// ── Upload ──────────────────────────────────────────────────────────────────
const dropZone  = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');

dropZone.addEventListener('dragover', e=>{e.preventDefault();dropZone.classList.add('drag-over');});
dropZone.addEventListener('dragleave',()=>dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop',e=>{
    e.preventDefault(); dropZone.classList.remove('drag-over');
    if(e.dataTransfer.files[0]) handleFile(e.dataTransfer.files[0]);
});
fileInput.addEventListener('change',e=>{if(e.target.files[0]) handleFile(e.target.files[0]);});

function handleFile(file) {
    const ext = file.name.split('.').pop().toLowerCase();
    if(!['xlsx','xls','csv'].includes(ext)){alert('Format tidak didukung.');return;}
    document.getElementById('fileNameDisplay').textContent='📄 '+file.name;
    document.getElementById('fileNameDisplay').style.display='block';
    dropZone.classList.add('has-file');
    document.getElementById('btnPreview').disabled=false;

    const reader=new FileReader();
    reader.onload=e=>{
        const data=new Uint8Array(e.target.result);
        const wb=XLSX.read(data,{type:'array',cellDates:true});
        const wsName=wb.SheetNames.find(s=>s.toLowerCase().includes('rpk'))||wb.SheetNames[0];
        const ws=wb.Sheets[wsName];

        // Cari baris header
        const range=XLSX.utils.decode_range(ws['!ref']);
        let headerRow=0;
        for(let r=0;r<=Math.min(20,range.e.r);r++){
            const cell=ws[XLSX.utils.encode_cell({r,c:1})];
            if(cell&&String(cell.v||'').toUpperCase().includes('NAMA KEGIATAN')){headerRow=r;break;}
        }
        parsedData=XLSX.utils.sheet_to_json(ws,{raw:false,defval:'',range:headerRow});

        const keys=Object.keys(parsedData[0]||{}).map(k=>k.toLowerCase().trim());
        detectedFmt=(keys.includes('nama kegiatan')||keys.includes('nilai pagu'))?'rpk':'template';
        document.getElementById('formatBadge').textContent=
            detectedFmt==='rpk'?'📋 Format RPK PTAM':'📝 Format Template SIREKA';
    };
    reader.readAsArrayBuffer(file);
}

// ── Normalisasi 1 baris mentah → object siap pakai ─────────────────────────
function normalizeRow(rawRow, tahunOvr) {
    const r={};
    Object.keys(rawRow).forEach(k=>r[k.toLowerCase().trim()]=String(rawRow[k]).trim());

    if(detectedFmt==='rpk'){
        const bulan=[];
        BULAN_NAMA.forEach((n,i)=>{if(r[n]==='-')bulan.push(i+1);});
        const jRaw=(r['jenis pengadaan']||'').toLowerCase().replace(/\s/g,'').replace('.','');
        const mRaw=(r['metode']||r['metode pengadaan']||'').toLowerCase().trim();
        const nilai=parseFloat((r['nilai pagu']||'').replace(/[^0-9.]/g,''))||0;
        return {
            nama_kegiatan:   r['nama kegiatan']||'',
            jenis_pengadaan: JENIS_MAP[jRaw]||jRaw,
            metode_pengadaan:METODE_MAP[mRaw]||mRaw.replace(/\s/g,'_'),
            volume:1, satuan:'Paket',
            nilai_anggaran:nilai,
            bulan_rencana:bulan.join(',')||'1',
            tahun:tahunOvr||2026,
            keterangan:'',
        };
    } else {
        const jRaw=(r['jenis_pengadaan']||'').toLowerCase().trim();
        const mRaw=(r['metode_pengadaan']||'').toLowerCase().trim();
        const nilai=parseFloat((r['nilai_anggaran']||'').replace(/[^0-9.]/g,''))||0;
        return {
            nama_kegiatan:   r['nama_kegiatan']||'',
            jenis_pengadaan: JENIS_MAP[jRaw.replace(/\s/g,'')]||JENIS_MAP[jRaw]||jRaw,
            metode_pengadaan:METODE_MAP[mRaw]||mRaw.replace(/\s/g,'_'),
            volume:parseFloat(r['volume'])||1,
            satuan:r['satuan']||'Paket',
            nilai_anggaran:nilai,
            bulan_rencana:r['bulan_rencana']||'1',
            tahun:parseInt(r['tahun'])||tahunOvr||2026,
            keterangan:r['keterangan']||'',
        };
    }
}

// ── Validasi 1 row → array pesan error ──────────────────────────────────────
function validateRow(row) {
    const errs=[];
    if(!row.nama_kegiatan)                         errs.push('nama_kegiatan kosong');
    if(!VALID_JENIS.includes(row.jenis_pengadaan)) errs.push('jenis "'+row.jenis_pengadaan+'" tidak dikenal');
    if(!VALID_METODE.includes(row.metode_pengadaan))errs.push('metode "'+row.metode_pengadaan+'" tidak dikenal');
    if(!row.nilai_anggaran||row.nilai_anggaran<=0)  errs.push('nilai_anggaran tidak valid');
    if(row.tahun<2000||row.tahun>2100)              errs.push('tahun tidak valid');
    return errs;
}

// ── PREVIEW ─────────────────────────────────────────────────────────────────
document.getElementById('btnPreview').addEventListener('click',buildPreview);

function buildPreview() {
    const tahunOvr=parseInt(document.getElementById('tahunOverride').value)||0;
    previewRows=[];

    parsedData.forEach(rawRow=>{
        const row=normalizeRow(rawRow,tahunOvr);
        if(!row.nama_kegiatan&&row.nilai_anggaran<=0) return; // baris kosong skip
        row.errors=validateRow(row);
        previewRows.push(row);
    });

    renderPreview();
    document.getElementById('previewSection').style.display='block';
    document.getElementById('importResult').style.display='none';
}

// ── Render tabel preview dari previewRows ────────────────────────────────────
function renderPreview() {
    const tbody=document.getElementById('previewBody');
    tbody.innerHTML='';

    let okCount=0, errCount=0;
    const errMessages=[];

    previewRows.forEach((row,idx)=>{
        const isOk=row.errors.length===0;
        if(isOk) okCount++; else errCount++;

        const tr=document.createElement('tr');
        tr.className=isOk?'row-ok':'row-error';
        tr.dataset.idx=idx;

        const jLabel=LABEL_JENIS[row.jenis_pengadaan]||('<span class="text-danger fw-bold">'+esc(row.jenis_pengadaan)+'</span>');
        const mLabel=LABEL_METODE[row.metode_pengadaan]||('<span class="text-danger fw-bold">'+esc(row.metode_pengadaan)+'</span>');
        const jValid=VALID_JENIS.includes(row.jenis_pengadaan);
        const mValid=VALID_METODE.includes(row.metode_pengadaan);

        // Kolom jenis & metode: klik buka modal jika error
        const jCell = !isOk
            ? `<td title="${row.errors.join('; ')}" onclick="bukaModalEdit(${idx})" style="cursor:pointer;">
                <span class="badge ${jValid?'bg-light text-dark border':'bg-danger'}" style="font-size:10px;">${jLabel}</span>
                ${!jValid?'<div class="err-tooltip">⚠ klik untuk perbaiki</div>':''}
               </td>`
            : `<td><span class="badge bg-light text-dark border" style="font-size:10px;">${jLabel}</span></td>`;

        const mCell = !isOk
            ? `<td title="${row.errors.join('; ')}" onclick="bukaModalEdit(${idx})" style="cursor:pointer;">
                <span class="badge ${mValid?'bg-secondary':'bg-danger'}" style="font-size:10px;">${mLabel}</span>
                ${!mValid?'<div class="err-tooltip">⚠ klik untuk perbaiki</div>':''}
               </td>`
            : `<td><span class="badge bg-secondary" style="font-size:10px;">${mLabel}</span></td>`;

        tr.innerHTML=`
            <td class="ps-2 text-muted">${idx+1}</td>
            <td style="max-width:200px;">
                <div class="fw-semibold text-truncate" title="${esc(row.nama_kegiatan)}" style="font-size:12px;">${esc(row.nama_kegiatan)}</div>
                ${!isOk?`<div style="font-size:10px;color:#dc2626;">✗ ${row.errors.join(' &bull; ')}</div>`:''}
            </td>
            ${jCell}
            ${mCell}
            <td class="text-center">${row.volume}</td>
            <td>${esc(row.satuan)}</td>
            <td class="text-end fw-semibold text-primary" style="font-size:12px;">${formatRp(row.nilai_anggaran)}</td>
            <td class="text-center" style="font-size:11px;">${esc(row.bulan_rencana)}</td>
            <td class="text-center">${row.tahun}</td>
            <td class="text-center">
                ${isOk
                    ?'<span class="badge badge-ok">✓ OK</span>'
                    :`<button class="btn btn-outline-warning btn-sm py-0 px-1" style="font-size:10px;"
                             onclick="bukaModalEdit(${idx})">
                         <i class="bi bi-pencil me-1"></i>Perbaiki
                      </button>`
                }
            </td>
        `;
        tbody.appendChild(tr);

        if(!isOk) errMessages.push(`Baris ${idx+1}: ${row.errors.join('; ')}`);
    });

    // Update badge header
    document.getElementById('previewBadge').innerHTML=
        `<span class="badge bg-success me-1">${okCount} OK</span>`+
        (errCount?`<span class="badge bg-danger">${errCount} error</span>`:'');

    const btnImp=document.getElementById('btnImport');
    btnImp.disabled=okCount===0;
    document.getElementById('btnImportCount').textContent=okCount>0?okCount+' data':'';

    // Banner & ringkasan error
    document.getElementById('editHintBanner').style.display=errCount>0?'':'none';
    const errDiv=document.getElementById('errorSummary');
    if(errCount>0){
        errDiv.style.display='';
        document.getElementById('errorList').innerHTML=
            `<strong>${errCount} baris belum bisa diimport:</strong><br>`+
            errMessages.map(m=>`<span class="text-danger">• ${m}</span>`).join('<br>');
    } else {
        errDiv.style.display='none';
    }
}

// ── Buka Modal Edit ──────────────────────────────────────────────────────────
function bukaModalEdit(idx) {
    editingIdx=idx;
    const row=previewRows[idx];
    document.getElementById('modalBarisNo').textContent='Baris '+(idx+1);
    document.getElementById('modalErrMsg').innerHTML=
        '<i class="bi bi-exclamation-triangle me-1"></i><strong>Error:</strong> '+row.errors.join(' &bull; ');
    document.getElementById('editNama').value   =row.nama_kegiatan;
    document.getElementById('editJenis').value  =row.jenis_pengadaan;
    document.getElementById('editMetode').value =row.metode_pengadaan;
    document.getElementById('editNilai').value  =row.nilai_anggaran;
    document.getElementById('editVolume').value =row.volume;
    document.getElementById('editSatuan').value =row.satuan;
    document.getElementById('editBulan').value  =row.bulan_rencana;
    document.getElementById('editTahun').value  =row.tahun;
    new bootstrap.Modal(document.getElementById('modalEditBaris')).show();
}

// ── Simpan hasil edit ────────────────────────────────────────────────────────
document.getElementById('btnSimpanEdit').addEventListener('click',function(){
    if(editingIdx<0) return;
    const row=previewRows[editingIdx];

    row.nama_kegiatan    =document.getElementById('editNama').value.trim();
    row.jenis_pengadaan  =document.getElementById('editJenis').value.trim();
    row.metode_pengadaan =document.getElementById('editMetode').value.trim();
    row.nilai_anggaran   =parseFloat(document.getElementById('editNilai').value)||0;
    row.volume           =parseFloat(document.getElementById('editVolume').value)||1;
    row.satuan           =document.getElementById('editSatuan').value.trim()||'Paket';
    row.bulan_rencana    =document.getElementById('editBulan').value.trim()||'1';
    row.tahun            =parseInt(document.getElementById('editTahun').value)||2026;

    // Validasi ulang
    row.errors=validateRow(row);

    bootstrap.Modal.getInstance(document.getElementById('modalEditBaris')).hide();
    renderPreview(); // re-render tabel

    // Scroll ke baris yang baru diedit
    setTimeout(()=>{
        const tr=document.querySelector(`#previewBody tr[data-idx="${editingIdx}"]`);
        if(tr) tr.scrollIntoView({behavior:'smooth',block:'center'});
    },100);
});

// ── Perbaiki otomatis (best-guess untuk metode/jenis kosong) ─────────────────
document.getElementById('btnFixAll').addEventListener('click',function(){
    let fixed=0;
    previewRows.forEach(row=>{
        if(row.errors.length===0) return;
        let changed=false;

        // Jika jenis kosong/tidak dikenal → tebak dari nama kegiatan
        if(!VALID_JENIS.includes(row.jenis_pengadaan)){
            const nama=row.nama_kegiatan.toLowerCase();
            let tebak='barang'; // default
            if(nama.includes('jasa')||nama.includes('konsultan')||nama.includes('pengawasan')||
               nama.includes('perencanaan')||nama.includes('studi')) tebak='jasa_konsultansi';
            else if(nama.includes('konstruksi')||nama.includes('bangunan')||
                    nama.includes('sipil')||nama.includes('pemasangan')||
                    nama.includes('pembangunan')||nama.includes('perbaikan')||
                    nama.includes('pengeboran')||nama.includes('bronjong')||
                    nama.includes('pembuatan')) tebak='pekerjaan_konstruksi';
            else if(nama.includes('leasing')||nama.includes('sewa')||
                    nama.includes('pengurus')||nama.includes('pengurusan')||
                    nama.includes('biaya')) tebak='jasa_lainnya';
            row.jenis_pengadaan=tebak;
            changed=true;
        }

        // Jika metode kosong → default tender_terbatas
        if(!VALID_METODE.includes(row.metode_pengadaan)){
            row.metode_pengadaan='tender_terbatas';
            changed=true;
        }

        if(changed){
            row.errors=validateRow(row);
            if(row.errors.length===0) fixed++;
        }
    });

    renderPreview();
    if(fixed>0){
        const banner=document.createElement('div');
        banner.className='alert alert-success py-2 mt-2 mx-3';
        banner.style.fontSize='12px';
        banner.innerHTML=`<i class="bi bi-check2-circle me-1"></i><strong>${fixed} baris berhasil diperbaiki otomatis.</strong> Periksa kembali sebelum import.`;
        document.getElementById('errorSummary').after(banner);
        setTimeout(()=>banner.remove(),4000);
    }
});

// ── Import ───────────────────────────────────────────────────────────────────
document.getElementById('btnImport').addEventListener('click',function(){
    const tahunOvr=parseInt(document.getElementById('tahunOverride').value)||0;
    const toImport=previewRows.filter(r=>r.errors.length===0);
    if(toImport.length===0){alert('Tidak ada data valid untuk diimport.');return;}

    const btnImp=this;
    btnImp.disabled=true;
    btnImp.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Mengimport...';

    const form=new FormData();
    form.append('json_data',      JSON.stringify(toImport));
    form.append('tahun_override', tahunOvr);

    fetch('import.php',{method:'POST',body:form})
        .then(r=>r.json())
        .then(res=>{
            btnImp.disabled=false;
            btnImp.innerHTML='<i class="bi bi-cloud-upload me-1"></i>Import Sekarang';
            const div=document.getElementById('importResult');
            div.style.display='block';
            if(res.status==='success'){
                const errCount=previewRows.filter(r=>r.errors.length>0).length;
                let html=`<div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>${res.imported} rencana berhasil diimport.</strong>
                    ${errCount>0?`<span class="text-warning ms-2">${errCount} baris error tidak diimport.</span>`:''}
                    <a href="index.php" class="btn btn-success btn-sm ms-3">
                        <i class="bi bi-list me-1"></i>Lihat Rencana
                    </a></div>`;
                if(res.errors&&res.errors.length)
                    html+=`<div class="alert alert-warning"><strong>${res.errors.length} baris bermasalah saat simpan:</strong>
                        <ul class="mb-0 mt-1">${res.errors.map(e=>`<li>${e}</li>`).join('')}</ul></div>`;
                div.innerHTML=html;
            } else {
                div.innerHTML=`<div class="alert alert-danger"><i class="bi bi-x-circle-fill me-2"></i>
                    <strong>Import gagal.</strong> ${res.message||''}
                    ${(res.errors||[]).map(e=>`<li>${e}</li>`).join('')}</div>`;
            }
            div.scrollIntoView({behavior:'smooth'});
        })
        .catch(()=>{
            btnImp.disabled=false;
            btnImp.innerHTML='<i class="bi bi-cloud-upload me-1"></i>Import Sekarang';
            document.getElementById('importResult').innerHTML=
                '<div class="alert alert-danger">Terjadi kesalahan jaringan. Coba lagi.</div>';
            document.getElementById('importResult').style.display='block';
        });
});

// ── Reset ────────────────────────────────────────────────────────────────────
document.getElementById('btnReset').addEventListener('click',()=>{
    parsedData=[]; previewRows=[];
    ['previewSection','importResult'].forEach(id=>document.getElementById(id).style.display='none');
    document.getElementById('fileNameDisplay').style.display='none';
    dropZone.classList.remove('has-file');
    fileInput.value='';
    document.getElementById('btnPreview').disabled=true;
});

// ── Helpers ──────────────────────────────────────────────────────────────────
function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function formatRp(v){return 'Rp '+Math.round(v||0).toLocaleString('id-ID');}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>