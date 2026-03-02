<?php
/**
 * modules/laporan/index.php
 * Laporan perbandingan rencana vs realisasi dengan toggle bulan/jenis/metode
 */

session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db    = getDB();
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$jenis = $_GET['jenis'] ?? '';

// WHERE clause
$where = ["rk.tahun = $tahun"];
if ($jenis && array_key_exists($jenis, LABEL_JENIS)) {
    $where[] = "rk.jenis_pengadaan = '" . $db->real_escape_string($jenis) . "'";
}
$whereStr = 'WHERE ' . implode(' AND ', $where);

// Data tabel laporan
$laporan = $db->query("
    SELECT rk.*,
        COALESCE(SUM(rd.nilai_anggaran), 0) as total_realisasi,
        COALESCE(SUM(rd.volume), 0)         as vol_realisasi,
        COUNT(DISTINCT rd.realisasi_id)      as jml_realisasi
    FROM rencana_kegiatan rk
    LEFT JOIN realisasi_detail rd ON rd.rencana_id = rk.id
    $whereStr
    GROUP BY rk.id
    ORDER BY rk.bulan_rencana ASC, rk.id ASC
");
if (!$laporan) die("Query error: " . $db->error);

// Statistik ringkasan
$statQ = $db->query("
    SELECT SUM(rk.nilai_anggaran) as total_rencana,
           COALESCE(SUM(rd.nilai_anggaran), 0) as total_realisasi
    FROM rencana_kegiatan rk
    LEFT JOIN realisasi_detail rd ON rd.rencana_id = rk.id
    $whereStr
");
$stat           = $statQ->fetch_assoc();
$totalRencana   = (float)($stat['total_rencana']   ?? 0);
$totalRealisasi = (float)($stat['total_realisasi'] ?? 0);
$persenSerapan  = $totalRencana > 0 ? round(($totalRealisasi / $totalRencana) * 100, 1) : 0;

// Realisasi di luar rencana
$whereLuar = "YEAR(r.tanggal_mulai) = $tahun";
if ($jenis) $whereLuar .= " AND d.jenis_pengadaan = '" . $db->real_escape_string($jenis) . "'";
$luarQ   = $db->query("SELECT SUM(d.nilai_anggaran) as total
    FROM realisasi_detail d JOIN realisasi_kegiatan r ON r.id = d.realisasi_id
    WHERE d.rencana_id IS NULL AND $whereLuar");
$totalLuar = (float)($luarQ->fetch_assoc()['total'] ?? 0);

// Rows ke array
$rows = [];
while ($r = $laporan->fetch_assoc()) $rows[] = $r;

// ================================================================
// DATA UNTUK CHART (BULAN, JENIS, METODE)
// ================================================================

// -------------------------------------------------------
// Data chart: per BULAN
// -------------------------------------------------------
$chartBulanLabels    = [];
$chartBulanRencana   = [];
$chartBulanRealisasi = [];

// Cek tipe kolom bulan_rencana
$cekKolom   = $db->query("SHOW COLUMNS FROM rencana_kegiatan LIKE 'bulan_rencana'");
$infoKolom  = $cekKolom ? $cekKolom->fetch_assoc() : null;
$isVarchar  = $infoKolom && stripos($infoKolom['Type'], 'varchar') !== false;

for ($i = 1; $i <= 12; $i++) {
    // Data rencana per bulan
    if ($isVarchar) {
        $q = $db->query("SELECT SUM(nilai_anggaran) as total FROM rencana_kegiatan
                         WHERE tahun = $tahun AND FIND_IN_SET($i, bulan_rencana)");
    } else {
        $q = $db->query("SELECT SUM(nilai_anggaran) as total FROM rencana_kegiatan
                         WHERE tahun = $tahun AND bulan_rencana = $i");
    }
    
    if ($jenis) {
        if ($isVarchar) {
            $q = $db->query("SELECT SUM(nilai_anggaran) as total FROM rencana_kegiatan
                             WHERE tahun = $tahun AND jenis_pengadaan = '$jenis' 
                             AND FIND_IN_SET($i, bulan_rencana)");
        } else {
            $q = $db->query("SELECT SUM(nilai_anggaran) as total FROM rencana_kegiatan
                             WHERE tahun = $tahun AND jenis_pengadaan = '$jenis' 
                             AND bulan_rencana = $i");
        }
    }
    
    $chartBulanRencana[] = ($q && $row = $q->fetch_assoc()) ? (float)($row['total'] ?? 0) : 0;
    
    // Data realisasi per bulan
    $q2 = $db->query("SELECT SUM(rd.nilai_anggaran) as total
                      FROM realisasi_detail rd
                      JOIN realisasi_kegiatan r ON r.id = rd.realisasi_id
                      WHERE YEAR(r.tanggal_mulai) = $tahun AND MONTH(r.tanggal_mulai) = $i" . 
                      ($jenis ? " AND rd.jenis_pengadaan = '$jenis'" : ""));
    $chartBulanRealisasi[] = ($q2 && $row2 = $q2->fetch_assoc()) ? (float)($row2['total'] ?? 0) : 0;
    
    $chartBulanLabels[] = NAMA_BULAN[$i];
}

// -------------------------------------------------------
// Data chart: per JENIS PENGADAAN
// -------------------------------------------------------
$chartJenisLabels    = [];
$chartJenisRencana   = [];
$chartJenisRealisasi = [];
foreach (LABEL_JENIS as $key => $label) {
    $q  = $db->query("SELECT SUM(nilai_anggaran) as tot FROM rencana_kegiatan
                      WHERE tahun=$tahun AND jenis_pengadaan='$key'");
    $q2 = $db->query("SELECT SUM(rd.nilai_anggaran) as tot FROM realisasi_detail rd
                      JOIN realisasi_kegiatan r ON r.id=rd.realisasi_id
                      WHERE YEAR(r.tanggal_mulai)=$tahun AND rd.jenis_pengadaan='$key'");
    
    $chartJenisLabels[]    = $label;
    $chartJenisRencana[]   = (float)($q->fetch_assoc()['tot'] ?? 0);
    $chartJenisRealisasi[] = (float)($q2->fetch_assoc()['tot'] ?? 0);
}

// -------------------------------------------------------
// Data chart: per METODE PENGADAAN
// -------------------------------------------------------
$chartMetodeLabels    = [];
$chartMetodeRencana   = [];
$chartMetodeRealisasi = [];
foreach (LABEL_METODE as $key => $label) {
    $q = $db->query("SELECT SUM(nilai_anggaran) as tot FROM rencana_kegiatan
                     WHERE tahun=$tahun AND metode_pengadaan='$key'");
    if (!$q) { $chartMetodeRencana[] = 0; }
    else      { $chartMetodeRencana[] = (float)($q->fetch_assoc()['tot'] ?? 0); }

    $q2 = $db->query("SELECT SUM(rd.nilai_anggaran) as tot FROM realisasi_detail rd
                      JOIN realisasi_kegiatan r ON r.id = rd.realisasi_id
                      WHERE YEAR(r.tanggal_mulai)=$tahun AND rd.metode_pengadaan='$key'");
    if (!$q2) { $chartMetodeRealisasi[] = 0; }
    else       { $chartMetodeRealisasi[] = (float)($q2->fetch_assoc()['tot'] ?? 0); }

    $chartMetodeLabels[] = $label;
}

// Sisa anggaran untuk donut
$sisaAnggaran = max(0, $totalRencana - $totalRealisasi);

// Encode JSON untuk chart
$jsonBulanLabels      = json_encode($chartBulanLabels);
$jsonBulanRencana     = json_encode($chartBulanRencana);
$jsonBulanRealisasi   = json_encode($chartBulanRealisasi);
$jsonJenisLabels      = json_encode($chartJenisLabels);
$jsonJenisRencana     = json_encode($chartJenisRencana);
$jsonJenisRealisasi   = json_encode($chartJenisRealisasi);
$jsonMetodeLabels     = json_encode($chartMetodeLabels);
$jsonMetodeRencana    = json_encode($chartMetodeRencana);
$jsonMetodeRealisasi  = json_encode($chartMetodeRealisasi);

$pageTitle = 'Laporan Perbandingan';
include __DIR__ . '/../../includes/header.php';
?>

<!-- ====== HEADER ====== -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0">Laporan Perbandingan Rencana vs Realisasi</h5>
        <small class="text-muted">Tahun <?= $tahun ?></small>
    </div>
    <div class="d-flex gap-2 flex-wrap no-print">
        <a href="print.php?tahun=<?= $tahun ?>&jenis=<?= urlencode($jenis) ?>"
           target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i>Cetak
        </a>
        <a href="export.php?tahun=<?= $tahun ?>&jenis=<?= urlencode($jenis) ?>"
           class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i>Excel
        </a>
    </div>
</div>

<!-- ====== FILTER ====== -->
<div class="card mb-3 no-print">
    <div class="card-body py-2 px-3">
        <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
            <label class="fw-semibold text-muted mb-0" style="font-size:.8rem; white-space:nowrap;">Filter:</label>
                <!-- Tahun: input bebas, tak terbatas -->
                <input type="number" name="tahun"
                       value="<?= $tahun ?>"
                       min="2000"
                       class="form-control form-control-sm text-center"
                       style="width:82px;"
                       placeholder="Tahun">
            <select name="jenis" class="form-select form-select-sm" style="width:165px;">
                <option value="">Semua Jenis</option>
                <?php foreach (LABEL_JENIS as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $jenis === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-funnel me-1"></i>Filter
            </button>
            <a href="?" class="btn btn-sm btn-outline-secondary">Reset</a>
        </form>
    </div>
</div>

<!-- ====== STAT CARDS ====== -->
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card bg-blue">
            <div class="stat-value" style="font-size:1.15rem;"><?= formatRupiah($totalRencana) ?></div>
            <div class="stat-label">Total Rencana</div>
            <i class="bi bi-journal-text stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card bg-green">
            <div class="stat-value" style="font-size:1.15rem;"><?= formatRupiah($totalRealisasi) ?></div>
            <div class="stat-label">Total Realisasi (dari rencana)</div>
            <i class="bi bi-check2-circle stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card bg-orange">
            <div class="stat-value"><?= $persenSerapan ?>%</div>
            <div class="stat-label">Serapan Anggaran</div>
            <i class="bi bi-pie-chart stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card bg-purple">
            <div class="stat-value" style="font-size:1.15rem;"><?= formatRupiah($totalLuar) ?></div>
            <div class="stat-label">Realisasi di luar Rencana</div>
            <i class="bi bi-plus-circle stat-icon"></i>
        </div>
    </div>
</div>

<!-- ====== PROGRESS SERAPAN ====== -->
<div class="card mb-3">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between mb-2">
            <span class="fw-semibold" style="font-size:.9rem;">Serapan Keseluruhan</span>
            <span class="fw-bold text-primary"><?= $persenSerapan ?>%</span>
        </div>
        <div class="progress" style="height:10px;">
            <div class="progress-bar bg-success" style="width:<?= min(100, $persenSerapan) ?>%"></div>
        </div>
        <div class="d-flex justify-content-between mt-1">
            <small class="text-muted">Rp 0</small>
            <small class="text-muted"><?= formatRupiah($totalRencana) ?></small>
        </div>
    </div>
</div>

<!-- ====== GRAFIK DENGAN TOGGLE ====== -->
<div class="row g-3 mb-3">
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>
                    <i class="bi bi-bar-chart-fill me-2 text-primary"></i>
                    Perbandingan Rencana vs Realisasi
                </span>
                <!-- Toggle tampilan -->
                <div class="btn-group btn-group-sm" id="chartToggle">
                    <button class="btn btn-primary active" data-mode="bulan">
                        <i class="bi bi-calendar3 me-1"></i>Per Bulan
                    </button>
                    <button class="btn btn-outline-primary" data-mode="jenis">
                        <i class="bi bi-tags me-1"></i>Per Jenis
                    </button>
                    <button class="btn btn-outline-primary" data-mode="metode">
                        <i class="bi bi-diagram-3 me-1"></i>Per Metode
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="chartUtama"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header" style="font-size:.875rem;">
                <i class="bi bi-pie-chart-fill me-2 text-success"></i>Distribusi Serapan
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="chartSerapan" style="max-height:220px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ====== TABEL DETAIL ====== -->
<div class="card">
    <div class="card-header" style="font-size:.875rem;">
        <i class="bi bi-table me-2 text-primary"></i>Detail Perbandingan Rencana vs Realisasi
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabelLaporan"
                   style="font-size:.875rem;">
                <thead>
                    <tr>
                        <th class="ps-3">No.</th>
                        <th>Nama Kegiatan</th>
                        <th>Jenis</th>
                        <th>Metode</th>
                        <th>Bulan</th>
                        <th class="text-end">Rencana (Vol)</th>
                        <th class="text-end">Realisasi (Vol)</th>
                        <th class="text-end">Anggaran</th>
                        <th class="text-end">Realisasi</th>
                        <th class="text-end">Selisih</th>
                        <th>% Serapan</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="11" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                            Belum ada data rencana untuk tahun <?= $tahun ?>.
                        </td>
                    </tr>
                <?php else: ?>
                <?php $no = 1; foreach ($rows as $r):
                    $selisih       = $r['total_realisasi'] - $r['nilai_anggaran'];
                    $persen        = $r['nilai_anggaran'] > 0
                                     ? round(($r['total_realisasi'] / $r['nilai_anggaran']) * 100, 1) : 0;
                    $selClass      = $selisih >= 0 ? 'danger' : 'success';
                    $progressColor = $persen >= 100 ? '#ef4444'
                                   : ($persen >= 70 ? '#22c55e'
                                   : ($persen >= 30 ? '#f59e0b' : '#94a3b8'));
                ?>
                <tr>
                    <td class="ps-3 text-muted"><?= $no++ ?></td>
                    <td class="fw-semibold"><?= sanitize($r['nama_kegiatan']) ?></td>
                    <td>
                        <span class="badge bg-light text-dark border" style="font-size:.72rem;">
                            <?= getLabelJenis($r['jenis_pengadaan']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border" style="font-size:.72rem;">
                            <?= getLabelMetode($r['metode_pengadaan'] ?? '') ?>
                        </span>
                    </td>
                    <td style="font-size:.82rem; color:#64748b;">
                        <?= formatBulanRencana($r['bulan_rencana'], true) ?>
                    </td>
                    <td class="text-end text-muted">
                        <?= formatAngka($r['volume']) ?> <?= sanitize($r['satuan']) ?>
                    </td>
                    <td class="text-end text-muted">
                        <?= $r['jml_realisasi'] > 0
                            ? formatAngka($r['vol_realisasi']) . ' ' . sanitize($r['satuan'])
                            : '<span class="text-muted">-</span>' ?>
                    </td>
                    <td class="text-end"><?= formatRupiah($r['nilai_anggaran']) ?></td>
                    <td class="text-end <?= $r['total_realisasi'] > 0 ? 'text-primary fw-semibold' : 'text-muted' ?>">
                        <?= $r['total_realisasi'] > 0 ? formatRupiah($r['total_realisasi']) : '-' ?>
                    </td>
                    <td class="text-end">
                        <?php if ($r['total_realisasi'] > 0): ?>
                            <span class="text-<?= $selClass ?> fw-semibold" style="font-size:.82rem;">
                                <?= ($selisih >= 0 ? '+' : '') . formatRupiah(abs($selisih)) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td style="min-width:120px;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height:5px;">
                                <div class="progress-bar"
                                     style="width:<?= min(100,$persen) ?>%; background:<?= $progressColor ?>;"></div>
                            </div>
                            <span style="font-size:.75rem; min-width:38px; color:#64748b;">
                                <?= $persen ?>%
                            </span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td colspan="7" class="text-end ps-3" style="font-size:.82rem;">Total:</td>
                        <td class="text-end text-primary"><?= formatRupiah($totalRencana) ?></td>
                        <td class="text-end text-success"><?= formatRupiah($totalRealisasi) ?></td>
                        <td class="text-end <?= ($totalRealisasi-$totalRencana) >= 0 ? 'text-danger' : 'text-success' ?>">
                            <?= (($totalRealisasi-$totalRencana) >= 0 ? '+' : '')
                                . formatRupiah(abs($totalRealisasi-$totalRencana)) ?>
                        </td>
                        <td style="font-size:.82rem;"><?= $persenSerapan ?>%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php
// ================================================================
// JavaScript untuk Chart dengan Toggle
// ================================================================
$extraJS = '<script>
$(document).ready(function() {

    // DataTable
    $("#tabelLaporan").DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json" },
        dom: "t",
        paging: false,
        columnDefs: [{ orderable: false, targets: [10] }],
        order: [[4, "asc"]]
    });

    // ===== DATA UNTUK SEMUA MODE =====
    var chartData = {
        bulan: {
            labels:    ' . $jsonBulanLabels . ',
            rencana:   ' . $jsonBulanRencana . ',
            realisasi: ' . $jsonBulanRealisasi . '
        },
        jenis: {
            labels:    ' . $jsonJenisLabels . ',
            rencana:   ' . $jsonJenisRencana . ',
            realisasi: ' . $jsonJenisRealisasi . '
        },
        metode: {
            labels:    ' . $jsonMetodeLabels . ',
            rencana:   ' . $jsonMetodeRencana . ',
            realisasi: ' . $jsonMetodeRealisasi . '
        }
    };

    // ===== BAR CHART UTAMA =====
    var ctxUtama = document.getElementById("chartUtama").getContext("2d");
    var chartUtama = new Chart(ctxUtama, {
        type: "bar",
        data: {
            labels: chartData.bulan.labels,
            datasets: [
                {
                    label: "Rencana",
                    data: chartData.bulan.rencana,
                    backgroundColor: "rgba(30,96,145,0.8)",
                    borderRadius: 5,
                    borderSkipped: false
                },
                {
                    label: "Realisasi",
                    data: chartData.bulan.realisasi,
                    backgroundColor: "rgba(46,196,182,0.8)",
                    borderRadius: 5,
                    borderSkipped: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { 
                legend: { position: "top", labels: { font: { size: 12 } } } 
            },
            scales: {
                y: {
                    ticks: {
                        callback: function(v) {
                            return "Rp " + (v / 1000000).toFixed(0) + " jt";
                        },
                        font: { size: 11 }
                    },
                    grid: { color: "rgba(0,0,0,0.05)" }
                },
                x: { ticks: { font: { size: 11 } } }
            }
        }
    });

    // ===== FUNGSI GANTI MODE CHART =====
    function gantiModeChart(mode) {
        var d = chartData[mode];
        chartUtama.data.labels = d.labels;
        chartUtama.data.datasets[0].data = d.rencana;
        chartUtama.data.datasets[1].data = d.realisasi;
        chartUtama.update();
    }

    // ===== TOGGLE BUTTON =====
    document.querySelectorAll("#chartToggle button").forEach(function(btn) {
        btn.addEventListener("click", function() {
            document.querySelectorAll("#chartToggle button").forEach(function(b) {
                b.classList.remove("btn-primary", "active");
                b.classList.add("btn-outline-primary");
            });
            this.classList.remove("btn-outline-primary");
            this.classList.add("btn-primary", "active");
            gantiModeChart(this.dataset.mode);
        });
    });

    // ===== CHART DONUT =====
    var ctxDonut = document.getElementById("chartSerapan");
    if (ctxDonut && typeof Chart !== "undefined") {
        new Chart(ctxDonut, {
            type: "doughnut",
            data: {
                labels: ["Terealisasi", "Sisa Rencana", "Di luar Rencana"],
                datasets: [{
                    data: [' . (float)$totalRealisasi . ', ' . (float)$sisaAnggaran . ', ' . (float)$totalLuar . '],
                    backgroundColor: ["#2ec4b6", "#e2e8f0", "#f8961e"],
                    borderWidth: 3,
                    borderColor: "#fff",
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                cutout: "65%",
                plugins: {
                    legend: { position: "bottom", labels: { font: { size: 12 }, padding: 16 } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return " " + ctx.label + ": Rp " + (ctx.parsed / 1000000).toFixed(1) + " jt";
                            }
                        }
                    }
                }
            }
        });
    }

});
</script>';

include __DIR__ . '/../../includes/footer.php';
?>