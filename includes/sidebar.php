<?php
/**
 * includes/sidebar.php
 * Sidebar navigasi utama aplikasi
 */

// Deteksi halaman aktif berdasarkan URL saat ini
$currentPath = $_SERVER['REQUEST_URI'];
$isActive = function($path) use ($currentPath) {
    return strpos($currentPath, $path) !== false ? 'active' : '';
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

    <!-- MENU NAVIGASI -->
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

            <!-- Separator -->
            <li class="nav-item mt-2">
                <span class="nav-section-label px-3 text-uppercase fw-semibold text-muted"
                      style="font-size:10px; letter-spacing:1px;">Manajemen Kegiatan</span>
            </li>

            <!-- Rencana Kegiatan -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/modules/rencana/index.php"
                   class="nav-link <?= $isActive('/modules/rencana') ?> rounded-2 d-flex align-items-center gap-2 px-3 py-2">
                    <i class="bi bi-journal-text nav-icon"></i>
                    <span>Rencana Kegiatan</span>
                </a>
            </li>

            <!-- Realisasi Kegiatan -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/modules/realisasi/index.php"
                   class="nav-link <?= $isActive('/modules/realisasi') ?> rounded-2 d-flex align-items-center gap-2 px-3 py-2">
                    <i class="bi bi-check2-circle nav-icon"></i>
                    <span>Realisasi Kegiatan</span>
                </a>
            </li>

            <!-- Laporan -->
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/modules/laporan/index.php"
                   class="nav-link <?= $isActive('/modules/laporan') ?> rounded-2 d-flex align-items-center gap-2 px-3 py-2">
                    <i class="bi bi-bar-chart-line-fill nav-icon"></i>
                    <span>Laporan</span>
                </a>
            </li>

            <!-- Separator -->
            <li class="nav-item mt-2">
                <span class="nav-section-label px-3 text-uppercase fw-semibold text-muted"
                      style="font-size:10px; letter-spacing:1px;">Administrasi</span>
            </li>

            <!-- Users (hanya admin) -->
            <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
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

    <!-- SIDEBAR FOOTER -->
    <div class="sidebar-footer border-top p-3 mt-auto">
        <div class="d-flex align-items-center gap-2">
            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:32px; height:32px;">
                <i class="bi bi-person-fill" style="font-size:14px;"></i>
            </div>
            <div class="overflow-hidden">
                <div class="fw-semibold text-truncate" style="font-size:13px;">
                    <?= sanitize($_SESSION['user_nama'] ?? 'User') ?>
                </div>
                <div class="text-muted text-capitalize" style="font-size:11px;">
                    <?= sanitize($_SESSION['user_role'] ?? '') ?>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/modules/auth/logout.php"
               class="btn btn-sm btn-light ms-auto" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</div>