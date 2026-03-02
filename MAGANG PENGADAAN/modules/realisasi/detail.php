<?php
/**
 * modules/realisasi/detail.php
 * Halaman detail realisasi kegiatan
 */

session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$realisasi = $db->query("SELECT r.*, u.nama_lengkap FROM realisasi_kegiatan r 
                          LEFT JOIN users u ON u.id = r.created_by WHERE r.id = $id")->fetch_assoc();
if (!$realisasi) {
    setFlash('error', 'Data tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$details = $db->query("SELECT d.*, rk.nama_kegiatan as rencana_nama, rk.nilai_anggaran as rencana_nilai
    FROM realisasi_detail d 
    LEFT JOIN rencana_kegiatan rk ON rk.id = d.rencana_id 
    WHERE d.realisasi_id = $id ORDER BY d.id ASC");

$pageTitle = 'Detail Realisasi';
include __DIR__ . '/../../includes/header.php';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <a href="index.php" class="btn btn-sm btn-light me-2"><i class="bi bi-arrow-left"></i></a>
        <span class="fw-bold">Detail Realisasi</span>
        <?php
        $statusColors = ['proses' => 'warning text-dark', 'selesai' => 'success', 'batal' => 'danger'];
        $sc = $statusColors[$realisasi['status']] ?? 'secondary';
        ?>
        <span class="badge bg-<?= $sc ?> ms-2"><?= ucfirst($realisasi['status']) ?></span>
    </div>
    <div class="d-flex gap-2 flex-wrap no-print">
        <a href="form.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-printer me-1"></i>Cetak
        </button>
        <button onclick="konfirmasiHapus('hapus.php?id=<?= $id ?>','Realisasi ini')"
                class="btn btn-sm btn-outline-danger">
            <i class="bi bi-trash me-1"></i>Hapus
        </button>
    </div>
</div>

<div class="row g-4">
    <!-- Informasi Umum -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-info-circle me-2 text-primary"></i>Informasi Umum
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width:40%">No. Kontrak</td>
                        <td class="fw-semibold"><?= sanitize($realisasi['nomor_kontrak'] ?: '-') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Metode</td>
                        <td>
                            <?php $c = ['pembelian_langsung'=>'success','tender_terbatas_spk'=>'info','tender_terbatas_pkp'=>'primary','tender_terbatas'=>'info','tender_umum'=>'danger','e_purchasing'=>'warning','swakelola'=>'secondary']; ?>
                            <span class="badge bg-<?= $c[$realisasi['metode_pengadaan']] ?? 'secondary' ?>">
                                <?= getLabelMetode($realisasi['metode_pengadaan']) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tgl Mulai</td>
                        <td><?= date('d F Y', strtotime($realisasi['tanggal_mulai'])) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tgl Selesai</td>
                        <td><?= $realisasi['tanggal_selesai'] ? date('d F Y', strtotime($realisasi['tanggal_selesai'])) : '<span class="text-muted">-</span>' ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Dibuat oleh</td>
                        <td><?= sanitize($realisasi['nama_lengkap'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Dibuat pada</td>
                        <td><?= date('d/m/Y H:i', strtotime($realisasi['created_at'])) ?></td>
                    </tr>
                </table>

                <?php if ($realisasi['catatan']): ?>
                    <hr>
                    <div class="text-muted small mb-1">Catatan:</div>
                    <div><?= nl2br(sanitize($realisasi['catatan'])) ?></div>
                <?php endif; ?>

                <!-- Total nilai besar -->
                <div class="card bg-primary text-white mt-3">
                    <div class="card-body py-2 px-3 text-center">
                        <div style="font-size:11px; opacity:0.8;">Total Nilai Realisasi</div>
                        <div class="fw-bold fs-5"><?= formatRupiah($realisasi['total_nilai']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Items -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-list-check me-2 text-primary"></i>Detail Item Kegiatan
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">No.</th>
                                <th>Nama Kegiatan</th>
                                <th class="text-center">Vol.</th>
                                <th class="text-end">Nilai Satuan</th>
                                <th class="text-end">Total</th>
                                <th>Jenis</th>
                                <th>Sumber</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; $totalItem = 0; while ($d = $details->fetch_assoc()): $totalItem += $d['nilai_anggaran']; ?>
                            <tr>
                                <td class="ps-3 text-muted"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-semibold"><?= sanitize($d['nama_kegiatan']) ?></div>
                                    <?php if ($d['keterangan']): ?>
                                        <small class="text-muted"><?= sanitize($d['keterangan']) ?></small>
                                    <?php endif; ?>
                                    <!-- Bandingkan dengan rencana jika ada -->
                                    <?php if ($d['rencana_id'] && $d['rencana_nilai']): ?>
                                        <?php
                                        $selisih = $d['nilai_anggaran'] - $d['rencana_nilai'];
                                        $selisihClass = $selisih > 0 ? 'danger' : ($selisih < 0 ? 'success' : 'secondary');
                                        $selisihLabel = $selisih > 0 ? '+' : '';
                                        ?>
                                        <div>
                                            <small class="text-muted">Rencana: <?= formatRupiah($d['rencana_nilai']) ?></small>
                                            <span class="badge bg-<?= $selisihClass ?> ms-1" style="font-size:10px;">
                                                <?= $selisihLabel . formatRupiah(abs($selisih)) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= formatAngka($d['volume']) ?> <?= sanitize($d['satuan']) ?></td>
                                <td class="text-end"><?= formatRupiah($d['nilai_satuan']) ?></td>
                                <td class="text-end fw-semibold text-primary"><?= formatRupiah($d['nilai_anggaran']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= getLabelJenis($d['jenis_pengadaan']) ?></span></td>
                                <td>
                                    <?php if ($d['rencana_id']): ?>
                                        <span class="badge bg-primary">Dari Rencana</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Item Baru</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-light fw-bold">
                                <td colspan="4" class="text-end ps-3">Total:</td>
                                <td class="text-end text-primary"><?= formatRupiah($totalItem) ?></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>