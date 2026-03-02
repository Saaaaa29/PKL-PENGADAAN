<?php
/**
 * modules/users/index.php
 */

session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$db    = getDB();
$users = $db->query("SELECT id, username, nama_lengkap, role, created_at FROM users ORDER BY id ASC");
$rows  = $users->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Manajemen User';
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Manajemen Pengguna</h5>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
        <i class="bi bi-person-plus-fill me-1"></i>Tambah User
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0" id="tabelUser" style="font-size:.875rem;">
            <thead>
                <tr>
                    <th class="ps-3">No.</th>
                    <th>Username</th>
                    <th>Nama Lengkap</th>
                    <th>Role</th>
                    <th>Dibuat</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $no => $u): ?>
                <tr>
                    <td class="ps-3 text-muted"><?= $no + 1 ?></td>
                    <td class="fw-semibold"><?= sanitize($u['username']) ?></td>
                    <td><?= sanitize($u['nama_lengkap']) ?></td>
                    <td>
                        <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : 'bg-secondary' ?>">
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>
                    <td class="text-muted"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary"
                                    onclick="editUser(<?= $u['id'] ?>,'<?= addslashes($u['username']) ?>','<?= addslashes($u['nama_lengkap']) ?>','<?= $u['role'] ?>')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <button onclick="konfirmasiHapus('hapus.php?id=<?= $u['id'] ?>','<?= addslashes($u['nama_lengkap']) ?>')"
                                    class="btn btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambahUser" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="proses.php">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Pengguna Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" required placeholder="Huruf kecil dan angka">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama_lengkap" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required placeholder="Minimal 8 karakter">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEditUser" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="proses.php">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id" id="editUserId">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" id="editUsername" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" id="editNama" name="nama_lengkap" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengganti">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" id="editRole" class="form-select">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraJS = '<script>
function editUser(id, username, nama, role) {
    document.getElementById("editUserId").value   = id;
    document.getElementById("editUsername").value = username;
    document.getElementById("editNama").value     = nama;
    document.getElementById("editRole").value     = role;
    new bootstrap.Modal(document.getElementById("modalEditUser")).show();
}

$(document).ready(function() {
    $("#tabelUser").DataTable({
        dom: "t",
        paging: false,
        order: [[0, "asc"]],
        columnDefs: [{ orderable: false, targets: [5] }]
    });
});
</script>';

include __DIR__ . '/../../includes/footer.php';
?> 