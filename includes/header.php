<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? APP_NAME ?> - <?= APP_FULLNAME ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="wrapper d-flex">
    <!-- SIDEBAR -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content flex-grow-1">
        <!-- TOP NAVBAR -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-2 sticky-top shadow-sm">
            <button class="btn btn-sm btn-outline-secondary me-3" id="sidebarToggle">
                <i class="bi bi-list fs-5"></i>
            </button>
            <span class="navbar-brand fw-bold text-muted mb-0">
                <?= $pageTitle ?? 'Dashboard' ?>
            </span>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-muted small">
                    <i class="bi bi-calendar3 me-1"></i><?= date('d M Y') ?>
                </span>
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle text-decoration-none text-dark d-flex align-items-center gap-2"
                       data-bs-toggle="dropdown">
                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <span class="small fw-semibold"><?= sanitize($_SESSION['user_nama'] ?? 'User') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li><h6 class="dropdown-header"><?= sanitize($_SESSION['user_nama'] ?? '') ?></h6></li>
                        <li><span class="dropdown-item-text text-muted small"><?= sanitize($_SESSION['user_role'] ?? '') ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/modules/auth/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

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
