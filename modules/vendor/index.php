<?php
/**
 * modules/vendor/index.php
 * Data Vendor / Penyedia — Ranking keterlibatan
 */

session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db    = getDB();
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$cari  = trim($_GET['cari'] ?? '');

// Daftar tahun tersedia
$qTahun    = $db->query("SELECT DISTINCT YEAR(tanggal_mulai) AS tahun FROM realisasi_kegiatan ORDER BY tahun DESC");
$listTahun = [];
while ($t = $qTahun->fetch_assoc()) $listTahun[] = (int)$t['tahun'];
if (!in_array($tahun, $listTahun) && !empty($listTahun)) {
    array_unshift($listTahun, $tahun);
}

// Query ranking vendor
$cariSQL = '';
if ($cari !== '') {
    $cariEsc = $db->real_escape_string($cari);
    $cariSQL = "AND v.nama_vendor LIKE '%$cariEsc%'";
}

$qVendor = $db->query("
    SELECT
        v.nama_vendor,
        COUNT(DISTINCT v.realisasi_id)          AS jumlah_kontrak,
        SUM(v.nilai_kontrak)                    AS total_nilai,
        AVG(v.nilai_kontrak)                    AS rata_nilai,
        MIN(v.nilai_kontrak)                    AS min_nilai,
        MAX(v.nilai_kontrak)                    AS max_nilai,
        MIN(r.tanggal_mulai)                    AS tgl_pertama,
        MAX(r.tanggal_mulai)                    AS tgl_terakhir,
        GROUP_CONCAT(
            DISTINCT r.nomor_kontrak
            ORDER BY r.tanggal_mulai ASC
            SEPARATOR ', '
        )                                       AS daftar_kontrak,
        GROUP_CONCAT(
            DISTINCT r.metode_pengadaan
            ORDER BY r.tanggal_mulai ASC
            SEPARATOR ','
        )                                       AS daftar_metode
    FROM realisasi_vendor v
    JOIN realisasi_kegiatan r ON r.id = v.realisasi_id
    WHERE YEAR(r.tanggal_mulai) = $tahun
      AND r.status != 'batal'
      $cariSQL
    GROUP BY v.nama_vendor
    ORDER BY jumlah_kontrak DESC, total_nilai DESC
");

$vendors = [];
while ($row = $qVendor->fetch_assoc()) $vendors[] = $row;

$totalVendor  = count($vendors);
$totalKontrak = array_sum(array_column($vendors, 'jumlah_kontrak'));
$totalNilai   = array_sum(array_column($vendors, 'total_nilai'));
$maxKontrak   = !empty($vendors) ? (int)$vendors[0]['jumlah_kontrak'] : 1;

// Chart data (top 10)
$chartLabels = [];
$chartJumlah = [];
$chartNilai  = [];
$chartColors = [
    '#4361ee','#3a0ca3','#7209b7','#f72585','#4cc9f0',
    '#4895ef','#560bad','#b5179e','#f77f00','#2ec4b6',
];
foreach (array_slice($vendors, 0, 10) as $v) {
    $chartLabels[] = mb_strlen($v['nama_vendor']) > 22
        ? mb_substr($v['nama_vendor'], 0, 20) . '…'
        : $v['nama_vendor'];
    $chartJumlah[] = (int)$v['jumlah_kontrak'];
    $chartNilai[]  = (float)$v['total_nilai'];
}

$pageTitle = 'Data Vendor';
include __DIR__ . '/../../includes/header.php';
?>

<style>
.vendor-rank-badge {
    width: 28px; height: 28px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .72rem; font-weight: 700; flex-shrink: 0;
}
.rank-1 { background: #ffd700; color: #7a5800; }
.rank-2 { background: #c0c0c0; color: #4a4a4a; }
.rank-3 { background: #cd7f32; color: #fff; }
.rank-n { background: #e9ecef; color: #64748b; }
.metode-badge {
    font-size: .68rem; padding: 2px 7px; border-radius: 20px;
    border: 1px solid #dee2e6; background: #f8fafc; color: #475569;
    white-space: nowrap;
}
.sparkbar { height: 6px; border-radius: 3px; background: #e9ecef; overflow: hidden; }
.sparkbar-fill { height: 100%; border-radius: 3px; transition: width .5s ease; }
#tabelVendor thead th {
    background: #f8fafc; font-size: .74rem; text-transform: uppercase;
    letter-spacing: .04em; color: #64748b;
    border-bottom: 2px solid #e2e8f0; white-space: nowrap;
}
#tabelVendor td { vertical-align: middle; font-size: .86rem; }
#tabelVendor tbody tr:hover td { background: #f8faff; }
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
</style>

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0">
            <i class="bi bi-building-check me-2 text-primary"></i>Data Vendor / Penyedia
        </h5>
        <small class="text-muted">Ranking keterlibatan vendor dalam realisasi kegiatan</small>
    </div>
    <div class="d-flex gap-2 flex-wrap no-print">
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-printer me-1"></i>Cetak
        </button>        
        <a href="export.php?tahun=<?= $tahun ?>&cari=<?= urlencode($cari) ?>"
           class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i>Excel
        </a>
    </div>
</div>

<!-- FILTER -->
<div class="card mb-3 no-print">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">

            <!-- Tampilkan N entri — di luar form, hanya kontrol DataTable -->
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

            <form method="GET" class="d-flex align-items-center gap-2 flex-wrap mb-0">
                <label class="fw-semibold text-muted mb-0" style="font-size:.8rem;white-space:nowrap;">Filter:</label>
                <!-- Tahun: input bebas, tak terbatas -->
                <input type="number" name="tahun"
                       value="<?= $tahun ?>"
                       min="2000"
                       class="form-control form-control-sm text-center"
                       style="width:82px;"
                       placeholder="Tahun">
                       
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="?tahun=<?= date('Y') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi me-1"></i>Reset
                </a>
            </form>

            <!-- Search DataTable — di luar form, ms-auto ke kanan -->
            <div class="ms-auto dt-search-wrapper">
                <i class="bi bi-search"></i>
                <input type="text" id="dtSearchCustom" class="form-control form-control-sm"
                       placeholder="Cari vendor..." autocomplete="off">
            </div>

        </div>
    </div>
</div>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card text-center py-3">
            <div class="fs-3 fw-bold text-primary"><?= $totalVendor ?></div>
            <small class="text-muted">Total Vendor Aktif</small>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card text-center py-3">
            <div class="fs-3 fw-bold text-success"><?= $totalKontrak ?></div>
            <small class="text-muted">Total Keterlibatan Kontrak</small>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card text-center py-3">
            <div class="fs-3 fw-bold text-warning" style="font-size:1.4rem !important;">
                <?= formatRupiah($totalNilai) ?>
            </div>
            <small class="text-muted">Total Nilai Seluruh Vendor</small>
        </div>
    </div>
</div>

<!-- TABEL LENGKAP -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>
            <i class="bi bi-list-ol me-2 text-primary"></i>
            Daftar Lengkap Vendor
            <span class="badge bg-primary ms-1"><?= $totalVendor ?> vendor</span>
        </span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($vendors)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-building fs-2 d-block mb-2 opacity-50"></i>
                Tidak ada data vendor untuk tahun <?= $tahun ?>
                <?= $cari ? " dengan kata kunci \"" . sanitize($cari) . "\"" : '' ?>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tabelVendor">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:44px;">No.</th>
                        <th>Nama Vendor</th>
                        <th class="text-center">Frekuensi</th>
                        <th class="text-center">Keterlibatan</th>
                        <th class="text-end">Total Nilai</th>
                        <th class="text-end">Rata-rata / Kontrak</th>
                        <th class="text-center">Periode</th>
                        <th>No. Kontrak</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($vendors as $i => $v):
                    $pct       = $totalKontrak > 0
                        ? round(($v['jumlah_kontrak'] / $totalKontrak) * 100, 1) : 0;
                    $barWidth  = $maxKontrak > 0
                        ? round(($v['jumlah_kontrak'] / $maxKontrak) * 100) : 0;
                    $rankClass = match(true) {
                        $i === 0 => 'rank-1',
                        $i === 1 => 'rank-2',
                        $i === 2 => 'rank-3',
                        default  => 'rank-n',
                    };
                    $barColor    = $chartColors[$i % count($chartColors)];
                    $metodes     = $v['daftar_metode']
                        ? array_unique(explode(',', $v['daftar_metode'])) : [];
                    $kontrakArr  = $v['daftar_kontrak']
                        ? explode(', ', $v['daftar_kontrak']) : [];
                ?>
                <tr>
                    <td class="ps-3">
                        <span class="vendor-rank-badge <?= $rankClass ?>"><?= $i + 1 ?></span>
                    </td>
                    <td>
                        <div class="fw-semibold" style="font-size:.87rem;">
                            <?= sanitize($v['nama_vendor']) ?>
                        </div>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            <?php foreach ($metodes as $m): ?>
                                <span class="metode-badge"><?= getLabelMetode(trim($m)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge rounded-pill px-3 py-1"
                              style="background:<?= $barColor ?>;font-size:.8rem;">
                            <?= $v['jumlah_kontrak'] ?>×
                        </span>
                    </td>
                    <td style="min-width:120px;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="sparkbar flex-grow-1">
                                <div class="sparkbar-fill"
                                     style="width:<?= $barWidth ?>%;background:<?= $barColor ?>;"></div>
                            </div>
                            <small class="text-muted fw-semibold" style="min-width:36px;">
                                <?= $pct ?>%
                            </small>
                        </div>
                    </td>
                    <td class="text-end fw-semibold text-primary">
                        <?= formatRupiah($v['total_nilai']) ?>
                        <div style="font-size:.72rem;color:#94a3b8;font-weight:400;">
                            min: <?= formatRupiah($v['min_nilai']) ?><br>
                            maks: <?= formatRupiah($v['max_nilai']) ?>
                        </div>
                    </td>
                    <td class="text-end text-muted" style="font-size:.82rem;">
                        <?= formatRupiah($v['rata_nilai']) ?>
                    </td>
                    <td class="text-center text-muted" style="font-size:.8rem;white-space:nowrap;">
                        <?= date('d/m/Y', strtotime($v['tgl_pertama'])) ?>
                        <?php if ($v['tgl_pertama'] !== $v['tgl_terakhir']): ?>
                            <br><span class="text-muted">s/d</span><br>
                            <?= date('d/m/Y', strtotime($v['tgl_terakhir'])) ?>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.78rem;max-width:200px;">
                        <?php foreach (array_slice($kontrakArr, 0, 2) as $nk): ?>
                            <span class="d-block text-truncate text-muted">
                                <?= sanitize(trim($nk)) ?>
                            </span>
                        <?php endforeach; ?>
                        <?php if (count($kontrakArr) > 2): ?>
                            <span class="text-primary" style="font-size:.72rem;">
                                +<?= count($kontrakArr) - 2 ?> lainnya
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
            <div id="dtInfoCustom" class="dataTables_info"></div>
            <div id="dtPaginateCustom"></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$extraJS = '<script>
var labelsVendor = ' . json_encode($chartLabels) . ';
var dataJumlah   = ' . json_encode($chartJumlah) . ';
var dataNilai    = ' . json_encode($chartNilai) . ';
var colors       = ' . json_encode(array_slice($chartColors, 0, count($chartLabels))) . ';

var ctxV = document.getElementById("chartVendor");
if (ctxV) {
    var chartV = new Chart(ctxV.getContext("2d"), {
        type: "bar",
        data: {
            labels: labelsVendor,
            datasets: [{
                label: "Jumlah Kontrak",
                data: dataJumlah,
                backgroundColor: colors,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) { return " " + ctx.parsed.y + " kontrak"; }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, font: { size: 11 } },
                    grid: { color: "#f1f5f9" }
                },
                x: {
                    ticks: { font: { size: 10 }, maxRotation: 30 },
                    grid: { display: false }
                }
            }
        }
    });

    document.querySelectorAll("#chartModeToggle button").forEach(function(btn) {
        btn.addEventListener("click", function() {
            document.querySelectorAll("#chartModeToggle button").forEach(function(b) {
                b.classList.remove("btn-primary","active");
                b.classList.add("btn-outline-primary");
            });
            this.classList.remove("btn-outline-primary");
            this.classList.add("btn-primary","active");
            var mode = this.dataset.mode;
            if (mode === "jumlah") {
                chartV.data.datasets[0].data  = dataJumlah;
                chartV.data.datasets[0].label = "Jumlah Kontrak";
                chartV.options.plugins.tooltip.callbacks.label = function(ctx) {
                    return " " + ctx.parsed.y + " kontrak";
                };
                chartV.options.scales.y.ticks.callback = undefined;
            } else {
                chartV.data.datasets[0].data  = dataNilai;
                chartV.data.datasets[0].label = "Total Nilai (Rp)";
                chartV.options.plugins.tooltip.callbacks.label = function(ctx) {
                    return " Rp " + (ctx.parsed.y / 1000000).toFixed(1) + " jt";
                };
                chartV.options.scales.y.ticks.callback = function(v) {
                    return "Rp " + (v / 1000000).toFixed(0) + " jt";
                };
            }
            chartV.update();
        });
    });
}

$(document).ready(function() {
    var table = $("#tabelVendor").DataTable({
        dom: "rt",
        pageLength: 25,
        order: [[2, "desc"]],
        columnDefs: [{ orderable: false, targets: [0, 3, 6, 7] }],
        language: {
            emptyTable:  "<div class=\"text-center py-4 text-muted\">Tidak ada data vendor</div>",
            zeroRecords: "<div class=\"text-center py-4 text-muted\">Tidak ada data yang cocok</div>",
            info:        "Menampilkan _START_–_END_ dari _TOTAL_ vendor",
            infoEmpty:   "Tidak ada data",
            paginate: {
                previous: "<i class=\"bi bi-chevron-left\"></i>",
                next:     "<i class=\"bi bi-chevron-right\"></i>"
            }
        }
    });

    function updateControls() {
        var info = table.page.info();
        var txt  = info.recordsDisplay === 0
            ? "Tidak ada data"
            : "Menampilkan " + (info.start + 1) + "–" + info.end + " dari " + info.recordsDisplay + " vendor";
        $("#dtInfoCustom").text(txt);
        var $pag = $(table.table().container()).find(".dataTables_paginate");
        $("#dtPaginateCustom").empty().append($pag.clone(true, true));
    }

    table.on("draw", updateControls).draw();

    $("#dtSearchCustom").on("input", function() {
        table.search(this.value).draw();
    });
});
</script>';

include __DIR__ . '/../../includes/footer.php';
?>