-- =============================================================
-- DATABASE: procurement_db
-- SIPPA - Sistem Informasi Perencanaan & Pengadaan
-- =============================================================

CREATE DATABASE IF NOT EXISTS procurement_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE procurement_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rencana_kegiatan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kegiatan VARCHAR(255) NOT NULL,
    volume DECIMAL(15,2) NOT NULL DEFAULT 1,
    satuan VARCHAR(50) NOT NULL,
    nilai_satuan DECIMAL(20,2) NOT NULL DEFAULT 0,
    nilai_anggaran DECIMAL(20,2) NOT NULL DEFAULT 0,
    jenis_pengadaan ENUM('barang','sipil','jasa_konsultan','jasa_lainnya') NOT NULL,
    metode_pengadaan ENUM('pembelian_langsung','tender_terbatas','tender_terbatas_PKP','tender_umum','e_purchasing','swakelola') NOT NULL,
    bulan_rencana VARCHAR(30) NOT NULL,   -- menyimpan beberapa bulan, misal: "1,3,5,12"
    tahun INT NOT NULL,
    keterangan TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS realisasi_kegiatan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomor_kontrak VARCHAR(100) NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NULL,
    status ENUM('proses','selesai','batal') DEFAULT 'proses',
    total_nilai DECIMAL(20,2) DEFAULT 0,
    metode_pengadaan ENUM('pembelian_langsung','tender_terbatas','tender_terbatas_PKP','tender_umum','e_purchasing','swakelola') NOT NULL,
    catatan TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS realisasi_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    realisasi_id INT NOT NULL,
    rencana_id INT NULL,
    nama_kegiatan VARCHAR(255) NOT NULL,
    volume DECIMAL(15,2) NOT NULL DEFAULT 1,
    satuan VARCHAR(50) NOT NULL,
    nilai_satuan DECIMAL(20,2) NOT NULL DEFAULT 0,
    nilai_anggaran DECIMAL(20,2) NOT NULL DEFAULT 0,
    jenis_pengadaan ENUM('barang','sipil','jasa_konsultan','jasa_lainnya') NOT NULL,
    keterangan TEXT NULL,
    FOREIGN KEY (realisasi_id) REFERENCES realisasi_kegiatan(id) ON DELETE CASCADE,
    FOREIGN KEY (rencana_id) REFERENCES rencana_kegiatan(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ================================================================
-- AKUN ADMIN DEFAULT
-- Password disimpan dalam format PLAIN TEXT sementara.
-- Sistem akan otomatis menggantinya ke bcrypt saat login pertama.
-- Username: admin | Password: admin123
-- ================================================================
INSERT INTO users (username, password, nama_lengkap, role) VALUES
('admin', 'PLAIN:admin123', 'Administrator', 'admin');


-- ================================================================
-- JIKA DATABASE SUDAH ADA SEBELUMNYA: jalankan perintah ini
-- untuk mengubah tipe kolom bulan_rencana ke VARCHAR
-- ================================================================
-- ALTER TABLE rencana_kegiatan MODIFY COLUMN bulan_rencana VARCHAR(30) NOT NULL;

-- Contoh data rencana
INSERT INTO rencana_kegiatan (nama_kegiatan, volume, satuan, nilai_satuan, nilai_anggaran, jenis_pengadaan, metode_pengadaan, bulan_rencana, tahun, created_by) VALUES
('Pengadaan Laptop dan Aksesoris', 5, 'unit', 12000000, 60000000, 'barang', 'tender_terbatas', '3,4', 2025, 1),
('Pengadaan Alat Tulis Kantor (ATK)', 1, 'paket', 5000000, 5000000, 'barang', 'pembelian_langsung', '1,2', 2025, 1),
('Jasa Konsultan Perencanaan Teknis', 1, 'paket', 150000000, 150000000, 'jasa_konsultan', 'tender_terbatas', '2,3,4', 2025, 1),
('Pembangunan Gedung Kantor', 1, 'unit', 800000000, 800000000, 'sipil', 'tender_umum', '4,5,6,7,8', 2025, 1),
('Pengadaan Printer Multifungsi', 3, 'unit', 4500000, 13500000, 'barang', 'pembelian_langsung', '1', 2025, 1),
('Jasa Kebersihan Gedung', 12, 'bulan', 8000000, 96000000, 'jasa_lainnya', 'tender_terbatas', '1,2,3,4,5,6,7,8,9,10,11,12', 2025, 1),
('Pengadaan Kursi dan Meja Kerja', 20, 'set', 3500000, 70000000, 'barang', 'tender_terbatas', '5,6', 2025, 1),
('Renovasi Ruang Rapat', 1, 'paket', 250000000, 250000000, 'sipil', 'tender_terbatas', '6,7,8', 2025, 1);
