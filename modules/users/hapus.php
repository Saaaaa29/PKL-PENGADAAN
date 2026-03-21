<?php
session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if ($id > 0 && $id !== (int)$_SESSION['user_id']) {
    $db = getDB();
    $db->query("DELETE FROM users WHERE id = $id");
    setFlash('success', 'User berhasil dihapus.');
} else {
    setFlash('error', 'Tidak dapat menghapus akun sendiri.');
}
header('Location: index.php');
exit;
