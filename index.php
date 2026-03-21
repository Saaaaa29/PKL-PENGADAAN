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

// ── RBAC ────────────────────────────────────────────────────────────────────
$role   = $_SESSION['user_role'] ?? 'staf_pengadaan';
$uid    = (int)($_SESSION['user_id'] ?? 0);
$isMgr  = in_array($role, ['admin', 'manajer_pengadaan']);
$isStaf = $role === 'staf_pengadaan';

// ── Data staf: inputan milik sendiri ─────────────────────────────────────
$stafStats   = [];
$recentMine  = null;
if ($isStaf) {
    $qS = $db->query("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status_verifikasi='menunggu'  THEN 1 ELSE 0 END) as menunggu,
            SUM(CASE WHEN status_verifikasi='disetujui' THEN 1 ELSE 0 END) as disetujui,
            SUM(CASE WHEN status_verifikasi='ditolak'   THEN 1 ELSE 0 END) as ditolak,
            SUM(total_nilai) as total_nilai
        FROM realisasi_kegiatan
        WHERE created_by = $uid AND YEAR(tanggal_mulai) = $tahun
    ");
    $stafStats  = $qS ? $qS->fetch_assoc() : [];
    $recentMine = $db->query("
        SELECT * FROM realisasi_kegiatan
        WHERE created_by = $uid
        ORDER BY created_at DESC LIMIT 5
    ");
}

// ── Pending verifikasi untuk manajer ─────────────────────────────────────
$pendingVerif = 0;
if ($isMgr) {
    $qP = $db->query("SELECT COUNT(*) as c FROM realisasi_kegiatan WHERE status_verifikasi='menunggu'");
    $pendingVerif = $qP ? (int)$qP->fetch_assoc()['c'] : 0;
}

// ── Data chart & rekap — hanya dipakai manajer ───────────────────────────
$chartBulanRencana = $chartBulanRealisasi = [];
$chartJenisLabels  = $chartJenisRencana   = $chartJenisRealisasi  = [];
$chartMetodeLabels = $chartMetodeRencana  = $chartMetodeRealisasi = [];
$rekapJenis = [];
$recentReal = null;

if ($isMgr) {
    $cekKolom  = $db->query("SHOW COLUMNS FROM rencana_kegiatan LIKE 'bulan_rencana'");
    $infoKolom = $cekKolom ? $cekKolom->fetch_assoc() : null;
    $isVarchar = $infoKolom && stripos($infoKolom['Type'], 'varchar') !== false;

    for ($i = 1; $i <= 12; $i++) {
        if ($isVarchar) {
            $q = $db->query("SELECT SUM(nilai_anggaran) as total FROM rencana_kegiatan
                             WHERE tahun = $tahun AND FIND_IN_SET($i, bulan_rencana)");
        } else {
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

    foreach (LABEL_METODE as $key => $label) {
        $q  = $db->query("SELECT SUM(nilai_anggaran) as tot FROM rencana_kegiatan
                         WHERE tahun=$tahun AND metode_pengadaan='$key'");
        $q2 = $db->query("SELECT SUM(total_nilai) as tot FROM realisasi_kegiatan
                          WHERE YEAR(tanggal_mulai)=$tahun AND metode_pengadaan='$key'");
        $chartMetodeRencana[]  = $q  ? (float)($q->fetch_assoc()['tot']  ?? 0) : 0;
        $chartMetodeRealisasi[]= $q2 ? (float)($q2->fetch_assoc()['tot'] ?? 0) : 0;
        $chartMetodeLabels[]   = $label;
    }

    foreach (LABEL_JENIS as $key => $label) {
        $q  = $db->query("SELECT SUM(nilai_anggaran) as rencana FROM rencana_kegiatan
                          WHERE tahun=$tahun AND jenis_pengadaan='$key'");
        $q2 = $db->query("SELECT SUM(rd.nilai_anggaran) as realisasi FROM realisasi_detail rd
                          JOIN realisasi_kegiatan r ON r.id=rd.realisasi_id
                          WHERE YEAR(r.tanggal_mulai)=$tahun AND rd.jenis_pengadaan='$key'");
        $rencana   = (float)($q->fetch_assoc()['rencana']   ?? 0);
        $realisasi = (float)($q2->fetch_assoc()['realisasi'] ?? 0);
        $rekapJenis[$key] = [
            'label'     => $label,
            'rencana'   => $rencana,
            'realisasi' => $realisasi,
            'persen'    => $rencana > 0 ? round(($realisasi / $rencana) * 100, 1) : 0,
        ];
    }

    $recentReal = $db->query("SELECT r.*, u.nama_lengkap
                               FROM realisasi_kegiatan r
                               LEFT JOIN users u ON u.id = r.created_by
                               WHERE YEAR(r.tanggal_mulai) = $tahun
                               ORDER BY r.created_at DESC LIMIT 5");

    $jenisLabels    = array_column(array_values($rekapJenis), 'label');
    $jenisRealisasi = array_column(array_values($rekapJenis), 'realisasi');
    if (array_sum($jenisRealisasi) == 0) {
        $jenisLabels    = ['Belum ada data'];
        $jenisRealisasi = [1];
    }

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
}

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<!-- ══ HEADER FILTER TAHUN ════════════════════════════════════════════════ -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <?php if ($isMgr): ?>
        <h5 class="fw-bold mb-0">Ringkasan Pengadaan</h5>
        <small class="text-muted">Data tahun <?= $tahun ?></small>
        <?php else: ?>
        <h5 class="fw-bold mb-0">
            Selamat datang, <?= sanitize($_SESSION['user_nama'] ?? 'Staf') ?> 👋
        </h5>
        <small class="text-muted">Realisasi yang Anda input — tahun <?= $tahun ?></small>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap no-print">
        <?php if ($isMgr && $pendingVerif > 0): ?>
        <a href="<?= BASE_URL ?>/modules/realisasi/verifikasi.php"
           class="btn btn-warning btn-sm">
            <i class="bi bi-shield-check me-1"></i><?= $pendingVerif ?> Menunggu Verifikasi
        </a>
        <?php endif; ?>
        <?php if ($isStaf): ?>
        <a href="<?= BASE_URL ?>/modules/realisasi/form.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Tambah Realisasi
        </a>
        <?php endif; ?>
        <form method="GET">
            <input type="number" name="tahun"
                   value="<?= $tahun ?>"
                   min="2000"
                   class="form-control form-control-sm text-center"
                   style="width:82px;"
                   placeholder="Tahun">
        </form>
    </div>
</div>

<?php if ($isStaf): ?>
<!-- ════════════════════════════════════════════════════════════════════════
     TAMPILAN STAF PENGADAAN
     ════════════════════════════════════════════════════════════════════════ -->

<!-- Alert jika ada yang ditolak -->
<?php if ((int)($stafStats['ditolak'] ?? 0) > 0): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 mb-4 py-2">
    <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
    <div>
        <strong><?= $stafStats['ditolak'] ?> realisasi Anda ditolak</strong> oleh Manajer Pengadaan.
        <a href="<?= BASE_URL ?>/modules/realisasi/index.php?verif=ditolak"
           class="alert-link ms-2">Lihat & Perbaiki →</a>
    </div>
</div>
<?php endif; ?>

<!-- Stat cards staf -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card bg-blue">
            <div class="stat-value"><?= (int)($stafStats['total'] ?? 0) ?></div>
            <div class="stat-label">Total Realisasi Saya</div>
            <i class="bi bi-file-earmark-text stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card bg-orange">
            <div class="stat-value"><?= (int)($stafStats['menunggu'] ?? 0) ?></div>
            <div class="stat-label">Menunggu Verifikasi</div>
            <i class="bi bi-hourglass-split stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card bg-green">
            <div class="stat-value"><?= (int)($stafStats['disetujui'] ?? 0) ?></div>
            <div class="stat-label">Disetujui Manajer</div>
            <i class="bi bi-check2-circle stat-icon"></i>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card bg-purple">
            <div class="stat-value"><?= formatRupiah($stafStats['total_nilai'] ?? 0) ?></div>
            <div class="stat-label">Total Nilai Diinput</div>
            <i class="bi bi-cash-stack stat-icon"></i>
        </div>
    </div>
</div>

<!-- Akses cepat -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <a href="<?= BASE_URL ?>/modules/realisasi/form.php"
           class="card text-decoration-none h-100 border-primary border-2">
            <div class="card-body text-center py-4">
                <i class="bi bi-plus-circle-fill text-primary fs-2 mb-2 d-block"></i>
                <div class="fw-bold text-primary">Tambah Realisasi</div>
                <small class="text-muted">Input data realisasi baru</small>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="<?= BASE_URL ?>/modules/realisasi/index.php?verif=menunggu"
           class="card text-decoration-none h-100">
            <div class="card-body text-center py-4">
                <i class="bi bi-hourglass-split text-warning fs-2 mb-2 d-block"></i>
                <div class="fw-bold">Menunggu Verifikasi</div>
                <small class="text-muted"><?= (int)($stafStats['menunggu'] ?? 0) ?> realisasi</small>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="<?= BASE_URL ?>/modules/realisasi/index.php?verif=disetujui"
           class="card text-decoration-none h-100">
            <div class="card-body text-center py-4">
                <i class="bi bi-check-circle-fill text-success fs-2 mb-2 d-block"></i>
                <div class="fw-bold">Disetujui</div>
                <small class="text-muted"><?= (int)($stafStats['disetujui'] ?? 0) ?> realisasi</small>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="<?= BASE_URL ?>/modules/rencana/index.php"
           class="card text-decoration-none h-100">
            <div class="card-body text-center py-4">
                <i class="bi bi-journal-text text-info fs-2 mb-2 d-block"></i>
                <div class="fw-bold">Rencana Kegiatan</div>
                <small class="text-muted">Lihat daftar rencana</small>
            </div>
        </a>
    </div>
</div>

<!-- Realisasi terbaru milik staf -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2 text-primary"></i>Realisasi Terbaru Saya</span>
        <a href="<?= BASE_URL ?>/modules/realisasi/index.php"
           class="btn btn-sm btn-outline-primary">Lihat Semua</a>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
        <?php
        $hasStafReal = false;
        if ($recentMine) while ($rm = $recentMine->fetch_assoc()):
            $hasStafReal = true;
            $vs = $rm['status_verifikasi'] ?? 'menunggu';
            $verifStyle = match($vs) {
                'menunggu'  => 'background:#fef3c7;color:#92400e;border:1px solid #fbbf24;',
                'disetujui' => 'background:#d1fae5;color:#065f46;border:1px solid #34d399;',
                'ditolak'   => 'background:#fee2e2;color:#991b1b;border:1px solid #f87171;',
                default     => '',
            };
        ?>
        <a href="<?= BASE_URL ?>/modules/realisasi/detail.php?id=<?= $rm['id'] ?>"
           class="list-group-item list-group-item-action px-3 py-3"
           style="<?= $vs==='ditolak' ? 'background:#fff1f2;' : ($vs==='menunggu' ? 'background:#fffbeb;' : '') ?>">
            <div class="d-flex justify-content-between align-items-start">
                <div class="overflow-hidden">
                    <div class="fw-semibold text-truncate" style="max-width:220px;font-size:.875rem;">
                        <?= sanitize($rm['nomor_kontrak'] ?: 'Tanpa No. Kontrak') ?>
                    </div>
                    <small class="text-muted">
                        <?= date('d M Y', strtotime($rm['tanggal_mulai'])) ?>
                        &bull; <?= getLabelMetode($rm['metode_pengadaan']) ?>
                    </small>
                </div>
                <div class="d-flex flex-column align-items-end gap-1 ms-2">
                    <span class="badge <?= $rm['status']==='selesai' ? 'bg-success' : ($rm['status']==='batal' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                        <?= ucfirst($rm['status']) ?>
                    </span>
                    <span class="badge" style="font-size:10px;<?= $verifStyle ?>">
                        <?= match($vs){
                            'menunggu' =>'⏳ Menunggu',
                            'disetujui'=>'✓ Disetujui',
                            'ditolak'  =>'✗ Ditolak',
                            default    => $vs,
                        } ?>
                    </span>
                </div>
            </div>
            <div class="text-primary fw-semibold mt-1" style="font-size:.82rem;">
                <?= formatRupiah($rm['total_nilai']) ?>
            </div>
        </a>
        <?php endwhile; ?>
        <?php if (!$hasStafReal): ?>
        <div class="p-4 text-center text-muted">
            <i class="bi bi-inbox d-block fs-2 mb-2 opacity-40"></i>
            Belum ada realisasi yang Anda input.
            <div class="mt-2">
                <a href="<?= BASE_URL ?>/modules/realisasi/form.php"
                   class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Sekarang
                </a>
            </div>
        </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ════════════════════════════════════════════════════════════════════════
     TAMPILAN MANAJER / ADMIN  (identik dengan dashboard asli Anda)
     ════════════════════════════════════════════════════════════════════════ -->

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
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>
                    <i class="bi bi-bar-chart me-2 text-primary"></i>
                    Perbandingan Rencana vs Realisasi
                </span>
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

<!-- REKAP JENIS + REALISASI TERBARU -->
<div class="row g-3 mb-4">
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
        <!-- Vendor Ranking -->
        <div class="mt-3">
            <?php include __DIR__ . '/includes/widgets/vendor_ranking.php'; ?>
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
                            <span class="badge ms-2 <?= $r['status']==='selesai' ? 'bg-success' : ($r['status']==='batal' ? 'bg-danger' : 'bg-warning text-dark') ?>">
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

<?php endif; // end else (manajer) ?>

<?php
// $extraJS sudah di-append oleh vendor_ranking.php (jika ada)
// Script chart hanya untuk manajer/admin
if ($isMgr):
$extraJS = ($extraJS ?? '') . '<script>
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

document.querySelectorAll("#chartToggle button").forEach(function(btn) {
    btn.addEventListener("click", function() {
        document.querySelectorAll("#chartToggle button").forEach(function(b) {
            b.classList.remove("btn-primary", "active");
            b.classList.add("btn-outline-primary");
        });
        this.classList.remove("btn-outline-primary");
        this.classList.add("btn-primary", "active");
        var d = chartData[this.dataset.mode];
        chartUtama.data.labels            = d.labels;
        chartUtama.data.datasets[0].data  = d.rencana;
        chartUtama.data.datasets[1].data  = d.realisasi;
        chartUtama.update();
    });
});

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
endif; // isMgr

include __DIR__ . '/includes/footer.php';
?>