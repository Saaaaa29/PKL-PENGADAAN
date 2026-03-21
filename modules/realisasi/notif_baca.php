<?php
/**
 * modules/realisasi/notif_baca.php
 * Tandai notifikasi sebagai dibaca (dipanggil via fetch/XHR)
 */
session_start();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$db  = getDB();
$id  = (int)($_GET['id'] ?? 0);
$uid = (int)($_SESSION['user_id'] ?? 0);

if ($id && $uid) {
    $db->query("UPDATE notifikasi SET dibaca=1 WHERE id=$id
                AND (untuk_user_id IS NULL OR untuk_user_id=$uid)");
}
http_response_code(200);
echo 'ok';  