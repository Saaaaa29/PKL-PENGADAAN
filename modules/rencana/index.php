<?php
/**
 * modules/rencana/index.php
 */

session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db     = getDB();
$tahun  = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$jenis  = $_GET['jenis'] ?? '';
$urutan = in_array($_GET['urutan'] ?? '', ['nama', 'id']) ? $_GET['urutan'] : 'nama';

$where = ["r.tahun = $tahun"];
if ($jenis && array_key_exists($jenis, LABEL_JENIS))
    $where[] = "r.jenis_pengadaan = '" . $db->real_escape_string($jenis) . "'";
$whereStr = 'WHERE ' . implode(' AND ', $where);

// Cek tabel realisasi
$tabelAda = false;
$c1 = $db->query("SHOW TABLES LIKE 'realisasi_kegiatan'");
$c2 = $db->query("SHOW TABLES LIKE 'realisasi_detail'");
if ($c1 && $c1->num_rows > 0 && $c2 && $c2->num_rows > 0) $tabelAda = true;

// Urutan dari SQL — bukan DataTable, agar konsisten saat export/print juga
$orderSQL = ($urutan === 'id') ? 'r.id ASC' : 'r.nama_kegiatan ASC';

if ($tabelAda) {
    $sql = "SELECT r.*, u.nama_lengkap,
                (SELECT rk.status
                 FROM realisasi_detail rd
                 JOIN realisasi_kegiatan rk ON rk.id = rd.realisasi_id
                 WHERE rd.rencana_id = r.id
                 ORDER BY rk.tanggal_mulai DESC LIMIT 1
                ) AS status_realisasi
            FROM rencana_kegiatan r
            LEFT JOIN users u ON u.id = r.created_by
            $whereStr
            ORDER BY $orderSQL";
} else {
    $sql = "SELECT r.*, u.nama_lengkap, NULL AS status_realisasi
            FROM rencana_kegiatan r
            LEFT JOIN users u ON u.id = r.created_by
            $whereStr
            ORDER BY $orderSQL";
}

$result = $db->query($sql);
if ($result === false)
    die('<div class="alert alert-danger m-4"><strong>Query Error:</strong> ' . htmlspecialchars($db->error) . '</div>');

$rows = [];
while ($row = $result->fetch_assoc()) $rows[] = $row;

$whereTotal = ["tahun = $tahun"];
if ($jenis && array_key_exists($jenis, LABEL_JENIS))
    $whereTotal[] = "jenis_pengadaan = '" . $db->real_escape_string($jenis) . "'";
$totalQ        = $db->query("SELECT SUM(nilai_anggaran) as total FROM rencana_kegiatan WHERE " . implode(' AND ', $whereTotal));
$totalAnggaran = ($totalQ !== false) ? ($totalQ->fetch_assoc()['total'] ?? 0) : 0;

$tahunSekarang = (int)date('Y');

$pageTitle = 'Rencana Kegiatan';
include __DIR__ . '/../../includes/header.php';
?>

<style>
#tabelRencana thead th {
    background: #f8fafc;
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #64748b;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}
#tabelRencana td { vertical-align: middle; font-size: .875rem; }
#tabelRencana tbody tr:hover td { background: #f8faff; }

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

/* Warna custom badge metode pengadaan */
.bg-purple { background-color: #7c3aed !important; }
.badge.bg-purple { color: white !important; }
</style>

<!-- PAGE HEADER -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0">Rencana Kegiatan Pengadaan</h5>
        <small class="text-muted">
            <?= count($rows) ?> kegiatan &bull; Total: <strong><?= formatRupiah($totalAnggaran) ?></strong>
        </small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= BASE_URL ?>/modules/rencana/form.php?<?= http_build_query(['ref_tahun'=>$tahun,'ref_jenis'=>$jenis,'ref_urutan'=>$urutan]) ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Tambah Rencana
        </a>
        <a href="<?= BASE_URL ?>/modules/rencana/print.php?tahun=<?= $tahun ?>&jenis=<?= urlencode($jenis) ?>"
           target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i>Cetak
        </a>
        <a href="<?= BASE_URL ?>/modules/rencana/export.php?tahun=<?= $tahun ?>&jenis=<?= urlencode($jenis) ?>"
           class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i>Excel
        </a>
    </div>
</div>

<!-- FILTER CARD -->
<div class="card mb-3 no-print">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">

            <!-- Tampilkan N entri — di LUAR form, hanya kontrol DataTable -->
            <div class="dt-entries-wrapper">
                <span>Tampilkan</span>
                <select id="dtLengthCustom" class="form-select form-select-sm">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="250">250</option>
                    <option value="-1">Semua</option>
                </select>
                <span>entri</span>
            </div>

            <div class="vr mx-1"></div>

            <!-- Form filter + urutan — submit ke server -->
            <form method="GET" class="d-flex align-items-center gap-2 flex-wrap mb-0">

                <label class="fw-semibold text-muted mb-0" style="font-size:.8rem; white-space:nowrap;">Filter:</label>

                <!-- Tahun: input bebas, tak terbatas -->
                <input type="number" name="tahun"
                       value="<?= $tahun ?>"
                       min="2000"
                       class="form-control form-control-sm text-center"
                       style="width:82px;"
                       placeholder="Tahun">

                <select name="jenis" class="form-select form-select-sm" style="width:140px;">
                    <option value="">Semua Jenis</option>
                    <?php foreach (LABEL_JENIS as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $jenis === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="vr mx-1"></div>

                <!-- FIX 2: Urutan dari server -->
                <label class="fw-semibold text-muted mb-0" style="font-size:.8rem; white-space:nowrap;">Urutan:</label>
                <select name="urutan" class="form-select form-select-sm" style="width:148px;">
                    <option value="nama" <?= $urutan === 'nama' ? 'selected' : '' ?>>A–Z Nama Kegiatan</option>
                    <option value="id"   <?= $urutan === 'id'   ? 'selected' : '' ?>>Urutan Input</option>
                </select>

                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="?" class="btn btn-sm btn-outline-secondary">Reset</a>
            </form>
            <!-- END form — search di luar agar tidak ikut submit -->

            <!-- FIX 3: Search benar-benar di luar form -->
            <div class="ms-auto dt-search-wrapper">
                <i class="bi bi-search"></i>
                <input type="text" id="dtSearchCustom"
                       class="form-control form-control-sm"
                       placeholder="Cari kegiatan..."
                       autocomplete="off">
            </div>

        </div>
    </div>
</div>

<!-- TABEL -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tabelRencana">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:40px;">No.</th>
                        <th>Nama Kegiatan</th>
                        <th>Jenis</th>
                        <th>Metode</th>
                        <th class="text-center">Volume</th>
                        <th class="text-end">Nilai Anggaran</th>
                        <th class="text-center">Jadwal Bulan</th>
                        <th class="text-center">Status Realisasi</th>
                        <th class="text-center no-print" style="width:90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $metodeColors = [
                                    'pembelian_langsung'   => 'success',
                                    'tender_terbatas_spk'  => 'info',
                                    'tender_terbatas_pkp'  => 'purple',
                                    'tender_terbatas'      => 'info',
                                    'tender_umum'          => 'danger',
                                    'e_purchasing'         => 'warning',
                                    'swakelola'            => 'secondary',
                                ];
                foreach ($rows as $no => $row):
                    $sr = $row['status_realisasi'] ?? null;
                    if ($sr === 'selesai')    { $srLabel = 'Selesai'; $srClass = 'bg-success'; }
                    elseif ($sr === 'proses') { $srLabel = 'Proses';  $srClass = 'bg-warning text-dark'; }
                    elseif ($sr === 'batal')  { $srLabel = 'Batal';   $srClass = 'bg-danger'; }
                    else                      { $srLabel = 'Belum Terlaksana'; $srClass = 'bg-secondary'; }
                    $color = $metodeColors[$row['metode_pengadaan']] ?? 'secondary';
                ?>
                <tr>
                    <td class="ps-3 text-muted"><?= $no + 1 ?></td>
                    <td>
                        <div class="fw-semibold"><?= sanitize($row['nama_kegiatan']) ?></div>
                        <?php if (!empty($row['keterangan'])): ?>
                            <small class="text-muted"><?= sanitize(mb_substr($row['keterangan'], 0, 60)) ?>…</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border" style="font-size:.73rem;">
                            <?= getLabelJenis($row['jenis_pengadaan']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $color ?>" style="font-size:.73rem;">
                            <?= getLabelMetode($row['metode_pengadaan']) ?>
                        </span>
                    </td>
                    <td class="text-center text-muted">
                        <?= formatAngka($row['volume']) ?> <?= sanitize($row['satuan']) ?>
                    </td>
                    <td class="text-end fw-semibold text-primary">
                        <?= formatRupiah($row['nilai_anggaran']) ?>
                    </td>
                    <td class="text-center text-muted" style="font-size:.82rem;">
                        <?= formatBulanRencana($row['bulan_rencana'], true) ?>
                    </td>
                    <td class="text-center">
                        <span class="badge <?= $srClass ?>"><?= $srLabel ?></span>
                    </td>
                    <td class="text-center no-print">
                        <div class="btn-group btn-group-sm">
                            <a href="form.php?id=<?= $row['id'] ?>&<?= http_build_query(['ref_tahun'=>$tahun,'ref_jenis'=>$jenis,'ref_urutan'=>$urutan]) ?>" class="btn btn-outline-primary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button onclick="konfirmasiHapus('hapus.php?id=<?= $row['id'] ?>&<?= http_build_query(['tahun'=>$tahun,'jenis'=>$jenis,'urutan'=>$urutan]) ?>','<?= addslashes($row['nama_kegiatan']) ?>')"
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

        <!-- Info & Paginasi custom -->
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
            <div id="dtInfoCustom" class="dataTables_info"></div>
            <div id="dtPaginateCustom"></div>
        </div>
    </div>
</div>

<?php
$extraJS = '<script>
$(document).ready(function() {

    var table = $("#tabelRencana").DataTable({
        dom:        "rt",
        pageLength: 25,
        /* order: [] — urutan sudah dari SQL, DataTable tidak sort ulang */
        order:      [],
        columnDefs: [{ orderable: false, targets: [0, 7, 8] }],

        rowCallback: function(row, data, displayIndex) {
            var pageInfo = this.api().page.info();
            $("td:first", row).html(
                "<span class=\"text-muted\">" + (pageInfo.start + displayIndex + 1) + "</span>"
            );
        },

        language: {
            emptyTable:  "<div class=\"text-center py-4 text-muted\">" +
                             "<i class=\"bi bi-inbox fs-2 d-block mb-2\"></i>" +
                             "Tidak ada data rencana kegiatan" +
                         "</div>",
            zeroRecords: "<div class=\"text-center py-4 text-muted\">" +
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

    /* Update info & paginate setiap draw */
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

    /* SEARCH — vanilla JS, di luar form, tidak akan trigger submit */
    var searchEl = document.getElementById("dtSearchCustom");
    if (searchEl) {
        searchEl.addEventListener("input", function() {
            table.search(this.value).draw();
        });
        searchEl.addEventListener("keydown", function(e) {
            if (e.key === "Enter") e.preventDefault();
        });
    }

    /* LENGTH — termasuk opsi Semua (-1) */
    $("#dtLengthCustom").on("change", function() {
        var val = $(this).val();
        table.page.len(val === "-1" ? -1 : parseInt(val)).draw();
    });

});
</script>';

include __DIR__ . '/../../includes/footer.php';
?>