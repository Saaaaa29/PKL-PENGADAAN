<?php
/**
 * modules/realisasi/index.php
 * Daftar realisasi — role-aware (staf: input+lihat, manajer: semua)
 */
session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db     = getDB();
$tahun  = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$status = $_GET['status'] ?? '';
$verif  = $_GET['verif']  ?? '';

$role   = $_SESSION['user_role'] ?? 'staf_pengadaan';
$uid    = (int)($_SESSION['user_id'] ?? 0);
$isMgr  = in_array($role, ['admin','manajer_pengadaan']);
$isStaf = $role === 'staf_pengadaan';

$where = ["YEAR(r.tanggal_mulai) = $tahun"];
if ($status) $where[] = "r.status = '" . $db->real_escape_string($status) . "'";
if ($verif)  $where[] = "r.status_verifikasi = '" . $db->real_escape_string($verif) . "'";
if ($isStaf) $where[] = "r.created_by = $uid";

$whereStr = 'WHERE ' . implode(' AND ', $where);

$realisasi = $db->query("
    SELECT r.*, u.nama_lengkap,
        (SELECT COUNT(*) FROM realisasi_detail WHERE realisasi_id = r.id) as jumlah_item,
        (SELECT COUNT(*) FROM realisasi_vendor  WHERE realisasi_id = r.id) as jumlah_vendor,
        (SELECT jenis_pengadaan FROM realisasi_detail WHERE realisasi_id = r.id ORDER BY id ASC LIMIT 1) as jenis_pengadaan,
        (SELECT rk.nama_kegiatan FROM realisasi_detail rd
         JOIN rencana_kegiatan rk ON rk.id = rd.rencana_id
         WHERE rd.realisasi_id = r.id ORDER BY rd.id ASC LIMIT 1) as nama_kegiatan
    FROM realisasi_kegiatan r
    LEFT JOIN users u ON u.id = r.created_by
    $whereStr
    ORDER BY r.tanggal_mulai DESC
");

$totalQ = $db->query("SELECT SUM(r.total_nilai) as total FROM realisasi_kegiatan r $whereStr");
$totalRealisasi = $totalQ->fetch_assoc()['total'] ?? 0;

$pendingQ     = $db->query("SELECT COUNT(*) as c FROM realisasi_kegiatan WHERE status_verifikasi='menunggu'" . ($isStaf ? " AND created_by=$uid" : ""));
$pendingCount = $pendingQ ? (int)$pendingQ->fetch_assoc()['c'] : 0;

$rows = [];
while ($row = $realisasi->fetch_assoc()) $rows[] = $row;

$pageTitle = 'Realisasi Kegiatan';
include __DIR__ . '/../../includes/header.php';
?>

<style>
/* ── Konsisten dengan rencana/index.php ─────────────────────── */
#tblRealisasi {
    table-layout: fixed;
    width: 100%;
}
#tblRealisasi thead th {
    background: #f8fafc;
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #64748b;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
#tblRealisasi td {
    vertical-align: middle;
    font-size: .875rem;
    overflow: hidden;
    text-overflow: ellipsis;
}
#tblRealisasi tbody tr:hover td { background: #f8faff; }

/* Lebar tiap kolom */
#tblRealisasi col.col-no       { width: 40px;  }
#tblRealisasi col.col-kontrak  { width: 155px; }
#tblRealisasi col.col-nama     { width: 220px; }
#tblRealisasi col.col-jenis    { width: 80px;  }
#tblRealisasi col.col-metode   { width: 135px; }
#tblRealisasi col.col-nilai    { width: 130px; }
#tblRealisasi col.col-status   { width: 70px;  }
#tblRealisasi col.col-verif    { width: 95px;  }
#tblRealisasi col.col-diinput  { width: 105px; }
#tblRealisasi col.col-aksi-mgr { width: 110px; }
#tblRealisasi col.col-aksi-stf { width: 75px;  }

.dataTables_filter { display: none !important; }
.dataTables_length { display: none !important; }

.dt-entries-wrapper {
    display: flex; align-items: center; gap: 6px;
    font-size: .82rem; color: #64748b; white-space: nowrap;
}
.dt-entries-wrapper select { width: 80px; }

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

.badge-verif-menunggu  { background:#fef3c7; color:#92400e; border:1px solid #fbbf24; }
.badge-verif-disetujui { background:#d1fae5; color:#065f46; border:1px solid #34d399; }
.badge-verif-ditolak   { background:#fee2e2; color:#991b1b; border:1px solid #f87171; }
.row-pending td { background: #fffbeb !important; }
.row-ditolak td { background: #fff1f2 !important; }
.bg-purple { background-color: #7c3aed !important; }
.badge.bg-purple { color: white !important; }
</style>

<!-- PAGE HEADER -->
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
        <!-- Import: semua role bisa -->
        <a href="<?= BASE_URL ?>/modules/realisasi/import.php"
           class="btn btn-outline-info btn-sm">
            <i class="bi bi-file-earmark-arrow-up me-1"></i>Import Excel
        </a>
        <?php if ($isMgr): ?>
        <a href="<?= BASE_URL ?>/modules/realisasi/verifikasi.php"
           class="btn btn-warning btn-sm position-relative">
            <i class="bi bi-shield-check me-1"></i>Verifikasi
            <?php if ($pendingCount > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                  style="font-size:9px;"><?= $pendingCount ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/modules/realisasi/print.php?tahun=<?= $tahun ?>&status=<?= $status ?>"
           target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i>Cetak
        </a>
        <a href="<?= BASE_URL ?>/modules/realisasi/export.php?tahun=<?= $tahun ?>&status=<?= $status ?>"
           class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i>Excel
        </a>
    </div>
</div>

<?php if ($isStaf && $pendingCount > 0): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 py-2 mb-3">
    <i class="bi bi-hourglass-split fs-5 flex-shrink-0"></i>
    <div><strong><?= $pendingCount ?> realisasi</strong> Anda sedang menunggu verifikasi manajer.</div>
</div>
<?php endif; ?>

<!-- FILTER CARD — konsisten dengan rencana -->
<div class="card mb-3 no-print">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">

            <!-- Tampilkan N entri -->
            <div class="dt-entries-wrapper">
                <span>Tampilkan</span>
                <select id="dtLengthCustom" class="form-select form-select-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="-1">Semua</option>
                </select>
                <span>entri</span>
            </div>

            <div class="vr mx-1"></div>

            <!-- Form filter — submit ke server -->
            <form method="GET" class="d-flex align-items-center gap-2 flex-wrap mb-0">

                <label class="fw-semibold text-muted mb-0" style="font-size:.8rem;white-space:nowrap;">Filter:</label>

                <!-- Tahun: input bebas -->
                <input type="number" name="tahun"
                       value="<?= $tahun ?>"
                       min="2000"
                       class="form-control form-control-sm text-center"
                       style="width:82px;"
                       placeholder="Tahun">

                <select name="status" class="form-select form-select-sm" style="width:130px;">
                    <option value="">Semua Status</option>
                    <option value="proses"  <?= $status==='proses'  ? 'selected':'' ?>>Proses</option>
                    <option value="selesai" <?= $status==='selesai' ? 'selected':'' ?>>Selesai</option>
                    <option value="batal"   <?= $status==='batal'   ? 'selected':'' ?>>Batal</option>
                </select>

                <select name="verif" class="form-select form-select-sm" style="width:145px;">
                    <option value="">Semua Verifikasi</option>
                    <option value="menunggu"  <?= $verif==='menunggu'  ? 'selected':'' ?>>⏳ Menunggu</option>
                    <option value="disetujui" <?= $verif==='disetujui' ? 'selected':'' ?>>✓ Disetujui</option>
                    <option value="ditolak"   <?= $verif==='ditolak'   ? 'selected':'' ?>>✗ Ditolak</option>
                </select>

                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="?tahun=<?= $tahun ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            </form>

            <!-- Search — di luar form agar tidak ikut submit -->
            <div class="ms-auto dt-search-wrapper">
                <i class="bi bi-search"></i>
                <input type="text" id="dtSearchCustom"
                       class="form-control form-control-sm"
                       placeholder="Cari realisasi..."
                       autocomplete="off">
            </div>

        </div>
    </div>
</div>

<!-- TABEL -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" id="tblRealisasi">
                <colgroup>
                    <col class="col-no">
                    <col class="col-kontrak">
                    <col class="col-nama">
                    <col class="col-jenis">
                    <col class="col-metode">
                    <col class="col-nilai">
                    <col class="col-status">
                    <col class="col-verif">
                    <?php if ($isMgr): ?><col class="col-diinput"><?php endif; ?>
                    <col class="<?= $isMgr ? 'col-aksi-mgr' : 'col-aksi-stf' ?>">
                </colgroup>
                <thead>
                    <tr>
                        <th class="ps-3">No.</th>
                        <th>No. Kontrak</th>
                        <th>Nama Kegiatan</th>
                        <th class="text-center">Jenis</th>
                        <th class="text-center">Metode</th>
                        <th class="text-end">Total Nilai</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Verifikasi</th>
                        <?php if ($isMgr): ?>
                        <th>Diinput oleh</th>
                        <?php endif; ?>
                        <th class="text-center no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="<?= $isMgr ? 10 : 9 ?>" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-40"></i>
                            Tidak ada data realisasi
                        </td>
                    </tr>
                <?php else:
                $metodeColors = [
                    'pembelian_langsung'  => 'success',
                    'tender_terbatas_spk' => 'info',
                    'tender_terbatas_pkp' => 'purple',
                    'tender_terbatas'     => 'info',
                    'tender_umum'         => 'danger',
                    'e_purchasing'        => 'warning',
                    'swakelola'           => 'secondary',
                ];
                foreach ($rows as $row):
                    $statusMap   = ['proses'=>'warning text-dark','selesai'=>'success','batal'=>'danger'];
                    $sc          = $statusMap[$row['status']] ?? 'secondary';
                    $verifStatus = $row['status_verifikasi'] ?? 'menunggu';
                    $rowClass    = match($verifStatus) {
                        'menunggu' => 'row-pending',
                        'ditolak'  => 'row-ditolak',
                        default    => '',
                    };
                    $metodeColor = $metodeColors[$row['metode_pengadaan']] ?? 'secondary';
                ?>
                    <tr class="<?= $rowClass ?>">
                        <td class="ps-3 text-muted"></td>
                        <td class="text-truncate" style="font-size:12px;max-width:155px;"
                            title="<?= sanitize($row['nomor_kontrak'] ?: '-') ?>">
                            <?= sanitize($row['nomor_kontrak'] ?: '-') ?>
                        </td>
                        <td>
                            <a href="detail.php?id=<?= $row['id'] ?>"
                               class="fw-semibold text-decoration-none text-dark d-block text-truncate"
                               style="font-size:13px;max-width:210px;"
                               title="<?= sanitize($row['nama_kegiatan'] ?: 'Realisasi #'.$row['id']) ?>">
                                <?= sanitize($row['nama_kegiatan'] ?: 'Realisasi #'.$row['id']) ?>
                            </a>
                            <div class="text-muted" style="font-size:11px;white-space:nowrap;">
                                <i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y', strtotime($row['tanggal_mulai'])) ?>
                                &nbsp;&middot;&nbsp;
                                <i class="bi bi-card-list me-1"></i><?= $row['jumlah_item'] ?> item
                                <?php if ($row['jumlah_vendor'] > 0): ?>
                                &nbsp;&middot;&nbsp;
                                <i class="bi bi-building me-1"></i><?= $row['jumlah_vendor'] ?> vendor
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border" style="font-size:.73rem;white-space:nowrap;">
                                <?= !empty($row['jenis_pengadaan']) ? getLabelJenis($row['jenis_pengadaan']) : '-' ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-<?= $metodeColor ?>" style="font-size:.73rem;white-space:nowrap;">
                                <?= getLabelMetode($row['metode_pengadaan']) ?>
                            </span>
                        </td>
                        <td class="text-end fw-semibold text-primary" style="font-size:13px;">
                            <?= formatRupiah($row['total_nilai']) ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-<?= $sc ?>"><?= ucfirst($row['status']) ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-verif-<?= $verifStatus ?>" style="font-size:10px;">
                                <?= match($verifStatus) {
                                    'menunggu'  => '⏳ Menunggu',
                                    'disetujui' => '✓ Disetujui',
                                    'ditolak'   => '✗ Ditolak',
                                    default     => $verifStatus,
                                } ?>
                            </span>
                            <?php if ($verifStatus === 'ditolak' && !empty($row['catatan_verifikasi'])): ?>
                            <div class="text-danger mt-1" style="font-size:10px;">
                                <i class="bi bi-info-circle me-1"></i><?= sanitize(mb_strimwidth($row['catatan_verifikasi'], 0, 40, '...')) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <?php if ($isMgr): ?>
                        <td style="font-size:11px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= sanitize($row['nama_lengkap'] ?? '-') ?>
                        </td>
                        <?php endif; ?>
                        <td class="text-center no-print">
                            <div class="btn-group btn-group-sm">
                                <a href="detail.php?id=<?= $row['id'] ?>"
                                   class="btn btn-outline-info" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ($isMgr): ?>
                                <a href="form.php?id=<?= $row['id'] ?>"
                                   class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button onclick="konfirmasiHapus('hapus.php?id=<?= $row['id'] ?>','Realisasi <?= addslashes($row['nomor_kontrak'] ?: '#'.$row['id']) ?>')"
                                        class="btn btn-outline-danger" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php elseif ($isStaf && $row['created_by'] == $uid && $verifStatus === 'menunggu'): ?>
                                <a href="form.php?id=<?= $row['id'] ?>"
                                   class="btn btn-outline-warning" title="Edit (belum diverifikasi)">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($isMgr && $verifStatus === 'menunggu'): ?>
                                <a href="verifikasi.php?id=<?= $row['id'] ?>"
                                   class="btn btn-outline-success" title="Verifikasi">
                                    <i class="bi bi-shield-check"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="5" class="text-end ps-3">Total:</td>
                        <td class="text-end text-primary"><?= formatRupiah($totalRealisasi) ?></td>
                        <td colspan="<?= $isMgr ? 4 : 3 ?>"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Info & Paginasi — konsisten dengan rencana -->
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
            <div id="dtInfoCustom" class="dataTables_info"></div>
            <div id="dtPaginateCustom"></div>
        </div>
    </div>
</div>

<?php
$extraJS = '<script>
$(document).ready(function() {

    var table = $("#tblRealisasi").DataTable({
        dom:        "rt",
        pageLength: 25,
        order:      [],
        columnDefs: [{ orderable: false, targets: ' . ($isMgr ? '[0,3,4,6,7,8,9]' : '[0,3,4,6,7,8]') . ' }],

        rowCallback: function(row, data, displayIndex) {
            var pageInfo = this.api().page.info();
            $("td:first", row).html(
                "<span class=\"text-muted ps-1\">" + (pageInfo.start + displayIndex + 1) + "</span>"
            );
        },

        language: {
            emptyTable:  "<div class=\"text-center py-5 text-muted\">" +
                             "<i class=\"bi bi-inbox fs-1 d-block mb-2 opacity-40\"></i>" +
                             "Tidak ada data realisasi" +
                         "</div>",
            zeroRecords: "<div class=\"text-center py-5 text-muted\">" +
                             "<i class=\"bi bi-search fs-2 d-block mb-2\"></i>" +
                             "Tidak ada data yang cocok" +
                         "</div>",
            info:      "Menampilkan _START_–_END_ dari _TOTAL_ entri",
            infoEmpty: "Tidak ada data",
            paginate: {
                previous: "<i class=\"bi bi-chevron-left\"></i>",
                next:     "<i class=\"bi bi-chevron-right\"></i>"
            }
        }
    });

    function updateControls() {
        var info  = table.page.info();
        var total = info.recordsTotal;
        var shown = info.recordsDisplay;

        if (total === 0) {
            $("#dtInfoCustom").text("Tidak ada data");
        } else if (shown === 0) {
            $("#dtInfoCustom").text("Tidak ada data yang cocok dengan pencarian");
        } else {
            var txt = "Menampilkan " + (info.start + 1) + "–" + info.end +
                      " dari " + shown + " entri";
            if (shown !== total) txt += " (difilter dari " + total + " total)";
            $("#dtInfoCustom").text(txt);
        }

        var $pg = $(table.table().container()).find(".dataTables_paginate");
        $("#dtPaginateCustom").empty().append($pg.clone(true, true));
        if (info.pages <= 1) $("#dtPaginateCustom").hide();
        else                  $("#dtPaginateCustom").show();
    }

    table.on("draw", updateControls).draw();

    /* Search — di luar form */
    var searchEl = document.getElementById("dtSearchCustom");
    if (searchEl) {
        searchEl.addEventListener("input", function() {
            table.search(this.value).draw();
        });
        searchEl.addEventListener("keydown", function(e) {
            if (e.key === "Enter") e.preventDefault();
        });
    }

    /* Length */
    $("#dtLengthCustom").on("change", function() {
        var val = $(this).val();
        table.page.len(val === "-1" ? -1 : parseInt(val)).draw();
    });

});

function konfirmasiHapus(url, nama) {
    if (confirm("Hapus " + nama + "?\nData tidak dapat dikembalikan."))
        window.location.href = url;
}
</script>';

include __DIR__ . '/../../includes/footer.php';
?>