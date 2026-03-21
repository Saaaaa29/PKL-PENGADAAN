<?php
/**
 * modules/realisasi/import.php
 * Import realisasi kegiatan dari Excel / CSV
 * Mendukung DUA format:
 *   A — Format Lengkap  : nomor_kontrak, tanggal_mulai, metode_pengadaan, nama_vendor, dst.
 *   B — Format Sederhana: no, nama_vendor, nama_kegiatan, nilai, tanggal_mulai, tanggal_akhir, catatan
 * Fitur: edit inline baris error, auto-fix, deteksi vendor otomatis
 */
session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db     = getDB();
$role   = $_SESSION['user_role'] ?? '';
$uid    = (int)($_SESSION['user_id'] ?? 0);
$isMgr  = in_array($role, ['admin', 'manajer_pengadaan']);
$isStaf = ($role === 'staf_pengadaan');

$validMetode = array_keys(LABEL_METODE);
$validJenis  = array_keys(LABEL_JENIS);
$validStatus = ['proses', 'selesai', 'batal'];

// ── Helper: cari atau buat vendor ────────────────────────────────────────────
function findOrCreateVendor($db, $namaVendor) {
    if (!$namaVendor) return null;
    $nm = $db->real_escape_string(trim($namaVendor));
    $r  = $db->query("SELECT id FROM vendor WHERE nama_vendor = '$nm' LIMIT 1");
    if ($r && $r->num_rows > 0) return (int)$r->fetch_assoc()['id'];
    // Buat baru
    $db->query("INSERT INTO vendor (nama_vendor, status) VALUES ('$nm', 'aktif')");
    return (int)$db->insert_id;
}

// ── PROSES IMPORT ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['json_data'])) {
    header('Content-Type: application/json');
    $rows   = json_decode($_POST['json_data'], true);
    $format = $_POST['format'] ?? 'auto';

    if (!is_array($rows)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak valid.']);
        exit;
    }

    // Normalisasi tanggal
    $normTgl = function ($tgl) {
        if (!$tgl) return null;
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $tgl, $m)) return "$m[3]-$m[2]-$m[1]";
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $tgl))        return $tgl;
        if (is_numeric($tgl)) return date('Y-m-d', (int)(($tgl - 25569) * 86400));
        return $tgl;
    };

    // Kelompokkan by nomor_kontrak
    $groups = [];
    foreach ($rows as $row) {
        $r = [];
        foreach ($row as $k => $v)
            $r[strtolower(trim($k))] = is_string($v) ? trim($v) : (string)$v;

        $noKontrak = $r['nomor_kontrak'] ?? ('IMPORT-' . md5(($r['nama_kegiatan_item'] ?? '') . ($r['tanggal_mulai'] ?? '') . rand()));
        $groups[$noKontrak][] = $r;
    }

    $imported = 0; $skipped = 0; $errors = [];
    $vendorBaru = 0;
    $db->begin_transaction();

    try {
        foreach ($groups as $noKontrak => $items) {
            $first = $items[0];

            $tglMulai   = $normTgl($first['tanggal_mulai']    ?? '');
            $tglSelesai = $normTgl($first['tanggal_selesai']  ?? '');
            $metode     = $first['metode_pengadaan'] ?? '';
            $status     = strtolower($first['status'] ?? 'proses');
            $catatan    = $first['catatan'] ?? '';
            $namaVendor = $first['nama_vendor'] ?? '';

            $hdrErr = [];
            if (!$tglMulai)                        $hdrErr[] = "tanggal_mulai kosong";
            if (!in_array($metode, $validMetode))  $hdrErr[] = "metode \"$metode\" tidak dikenal";
            if (!in_array($status, $validStatus))  $hdrErr[] = "status \"$status\" tidak valid";

            $totalNilai = 0;
            $validItems = [];
            $itemErrs   = [];

            foreach ($items as $itmIdx => $itm) {
                $namaItem = $itm['nama_kegiatan_item'] ?? ($itm['nama_kegiatan'] ?? '');
                $jenis    = $itm['jenis_pengadaan']    ?? '';
                $volume   = $itm['volume']             ?? '1';
                $satuan   = $itm['satuan']             ?? 'Paket';
                $nilaiSat = preg_replace('/[^0-9.]/', '', str_replace(',', '', $itm['nilai_satuan'] ?? ''));
                $itVendor = $itm['nama_vendor'] ?? $namaVendor;

                $itErr = [];
                if (!$namaItem)                              $itErr[] = "nama_kegiatan_item kosong";
                if (!in_array($jenis, $validJenis))         $itErr[] = "jenis \"$jenis\" tdk valid";
                if (!is_numeric($volume) || (float)$volume <= 0) $itErr[] = "volume tdk valid";
                if (!is_numeric($nilaiSat) || (float)$nilaiSat <= 0) $itErr[] = "nilai_satuan tdk valid";

                if ($itErr) {
                    $itemErrs[] = "Item " . ($itmIdx + 1) . ": " . implode('; ', $itErr);
                } else {
                    $vol    = (float)$volume;
                    $nSat   = (float)$nilaiSat;
                    $nTotal = $vol * $nSat;
                    $totalNilai += $nTotal;
                    $validItems[] = [
                        'nama'          => $namaItem,
                        'jenis'         => $jenis,
                        'volume'        => $vol,
                        'satuan'        => $satuan ?: 'Paket',
                        'nilai_satuan'  => $nSat,
                        'nilai_anggaran'=> $nTotal,
                        'nama_vendor'   => $itVendor,
                    ];
                }
            }

            if (!empty($hdrErr) || !empty($itemErrs)) {
                $allErr = array_merge(
                    array_map(fn($e) => "[$noKontrak] $e", $hdrErr),
                    array_map(fn($e) => "[$noKontrak] $e", $itemErrs)
                );
                $errors  = array_merge($errors, $allErr);
                $skipped++;
                continue;
            }

            // Cek duplikat (kecuali auto-generated key)
            if (!str_starts_with($noKontrak, 'IMPORT-')) {
                $chk = $db->query("SELECT id FROM realisasi_kegiatan WHERE nomor_kontrak='" . $db->real_escape_string($noKontrak) . "'");
                if ($chk && $chk->num_rows > 0) {
                    $errors[] = "[$noKontrak] Nomor kontrak sudah ada, dilewati.";
                    $skipped++;
                    continue;
                }
            }

            // Vendor utama kontrak
            $vendorId = null;
            if ($namaVendor) {
                $existVendor = $db->query("SELECT id FROM vendor WHERE nama_vendor='" . $db->real_escape_string($namaVendor) . "' LIMIT 1");
                if ($existVendor && $existVendor->num_rows === 0) $vendorBaru++;
                $vendorId = findOrCreateVendor($db, $namaVendor);
            }

            // Insert realisasi_kegiatan
            $svInit  = $isMgr ? 'disetujui' : 'menunggu';
            $noFinal = str_starts_with($noKontrak, 'IMPORT-') ? ('IMP-' . date('ymd') . '-' . ($imported + 1)) : $noKontrak;

            $stmtR = $db->prepare("
                INSERT INTO realisasi_kegiatan
                    (nomor_kontrak, tanggal_mulai, tanggal_selesai,
                     metode_pengadaan, status, total_nilai, catatan,
                     vendor_id, created_by, status_verifikasi)
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ");
            $stmtR->bind_param('sssssdsiss',
                $noFinal, $tglMulai, $tglSelesai,
                $metode, $status, $totalNilai, $catatan,
                $vendorId, $uid, $svInit
            );
            $stmtR->execute();
            $realId = $db->insert_id;
            $stmtR->close();

            // Insert detail items
            foreach ($validItems as $vi) {
                $viVendorId = $vi['nama_vendor'] ? findOrCreateVendor($db, $vi['nama_vendor']) : $vendorId;
                $stmtD = $db->prepare("
                    INSERT INTO realisasi_detail
                        (realisasi_id, nama_kegiatan, jenis_pengadaan,
                         volume, satuan, nilai_satuan, nilai_anggaran, vendor_id)
                    VALUES (?,?,?,?,?,?,?,?)
                ");
                $stmtD->bind_param('isssdddI',
                    $realId, $vi['nama'], $vi['jenis'],
                    $vi['volume'], $vi['satuan'],
                    $vi['nilai_satuan'], $vi['nilai_anggaran'], $viVendorId
                );
                $stmtD->execute();
                $stmtD->close();
            }

            // Notif ke manajer jika staf
            if (!$isMgr) {
                $pesan = "Realisasi baru via import: \"$noFinal\"";
                $stmtN = $db->prepare("INSERT INTO notifikasi
                    (untuk_role, untuk_user_id, tipe, pesan, realisasi_id, dari_user_id)
                    VALUES ('manajer_pengadaan', NULL, 'input_baru', ?, ?, ?)");
                $stmtN->bind_param('sii', $pesan, $realId, $uid);
                $stmtN->execute();
                $stmtN->close();
            }

            $imported++;
        }

        if ($imported === 0 && !empty($errors)) {
            $db->rollback();
            echo json_encode(['status' => 'error', 'errors' => $errors]);
        } else {
            $db->commit();
            echo json_encode([
                'status'      => 'success',
                'imported'    => $imported,
                'skipped'     => $skipped,
                'vendor_baru' => $vendorBaru,
                'errors'      => $errors,
            ]);
        }
    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

$pageTitle = 'Import Realisasi Kegiatan';
include __DIR__ . '/../../includes/header.php';
?>

<style>
/* ── Drop zone ───────────────────────────────────────────────────── */
.drop-zone {
    border: 2.5px dashed #93c5fd; border-radius: 12px;
    background: #f0f9ff; transition: all .2s; cursor: pointer;
}
.drop-zone:hover, .drop-zone.drag-over { border-color: #2563eb; background: #dbeafe; }
.drop-zone.has-file                    { border-color: #22c55e; background: #f0fdf4; }

/* ── Tabel preview ───────────────────────────────────────────────── */
#previewTable { table-layout: fixed; font-size: .78rem; }
#previewTable thead th {
    background: #1e3a5f; color: white;
    font-size: .68rem; text-transform: uppercase;
    letter-spacing: .04em; white-space: nowrap;
    position: sticky; top: 0; z-index: 2;
}
#previewTable td { vertical-align: middle; padding: 4px 5px; }

/* Status baris */
.row-ok    td { background: #f0fdf4 !important; }
.row-error td { background: #fff1f2 !important; }
.row-new-grup td { border-top: 2px solid #3b82f6 !important; }

/* Badge */
.badge-ok    { background:#d1fae5; color:#065f46; border:1px solid #34d399; font-size:10px; }
.badge-err   { background:#fee2e2; color:#991b1b; border:1px solid #f87171; font-size:10px; }
.err-tooltip { font-size:10px; color:#dc2626; margin-top:2px; line-height:1.3; }

/* Format tab */
.format-tab {
    cursor: pointer; border: 2px solid #e2e8f0;
    border-radius: 8px; padding: 10px 14px; transition: all .15s;
    height: 100%;
}
.format-tab:hover  { border-color: #93c5fd; background: #f0f9ff; }
.format-tab.active { border-color: #2563eb; background: #eff6ff; }
</style>

<!-- ── PAGE HEADER ─────────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0">Import Realisasi Kegiatan</h5>
        <small class="text-muted">Mendukung Format Lengkap dan Format Sederhana Staf</small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/modules/realisasi/templates/template_realisasi.xlsx"
           class="btn btn-outline-success btn-sm" download>
            <i class="bi bi-file-earmark-arrow-down me-1"></i>Unduh Template Lengkap
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

            <!-- Format A -->
            <div class="col-md-4">
                <div class="format-tab active" data-format="auto" onclick="setFormat(this)">
                    <div class="d-flex gap-2 align-items-start">
                        <div style="font-size:1.3rem;">🔍</div>
                        <div>
                            <div class="fw-bold mb-0">Deteksi Otomatis</div>
                            <small class="text-muted">Sistem mendeteksi format dari nama kolom</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Format B — Lengkap -->
            <div class="col-md-4">
                <div class="format-tab" data-format="lengkap" onclick="setFormat(this)">
                    <div class="d-flex gap-2 align-items-start">
                        <div style="font-size:1.3rem;">📋</div>
                        <div>
                            <div class="fw-bold mb-0">Format Lengkap</div>
                            <small class="text-muted">
                                Kolom lengkap: <code>nomor_kontrak</code>,
                                <code>nama_vendor</code>, <code>metode_pengadaan</code>,
                                <code>jenis_pengadaan</code>, dll.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Format C — Sederhana Staf -->
            <div class="col-md-4">
                <div class="format-tab" data-format="sederhana" onclick="setFormat(this)">
                    <div class="d-flex gap-2 align-items-start">
                        <div style="font-size:1.3rem;">📝</div>
                        <div>
                            <div class="fw-bold mb-0">Format Sederhana Staf</div>
                            <small class="text-muted">
                                Kolom minimal: <code>nama_vendor</code>,
                                <code>nama_kegiatan</code>, <code>nilai</code>,
                                <code>tanggal_mulai</code>. Jenis & metode diisi via modal.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <input type="hidden" id="selectedFormat" value="auto">

        <!-- Info RBAC -->
        <?php if ($isMgr): ?>
        <div class="alert alert-success py-2 mb-0 mt-3" style="font-size:12px;">
            <i class="bi bi-shield-check me-1"></i>
            <strong>Manajer/Admin:</strong> semua realisasi yang diimport langsung berstatus <strong>Disetujui</strong>.
        </div>
        <?php else: ?>
        <div class="alert alert-warning py-2 mb-0 mt-3" style="font-size:12px;">
            <i class="bi bi-hourglass-split me-1"></i>
            <strong>Staf:</strong> realisasi yang diimport masuk status <strong>Menunggu Verifikasi</strong> dan manajer akan mendapat notifikasi.
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── KETENTUAN (collapsible) ──────────────────────────────────────────────── -->
<div class="card mb-3 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center"
         style="background:#1e3a5f;color:white;cursor:pointer;"
         data-bs-toggle="collapse" data-bs-target="#infoCollapse">
        <span><i class="bi bi-info-circle me-2"></i>Ketentuan Kolom & Kode Nilai</span>
        <i class="bi bi-chevron-down"></i>
    </div>
    <div class="collapse show" id="infoCollapse">
        <div class="card-body py-3">
            <div class="row g-3">

                <!-- Format Lengkap -->
                <div class="col-md-5" id="infoLengkap">
                    <h6 class="fw-bold text-primary mb-2" style="font-size:12px;">
                        📋 Format Lengkap — Kolom yang dikenali
                    </h6>
                    <table class="table table-sm table-bordered mb-0" style="font-size:10.5px;">
                        <thead class="table-light">
                            <tr><th>Nama Kolom</th><th class="text-center">Wajib</th><th>Keterangan</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ([
                            ['nomor_kontrak',      '✓', 'ID unik kontrak; jika kosong dibuat otomatis'],
                            ['tanggal_mulai',      '✓', 'YYYY-MM-DD atau DD/MM/YYYY'],
                            ['tanggal_selesai',    '—', 'Opsional'],
                            ['metode_pengadaan',   '✓', 'Lihat kode di samping'],
                            ['status',             '✓', 'proses / selesai / batal'],
                            ['nama_vendor',        '—', 'Dicari/dibuat otomatis di tabel vendor'],
                            ['catatan',            '—', 'Opsional'],
                            ['nama_kegiatan_item', '✓', 'Nama item (1 baris = 1 item)'],
                            ['jenis_pengadaan',    '✓', 'Lihat kode di samping'],
                            ['volume',             '✓', 'Angka; default 1 jika kosong'],
                            ['satuan',             '—', 'Default: Paket'],
                            ['nilai_satuan',       '✓', 'Angka bulat, contoh: 8000000'],
                        ] as [$col, $req, $ket]): ?>
                        <tr>
                            <td><code><?= $col ?></code></td>
                            <td class="text-center"><?= $req==='✓' ? '<b class="text-success">✓</b>' : '<span class="text-muted">—</span>' ?></td>
                            <td><?= $ket ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Format Sederhana -->
                <div class="col-md-3" id="infoSederhana">
                    <h6 class="fw-bold text-success mb-2" style="font-size:12px;">
                        📝 Format Sederhana Staf
                    </h6>
                    <table class="table table-sm table-bordered mb-0" style="font-size:10.5px;">
                        <thead class="table-light">
                            <tr><th>Nama Kolom</th><th class="text-center">Wajib</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ([
                            ['no',             '—'],
                            ['nama_vendor',    '—'],
                            ['nama_kegiatan',  '✓'],
                            ['nilai',          '✓'],
                            ['tanggal_mulai',  '✓'],
                            ['tanggal_akhir',  '—'],
                            ['catatan',        '—'],
                        ] as [$col, $req]): ?>
                        <tr>
                            <td><code><?= $col ?></code></td>
                            <td class="text-center"><?= $req==='✓' ? '<b class="text-success">✓</b>' : '<span class="text-muted">—</span>' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="alert alert-warning py-2 mt-2 mb-0" style="font-size:10.5px;">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Kolom <code>jenis_pengadaan</code> dan <code>metode_pengadaan</code>
                        tidak ada di format ini — baris akan <strong>diflag error</strong>
                        dan harus diisi via tombol <strong>Perbaiki</strong> di preview.
                    </div>
                </div>

                <!-- Kode nilai -->
                <div class="col-md-4">
                    <h6 class="fw-bold text-primary mb-2" style="font-size:12px;">Kode metode_pengadaan</h6>
                    <table class="table table-sm table-bordered mb-2" style="font-size:10.5px;">
                        <tbody>
                        <?php foreach (LABEL_METODE as $k => $v): ?>
                        <tr><td><code><?= $k ?></code></td><td><?= $v ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <h6 class="fw-bold text-primary mb-2" style="font-size:12px;">Kode jenis_pengadaan</h6>
                    <table class="table table-sm table-bordered mb-0" style="font-size:10.5px;">
                        <tbody>
                        <?php foreach (LABEL_JENIS as $k => $v): ?>
                        <tr><td><code><?= $k ?></code></td><td><?= $v ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
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
        <div class="d-flex justify-content-end">
            <button id="btnPreview" class="btn btn-primary btn-sm" disabled>
                <i class="bi bi-eye me-1"></i>Preview Data
            </button>
        </div>
    </div>
</div>

<!-- ── PREVIEW ──────────────────────────────────────────────────────────────── -->
<div id="previewSection" style="display:none;" class="card shadow-sm mb-3">
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

        <!-- Banner panduan edit -->
        <div id="editHintBanner" class="alert alert-warning py-2 mb-0 mt-2" style="display:none;font-size:12px;">
            <i class="bi bi-pencil-square me-1"></i>
            <strong>Ada baris error.</strong>
            Klik tombol <strong>Perbaiki</strong> di kolom Status untuk memperbaiki baris sebelum import.
            Atau klik <strong>Coba Perbaiki Otomatis</strong> di bawah tabel.
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:480px;overflow-y:auto;">
            <table class="table table-hover mb-0" id="previewTable">
                <colgroup>
                    <col style="width:32px;">
                    <col style="width:115px;">
                    <col style="width:90px;">
                    <col style="width:85px;">
                    <col style="width:115px;">
                    <col style="width:80px;">
                    <col style="width:145px;">
                    <col style="width:95px;">
                    <col style="width:42px;">
                    <col style="width:55px;">
                    <col style="width:105px;">
                    <col style="width:95px;">
                    <col style="width:92px;">
                </colgroup>
                <thead>
                    <tr>
                        <th class="ps-2">#</th>
                        <th>No. Kontrak</th>
                        <th>Tgl. Mulai</th>
                        <th>Metode</th>
                        <th>Vendor</th>
                        <th>Status</th>
                        <th>Nama Item</th>
                        <th>Jenis</th>
                        <th class="text-center">Vol</th>
                        <th>Satuan</th>
                        <th class="text-end">Nilai Satuan</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-center">Status Baris</th>
                    </tr>
                </thead>
                <tbody id="previewBody"></tbody>
            </table>
        </div>

        <!-- Ringkasan error + tombol fix all -->
        <div id="errorSummary" style="display:none;" class="px-3 py-2 border-top bg-light">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                <div id="errorList" style="font-size:11px; flex:1;"></div>
                <button id="btnFixAll" class="btn btn-warning btn-sm flex-shrink-0">
                    <i class="bi bi-magic me-1"></i>Coba Perbaiki Otomatis
                </button>
            </div>
        </div>
    </div>
</div>

<div id="importResult" style="display:none;" class="mb-3"></div>

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
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">No. Kontrak</label>
                        <input type="text" id="editNoKontrak" class="form-control form-control-sm"
                               placeholder="Kosong = dibuat otomatis">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Nama Vendor</label>
                        <input type="text" id="editVendor" class="form-control form-control-sm"
                               placeholder="Opsional">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold small">Nama Item Kegiatan <span class="text-danger">*</span></label>
                        <input type="text" id="editNamaItem" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Jenis Pengadaan <span class="text-danger">*</span></label>
                        <select id="editJenis" class="form-select form-select-sm">
                            <option value="">— Pilih Jenis —</option>
                            <?php foreach (LABEL_JENIS as $k => $v): ?>
                            <option value="<?= $k ?>"><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Metode Pengadaan <span class="text-danger">*</span></label>
                        <select id="editMetode" class="form-select form-select-sm">
                            <option value="">— Pilih Metode —</option>
                            <?php foreach (LABEL_METODE as $k => $v): ?>
                            <option value="<?= $k ?>"><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Nilai Satuan (Rp) <span class="text-danger">*</span></label>
                        <input type="number" id="editNilaiSat" class="form-control form-control-sm" min="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">Volume</label>
                        <input type="number" id="editVolume" class="form-control form-control-sm"
                               min="0.01" step="0.01" value="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Satuan</label>
                        <input type="text" id="editSatuan" class="form-control form-control-sm"
                               placeholder="Paket">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Status</label>
                        <select id="editStatus" class="form-select form-select-sm">
                            <option value="proses">Proses</option>
                            <option value="selesai">Selesai</option>
                            <option value="batal">Batal</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Tanggal Mulai <span class="text-danger">*</span></label>
                        <input type="date" id="editTglMulai" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Tanggal Selesai</label>
                        <input type="date" id="editTglSelesai" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Catatan</label>
                        <input type="text" id="editCatatan" class="form-control form-control-sm">
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
const VALID_METODE = <?= json_encode(array_keys(LABEL_METODE)) ?>;
const VALID_JENIS  = <?= json_encode(array_keys(LABEL_JENIS)) ?>;
const LABEL_METODE = <?= json_encode(LABEL_METODE) ?>;
const LABEL_JENIS  = <?= json_encode(LABEL_JENIS) ?>;
const VALID_STATUS = ['proses','selesai','batal'];

let parsedData  = [];
let previewRows = [];  // [{nomor_kontrak, tanggal_mulai, metode_pengadaan, nama_vendor,
                       //   status, nama_kegiatan_item, jenis_pengadaan, volume, satuan,
                       //   nilai_satuan, tanggal_selesai, catatan, errors[]}]
let detectedFmt = 'lengkap';
let editingIdx  = -1;

// ── Format tab ──────────────────────────────────────────────────────────────
function setFormat(el) {
    document.querySelectorAll('.format-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('selectedFormat').value = el.dataset.format;
}

// ── Upload ──────────────────────────────────────────────────────────────────
const dropZone  = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');

dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', e => {
    e.preventDefault(); dropZone.classList.remove('drag-over');
    if (e.dataTransfer.files[0]) handleFile(e.dataTransfer.files[0]);
});
fileInput.addEventListener('change', e => { if (e.target.files[0]) handleFile(e.target.files[0]); });

function handleFile(file) {
    const ext = file.name.split('.').pop().toLowerCase();
    if (!['xlsx','xls','csv'].includes(ext)) { alert('Format tidak didukung.'); return; }
    document.getElementById('fileNameDisplay').textContent = '📄 ' + file.name;
    document.getElementById('fileNameDisplay').style.display = 'block';
    dropZone.classList.add('has-file');
    document.getElementById('btnPreview').disabled = false;

    const reader = new FileReader();
    reader.onload = e => {
        const data = new Uint8Array(e.target.result);
        const wb   = XLSX.read(data, { type: 'array', cellDates: true });
        const ws   = wb.Sheets[wb.SheetNames[0]];
        parsedData = XLSX.utils.sheet_to_json(ws, { raw: false, defval: '' });

        // Deteksi format dari nama kolom
        const keys = Object.keys(parsedData[0] || {}).map(k => k.toLowerCase().trim());
        const fmtSel = document.getElementById('selectedFormat').value;
        if (fmtSel !== 'auto') {
            detectedFmt = fmtSel;
        } else if (keys.includes('nama_kegiatan_item') || keys.includes('nomor_kontrak')) {
            detectedFmt = 'lengkap';
        } else if (keys.includes('nama_kegiatan') || keys.includes('nilai')) {
            detectedFmt = 'sederhana';
        } else {
            detectedFmt = 'lengkap';
        }
        document.getElementById('formatBadge').textContent =
            detectedFmt === 'sederhana' ? '📝 Format Sederhana Staf' : '📋 Format Lengkap';
    };
    reader.readAsArrayBuffer(file);
}

// ── Normalisasi tanggal ──────────────────────────────────────────────────────
function normTgl(s) {
    s = String(s || '').trim();
    if (!s) return '';
    const m = s.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (m) return `${m[3]}-${m[2]}-${m[1]}`;
    if (/^\d{4}-\d{2}-\d{2}/.test(s)) return s.substring(0, 10);
    // Coba format "DD-MM-YYYY"
    const m2 = s.match(/^(\d{2})-(\d{2})-(\d{4})$/);
    if (m2) return `${m2[3]}-${m2[2]}-${m2[1]}`;
    return s;
}

// ── Normalisasi 1 baris mentah → object siap pakai ─────────────────────────
function normalizeRow(rawRow) {
    const r = {};
    Object.keys(rawRow).forEach(k => r[k.toLowerCase().trim()] = String(rawRow[k] || '').trim());

    if (detectedFmt === 'sederhana') {
        // no | nama_vendor | nama_kegiatan | nilai | tanggal_mulai | tanggal_akhir | catatan
        const nilai = parseFloat((r['nilai'] || '').replace(/[^0-9.]/g,'')) || 0;
        return {
            nomor_kontrak:      '',       // kosong = auto-generate
            tanggal_mulai:      normTgl(r['tanggal_mulai'] || r['tanggal'] || ''),
            tanggal_selesai:    normTgl(r['tanggal_akhir'] || r['tanggal_selesai'] || ''),
            metode_pengadaan:   '',       // wajib diisi via modal
            status:             'proses',
            nama_vendor:        r['nama_vendor'] || r['vendor'] || '',
            catatan:            r['catatan'] || r['keterangan'] || '',
            nama_kegiatan_item: r['nama_kegiatan'] || r['nama_kegiatan_item'] || '',
            jenis_pengadaan:    '',       // wajib diisi via modal
            volume:             parseFloat(r['volume'] || '1') || 1,
            satuan:             r['satuan'] || 'Paket',
            nilai_satuan:       nilai,
        };
    } else {
        // Format Lengkap
        const nilaiSat = parseFloat((r['nilai_satuan'] || '').replace(/[^0-9.]/g,'')) || 0;
        return {
            nomor_kontrak:      r['nomor_kontrak'] || '',
            tanggal_mulai:      normTgl(r['tanggal_mulai'] || ''),
            tanggal_selesai:    normTgl(r['tanggal_selesai'] || ''),
            metode_pengadaan:   r['metode_pengadaan'] || '',
            status:             (r['status'] || 'proses').toLowerCase(),
            nama_vendor:        r['nama_vendor'] || r['vendor'] || '',
            catatan:            r['catatan'] || '',
            nama_kegiatan_item: r['nama_kegiatan_item'] || r['nama_kegiatan'] || '',
            jenis_pengadaan:    r['jenis_pengadaan'] || '',
            volume:             parseFloat(r['volume'] || '1') || 1,
            satuan:             r['satuan'] || 'Paket',
            nilai_satuan:       nilaiSat,
        };
    }
}

// ── Validasi ─────────────────────────────────────────────────────────────────
function validateRow(row) {
    const errs = [];
    if (!row.nama_kegiatan_item)                        errs.push('nama_kegiatan_item kosong');
    if (!VALID_JENIS.includes(row.jenis_pengadaan))     errs.push('jenis_pengadaan tidak valid');
    if (!VALID_METODE.includes(row.metode_pengadaan))   errs.push('metode_pengadaan tidak valid');
    if (!row.nilai_satuan || row.nilai_satuan <= 0)     errs.push('nilai_satuan tidak valid');
    if (!row.tanggal_mulai)                             errs.push('tanggal_mulai kosong');
    if (!VALID_STATUS.includes(row.status || 'proses')) errs.push('status tidak valid');
    return errs;
}

// ── Build preview ─────────────────────────────────────────────────────────────
document.getElementById('btnPreview').addEventListener('click', buildPreview);

function buildPreview() {
    previewRows = [];
    parsedData.forEach(rawRow => {
        const row = normalizeRow(rawRow);
        if (!row.nama_kegiatan_item && row.nilai_satuan <= 0) return; // baris kosong
        row.errors = validateRow(row);
        previewRows.push(row);
    });
    renderPreview();
    document.getElementById('previewSection').style.display = 'block';
    document.getElementById('importResult').style.display   = 'none';
}

// ── Render tabel ──────────────────────────────────────────────────────────────
function renderPreview() {
    const tbody = document.getElementById('previewBody');
    tbody.innerHTML = '';
    let okCount = 0, errCount = 0;
    const errMessages = [];
    let prevKontrak = null;

    previewRows.forEach((row, idx) => {
        const isOk       = row.errors.length === 0;
        const isNewGroup = row.nomor_kontrak !== prevKontrak;
        prevKontrak = row.nomor_kontrak;
        if (isOk) okCount++; else errCount++;

        const jValid = VALID_JENIS.includes(row.jenis_pengadaan);
        const mValid = VALID_METODE.includes(row.metode_pengadaan);
        const jLabel = LABEL_JENIS[row.jenis_pengadaan]
            || (row.jenis_pengadaan ? `<span class="text-danger">${esc(row.jenis_pengadaan)}</span>` : '<span class="text-muted fst-italic">kosong</span>');
        const mLabel = LABEL_METODE[row.metode_pengadaan]
            || (row.metode_pengadaan ? `<span class="text-danger">${esc(row.metode_pengadaan)}</span>` : '<span class="text-muted fst-italic">kosong</span>');
        const subtotal = (row.volume || 1) * (row.nilai_satuan || 0);

        const tr = document.createElement('tr');
        tr.dataset.idx = idx;
        tr.className = isOk ? 'row-ok' : 'row-error';
        if (isNewGroup && idx > 0) tr.classList.add('row-new-grup');

        tr.innerHTML = `
            <td class="ps-2 text-muted">${idx + 1}</td>
            <td style="font-size:10px;${isNewGroup?'font-weight:700;color:#1e40af;':''}">
                ${esc(row.nomor_kontrak) || '<span class="text-muted fst-italic">auto</span>'}
            </td>
            <td style="font-size:10px;">${esc(row.tanggal_mulai) || '<span class="text-danger">—</span>'}</td>
            <td>
                <span class="badge ${mValid?'bg-secondary':'bg-danger'}" style="font-size:9px;">${mLabel}</span>
                ${!mValid?'<div class="err-tooltip">⚠ klik perbaiki</div>':''}
            </td>
            <td style="font-size:10px;max-width:90px;" class="text-truncate"
                title="${esc(row.nama_vendor)}">${esc(row.nama_vendor)||'<span class="text-muted">—</span>'}</td>
            <td>
                <span class="badge bg-${row.status==='selesai'?'success':row.status==='batal'?'danger':'warning text-dark'}"
                      style="font-size:9px;">${esc(row.status||'proses')}</span>
            </td>
            <td style="font-size:10px;max-width:140px;" class="text-truncate fw-semibold"
                title="${esc(row.nama_kegiatan_item)}">${esc(row.nama_kegiatan_item)||'<span class="text-danger">—</span>'}</td>
            <td>
                <span class="badge ${jValid?'bg-light text-dark border':'bg-danger'}" style="font-size:9px;">${jLabel}</span>
                ${!jValid?'<div class="err-tooltip">⚠ klik perbaiki</div>':''}
            </td>
            <td class="text-center">${row.volume || 1}</td>
            <td style="font-size:10px;">${esc(row.satuan)||'Paket'}</td>
            <td class="text-end" style="font-size:10px;">${row.nilai_satuan > 0 ? formatRp(row.nilai_satuan) : '<span class="text-danger">—</span>'}</td>
            <td class="text-end fw-semibold text-primary" style="font-size:10px;">${subtotal > 0 ? formatRp(subtotal) : '—'}</td>
            <td class="text-center">
                ${isOk
                    ? '<span class="badge badge-ok">✓ OK</span>'
                    : `<button class="btn btn-outline-warning btn-sm py-0 px-1" style="font-size:10px;"
                               onclick="bukaModalEdit(${idx})">
                           <i class="bi bi-pencil me-1"></i>Perbaiki
                       </button>`
                }
            </td>
        `;
        tbody.appendChild(tr);

        if (!isOk) errMessages.push(`Baris ${idx+1} (${row.nama_kegiatan_item||'?'}): ${row.errors.join('; ')}`);
    });

    // Badge header
    const kontrakUnik = new Set(previewRows.filter(r=>r.errors.length===0).map(r=>r.nomor_kontrak||Math.random()));
    document.getElementById('previewBadge').innerHTML =
        `<span class="badge bg-primary me-1">${kontrakUnik.size} kontrak</span>` +
        `<span class="badge bg-success me-1">${okCount} baris OK</span>` +
        (errCount ? `<span class="badge bg-danger">${errCount} error</span>` : '');

    const btnImp = document.getElementById('btnImport');
    btnImp.disabled = okCount === 0;
    document.getElementById('btnImportCount').textContent = okCount > 0 ? okCount + ' data' : '';
    document.getElementById('editHintBanner').style.display = errCount > 0 ? '' : 'none';

    const errDiv = document.getElementById('errorSummary');
    if (errCount > 0) {
        errDiv.style.display = '';
        document.getElementById('errorList').innerHTML =
            `<strong>${errCount} baris perlu diperbaiki:</strong><br>` +
            errMessages.map(m => `<span class="text-danger">• ${m}</span>`).join('<br>');
    } else {
        errDiv.style.display = 'none';
    }
}

// ── Modal edit ────────────────────────────────────────────────────────────────
function bukaModalEdit(idx) {
    editingIdx = idx;
    const row  = previewRows[idx];
    document.getElementById('modalBarisNo').textContent  = 'Baris ' + (idx + 1);
    document.getElementById('modalErrMsg').innerHTML     =
        '<i class="bi bi-exclamation-triangle me-1"></i><strong>Error:</strong> ' + row.errors.join(' &bull; ');
    document.getElementById('editNoKontrak').value  = row.nomor_kontrak || '';
    document.getElementById('editVendor').value     = row.nama_vendor || '';
    document.getElementById('editNamaItem').value   = row.nama_kegiatan_item || '';
    document.getElementById('editJenis').value      = row.jenis_pengadaan || '';
    document.getElementById('editMetode').value     = row.metode_pengadaan || '';
    document.getElementById('editNilaiSat').value   = row.nilai_satuan || '';
    document.getElementById('editVolume').value     = row.volume || 1;
    document.getElementById('editSatuan').value     = row.satuan || 'Paket';
    document.getElementById('editStatus').value     = row.status || 'proses';
    document.getElementById('editTglMulai').value   = row.tanggal_mulai || '';
    document.getElementById('editTglSelesai').value = row.tanggal_selesai || '';
    document.getElementById('editCatatan').value    = row.catatan || '';
    new bootstrap.Modal(document.getElementById('modalEditBaris')).show();
}

document.getElementById('btnSimpanEdit').addEventListener('click', function () {
    if (editingIdx < 0) return;
    const row = previewRows[editingIdx];

    row.nomor_kontrak      = document.getElementById('editNoKontrak').value.trim();
    row.nama_vendor        = document.getElementById('editVendor').value.trim();
    row.nama_kegiatan_item = document.getElementById('editNamaItem').value.trim();
    row.jenis_pengadaan    = document.getElementById('editJenis').value;
    row.metode_pengadaan   = document.getElementById('editMetode').value;
    row.nilai_satuan       = parseFloat(document.getElementById('editNilaiSat').value) || 0;
    row.volume             = parseFloat(document.getElementById('editVolume').value) || 1;
    row.satuan             = document.getElementById('editSatuan').value.trim() || 'Paket';
    row.status             = document.getElementById('editStatus').value;
    row.tanggal_mulai      = document.getElementById('editTglMulai').value;
    row.tanggal_selesai    = document.getElementById('editTglSelesai').value;
    row.catatan            = document.getElementById('editCatatan').value.trim();
    row.errors             = validateRow(row);

    bootstrap.Modal.getInstance(document.getElementById('modalEditBaris')).hide();
    renderPreview();

    setTimeout(() => {
        const tr = document.querySelector(`#previewBody tr[data-idx="${editingIdx}"]`);
        if (tr) tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 150);
});

// ── Auto-fix ──────────────────────────────────────────────────────────────────
document.getElementById('btnFixAll').addEventListener('click', function () {
    let fixed = 0;
    previewRows.forEach(row => {
        if (row.errors.length === 0) return;
        let changed = false;

        // Tebak jenis dari nama kegiatan
        if (!VALID_JENIS.includes(row.jenis_pengadaan)) {
            const n = (row.nama_kegiatan_item || '').toLowerCase();
            let tebak = 'barang';
            if (n.match(/jasa|konsultan|pengawas|perencanaan|studi|dokumen|appraisal/))
                tebak = 'jasa_konsultansi';
            else if (n.match(/konstruksi|bangunan|sipil|pemasangan|pembangunan|perbaikan|pengeboran|bronjong|pembuatan|pagar|atap/))
                tebak = 'pekerjaan_konstruksi';
            else if (n.match(/leasing|sewa|pengurusan|biaya|asuransi|bphtb/))
                tebak = 'jasa_lainnya';
            row.jenis_pengadaan = tebak;
            changed = true;
        }

        // Default metode
        if (!VALID_METODE.includes(row.metode_pengadaan)) {
            row.metode_pengadaan = 'tender_terbatas';
            changed = true;
        }

        // Default status
        if (!VALID_STATUS.includes(row.status)) {
            row.status = 'proses';
            changed = true;
        }

        if (changed) {
            row.errors = validateRow(row);
            if (row.errors.length === 0) fixed++;
        }
    });

    renderPreview();
    if (fixed > 0) {
        const banner = document.createElement('div');
        banner.className = 'alert alert-success py-2 mx-3 mt-2';
        banner.style.fontSize = '12px';
        banner.innerHTML = `<i class="bi bi-check2-circle me-1"></i>
            <strong>${fixed} baris diperbaiki otomatis.</strong>
            Silakan periksa hasilnya sebelum import — khususnya kolom <strong>Jenis</strong> dan <strong>Metode</strong>.`;
        document.getElementById('errorSummary').after(banner);
        setTimeout(() => banner.remove(), 5000);
    }
});

// ── Import ────────────────────────────────────────────────────────────────────
document.getElementById('btnImport').addEventListener('click', function () {
    const toImport = previewRows.filter(r => r.errors.length === 0);
    if (toImport.length === 0) { alert('Tidak ada data valid.'); return; }

    const btnImp = this;
    btnImp.disabled = true;
    btnImp.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Mengimport...';

    const form = new FormData();
    form.append('json_data', JSON.stringify(toImport));
    form.append('format',    detectedFmt);

    fetch('import.php', { method: 'POST', body: form })
        .then(r => r.json())
        .then(res => {
            btnImp.disabled = false;
            btnImp.innerHTML = '<i class="bi bi-cloud-upload me-1"></i>Import Sekarang';
            const div = document.getElementById('importResult');
            div.style.display = 'block';

            if (res.status === 'success') {
                const errSisa = previewRows.filter(r => r.errors.length > 0).length;
                let html = `<div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>${res.imported} kontrak berhasil diimport.</strong>
                    ${res.skipped > 0 ? `${res.skipped} dilewati.` : ''}
                    ${res.vendor_baru > 0 ? `<span class="ms-2 badge bg-info text-dark">${res.vendor_baru} vendor baru ditambahkan</span>` : ''}
                    ${errSisa > 0 ? `<span class="ms-2 text-warning">${errSisa} baris error tidak diimport.</span>` : ''}
                    <a href="index.php" class="btn btn-success btn-sm ms-3">
                        <i class="bi bi-list me-1"></i>Lihat Realisasi
                    </a></div>`;
                if (res.errors && res.errors.length)
                    html += `<div class="alert alert-warning">
                        <strong>${res.errors.length} pesan:</strong>
                        <ul class="mb-0 mt-1">${res.errors.map(e => `<li>${e}</li>`).join('')}</ul></div>`;
                div.innerHTML = html;
            } else {
                div.innerHTML = `<div class="alert alert-danger">
                    <i class="bi bi-x-circle-fill me-2"></i>
                    <strong>Import gagal.</strong> ${res.message || ''}
                    ${(res.errors || []).length ? '<ul class="mt-2 mb-0">' + res.errors.map(e=>`<li>${e}</li>`).join('') + '</ul>' : ''}
                    </div>`;
            }
            div.scrollIntoView({ behavior: 'smooth' });
        })
        .catch(() => {
            btnImp.disabled = false;
            btnImp.innerHTML = '<i class="bi bi-cloud-upload me-1"></i>Import Sekarang';
            document.getElementById('importResult').innerHTML =
                '<div class="alert alert-danger">Terjadi kesalahan. Coba lagi.</div>';
            document.getElementById('importResult').style.display = 'block';
        });
});

// ── Reset ─────────────────────────────────────────────────────────────────────
document.getElementById('btnReset').addEventListener('click', () => {
    parsedData = []; previewRows = [];
    ['previewSection','importResult'].forEach(id => document.getElementById(id).style.display = 'none');
    document.getElementById('fileNameDisplay').style.display = 'none';
    dropZone.classList.remove('has-file');
    fileInput.value = '';
    document.getElementById('btnPreview').disabled = true;
});

// ── Helpers ───────────────────────────────────────────────────────────────────
function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function formatRp(v) { return 'Rp ' + Math.round(v||0).toLocaleString('id-ID'); }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>