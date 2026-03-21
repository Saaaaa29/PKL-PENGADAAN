<?php
/**
 * modules/realisasi/form.php
 */

session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
if ($isEdit) {
    requireManajer('index.php');
}
$realisasi = [];
$existingDetails = [];
$existingVendors = [];

if (!defined('LABEL_METODE')) {
    define('LABEL_METODE', [
        'pembelian_langsung'  => 'Pembelian Langsung (≤ Rp 15 Juta)',
        'tender_terbatas_spk' => 'Tender Terbatas SPK (> Rp 15 Juta - 50 Juta)',
        'tender_terbatas_pkp' => 'Tender Terbatas PKP (> Rp 50 Juta - 600 Juta)',
        'tender_umum'         => 'Tender Umum (> Rp 600 Juta)',
        'e_purchasing'        => 'E-Purchasing',
        'swakelola'           => 'Swakelola',
    ]);
}

if ($isEdit) {
    $q = $db->query("SELECT * FROM realisasi_kegiatan WHERE id = $id");
    $realisasi = $q->fetch_assoc();
    if (!$realisasi) {
        setFlash('error', 'Data tidak ditemukan.');
        header('Location: index.php');
        exit;
    }
    $qd = $db->query("SELECT * FROM realisasi_detail WHERE realisasi_id = $id ORDER BY id ASC");
    while ($d = $qd->fetch_assoc()) $existingDetails[] = $d;
    $existingVendors = getVendorByRealisasi($db, $id);
}

$rencanaList = $db->query("SELECT * FROM rencana_kegiatan ORDER BY tahun DESC, bulan_rencana ASC, id ASC");
$rencanaData = [];
while ($r = $rencanaList->fetch_assoc()) $rencanaData[] = $r;

// ── Ambil riwayat vendor yang pernah digunakan ─────────────────────────
// Ambil data terakhir per vendor (nomor kontrak, tgl, nilai dari transaksi terbaru)
$qVendorHistory = $db->query("
    SELECT
        v.nama_vendor,
        v.nomor_kontrak   AS last_nomor_kontrak,
        v.tanggal_kontrak AS last_tanggal_kontrak,
        v.nilai_kontrak   AS last_nilai_kontrak,
        COUNT(*)          AS frekuensi
    FROM realisasi_vendor v
    JOIN realisasi_kegiatan r ON r.id = v.realisasi_id
    WHERE r.status != 'batal'
    GROUP BY v.nama_vendor
    ORDER BY frekuensi DESC, v.nama_vendor ASC
");
$vendorHistory = [];
if ($qVendorHistory) {
    while ($vh = $qVendorHistory->fetch_assoc()) {
        $vendorHistory[] = $vh;
    }
}

function tentukanMetodeBaru($totalNilai) {
    if ($totalNilai <= 15000000)  return 'pembelian_langsung';
    if ($totalNilai <= 50000000)  return 'tender_terbatas_spk';
    if ($totalNilai <= 600000000) return 'tender_terbatas_pkp';
    return 'tender_umum';
}

// ── HANDLE POST ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomorKontrak = trim($_POST['nomor_kontrak'] ?? '');
    $tglMulai     = $_POST['tanggal_mulai'] ?? '';
    $tglSelesai   = $_POST['tanggal_selesai'] ?? null;
    $status       = $_POST['status'] ?? 'proses';
    $catatan      = trim($_POST['catatan'] ?? '');
    $items        = $_POST['items'] ?? [];

    $errors = [];
    if (!$tglMulai) $errors[] = 'Tanggal mulai wajib diisi.';
    if (empty($items)) $errors[] = 'Minimal satu item kegiatan harus ditambahkan.';

    $totalNilai = 0;
    $validItems = [];
    foreach ($items as $i => $item) {
        $namaItem = trim($item['nama_kegiatan'] ?? '');
        $vol      = (float)($item['volume'] ?? 0);
        $satuan   = trim($item['satuan'] ?? '');
        $nilSat   = (float)($item['nilai_satuan'] ?? 0);
        $jenisP   = $item['jenis_pengadaan'] ?? '';
        $rencId   = !empty($item['rencana_id']) ? (int)$item['rencana_id'] : null;
        $ket      = trim($item['keterangan'] ?? '');

        if (!$namaItem || $vol <= 0 || !$satuan || $nilSat <= 0) {
            $errors[] = "Item ke-" . ($i + 1) . ": Nama, volume, satuan, dan nilai satuan wajib diisi.";
            continue;
        }
        if (!array_key_exists($jenisP, LABEL_JENIS)) {
            $errors[] = "Item ke-" . ($i + 1) . ": Jenis pengadaan tidak valid.";
            continue;
        }

        $nilaiItem   = $vol * $nilSat;
        $totalNilai += $nilaiItem;
        $validItems[] = [
            'rencana_id'      => $rencId,
            'nama_kegiatan'   => $namaItem,
            'volume'          => $vol,
            'satuan'          => $satuan,
            'nilai_satuan'    => $nilSat,
            'nilai_anggaran'  => $nilaiItem,
            'jenis_pengadaan' => $jenisP,
            'keterangan'      => $ket,
        ];
    }

    $metode = $_POST['metode_pengadaan'] ?? tentukanMetodeBaru($totalNilai);

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $db->prepare("UPDATE realisasi_kegiatan SET nomor_kontrak=?, tanggal_mulai=?,
                tanggal_selesai=?, status=?, total_nilai=?, metode_pengadaan=?, catatan=? WHERE id=?");
            $tglSelesaiVal = $tglSelesai ?: null;
            $stmt->bind_param('ssssdssi', $nomorKontrak, $tglMulai, $tglSelesaiVal,
                $status, $totalNilai, $metode, $catatan, $id);
            $stmt->execute();
            $stmt->close();
            $db->query("DELETE FROM realisasi_detail WHERE realisasi_id = $id");
            $realId = $id;
        } else {
            $userId = $_SESSION['user_id'];
            $stmt = $db->prepare("INSERT INTO realisasi_kegiatan
                (nomor_kontrak, tanggal_mulai, tanggal_selesai, status, total_nilai, metode_pengadaan, catatan, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $tglSelesaiVal = $tglSelesai ?: null;
            $stmt->bind_param('ssssdssi', $nomorKontrak, $tglMulai, $tglSelesaiVal,
                $status, $totalNilai, $metode, $catatan, $userId);
            $stmt->execute();
            $realId = $stmt->insert_id;
            $stmt->close();
        }

        foreach ($validItems as $item) {
            $stmtD = $db->prepare("INSERT INTO realisasi_detail
                (realisasi_id, rencana_id, nama_kegiatan, volume, satuan,
                 nilai_satuan, nilai_anggaran, jenis_pengadaan, keterangan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtD->bind_param('iisdsdsss',
                $realId, $item['rencana_id'], $item['nama_kegiatan'],
                $item['volume'], $item['satuan'], $item['nilai_satuan'],
                $item['nilai_anggaran'], $item['jenis_pengadaan'], $item['keterangan']
            );
            $stmtD->execute();
            $stmtD->close();
        }

        $vendors = $_POST['vendor'] ?? [];
        saveVendors($db, $realId, $vendors);

        setFlash('success', 'Realisasi berhasil ' . ($isEdit ? 'diperbarui' : 'disimpan') . '.');
        header('Location: detail.php?id=' . $realId);
        exit;
    }
}

$pageTitle = ($isEdit ? 'Edit' : 'Tambah') . ' Realisasi Kegiatan';
include __DIR__ . '/../../includes/header.php';
?>

<style>
.rencana-item {
    border: 1px solid #dee2e6; border-radius: 8px;
    padding: 10px 12px; cursor: pointer;
    transition: all .15s ease; background: #fff; margin-bottom: 4px;
}
.rencana-item:hover    { border-color: #4361ee; background: #f0f4ff; }
.rencana-item.selected { border-color: #4361ee; background: #e8eeff; }
.detail-item-card {
    border: 1px solid #dee2e6; border-radius: 10px;
    padding: 16px; background: #fafafa;
}
.item-header {
    display: flex; justify-content: space-between;
    align-items: center; margin-bottom: 12px;
}
.vendor-card {
    background: #fafcff; transition: box-shadow .15s ease;
}
.vendor-card:hover { box-shadow: 0 2px 8px rgba(67,97,238,.1); }

/* ── Vendor Autocomplete ───────────────────── */
.vendor-autocomplete-wrap { position: relative; }
.vendor-suggestions {
    position: absolute; top: 100%; left: 0; right: 0; z-index: 1000;
    background: #fff; border: 1px solid #c7d2fe;
    border-top: none; border-radius: 0 0 8px 8px;
    max-height: 220px; overflow-y: auto;
    box-shadow: 0 4px 12px rgba(67,97,238,.12);
    display: none;
}
.vendor-suggestions.show { display: block; }
.vendor-suggestion-item {
    padding: 8px 12px; cursor: pointer;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: 8px;
}
.vendor-suggestion-item:last-child { border-bottom: none; }
.vendor-suggestion-item:hover { background: #eef2ff; }
.vs-name { font-size: .87rem; font-weight: 600; color: #1e293b; flex: 1; }
.vs-freq {
    font-size: .72rem; padding: 1px 7px; border-radius: 20px;
    background: #e0e7ff; color: #4361ee; white-space: nowrap;
}
.vs-empty { padding: 10px 12px; color: #94a3b8; font-size: .83rem; font-style: italic; }
</style>

<script>
const RENCANA_DATA   = <?= json_encode($rencanaData) ?>;
const LABEL_JENIS    = <?= json_encode(LABEL_JENIS) ?>;
// Riwayat vendor: array { nama_vendor, last_nomor_kontrak, last_tanggal_kontrak, last_nilai_kontrak, frekuensi }
const VENDOR_HISTORY = <?= json_encode($vendorHistory) ?>;
</script>

<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <a href="index.php" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
        <span><?= $isEdit ? 'Edit' : 'Tambah' ?> Realisasi Kegiatan</span>
    </div>
    <div class="card-body">

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= sanitize($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" id="formRealisasi">

            <!-- ══ HEADER INFORMASI ══════════════════════════════════════ -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Nomor Kontrak / SPK <span class="text-danger">*</span></label>
                    <input type="text" name="nomor_kontrak" class="form-control"
                           placeholder="Nomor/Jenis/AMGM/Tahun"
                           value="<?= sanitize($realisasi['nomor_kontrak'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_mulai" class="form-control" required
                           value="<?= $realisasi['tanggal_mulai'] ?? date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" class="form-control"
                           value="<?= $realisasi['tanggal_selesai'] ?? '' ?>">
                    <div class="form-text">Kosongkan untuk jenis Barang</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="proses"  <?= ($realisasi['status'] ?? 'proses') === 'proses'  ? 'selected' : '' ?>>Proses</option>
                        <option value="selesai" <?= ($realisasi['status'] ?? '') === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        <option value="batal"   <?= ($realisasi['status'] ?? '') === 'batal'   ? 'selected' : '' ?>>Batal</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2"
                              placeholder="Catatan tambahan (opsional)"><?= sanitize($realisasi['catatan'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- ══ METODE PENGADAAN ══════════════════════════════════════ -->
            <div class="alert alert-info py-2 d-flex align-items-center gap-3 mb-4">
                <i class="bi bi-info-circle-fill fs-5"></i>
                <div class="flex-grow-1">
                    <strong>Metode Pengadaan (berdasarkan total semua item):</strong>
                    <span class="ms-2" id="infoMetode">-</span>
                </div>
                <select name="metode_pengadaan" id="metodePengadaan" class="form-select form-select-sm" style="width:300px;">
                    <?php foreach (LABEL_METODE as $k => $v): ?>
                        <option value="<?= $k ?>" <?= ($realisasi['metode_pengadaan'] ?? '') === $k ? 'selected' : '' ?>>
                            <?= $v ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- ══ PILIH DARI RENCANA ════════════════════════════════════ -->
            <div class="card mb-4" style="border: 2px dashed #4361ee;">
                <div class="card-header bg-transparent">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold text-primary">
                            <i class="bi bi-journal-check me-1"></i>
                            Pilih dari Rencana Kegiatan
                            <span class="badge bg-primary ms-1" id="selectedCount">0 dipilih</span>
                        </span>
                        <button type="button" class="btn btn-sm btn-primary" id="btnTambahDariRencana">
                            <i class="bi bi-plus-circle me-1"></i>Tambahkan ke Daftar Item
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-sm-3">
                            <input type="text" id="searchRencana" class="form-control form-control-sm"
                                   placeholder="Cari nama kegiatan...">
                        </div>
                        <div class="col-sm-3">
                            <select id="filterTahunRencana" class="form-select form-select-sm">
                                <option value="">Semua Tahun</option>
                                <?php
                                $years = array_unique(array_column($rencanaData, 'tahun'));
                                rsort($years);
                                foreach ($years as $y): ?>
                                    <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <select id="filterJenisRencana" class="form-select form-select-sm">
                                <option value="">Semua Jenis</option>
                                <?php foreach (LABEL_JENIS as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetFilterRencana()">
                                <i class="bi bi-x-circle me-1"></i>Reset Filter
                            </button>
                        </div>
                    </div>
                    <div id="daftarRencana" style="max-height: 280px; overflow-y:auto;">
                        <div class="text-muted text-center py-3">Memuat data rencana...</div>
                    </div>
                </div>
            </div>

            <!-- ══ DAFTAR ITEM REALISASI ═════════════════════════════════ -->
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-list-check me-2 text-primary"></i>Daftar Item Realisasi
                </h6>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="tambahItemBaru()">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Item Baru (di luar rencana)
                </button>
            </div>

            <div id="containerItems">
                <div id="emptyItems" class="text-center py-4 text-muted border rounded-3">
                    <i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>
                    Belum ada item. Pilih dari rencana di atas atau tambah item baru.
                </div>
            </div>

            <div class="card bg-primary text-white mt-3 mb-4">
                <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Total Nilai Realisasi</span>
                    <span class="fs-5 fw-bold" id="totalNilaiDisplay">Rp 0</span>
                </div>
            </div>

            <!-- ══ SEKSI VENDOR ══════════════════════════════════════════ -->
            <div class="card mt-4 mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-building me-2"></i>Vendor / Penyedia
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnTambahVendor">
                        <i class="bi bi-plus-lg me-1"></i>Tambah Vendor
                    </button>
                </div>
                <div class="card-body">
                    <div id="vendorContainer">
                        <?php foreach ($existingVendors as $i => $v): ?>
                        <div class="vendor-card border rounded p-3 mb-3 position-relative">
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2 btn-hapus-vendor">
                                <i class="bi bi-trash"></i>
                            </button>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label form-label-sm fw-semibold">
                                        Nama Vendor <span class="text-danger">*</span>
                                    </label>
                                    <!-- Wrap dengan div untuk autocomplete -->
                                    <div class="vendor-autocomplete-wrap">
                                        <input type="text"
                                               name="vendor[<?= $i ?>][nama_vendor]"
                                               class="form-control form-control-sm vendor-nama"
                                               value="<?= sanitize($v['nama_vendor']) ?>"
                                               placeholder="PT / CV / Nama Perorangan"
                                               autocomplete="off">
                                        <div class="vendor-suggestions"></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label form-label-sm fw-semibold">Nomor Kontrak</label>
                                    <input type="text"
                                           name="vendor[<?= $i ?>][nomor_kontrak]"
                                           class="form-control form-control-sm vendor-nokont"
                                           value="<?= sanitize($v['nomor_kontrak']) ?>"
                                           placeholder="No. Kontrak">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label form-label-sm fw-semibold">Tgl. Kontrak</label>
                                    <input type="date"
                                           name="vendor[<?= $i ?>][tanggal_kontrak]"
                                           class="form-control form-control-sm vendor-tgl"
                                           value="<?= $v['tanggal_kontrak'] ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label form-label-sm fw-semibold">Nilai Kontrak (Rp)</label>
                                    <input type="number"
                                           name="vendor[<?= $i ?>][nilai_kontrak]"
                                           class="form-control form-control-sm vendor-nilai"
                                           value="<?= $v['nilai_kontrak'] ?>"
                                           placeholder="0" min="0" step="1">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div id="vendorEmptyState"
                         class="text-center py-3 text-muted"
                         style="<?= !empty($existingVendors) ? 'display:none' : '' ?>">
                        <i class="bi bi-building fs-3 d-block mb-1 opacity-50"></i>
                        <small>Belum ada vendor. Klik "Tambah Vendor" untuk menambahkan.</small>
                    </div>

                    <div class="d-flex justify-content-end mt-2 border-top pt-2">
                        <small class="text-muted me-2 align-self-center">Total Nilai Vendor:</small>
                        <strong class="text-primary" id="totalNilaiVendor">Rp 0</strong>
                    </div>
                </div>
            </div>

            <!-- ══ TOMBOL SUBMIT ═════════════════════════════════════════ -->
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i><?= $isEdit ? 'Perbarui' : 'Simpan Realisasi' ?>
                </button>
                <a href="index.php" class="btn btn-outline-secondary px-4">Batal</a>
            </div>

        </form>
    </div>
</div>

<!-- ══ TEMPLATE ITEM CARD ════════════════════════════════════════════════ -->
<template id="templateItem">
    <div class="detail-item-card mb-3" data-item-index="">
        <div class="item-header">
            <div class="d-flex align-items-center gap-2">
                <span class="badge badge-sumber bg-secondary text-white">Item</span>
                <span class="fw-semibold item-title">Item Baru</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-item">
                <i class="bi bi-trash"></i> Hapus
            </button>
        </div>
        <div class="row g-2">
            <input type="hidden" name="items[IDX][rencana_id]" class="field-rencana-id">
            <div class="col-12">
                <label class="form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                <input type="text" name="items[IDX][nama_kegiatan]"
                       class="form-control field-nama" placeholder="Nama kegiatan" required>
            </div>
            <div class="col-sm-3">
                <label class="form-label">Volume <span class="text-danger">*</span></label>
                <input type="number" name="items[IDX][volume]"
                       class="form-control field-volume" min="0.01" step="0.01" required>
            </div>
            <div class="col-sm-3">
                <label class="form-label">Satuan <span class="text-danger">*</span></label>
                <input type="text" name="items[IDX][satuan]"
                       class="form-control field-satuan" placeholder="unit/paket/m²" required>
            </div>
            <div class="col-sm-3">
                <label class="form-label">Nilai Satuan (Rp) <span class="text-danger">*</span></label>
                <input type="number" name="items[IDX][nilai_satuan]"
                       class="form-control field-nilai-satuan" min="0" step="1" required>
            </div>
            <div class="col-sm-3">
                <label class="form-label">Nilai (auto)</label>
                <div class="form-control bg-light fw-semibold text-primary field-nilai-display">Rp 0</div>
                <input type="hidden" name="items[IDX][nilai_anggaran]" class="field-nilai-anggaran">
            </div>
            <div class="col-sm-6">
                <label class="form-label">Jenis Pengadaan <span class="text-danger">*</span></label>
                <select name="items[IDX][jenis_pengadaan]" class="form-select field-jenis" required>
                    <option value="">-- Pilih --</option>
                    <?php foreach (LABEL_JENIS as $k => $v): ?>
                        <option value="<?= $k ?>"><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6">
                <label class="form-label">Keterangan</label>
                <input type="text" name="items[IDX][keterangan]"
                       class="form-control field-keterangan" placeholder="Opsional">
            </div>
        </div>
    </div>
</template>

<!-- ══ TEMPLATE VENDOR CARD ═════════════════════════════════════════════ -->
<template id="templateVendor">
    <div class="vendor-card border rounded p-3 mb-3 position-relative">
        <button type="button"
                class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2 btn-hapus-vendor">
            <i class="bi bi-trash"></i>
        </button>
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label form-label-sm fw-semibold">
                    Nama Vendor <span class="text-danger">*</span>
                </label>
                <div class="vendor-autocomplete-wrap">
                    <input type="text"
                           name="vendor[__IDX__][nama_vendor]"
                           class="form-control form-control-sm vendor-nama"
                           placeholder="PT / CV / Nama Perorangan"
                           autocomplete="off">
                    <div class="vendor-suggestions"></div>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label form-label-sm fw-semibold">Nomor Kontrak</label>
                <input type="text"
                       name="vendor[__IDX__][nomor_kontrak]"
                       class="form-control form-control-sm vendor-nokont"
                       placeholder="No. Kontrak">
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm fw-semibold">Tgl. Kontrak</label>
                <input type="date"
                       name="vendor[__IDX__][tanggal_kontrak]"
                       class="form-control form-control-sm vendor-tgl">
            </div>
            <div class="col-md-3">
                <label class="form-label form-label-sm fw-semibold">Nilai Kontrak (Rp)</label>
                <input type="number"
                       name="vendor[__IDX__][nilai_kontrak]"
                       class="form-control form-control-sm vendor-nilai"
                       placeholder="0" min="0" step="1">
            </div>
        </div>
    </div>
</template>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script>
/* ══════════════════════════════════════════════════════════════════
   VARIABEL GLOBAL
══════════════════════════════════════════════════════════════════ */
let itemIndex        = 0;
let vendorIndex      = <?= count($existingVendors) ?>;
let selectedRencanaIds = new Set();

const NAMA_BULAN = ['?','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
const LABEL_METODE_JS = {
    'pembelian_langsung':  'Pembelian Langsung (≤ Rp 15 Juta)',
    'tender_terbatas_spk': 'Tender Terbatas SPK (> Rp 15 Juta - 50 Juta)',
    'tender_terbatas_pkp': 'Tender Terbatas PKP (> Rp 50 Juta - 600 Juta)',
    'tender_umum':         'Tender Umum (> Rp 600 Juta)',
    'e_purchasing':        'E-Purchasing',
    'swakelola':           'Swakelola',
};

/* ══════════════════════════════════════════════════════════════════
   VENDOR AUTOCOMPLETE
   - Ketik min 1 karakter → muncul dropdown suggestion
   - Klik suggestion → isi nama saja; field lain tetap kosong/editable
   - Klik di luar → tutup dropdown
══════════════════════════════════════════════════════════════════ */
function pasangAutocompleteVendor(card) {
    const namaInput  = card.querySelector('.vendor-nama');
    const suggestBox = card.querySelector('.vendor-suggestions');
    if (!namaInput || !suggestBox) return;

    let activeIdx = -1;

    function renderSuggestions(query) {
        const q = query.trim().toLowerCase();
        if (!q) { suggestBox.classList.remove('show'); return; }

        const matched = VENDOR_HISTORY.filter(v =>
            v.nama_vendor.toLowerCase().includes(q)
        ).slice(0, 8); // max 8 suggestions

        if (matched.length === 0) {
            suggestBox.innerHTML = '<div class="vs-empty">Tidak ada vendor yang cocok</div>';
        } else {
            suggestBox.innerHTML = matched.map((v, i) => `
                <div class="vendor-suggestion-item" data-idx="${i}"
                     data-nama="${escHtml(v.nama_vendor)}">
                    <i class="bi bi-building text-primary" style="font-size:.8rem;flex-shrink:0;"></i>
                    <span class="vs-name">${highlight(v.nama_vendor, q)}</span>
                    <span class="vs-freq">${v.frekuensi}× pakai</span>
                </div>
            `).join('');

            // Klik suggestion
            suggestBox.querySelectorAll('.vendor-suggestion-item').forEach((item, i) => {
                item.addEventListener('mousedown', function(e) {
                    e.preventDefault(); // cegah blur dulu
                    pilihSuggestion(matched[i]);
                });
            });
        }

        suggestBox.classList.add('show');
        activeIdx = -1;
    }

    function pilihSuggestion(vendor) {
        // Isi nama — field lain dikosongkan agar user isi manual (kontrak baru)
        namaInput.value = vendor.nama_vendor;
        suggestBox.classList.remove('show');
        namaInput.focus();
    }

    function escHtml(s) {
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    function highlight(text, q) {
        const re = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&') + ')', 'gi');
        return escHtml(text).replace(re, '<mark style="background:#fde68a;padding:0;">$1</mark>');
    }

    namaInput.addEventListener('input', function() {
        renderSuggestions(this.value);
    });

    namaInput.addEventListener('focus', function() {
        if (this.value.trim()) renderSuggestions(this.value);
    });

    namaInput.addEventListener('blur', function() {
        // Delay agar klik pada item masih bisa terpicu
        setTimeout(() => suggestBox.classList.remove('show'), 150);
    });

    // Navigasi keyboard (opsional: panah atas/bawah + Enter)
    namaInput.addEventListener('keydown', function(e) {
        const items = suggestBox.querySelectorAll('.vendor-suggestion-item');
        if (!items.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIdx = Math.min(activeIdx + 1, items.length - 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIdx = Math.max(activeIdx - 1, 0);
        } else if (e.key === 'Enter' && activeIdx >= 0) {
            e.preventDefault();
            items[activeIdx].dispatchEvent(new MouseEvent('mousedown'));
            return;
        } else if (e.key === 'Escape') {
            suggestBox.classList.remove('show');
            return;
        }
        items.forEach((el, i) => el.style.background = i === activeIdx ? '#eef2ff' : '');
    });
}

/* ══════════════════════════════════════════════════════════════════
   METODE PENGADAAN
══════════════════════════════════════════════════════════════════ */
function tentukanMetode(total) {
    if (total <= 15000000)  return 'pembelian_langsung';
    if (total <= 50000000)  return 'tender_terbatas_spk';
    if (total <= 600000000) return 'tender_terbatas_pkp';
    return 'tender_umum';
}
function autoUpdateMetode() {
    const total   = updateTotalNilai();
    const metodeEl = document.getElementById('metodePengadaan');
    const infoEl   = document.getElementById('infoMetode');
    const current  = metodeEl.value;
    if (current !== 'e_purchasing' && current !== 'swakelola') {
        const suggested = tentukanMetode(total);
        metodeEl.value  = suggested;
        infoEl.textContent = LABEL_METODE_JS[suggested] || suggested;
    } else {
        infoEl.textContent = LABEL_METODE_JS[current] || current;
    }
}
document.getElementById('metodePengadaan').addEventListener('change', function() {
    document.getElementById('infoMetode').textContent = LABEL_METODE_JS[this.value] || this.value;
});

/* ══════════════════════════════════════════════════════════════════
   TOTAL NILAI ITEM
══════════════════════════════════════════════════════════════════ */
function updateTotalNilai() {
    let total = 0;
    document.querySelectorAll('.field-nilai-anggaran').forEach(el => {
        total += parseFloat(el.value) || 0;
    });
    document.getElementById('totalNilaiDisplay').textContent =
        'Rp ' + total.toLocaleString('id-ID');
    return total;
}

/* ══════════════════════════════════════════════════════════════════
   DAFTAR RENCANA
══════════════════════════════════════════════════════════════════ */
function renderDaftarRencana() {
    const search    = document.getElementById('searchRencana').value.toLowerCase();
    const tahun     = document.getElementById('filterTahunRencana').value;
    const jenis     = document.getElementById('filterJenisRencana').value;
    const container = document.getElementById('daftarRencana');
    const filtered  = RENCANA_DATA.filter(r => {
        return (!search || r.nama_kegiatan.toLowerCase().includes(search))
            && (!tahun  || String(r.tahun) === String(tahun))
            && (!jenis  || r.jenis_pengadaan === jenis);
    });
    if (filtered.length === 0) {
        container.innerHTML = '<div class="text-muted text-center py-3">Tidak ada rencana yang sesuai filter</div>';
        return;
    }
    let html = '<div class="row g-2">';
    filtered.forEach(r => {
        const isSelected  = selectedRencanaIds.has(parseInt(r.id));
        const bulanIdx    = parseInt(r.bulan_rencana);
        const bulanNama   = (bulanIdx >= 1 && bulanIdx <= 12) ? NAMA_BULAN[bulanIdx] : '?';
        const nilaiFormat = 'Rp ' + (parseFloat(r.nilai_anggaran) || 0).toLocaleString('id-ID');
        html += `<div class="col-md-6">
            <div class="rencana-item ${isSelected ? 'selected' : ''}"
                 onclick="toggleRencana(this, ${parseInt(r.id)})" data-id="${parseInt(r.id)}">
                <input type="checkbox" class="rencana-checkbox" data-id="${r.id}"
                       ${isSelected ? 'checked' : ''} style="display:none">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="fw-semibold" style="font-size:13px;">${isSelected ? '✓ ' : ''}${r.nama_kegiatan}</div>
                    <span class="badge bg-light text-dark border ms-2" style="font-size:10px;">${bulanNama} ${r.tahun || '-'}</span>
                </div>
                <div class="d-flex gap-2 mt-1 flex-wrap" style="font-size:11px;">
                    <span class="text-muted">${r.volume || 0} ${r.satuan || ''}</span>
                    <span class="text-primary fw-semibold">${nilaiFormat}</span>
                    <span class="badge bg-secondary" style="font-size:10px;">
                        ${LABEL_JENIS[r.jenis_pengadaan] || r.jenis_pengadaan || '-'}
                    </span>
                </div>
            </div>
        </div>`;
    });
    html += '</div>';
    container.innerHTML = html;
    updateSelectedCount();
}

function toggleRencana(el, rencanaId) {
    rencanaId = parseInt(rencanaId);
    if (selectedRencanaIds.has(rencanaId)) {
        selectedRencanaIds.delete(rencanaId);
        el.classList.remove('selected');
        el.querySelector('input').checked = false;
    } else {
        selectedRencanaIds.add(rencanaId);
        el.classList.add('selected');
        el.querySelector('input').checked = true;
    }
    updateSelectedCount();
}
function updateSelectedCount() {
    document.getElementById('selectedCount').textContent = selectedRencanaIds.size + ' dipilih';
}
function resetFilterRencana() {
    document.getElementById('searchRencana').value = '';
    document.getElementById('filterTahunRencana').value = String(new Date().getFullYear());
    document.getElementById('filterJenisRencana').value = '';
    renderDaftarRencana();
}

document.getElementById('btnTambahDariRencana').addEventListener('click', function() {
    if (selectedRencanaIds.size === 0) { alert('Pilih minimal satu rencana kegiatan.'); return; }
    selectedRencanaIds.forEach(rencanaId => {
        const r = RENCANA_DATA.find(r => parseInt(r.id) === parseInt(rencanaId));
        if (r) addItemCard({ rencana_id: r.id, nama_kegiatan: r.nama_kegiatan,
            volume: r.volume, satuan: r.satuan, nilai_satuan: r.nilai_satuan,
            nilai_anggaran: r.nilai_anggaran, jenis_pengadaan: r.jenis_pengadaan, sumber: 'rencana' });
    });
    selectedRencanaIds.clear();
    renderDaftarRencana();
});
document.getElementById('searchRencana').addEventListener('input', renderDaftarRencana);
document.getElementById('filterTahunRencana').addEventListener('change', renderDaftarRencana);
document.getElementById('filterJenisRencana').addEventListener('change', renderDaftarRencana);

/* ══════════════════════════════════════════════════════════════════
   ITEM CARD
══════════════════════════════════════════════════════════════════ */
function tambahItemBaru() { addItemCard({ sumber: 'baru' }); }

function addItemCard(data) {
    const template = document.getElementById('templateItem');
    const clone    = template.content.cloneNode(true);
    const card     = clone.querySelector('.detail-item-card');
    const idx      = itemIndex++;
    const emptyEl  = document.getElementById('emptyItems');
    if (emptyEl) emptyEl.remove();

    card.querySelectorAll('[name]').forEach(el => { el.name = el.name.replace('IDX', idx); });
    card.setAttribute('data-item-index', idx);
    card.querySelector('.field-rencana-id').value  = data.rencana_id    || '';
    card.querySelector('.field-nama').value         = data.nama_kegiatan || '';
    card.querySelector('.field-volume').value       = data.volume        || '';
    card.querySelector('.field-satuan').value       = data.satuan        || '';
    card.querySelector('.field-nilai-satuan').value = data.nilai_satuan  || '';
    card.querySelector('.field-keterangan').value   = data.keterangan    || '';

    const jenisSelect = card.querySelector('.field-jenis');
    if (data.jenis_pengadaan) jenisSelect.value = data.jenis_pengadaan;

    const badge = card.querySelector('.badge-sumber');
    if (data.sumber === 'rencana') { badge.textContent = 'Dari Rencana'; badge.className = 'badge badge-sumber bg-primary text-white'; }
    else { badge.textContent = 'Item Baru'; badge.className = 'badge badge-sumber bg-warning text-dark'; }

    card.querySelector('.item-title').textContent = data.nama_kegiatan || 'Item Baru';
    const nilai = (parseFloat(data.volume) || 0) * (parseFloat(data.nilai_satuan) || 0);
    card.querySelector('.field-nilai-display').textContent = 'Rp ' + nilai.toLocaleString('id-ID');
    card.querySelector('.field-nilai-anggaran').value = nilai;

    const volEl  = card.querySelector('.field-volume');
    const satEl  = card.querySelector('.field-nilai-satuan');
    const namaEl = card.querySelector('.field-nama');

    function hitungNilaiItem() {
        const n = (parseFloat(volEl.value) || 0) * (parseFloat(satEl.value) || 0);
        card.querySelector('.field-nilai-display').textContent = 'Rp ' + n.toLocaleString('id-ID');
        card.querySelector('.field-nilai-anggaran').value = n;
        updateTotalNilai(); autoUpdateMetode();
    }
    namaEl.addEventListener('input', function() { card.querySelector('.item-title').textContent = this.value || 'Item Baru'; });
    volEl.addEventListener('input', hitungNilaiItem);
    satEl.addEventListener('input', hitungNilaiItem);

    card.querySelector('.btn-hapus-item').addEventListener('click', function() {
        card.remove(); updateTotalNilai(); autoUpdateMetode();
        if (document.getElementById('containerItems').children.length === 0) {
            document.getElementById('containerItems').innerHTML =
                '<div id="emptyItems" class="text-center py-4 text-muted border rounded-3">' +
                '<i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>' +
                'Belum ada item. Pilih dari rencana di atas atau tambah item baru.</div>';
        }
    });
    document.getElementById('containerItems').appendChild(clone);
    updateTotalNilai(); autoUpdateMetode();
}

/* ══════════════════════════════════════════════════════════════════
   VENDOR
══════════════════════════════════════════════════════════════════ */
function updateVendorEmptyState() {
    const cards   = document.querySelectorAll('#vendorContainer .vendor-card');
    const emptyEl = document.getElementById('vendorEmptyState');
    if (emptyEl) emptyEl.style.display = cards.length === 0 ? '' : 'none';
}
function hitungTotalVendor() {
    let total = 0;
    document.querySelectorAll('.vendor-nilai').forEach(el => { total += parseFloat(el.value) || 0; });
    const el = document.getElementById('totalNilaiVendor');
    if (el) el.textContent = 'Rp ' + total.toLocaleString('id-ID');
}

function buatKartuVendor(idx) {
    const tmpl  = document.getElementById('templateVendor');
    const clone = tmpl.content.cloneNode(true);
    const card  = clone.querySelector('.vendor-card');
    card.innerHTML = card.innerHTML.replaceAll('__IDX__', idx);

    card.querySelector('.btn-hapus-vendor').addEventListener('click', function() {
        card.remove(); updateVendorEmptyState(); hitungTotalVendor();
    });
    card.querySelector('.vendor-nilai').addEventListener('input', hitungTotalVendor);

    // Pasang autocomplete setelah DOM ready
    pasangAutocompleteVendor(card);
    return card;
}

document.getElementById('btnTambahVendor').addEventListener('click', function() {
    const card = buatKartuVendor(vendorIndex++);
    document.getElementById('vendorContainer').appendChild(card);
    updateVendorEmptyState();
    card.querySelector('.vendor-nama').focus();
});

// Pasang event & autocomplete untuk vendor existing (mode edit)
document.querySelectorAll('#vendorContainer .vendor-card').forEach(function(card) {
    card.querySelector('.btn-hapus-vendor').addEventListener('click', function() {
        card.remove(); updateVendorEmptyState(); hitungTotalVendor();
    });
    const nilaiEl = card.querySelector('.vendor-nilai');
    if (nilaiEl) nilaiEl.addEventListener('input', hitungTotalVendor);
    pasangAutocompleteVendor(card); // autocomplete untuk vendor yang sudah ada
});

/* ══════════════════════════════════════════════════════════════════
   INIT
══════════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function() {
    renderDaftarRencana();

    const existingItems = <?= json_encode($existingDetails) ?>;
    existingItems.forEach(item => {
        addItemCard({
            rencana_id: item.rencana_id, nama_kegiatan: item.nama_kegiatan,
            volume: item.volume, satuan: item.satuan,
            nilai_satuan: item.nilai_satuan, nilai_anggaran: item.nilai_anggaran,
            jenis_pengadaan: item.jenis_pengadaan, keterangan: item.keterangan,
            sumber: item.rencana_id ? 'rencana' : 'baru',
        });
    });

    updateTotalNilai(); autoUpdateMetode();
    hitungTotalVendor(); updateVendorEmptyState();
});
</script>