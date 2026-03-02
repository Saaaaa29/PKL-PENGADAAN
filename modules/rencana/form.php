<?php
/**
 * modules/rencana/form.php
 * Form tambah / edit rencana kegiatan
 * Bulan rencana bisa dipilih LEBIH DARI SATU
 */

session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db     = getDB();
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Simpan filter asal agar bisa redirect kembali ke posisi yang sama
$refTahun  = isset($_GET['ref_tahun'])  ? (int)$_GET['ref_tahun']        : (int)date('Y');
$refJenis  = isset($_GET['ref_jenis'])  ? trim($_GET['ref_jenis'])        : '';
$refUrutan = isset($_GET['ref_urutan']) ? trim($_GET['ref_urutan'])       : 'nama';
$refParams = http_build_query(array_filter([
    'tahun'  => $refTahun ?: null,
    'jenis'  => $refJenis  ?: null,
    'urutan' => $refUrutan !== 'nama' ? $refUrutan : null,
]));
$redirectBack = 'index.php' . ($refParams ? '?' . $refParams : '');
$isEdit = $id > 0;
$data   = [];

// Jika edit: ambil data yang ada
if ($isEdit) {
    $q    = $db->query("SELECT * FROM rencana_kegiatan WHERE id = $id");
    $data = $q->fetch_assoc();
    if (!$data) {
        setFlash('error', 'Data tidak ditemukan.');
        header('Location: ' . $redirectBack);
        exit;
    }
}

// Bulan yang sudah dipilih sebelumnya (untuk mode edit)
// Disimpan sebagai "1,3,5" di database, dipecah jadi array [1,3,5]
$bulanDipilih = [];
if ($isEdit && !empty($data['bulan_rencana'])) {
    $bulanDipilih = array_map('intval', explode(',', $data['bulan_rencana']));
}

// Proses simpan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil ref filter dari hidden fields POST
    if (isset($_POST['ref_tahun']))  $refTahun  = (int)$_POST['ref_tahun'];
    if (isset($_POST['ref_jenis']))  $refJenis  = trim($_POST['ref_jenis']);
    if (isset($_POST['ref_urutan'])) $refUrutan = trim($_POST['ref_urutan']);
    $refParams = http_build_query(array_filter([
        'tahun'  => $refTahun ?: null,
        'jenis'  => $refJenis  ?: null,
        'urutan' => $refUrutan !== 'nama' ? $refUrutan : null,
    ]));
    $redirectBack = 'index.php' . ($refParams ? '?' . $refParams : '');
    $nama          = trim($_POST['nama_kegiatan'] ?? '');
    $volume        = (float)($_POST['volume'] ?? 0);
    $satuan        = trim($_POST['satuan'] ?? '');
    $nilaiSatuan   = (float)($_POST['nilai_satuan'] ?? 0);
    $nilaiAnggaran = $volume * $nilaiSatuan;
    $jenisP        = $_POST['jenis_pengadaan'] ?? '';
    $tahun         = (int)($_POST['tahun'] ?? date('Y'));
    $keterangan    = trim($_POST['keterangan'] ?? '');
    $metode        = $_POST['metode_pengadaan'] ?? tentukanMetode($nilaiAnggaran);

    // Ambil bulan yang dipilih (array dari checkbox)
    $bulanArr = $_POST['bulan_rencana'] ?? [];
    // Filter hanya angka 1-12 yang valid
    $bulanArr = array_filter(array_map('intval', $bulanArr), fn($b) => $b >= 1 && $b <= 12);
    sort($bulanArr); // urutkan Jan → Des
    // Simpan sebagai string "1,3,5"
    $bulanStr = implode(',', $bulanArr);

    // Validasi
    $errors = [];
    if (!$nama)             $errors[] = 'Nama kegiatan wajib diisi.';
    if ($volume <= 0)       $errors[] = 'Volume harus lebih dari 0.';
    if (!$satuan)           $errors[] = 'Satuan wajib diisi.';
    if ($nilaiSatuan <= 0)  $errors[] = 'Nilai satuan harus lebih dari 0.';
    if (!array_key_exists($jenisP, LABEL_JENIS)) $errors[] = 'Jenis pengadaan tidak valid.';
    if (empty($bulanArr))   $errors[] = 'Pilih minimal satu bulan rencana.';

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $db->prepare("UPDATE rencana_kegiatan SET nama_kegiatan=?, volume=?, satuan=?,
                nilai_satuan=?, nilai_anggaran=?, jenis_pengadaan=?, metode_pengadaan=?,
                bulan_rencana=?, tahun=?, keterangan=? WHERE id=?");
            $stmt->bind_param('sdsdssssisi',
                $nama, $volume, $satuan, $nilaiSatuan, $nilaiAnggaran,
                $jenisP, $metode, $bulanStr, $tahun, $keterangan, $id);
        } else {
            $userId = $_SESSION['user_id'];
            $stmt   = $db->prepare("INSERT INTO rencana_kegiatan
                (nama_kegiatan, volume, satuan, nilai_satuan, nilai_anggaran, jenis_pengadaan,
                 metode_pengadaan, bulan_rencana, tahun, keterangan, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sdsdssssisi',
                $nama, $volume, $satuan, $nilaiSatuan, $nilaiAnggaran,
                $jenisP, $metode, $bulanStr, $tahun, $keterangan, $userId);
        }
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Rencana kegiatan berhasil ' . ($isEdit ? 'diperbarui' : 'disimpan') . '.');
        header('Location: ' . $redirectBack);
        exit;
    }

    // Jika ada error, restore pilihan bulan dari POST
    $bulanDipilih = $bulanArr;
}

$pageTitle = ($isEdit ? 'Edit' : 'Tambah') . ' Rencana Kegiatan';
include __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-8">

<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <a href="index.php" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
        <span><?= $isEdit ? 'Edit' : 'Tambah' ?> Rencana Kegiatan</span>
    </div>
    <div class="card-body">

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= sanitize($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" id="formRencana">
    <input type="hidden" name="ref_tahun"  value="<?= $refTahun ?>">
    <input type="hidden" name="ref_jenis"  value="<?= htmlspecialchars($refJenis) ?>">
    <input type="hidden" name="ref_urutan" value="<?= htmlspecialchars($refUrutan) ?>">

            <!-- Nama Kegiatan -->
            <div class="mb-3">
                <label class="form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                <input type="text" name="nama_kegiatan" class="form-control"
                       placeholder="Masukkan nama kegiatan pengadaan"
                       value="<?= sanitize($data['nama_kegiatan'] ?? $_POST['nama_kegiatan'] ?? '') ?>" required>
            </div>

            <!-- Volume & Satuan -->
            <div class="row g-3 mb-3">
                <div class="col-sm-4">
                    <label class="form-label">Volume <span class="text-danger">*</span></label>
                    <input type="number" name="volume" class="form-control" min="0.01" step="0.01"
                           placeholder="Misal: 5"
                           value="<?= $data['volume'] ?? $_POST['volume'] ?? '' ?>" required>
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Satuan <span class="text-danger">*</span></label>
                    <input type="text" name="satuan" class="form-control"
                           placeholder="unit / paket / m² / dll"
                           value="<?= sanitize($data['satuan'] ?? $_POST['satuan'] ?? '') ?>" required>
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Nilai Satuan (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="nilai_satuan" id="nilaiSatuan" class="form-control" min="0" step="1"
                           placeholder="0"
                           value="<?= $data['nilai_satuan'] ?? $_POST['nilai_satuan'] ?? '' ?>" required>
                </div>
            </div>

            <!-- Nilai Anggaran (auto) -->
            <div class="mb-3 p-3 bg-light rounded-3">
                <label class="form-label fw-semibold">Nilai Anggaran (Otomatis: Volume × Nilai Satuan)</label>
                <div class="fs-5 fw-bold text-primary" id="displayAnggaran">
                    <?= formatRupiah($data['nilai_anggaran'] ?? 0) ?>
                </div>
                <input type="hidden" name="nilai_anggaran" id="nilaiAnggaran"
                       value="<?= $data['nilai_anggaran'] ?? 0 ?>">
            </div>

            <!-- Jenis & Metode Pengadaan -->
            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label">Jenis Pengadaan <span class="text-danger">*</span></label>
                    <select name="jenis_pengadaan" class="form-select" required>
                        <option value="">-- Pilih Jenis --</option>
                        <?php foreach (LABEL_JENIS as $k => $v): ?>
                            <option value="<?= $k ?>" <?= ($data['jenis_pengadaan'] ?? '') === $k ? 'selected' : '' ?>>
                                <?= $v ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6">
                    <label class="form-label">
                        Metode Pengadaan
                        <span class="badge bg-info ms-1" style="font-size:10px;">Auto</span>
                    </label>
                    <select name="metode_pengadaan" id="metodePengadaan" class="form-select">
                        <?php foreach (LABEL_METODE as $k => $v): ?>
                            <option value="<?= $k ?>"
                                <?= ($data['metode_pengadaan'] ?? tentukanMetode($data['nilai_anggaran'] ?? 0)) === $k ? 'selected' : '' ?>>
                                <?= $v ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        <i class="bi bi-info-circle me-1"></i>
                        ≤15jt: Pembelian Langsung | ≤600jt: Tender Terbatas | >600jt: Tender Umum.
                        E-Purchasing & Swakelola dipilih manual.
                    </div>
                </div>
            </div>

            <!-- ============================================================
                 BULAN RENCANA - Multi pilih dengan checkbox visual
                 Tampil sebagai grid 6 kolom (Jan-Jun baris 1, Jul-Des baris 2)
                 ============================================================ -->
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Bulan Rencana <span class="text-danger">*</span>
                    <span class="text-muted fw-normal ms-1" style="font-size:12px;">(boleh pilih lebih dari satu)</span>
                </label>

                <!-- Tombol pilih semua / bersihkan -->
                <div class="mb-2 d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="pilihSemuaBulan()">
                        <i class="bi bi-check-all me-1"></i>Pilih Semua
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="bersihkanBulan()">
                        <i class="bi bi-x-lg me-1"></i>Bersihkan
                    </button>
                    <span class="ms-2 align-self-center text-muted small" id="infoBulanDipilih">
                        <?= count($bulanDipilih) ?> bulan dipilih
                    </span>
                </div>

                <!-- Grid checkbox bulan -->
                <div class="row g-2" id="gridBulan">
                    <?php foreach (NAMA_BULAN as $nomor => $namaBulan):
                        $checked = in_array($nomor, $bulanDipilih) ? 'checked' : '';
                    ?>
                    <div class="col-6 col-sm-4 col-md-2">
                        <input type="checkbox"
                               class="btn-check bulan-checkbox"
                               name="bulan_rencana[]"
                               id="bulan<?= $nomor ?>"
                               value="<?= $nomor ?>"
                               autocomplete="off"
                               <?= $checked ?>>
                        <label class="btn btn-outline-primary w-100 btn-bulan" for="bulan<?= $nomor ?>">
                            <span class="d-block fw-semibold"><?= substr($namaBulan, 0, 3) ?></span>
                            <small class="opacity-75"><?= $namaBulan ?></small>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Ringkasan bulan yang dipilih -->
                <div class="mt-2 p-2 bg-light rounded-2" id="ringkasanBulan" style="font-size:13px; min-height:32px;">
                    <?php if (!empty($bulanDipilih)): ?>
                        <i class="bi bi-calendar3 me-1 text-primary"></i>
                        <span id="textBulanDipilih">
                            <?= implode(', ', array_map(fn($b) => NAMA_BULAN[$b], $bulanDipilih)) ?>
                        </span>
                    <?php else: ?>
                        <span class="text-muted" id="textBulanDipilih">Belum ada bulan yang dipilih</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tahun -->
            <div class="mb-4">
                <label class="form-label">Tahun <span class="text-danger">*</span></label>
                <select name="tahun" class="form-select" style="max-width:150px;" required>
                    <?php for ($y = date('Y') + 1; $y >= 2020; $y--): ?>
                        <option value="<?= $y ?>" <?= ($data['tahun'] ?? date('Y')) == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Keterangan -->
            <div class="mb-4">
                <label class="form-label">Keterangan / Catatan</label>
                <textarea name="keterangan" class="form-control" rows="3"
                          placeholder="Opsional: tambahkan catatan relevan"><?= sanitize($data['keterangan'] ?? '') ?></textarea>
            </div>

            <!-- Tombol -->
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i><?= $isEdit ? 'Perbarui' : 'Simpan' ?>
                </button>
                <a href="index.php" class="btn btn-outline-secondary px-4">Batal</a>
            </div>

        </form>
    </div>
</div>

</div><!-- col -->
</div><!-- row -->

<?php
$namaBulanJS = json_encode(array_values(NAMA_BULAN)); // ['Januari','Februari',...]

$extraJS = <<<JS
<style>
/* Tombol bulan custom */
.btn-bulan {
    padding: 8px 4px;
    font-size: 12px;
    border-radius: 8px;
    transition: all 0.15s;
    text-align: center;
}
.btn-check:checked + .btn-bulan {
    background-color: #4361ee;
    border-color: #4361ee;
    color: white;
    box-shadow: 0 2px 8px rgba(67,97,238,0.35);
}
</style>

<script>
const NAMA_BULAN_JS = $namaBulanJS; // ['Januari', 'Februari', ...]

// -------------------------------------------------------
// Hitung nilai anggaran otomatis
// -------------------------------------------------------
(function() {
    const volEl    = document.querySelector('[name="volume"]');
    const satEl    = document.querySelector('[name="nilai_satuan"]');
    const totalEl  = document.getElementById('nilaiAnggaran');
    const dispEl   = document.getElementById('displayAnggaran');
    const metodeEl = document.getElementById('metodePengadaan');

    function hitung() {
        const vol   = parseFloat(volEl.value) || 0;
        const harga = parseFloat(satEl.value) || 0;
        const total = vol * harga;
        totalEl.value = total;
        dispEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
        // Auto metode kecuali e_purchasing / swakelola
        if (metodeEl.value !== 'e_purchasing' && metodeEl.value !== 'swakelola') {
            metodeEl.value = window.tentukanMetode(total);
        }
    }

    volEl.addEventListener('input', hitung);
    satEl.addEventListener('input', hitung);
    hitung(); // init saat halaman load
})();

// -------------------------------------------------------
// Update ringkasan bulan yang dipilih
// -------------------------------------------------------
function updateRingkasanBulan() {
    const checkboxes = document.querySelectorAll('.bulan-checkbox:checked');
    const dipilih    = Array.from(checkboxes).map(cb => parseInt(cb.value));
    dipilih.sort((a, b) => a - b);

    const textEl  = document.getElementById('textBulanDipilih');
    const infoEl  = document.getElementById('infoBulanDipilih');

    infoEl.textContent = dipilih.length + ' bulan dipilih';

    if (dipilih.length === 0) {
        textEl.innerHTML = '<span class="text-muted">Belum ada bulan yang dipilih</span>';
    } else {
        const namaList = dipilih.map(b => NAMA_BULAN_JS[b - 1]);
        textEl.innerHTML = '<i class="bi bi-calendar3 me-1 text-primary"></i>' + namaList.join(', ');
    }
}

// Pasang event listener ke semua checkbox bulan
document.querySelectorAll('.bulan-checkbox').forEach(cb => {
    cb.addEventListener('change', updateRingkasanBulan);
});

// Pilih semua bulan
function pilihSemuaBulan() {
    document.querySelectorAll('.bulan-checkbox').forEach(cb => cb.checked = true);
    updateRingkasanBulan();
}

// Bersihkan semua pilihan bulan
function bersihkanBulan() {
    document.querySelectorAll('.bulan-checkbox').forEach(cb => cb.checked = false);
    updateRingkasanBulan();
}

// Init ringkasan saat halaman load
updateRingkasanBulan();
</script>
JS;

include __DIR__ . '/../../includes/footer.php';
?>