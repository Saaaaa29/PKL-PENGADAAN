<?php
/**
 * modules/users/proses.php
 * Proses tambah / edit user
 */

session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$db = getDB();
$aksi = $_POST['aksi'] ?? '';

// ── Daftar role yang valid ─────────────────────────────────────────────────
$validRoles = ['admin', 'manajer_pengadaan', 'staf_pengadaan'];

if ($aksi === 'tambah') {
    $username = trim($_POST['username'] ?? '');
    $nama     = trim($_POST['nama_lengkap'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $role     = in_array($_POST['role'], $validRoles) ? $_POST['role'] : 'staf_pengadaan';

    if (!$username || !$nama || !$pass) {
        setFlash('error', 'Semua field wajib diisi.');
    } elseif (strlen($pass) < 8) {
        setFlash('error', 'Password minimal 8 karakter.');
    } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?,?,?,?)");
        $stmt->bind_param('ssss', $username, $hash, $nama, $role);
        if ($stmt->execute()) {
            setFlash('success', 'User berhasil ditambahkan.');
        } else {
            setFlash('error', 'Username sudah digunakan.');
        }
        $stmt->close();
    }
} elseif ($aksi === 'edit') {
    $id       = (int)($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $nama     = trim($_POST['nama_lengkap'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $role     = in_array($_POST['role'], $validRoles) ? $_POST['role'] : 'staf_pengadaan';

    if ($pass) {
        if (strlen($pass) < 8) {
            setFlash('error', 'Password minimal 8 karakter.');
            header('Location: index.php');
            exit;
        }
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET username=?, nama_lengkap=?, password=?, role=? WHERE id=?");
        $stmt->bind_param('ssssi', $username, $nama, $hash, $role, $id);
    } else {
        $stmt = $db->prepare("UPDATE users SET username=?, nama_lengkap=?, role=? WHERE id=?");
        $stmt->bind_param('sssi', $username, $nama, $role, $id);
    }
    $stmt->execute();
    $stmt->close();
    setFlash('success', 'User berhasil diperbarui.');
}

header('Location: index.php');
exit;