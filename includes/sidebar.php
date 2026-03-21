<?php
/**
 * includes/sidebar.php — role-aware
 */
$currentPath = $_SERVER['REQUEST_URI'];
$isActive = fn($path) => strpos($currentPath, $path) !== false ? 'active' : '';
$role     = $_SESSION['user_role'] ?? 'staf_pengadaan';
$isAdmin  = $role === 'admin';
$isMgr    = in_array($role, ['admin', 'manajer_pengadaan']);

// Hitung notif belum dibaca (hanya untuk manajer/admin)
$notifCount = 0;
if ($isMgr) {
    $uid   = (int)($_SESSION['user_id'] ?? 0);
    $qNotif = $GLOBALS['db'] ?? null;
    if (!$qNotif) { $qNotif = getDB(); }
    $nRes = $qNotif->query("
        SELECT COUNT(*) as c FROM notifikasi
        WHERE untuk_role IN ('manajer_pengadaan','admin')
          AND dibaca = 0
          AND (untuk_user_id IS NULL OR untuk_user_id = $uid)
    ");
    $notifCount = $nRes ? (int)$nRes->fetch_assoc()['c'] : 0;
}

// Label & warna badge role
$roleLabel = match($role) {
    'admin'             => ['Admin',    '#ef4444'],
    'manajer_pengadaan' => ['Manajer',  '#3b82f6'],
    'staf_pengadaan'    => ['Staf',     '#8b5cf6'],
    default             => ['User',     '#64748b'],
};
?>

<div class="sidebar bg-white border-end" id="sidebar">
    <!-- LOGO / BRAND -->
    <div class="sidebar-brand border-bottom p-3">
        <a href="<?= BASE_URL ?>/index.php" class="text-decoration-none d-flex align-items-center gap-2">
            <div class="brand-icon d-flex align-items-center justify-content-center flex-shrink-0">
                <!-- PERBAIKAN: gunakan BASE_URL konstanta biasa, bukan fungsi -->
                <img src="<?= BASE_URL ?>/includes/LOGO.png"
                     alt="Logo"
                     style="height:65px; width:65px; object-fit:contain;">
            </div>
            <div class="brand-text">
                <div class="fw-bold text-primary lh-1" style="font-size:17px;">
                    PT Air Minum Giri Menang (Perseroda)
                </div>
                <div class="text-muted" style="font-size:11px;"> Sistem Rekapan Pengadaan</div>
            </div>
        </a>
    </div>
    <!-- MENU -->
    <nav class="sidebar-nav py-3 px-2">
        <ul class="nav flex-column gap-1">

            <!-- Dashboard -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/index.php"
                   class="nav-link <?= $isActive('/index.php') ?> rounded-2 d-flex align-items-center gap-2 px-3 py-2">
                    <i class="bi bi-grid-1x2-fill nav-icon"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- ── MANAJEMEN KEGIATAN ── -->
            <li class="nav-item mt-2">
                <span class="nav-section-label px-3">MANAJEMEN KEGIATAN</span>
            </li>

            <!-- Rencana: semua role lihat; hanya manajer/admin yang bisa tambah (dikontrol di halaman) -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/modules/rencana/index.php"
                   class="nav-link <?= $isActive('/modules/rencana') ?> rounded-2 d-flex align-items-center gap-2 px-3 py-2">
                    <i class="bi bi-journal-text nav-icon"></i>
                    <span>Rencana Kegiatan</span>
                </a>
            </li>

            <!-- Realisasi: semua role -->
            <?php
                $realisasiActive   = $isActive('/modules/realisasi') !== '';
                $verifikasiActive  = $isActive('/modules/realisasi/verifikasi') !== '';
                // Dropdown terbuka otomatis jika sedang di halaman realisasi/verifikasi
                $dropdownOpen = ($isMgr && $realisasiActive) ? 'show' : '';
            ?>
            <li class="nav-item">
                <?php if ($isMgr): ?>
                <!-- Trigger dropdown (bukan link langsung) untuk manajer/admin -->
                <a href="#submenu-realisasi"
                   class="nav-link <?= $realisasiActive ? 'active' : '' ?> rounded-2 d-flex align-items-center gap-2 px-3 py-2"
                   data-bs-toggle="collapse"
                   aria-expanded="<?= $dropdownOpen ? 'true' : 'false' ?>"
                   aria-controls="submenu-realisasi"
                   style="cursor:pointer;">
                    <i class="bi bi-check2-circle nav-icon"></i>
                    <span class="flex-grow-1">Realisasi Kegiatan</span>
                    <i class="bi bi-chevron-down submenu-arrow"
                       style="font-size:11px; transition:transform .25s;
                              <?= $dropdownOpen ? 'transform:rotate(180deg);' : '' ?>"></i>
                </a>

                <!-- Submenu collapse -->
                <div class="collapse <?= $dropdownOpen ?>" id="submenu-realisasi">
                    <ul class="nav flex-column gap-1 mt-1" style="padding-left:0.85rem;">
                        <!-- Link ke halaman realisasi utama -->
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/modules/realisasi/index.php"
                               class="nav-link <?= (!$verifikasiActive && $realisasiActive) ? 'active' : '' ?> rounded-2 d-flex align-items-center gap-2 px-3 py-2">
                                <i class="bi bi-list-check nav-icon" style="font-size:13px;"></i>
                                <span style="font-size:13px;">Daftar Realisasi</span>
                            </a>
                        </li>
                        <!-- Verifikasi Input -->
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/modules/realisasi/verifikasi.php"
                               class="nav-link <?= $verifikasiActive ? 'active' : '' ?> rounded-2 d-flex align-items-center gap-2 px-3 py-2">
                                <i class="bi bi-shield-check nav-icon" style="font-size:13px;"></i>
                                <span class="flex-grow-1" style="font-size:13px;">Verifikasi Input</span>
                                <?php if ($notifCount > 0): ?>
                                <span class="badge rounded-pill ms-auto"
                                      style="background:#ef4444;font-size:10px;min-width:20px;">
                                    <?= $notifCount ?>
                                </span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>
                </div>

                <?php else: ?>
                <!-- Staf: link langsung tanpa dropdown -->
                <a href="<?= BASE_URL ?>/modules/realisasi/index.php"
                   class="nav-link <?= $realisasiActive ? 'active' : '' ?> rounded-2 d-flex align-items-center gap-2 px-3 py-2">
                    <i class="bi bi-check2-circle nav-icon"></i>
                    <span>Realisasi Kegiatan</span>
                </a>
                <?php endif; ?>
            </li>

            <style>
            /* Rotate chevron saat collapse terbuka */
            a[aria-expanded="true"] .submenu-arrow {
                transform: rotate(180deg) !important;
            }
            /* Garis kiri submenu */
            #submenu-realisasi .nav {
                border-left: 2px solid rgba(255,255,255,0.15);
                margin-left: 1.1rem;
            }
            </style>

            <!-- Laporan & Vendor: semua role lihat -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/modules/vendor/index.php"
                   class="nav-link <?= $isActive('/modules/vendor') ?> rounded-2 d-flex align-items-center gap-2 px-3 py-2">
                    <i class="bi bi-building-check nav-icon"></i>
                    <span>Data Vendor</span>
                </a>
            </li>
                         
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/modules/laporan/index.php"
                   class="nav-link <?= $isActive('/modules/laporan') ?> rounded-2 d-flex align-items-center gap-2 px-3 py-2">
                    <i class="bi bi-bar-chart-line-fill nav-icon"></i>
                    <span>Laporan</span>
                </a>
            </li>



            <!-- ── ADMINISTRASI ── -->
            <li class="nav-item mt-2">
                <span class="nav-section-label px-3">ADMINISTRASI</span>
            </li>

            <!-- Profil: semua role -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/modules/auth/profil.php"
                   class="nav-link <?= $isActive('/modules/auth/profil') ?> rounded-2 d-flex align-items-center gap-2 px-3 py-2">
                    <i class="bi bi-person-gear nav-icon"></i>
                    <span>Profil & Password</span>
                </a>
            </li>

            <!-- Manajemen User: admin only -->
            <?php if ($isAdmin): ?>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/modules/users/index.php"
                   class="nav-link <?= $isActive('/modules/users') ?> rounded-2 d-flex align-items-center gap-2 px-3 py-2">
                    <i class="bi bi-people-fill nav-icon"></i>
                    <span>Manajemen User</span>
                </a>
            </li>
            <?php endif; ?>

        </ul>
    </nav>

    <!-- FOOTER SIDEBAR -->
    <div class="sidebar-footer px-3 py-3 mt-auto">
        <div class="d-flex align-items-center gap-2">
            <div class="avatar-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                <i class="bi bi-person-fill" style="font-size:14px;color:white;"></i>
            </div>
            <div class="overflow-hidden flex-grow-1">
                <div class="d-flex align-items-center gap-1">
                    <div class="fw-semibold text-truncate" style="font-size:13px;color:#fff;">
                        <?= sanitize($_SESSION['user_nama'] ?? 'User') ?>
                    </div>
                    <span class="badge rounded-pill" style="font-size:9px;background:<?= $roleLabel[1] ?>;">
                        <?= $roleLabel[0] ?>
                    </span>
                </div>
                <div class="text-capitalize" style="font-size:11px;color:rgba(255,255,255,0.5);">
                    <?= sanitize(str_replace('_', ' ', $role)) ?>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/modules/auth/logout.php"
               class="btn btn-sm" title="Logout"
               style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);color:white;">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</div>