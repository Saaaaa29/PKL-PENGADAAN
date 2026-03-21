<?php
/**
 * includes/widgets/vendor_ranking.php
 * Widget: Ranking Vendor Berdasarkan Frekuensi Keterlibatan
 * Di-include dari halaman dashboard
 *
 * Variabel yang dibutuhkan dari parent:
 *   $db     — koneksi mysqli
 *   $tahun  — tahun filter (opsional, default tahun berjalan)
 */

$tahunVendor = $tahun ?? (int)date('Y');

// ── Query ranking vendor ─────────────────────────────────────
$qVendor = $db->query("
    SELECT
        v.nama_vendor,
        COUNT(DISTINCT v.realisasi_id)   AS jumlah_kontrak,
        SUM(v.nilai_kontrak)             AS total_nilai,
        MIN(r.tanggal_mulai)             AS tgl_pertama,
        MAX(r.tanggal_mulai)             AS tgl_terakhir
    FROM realisasi_vendor v
    JOIN realisasi_kegiatan r ON r.id = v.realisasi_id
    WHERE YEAR(r.tanggal_mulai) = $tahunVendor
      AND r.status != 'batal'
    GROUP BY v.nama_vendor
    ORDER BY jumlah_kontrak DESC, total_nilai DESC
    LIMIT 10
");

$vendorRanking = [];
if ($qVendor !== false) {
    while ($row = $qVendor->fetch_assoc()) $vendorRanking[] = $row;
}

// Nilai max untuk bar width
$maxKontrak = !empty($vendorRanking) ? (int)$vendorRanking[0]['jumlah_kontrak'] : 1;

// Total semua kontrak tahun ini (untuk persentase)
$qTotal = $db->query("
    SELECT COUNT(DISTINCT v.realisasi_id) AS total
    FROM realisasi_vendor v
    JOIN realisasi_kegiatan r ON r.id = v.realisasi_id
    WHERE YEAR(r.tanggal_mulai) = $tahunVendor
      AND r.status != 'batal'
");
$totalKontrakTahun = ($qTotal !== false) ? (int)($qTotal->fetch_assoc()['total'] ?? 1) : 1;

// Data untuk Chart.js
$chartLabels = [];
$chartData   = [];
$chartColors = [
    '#4361ee','#3a0ca3','#7209b7','#f72585','#4cc9f0',
    '#4895ef','#560bad','#b5179e','#f77f00','#2ec4b6',
];
foreach (array_slice($vendorRanking, 0, 7) as $v) {
    $chartLabels[] = mb_strlen($v['nama_vendor']) > 20
        ? mb_substr($v['nama_vendor'], 0, 18) . '…'
        : $v['nama_vendor'];
    $chartData[] = (int)$v['jumlah_kontrak'];
}
?>

<div class="card h-100" id="cardVendorRanking">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <i class="bi bi-building-check me-2 text-primary"></i>
            <strong>Ranking Vendor</strong>
            <span class="badge bg-primary ms-1"><?= $tahunVendor ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <small class="text-muted"><?= count($vendorRanking) ?> vendor aktif</small>
            <div class="btn-group btn-group-sm" id="vendorViewToggle">
                <button class="btn btn-outline-primary active" data-view="chart" title="Chart">
                    <i class="bi bi-bar-chart-fill"></i>
                </button>
                <button class="btn btn-outline-primary" data-view="table" title="Tabel">
                    <i class="bi bi-list-ol"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="card-body">
        <?php if (empty($vendorRanking)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-building fs-2 d-block mb-2 opacity-50"></i>
                <small>Belum ada data vendor untuk tahun <?= $tahunVendor ?></small>
            </div>
        <?php else: ?>

            <!-- ── CHART VIEW ── -->
            <div id="vendorChartView">
                <canvas id="chartVendorRanking" style="max-height:260px;"></canvas>
            </div>

            <!-- ── TABLE VIEW (tersembunyi default) ── -->
            <div id="vendorTableView" style="display:none;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:32px;">#</th>
                                <th>Nama Vendor</th>
                                <th class="text-center">Kontrak</th>
                                <th class="text-end">Total Nilai</th>
                                <th class="text-center">Keterlibatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vendorRanking as $i => $v):
                                $pct      = $totalKontrakTahun > 0
                                    ? round(($v['jumlah_kontrak'] / $totalKontrakTahun) * 100)
                                    : 0;
                                $barWidth = $maxKontrak > 0
                                    ? round(($v['jumlah_kontrak'] / $maxKontrak) * 100)
                                    : 0;
                                $medal    = match($i) {
                                    0 => '<span title="Peringkat 1">🥇</span>',
                                    1 => '<span title="Peringkat 2">🥈</span>',
                                    2 => '<span title="Peringkat 3">🥉</span>',
                                    default => '<span class="text-muted fw-semibold">' . ($i + 1) . '</span>',
                                };
                                $colorIdx = $i % count($chartColors);
                                $barColor = $chartColors[$colorIdx];
                            ?>
                            <tr>
                                <td class="text-center"><?= $medal ?></td>
                                <td>
                                    <div class="fw-semibold" style="font-size:.83rem;line-height:1.2;">
                                        <?= sanitize($v['nama_vendor']) ?>
                                    </div>
                                    <!-- Mini progress bar -->
                                    <div class="mt-1" style="height:4px;background:#e9ecef;border-radius:2px;">
                                        <div style="width:<?= $barWidth ?>%;height:4px;background:<?= $barColor ?>;border-radius:2px;transition:width .4s ease;"></div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill"
                                          style="background:<?= $barColor ?>;font-size:.75rem;">
                                        <?= $v['jumlah_kontrak'] ?>x
                                    </span>
                                </td>
                                <td class="text-end text-primary fw-semibold" style="font-size:.8rem;">
                                    <?= formatRupiah($v['total_nilai']) ?>
                                </td>
                                <td class="text-center">
                                    <small class="text-muted"><?= $pct ?>%</small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2" class="text-end text-muted small">
                                    Total (<?= $totalKontrakTahun ?> kontrak):
                                </td>
                                <td colspan="3" class="text-primary fw-bold" style="font-size:.82rem;">
                                    <?= formatRupiah(array_sum(array_column($vendorRanking, 'total_nilai'))) ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <?php if (!empty($vendorRanking)): ?>
    <div class="card-footer py-1 px-3 d-flex justify-content-between align-items-center">
        <small class="text-muted">
            <?= $vendorRanking[0]['nama_vendor'] ?? '' ?> paling sering terlibat
            (<?= $vendorRanking[0]['jumlah_kontrak'] ?? 0 ?>x kontrak)
        </small>
        <a href="<?= BASE_URL ?>/modules/vendor/index.php?tahun=<?= $tahunVendor ?>"
           class="btn btn-xs btn-link text-primary p-0" style="font-size:.78rem;">
            Lihat semua →
        </a>
    </div>
    <?php endif; ?>
</div>

<?php
// Tambahkan script Chart.js ke antrian $extraJS
// Pastikan Chart.js sudah di-load di header/footer template
$vendorChartScript = "
<script>
(function() {
    var ctx = document.getElementById('chartVendorRanking');
    if (!ctx) return;

    var labels = " . json_encode($chartLabels) . ";
    var data   = " . json_encode($chartData) . ";
    var colors = " . json_encode(array_slice($chartColors, 0, count($chartData))) . ";

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Jumlah Kontrak',
                data: data,
                backgroundColor: colors,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ' ' + ctx.parsed.y + ' kontrak';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: { size: 11 }
                    },
                    grid: { color: '#f1f5f9' }
                },
                x: {
                    ticks: {
                        font: { size: 10 },
                        maxRotation: 30,
                        minRotation: 0
                    },
                    grid: { display: false }
                }
            }
        }
    });

    // Toggle chart ↔ tabel
    document.querySelectorAll('#vendorViewToggle button').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#vendorViewToggle button')
                    .forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            var view = this.dataset.view;
            document.getElementById('vendorChartView').style.display  = view === 'chart' ? '' : 'none';
            document.getElementById('vendorTableView').style.display  = view === 'table' ? '' : 'none';
        });
    });
})();
</script>
";

// Append ke $extraJS jika sudah ada, atau buat baru
if (isset($extraJS)) {
    $extraJS .= $vendorChartScript;
} else {
    $extraJS = $vendorChartScript;
}
?>