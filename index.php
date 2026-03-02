<?php
/**
 * index.php - Dashboard Utama
 */

session_start();
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$db    = getDB();
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$stats = getDashboardStats($tahun);

// -------------------------------------------------------
// Data chart: per BULAN
// Gunakan FIND_IN_SET untuk VARCHAR, fallback = untuk TINYINT
// -------------------------------------------------------
$chartBulanRencana   = [];
$chartBulanRealisasi = [];

// Cek tipe kolom bulan_rencana
$cekKolom   = $db->query("SHOW COLUMNS FROM rencana_kegiatan LIKE 'bulan_rencana'");
$infoKolom  = $cekKolom ? $cekKolom->fetch_assoc() : null;
$isVarchar  = $infoKolom && stripos($infoKolom['Type'], 'varchar') !== false;

for ($i = 1; $i <= 12; $i++) {
    if ($isVarchar) {
        $q = $db->query("SELECT SUM(nilai_anggaran) as total FROM rencana_kegiatan
                         WHERE tahun = $tahun AND FIND_IN_SET($i, bulan_rencana)");
    } else {
        // Kolom masih TINYINT (belum migrasi)
        $q = $db->query("SELECT SUM(nilai_anggaran) as total FROM rencana_kegiatan
                         WHERE tahun = $tahun AND bulan_rencana = $i");
    }
    $chartBulanRencana[] = ($q && $row = $q->fetch_assoc()) ? (float)($row['total'] ?? 0) : 0;

    $q2 = $db->query("SELECT SUM(rd.nilai_anggaran) as total
                      FROM realisasi_detail rd
                      JOIN realisasi_kegiatan r ON r.id = rd.realisasi_id
                      WHERE YEAR(r.tanggal_mulai) = $tahun AND MONTH(r.tanggal_mulai) = $i");
    $chartBulanRealisasi[] = ($q2 && $row2 = $q2->fetch_assoc()) ? (float)($row2['total'] ?? 0) : 0;
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
// metode ada di rencana_kegiatan & realisasi_kegiatan (bukan realisasi_detail)
// -------------------------------------------------------
$chartMetodeLabels    = [];
$chartMetodeRencana   = [];
$chartMetodeRealisasi = [];
foreach (LABEL_METODE as $key => $label) {
    $q = $db->query("SELECT SUM(nilai_anggaran) as tot FROM rencana_kegiatan
                     WHERE tahun=$tahun AND metode_pengadaan='$key'");
    if (!$q) { $chartMetodeRencana[] = 0; }
    else      { $chartMetodeRencana[] = (float)($q->fetch_assoc()['tot'] ?? 0); }

    $q2 = $db->query("SELECT SUM(total_nilai) as tot FROM realisasi_kegiatan
                      WHERE YEAR(tanggal_mulai)=$tahun AND metode_pengadaan='$key'");
    if (!$q2) { $chartMetodeRealisasi[] = 0; }
    else       { $chartMetodeRealisasi[] = (float)($q2->fetch_assoc()['tot'] ?? 0); }

    $chartMetodeLabels[] = $label;
}

// -------------------------------------------------------
// Rekap per jenis (untuk tabel & donut)
// -------------------------------------------------------
$rekapJenis = [];
foreach (LABEL_JENIS as $key => $label) {
    $q  = $db->query("SELECT SUM(nilai_anggaran) as rencana FROM rencana_kegiatan
                      WHERE tahun=$tahun AND jenis_pengadaan='$key'");
    $q2 = $db->query("SELECT SUM(rd.nilai_anggaran) as realisasi FROM realisasi_detail rd
                      JOIN realisasi_kegiatan r ON r.id=rd.realisasi_id
                      WHERE YEAR(r.tanggal_mulai)=$tahun AND rd.jenis_pengadaan='$key'");
    $rencana   = (float)($q->fetch_assoc()['rencana'] ?? 0);
    $realisasi = (float)($q2->fetch_assoc()['realisasi'] ?? 0);
    $rekapJenis[$key] = [
        'label'     => $label,
        'rencana'   => $rencana,
        'realisasi' => $realisasi,
        'persen'    => $rencana > 0 ? round(($realisasi / $rencana) * 100, 1) : 0,
    ];
}

// Realisasi terbaru
$recentReal = $db->query("SELECT r.*, u.nama_lengkap
                           FROM realisasi_kegiatan r
                           LEFT JOIN users u ON u.id = r.created_by
                           WHERE YEAR(r.tanggal_mulai) = $tahun
                           ORDER BY r.created_at DESC LIMIT 5");

// Donut data
$jenisLabels    = array_column(array_values($rekapJenis), 'label');
$jenisRealisasi = array_column(array_values($rekapJenis), 'realisasi');
if (array_sum($jenisRealisasi) == 0) {
    $jenisLabels    = ['Belum ada data'];
    $jenisRealisasi = [1];
}

// Encode semua JSON
$jsonBulanLabels      = json_encode(array_values(NAMA_BULAN));
$jsonBulanRencana     = json_encode($chartBulanRencana);
$jsonBulanRealisasi   = json_encode($chartBulanRealisasi);
$jsonJenisLabels      = json_encode($chartJenisLabels);
$jsonJenisRencana     = json_encode($chartJenisRencana);
$jsonJenisRealisasi   = json_encode($chartJenisRealisasi);
$jsonMetodeLabels     = json_encode($chartMetodeLabels);
$jsonMetodeRencana    = json_encode($chartMetodeRencana);
$jsonMetodeRealisasi  = json_encode($chartMetodeRealisasi);
$jsonDonutLabels      = json_encode($jenisLabels);
$jsonDonutData        = json_encode($jenisRealisasi);

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<!-- Filter Tahun -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Ringkasan Pengadaan</h5>
        <small class="text-muted">Data tahun <?= $tahun ?></small>
    </div>
    <form method="GET" class="d-flex gap-2 no-print">
                <!-- Tahun: input bebas, tak terbatas -->
                <input type="number" name="tahun"
                       value="<?= $tahun ?>"
                       min="2000"
                       class="form-control form-control-sm text-center"
                       style="width:82px;"
                       placeholder="Tahun">
    </form>
</div>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card bg-blue">
            <div class="stat-value"><?= formatRupiah($stats['total_rencana']) ?></div>
            <div class="stat-label">Total Anggaran Rencana</div>
            <i class="bi bi-journal-text stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card bg-green">
            <div class="stat-value"><?= formatRupiah($stats['total_realisasi']) ?></div>
            <div class="stat-label">Total Realisasi</div>
            <i class="bi bi-check2-circle stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card bg-orange">
            <div class="stat-value"><?= $stats['persen_serapan'] ?>%</div>
            <div class="stat-label">Persentase Serapan</div>
            <i class="bi bi-pie-chart stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card bg-purple">
            <div class="stat-value"><?= $stats['jumlah_rencana'] ?> / <?= $stats['jumlah_realisasi'] ?></div>
            <div class="stat-label">Rencana / Realisasi Kegiatan</div>
            <i class="bi bi-list-check stat-icon"></i>
        </div>
    </div>
</div>

<!-- GRAFIK -->
<div class="row g-3 mb-4">
    <!-- Bar chart dengan toggle -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>
                    <i class="bi bi-bar-chart me-2 text-primary"></i>
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
                <canvas id="chartUtama" height="110"></canvas>
            </div>
        </div>
    </div>

    <!-- Donut -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-pie-chart me-2 text-primary"></i>Komposisi per Jenis Pengadaan
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="chartJenis" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- REKAP + REALISASI TERBARU -->
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-table me-2 text-primary"></i>Rekap per Jenis Pengadaan
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0" style="font-size:.875rem;">
                    <thead>
                        <tr>
                            <th class="ps-3">Jenis Pengadaan</th>
                            <th class="text-end">Rencana</th>
                            <th class="text-end">Realisasi</th>
                            <th>Serapan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rekapJenis as $jenis): ?>
                        <tr>
                            <td class="ps-3 fw-semibold"><?= $jenis['label'] ?></td>
                            <td class="text-end text-muted"><?= formatRupiah($jenis['rencana']) ?></td>
                            <td class="text-end text-primary fw-semibold"><?= formatRupiah($jenis['realisasi']) ?></td>
                            <td style="min-width:130px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:6px;">
                                        <div class="progress-bar bg-success"
                                             style="width:<?= min(100,$jenis['persen']) ?>%"></div>
                                    </div>
                                    <span class="small fw-semibold" style="min-width:38px;">
                                        <?= $jenis['persen'] ?>%
                                    </span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-2 text-primary"></i>Realisasi Terbaru</span>
                <a href="<?= BASE_URL ?>/modules/realisasi/index.php"
                   class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php
                    $hasRecent = false;
                    while ($r = $recentReal->fetch_assoc()):
                        $hasRecent = true;
                    ?>
                    <a href="<?= BASE_URL ?>/modules/realisasi/detail.php?id=<?= $r['id'] ?>"
                       class="list-group-item list-group-item-action px-3 py-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="overflow-hidden">
                                <div class="fw-semibold text-truncate" style="max-width:200px; font-size:.875rem;">
                                    <?= sanitize($r['nomor_kontrak'] ?: 'Tanpa No. Kontrak') ?>
                                </div>
                                <small class="text-muted">
                                    <?= date('d M Y', strtotime($r['tanggal_mulai'])) ?>
                                    &bull; <?= getLabelMetode($r['metode_pengadaan']) ?>
                                </small>
                            </div>
                            <span class="badge ms-2 <?= $r['status'] === 'selesai' ? 'bg-success' : ($r['status'] === 'batal' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                                <?= ucfirst($r['status']) ?>
                            </span>
                        </div>
                        <div class="text-primary fw-semibold mt-1" style="font-size:.82rem;">
                            <?= formatRupiah($r['total_nilai']) ?>
                        </div>
                    </a>
                    <?php endwhile; ?>
                    <?php if (!$hasRecent): ?>
                        <div class="p-4 text-center text-muted">Belum ada realisasi</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraJS = '<script>
// ===== DATA SEMUA MODE =====
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
                backgroundColor: "rgba(30,96,145,0.75)",
                borderRadius: 5,
                borderSkipped: false
            },
            {
                label: "Realisasi",
                data: chartData.bulan.realisasi,
                backgroundColor: "rgba(46,196,182,0.75)",
                borderRadius: 5,
                borderSkipped: false
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: "top", labels: { font: { size: 12 } } } },
        scales: {
            y: {
                ticks: {
                    callback: function(v) {
                        return "Rp " + (v / 1000000).toFixed(0) + " jt";
                    },
                    font: { size: 11 }
                },
                grid: { color: "rgba(0,0,0,0.04)" }
            },
            x: { ticks: { font: { size: 11 } } }
        }
    }
});

// ===== FUNGSI GANTI MODE CHART =====
function gantiModeChart(mode) {
    var d = chartData[mode];
    chartUtama.data.labels            = d.labels;
    chartUtama.data.datasets[0].data  = d.rencana;
    chartUtama.data.datasets[1].data  = d.realisasi;
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

// ===== DONUT CHART =====
var ctxJenis = document.getElementById("chartJenis").getContext("2d");
new Chart(ctxJenis, {
    type: "doughnut",
    data: {
        labels: ' . $jsonDonutLabels . ',
        datasets: [{
            data: ' . $jsonDonutData . ',
            backgroundColor: ["#1e6091","#2ec4b6","#f8961e","#7b2d8b","#e76f51"],
            borderWidth: 2,
            borderColor: "#fff",
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "60%",
        plugins: {
            legend: { position: "bottom", labels: { font: { size: 11 }, padding: 12 } },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        return " " + ctx.label + ": Rp " + (ctx.raw / 1000000).toFixed(1) + " jt";
                    }
                }
            }
        }
    }
});
</script>';

include __DIR__ . '/includes/footer.php';
?>