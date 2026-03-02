<?php
/**
 * modules/realisasi/index.php
 */

session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db = getDB();

// -------------------------------------------------------
// SIMPAN FILTER KE SESSION — persisten HANYA saat
// kembali dari halaman dalam modul realisasi
// (form, detail, hapus). Reset otomatis jika datang
// dari luar modul.
// -------------------------------------------------------

// Deteksi: apakah user datang dari dalam modul realisasi?
$referer        = $_SERVER['HTTP_REFERER'] ?? '';
$baseUrl        = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$realisasiPath  = rtrim($baseUrl, '/') . '/modules/realisasi/';
$dariDalamModul = strpos($referer, $realisasiPath) !== false;

if (isset($_GET['_reset'])) {
    // Tombol Reset diklik — hapus filter
    unset($_SESSION['filter_realisasi']);

} elseif (isset($_GET['tahun']) || isset($_GET['status'])) {
    // User submit form filter — simpan ke session
    $_SESSION['filter_realisasi'] = [
        'tahun'  => isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y'),
        'status' => $_GET['status'] ?? '',
    ];

} elseif (!$dariDalamModul) {
    // Datang dari luar modul (dashboard, menu, dll)
    // → reset session filter supaya tampilan segar
    unset($_SESSION['filter_realisasi']);
}

// Ambil filter dari session, fallback ke default
$filterSaved = $_SESSION['filter_realisasi'] ?? [];
$tahun  = $filterSaved['tahun']  ?? (int)date('Y');
$status = $filterSaved['status'] ?? '';

// -------------------------------------------------------
// QUERY
// -------------------------------------------------------
$where = ["YEAR(r.tanggal_mulai) = $tahun"];
if ($status) $where[] = "r.status = '" . $db->real_escape_string($status) . "'";
$whereStr = 'WHERE ' . implode(' AND ', $where);

$result = $db->query("
    SELECT r.*, u.nama_lengkap,
        (SELECT COUNT(*) FROM realisasi_detail WHERE realisasi_id = r.id) as jumlah_item,
        (SELECT GROUP_CONCAT(nama_kegiatan ORDER BY id ASC SEPARATOR ', ')
         FROM realisasi_detail WHERE realisasi_id = r.id) as nama_kegiatan_list
    FROM realisasi_kegiatan r
    LEFT JOIN users u ON u.id = r.created_by
    $whereStr
    ORDER BY r.tanggal_mulai DESC
");
if ($result === false) {
    die('<div class="alert alert-danger m-4"><strong>Query Error:</strong> ' . htmlspecialchars($db->error) . '</div>');
}

$rows = [];
while ($row = $result->fetch_assoc()) $rows[] = $row;

$totalQ         = $db->query("SELECT SUM(r.total_nilai) as total FROM realisasi_kegiatan r $whereStr");
$totalRealisasi = ($totalQ !== false) ? ($totalQ->fetch_assoc()['total'] ?? 0) : 0;

$pageTitle = 'Realisasi Kegiatan';
include __DIR__ . '/../../includes/header.php';
?>

<style>
#tabelRealisasi thead th {
    background: #f8fafc; font-size: .75rem; text-transform: uppercase;
    letter-spacing: .04em; color: #64748b; border-bottom: 2px solid #e2e8f0; white-space: nowrap;
}
#tabelRealisasi td { vertical-align: middle; font-size: .875rem; }
#tabelRealisasi tbody tr:hover td { background: #f8faff; }

.dataTables_filter { display: none !important; }
.dataTables_length { display: none !important; }

.dt-entries-wrapper {
    display: flex; align-items: center; gap: 6px;
    font-size: .82rem; color: #64748b; white-space: nowrap;
}
.dt-entries-wrapper select { width: 70px; }

.dt-search-wrapper {
    position: relative; flex: 1; min-width: 180px; max-width: 260px;
}
.dt-search-wrapper .bi-search {
    position: absolute; left: 9px; top: 50%; transform: translateY(-50%);
    color: #94a3b8; font-size: .85rem; pointer-events: none;
}
.dt-search-wrapper input { padding-left: 28px; }

.dataTables_info { font-size: .8rem; color: #64748b; }
.dataTables_paginate .paginate_button { font-size: .8rem !important; padding: 3px 8px !important; }

.kegiatan-list {
    display: -webkit-box; -webkit-line-clamp: 2;
    -webkit-box-orient: vertical; overflow: hidden;
    font-size: .8rem; color: #475569; max-width: 260px;
}

/* Badge filter aktif */
.filter-active-badge {
    font-size: .72rem;
    background: #eef2ff;
    color: #4361ee;
    border: 1px solid #c7d2fe;
    border-radius: 20px;
    padding: 2px 10px;
    white-space: nowrap;
}
/* Warna custom badge metode pengadaan */
.bg-purple { background-color: #7c3aed !important; }
.badge.bg-purple { color: white !important; }
</style>

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0">Realisasi Kegiatan Pengadaan</h5>
        <small class="text-muted">
            <?= count($rows) ?> realisasi &bull; Total: <strong><?= formatRupiah($totalRealisasi) ?></strong>
        </small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= BASE_URL ?>/modules/realisasi/form.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Tambah Realisasi
        </a>
        <a href="<?= BASE_URL ?>/modules/realisasi/print.php?tahun=<?= $tahun ?>&status=<?= urlencode($status) ?>"
           target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i>Cetak
        </a>
        <a href="<?= BASE_URL ?>/modules/realisasi/export.php?tahun=<?= $tahun ?>&status=<?= urlencode($status) ?>"
           class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i>Excel
        </a>
    </div>
</div>

<!-- FILTER CARD -->
<div class="card mb-3 no-print">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">

            <!-- Entries -->
            <div class="dt-entries-wrapper">
                <span>Tampilkan</span>
                <select id="dtLengthCustom" class="form-select form-select-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>entri</span>
            </div>

            <div class="vr mx-1"></div>
            
            <!-- Filter form -->
            <form method="GET" class="d-flex align-items-center gap-2 flex-wrap mb-0">
                <label class="fw-semibold text-muted mb-0" style="font-size:.8rem;white-space:nowrap">Filter:</label>
                <input type="number" name="tahun"
                       value="<?= $tahun ?>"
                       min="2000"
                       class="form-control form-control-sm text-center"
                       style="width:82px;"
                       placeholder="Tahun">
                <select name="status" class="form-select form-select-sm" style="width:130px;">
                    <option value="">Semua Status</option>
                    <option value="proses"  <?= $status === 'proses'  ? 'selected' : '' ?>>Proses</option>
                    <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                    <option value="batal"   <?= $status === 'batal'   ? 'selected' : '' ?>>Batal</option>
                </select>

                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="?_reset=1" class="btn btn-sm btn-outline-secondary">
                    <i class="bi me-1"></i>Reset
                </a>
            </form>

            <!-- Search -->
            <div class="ms-auto dt-search-wrapper">
                <i class="bi bi-search"></i>
                <input type="text" id="dtSearchCustom" class="form-control form-control-sm"
                       placeholder="Cari realisasi..." autocomplete="off">
            </div>

        </div>
    </div>
</div>

<!-- TABEL -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tabelRealisasi">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:40px;">No.</th>
                        <th>No. Kontrak</th>
                        <th>Nama Kegiatan</th>
                        <th>Metode</th>
                        <th class="text-center">Tgl Mulai</th>
                        <th class="text-center">Tgl Selesai</th>
                        <th class="text-end">Total Nilai</th>
                        <th class="text-center">Status</th>
                        <th class="text-center no-print" style="width:100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $metodeColors = [
                    'pembelian_langsung'  => 'success',
                    'tender_terbatas_spk' => 'info',
                    'tender_terbatas_pkp' => 'purple',
                    'tender_terbatas'     => 'info',
                    'tender_umum'         => 'danger',
                    'e_purchasing'        => 'warning',
                    'swakelola'           => 'secondary',
                ];
                $statusMap = [
                    'proses'  => 'bg-warning text-dark',
                    'selesai' => 'bg-success',
                    'batal'   => 'bg-danger',
                ];
                foreach ($rows as $no => $row):
                    $mc          = $metodeColors[$row['metode_pengadaan']] ?? 'secondary';
                    $sc          = $statusMap[$row['status']] ?? 'bg-secondary';
                    $kegiatanArr = $row['nama_kegiatan_list'] ? explode(', ', $row['nama_kegiatan_list']) : [];
                ?>
                <tr>
                    <td class="ps-3 text-muted"><?= $no + 1 ?></td>
                    <td>
                        <a href="detail.php?id=<?= $row['id'] ?>"
                           class="fw-semibold text-decoration-none text-dark">
                            <?= sanitize($row['nomor_kontrak'] ?: '-') ?>
                        </a>
                        <?php if (!empty($row['catatan'])): ?>
                            <br><small class="text-muted"><?= sanitize(mb_substr($row['catatan'], 0, 40)) ?>…</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($kegiatanArr)): ?>
                            <div class="kegiatan-list">
                                <?php foreach ($kegiatanArr as $i => $kg): ?>
                                    <span class="d-block">
                                        <?php if (count($kegiatanArr) > 1): ?>
                                            <span class="text-muted me-1" style="font-size:.7rem;"><?= $i+1 ?>.</span>
                                        <?php endif; ?>
                                        <?= sanitize(trim($kg)) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($kegiatanArr) > 1): ?>
                                <small class="text-primary"><?= count($kegiatanArr) ?> item</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $mc ?>" style="font-size:.73rem;">
                            <?= getLabelMetode($row['metode_pengadaan']) ?>
                        </span>
                    </td>
                    <td class="text-center text-muted">
                        <?= date('d/m/Y', strtotime($row['tanggal_mulai'])) ?>
                    </td>
                    <td class="text-center text-muted">
                        <?= $row['tanggal_selesai']
                            ? date('d/m/Y', strtotime($row['tanggal_selesai']))
                            : '<span class="text-muted">-</span>' ?>
                    </td>
                    <td class="text-end fw-semibold text-primary">
                        <?= formatRupiah($row['total_nilai']) ?>
                    </td>
                    <td class="text-center">
                        <span class="badge <?= $sc ?>"><?= ucfirst($row['status']) ?></span>
                    </td>
                    <td class="text-center no-print">
                        <div class="btn-group btn-group-sm">
                            <a href="detail.php?id=<?= $row['id'] ?>"
                               class="btn btn-outline-info" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="form.php?id=<?= $row['id'] ?>"
                               class="btn btn-outline-primary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button onclick="konfirmasiHapus('hapus.php?id=<?= $row['id'] ?>','Realisasi <?= addslashes($row['nomor_kontrak'] ?: '#'.$row['id']) ?>')"
                                    class="btn btn-outline-danger" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Info & Paginasi -->
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
            <div id="dtInfoCustom" class="dataTables_info"></div>
            <div id="dtPaginateCustom"></div>
        </div>
    </div>
</div>

<?php
$extraJS = '<script>
$(document).ready(function() {
    var table = $("#tabelRealisasi").DataTable({
        dom: "rt",
        pageLength: 25,
        order: [[4, "desc"]],
        columnDefs: [
            { orderable: false, targets: [0, 2, 7, 8] }
        ],
        rowCallback: function(row, data, displayIndex) {
            var pageInfo = this.api().page.info();
            $("td:first", row).html(
                "<span class=\"text-muted\">" + (pageInfo.start + displayIndex + 1) + "</span>"
            );
        },
        language: {
            emptyTable:  "<div class=\"text-center py-4 text-muted\"><i class=\"bi bi-inbox fs-2 d-block mb-2\"></i>Tidak ada data realisasi kegiatan</div>",
            zeroRecords: "<div class=\"text-center py-4 text-muted\"><i class=\"bi bi-search fs-2 d-block mb-2\"></i>Tidak ada data yang cocok</div>",
            info:      "Menampilkan _START_–_END_ dari _TOTAL_ entri",
            infoEmpty: "Tidak ada data",
            paginate: {
                previous: "<i class=\"bi bi-chevron-left\"></i>",
                next:     "<i class=\"bi bi-chevron-right\"></i>"
            }
        }
    });

    function updateControls() {
        var info = table.page.info();
        if (info.recordsTotal === 0) {
            $("#dtInfoCustom").text("Tidak ada data");
        } else if (info.recordsDisplay === 0) {
            $("#dtInfoCustom").text("Tidak ada data yang cocok");
        } else {
            var txt = "Menampilkan " + (info.start + 1) + "–" + info.end + " dari " + info.recordsDisplay + " entri";
            if (info.recordsDisplay !== info.recordsTotal) {
                txt += " (dari " + info.recordsTotal + " total)";
            }
            $("#dtInfoCustom").text(txt);
        }
        var $pag = $(table.table().container()).find(".dataTables_paginate");
        $("#dtPaginateCustom").empty().append($pag.clone(true, true));
    }

    table.on("draw", updateControls).draw();

    $("#dtSearchCustom").on("keypress", function(e) {
        if (e.key === "Enter") e.preventDefault();
    }).on("keyup", function() {
        table.search(this.value).draw();
    });

    $("#dtLengthCustom").on("change", function() {
        table.page.len(parseInt($(this).val())).draw();
    });
});
</script>';

include __DIR__ . '/../../includes/footer.php';
?>