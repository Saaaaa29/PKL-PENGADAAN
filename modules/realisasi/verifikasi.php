<?php
/**
 * modules/realisasi/verifikasi.php
 * Halaman verifikasi inputan staf — hanya manajer & admin
 */
session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();
requireManajer('index.php');

$db   = getDB();
$uid  = (int)($_SESSION['user_id'] ?? 0);
$role = $_SESSION['user_role'] ?? '';

// ── HANDLE AKSI POST (setujui / tolak) ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rid     = (int)($_POST['realisasi_id'] ?? 0);
    $aksi    = $_POST['aksi'] ?? '';  // 'setujui' atau 'tolak'
    $catatan = trim($_POST['catatan_verifikasi'] ?? '');

    if ($rid && in_array($aksi, ['setujui', 'tolak'])) {
        $newVerif = $aksi === 'setujui' ? 'disetujui' : 'ditolak';

        $stmt = $db->prepare("UPDATE realisasi_kegiatan
            SET status_verifikasi=?, catatan_verifikasi=?,
                diverifikasi_oleh=?, tgl_verifikasi=NOW()
            WHERE id=?");
        $stmt->bind_param('ssii', $newVerif, $catatan, $uid, $rid);
        $stmt->execute();
        $stmt->close();

        // Kirim notifikasi balik ke staf yang input
        $qCreator = $db->query("SELECT created_by, nomor_kontrak FROM realisasi_kegiatan WHERE id=$rid");
        if ($qCreator) {
            $creator = $qCreator->fetch_assoc();
            $creatorId = (int)$creator['created_by'];
            $noKontrak = $creator['nomor_kontrak'] ?: "ID #$rid";

            $pesan = $aksi === 'setujui'
                ? "Realisasi \"$noKontrak\" telah disetujui oleh manajer."
                : "Realisasi \"$noKontrak\" ditolak oleh manajer." . ($catatan ? " Alasan: $catatan" : '');
            $tipeNotif = $aksi === 'setujui' ? 'disetujui' : 'ditolak';

            $stmtN = $db->prepare("INSERT INTO notifikasi
                (untuk_role, untuk_user_id, tipe, pesan, realisasi_id, dari_user_id)
                VALUES ('staf_pengadaan', ?, ?, ?, ?, ?)");
            $stmtN->bind_param('issii', $creatorId, $tipeNotif, $pesan, $rid, $uid);
            $stmtN->execute();
            $stmtN->close();
        }

        // Tandai notif input_baru untuk realisasi ini sebagai dibaca
        $db->query("UPDATE notifikasi SET dibaca=1 WHERE realisasi_id=$rid AND tipe='input_baru'");

        setFlash('success', 'Realisasi berhasil ' . ($aksi === 'setujui' ? 'disetujui' : 'ditolak') . '.');
        header('Location: verifikasi.php');
        exit;
    }
}

// ── JIKA ADA ?id= → mode detail verifikasi satu item ───────────
$detailId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$detailReal = null;
$detailItems = [];
$detailVendors = [];

if ($detailId) {
    $qD = $db->query("SELECT r.*, u.nama_lengkap FROM realisasi_kegiatan r
                      LEFT JOIN users u ON u.id = r.created_by
                      WHERE r.id = $detailId");
    $detailReal = $qD ? $qD->fetch_assoc() : null;
    if ($detailReal) {
        $qDI = $db->query("SELECT * FROM realisasi_detail WHERE realisasi_id=$detailId ORDER BY id ASC");
        while ($di = $qDI->fetch_assoc()) $detailItems[] = $di;
        $qDV = $db->query("SELECT * FROM realisasi_vendor WHERE realisasi_id=$detailId ORDER BY id ASC");
        while ($dv = $qDV->fetch_assoc()) $detailVendors[] = $dv;
    }
}

// ── DAFTAR PENDING ──────────────────────────────────────────────
$filter = $_GET['filter'] ?? 'menunggu';
$filterSQL = in_array($filter, ['menunggu','disetujui','ditolak','semua'])
    ? ($filter === 'semua' ? '' : "WHERE r.status_verifikasi = '$filter'")
    : "WHERE r.status_verifikasi = 'menunggu'";

$qList = $db->query("
    SELECT r.*, u.nama_lengkap,
        (SELECT COUNT(*) FROM realisasi_detail WHERE realisasi_id=r.id) as jml_item,
        (SELECT COUNT(*) FROM realisasi_vendor  WHERE realisasi_id=r.id) as jml_vendor
    FROM realisasi_kegiatan r
    LEFT JOIN users u ON u.id = r.created_by
    $filterSQL
    ORDER BY r.created_at DESC
    LIMIT 100
");
$listRows = [];
while ($lr = $qList->fetch_assoc()) $listRows[] = $lr;

// Hitung ringkasan
$cntQ = $db->query("SELECT status_verifikasi, COUNT(*) as c FROM realisasi_kegiatan GROUP BY status_verifikasi");
$cnt = ['menunggu'=>0,'disetujui'=>0,'ditolak'=>0];
while ($c = $cntQ->fetch_assoc()) $cnt[$c['status_verifikasi']] = (int)$c['c'];

$pageTitle = 'Verifikasi Realisasi';
include __DIR__ . '/../../includes/header.php';
?>

<style>
.badge-verif-menunggu  { background:#fef3c7;color:#92400e;border:1px solid #fbbf24; }
.badge-verif-disetujui { background:#d1fae5;color:#065f46;border:1px solid #34d399; }
.badge-verif-ditolak   { background:#fee2e2;color:#991b1b;border:1px solid #f87171; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0">Verifikasi Inputan Realisasi</h5>
        <small class="text-muted">Tinjau dan setujui/tolak data yang diinput staf pengadaan</small>
    </div>
    <a href="index.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar
    </a>
</div>

<!-- Ringkasan -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card border-0 text-white h-100" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-hourglass-split fs-2 opacity-75"></i>
                <div>
                    <div class="fs-3 fw-bold"><?= $cnt['menunggu'] ?></div>
                    <div style="font-size:12px;opacity:.85;">Menunggu Verifikasi</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 text-white h-100" style="background:linear-gradient(135deg,#10b981,#059669);">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-check-circle-fill fs-2 opacity-75"></i>
                <div>
                    <div class="fs-3 fw-bold"><?= $cnt['disetujui'] ?></div>
                    <div style="font-size:12px;opacity:.85;">Disetujui</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 text-white h-100" style="background:linear-gradient(135deg,#ef4444,#dc2626);">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-x-circle-fill fs-2 opacity-75"></i>
                <div>
                    <div class="fs-3 fw-bold"><?= $cnt['ditolak'] ?></div>
                    <div style="font-size:12px;opacity:.85;">Ditolak</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($detailReal): ?>
<!-- ── MODE DETAIL VERIFIKASI ─────────────────────────────── -->
<div class="card mb-4 border-warning">
    <div class="card-header" style="background:#fffbeb;">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-file-earmark-check me-2 text-warning"></i>
                    Detail Realisasi — <?= sanitize($detailReal['nomor_kontrak'] ?: 'ID #'.$detailId) ?>
                </h6>
                <small class="text-muted">
                    Diinput oleh: <strong><?= sanitize($detailReal['nama_lengkap'] ?? '-') ?></strong>
                    &nbsp;·&nbsp; <?= date('d M Y H:i', strtotime($detailReal['created_at'])) ?>
                </small>
            </div>
            <span class="badge badge-verif-<?= $detailReal['status_verifikasi'] ?> px-3 py-2" style="font-size:12px;">
                <?= ucfirst($detailReal['status_verifikasi']) ?>
            </span>
        </div>
    </div>
    <div class="card-body">

        <!-- Info header realisasi -->
        <div class="row g-3 mb-3">
            <div class="col-sm-3">
                <label class="form-label fw-semibold text-muted" style="font-size:11px;">TGL MULAI</label>
                <div><?= date('d M Y', strtotime($detailReal['tanggal_mulai'])) ?></div>
            </div>
            <div class="col-sm-3">
                <label class="form-label fw-semibold text-muted" style="font-size:11px;">TGL SELESAI</label>
                <div><?= $detailReal['tanggal_selesai'] ? date('d M Y', strtotime($detailReal['tanggal_selesai'])) : '-' ?></div>
            </div>
            <div class="col-sm-3">
                <label class="form-label fw-semibold text-muted" style="font-size:11px;">METODE</label>
                <div style="font-size:12px;"><?= getLabelMetode($detailReal['metode_pengadaan']) ?></div>
            </div>
            <div class="col-sm-3">
                <label class="form-label fw-semibold text-muted" style="font-size:11px;">TOTAL NILAI</label>
                <div class="fw-bold text-primary"><?= formatRupiah($detailReal['total_nilai']) ?></div>
            </div>
        </div>

        <!-- Tabel item -->
        <?php if (!empty($detailItems)): ?>
        <h6 class="fw-semibold mb-2 mt-3" style="font-size:13px;">
            <i class="bi bi-list-check me-1 text-primary"></i>Item Kegiatan (<?= count($detailItems) ?>)
        </h6>
        <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered mb-0" style="font-size:12px;">
                <thead class="table-light">
                    <tr>
                        <th>#</th><th>Nama Kegiatan</th><th>Jenis</th>
                        <th class="text-center">Vol</th><th>Satuan</th>
                        <th class="text-end">Nilai Satuan</th><th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detailItems as $i => $di): ?>
                    <tr>
                        <td class="text-center"><?= $i+1 ?></td>
                        <td><?= sanitize($di['nama_kegiatan']) ?></td>
                        <td><?= getLabelJenis($di['jenis_pengadaan']) ?></td>
                        <td class="text-center"><?= formatAngka($di['volume']) ?></td>
                        <td><?= sanitize($di['satuan']) ?></td>
                        <td class="text-end"><?= formatRupiah($di['nilai_satuan']) ?></td>
                        <td class="text-end fw-semibold"><?= formatRupiah($di['nilai_anggaran']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Tabel vendor -->
        <?php if (!empty($detailVendors)): ?>
        <h6 class="fw-semibold mb-2" style="font-size:13px;">
            <i class="bi bi-building me-1 text-primary"></i>Vendor (<?= count($detailVendors) ?>)
        </h6>
        <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered mb-0" style="font-size:12px;">
                <thead class="table-light">
                    <tr><th>#</th><th>Nama Vendor</th><th>No. Kontrak</th><th>Tgl. Kontrak</th><th class="text-end">Nilai</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($detailVendors as $i => $dv): ?>
                    <tr>
                        <td class="text-center"><?= $i+1 ?></td>
                        <td><?= sanitize($dv['nama_vendor']) ?></td>
                        <td><?= sanitize($dv['nomor_kontrak'] ?: '-') ?></td>
                        <td><?= $dv['tanggal_kontrak'] ? date('d/m/Y', strtotime($dv['tanggal_kontrak'])) : '-' ?></td>
                        <td class="text-end"><?= formatRupiah($dv['nilai_kontrak']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if (!empty($detailReal['catatan'])): ?>
        <div class="alert alert-light border mb-3 py-2" style="font-size:12px;">
            <strong>Catatan:</strong> <?= sanitize($detailReal['catatan']) ?>
        </div>
        <?php endif; ?>

        <!-- Form aksi verifikasi -->
        <?php if ($detailReal['status_verifikasi'] === 'menunggu'): ?>
        <hr>
        <h6 class="fw-bold mb-3">Keputusan Verifikasi</h6>
        <form method="POST">
            <input type="hidden" name="realisasi_id" value="<?= $detailId ?>">
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Catatan (opsional untuk disetujui, wajib untuk ditolak)
                </label>
                <textarea name="catatan_verifikasi" class="form-control" rows="3"
                          id="catatanVerif"
                          placeholder="Berikan keterangan atau alasan penolakan..."></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" name="aksi" value="setujui"
                        class="btn btn-success px-4">
                    <i class="bi bi-check-lg me-1"></i>Setujui
                </button>
                <button type="submit" name="aksi" value="tolak"
                        class="btn btn-danger px-4" id="btnTolak">
                    <i class="bi bi-x-lg me-1"></i>Tolak
                </button>
                <a href="verifikasi.php" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
        <script>
        document.getElementById('btnTolak').addEventListener('click', function(e) {
            var cat = document.getElementById('catatanVerif').value.trim();
            if (!cat) {
                e.preventDefault();
                document.getElementById('catatanVerif').focus();
                document.getElementById('catatanVerif').classList.add('is-invalid');
                if (!document.getElementById('catatanHelp')) {
                    var d = document.createElement('div');
                    d.id = 'catatanHelp';
                    d.className = 'invalid-feedback';
                    d.textContent = 'Catatan wajib diisi untuk penolakan.';
                    document.getElementById('catatanVerif').after(d);
                }
            }
        });
        </script>
        <?php else: ?>
        <div class="alert alert-secondary py-2" style="font-size:12px;">
            <i class="bi bi-info-circle me-1"></i>
            Sudah diverifikasi pada <?= $detailReal['tgl_verifikasi'] ? date('d M Y H:i', strtotime($detailReal['tgl_verifikasi'])) : '-' ?>
            <?php if ($detailReal['catatan_verifikasi']): ?>
            &nbsp;·&nbsp; <?= sanitize($detailReal['catatan_verifikasi']) ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ── DAFTAR SEMUA REALISASI ──────────────────────────────── -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Daftar Realisasi</h6>
        <div class="d-flex gap-1">
            <?php foreach ([
                'menunggu'  => ['warning','Menunggu ('.($cnt['menunggu']).')'],
                'disetujui' => ['success','Disetujui'],
                'ditolak'   => ['danger','Ditolak'],
                'semua'     => ['secondary','Semua'],
            ] as $f => $info): ?>
            <a href="?filter=<?= $f ?>"
               class="btn btn-sm <?= $filter===$f ? 'btn-'.$info[0] : 'btn-outline-'.$info[0] ?>">
                <?= $info[1] ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:12px;">
                <thead class="table-light">
                    <tr>
                        <th>No. Kontrak</th>
                        <th>Diinput oleh</th>
                        <th>Tanggal Input</th>
                        <th class="text-end">Total Nilai</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Verifikasi</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($listRows)): ?>
                    <tr><td colspan="7" class="text-center py-3 text-muted">Tidak ada data</td></tr>
                <?php else: foreach ($listRows as $lr):
                    $vs = $lr['status_verifikasi'] ?? 'menunggu';
                    $bgRow = $vs === 'menunggu' ? 'background:#fffbeb;' : ($vs === 'ditolak' ? 'background:#fff1f2;' : '');
                ?>
                    <tr style="<?= $bgRow ?>">
                        <td>
                            <a href="?id=<?= $lr['id'] ?>&filter=<?= $filter ?>"
                               class="text-decoration-none fw-semibold">
                                <?= sanitize($lr['nomor_kontrak'] ?: 'ID #'.$lr['id']) ?>
                            </a>
                            <div class="text-muted" style="font-size:10px;">
                                <?= $lr['jml_item'] ?> item · <?= $lr['jml_vendor'] ?> vendor
                            </div>
                        </td>
                        <td><?= sanitize($lr['nama_lengkap'] ?? '-') ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($lr['created_at'])) ?></td>
                        <td class="text-end fw-semibold"><?= formatRupiah($lr['total_nilai']) ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= ['proses'=>'warning text-dark','selesai'=>'success','batal'=>'danger'][$lr['status']]??'secondary' ?>">
                                <?= ucfirst($lr['status']) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge" style="
                                font-size:10px;
                                <?= $vs==='menunggu' ? 'background:#fef3c7;color:#92400e;border:1px solid #fbbf24;'
                                  : ($vs==='disetujui' ? 'background:#d1fae5;color:#065f46;border:1px solid #34d399;'
                                  : 'background:#fee2e2;color:#991b1b;border:1px solid #f87171;') ?>
                            ">
                                <?= ucfirst($vs) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="?id=<?= $lr['id'] ?>&filter=<?= $filter ?>"
                               class="btn btn-sm <?= $vs==='menunggu' ? 'btn-warning' : 'btn-outline-secondary' ?>">
                                <?= $vs==='menunggu' ? '<i class="bi bi-shield-check me-1"></i>Verifikasi' : '<i class="bi bi-eye me-1"></i>Detail' ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<?php include __DIR__ . '/../../includes/footer.php'; ?>