<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? APP_NAME ?> - <?= APP_FULLNAME ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="wrapper d-flex">
    <?php
    if (!isset($db)) { $db = getDB(); }
    include __DIR__ . '/sidebar.php';
    ?>

    <div class="main-content flex-grow-1">

        <!-- TOP NAVBAR -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-2 sticky-top shadow-sm">

            <!-- Toggle sidebar -->
            <button class="btn btn-sm btn-outline-secondary me-3" id="sidebarToggle">
                <i class="bi bi-list fs-5"></i>
            </button>

            <!-- Judul halaman -->
            <span class="navbar-brand fw-bold text-muted mb-0">
                <?= $pageTitle ?? 'Dashboard' ?>
            </span>

            <?php
            // ── Data notifikasi ────────────────────────────────────────
            $role   = $_SESSION['user_role'] ?? '';
            $uid    = (int)($_SESSION['user_id'] ?? 0);
            $isMgr  = in_array($role, ['admin', 'manajer_pengadaan']);
            $notifList   = [];
            $notifUnread = 0;

            if ($isMgr) {
                $nQ = $db->query("
                    SELECT n.*, rk.nomor_kontrak, rk.id AS rid,
                           u.nama_lengkap AS nama_staf
                    FROM notifikasi n
                    JOIN realisasi_kegiatan rk ON rk.id = n.realisasi_id
                    LEFT JOIN users u ON u.id = n.dari_user_id
                    WHERE n.untuk_role IN ('manajer_pengadaan','admin')
                      AND (n.untuk_user_id IS NULL OR n.untuk_user_id = $uid)
                    ORDER BY n.created_at DESC
                    LIMIT 8
                ");
                if ($nQ) while ($n = $nQ->fetch_assoc()) $notifList[] = $n;
                $notifUnread = count(array_filter($notifList, fn($n) => !$n['dibaca']));
            }

            // ── Label role ─────────────────────────────────────────────
            $roleInfo = match($role) {
                'admin'             => ['Admin',             'danger'],
                'manajer_pengadaan' => ['Manajer Pengadaan', 'primary'],
                'staf_pengadaan'    => ['Staf Pengadaan',    'secondary'],
                default             => ['User',              'light'],
            };
            ?>

            <div class="ms-auto d-flex align-items-center gap-3">

                <!-- Tanggal -->
                <span class="text-muted small d-none d-md-inline">
                    <i class="bi bi-calendar3 me-1"></i><?= date('d M Y') ?>
                </span>

                <!-- ── Bell notifikasi (hanya manajer & admin) ── -->
                <?php if ($isMgr): ?>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary position-relative"
                            data-bs-toggle="dropdown" id="btnBell" aria-expanded="false">
                        <i class="bi bi-bell<?= $notifUnread > 0 ? '-fill text-warning' : '' ?> fs-6"></i>
                        <?php if ($notifUnread > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                              style="font-size:9px;"><?= $notifUnread ?></span>
                        <?php endif; ?>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end shadow border-0 p-0 mt-1"
                         style="border-radius:12px;min-width:340px;overflow:hidden;">

                        <!-- Header dropdown notif -->
                        <div class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom"
                             style="background:#f8fafc;">
                            <span class="fw-bold text-dark" style="font-size:13px;">
                                <i class="bi bi-bell me-1 text-primary"></i>Notifikasi
                            </span>
                            <?php if ($notifUnread > 0): ?>
                            <span class="badge bg-danger rounded-pill" style="font-size:10px;">
                                <?= $notifUnread ?> baru
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- List notifikasi -->
                        <div style="max-height:300px;overflow-y:auto;">
                            <?php if (empty($notifList)): ?>
                            <div class="text-center py-4 text-muted" style="font-size:12px;">
                                <i class="bi bi-bell-slash d-block fs-3 mb-1 opacity-25"></i>
                                Tidak ada notifikasi
                            </div>
                            <?php else: ?>
                                <?php foreach ($notifList as $notif):
                                    $unread = !$notif['dibaca'];
                                    [$iconClass, $iconColor] = match($notif['tipe']) {
                                        'input_baru' => ['bi-plus-circle-fill',  '#3b82f6'],
                                        'disetujui'  => ['bi-check-circle-fill', '#16a34a'],
                                        'ditolak'    => ['bi-x-circle-fill',     '#dc2626'],
                                        default      => ['bi-info-circle-fill',  '#64748b'],
                                    };
                                ?>
                                <a href="<?= BASE_URL ?>/modules/realisasi/verifikasi.php?id=<?= $notif['rid'] ?>"
                                   class="d-flex gap-2 px-3 py-2 text-decoration-none border-bottom notif-item"
                                   data-notif-id="<?= $notif['id'] ?>"
                                   style="background:<?= $unread ? '#eff6ff' : '#fff' ?>;color:#1e293b;">
                                    <div class="mt-1 flex-shrink-0">
                                        <i class="bi <?= $iconClass ?>"
                                           style="color:<?= $iconColor ?>;font-size:15px;"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div style="font-size:12px;font-weight:<?= $unread ? '600' : '400' ?>;">
                                            <?= sanitize($notif['pesan']) ?>
                                        </div>
                                        <div style="font-size:10px;color:#94a3b8;margin-top:2px;">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= date('d M Y H:i', strtotime($notif['created_at'])) ?>
                                            <?php if ($notif['nama_staf']): ?>
                                            &nbsp;·&nbsp; <?= sanitize($notif['nama_staf']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($unread): ?>
                                    <div class="flex-shrink-0 mt-2">
                                        <div style="width:8px;height:8px;border-radius:50%;background:#3b82f6;"></div>
                                    </div>
                                    <?php endif; ?>
                                </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Footer dropdown notif -->
                        <div class="text-center px-3 py-2 border-top bg-light">
                            <a href="<?= BASE_URL ?>/modules/realisasi/verifikasi.php"
                               class="text-decoration-none fw-semibold" style="font-size:12px;color:#3b82f6;">
                                Lihat semua &rarr;
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ── User dropdown ── -->
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle text-decoration-none text-dark d-flex align-items-center gap-2"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                             style="width:32px;height:32px;flex-shrink:0;">
                            <i class="bi bi-person-fill" style="font-size:14px;"></i>
                        </div>
                        <div class="d-none d-sm-block">
                            <div class="fw-semibold lh-1" style="font-size:13px;">
                                <?= sanitize($_SESSION['user_nama'] ?? 'User') ?>
                            </div>
                            <div style="font-size:10px;color:#94a3b8;">
                                <?= $roleInfo[0] ?>
                            </div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-1"
                        style="border-radius:10px;min-width:200px;">
                        <li>
                            <div class="px-3 py-2">
                                <div class="fw-semibold" style="font-size:13px;">
                                    <?= sanitize($_SESSION['user_nama'] ?? '') ?>
                                </div>
                                <div>
                                    <span class="badge bg-<?= $roleInfo[1] ?>" style="font-size:10px;">
                                        <?= $roleInfo[0] ?>
                                    </span>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item py-2"
                               href="<?= BASE_URL ?>/modules/auth/profil.php">
                                <i class="bi bi-person-gear me-2 text-primary"></i>Profil & Password
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2"
                               href="<?= BASE_URL ?>/modules/auth/logout.php">
                                <i class="bi bi-box-arrow-right me-2 text-danger"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>

            </div>
        </nav><!-- end navbar -->

        <!-- FLASH MESSAGES -->
        <div class="px-4 pt-3">
            <?php if ($msg = getFlash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= sanitize($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($msg = getFlash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= sanitize($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>

        <!-- PAGE CONTENT -->
        <div class="content-area p-4">

<script>
// Tandai notif dibaca saat diklik
document.querySelectorAll('.notif-item').forEach(function(el) {
    el.addEventListener('click', function() {
        var id = this.dataset.notifId;
        if (id) fetch('<?= BASE_URL ?>/modules/realisasi/notif_baca.php?id=' + id);
    });
});
</script>