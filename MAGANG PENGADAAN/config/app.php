<?php
/**
 * config/app.php
 * Konfigurasi umum aplikasi
 */

// Nama aplikasi
define('APP_NAME', 'SIPEKA');
define('APP_FULLNAME', 'Sistem Pengadaan Kegiatan');

// Base URL - sesuaikan dengan nama folder project Anda di htdocs
define('BASE_URL', '/procurement');

// Batas nilai metode pengadaan (dalam Rupiah)
define('BATAS_PEMBELIAN_LANGSUNG', 15000000);        // <= 15 juta
define('BATAS_TENDER_TERBATAS_SPK', 50000000);       // 15jt - 50jt → Tender Terbatas SPK
define('BATAS_TENDER_TERBATAS_PKP', 600000000);      // 50jt - 600jt → Tender Terbatas PKP
define('BATAS_TENDER_TERBATAS', 600000000);          // <= 600 juta (backward compat)
// Tender Umum = > 600 juta

// Nama bulan dalam Bahasa Indonesia
define('NAMA_BULAN', [
    1  => 'Januari',
    2  => 'Februari',
    3  => 'Maret',
    4  => 'April',
    5  => 'Mei',
    6  => 'Juni',
    7  => 'Juli',
    8  => 'Agustus',
    9  => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember',
]);

// Label jenis pengadaan
define('LABEL_JENIS', [
    'barang'          => 'Barang',
    'sipil'           => 'Pekerjaan Sipil',
    'jasa_konsultan'  => 'Jasa Konsultan',
    'jasa_lainnya'    => 'Jasa Lainnya',
]);

// Label metode pengadaan
define('LABEL_METODE', [
    'pembelian_langsung'   => 'Pembelian Langsung',
    'tender_terbatas_spk'  => 'Tender Terbatas SPK',
    'tender_terbatas_pkp'  => 'Tender Terbatas PKP',
    'tender_terbatas'      => 'Tender Terbatas',      
    'tender_umum'          => 'Tender Umum',
    'e_purchasing'         => 'E-Purchasing',
    'swakelola'            => 'Swakelola',
]);