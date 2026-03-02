-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 02 Mar 2026 pada 06.17
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `procurement_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `realisasi_detail`
--

CREATE TABLE `realisasi_detail` (
  `id` int(11) NOT NULL,
  `realisasi_id` int(11) NOT NULL,
  `rencana_id` int(11) DEFAULT NULL,
  `nama_kegiatan` varchar(255) NOT NULL,
  `volume` decimal(15,2) NOT NULL DEFAULT 1.00,
  `satuan` varchar(50) NOT NULL,
  `nilai_satuan` decimal(20,2) NOT NULL DEFAULT 0.00,
  `nilai_anggaran` decimal(20,2) NOT NULL DEFAULT 0.00,
  `jenis_pengadaan` enum('barang','sipil','jasa_konsultan','jasa_lainnya') NOT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `realisasi_detail`
--

INSERT INTO `realisasi_detail` (`id`, `realisasi_id`, `rencana_id`, `nama_kegiatan`, `volume`, `satuan`, `nilai_satuan`, `nilai_anggaran`, `jenis_pengadaan`, `keterangan`) VALUES
(37, 11, NULL, 'Pembangunan Pelindung Mata Air dari Tanah Longsor Mata Air Lembah Sempage', 1.00, 'keg', 39696000.00, 39696000.00, 'sipil', 'Tidak Ada di RPK Perubahan 2025'),
(40, 12, NULL, 'Pemasangan Pipa PVC Ø 2\'\' Sepanjang 462 m dan Pipa  GI Ø 2\'\' Sepanjang 6 m Untuk Perbaikan Jaringan Pipa', 1.00, 'keg', 69534000.00, 69534000.00, 'sipil', 'Tidak Ada di RPK Perubahan 2025'),
(41, 13, 40, 'Pembuatan bak penampungan limbah kaporit', 1.00, 'keg', 110832000.00, 110832000.00, 'sipil', ''),
(42, 14, 41, 'DED Perencanaan Pembangunan Prasedimentasi dan Optimalisasi Intake Sumber Air Remeneng', 1.00, 'keg', 297060000.00, 297060000.00, 'jasa_konsultan', ''),
(43, 15, 47, 'Mesin Mesin Untuk WTP Sembung', 1.00, 'keg', 522426000.00, 522426000.00, 'barang', ''),
(51, 17, 53, 'Instalasi Sumber Lainnya Butterfly valve Wafer with pneumatic Dia. 4\"', 1.00, 'keg', 118435000.00, 118435000.00, 'barang', ''),
(52, 17, 54, 'Butterfly valve Wafer with pneumatic Actuator Double Acting and Positioner Dia. 12\"', 1.00, 'keg', 118435000.00, 118435000.00, 'barang', ''),
(53, 17, 68, 'Instalasi Sumber Lainnya Selenoid', 1.00, 'keg', 118435000.00, 118435000.00, 'barang', ''),
(55, 16, 49, 'Turbidity meter Air Baku dan air Bersih,pembacaan 0 - 2000 NTU,termasuk on-line turbidity sensor,transmitter dan holder', 1.00, 'keg', 522426000.00, 522426000.00, 'barang', ''),
(56, 18, 66, 'Instalasi Sumber Lainnya Vertical Mixer With Gear Box (Daya Motor : 0,75 Kw/380 V/50 Hz/1400 RPM/3-Phase ; Diameter Shaft : 22 mm ; Tinggi Shaft : 1600 mm ; Impeller : 2 ea ; Shaft & Impeller : SS304 ; Rasio Gear Box : 1:5)', 1.00, 'keg', 127120000.00, 127120000.00, 'barang', ''),
(57, 19, NULL, 'Instalasi Sumber Lainnya Pengadaan Arrester dan Grounding, dan Panel MDP untuk Kebutuhan Bidang Produksi PT Air Minum Giri Menang (Perseroda)', 1.00, 'keg', 83100000.00, 83100000.00, 'barang', 'Tidak Ada di RPK Perubahan 2025'),
(61, 23, 75, 'Instalasi Pompa Lainnya Pergantian ATS SB GPA 1', 1.00, 'keg', 69176000.00, 69176000.00, 'barang', ''),
(62, 21, 45, 'Perbaikan Talud batu Kali, Rabat Beton, Plat Beton Bertulang dan Boring Pipa', 1.00, 'keg', 32838000.00, 32838000.00, 'sipil', ''),
(63, 20, 39, 'Pemasangan Pipa HDPE 12\" akibat Longsor dan Reposisi Pipa Transmisi Intake Serepak', 1.00, 'keg', 181663000.00, 181663000.00, 'sipil', ''),
(64, 22, 74, 'Peralatan Pompa Pemasangan casing SB Gora Town', 1.00, 'keg', 102129000.00, 102129000.00, 'sipil', ''),
(65, 24, 76, 'Instalasi Pompa Lainnya Pengadaan dan Pemasangan Panel Inverter 45 KW', 1.00, 'keg', 105000000.00, 105000000.00, 'barang', ''),
(66, 24, 77, 'Instalasi Pompa Lainnya Pengadaan dan Pemasangan Panel Inverter 22 KW', 1.00, 'keg', 104000000.00, 104000000.00, 'barang', ''),
(67, 24, 70, 'Instalasi Sumber Lainnya Kontaktor', 1.00, 'keg', 5224260.00, 5224260.00, 'barang', ''),
(68, 25, 89, 'Logger Pressure (Display) untuk Bidang Distribusi', 1.00, 'keg', 329095000.00, 329095000.00, 'barang', ''),
(69, 26, 90, 'Logger Pressure (Portable) untuk Bidang Distribusi', 1.00, 'keg', 203895000.00, 203895000.00, 'barang', ''),
(70, 27, 99, 'Box Panel', 1.00, 'buah', 42000000.00, 42000000.00, 'barang', ''),
(71, 28, 111, 'Lain-Lain Transdit Sparepart Telemetri : Modbus Mag 6000', 1.00, 'unit', 6952000.00, 6952000.00, 'barang', ''),
(72, 28, 103, 'Lain-Lain Transdit Sparepart Telemetri : Baterai transmiter Mag 8000', 10.00, 'unit', 9534000.00, 95340000.00, 'barang', ''),
(73, 28, 104, 'Lain-Lain Transdit Sparepart Telemetri : Baterai Logger Sofrel', 5.00, 'unit', 9361000.00, 46805000.00, 'barang', ''),
(74, 28, 105, 'Lain-Lain Transdit Sparepart Telemetri : Baterai Logger Ijinus', 5.00, 'unit', 9361000.00, 46805000.00, 'barang', ''),
(75, 28, 106, 'Lain-Lain Transdit Sparepart Telemetri : Kabel Koil dan Kabel electrode', 2.00, 'set', 7800000.00, 15600000.00, 'barang', ''),
(76, 28, 108, 'Lain-Lain Transdit Sparepart Telemetri : Modbus Mag 8000', 3.00, 'set', 8637000.00, 25911000.00, 'barang', ''),
(77, 28, 110, 'Lain-Lain Transdit Sparepart Telemetri : PCB Mag 8000', 2.00, 'unit', 75771000.00, 151542000.00, 'barang', ''),
(78, 28, 109, 'Lain-Lain Transdit Sparepart Telemetri : GPRS PLC', 3.00, 'set', 23384000.00, 70152000.00, 'barang', ''),
(79, 28, 107, 'Lain-Lain Transdit Sparepart Telemetri : Modbus PLC', 1.00, 'unit', 41873000.00, 41873000.00, 'barang', ''),
(80, 29, 111, 'Lain-Lain Transdit Sparepart Telemetri : Modbus Mag 6000', 1.00, 'unit', 6952000.00, 6952000.00, 'barang', ''),
(81, 29, 103, 'Lain-Lain Transdit Sparepart Telemetri : Baterai transmiter Mag 8000', 10.00, 'unit', 9534000.00, 95340000.00, 'barang', ''),
(82, 29, 105, 'Lain-Lain Transdit Sparepart Telemetri : Baterai Logger Ijinus', 5.00, 'unit', 9361000.00, 46805000.00, 'barang', ''),
(83, 29, 104, 'Lain-Lain Transdit Sparepart Telemetri : Baterai Logger Sofrel', 5.00, 'unit', 9361000.00, 46805000.00, 'barang', ''),
(84, 29, 106, 'Lain-Lain Transdit Sparepart Telemetri : Kabel Koil dan Kabel electrode', 1.00, 'set', 7800000.00, 7800000.00, 'barang', ''),
(85, 29, 108, 'Lain-Lain Transdit Sparepart Telemetri : Modbus Mag 8000', 2.00, 'set', 8637000.00, 17274000.00, 'barang', ''),
(86, 29, 110, 'Lain-Lain Transdit Sparepart Telemetri : PCB Mag 8000', 1.00, 'unit', 75771000.00, 75771000.00, 'barang', ''),
(87, 29, 109, 'Lain-Lain Transdit Sparepart Telemetri : GPRS PLC', 3.00, 'set', 23384000.00, 70152000.00, 'barang', ''),
(88, 29, 107, 'Lain-Lain Transdit Sparepart Telemetri : Modbus PLC', 1.00, 'unit', 41873000.00, 41873000.00, 'barang', ''),
(89, 30, 92, 'Lain-Lain Transdit Manometer (4 Bar) Glysterin untuk Bidang Distribusi', 1.00, 'keg', 14625000.00, 14625000.00, 'barang', ''),
(90, 31, 92, 'Lain-Lain Transdit Manometer (4 Bar) Glysterin untuk Bidang Distribusi', 1.00, 'keg', 14625000.00, 14625000.00, 'barang', ''),
(91, 32, 102, 'Lain-Lain Transdit Pembuatan bak meter untuk meter induk', 1.00, 'keg', 51350000.00, 51350000.00, 'sipil', ''),
(93, 33, 126, 'Pembuatan Gudang Perangkat dan Peralatan IT di Gudang Gegutu', 1.00, 'keg', 69467000.00, 69467000.00, 'sipil', ''),
(94, 34, 125, 'Pembuatan Lantai Plat Dan Dinding  Gudang Terbuka di Gudang Gegutu', 1.00, 'keg', 100000000.00, 100000000.00, 'sipil', ''),
(95, 35, 120, 'Rehabilitasi (Pergantian) Kamar Mandi di Ruang Pelayanan Kantor Pusat', 1.00, 'keg', 34261000.00, 34261000.00, 'sipil', ''),
(96, 36, 121, 'Grounding Penangkal Petir Wilayah Mataram', 1.00, 'keg', 90000000.00, 90000000.00, 'sipil', ''),
(97, 36, 123, 'Grounding Penangkal Petir Wilayah Gunungsari', 1.00, 'keg', 80000000.00, 80000000.00, 'sipil', ''),
(98, 36, 122, 'Grounding Penangkal Petir Wilayah Gerung', 1.00, 'keg', 90000000.00, 90000000.00, 'sipil', ''),
(100, 37, 42, 'Pemasangan Pagar SB Gora Town Palace', 1.00, 'keg', 120000000.00, 120000000.00, 'sipil', ''),
(104, 41, 170, 'Mesin Penghitung Uang Kecil', 2.00, 'unit', 4135000.00, 8270000.00, 'barang', ''),
(107, 42, 172, 'Proyektor : SPI', 1.00, 'unit', 13270000.00, 13270000.00, 'barang', ''),
(108, 42, 140, 'Notebook : SPI', 1.00, 'unit', 17941000.00, 17941000.00, 'barang', ''),
(109, 43, NULL, 'Notebook : Teknologi Informasi', 1.00, 'unit', 15000000.00, 15000000.00, 'barang', 'Tidak di Jadwalkan dalam RPK Perubahan 2025'),
(110, 44, 139, 'Notebook : Aset', 1.00, 'unit', 13768000.00, 13768000.00, 'barang', 'Perubahan Metode Pengadaan'),
(111, 45, NULL, 'printer dan handphone untuk TI', 1.00, 'paket', 16000000.00, 16000000.00, 'barang', 'Tambahan Pengadaan terdata kegiatan printer dan handphone untuk TI jadi satu.'),
(112, 46, 145, 'Printer L15150 : Aset', 1.00, 'unit', 23500000.00, 23500000.00, 'barang', ''),
(113, 47, NULL, 'AC Daikin 2 PK untuk Pelayanan', 1.00, 'unit', 9115000.00, 9115000.00, 'barang', 'Tambahan Pengadaan'),
(114, 48, NULL, 'AC Daikin 2 PK untuk Pelayanan', 1.00, 'unit', 9115000.00, 9115000.00, 'barang', 'Tambahan Pengadaan'),
(115, 49, 154, 'AC Daikin (2 PK) : Manager Aset', 1.00, 'unit', 9115000.00, 9115000.00, 'barang', ''),
(116, 50, NULL, 'AC Daikin (2 PK) : Sekretariat Perusahaan', 1.00, 'unit', 9115000.00, 9115000.00, 'barang', 'Tambahan Pengadaan'),
(117, 51, 159, 'TV LED : Distribusi (43\")', 1.00, 'unit', 14350000.00, 14350000.00, 'barang', ''),
(118, 52, NULL, 'Komputer untuk Teknologi Informasi', 1.00, 'unit', 13000000.00, 13000000.00, 'barang', 'Tambahan Pengadaan'),
(119, 53, NULL, 'Mini PC 24\" untuk IT', 1.00, 'unit', 14350000.00, 14350000.00, 'barang', 'Tambahan Pengadaan'),
(120, 54, NULL, 'Monitor 24\" untuk IT', 1.00, 'unit', 13000000.00, 13000000.00, 'barang', 'Tambahan Pengadaan'),
(121, 55, 153, 'AC Daikin (1 PK) : Manager Aset', 1.00, 'unit', 5775000.00, 5775000.00, 'barang', ''),
(122, 56, NULL, 'Monitor 27\" untuk IT', 1.00, 'unit', 13000000.00, 13000000.00, 'barang', 'Tambahan Pengadaan'),
(123, 57, 166, 'Papper Shredder : Keuangan', 1.00, 'unit', 7500000.00, 7500000.00, 'barang', ''),
(124, 58, 168, 'Papper Shredder : Aset', 1.00, 'unit', 7500000.00, 7500000.00, 'barang', ''),
(125, 59, 167, 'Papper Shredder : Distribusi', 1.00, 'unit', 7500000.00, 7500000.00, 'barang', ''),
(126, 60, 145, 'Printer L15150 : Manager Aset', 1.00, 'unit', 13500000.00, 13500000.00, 'barang', ''),
(127, 61, NULL, 'Printer L15150 : IT', 1.00, 'unit', 13500000.00, 13500000.00, 'barang', 'Tambahan Pengadaan'),
(128, 62, 146, 'Printer L5190 : Pelayanan', 1.00, 'unit', 4000000.00, 4000000.00, 'barang', ''),
(129, 63, 173, 'Tambahan Mesin-Mesin Kantor Firewall', 2.00, 'unit', 175000000.00, 350000000.00, 'barang', ''),
(130, 64, 174, 'Tambahan Mesin-Mesin Kantor untuk IT Switch Core Aruba HPE,Switch Distribusi Aruba HPE', 1.00, 'unit', 520000000.00, 520000000.00, 'barang', ''),
(131, 65, 179, 'Tambahan Mesin-Mesin Kantor untuk IT Switch Manage Aruba', 2.00, 'unit', 60000000.00, 120000000.00, 'barang', ''),
(132, 65, 178, 'Tambahan Mesin-Mesin Kantor untuk IT Switch Unmanage 5 port', 8.00, 'unit', 12000000.00, 96000000.00, 'barang', ''),
(133, 65, 177, 'Tambahan Mesin-Mesin Kantor untuk IT Switch Unmanage 8 port', 5.00, 'unit', 6250000.00, 31250000.00, 'barang', ''),
(134, 65, 180, 'Tambahan Mesin-Mesin Kantor untuk IT HDD CCTV', 5.00, 'unit', 50000000.00, 250000000.00, 'barang', ''),
(135, 65, 181, 'Tambahan Mesin-Mesin Kantor untuk IT Media Converter FO 10G', 4.00, 'unit', 20000000.00, 80000000.00, 'barang', ''),
(136, 66, 176, 'Tambahan Mesin-Mesin Kantor untuk IT Server DRC', 1.00, 'unit', 300000000.00, 300000000.00, 'barang', ''),
(137, 67, 183, 'Tambahan Mesin-Mesin Kantor Videotron', 1.00, 'unit', 237500000.00, 237500000.00, 'barang', ''),
(138, 68, 184, 'Tambahan Mesin-Mesin Kantor ATS & Panel di Kantor Pusat Mataram (Aula)', 1.00, 'set', 117000000.00, 117000000.00, 'barang', ''),
(139, 69, 203, 'Peralatan dan Perlengkapan Kerja Pengadaan dan Pemasangan Fire Alert System Ruang Server', 1.00, 'set', 150000000.00, 150000000.00, 'barang', ''),
(140, 70, NULL, 'Pengadaan Kalender dan Buku Agenda Untuk Kebutuhan Eksternal dan Internal', 1.00, 'set', 16000000.00, 16000000.00, 'barang', 'Tidak Ada di RPK Perubahan 2025'),
(141, 71, 191, 'Peralatan dan Perlengkapan Kerja Mesin Genset Besar : Kehilangan Air', 2.00, 'buah', 4750000.00, 9500000.00, 'barang', ''),
(142, 72, 200, 'Peralatan dan Perlengkapan Kerja Mesin Pompa Hisap+selang : Kehilangan Air', 2.00, 'buah', 5450000.00, 10900000.00, 'barang', ''),
(143, 73, 193, 'Peralatan dan Perlengkapan Kerja Jack Hammer : Kehilangan Air', 1.00, 'buah', 13950000.00, 13950000.00, 'barang', ''),
(144, 74, 192, 'Peralatan dan Perlengkapan Kerja Jack Hammer : Distribusi', 4.00, 'buah', 3650500.00, 14602000.00, 'barang', ''),
(145, 75, 199, 'Peralatan dan Perlengkapan Kerja GPS Garmin : DSMT', 1.00, 'buah', 10707000.00, 10707000.00, 'barang', ''),
(146, 76, 202, 'Peralatan dan Perlengkapan Kerja Mesin Potong Rumput : Umum', 2.00, 'buah', 5700000.00, 11400000.00, 'barang', ''),
(147, 77, 204, 'Peralatan Gudang Rak besi Gudang A', 1.00, 'keg', 180000000.00, 180000000.00, 'sipil', ''),
(148, 78, 207, 'Perabot Kantor Lemari Arsip : Pelayanan', 1.00, 'unit', 4180000.00, 4180000.00, 'barang', ''),
(149, 79, 207, 'Perabot Kantor Lemari Arsip : Pelayanan', 2.00, 'unit', 4180000.00, 8360000.00, 'barang', ''),
(150, 80, 208, 'Perabot Kantor Lemari Arsip : Sekretariat Perusahaan', 2.00, 'unit', 4180000.00, 8360000.00, 'barang', ''),
(151, 81, 211, 'Perabot Kantor Lemari Arsip : Manager Aset', 2.00, 'unit', 3300000.00, 6600000.00, 'barang', ''),
(152, 82, 211, 'Perabot Kantor Lemari Arsip : Aset', 2.00, 'unit', 3300000.00, 6600000.00, 'barang', ''),
(153, 83, 210, 'Perabot Kantor Lemari Arsip : Renbang', 1.00, 'unit', 4180000.00, 4180000.00, 'barang', ''),
(154, 84, 206, 'Perabot Kantor Lemari Arsip : Umum', 1.00, 'unit', 4180000.00, 4180000.00, 'barang', ''),
(156, 86, 218, 'Perabot Kantor Kursi Kerja : Kehilangan Air', 2.00, 'unit', 3543000.00, 7086000.00, 'barang', ''),
(159, 88, 214, 'Perabot Kantor Kursi Kerja :  Pelayanan', 1.00, 'unit', 3543000.00, 3543000.00, 'barang', ''),
(160, 89, 220, 'Perabot Kantor Kursi Kerja : Keuangan', 2.00, 'unit', 3543000.00, 7086000.00, 'barang', ''),
(161, 90, 213, 'Perabot Kantor Kursi Kerja : Umum', 4.00, 'unit', 3543000.00, 14172000.00, 'barang', ''),
(162, 91, 240, 'Perabot Kantor Kursi Kerja : Renbang', 3.00, 'unit', 3543000.00, 10629000.00, 'barang', ''),
(165, 94, 221, 'Perabot Kantor Meja Kerja : Umum', 2.00, 'unit', 4409000.00, 8818000.00, 'barang', ''),
(167, 95, 232, 'Perabot Kantor Meja Pingpong Umum', 1.00, 'unit', 11500000.00, 11500000.00, 'barang', ''),
(168, 93, 222, 'Perabot Kantor Meja Kerja : Sekretariat Perusahaan', 2.00, 'unit', 4409000.00, 8818000.00, 'barang', ''),
(169, 92, 225, 'Perabot Kantor Meja Kerja : Kehilangan Air', 3.00, 'unit', 4409000.00, 13227000.00, 'barang', ''),
(170, 87, 214, 'Perabot Kantor Kursi Kerja :  Pelayanan', 1.00, 'unit', 3543000.00, 3543000.00, 'barang', ''),
(171, 85, 218, 'Perabot Kantor Kursi Kerja : Kehilangan Air', 1.00, 'unit', 3543000.00, 3543000.00, 'barang', ''),
(173, 38, NULL, 'Bangunan Lainnya Pengaspalan Kembali Bekas Galian  Pipa PT Air Minum Giri Menang (Perseroda)', 20.00, 'keg', 935000.00, 18700000.00, 'sipil', 'Tidak Ada di RPK Perubahan 2025'),
(174, 39, 124, 'Pemagaran Kawat Duri Tanah Gegutu', 1.00, 'keg', 236823000.00, 236823000.00, 'sipil', ''),
(175, 96, 229, 'Perabot Kantor Meja Manager : Aset', 1.00, 'unit', 3900000.00, 3900000.00, 'barang', ''),
(176, 97, 229, 'Perabot Kantor Meja Manager : Aset', 1.00, 'unit', 3900000.00, 3900000.00, 'barang', ''),
(177, 40, NULL, 'Komputer,Mini PC 24\",Mini Komputer,Notebook,Printer,Monitor 24\"dan Monitor 27\" untuk Teknologi Informasi', 1.00, 'set', 525368000.00, 525368000.00, 'barang', 'PENAMBAHAN PENGADAAN'),
(178, 98, 231, 'Perabot Kantor Kursi Manager : Aset', 1.00, 'unit', 3500000.00, 3500000.00, 'barang', ''),
(179, 99, 185, 'Alat-Alat Laboratorium Turbiditimeter,Alat pH Meter,TDS Meter ,Conductivity Meter,Autoklaf,Hotplate Magnetic Stirer', 1.00, 'set', 410000000.00, 410000000.00, 'barang', ''),
(180, 100, 186, 'Alat-Alat Laboratorium pH Meter : Produksi', 1.00, 'unit', 35000000.00, 35000000.00, 'barang', ''),
(181, 100, 187, 'Alat-Alat Laboratorium TDS Meter : Produksi', 1.00, 'unit', 15000000.00, 15000000.00, 'barang', ''),
(182, 101, 187, 'Alat-Alat Laboratorium TDS Meter : Produksi', 1.00, 'unit', 15000000.00, 15000000.00, 'barang', ''),
(183, 101, 186, 'Alat-Alat Laboratorium pH Meter : Produksi', 1.00, 'unit', 35000000.00, 35000000.00, 'barang', ''),
(184, 102, 189, 'Alat-Alat Laboratorium Turbidimeter : Desain dan Mutu', 1.00, 'unit', 55000000.00, 55000000.00, 'barang', ''),
(185, 103, 189, 'Alat-Alat Laboratorium Turbidimeter : Desain dan Mutu', 1.00, 'unit', 55000000.00, 55000000.00, 'barang', ''),
(186, 104, NULL, 'Dokumen Geolistrik', 1.00, 'keg', 42860000.00, 42860000.00, 'jasa_konsultan', 'Tambahan Pengadaan'),
(187, 105, NULL, 'Dokumen UKL/UPL & DOKUMEN KELAYAKAN SB', 1.00, 'keg', 298340000.00, 298340000.00, 'jasa_konsultan', ''),
(188, 106, NULL, 'Pengukuran Debit Sumber Air Lukatan dan Sungai Dodokan Untuk Potensi Air PT Air Minum Giri Menang', 1.00, 'keg', 245543000.00, 245543000.00, 'jasa_konsultan', 'Tambahan Pengadaan'),
(189, 107, NULL, 'Bimtek dan Penyusunan Rencana Pengamanan Air Minum PT Air Minum Giri Menang (Perseroda)', 1.00, 'unit', 297951000.00, 297951000.00, 'jasa_konsultan', 'Tambahan Pengadaan'),
(190, 108, NULL, 'Dokumen Uji Pumping Pembaharuan Sumur Bor', 1.00, 'keg', 233672000.00, 233672000.00, 'jasa_konsultan', ''),
(191, 109, 116, 'Lain-Lain Transdit Dokumen DED : Reviu Masterplan Pengendalian Kehilangan Air', 1.00, 'keg', 298723000.00, 298723000.00, 'jasa_konsultan', ''),
(192, 110, 114, 'Lain-Lain Transdit Dokumen DED : Evaluasi Jaringan Distribusi Wilayah Pelayanan Kota Mataram', 1.00, 'keg', 300000000.00, 300000000.00, 'jasa_konsultan', ''),
(193, 111, 235, 'Pengembangan Jaringan Aksesoris untuk pengembangan', 1.00, 'set', 282102400.00, 282102400.00, 'barang', ''),
(194, 112, 235, 'Pengembangan Jaringan Aksesoris untuk pengembangan', 1.00, 'set', 282102400.00, 282102400.00, 'barang', ''),
(195, 113, 235, 'Pengembangan Jaringan Aksesoris untuk pengembangan', 1.00, 'set', 282102400.00, 282102400.00, 'barang', ''),
(196, 114, 235, 'Pengembangan Jaringan Aksesoris untuk pengembangan', 1.00, 'set', 282102400.00, 282102400.00, 'barang', ''),
(198, 116, 235, 'Pengembangan Jaringan Aksesoris untuk pengembangan', 1.00, 'set', 282102400.00, 282102400.00, 'barang', ''),
(199, 117, NULL, 'Pengembangan Jaringan Aksesoris untuk Pemeliharaan Jaringan', 1.00, 'set', 138101000.00, 138101000.00, 'barang', 'Tidak Ada di RPK Perubahan 2025'),
(200, 118, NULL, 'Pengembangan Jaringan Aksesoris untuk Pemeliharaan Jaringan', 1.00, 'keg', 138101000.00, 138101000.00, 'barang', 'Tidak Ada di RPK Perubahan 2025'),
(201, 119, NULL, 'Pengembangan Jaringan  Aksesoris untuk Pemeliharaan Jaringan', 1.00, 'keg', 138101000.00, 138101000.00, 'barang', 'Tidak Ada di RPK Perubahan 2025'),
(202, 120, NULL, 'Pengembangan Jaringan Aksesoris untuk Pemeliharaan', 1.00, 'keg', 138101000.00, 138101000.00, 'barang', 'Tidak Ada di RPK Perubahan 2025'),
(203, 121, NULL, 'Pengembangan Jaringan Akesesoris untuk Pemeliharaan', 1.00, 'keg', 138101000.00, 138101000.00, 'barang', 'Tidak Ada di RPK Perubahan 2025'),
(204, 122, NULL, 'Pengembangan Jaringan Aksesoris untuk Pemeliharaan', 1.00, 'keg', 138101000.00, 138101000.00, 'barang', 'Tidak Ada di RPK Perubahan 2025'),
(206, 123, NULL, 'Pengembangan Jaringan Aksesoris untuk Pemeliharaan', 4.00, 'keg', 138101000.00, 552404000.00, 'barang', 'Tidak Ada di RPK Perubahan 2025'),
(207, 124, NULL, 'Pengembangan Jaringan Aksesoris untuk Pemeliharaan', 1.00, 'keg', 138101000.00, 138101000.00, 'barang', 'Tidak Ada di RPK Perubahan 2025'),
(208, 125, NULL, 'Pengadaan dan pemasangan pipa HDPE untuk pengembangan jaringan pipa distribusi utama, Jalan rengganis raya desa bajur, kec.labuapi, Kab. Lobar', 1.00, 'keg', 650000000.00, 650000000.00, 'barang', 'Tambahan Pengadaan'),
(212, 126, NULL, 'Pengembangan Jaringan PiPA, Perumahan Gardenia Raya Tahap III, Desa Bug-Bug', 1.00, 'keg', 40582000.00, 40582000.00, 'sipil', 'Tambahan Pengadaan'),
(213, 127, NULL, 'Pengembangan Jaringan Pipa,Perumahan Octavia Garden, Kec. Labuapi, Kab. Lombok Barat', 1.00, 'keg', 36424000.00, 36424000.00, 'sipil', 'Tambahan Pengadaan'),
(214, 128, NULL, 'Pengembangan Jaringan Pipa Perumahan Gora Town', 1.00, 'keg', 210820000.00, 210820000.00, 'sipil', 'Tambahan Pengadaan'),
(215, 129, NULL, 'Pemasangan Pipa PVC Ø 2\'\'= 450 m, GI Ø 2\'\'= 10 m, GI Ø 1½\'\'=29 m, PVC  Ø 1½\'\'=888 m, Untuk Pengembangan', 1.00, 'keg', 72834000.00, 72834000.00, 'sipil', 'Tambahan Pengadaan'),
(216, 130, NULL, 'Pengembangan Jaringan Pipa Perumahan Bellpark 2 Cluster Harmoni, Kec.Gunungsari Kab. Lombok Barat. Jaringan Pipa Perumahan Ayodhya Palace Tahap IV, Kec. Batu Layar, Kab. Lombok Barat', 1.00, 'keg', 56218000.00, 56218000.00, 'sipil', 'Tambahan Pengadaan'),
(217, 131, NULL, 'Pemasangan Pipa PVC Ø 1½\'\'= 768 M, Untuk Pengembangan Jaringan Pipa Pipa . Perumahan Graha Kartika Perdana Tahap II , Desa Gelogor, Kecamatan Kediri, Kabupaten Lombok Barat', 1.00, 'keg', 37298000.00, 37298000.00, 'sipil', 'Tambahan Pengadaan'),
(218, 132, NULL, 'Pemassanga Pipa PVC Ø 2\'\'= 186 m, GI Ø 2\'\' = 3 m, GI Ø 1½\'\' = 12 m, PVC Ø 1½\'\' = 840 m, Untuk Pengembangan Jaringan Pipa Pipa', 1.00, 'keg', 47618000.00, 47618000.00, 'sipil', 'Tambahan Pengadaan'),
(219, 133, NULL, 'Pemasangan Pipa Ø PVC 6\'\' = 1200 m dan Pipa Ø GI 6\'\'= 12 m,Untuk Pengembangan Jaringan Pipa  Jl. Pantai Cemare (Ditpolairud), Desa Lembar', 1.00, 'keg', 214008000.00, 214008000.00, 'sipil', 'Tambahan Pengadaan'),
(220, 134, NULL, 'Pemasangan Pipa GI  Ø 1½\'\'= 24 m, PVC Ø 1½\'\'= 882 m, Untuk Pengembangan Jaringan Pipa Perumahan Griya Sehati Tahap 7, Kec.Labu Api', 1.00, 'keg', 42065000.00, 42065000.00, 'sipil', 'Tambahan Pengadaan'),
(221, 135, NULL, 'Pemasangan Pipa PVC Ø 3\'\'= 282 m dan Pipa PVC Ø 1½\'\'= 408 m, UntukPengembangan Jaringan Pipa Perumahan Lagoon Bay Residence  Tahap I', 1.00, 'keg', 42065000.00, 42065000.00, 'sipil', 'Tambahan Pengadaan'),
(222, 136, 238, 'Bahan Kimia  Kaporit', 1.00, 'set', 9841740000.00, 9841740000.00, 'barang', 'Tambahan Pengadaan'),
(223, 137, NULL, 'Kaporit untuk Disinfektan Bidang Umum', 1.00, 'keg', 42065000.00, 42065000.00, 'barang', 'Tambahan Pengadaan'),
(224, 138, NULL, 'Kaporit untuk Disinfektan Bidang Umum', 1.00, 'keg', 42065000.00, 42065000.00, 'barang', 'Tambahan Pengadaan'),
(226, 140, NULL, 'Pengadaan Leasing Kendaraan Operasional PT Air Minum Giri Menang (Perseroda)', 1.00, 'keg', 423600000.00, 423600000.00, 'jasa_lainnya', 'Tambahan Pengadaan'),
(227, 115, 235, 'Pengembangan Jaringan Aksesoris untuk pengembangan', 1.00, 'set', 282102400.00, 282102400.00, 'barang', ''),
(228, 139, NULL, 'Pengadaan Leasing Kendaraan Operasional PT Air Minum Giri Menang (Perseroda)', 1.00, 'keg', 9265000000.00, 9265000000.00, 'jasa_lainnya', ''),
(229, 141, NULL, 'Pengadaan Leasing Kendaraan Operasional PT Air Minum Giri Menang (Perseroda)', 1.00, 'keg', 423600000.00, 423600000.00, 'jasa_lainnya', 'Tambahan Pengadaan'),
(230, 142, NULL, 'Tender Cepat Pengadaan Sambungan Baru Regular, Pasang Kembali dan Water Meter untuk Pergantian Meter Pelanggan', 1.00, 'keg', 4236000000.00, 4236000000.00, 'barang', 'Tambahan Pengadaan'),
(231, 143, NULL, 'Sewa Alat Electroclorinator untuk Produksi Sodium Hypochlorite di Reservoir Bug-bug', 1.00, 'keg', 462000000.00, 462000000.00, 'jasa_lainnya', 'Tambahan Pengadaan'),
(233, 144, NULL, 'Sewa Alat Electroclorinator untuk Produksi Sodium Hypochlorite di Reservoir Bug-bug', 1.00, 'keg', 462000000.00, 462000000.00, 'jasa_lainnya', 'Tambahan Pengadaan'),
(234, 145, NULL, 'Jasa Keamanan Gedung Kantor PT Air Minum Giri Menang (Perseroda)', 1.00, 'keg', 453600000.00, 453600000.00, 'jasa_lainnya', 'Tambahan Pengadaan'),
(235, 146, NULL, 'Tera Water Meter Induk Produksi dan Distribusi PT Air Minum Giri Menang (Perseroda)', 1.00, 'keg', 458167000.00, 458167000.00, 'jasa_lainnya', 'Tambahan Pengadaan'),
(236, 147, NULL, 'Jasa Pengamanan (Security) Pada Kantor/Asset Pada Kantor Milik PT. Air Minum Giri', 1.00, 'keg', 485460000.00, 485460000.00, 'jasa_lainnya', 'Tambahan Pengadaan'),
(237, 148, NULL, 'Pengadaan Pemeliharaan Sumur Bor Bidang Produksi PT Air Minum Giri Menang (Perseroda)', 1.00, 'keg', 213058000.00, 213058000.00, 'jasa_lainnya', 'Tambahan Pengadaan');

-- --------------------------------------------------------

--
-- Struktur dari tabel `realisasi_kegiatan`
--

CREATE TABLE `realisasi_kegiatan` (
  `id` int(11) NOT NULL,
  `nomor_kontrak` varchar(100) DEFAULT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `status` enum('proses','selesai','batal') DEFAULT 'proses',
  `total_nilai` decimal(20,2) DEFAULT 0.00,
  `metode_pengadaan` enum('pembelian_langsung','tender_terbatas_spk','tender_terbatas_pkp','tender_umum','e_purchasing','swakelola') NOT NULL,
  `catatan` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `realisasi_kegiatan`
--

INSERT INTO `realisasi_kegiatan` (`id`, `nomor_kontrak`, `tanggal_mulai`, `tanggal_selesai`, `status`, `total_nilai`, `metode_pengadaan`, `catatan`, `created_by`, `created_at`, `updated_at`) VALUES
(11, 'NO 1 RAB', '2025-06-26', '2025-06-30', 'selesai', 39696000.00, 'tender_terbatas_spk', '', 1, '2026-02-26 00:27:30', '2026-02-26 00:27:51'),
(12, 'NO 2 RAB', '2025-07-20', '2025-08-26', 'selesai', 69534000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 00:30:25', '2026-02-26 00:32:44'),
(13, 'NO 3 RAB', '2025-09-26', '2025-10-26', 'selesai', 110832000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 00:32:03', '2026-02-26 00:33:21'),
(14, 'NO 1 KONS', '2025-01-26', '2025-02-26', 'selesai', 297060000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 00:39:11', '2026-02-26 00:39:11'),
(15, '300.XX/PB/AMGM/2025', '2025-09-26', NULL, 'selesai', 522426000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 00:40:57', '2026-02-26 00:40:57'),
(16, '300.XX/PB/AMGM/2025', '2025-09-26', NULL, 'selesai', 522426000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 00:42:16', '2026-02-26 00:42:16'),
(17, '300.XX/PB/AMGM/2025', '2025-08-26', NULL, 'selesai', 355305000.00, 'tender_terbatas_pkp', '3 Kegiatan Rencana dijadikan satu', 1, '2026-02-26 00:48:27', '2026-02-26 00:49:53'),
(18, '300.XX/PB/AMGM/2025', '2025-11-26', NULL, 'selesai', 127120000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 00:52:53', '2026-02-26 00:52:53'),
(19, '300.XX/PB/AMGM/2025', '2025-11-26', NULL, 'selesai', 83100000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 06:09:49', '2026-02-26 06:09:49'),
(20, 'NO 4 RAB', '2025-07-26', '2025-08-26', 'selesai', 181663000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 06:11:27', '2026-02-26 06:19:54'),
(21, 'NO 5 RAB', '2025-09-26', '2025-10-26', 'selesai', 32838000.00, 'tender_terbatas_spk', '', 1, '2026-02-26 06:13:07', '2026-02-26 06:19:31'),
(22, 'NO 6 RAB', '2025-02-26', '2025-03-26', 'selesai', 102129000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 06:16:50', '2026-02-26 06:20:17'),
(23, '300.XX/PB/AMGM/2025', '2025-05-26', NULL, 'selesai', 69176000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 06:18:05', '2026-02-26 06:18:05'),
(24, '300.XX/PB/AMGM/2025', '2025-07-26', NULL, 'selesai', 214224260.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 07:32:48', '2026-02-26 07:32:48'),
(25, '300.XX/PB/AMGM/2025', '2025-08-26', NULL, 'selesai', 329095000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 09:56:06', '2026-02-26 09:56:06'),
(26, '300.XX/PB/AMGM/2025', '2025-08-26', NULL, 'selesai', 203895000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 09:57:13', '2026-02-26 09:57:13'),
(27, '300.XX/PB/AMGM/2025', '2025-08-26', NULL, 'selesai', 42000000.00, 'tender_terbatas_spk', '', 1, '2026-02-26 09:58:14', '2026-02-26 09:58:14'),
(28, '300.XX/PB/AMGM/2025', '2025-03-26', NULL, 'selesai', 500980000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 10:32:01', '2026-02-26 10:32:01'),
(29, '300.XX/PB/AMGM/2025', '2025-06-26', NULL, 'selesai', 408772000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 10:33:47', '2026-02-26 10:33:47'),
(30, '300.XX/PB/AMGM/2025', '2025-10-26', NULL, 'selesai', 14625000.00, 'pembelian_langsung', '', 1, '2026-02-26 11:26:17', '2026-02-26 11:26:17'),
(31, '300.XX/PB/AMGM/2025', '2025-11-26', NULL, 'selesai', 14625000.00, 'pembelian_langsung', '', 1, '2026-02-26 11:26:53', '2026-02-26 11:26:53'),
(32, '300.XX/PB/AMGM/2025', '2025-11-26', NULL, 'selesai', 51350000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 11:28:01', '2026-02-26 11:28:01'),
(33, 'NO 7 RAB', '2025-02-26', '2025-03-26', 'selesai', 69467000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 11:29:30', '2026-02-26 11:30:52'),
(34, 'NO 8 RAB', '2025-06-26', '2025-07-26', 'selesai', 100000000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 11:32:18', '2026-02-26 11:32:18'),
(35, 'NO 9 RAB', '2025-10-26', '2025-11-26', 'selesai', 34261000.00, 'tender_terbatas_spk', '', 1, '2026-02-26 11:33:39', '2026-02-26 11:33:39'),
(36, '300.XX/PB/AMGM/2025', '2025-04-26', NULL, 'selesai', 260000000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 11:34:43', '2026-02-26 11:34:43'),
(37, 'NO 10 RAB', '2025-03-26', '2025-04-26', 'selesai', 120000000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 11:36:24', '2026-02-26 11:36:56'),
(38, 'NO 11 RAB', '2025-02-26', '2025-04-26', 'selesai', 18700000.00, 'tender_terbatas_spk', '', 1, '2026-02-26 13:23:44', '2026-02-26 16:00:13'),
(39, 'NO 12 RAB', '2025-11-26', '2025-12-26', 'selesai', 236823000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 13:25:47', '2026-02-26 16:00:21'),
(40, '300.XX/PB/AMGM/2025', '2025-02-26', NULL, 'selesai', 525368000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 13:36:58', '2026-02-26 16:02:02'),
(41, '300.XX/PB/AMGM/2025', '2025-05-26', NULL, 'selesai', 8270000.00, 'pembelian_langsung', '', 1, '2026-02-26 13:39:13', '2026-02-26 13:39:13'),
(42, '300.XX/PB/AMGM/2025', '2025-09-26', NULL, 'selesai', 31211000.00, 'tender_terbatas_spk', '', 1, '2026-02-26 13:40:35', '2026-02-26 13:42:36'),
(43, '300.XX/PB/AMGM/2025', '2025-03-26', NULL, 'selesai', 15000000.00, 'pembelian_langsung', '', 1, '2026-02-26 13:51:33', '2026-02-26 13:51:33'),
(44, '300.XX/PB/AMGM/2025', '2025-09-26', NULL, 'selesai', 13768000.00, 'pembelian_langsung', '', 1, '2026-02-26 14:04:40', '2026-02-26 14:04:40'),
(45, '300.XX/PB/AMGM/2025', '2025-07-26', NULL, 'selesai', 16000000.00, 'tender_terbatas_spk', '', 1, '2026-02-26 14:12:23', '2026-02-26 14:12:23'),
(46, '300.XX/PB/AMGM/2025', '2025-04-26', NULL, 'selesai', 23500000.00, 'tender_terbatas_spk', '', 1, '2026-02-26 14:16:28', '2026-02-26 14:16:28'),
(47, '300.XX/PB/AMGM/2025', '2025-01-26', NULL, 'selesai', 9115000.00, 'pembelian_langsung', '', 1, '2026-02-26 14:19:49', '2026-02-26 14:19:49'),
(48, '300.XX/PB/AMGM/2025', '2025-05-26', NULL, 'selesai', 9115000.00, 'pembelian_langsung', '', 1, '2026-02-26 14:21:02', '2026-02-26 14:21:02'),
(49, '300.XX/PB/AMGM/2025', '2025-03-26', NULL, 'selesai', 9115000.00, 'pembelian_langsung', '', 1, '2026-02-26 14:22:26', '2026-02-26 14:22:26'),
(50, '300.XX/PB/AMGM/2025', '2025-08-26', NULL, 'selesai', 9115000.00, 'pembelian_langsung', '', 1, '2026-02-26 14:29:28', '2026-02-26 14:29:28'),
(51, '300.XX/PB/AMGM/2025', '2025-01-26', NULL, 'selesai', 14350000.00, 'pembelian_langsung', '', 1, '2026-02-26 14:31:04', '2026-02-26 14:31:04'),
(52, '300.XX/PB/AMGM/2025', '2025-03-26', NULL, 'selesai', 13000000.00, 'pembelian_langsung', '', 1, '2026-02-26 14:33:17', '2026-02-26 14:33:17'),
(53, '300.XX/PB/AMGM/2025', '2025-03-26', NULL, 'selesai', 14350000.00, 'pembelian_langsung', '', 1, '2026-02-26 14:35:10', '2026-02-26 14:35:10'),
(54, '300.XX/PB/AMGM/2025', '2025-03-26', NULL, 'selesai', 13000000.00, 'pembelian_langsung', '', 1, '2026-02-26 14:39:08', '2026-02-26 14:39:08'),
(55, '300.XX/PB/AMGM/2025', '2025-03-26', NULL, 'selesai', 5775000.00, 'pembelian_langsung', '', 1, '2026-02-26 14:40:31', '2026-02-26 14:40:31'),
(56, '300.XX/PB/AMGM/2025', '2025-03-26', NULL, 'selesai', 13000000.00, 'pembelian_langsung', '', 1, '2026-02-26 14:41:30', '2026-02-26 14:41:30'),
(57, '300.XX/PB/AMGM/2025', '2025-01-26', NULL, 'selesai', 7500000.00, 'pembelian_langsung', '', 1, '2026-02-26 14:42:36', '2026-02-26 14:42:36'),
(58, '300.XX/PB/AMGM/2025', '2025-09-26', NULL, 'selesai', 7500000.00, 'pembelian_langsung', '', 1, '2026-02-26 14:43:09', '2026-02-26 14:43:09'),
(59, '300.XX/PB/AMGM/2025', '2025-09-26', NULL, 'selesai', 7500000.00, 'pembelian_langsung', '', 1, '2026-02-26 14:43:39', '2026-02-26 14:43:39'),
(60, '300.XX/PB/AMGM/2025', '2025-04-26', NULL, 'selesai', 13500000.00, 'pembelian_langsung', '', 1, '2026-02-26 14:45:45', '2026-02-26 14:45:45'),
(61, '300.XX/PB/AMGM/2025', '2025-08-26', NULL, 'selesai', 13500000.00, 'pembelian_langsung', '', 1, '2026-02-26 14:46:52', '2026-02-26 14:46:52'),
(62, '300.XX/PB/AMGM/2025', '2025-09-26', NULL, 'selesai', 4000000.00, 'pembelian_langsung', '', 1, '2026-02-26 14:47:48', '2026-02-26 14:47:48'),
(63, '300.XX/PB/AMGM/2025', '2025-08-26', NULL, 'selesai', 350000000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 14:48:52', '2026-02-26 14:48:52'),
(64, '300.XX/PB/AMGM/2025', '2025-10-26', NULL, 'selesai', 520000000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 14:50:15', '2026-02-26 14:50:15'),
(65, '300.XX/PB/AMGM/2025', '2025-11-26', NULL, 'selesai', 577250000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 14:53:12', '2026-02-26 14:53:12'),
(66, '300.XX/PB/AMGM/2025', '2025-11-26', NULL, 'selesai', 300000000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 14:54:04', '2026-02-26 14:54:04'),
(67, '300.XX/PB/AMGM/2025', '2025-06-26', NULL, 'selesai', 237500000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 14:54:59', '2026-02-26 14:54:59'),
(68, '300.XX/PB/AMGM/2025', '2025-04-26', NULL, 'selesai', 117000000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 14:56:05', '2026-02-26 14:56:05'),
(69, '300.XX/PB/AMGM/2025', '2025-10-26', NULL, 'selesai', 150000000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 14:57:18', '2026-02-26 14:57:18'),
(70, '300.XX/PB/AMGM/2025', '2025-11-26', NULL, 'selesai', 16000000.00, 'tender_terbatas_spk', '', 1, '2026-02-26 14:58:32', '2026-02-26 14:58:32'),
(71, '300.XX/PB/AMGM/2025', '2025-01-26', NULL, 'selesai', 9500000.00, 'pembelian_langsung', '', 1, '2026-02-26 14:59:50', '2026-02-26 14:59:50'),
(72, '300.XX/PB/AMGM/2025', '2025-01-26', NULL, 'selesai', 10900000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:00:47', '2026-02-26 15:00:47'),
(73, '300.XX/PB/AMGM/2025', '2025-01-26', NULL, 'selesai', 13950000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:01:54', '2026-02-26 15:01:54'),
(74, '300.XX/PB/AMGM/2025', '2025-09-26', NULL, 'selesai', 14602000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:02:30', '2026-02-26 15:02:30'),
(75, '300.XX/PB/AMGM/2025', '2025-10-26', NULL, 'selesai', 10707000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:03:29', '2026-02-26 15:03:29'),
(76, '300.XX/PB/AMGM/2025', '2025-10-26', NULL, 'selesai', 11400000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:04:38', '2026-02-26 15:04:38'),
(77, 'NO 11 RAB', '2025-05-26', NULL, 'selesai', 180000000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 15:05:36', '2026-02-26 15:05:36'),
(78, '300.XX/PB/AMGM/2025', '2025-01-26', NULL, 'selesai', 4180000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:06:59', '2026-02-26 15:06:59'),
(79, '300.XX/PB/AMGM/2025', '2025-05-26', NULL, 'selesai', 8360000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:07:31', '2026-02-26 15:07:31'),
(80, '300.XX/PB/AMGM/2025', '2025-02-26', NULL, 'selesai', 8360000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:44:51', '2026-02-26 15:44:51'),
(81, '300.XX/PB/AMGM/2025', '2025-03-26', NULL, 'selesai', 6600000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:45:46', '2026-02-26 15:45:46'),
(82, '300.XX/PB/AMGM/2025', '2025-05-26', NULL, 'selesai', 6600000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:46:23', '2026-02-26 15:46:23'),
(83, '300.XX/PB/AMGM/2025', '2025-09-26', NULL, 'selesai', 4180000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:47:12', '2026-02-26 15:47:12'),
(84, '300.XX/PB/AMGM/2025', '2025-09-26', NULL, 'selesai', 4180000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:47:43', '2026-02-26 15:47:43'),
(85, '300.XX/PB/AMGM/2025', '2025-02-26', NULL, 'selesai', 3543000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:48:33', '2026-02-26 15:59:54'),
(86, '300.XX/PB/AMGM/2025', '2025-10-26', NULL, 'selesai', 7086000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:49:02', '2026-02-26 15:49:02'),
(87, '300.XX/PB/AMGM/2025', '2025-02-26', NULL, 'selesai', 3543000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:49:41', '2026-02-26 15:59:45'),
(88, '300.XX/PB/AMGM/2025', '2025-05-26', NULL, 'selesai', 3543000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:50:19', '2026-02-26 15:50:19'),
(89, '300.XX/PB/AMGM/2025', '2025-05-26', NULL, 'selesai', 7086000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:51:01', '2026-02-26 15:51:01'),
(90, '300.XX/PB/AMGM/2025', '2025-09-26', NULL, 'selesai', 14172000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:51:45', '2026-02-26 15:51:45'),
(91, '300.XX/PB/AMGM/2025', '2025-09-26', NULL, 'selesai', 10629000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:56:08', '2026-02-26 15:56:08'),
(92, '300.XX/PB/AMGM/2025', '2025-02-26', NULL, 'selesai', 13227000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:56:47', '2026-02-26 15:59:34'),
(93, '300.XX/PB/AMGM/2025', '2025-02-26', NULL, 'selesai', 8818000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:57:20', '2026-02-26 15:59:22'),
(94, '300.XX/PB/AMGM/2025', '2025-09-26', NULL, 'selesai', 8818000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:57:42', '2026-02-26 15:57:42'),
(95, '300.XX/PB/AMGM/2025', '2025-02-26', NULL, 'selesai', 11500000.00, 'pembelian_langsung', '', 1, '2026-02-26 15:58:43', '2026-02-26 15:58:55'),
(96, '300.XX/PB/AMGM/2025', '2025-03-26', NULL, 'selesai', 3900000.00, 'pembelian_langsung', '', 1, '2026-02-26 16:01:10', '2026-02-26 16:01:10'),
(97, '300.XX/PB/AMGM/2025', '2025-04-26', NULL, 'selesai', 3900000.00, 'pembelian_langsung', '', 1, '2026-02-26 16:01:39', '2026-02-26 16:01:39'),
(98, '300.XX/PB/AMGM/2025', '2025-03-26', NULL, 'selesai', 3500000.00, 'pembelian_langsung', '', 1, '2026-02-26 16:02:56', '2026-02-26 16:02:56'),
(99, '300.XX/PB/AMGM/2025', '2025-02-26', NULL, 'selesai', 410000000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 16:04:23', '2026-02-26 16:04:23'),
(100, '300.XX/PB/AMGM/2025', '2025-03-26', NULL, 'selesai', 50000000.00, 'tender_terbatas_spk', '', 1, '2026-02-26 16:05:38', '2026-02-26 16:05:38'),
(101, '300.XX/PB/AMGM/2025', '2025-06-26', NULL, 'selesai', 50000000.00, 'tender_terbatas_spk', '', 1, '2026-02-26 16:06:51', '2026-02-26 16:06:51'),
(102, '300.XX/PB/AMGM/2025', '2025-02-26', NULL, 'selesai', 55000000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 16:07:57', '2026-02-26 16:07:57'),
(103, '300.XX/PB/AMGM/2025', '2025-03-26', NULL, 'selesai', 55000000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 16:08:29', '2026-02-26 16:08:29'),
(104, 'NO 2 KONS', '2025-06-26', NULL, 'selesai', 42860000.00, 'tender_terbatas_spk', '', 1, '2026-02-26 16:10:58', '2026-02-26 16:10:58'),
(105, 'NO 3 KONS', '2025-05-26', NULL, 'selesai', 298340000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 16:13:24', '2026-02-26 16:13:24'),
(106, 'NO 4 KONS', '2025-05-26', NULL, 'selesai', 245543000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 16:14:47', '2026-02-26 16:14:47'),
(107, 'NO 5 KONS', '2025-08-26', NULL, 'selesai', 297951000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 16:15:52', '2026-02-26 16:15:52'),
(108, 'NO 6 KONS', '2025-06-26', NULL, 'selesai', 233672000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 16:17:27', '2026-02-26 16:17:27'),
(109, 'NO 7 KONS', '2025-09-26', NULL, 'selesai', 298723000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 16:18:57', '2026-02-26 16:18:57'),
(110, 'NO 8 KONS', '2025-09-26', NULL, 'selesai', 300000000.00, 'tender_terbatas_pkp', '', 1, '2026-02-26 16:20:01', '2026-02-26 16:20:01'),
(111, '300.XX/PB/AMGM/2025', '2025-01-01', NULL, 'selesai', 282102400.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 08:18:31', '2026-03-01 08:18:31'),
(112, '300.XX/PB/AMGM/2025', '2025-04-01', NULL, 'selesai', 282102400.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 08:19:05', '2026-03-01 08:19:05'),
(113, '300.XX/PB/AMGM/2025', '2025-05-01', NULL, 'selesai', 282102400.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 08:19:46', '2026-03-01 08:19:46'),
(114, '300.XX/PB/AMGM/2025', '2025-06-01', NULL, 'selesai', 282102400.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 08:20:18', '2026-03-01 08:20:18'),
(115, '300.XX/PB/AMGM/2025', '2025-07-01', NULL, 'selesai', 282102400.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 08:20:40', '2026-03-01 15:29:51'),
(116, '300.XX/PB/AMGM/2025', '2025-11-01', NULL, 'selesai', 282102400.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 08:21:13', '2026-03-01 08:21:13'),
(117, '300.XX/PB/AMGM/2025', '2025-03-01', NULL, 'selesai', 138101000.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 08:23:33', '2026-03-01 08:23:33'),
(118, '300.XX/PB/AMGM/2025', '2025-04-01', NULL, 'selesai', 138101000.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 08:24:35', '2026-03-01 08:24:35'),
(119, '300.XX/PB/AMGM/2025', '2025-05-01', NULL, 'selesai', 138101000.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 08:26:03', '2026-03-01 08:26:03'),
(120, '300.XX/PB/AMGM/2025', '2025-06-01', NULL, 'selesai', 138101000.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 08:31:11', '2026-03-01 08:31:11'),
(121, '300.XX/PB/AMGM/2025', '2025-07-01', NULL, 'selesai', 138101000.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 08:32:33', '2026-03-01 08:32:33'),
(122, '300.XX/PB/AMGM/2025', '2025-08-01', NULL, 'selesai', 138101000.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 08:33:37', '2026-03-01 08:33:37'),
(123, '300.XX/PB/AMGM/2025', '2025-10-01', NULL, 'selesai', 552404000.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 08:34:41', '2026-03-01 08:37:25'),
(124, '300.XX/PB/AMGM/2025', '2025-11-01', NULL, 'selesai', 138101000.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 11:14:53', '2026-03-01 11:14:53'),
(125, '300.XX/PB/AMGM/2025', '2025-09-01', NULL, 'selesai', 650000000.00, 'tender_umum', '', 1, '2026-03-01 13:35:52', '2026-03-01 13:35:52'),
(126, 'NO 13 RAB', '2025-03-01', '2025-04-29', 'selesai', 40582000.00, 'tender_terbatas_spk', '', 1, '2026-03-01 13:36:55', '2026-03-01 13:44:02'),
(127, 'NO 14 RAB', '2025-03-01', '2025-04-01', 'selesai', 36424000.00, 'tender_terbatas_spk', '', 1, '2026-03-01 13:37:40', '2026-03-01 13:44:40'),
(128, 'NO 15 RAB', '2025-03-01', '2025-04-01', 'selesai', 210820000.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 13:40:22', '2026-03-01 13:45:17'),
(129, 'NO 16 RAB', '2025-04-01', '2025-05-01', 'selesai', 72834000.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 13:46:37', '2026-03-01 13:46:37'),
(130, 'NO 18 RAB', '2025-06-01', '2025-07-01', 'selesai', 56218000.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 15:11:03', '2026-03-01 15:11:03'),
(131, 'NO 19 RAB', '2025-06-01', '2025-07-01', 'selesai', 37298000.00, 'tender_terbatas_spk', '', 1, '2026-03-01 15:12:26', '2026-03-01 15:12:26'),
(132, 'NO 20 RAB', '2025-09-01', '2025-10-01', 'selesai', 47618000.00, 'tender_terbatas_spk', '', 1, '2026-03-01 15:15:37', '2026-03-01 15:15:37'),
(133, 'NO 21 RAB', '2025-09-01', '2025-10-01', 'selesai', 214008000.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 15:16:54', '2026-03-01 15:16:54'),
(134, 'NO 22 RAB', '2025-11-01', '2025-12-01', 'selesai', 42065000.00, 'tender_terbatas_spk', '', 1, '2026-03-01 15:18:48', '2026-03-01 15:18:48'),
(135, 'NO 23 RAB', '2025-11-01', '2025-12-01', 'selesai', 42065000.00, 'tender_terbatas_spk', '', 1, '2026-03-01 15:20:37', '2026-03-01 15:20:37'),
(136, '300.XX/PB/AMGM/2025', '2025-04-01', NULL, 'selesai', 9841740000.00, 'tender_umum', '', 1, '2026-03-01 15:21:50', '2026-03-01 15:21:50'),
(137, '300.XX/PB/AMGM/2025', '2025-01-01', NULL, 'selesai', 42065000.00, 'tender_terbatas_spk', '', 1, '2026-03-01 15:23:41', '2026-03-01 15:23:41'),
(138, '300.XX/PB/AMGM/2025', '2025-03-01', NULL, 'selesai', 42065000.00, 'tender_terbatas_spk', '', 1, '2026-03-01 15:24:57', '2026-03-01 15:24:57'),
(139, 'NO 1 JL', '2025-03-01', '2025-06-01', 'selesai', 9265000000.00, 'tender_umum', '', 1, '2026-03-01 15:28:15', '2026-03-01 15:29:59'),
(140, 'NO 2 JL', '2025-04-01', '2025-05-01', 'selesai', 423600000.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 15:29:32', '2026-03-01 15:29:32'),
(141, 'NO 3 JL', '2025-06-01', '2025-07-01', 'selesai', 423600000.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 15:31:12', '2026-03-01 15:31:12'),
(142, '300.XX/PB/AMGM/2025', '2025-01-01', NULL, 'selesai', 4236000000.00, 'tender_umum', '', 1, '2026-03-01 15:32:39', '2026-03-01 15:32:39'),
(143, 'NO 4 JL', '2025-01-01', NULL, 'selesai', 462000000.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 15:33:54', '2026-03-01 15:33:54'),
(144, 'NO 5 JL', '2025-12-01', NULL, 'selesai', 462000000.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 15:34:27', '2026-03-01 15:35:04'),
(145, 'NO 6 JL', '2025-12-01', '2026-01-01', 'selesai', 453600000.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 15:36:15', '2026-03-01 15:36:15'),
(146, 'NO 8 JL', '2025-04-01', '2025-05-01', 'selesai', 458167000.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 15:37:21', '2026-03-01 15:37:21'),
(147, 'NO 8 JL', '2025-06-01', '2025-07-01', 'selesai', 485460000.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 15:38:33', '2026-03-01 15:38:33'),
(148, 'NO 9 JL', '2025-06-01', '2025-07-01', 'selesai', 213058000.00, 'tender_terbatas_pkp', '', 1, '2026-03-01 15:39:45', '2026-03-01 15:39:45');

-- --------------------------------------------------------

--
-- Struktur dari tabel `rencana_kegiatan`
--

CREATE TABLE `rencana_kegiatan` (
  `id` int(11) NOT NULL,
  `nama_kegiatan` varchar(255) NOT NULL,
  `volume` decimal(15,2) NOT NULL DEFAULT 1.00,
  `satuan` varchar(50) NOT NULL,
  `nilai_satuan` decimal(20,2) NOT NULL DEFAULT 0.00,
  `nilai_anggaran` decimal(20,2) NOT NULL DEFAULT 0.00,
  `jenis_pengadaan` enum('barang','sipil','jasa_konsultan','jasa_lainnya') NOT NULL,
  `metode_pengadaan` enum('pembelian_langsung','tender_terbatas_spk','tender_terbatas_pkp','tender_umum','e_purchasing','swakelola') NOT NULL,
  `tahun` int(11) NOT NULL,
  `bulan_rencana` varchar(30) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `rencana_kegiatan`
--

INSERT INTO `rencana_kegiatan` (`id`, `nama_kegiatan`, `volume`, `satuan`, `nilai_satuan`, `nilai_anggaran`, `jenis_pengadaan`, `metode_pengadaan`, `tahun`, `bulan_rencana`, `keterangan`, `created_by`, `created_at`, `updated_at`) VALUES
(12, 'Tanah dan Hak atas Tanah Pembebasan Lahan SPAM Dodokan', 1000.00, 'm²', 350000.00, 350000000.00, 'sipil', 'tender_terbatas_pkp', 2026, '6', '', 1, '2026-02-22 09:21:10', '2026-02-24 14:41:40'),
(13, 'Tanah dan Hak Atas Tanah Biaya BPHTB', 1.00, 'keg', 50000000.00, 50000000.00, 'jasa_konsultan', 'tender_terbatas_spk', 2026, '6', '', 1, '2026-02-22 09:22:13', '2026-02-24 14:40:48'),
(14, 'Tanah dan Hak Atas Tanah Biaya Apraisal', 1.00, 'keg', 30000000.00, 30000000.00, 'jasa_lainnya', 'tender_terbatas_spk', 2026, '4', '', 1, '2026-02-22 09:22:45', '2026-02-24 14:40:48'),
(15, 'Penyempurnaan Tanah Apraisal Aset Batu Kumbung', 1.00, 'set', 30000000.00, 30000000.00, 'jasa_lainnya', 'tender_terbatas_spk', 2026, '1', '', 1, '2026-02-22 09:23:55', '2026-02-24 14:40:48'),
(16, 'Penyempurnaan Tanah Apraisal Aset Ex Senggigi Batu Layar (Seraton)', 1.00, 'set', 30000000.00, 30000000.00, 'jasa_lainnya', 'tender_terbatas_spk', 2026, '1', '', 1, '2026-02-22 09:26:14', '2026-02-24 14:40:48'),
(17, 'Penyempurnaan Tanah Apraisal Aset Samping Kantor Wilayah Pelayanan Gerung', 1.00, 'set', 30000000.00, 30000000.00, 'jasa_lainnya', 'tender_terbatas_spk', 2026, '1', '', 1, '2026-02-22 09:27:06', '2026-02-24 14:40:48'),
(18, 'Penyempurnaan Tanah Pengurusan Perpanjangan Sertifikat HGB Tanah Serasute', 1.00, 'set', 80000000.00, 80000000.00, 'jasa_konsultan', 'tender_terbatas_pkp', 2026, '2', '', 1, '2026-02-22 09:30:04', '2026-02-24 14:41:40'),
(19, 'Penyempurnaan Tanah Pengurusan Perpanjangan Sertifikat HGB Tanah Ranget (Jalur Pipa)', 1.00, 'set', 18625000.00, 18625000.00, 'jasa_konsultan', 'tender_terbatas_spk', 2026, '1', '', 1, '2026-02-22 09:31:28', '2026-02-24 14:40:48'),
(20, 'Penyempurnaan Tanah Pal Jalur Pipa Ranget (2726 m²)', 136.00, 'm²', 75000.00, 10200000.00, 'sipil', 'pembelian_langsung', 2026, '4', '', 1, '2026-02-22 09:33:15', '2026-02-22 09:33:15'),
(21, 'Bangunan dan Perbaikannya Inst. Sumber Pemasangan Batu Bronjong dan Pengamanan Pipa Transmisi Remeneng (Tanak Beak)', 1.00, 'keg', 600000000.00, 600000000.00, 'sipil', 'tender_terbatas_pkp', 2026, '5', '', 1, '2026-02-22 09:36:21', '2026-02-24 14:41:40'),
(22, 'Bangunan dan Perbaikannya Inst. Sumber Pemasangan Batu Bronjong dan Pengamanan Pipa Transmisi Intake Serepak', 1.00, 'keg', 400000000.00, 400000000.00, 'sipil', 'tender_terbatas_pkp', 2026, '6', '', 1, '2026-02-22 09:37:50', '2026-02-24 14:41:40'),
(23, 'Bangunan dan Perbaikannya Inst. Sumber Pemasangan Batu Bronjong di Intake Penimbung', 16.00, 'm²', 4062500.00, 65000000.00, 'sipil', 'tender_terbatas_pkp', 2026, '5', '', 1, '2026-02-22 09:39:19', '2026-02-24 14:41:40'),
(24, 'Bangunan dan Perbaikannya Inst. Sumber Berugaq di Res. Telaga Ngembeng', 1.00, 'keg', 10000000.00, 10000000.00, 'barang', 'pembelian_langsung', 2026, '3', '', 1, '2026-02-22 09:40:40', '2026-02-22 09:40:40'),
(25, 'Sumur-Sumur Pengeboran Sumur Bor SB Bellpark 1-Tahap II', 1.00, 'keg', 500000000.00, 500000000.00, 'sipil', 'tender_terbatas_pkp', 2026, '4', '', 1, '2026-02-22 09:41:59', '2026-02-24 14:41:40'),
(26, 'Sumur-Sumur Pengeboran Sumur Bor SB GPA 3', 1.00, 'keg', 500000000.00, 500000000.00, 'sipil', 'tender_terbatas_pkp', 2026, '6', '', 1, '2026-02-22 09:43:12', '2026-02-24 14:41:40'),
(27, 'Sumur-Sumur Dokumen UKL/UPL SB A', 1.00, 'keg', 35000000.00, 35000000.00, 'jasa_lainnya', 'tender_terbatas_spk', 2026, '4', '', 1, '2026-02-22 09:44:32', '2026-02-24 14:40:48'),
(28, 'Sumur-Sumur Dokumen UKL/UPL SB B', 1.00, 'keg', 35000000.00, 35000000.00, 'jasa_lainnya', 'tender_terbatas_spk', 2026, '4', '', 1, '2026-02-22 09:45:10', '2026-02-24 14:40:48'),
(29, 'Sumur-Sumur Dokumen Geolistrik SB A', 1.00, 'keg', 25000000.00, 25000000.00, 'jasa_lainnya', 'tender_terbatas_spk', 2026, '4', '', 1, '2026-02-22 09:46:24', '2026-02-24 14:40:48'),
(30, 'Sumur-Sumur Dokumen Geolistrik SB B', 1.00, 'keg', 25000000.00, 25000000.00, 'jasa_lainnya', 'tender_terbatas_spk', 2026, '4', '', 1, '2026-02-22 09:47:12', '2026-02-24 14:40:48'),
(31, 'Sumur-Sumur Dokumen Studi Kelayakan Sumur Bor SB A', 1.00, 'keg', 15000000.00, 15000000.00, 'jasa_lainnya', 'pembelian_langsung', 2026, '4', '', 1, '2026-02-22 09:50:51', '2026-02-22 09:50:51'),
(32, 'Sumur-Sumur Dokumen Studi Kelayakan Sumur Bor SB B', 1.00, 'keg', 15000000.00, 15000000.00, 'jasa_lainnya', 'pembelian_langsung', 2026, '4', '', 1, '2026-02-22 09:51:27', '2026-02-22 09:51:27'),
(33, 'Sumur-Sumur Pengawasan Konstruksi SB Baru SB Bellpark 1 - Tahap II', 1.00, 'keg', 9000000.00, 9000000.00, 'jasa_lainnya', 'pembelian_langsung', 2026, '4', '', 1, '2026-02-22 09:52:20', '2026-02-22 09:52:20'),
(34, 'Sumur-Sumur Pengawasan Konstruksi SB Baru SB GPA 3', 1.00, 'keg', 9000000.00, 9000000.00, 'jasa_lainnya', 'pembelian_langsung', 2026, '6', '', 1, '2026-02-22 09:53:07', '2026-02-22 09:53:07'),
(35, 'Sumur-Sumur Dokumen Uji Pumping GPA 3', 1.00, 'keg', 15000000.00, 15000000.00, 'jasa_lainnya', 'pembelian_langsung', 2026, '7', '', 1, '2026-02-22 09:53:59', '2026-02-22 09:53:59'),
(36, 'Reposisi Pipa Remeneng (lanjutan)', 1.00, 'keg', 600000000.00, 600000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '7', '', 1, '2026-02-23 01:23:05', '2026-02-24 14:41:40'),
(37, 'Pengadaan Berugak Sekenem di Intake Serepak', 1.00, 'keg', 30000000.00, 30000000.00, 'sipil', 'tender_terbatas_spk', 2025, '7', '', 1, '2026-02-23 01:23:59', '2026-02-24 14:40:48'),
(38, 'Perbaikan gelagar pipa transmisi Lebah Sempage', 1.00, 'keg', 500000000.00, 500000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '8', '', 1, '2026-02-23 01:24:49', '2026-02-24 14:41:40'),
(39, 'Pemasangan Pipa HDPE 12\" akibat Longsor dan Reposisi Pipa Transmisi Intake Serepak', 1.00, 'keg', 181663000.00, 181663000.00, 'sipil', 'tender_terbatas_pkp', 2025, '9', '', 1, '2026-02-23 01:25:51', '2026-02-24 14:41:40'),
(40, 'Pembuatan bak penampungan limbah kaporit', 1.00, 'keg', 110832000.00, 110832000.00, 'sipil', 'tender_terbatas_pkp', 2025, '6', '', 1, '2026-02-23 01:26:37', '2026-02-24 14:41:40'),
(41, 'DED Perencanaan Pembangunan Prasedimentasi dan Optimalisasi Intake Sumber Air Remeneng', 1.00, 'keg', 297060000.00, 297060000.00, 'jasa_konsultan', 'tender_terbatas_pkp', 2025, '5', '', 1, '2026-02-23 01:30:45', '2026-02-24 14:41:40'),
(42, 'Pemasangan Pagar SB Gora Town Palace', 1.00, 'keg', 120000000.00, 120000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '7', '', 1, '2026-02-23 01:32:25', '2026-02-24 14:41:40'),
(43, 'Pemasangan pagar SB Mahkota Bertais', 1.00, 'keg', 120000000.00, 120000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '9', '', 1, '2026-02-23 01:33:41', '2026-02-24 14:41:40'),
(44, 'Pengadaan Pasir pada Bak Filtrasi WTP Sembung', 1.00, 'keg', 315000000.00, 315000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '7', '', 1, '2026-02-23 01:34:23', '2026-02-24 14:41:40'),
(45, 'Perbaikan Talud batu Kali, Rabat Beton, Plat Beton Bertulang dan Boring Pipa', 1.00, 'keg', 315000000.00, 315000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '9', '', 1, '2026-02-23 01:35:13', '2026-02-24 14:41:40'),
(46, 'Perbaikan Plat Sunscreen dan Plafon Ruang Kimia', 1.00, 'keg', 315000000.00, 315000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '8', '', 1, '2026-02-23 01:35:58', '2026-02-24 14:41:40'),
(47, 'Mesin Mesin Untuk WTP Sembung', 1.00, 'keg', 522426000.00, 522426000.00, 'barang', 'tender_terbatas_pkp', 2025, '8', '', 1, '2026-02-23 01:37:20', '2026-02-24 14:41:40'),
(48, 'Instalasi perkabelan screen 300 m untuk beberapa valve, analyzer dan pompa di WTP Sembung', 1.00, 'keg', 522426000.00, 522426000.00, 'barang', 'tender_terbatas_pkp', 2025, '8', '', 1, '2026-02-23 01:38:30', '2026-02-24 14:41:40'),
(49, 'Turbidity meter Air Baku dan air Bersih, pembacaan 0 - 2000 NTU,termasuk on-line turbidity sensor, transmitter dan holder', 1.00, 'keg', 522426000.00, 522426000.00, 'barang', 'tender_terbatas_pkp', 2025, '8', '', 1, '2026-02-23 01:39:05', '2026-02-24 14:41:40'),
(50, 'PH Meter Air Bersih, pembacaan PH 4 - 10, termasuk on-line PH sensor, transmitter dan holder', 1.00, 'keg', 522426000.00, 522426000.00, 'barang', 'tender_terbatas_pkp', 2025, '8', '', 1, '2026-02-23 01:39:45', '2026-02-24 14:41:40'),
(51, 'SCM Complete set, include PLC Siemens S71200, Pompa Dosing, Inverter 0,75 kW, Panel dan Accessories', 1.00, 'keg', 522426000.00, 522426000.00, 'barang', 'tender_terbatas_pkp', 2025, '9', '', 1, '2026-02-23 01:40:33', '2026-02-24 14:41:40'),
(52, 'Instalasi Sumber Level MPS', 1.00, 'keg', 522426000.00, 522426000.00, 'barang', 'tender_terbatas_pkp', 2025, '9', '', 1, '2026-02-23 01:41:18', '2026-02-24 14:41:40'),
(53, 'Instalasi Sumber Lainnya Butterfly valve Wafer with pneumatic Dia. 4\"', 1.00, 'keg', 522426000.00, 522426000.00, 'barang', 'tender_terbatas_pkp', 2025, '9', '', 1, '2026-02-23 01:42:21', '2026-02-24 14:41:40'),
(54, 'Butterfly valve Wafer with pneumatic Actuator Double Acting and Positioner Dia. 12\"', 1.00, 'keg', 522426000.00, 522426000.00, 'barang', 'tender_terbatas_pkp', 2025, '9', '', 1, '2026-02-23 01:43:03', '2026-02-24 14:41:40'),
(55, 'Instalasi Sumber Lainnya Pompa chemical dan inverter 1.5kW', 1.00, 'keg', 5224260.00, 5224260.00, 'barang', 'pembelian_langsung', 2025, '9', '', 1, '2026-02-23 01:43:57', '2026-02-23 01:43:57'),
(56, 'Instalasi Sumber Lainnya Regulator', 1.00, 'keg', 5224260.00, 5224260.00, 'barang', 'pembelian_langsung', 2025, '10', '', 1, '2026-02-23 01:44:56', '2026-02-23 01:44:56'),
(57, 'Instalasi Sumber Lainnya Konektor Selang 8 mm', 1.00, 'keg', 5224260.00, 5224260.00, 'barang', 'pembelian_langsung', 2025, '10', '', 1, '2026-02-23 01:46:22', '2026-02-23 01:46:22'),
(58, 'Instalasi Sumber Lainnya Pneumatic Valve Ø 2\"', 1.00, 'keg', 2522426.00, 2522426.00, 'barang', 'pembelian_langsung', 2025, '10', '', 1, '2026-02-23 01:47:36', '2026-02-23 01:47:36'),
(59, 'Instalasi Sumber Lainnya Pneumatic Valve Ø 3\"', 1.00, 'keg', 52242600.00, 52242600.00, 'barang', 'tender_terbatas_pkp', 2025, '10', '', 1, '2026-02-23 01:48:15', '2026-02-24 14:41:40'),
(60, 'Instalasi Sumber Lainnya Pneumatic Valve Ø 4\"', 1.00, 'unit', 52242600.00, 52242600.00, 'barang', 'tender_terbatas_pkp', 2025, '10', '', 1, '2026-02-23 01:49:22', '2026-02-24 14:41:40'),
(61, 'Instalasi Sumber Lainnya Pneumatic Valve Ø 8\"', 1.00, 'keg', 52242600.00, 52242600.00, 'barang', 'tender_terbatas_pkp', 2025, '11', '', 1, '2026-02-23 01:51:11', '2026-02-24 14:41:40'),
(62, 'Instalasi Sumber Lainnya Residual Chlorine Meter Air Olahan, pembacaan 0,0 - 2 ppm, termasuk on-line sensor, transmitter dan holder', 1.00, 'keg', 5224260.00, 5224260.00, 'barang', 'pembelian_langsung', 2025, '11', '', 1, '2026-02-23 01:51:55', '2026-02-23 01:51:55'),
(63, 'Instalasi Sumber Lainnya WLC', 1.00, 'keg', 5224260.00, 5224260.00, 'barang', 'pembelian_langsung', 2025, '11', '', 1, '2026-02-23 01:52:39', '2026-02-23 01:52:39'),
(64, 'Instalasi Sumber Lainnya RELAY Auto', 1.00, 'keg', 5224260.00, 5224260.00, 'barang', 'pembelian_langsung', 2025, '11', '', 1, '2026-02-23 01:53:27', '2026-02-23 01:53:27'),
(65, 'Instalasi Sumber Lainnya Lampu Panel', 1.00, 'keg', 52242600.00, 52242600.00, 'barang', 'tender_terbatas_pkp', 2025, '11', '', 1, '2026-02-23 01:54:20', '2026-02-24 14:41:40'),
(66, 'Instalasi Sumber Lainnya Vertical Mixer With Gear Box (Daya Motor : 0,75 Kw/380 V/50 Hz/1400 RPM/3-Phase ; Diameter Shaft : 22 mm ; Tinggi Shaft : 1600 mm ; Impeller : 2 ea ; Shaft & Impeller : SS304 ; Rasio Gear Box : 1:5)', 1.00, 'keg', 522426000.00, 522426000.00, 'barang', 'tender_terbatas_pkp', 2025, '12', '', 1, '2026-02-23 01:55:28', '2026-02-24 14:41:40'),
(67, 'Instalasi Sumber Lainnya Analog input&autput S7 1500', 1.00, 'keg', 52242600.00, 52242600.00, 'barang', 'tender_terbatas_pkp', 2025, '12', '', 1, '2026-02-23 01:57:05', '2026-02-24 14:41:40'),
(68, 'Instalasi Sumber Lainnya Selenoid', 1.00, 'keg', 52242600.00, 52242600.00, 'barang', 'tender_terbatas_pkp', 2025, '7', '', 1, '2026-02-23 01:58:17', '2026-02-24 14:41:40'),
(69, 'Instalasi Sumber Lainnya Kabel Liycy 3 x0.75', 1.00, 'keg', 5224260.00, 5224260.00, 'barang', 'pembelian_langsung', 2025, '7', '', 1, '2026-02-23 02:03:17', '2026-02-23 02:03:17'),
(70, 'Instalasi Sumber Lainnya Kontaktor', 1.00, 'keg', 5224260.00, 5224260.00, 'barang', 'pembelian_langsung', 2025, '7', '', 1, '2026-02-23 02:30:13', '2026-02-23 02:30:13'),
(71, 'Peralatan Pompa Pengadaan dan Pemasangan Pompa di SB GPA 3', 1.00, 'keg', 200000000.00, 200000000.00, 'barang', 'tender_terbatas_pkp', 2025, '9', '', 1, '2026-02-23 02:37:37', '2026-02-24 14:41:40'),
(72, 'Pengadaan dan Pemasangan Pompa di SB Bellpark 2', 1.00, 'keg', 200000000.00, 200000000.00, 'barang', 'tender_terbatas_pkp', 2025, '9', '', 1, '2026-02-23 02:38:52', '2026-02-24 14:41:40'),
(73, 'Instalasi Pompa SB Perumda Kab. Lombok Barat', 1.00, 'keg', 70000000.00, 70000000.00, 'barang', 'tender_terbatas_pkp', 2025, '8', '', 1, '2026-02-23 02:40:31', '2026-02-24 14:41:40'),
(74, 'Peralatan Pompa Pemasangan casing SB Gora Town', 1.00, 'keg', 105000000.00, 105000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '6', '', 1, '2026-02-23 02:42:05', '2026-02-24 14:41:40'),
(75, 'Instalasi Pompa Lainnya Pergantian ATS SB GPA 1', 1.00, 'keg', 80000000.00, 80000000.00, 'barang', 'tender_terbatas_pkp', 2025, '6', '', 1, '2026-02-23 02:42:58', '2026-02-24 14:41:40'),
(76, 'Instalasi Pompa Lainnya Pengadaan dan Pemasangan Panel Inverter 45 KW', 1.00, 'keg', 105000000.00, 105000000.00, 'barang', 'tender_terbatas_pkp', 2025, '11', '', 1, '2026-02-23 02:44:29', '2026-02-24 14:41:40'),
(77, 'Instalasi Pompa Lainnya Pengadaan dan Pemasangan Panel Inverter 22 KW', 1.00, 'keg', 104000000.00, 104000000.00, 'barang', 'tender_terbatas_pkp', 2025, '11', '', 1, '2026-02-23 02:45:22', '2026-02-24 14:41:40'),
(78, 'Instalasi Pompa Lainnya Pengadaan dan Pemasangan Panel Inverter 11 KW', 1.00, 'keg', 25641000.00, 25641000.00, 'barang', 'tender_terbatas_spk', 2025, '11', '', 1, '2026-02-23 02:46:03', '2026-02-24 14:40:48'),
(79, 'Pengadaan & Pemasangan Media Sand Filter SB Royal Madinah', 1.00, 'keg', 81000000.00, 81000000.00, 'barang', 'tender_terbatas_pkp', 2025, '7', '', 1, '2026-02-23 02:47:41', '2026-02-24 14:41:40'),
(80, 'Pengadaan & Pemasangan Media Sand Filter SB Jatisela', 1.00, 'keg', 81000000.00, 81000000.00, 'barang', 'tender_terbatas_pkp', 2025, '7', '', 1, '2026-02-23 02:48:22', '2026-02-24 14:41:40'),
(81, 'Pemasangan kwh 33.000 SB GPA 3', 1.00, 'keg', 4500000000.00, 4500000000.00, 'jasa_lainnya', 'tender_terbatas_pkp', 2025, '5', '', 1, '2026-02-23 02:50:40', '2026-02-24 14:43:08'),
(82, 'Pemasangan kwh 33.000 SB Bellpark 2', 1.00, 'keg', 45000000.00, 45000000.00, 'jasa_lainnya', 'tender_terbatas_spk', 2025, '9', '', 1, '2026-02-23 02:53:22', '2026-02-24 14:40:48'),
(83, 'Pengadaan dan Pemasangan Panel Inverter SB GPA 3', 1.00, 'keg', 20000000.00, 20000000.00, 'barang', 'tender_terbatas_spk', 2025, '9', '', 1, '2026-02-23 02:54:51', '2026-02-24 14:40:48'),
(84, 'Pengadaan dan Pemasangan Panel Inverter SB Bellpark 2', 1.00, 'keg', 20000000.00, 20000000.00, 'barang', 'tender_terbatas_spk', 2025, '9', '', 1, '2026-02-23 02:55:46', '2026-02-24 14:40:48'),
(85, 'Jembatan Pipa Perbaikan perlintasan pipa dan gelagar Perempung Timur  Ǿ 8\"', 1.00, 'keg', 250000000.00, 250000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '8', '', 1, '2026-02-23 02:56:59', '2026-02-24 14:41:40'),
(86, 'Jembatan Pipa Pembuatan perlintasan pipa dan gelagar Kuripan Ǿ 8\"', 1.00, 'keg', 250000000.00, 250000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '9', '', 1, '2026-02-23 02:57:58', '2026-02-24 14:41:40'),
(87, 'Jembatan Pipa Pembuatan Perlintasan jaringan Jl Darul Hikmah', 1.00, 'keg', 300000000.00, 300000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '11', '', 1, '2026-02-23 02:58:55', '2026-02-24 14:41:40'),
(88, 'Jembatan Pipa Pembuatan perlintasan tangkeban 6\"', 1.00, 'keg', 220000000.00, 220000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '10', '', 1, '2026-02-23 03:00:08', '2026-02-24 14:41:40'),
(89, 'Lain-Lain Transdit Logger Pressure (Display) untuk Bidang Distribusi', 1.00, 'keg', 329095000.00, 329095000.00, 'barang', 'tender_terbatas_pkp', 2025, '7,8,9', '', 1, '2026-02-23 03:01:25', '2026-02-26 10:07:50'),
(90, 'Lain-Lain Transdit Logger Pressure (Portable) untuk Bidang Distribusi', 1.00, 'keg', 203895000.00, 203895000.00, 'barang', 'tender_terbatas_pkp', 2025, '7', '', 1, '2026-02-23 03:02:26', '2026-02-26 10:08:26'),
(91, 'Lain-Lain Transdit Manometer (4 Bar) untuk Bidang Distribusi', 1.00, 'keg', 3705000.00, 3705000.00, 'barang', 'pembelian_langsung', 2025, '9', '', 1, '2026-02-23 03:03:42', '2026-02-26 10:09:03'),
(92, 'Lain-Lain Transdit Manometer (4 Bar) Glysterin untuk Bidang Distribusi', 1.00, 'keg', 14625000.00, 14625000.00, 'barang', 'pembelian_langsung', 2025, '8', '', 1, '2026-02-23 03:04:52', '2026-02-26 10:09:32'),
(93, 'Lain-Lain Transdit Manometer Digital Pressure DSMT', 1.00, 'keg', 131064000.00, 131064000.00, 'barang', 'tender_terbatas_pkp', 2025, '6', '', 1, '2026-02-23 03:06:58', '2026-02-26 10:10:02'),
(94, 'Lain-Lain Transdit Flowmeter (Meter Induk Distribusi): 3\"', 1.00, 'keg', 25000000.00, 25000000.00, 'barang', 'tender_terbatas_spk', 2025, '9', '', 1, '2026-02-23 03:08:04', '2026-02-26 10:10:27'),
(95, 'Lain-Lain Transdit Flowmeter (Meter Induk Distribusi): 6\"', 1.00, 'buah', 127902000.00, 127902000.00, 'barang', 'tender_terbatas_pkp', 2025, '7', '', 1, '2026-02-23 03:09:49', '2026-02-26 10:10:38'),
(96, 'Lain-Lain Transdit Flowmeter (Meter Induk Distribusi): 12\"', 1.00, 'buah', 191024000.00, 191024000.00, 'barang', 'tender_terbatas_pkp', 2025, '7', '', 1, '2026-02-23 03:11:04', '2026-02-26 10:10:52'),
(97, 'Lain-Lain Transdit Logger Flowmeter', 1.00, 'buah', 402000000.00, 402000000.00, 'barang', 'tender_terbatas_pkp', 2025, '6,7,8', '', 1, '2026-02-23 03:12:43', '2026-02-26 10:17:50'),
(98, 'Lain-Lain Transdit Baterai Logger (Distribusi)', 10.00, 'buah', 9361000.00, 93610000.00, 'barang', 'tender_terbatas_pkp', 2025, '9', '', 1, '2026-02-23 03:15:12', '2026-02-26 10:17:01'),
(99, 'Lain-Lain Transdit Box Panel', 1.00, 'buah', 42000000.00, 42000000.00, 'barang', 'tender_terbatas_spk', 2025, '8,10', '', 1, '2026-02-23 03:16:54', '2026-02-26 10:28:36'),
(100, 'Fuji Electric FSC Signal Cable Sensor Cables, Pair of 16 foot cables with Lemo', 1.00, 'buah', 40000000.00, 40000000.00, 'barang', 'tender_terbatas_spk', 2025, '8', '', 1, '2026-02-23 03:17:59', '2026-02-24 14:40:48'),
(101, 'Pergantian Gate Valve 12\" di simpang 5 koperasi', 1.00, 'keg', 30000000.00, 30000000.00, 'barang', 'tender_terbatas_spk', 2025, '6', '', 1, '2026-02-23 03:19:08', '2026-02-24 14:40:48'),
(102, 'Lain-Lain Transdit Pembuatan bak meter untuk meter induk', 1.00, 'keg', 180000000.00, 180000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '9', '', 1, '2026-02-23 03:20:07', '2026-02-26 10:18:08'),
(103, 'Lain-Lain Transdit Sparepart Telemetri : Baterai transmiter Mag 8000', 20.00, 'unit', 9534000.00, 190680000.00, 'barang', 'tender_terbatas_pkp', 2025, '4,7', '', 1, '2026-02-23 03:22:33', '2026-02-26 10:18:41'),
(104, 'Lain-Lain Transdit Sparepart Telemetri : Baterai Logger Sofrel', 10.00, 'unit', 9361000.00, 93610000.00, 'barang', 'tender_terbatas_pkp', 2025, '4,7', '', 1, '2026-02-23 03:23:14', '2026-02-26 10:19:26'),
(105, 'Lain-Lain Transdit Sparepart Telemetri : Baterai Logger Ijinus', 10.00, 'unit', 9361000.00, 93610000.00, 'barang', 'tender_terbatas_pkp', 2025, '4,7', '', 1, '2026-02-23 03:24:11', '2026-02-26 10:19:47'),
(106, 'Lain-Lain Transdit Sparepart Telemetri : Kabel Koil dan Kabel electrode', 3.00, 'set', 7800000.00, 23400000.00, 'barang', 'tender_terbatas_spk', 2025, '4,7', '', 1, '2026-02-23 03:25:09', '2026-02-26 10:23:59'),
(107, 'Lain-Lain Transdit Sparepart Telemetri : Modbus PLC', 2.00, 'unit', 41873000.00, 83746000.00, 'barang', 'tender_terbatas_pkp', 2025, '7', '', 1, '2026-02-23 03:26:09', '2026-02-26 10:24:17'),
(108, 'Lain-Lain Transdit Sparepart Telemetri : Modbus Mag 8000', 5.00, 'set', 8637000.00, 43185000.00, 'barang', 'tender_terbatas_spk', 2025, '4,7', '', 1, '2026-02-23 03:27:16', '2026-02-26 10:24:40'),
(109, 'Lain-Lain Transdit Sparepart Telemetri : GPRS PLC', 6.00, 'set', 23384000.00, 140304000.00, 'barang', 'tender_terbatas_pkp', 2025, '4,7', '', 1, '2026-02-23 03:28:43', '2026-02-26 10:25:53'),
(110, 'Lain-Lain Transdit Sparepart Telemetri : PCB Mag 8000', 3.00, 'unit', 75771000.00, 227313000.00, 'barang', 'tender_terbatas_pkp', 2025, '4,7', '', 1, '2026-02-23 03:29:55', '2026-02-26 10:25:08'),
(111, 'Lain-Lain Transdit Sparepart Telemetri : Modbus Mag 6000', 1.00, 'unit', 6952000.00, 6952000.00, 'barang', 'pembelian_langsung', 2025, '4', '', 1, '2026-02-23 03:31:04', '2026-02-26 10:24:29'),
(112, 'Lain-Lain Transdit Dokumen DED : Reviu Dok. RPAM', 1.00, 'keg', 300000000.00, 300000000.00, 'jasa_konsultan', 'tender_terbatas_pkp', 2025, '11', '', 1, '2026-02-23 03:32:27', '2026-02-26 10:26:22'),
(113, 'Lain-Lain Transdit Dokumen DED : Feasibility Study SPAM Dodokan dan SPAM Meninting', 1.00, 'keg', 300000000.00, 300000000.00, 'barang', 'tender_terbatas_pkp', 2025, '10', '', 1, '2026-02-23 03:34:26', '2026-02-26 10:27:12'),
(114, 'Lain-Lain Transdit Dokumen DED : Evaluasi Jaringan Distribusi Wilayah Pelayanan Gunung Sari', 1.00, 'keg', 300000000.00, 300000000.00, 'jasa_konsultan', 'tender_terbatas_pkp', 2025, '10', '', 1, '2026-02-23 03:39:52', '2026-02-26 10:27:24'),
(115, 'Lain-Lain Transdit Dokumen DED : Reservoir Penyeimbang', 1.00, 'keg', 300000000.00, 300000000.00, 'jasa_konsultan', 'tender_terbatas_pkp', 2025, '10', '', 1, '2026-02-23 03:40:21', '2026-02-26 10:27:41'),
(116, 'Lain-Lain Transdit Dokumen DED : Reviu Masterplan Pengendalian Kehilangan Air', 1.00, 'keg', 300000000.00, 300000000.00, 'jasa_konsultan', 'tender_terbatas_pkp', 2025, '11', '', 1, '2026-02-23 03:41:06', '2026-02-26 10:27:56'),
(117, 'Lain-Lain Transdit Dokumen DED : Pembentukan Zona Pelayanan/DMA', 1.00, 'keg', 300000000.00, 300000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '9,11', '', 1, '2026-02-23 03:41:48', '2026-02-26 10:28:09'),
(118, 'Interior Ruang SCADA Kehilangan Air', 1.00, 'keg', 300000000.00, 300000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '9', '', 1, '2026-02-23 03:42:28', '2026-02-24 14:41:40'),
(119, 'Rehabilitasi Dan Pengecatan Gedung Kantor Pelayanan Gunung Sari', 1.00, 'keg', 200000000.00, 200000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '8', '', 1, '2026-02-23 03:43:20', '2026-02-24 14:41:40'),
(120, 'Rehabilitasi (Pergantian) Kamar Mandi di Ruang Pelayanan Kantor Pusat', 2.00, 'keg', 25000000.00, 50000000.00, 'sipil', 'tender_terbatas_spk', 2025, '8', '', 1, '2026-02-23 03:45:16', '2026-02-24 14:40:48'),
(121, 'Grounding Penangkal Petir Wilayah Mataram', 1.00, 'keg', 90000000.00, 90000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '5', '', 1, '2026-02-23 03:47:19', '2026-02-24 14:41:40'),
(122, 'Grounding Penangkal Petir Wilayah Gerung', 1.00, 'keg', 90000000.00, 90000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '5', '', 1, '2026-02-23 03:48:31', '2026-02-24 14:41:40'),
(123, 'Grounding Penangkal Petir Wilayah Gunungsari', 1.00, 'keg', 80000000.00, 80000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '5', '', 1, '2026-02-23 03:49:41', '2026-02-24 14:41:40'),
(124, 'Pemagaran Kawat Duri Tanah Gegutu', 1.00, 'keg', 300000000.00, 300000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '9', '', 1, '2026-02-23 03:50:39', '2026-02-24 14:41:40'),
(125, 'Pembuatan Lantai Plat Dan Dinding  Gudang Terbuka di Gudang Gegutu', 1.00, 'keg', 105000000.00, 105000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '5', '', 1, '2026-02-23 03:52:04', '2026-02-24 14:41:40'),
(126, 'Pembuatan Gudang Perangkat dan Peralatan IT di Gudang Gegutu', 1.00, 'keg', 105000000.00, 105000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '4', '', 1, '2026-02-23 03:52:25', '2026-02-24 14:41:40'),
(127, 'Komputer Rakitan Sekretariat Perusahaan', 1.00, 'unit', 32500000.00, 32500000.00, 'barang', 'tender_terbatas_spk', 2025, '4', '', 1, '2026-02-23 03:54:04', '2026-02-24 14:40:48'),
(128, 'Komputer Rakitan DSMT', 1.00, 'unit', 32500000.00, 32500000.00, 'barang', 'tender_terbatas_spk', 2025, '4', '', 1, '2026-02-23 03:54:39', '2026-02-24 14:40:48'),
(129, 'Komputer Builup Keuangan', 1.00, 'unit', 18500000.00, 18500000.00, 'barang', 'tender_terbatas_spk', 2025, '4', '', 1, '2026-02-23 03:56:06', '2026-02-24 14:40:48'),
(130, 'Komputer Builup Kehilangan Air', 2.00, 'unit', 18500000.00, 37000000.00, 'barang', 'tender_terbatas_spk', 2025, '4', '', 1, '2026-02-23 03:56:52', '2026-02-24 14:40:48'),
(131, 'Komputer Builup Distribusi', 1.00, 'unit', 0.00, 22000000.00, 'barang', 'tender_terbatas_spk', 2025, '7', '', 1, '2026-02-23 03:58:04', '2026-02-24 14:40:48'),
(132, 'Mini PC 24\" : Keuangan', 3.00, 'unit', 13000000.00, 39000000.00, 'barang', 'tender_terbatas_spk', 2025, '4', '', 1, '2026-02-23 03:59:11', '2026-02-24 14:40:48'),
(133, 'Mini PC 24\" : Pelayanan', 1.00, 'unit', 13000000.00, 13000000.00, 'barang', 'pembelian_langsung', 2025, '4', '', 1, '2026-02-23 04:00:12', '2026-02-23 04:00:12'),
(134, 'Mini PC 24\" : SDM', 3.00, 'unit', 13000000.00, 39000000.00, 'barang', 'tender_terbatas_spk', 2025, '4', '', 1, '2026-02-23 04:00:56', '2026-02-24 14:40:48'),
(135, 'Mini PC 24\" : Produksi', 1.00, 'unit', 13000000.00, 13000000.00, 'barang', 'pembelian_langsung', 2025, '4', '', 1, '2026-02-23 04:02:17', '2026-02-23 04:02:17'),
(136, 'Mini PC 24\" : Umum', 1.00, 'unit', 13000000.00, 13000000.00, 'barang', 'pembelian_langsung', 2025, '4', '', 1, '2026-02-23 04:02:52', '2026-02-23 04:02:52'),
(137, 'Notebook :  Pelayanan', 2.00, 'unit', 16685000.00, 33370000.00, 'barang', 'tender_terbatas_spk', 2025, '4', '', 1, '2026-02-23 06:32:18', '2026-02-24 14:40:48'),
(138, 'Notebook : Keuangan', 2.00, 'unit', 16685000.00, 33370000.00, 'barang', 'tender_terbatas_spk', 2025, '4,8', '', 1, '2026-02-23 06:54:48', '2026-02-24 14:40:48'),
(139, 'Notebook : Aset', 1.00, 'unit', 16768000.00, 16768000.00, 'barang', 'tender_terbatas_spk', 2025, '7', '', 1, '2026-02-23 06:58:00', '2026-02-24 14:40:48'),
(140, 'Notebook : SPI', 1.00, 'unit', 17941000.00, 17941000.00, 'barang', 'tender_terbatas_spk', 2025, '7', '', 1, '2026-02-23 06:59:55', '2026-02-24 14:40:48'),
(141, 'Notebook : Sekretariatan Perusahaan', 1.00, 'unit', 24125000.00, 24125000.00, 'barang', 'tender_terbatas_spk', 2025, '4', '', 1, '2026-02-23 07:01:28', '2026-02-24 14:40:48'),
(142, 'Printer L15150 : DSMT', 1.00, 'unit', 23500000.00, 23500000.00, 'barang', 'tender_terbatas_spk', 2025, '6', '', 1, '2026-02-23 07:02:58', '2026-02-24 14:40:48'),
(143, 'Printer L15150 : Sekretariatan Perusahaan', 1.00, 'unit', 23500000.00, 23500000.00, 'barang', 'tender_terbatas_spk', 2025, '7', '', 1, '2026-02-23 07:04:11', '2026-02-24 14:40:48'),
(145, 'Printer L15150 : Aset', 1.00, 'unit', 23500000.00, 23500000.00, 'barang', 'pembelian_langsung', 2025, '5', '', 1, '2026-02-23 07:07:58', '2026-02-23 07:07:58'),
(146, 'Printer L5190 : Pelayanan', 1.00, 'unit', 4000000.00, 4000000.00, 'barang', 'pembelian_langsung', 2025, '8', '', 1, '2026-02-23 07:09:27', '2026-02-23 07:09:27'),
(147, 'Firewall : Keuangan', 1.00, 'unit', 2000000.00, 2000000.00, 'barang', 'pembelian_langsung', 2025, '6', '', 1, '2026-02-23 10:07:07', '2026-02-23 10:07:07'),
(148, 'Firewall : Distribusi', 1.00, 'unit', 2000000.00, 2000000.00, 'barang', 'pembelian_langsung', 2025, '6', '', 1, '2026-02-23 10:07:47', '2026-02-23 10:07:47'),
(149, 'Firewall : Kehilangan Air', 1.00, 'unit', 2000000.00, 2000000.00, 'barang', 'pembelian_langsung', 2025, '6', '', 1, '2026-02-23 10:08:20', '2026-02-23 10:08:20'),
(150, 'Scanner : Distribusi', 2.00, 'buah', 40498500.00, 80997000.00, 'barang', 'tender_terbatas_pkp', 2025, '9', '', 1, '2026-02-23 10:09:59', '2026-02-24 14:41:40'),
(151, 'AC Daikin (1 PK) : Teknologi Informasi', 1.00, 'unit', 6572000.00, 6572000.00, 'barang', 'pembelian_langsung', 2025, '7', '', 1, '2026-02-23 10:12:28', '2026-02-23 12:19:16'),
(152, 'AC Daikin (1 PK) : Tenaga Ahli', 1.00, 'unit', 6725000.00, 6725000.00, 'barang', 'pembelian_langsung', 2025, '2', '', 1, '2026-02-23 10:28:38', '2026-02-23 10:28:38'),
(153, 'AC Daikin (1 PK) : Aset', 1.00, 'unit', 5775000.00, 5775000.00, 'barang', 'pembelian_langsung', 2025, '4', '', 1, '2026-02-23 10:29:52', '2026-02-23 10:29:52'),
(154, 'AC Daikin (2 PK) : Aset', 1.00, 'unit', 9115000.00, 9115000.00, 'barang', 'pembelian_langsung', 2025, '4', '', 1, '2026-02-23 10:30:55', '2026-02-23 13:02:26'),
(155, 'HandPhone : Pelayanan', 12.00, 'unit', 4500000.00, 54000000.00, 'barang', 'tender_terbatas_pkp', 2025, '8', '', 1, '2026-02-23 10:32:06', '2026-02-24 14:41:40'),
(156, 'HandPhone : Kehilangan Air', 6.00, 'unit', 4500000.00, 27000000.00, 'barang', 'tender_terbatas_spk', 2025, '8', '', 1, '2026-02-23 10:33:01', '2026-02-24 14:40:48'),
(158, 'Handphone : Distribusi', 5.00, 'unit', 4500000.00, 22500000.00, 'barang', 'tender_terbatas_spk', 2025, '8', '', 1, '2026-02-23 14:05:21', '2026-02-24 14:40:48'),
(159, 'TV LED : Distribusi (43\")', 1.00, 'unit', 16350000.00, 16350000.00, 'barang', 'tender_terbatas_spk', 2025, '5,7', '', 1, '2026-02-25 14:14:26', '2026-02-25 14:14:26'),
(160, 'Monitor 24\" : Keuangan', 2.00, 'unit', 4000000.00, 8000000.00, 'barang', 'pembelian_langsung', 2025, '4', '', 1, '2026-02-25 14:32:00', '2026-02-25 14:32:00'),
(161, 'Monitor 24\" : Pelayanan', 1.00, 'unit', 4000000.00, 4000000.00, 'barang', 'pembelian_langsung', 2025, '4', '', 1, '2026-02-25 15:09:58', '2026-02-25 15:09:58'),
(162, 'Monitor 24\" : SDM', 3.00, 'unit', 4000000.00, 12000000.00, 'barang', 'pembelian_langsung', 2025, '4', '', 1, '2026-02-25 15:10:36', '2026-02-25 15:10:36'),
(163, 'Monitor 24\" : Produksi', 1.00, 'unit', 4000000.00, 4000000.00, 'barang', 'pembelian_langsung', 2025, '4', '', 1, '2026-02-25 15:11:11', '2026-02-25 15:11:11'),
(164, 'Monitor 24\" : Umum', 1.00, 'unit', 4000000.00, 4000000.00, 'barang', 'pembelian_langsung', 2025, '4', '', 1, '2026-02-25 15:11:54', '2026-02-25 15:11:54'),
(165, 'Monitor 27\" : DSMT', 4.00, 'unit', 5000000.00, 20000000.00, 'barang', 'tender_terbatas_spk', 2025, '4', '', 1, '2026-02-25 15:14:20', '2026-02-25 15:14:20'),
(166, 'Papper Shredder : Keuangan', 1.00, 'unit', 7500000.00, 7500000.00, 'barang', 'pembelian_langsung', 2025, '2', '', 1, '2026-02-25 15:16:00', '2026-02-25 15:16:00'),
(167, 'Papper Shredder : Distribusi', 1.00, 'unit', 7500000.00, 7500000.00, 'barang', 'pembelian_langsung', 2025, '8', '', 1, '2026-02-25 15:16:48', '2026-02-25 15:16:48'),
(168, 'Papper Shredder : Aset', 1.00, 'unit', 7500000.00, 7500000.00, 'barang', 'pembelian_langsung', 2025, '8', '', 1, '2026-02-25 15:17:23', '2026-02-25 15:17:23'),
(169, 'Mesin Penghitung Uang Besar', 1.00, 'unit', 17736000.00, 17736000.00, 'barang', 'tender_terbatas_spk', 2025, '6', '', 1, '2026-02-25 15:18:37', '2026-02-25 15:18:37'),
(170, 'Mesin Penghitung Uang Kecil', 2.00, 'unit', 4135000.00, 8270000.00, 'barang', 'pembelian_langsung', 2025, '5', '', 1, '2026-02-25 15:19:52', '2026-02-25 15:19:52'),
(171, 'Proyektor : Aset', 1.00, 'unit', 13270000.00, 13270000.00, 'barang', 'pembelian_langsung', 2025, '8', '', 1, '2026-02-25 15:20:52', '2026-02-25 15:20:52'),
(172, 'Proyektor : SPI', 1.00, 'unit', 13270000.00, 13270000.00, 'barang', 'pembelian_langsung', 2025, '8', '', 1, '2026-02-25 15:21:26', '2026-02-25 15:21:26'),
(173, 'Tambahan Mesin-Mesin Kantor Firewall', 2.00, 'unit', 175000000.00, 350000000.00, 'barang', 'tender_terbatas_pkp', 2025, '10', '', 1, '2026-02-25 15:22:42', '2026-02-25 15:22:42'),
(174, 'Tambahan Mesin-Mesin Kantor untuk IT Switch Core Aruba HPE,Switch Distribusi Aruba HPE', 1.00, 'unit', 520000000.00, 520000000.00, 'barang', 'tender_terbatas_pkp', 2025, '7,9', '', 1, '2026-02-25 15:24:54', '2026-02-25 15:24:54'),
(175, 'Tambahan Mesin-Mesin Kantor untuk IT Mikrobits SFP+ Transceiver SFP-10G-SR-MM 300M', 1.00, 'unit', 414000000.00, 414000000.00, 'barang', 'tender_terbatas_pkp', 2025, '9', '', 1, '2026-02-25 15:26:24', '2026-02-25 15:26:24'),
(176, 'Tambahan Mesin-Mesin Kantor untuk IT Server DRC', 1.00, 'unit', 300000000.00, 300000000.00, 'barang', 'tender_terbatas_pkp', 2025, '9', '', 1, '2026-02-25 15:27:26', '2026-02-25 15:27:26'),
(177, 'Tambahan Mesin-Mesin Kantor untuk IT Switch Unmanage 8 port', 5.00, 'unit', 6250000.00, 31250000.00, 'barang', 'tender_terbatas_spk', 2025, '8', '', 1, '2026-02-25 15:28:28', '2026-02-25 15:28:28'),
(178, 'Tambahan Mesin-Mesin Kantor untuk IT Switch Unmanage 5 port', 8.00, 'unit', 12000000.00, 96000000.00, 'barang', 'tender_terbatas_pkp', 2025, '8', '', 1, '2026-02-25 15:29:14', '2026-02-25 15:29:14'),
(179, 'Tambahan Mesin-Mesin Kantor untuk IT Switch Manage Aruba', 2.00, 'unit', 60000000.00, 120000000.00, 'barang', 'tender_terbatas_pkp', 2025, '8', '', 1, '2026-02-25 15:30:12', '2026-02-25 15:30:12'),
(180, 'Tambahan Mesin-Mesin Kantor untuk IT HDD CCTV', 5.00, 'unit', 50000000.00, 250000000.00, 'barang', 'tender_terbatas_pkp', 2025, '7', '', 1, '2026-02-25 15:31:18', '2026-02-25 15:31:18'),
(181, 'Tambahan Mesin-Mesin Kantor untuk IT Media Converter FO 10G', 4.00, 'unit', 20000000.00, 80000000.00, 'barang', 'tender_terbatas_pkp', 2025, '7', '', 1, '2026-02-25 15:32:03', '2026-02-25 15:32:03'),
(182, 'Tambahan Mesin-Mesin Kantor IP Camera 2 MP', 6.00, 'unit', 2500000.00, 15000000.00, 'barang', 'pembelian_langsung', 2025, '8', '', 1, '2026-02-25 15:33:02', '2026-02-25 15:33:02'),
(183, 'Tambahan Mesin-Mesin Kantor Videotron', 1.00, 'unit', 237500000.00, 237500000.00, 'barang', 'tender_terbatas_pkp', 2025, '9', '', 1, '2026-02-25 15:35:00', '2026-02-25 15:35:00'),
(184, 'Tambahan Mesin-Mesin Kantor ATS & Panel di Kantor Pusat Mataram (Aula)', 1.00, 'set', 117000000.00, 117000000.00, 'barang', 'tender_terbatas_pkp', 2025, '5', '', 1, '2026-02-25 15:35:45', '2026-02-25 15:35:45'),
(185, 'Alat-Alat Laboratorium Turbiditimeter,Alat pH Meter,TDS Meter ,Conductivity Meter,Autoklaf,Hotplate Magnetic Stirer', 1.00, 'set', 410000000.00, 410000000.00, 'barang', 'tender_terbatas_pkp', 2025, '3', '', 1, '2026-02-25 15:37:32', '2026-02-25 15:37:32'),
(186, 'Alat-Alat Laboratorium pH Meter : Produksi', 2.00, 'unit', 35000000.00, 70000000.00, 'barang', 'tender_terbatas_pkp', 2025, '6', '', 1, '2026-02-25 15:39:40', '2026-02-25 15:39:40'),
(187, 'Alat-Alat Laboratorium TDS Meter : Produksi', 2.00, 'unit', 15000000.00, 30000000.00, 'barang', 'tender_terbatas_spk', 2025, '3', '', 1, '2026-02-25 15:42:23', '2026-02-25 15:42:23'),
(188, 'Alat-Alat Laboratorium TDS Meter : DSMT', 2.00, 'unit', 15000000.00, 30000000.00, 'barang', 'tender_terbatas_spk', 2025, '6', '', 1, '2026-02-25 15:43:30', '2026-02-25 15:43:30'),
(189, 'Alat-Alat Laboratorium Turbidimeter : Desain dan Mutu', 1.00, 'unit', 55000000.00, 55000000.00, 'barang', 'tender_terbatas_pkp', 2025, '6', '', 1, '2026-02-25 15:45:14', '2026-02-25 15:45:14'),
(190, 'Peralatan dan Perlengkapan Kerja Mesin Genset : Pengurusan Ijin mesin genset NIDI dan SLO (100 KVA)', 3.00, 'set', 13000000.00, 39000000.00, 'jasa_lainnya', 'tender_terbatas_spk', 2025, '8', '', 1, '2026-02-25 15:47:06', '2026-02-25 15:47:06'),
(191, 'Peralatan dan Perlengkapan Kerja Mesin Genset Besar : Kehilangan Air', 2.00, 'buah', 4750000.00, 9500000.00, 'barang', 'pembelian_langsung', 2025, '2', '', 1, '2026-02-25 15:50:13', '2026-02-25 15:50:13'),
(192, 'Peralatan dan Perlengkapan Kerja Jack Hammer : Distribusi', 4.00, 'buah', 3650500.00, 14602000.00, 'barang', 'pembelian_langsung', 2025, '8', '', 1, '2026-02-25 15:52:12', '2026-02-25 15:52:12'),
(193, 'Peralatan dan Perlengkapan Kerja Jack Hammer : Kehilangan Air', 1.00, 'buah', 13950000.00, 13950000.00, 'barang', 'pembelian_langsung', 2025, '2', '', 1, '2026-02-25 15:53:11', '2026-02-25 15:53:11'),
(194, 'Peralatan dan Perlengkapan Kerja Serkel Duduk', 1.00, 'buah', 7697000.00, 7697000.00, 'barang', 'pembelian_langsung', 2025, '6', '', 1, '2026-02-25 15:54:25', '2026-02-25 15:54:25'),
(195, 'Peralatan dan Perlengkapan Kerja Mesin Senai', 1.00, 'buah', 13252000.00, 13252000.00, 'barang', 'pembelian_langsung', 2025, '7', '', 1, '2026-02-25 15:55:05', '2026-02-25 15:55:05'),
(196, 'Peralatan dan Perlengkapan Kerja Meteran Digital (Laser Distance Meter-DSMT)', 2.00, 'buah', 500000.00, 1000000.00, 'barang', 'pembelian_langsung', 2025, '8', '', 1, '2026-02-25 15:55:56', '2026-02-25 15:55:56'),
(197, 'Peralatan dan Perlengkapan Kerja Pengukur Kedalaman (Fishing Reel Counter-DSMT)', 1.00, 'buah', 500000.00, 500000.00, 'barang', 'pembelian_langsung', 2025, '8', '', 1, '2026-02-25 15:56:54', '2026-02-25 15:56:54'),
(198, 'Peralatan dan Perlengkapan Kerja Meteran Dorong (Measuring Wheel-DSMT)', 2.00, 'buah', 400000.00, 800000.00, 'barang', 'pembelian_langsung', 2025, '8', '', 1, '2026-02-25 20:47:39', '2026-02-25 20:47:39'),
(199, 'Peralatan dan Perlengkapan Kerja GPS Garmin : DSMT', 3.00, 'buah', 10707000.00, 32121000.00, 'barang', 'tender_terbatas_spk', 2025, '8', '', 1, '2026-02-25 21:02:55', '2026-02-25 21:02:55'),
(200, 'Peralatan dan Perlengkapan Kerja Mesin Pompa Hisap+selang : Kehilangan Air', 2.00, 'buah', 5450000.00, 10900000.00, 'barang', 'pembelian_langsung', 2025, '2,8', '', 1, '2026-02-25 21:04:30', '2026-02-25 21:05:25'),
(201, 'Peralatan dan Perlengkapan Kerja Mesin Pompa Hisap+selang : Distribusi', 1.00, 'buah', 5450000.00, 5450000.00, 'barang', 'pembelian_langsung', 2025, '8', '', 1, '2026-02-25 21:11:18', '2026-02-25 21:11:18'),
(202, 'Peralatan dan Perlengkapan Kerja Mesin Potong Rumput : Umum', 6.00, 'buah', 5700000.00, 34200000.00, 'barang', 'tender_terbatas_spk', 2025, '8,10', '', 1, '2026-02-25 21:12:45', '2026-02-25 21:14:15'),
(203, 'Peralatan dan Perlengkapan Kerja Pengadaan dan Pemasangan Fire Alert System Ruang Server', 1.00, 'set', 150000000.00, 150000000.00, 'barang', 'tender_terbatas_pkp', 2025, '10', '', 1, '2026-02-25 21:14:00', '2026-02-25 21:14:00'),
(204, 'Peralatan Gudang Rak besi Gudang A', 1.00, 'keg', 180000000.00, 180000000.00, 'sipil', 'tender_terbatas_pkp', 2025, '9', '', 1, '2026-02-25 21:15:16', '2026-02-25 21:15:16'),
(205, 'Peralatan Gudang Pallet Untuk Bahan Kimia : Umum', 28.00, 'buahq', 1500000.00, 42000000.00, 'barang', 'tender_terbatas_spk', 2025, '6,8,10,12', '', 1, '2026-02-25 21:17:06', '2026-02-25 21:17:06'),
(206, 'Perabot Kantor Lemari Arsip : Umum', 1.00, 'unit', 4180000.00, 4180000.00, 'barang', 'pembelian_langsung', 2025, '9', '', 1, '2026-02-25 21:19:12', '2026-02-25 21:19:12'),
(207, 'Perabot Kantor Lemari Arsip : Pelayanan', 3.00, 'unit', 4180000.00, 12540000.00, 'barang', 'pembelian_langsung', 2025, '2,8', '', 1, '2026-02-25 21:50:39', '2026-02-25 21:50:39'),
(208, 'Perabot Kantor Lemari Arsip : Sekretariat Perusahaan', 2.00, 'unit', 4180000.00, 8360000.00, 'barang', 'pembelian_langsung', 2025, '2,10', '', 1, '2026-02-25 21:51:39', '2026-02-25 21:51:39'),
(209, 'Perabot Kantor Lemari Arsip : Desain & Mutu', 2.00, 'unit', 4180000.00, 8360000.00, 'barang', 'pembelian_langsung', 2025, '7', '', 1, '2026-02-25 22:03:13', '2026-02-25 22:03:13'),
(210, 'Perabot Kantor Lemari Arsip : Renbang', 1.00, 'unit', 4180000.00, 4180000.00, 'barang', 'pembelian_langsung', 2025, '9', '', 1, '2026-02-25 22:05:23', '2026-02-25 22:08:37'),
(211, 'Perabot Kantor Lemari Arsip : Aset', 2.00, 'unit', 3300000.00, 6600000.00, 'barang', 'pembelian_langsung', 2025, '4,5', '', 1, '2026-02-25 22:06:06', '2026-02-25 22:08:53'),
(212, 'Perabot Kantor Lemari Arsip Besi : Sekretariat Perusahaan', 14.00, 'unit', 12500000.00, 175000000.00, 'barang', 'tender_terbatas_pkp', 2025, '6,7,10', '', 1, '2026-02-25 22:09:59', '2026-02-25 22:09:59'),
(213, 'Perabot Kantor Kursi Kerja : Umum', 7.00, 'unit', 3543000.00, 24801000.00, 'barang', 'tender_terbatas_spk', 2025, '6,8', '', 1, '2026-02-25 23:10:54', '2026-02-25 23:10:54'),
(214, 'Perabot Kantor Kursi Kerja :  Pelayanan', 2.00, 'unit', 3543000.00, 7086000.00, 'barang', 'pembelian_langsung', 2025, '9', '', 1, '2026-02-25 23:15:07', '2026-02-25 23:15:07'),
(215, 'Perabot Kantor Kursi Kerja : Desain & Mutu', 1.00, 'unit', 3543000.00, 3543000.00, 'barang', 'pembelian_langsung', 2025, '7', '', 1, '2026-02-25 23:36:39', '2026-02-25 23:36:39'),
(216, 'Perabot Kantor Kursi Kerja : Distribusi', 6.00, 'unit', 3543000.00, 21258000.00, 'barang', 'tender_terbatas_spk', 2025, '6,8,10', '', 1, '2026-02-25 23:38:18', '2026-02-25 23:38:18'),
(217, 'Perabot Kantor Kursi Kerja : Produksi', 1.00, 'unit', 3543000.00, 3543000.00, 'barang', 'pembelian_langsung', 2025, '4', '', 1, '2026-02-25 23:39:08', '2026-02-25 23:39:08'),
(218, 'Perabot Kantor Kursi Kerja : Kehilangan Air', 3.00, 'unit', 3543000.00, 10629000.00, 'barang', 'pembelian_langsung', 2025, '3,6', '', 1, '2026-02-25 23:40:40', '2026-02-25 23:40:40'),
(219, 'Perabot Kantor Kursi Kerja : Renbang', 3.00, 'unit', 3543000.00, 10629000.00, 'barang', 'pembelian_langsung', 2026, '10', '', 1, '2026-02-25 23:41:49', '2026-02-25 23:41:49'),
(220, 'Perabot Kantor Kursi Kerja : Keuangan', 2.00, 'unit', 3543000.00, 7086000.00, 'barang', 'pembelian_langsung', 2025, '5', '', 1, '2026-02-25 23:43:26', '2026-02-25 23:43:26'),
(221, 'Perabot Kantor Meja Kerja : Umum', 2.00, 'unit', 4409000.00, 8818000.00, 'barang', 'pembelian_langsung', 2025, '8', '', 1, '2026-02-25 23:44:39', '2026-02-25 23:44:39'),
(222, 'Perabot Kantor Meja Kerja : Sekretariat Perusahaan', 2.00, 'unit', 4409000.00, 8818000.00, 'barang', 'pembelian_langsung', 2025, '2,6', '', 1, '2026-02-25 23:58:08', '2026-02-25 23:58:08'),
(223, 'Perabot Kantor Meja Kerja : Desain & Mutu', 1.00, 'unit', 4409000.00, 4409000.00, 'barang', 'pembelian_langsung', 2025, '8', '', 1, '2026-02-25 23:59:08', '2026-02-25 23:59:08'),
(224, 'Perabot Kantor Meja Kerja : Distribusi', 3.00, 'unit', 4409000.00, 13227000.00, 'barang', 'pembelian_langsung', 2025, '7', '', 1, '2026-02-26 00:00:31', '2026-02-26 00:00:31'),
(225, 'Perabot Kantor Meja Kerja : Kehilangan Air', 3.00, 'unit', 4409000.00, 13227000.00, 'barang', 'pembelian_langsung', 2025, '3,6', '', 1, '2026-02-26 00:01:36', '2026-02-26 00:01:36'),
(226, 'Perabot Kantor Kursi & Meja Rapat : Pelayanan', 1.00, 'unit', 32526000.00, 32526000.00, 'barang', 'tender_terbatas_spk', 2025, '3,6', '', 1, '2026-02-26 00:02:42', '2026-02-26 00:02:42'),
(227, 'Perabot Kantor Kursi & Meja Rapat : Aset', 1.00, 'unit', 7349000.00, 7349000.00, 'barang', 'pembelian_langsung', 2025, '8', '', 1, '2026-02-26 00:03:30', '2026-02-26 00:03:30'),
(228, 'Perabot Kantor Meja Printer : Tenaga Ahli', 1.00, 'unit', 875000.00, 875000.00, 'barang', 'pembelian_langsung', 2025, '3', '', 1, '2026-02-26 00:04:46', '2026-02-26 00:04:46'),
(229, 'Perabot Kantor Meja Manager : Aset', 1.00, 'unit', 3900000.00, 3900000.00, 'barang', 'pembelian_langsung', 2025, '4', '', 1, '2026-02-26 00:05:48', '2026-02-26 00:05:48'),
(230, 'Perabot Kantor Meja Asisten Manager : Aset', 2.00, 'unit', 5000000.00, 10000000.00, 'barang', 'pembelian_langsung', 2025, '4', '', 1, '2026-02-26 00:06:49', '2026-02-26 00:06:49'),
(231, 'Perabot Kantor Kursi Manager : Aset', 1.00, 'unit', 3500000.00, 3500000.00, 'barang', 'pembelian_langsung', 2025, '4', '', 1, '2026-02-26 00:11:31', '2026-02-26 00:11:31'),
(232, 'Perabot Kantor Meja Pingpong', 1.00, 'unit', 11500000.00, 11500000.00, 'barang', 'pembelian_langsung', 2025, '4', '', 1, '2026-02-26 00:12:27', '2026-02-26 00:12:27'),
(233, 'Aktiva Tak Berwujud Asistensi Penyusunan Pedoman QAIP (Quality Assurance Improvement Program)', 1.00, 'keg', 70000000.00, 70000000.00, 'jasa_konsultan', 'tender_terbatas_pkp', 2025, '6', '', 1, '2026-02-26 00:14:06', '2026-02-26 00:14:06'),
(234, 'Aktiva Tak Berwujud Asistensi Penyusunan Petunjuk Teknis Dokumen RKA dan Kinerja', 1.00, 'keg', 100000000.00, 100000000.00, 'jasa_konsultan', 'tender_terbatas_pkp', 2025, '8', '', 1, '2026-02-26 00:14:53', '2026-02-26 00:14:53'),
(235, 'Pengembangan Jaringan Aksesoris untuk pengembangan', 1.00, 'set', 282102400.00, 282102400.00, 'barang', 'tender_terbatas_pkp', 2025, '6,7,8,9,10,11,12', '', 1, '2026-02-26 00:17:14', '2026-02-26 00:17:14'),
(236, 'Pengembangan Jaringan Bahan : Pipa PVC Ø 1\" Pipa PVC Ø 1,5\" Pipa PVC Ø 2\" Pipa PVC Ø 3\" Pipa PVC Ø 4\" Pipa PVC Ø 6\" Pipa PVC Ø 8\" Pipa PVC Ø 10\" Pipa GI Ø 1,5\" Pipa GI Ø 2\" Pipa GI Ø 3\" Pipa GI Ø 4\"', 1.00, 'set', 293840800.00, 293840800.00, 'barang', 'tender_terbatas_pkp', 2025, '6,7,8,9,10,11,12', '', 1, '2026-02-26 00:20:28', '2026-02-26 00:20:28'),
(237, 'Sambungan Baru Rangkaian SL Reguler', 1.00, 'set', 642645000000.00, 642645000000.00, 'barang', 'tender_umum', 2025, '1', '', 1, '2026-02-26 00:21:49', '2026-02-26 00:21:49'),
(238, 'Bahan Kimia  Kaporit', 1.00, 'set', 9841740000.00, 9841740000.00, 'barang', 'tender_umum', 2025, '1', '', 1, '2026-02-26 00:23:12', '2026-03-01 15:40:47'),
(239, 'Bangunan dan Perbaikan Instalasi Sumber DED Perencanaan Pembangunan Prasedimentasi dan Optimalisasi Intake Sumber Air Remeneng', 1.00, 'keg', 297060000.00, 297060000.00, 'jasa_konsultan', 'tender_terbatas_pkp', 2025, '5', '', 1, '2026-02-26 00:37:01', '2026-02-26 00:37:01'),
(240, 'Perabot Kantor Kursi Kerja : Renbang', 3.00, 'unit', 3543000.00, 10629000.00, 'barang', 'pembelian_langsung', 2025, '10', '', 1, '2026-02-26 15:55:02', '2026-02-26 15:55:02');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$wo7iAQsI1FqUSzBxbOX.nu2Pvi9UyeRp93TpKt2GBjPEVg7y7XL1u', 'Staf Pengadaan', 'admin', '2026-02-20 12:08:17');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `realisasi_detail`
--
ALTER TABLE `realisasi_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `realisasi_id` (`realisasi_id`),
  ADD KEY `rencana_id` (`rencana_id`);

--
-- Indeks untuk tabel `realisasi_kegiatan`
--
ALTER TABLE `realisasi_kegiatan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeks untuk tabel `rencana_kegiatan`
--
ALTER TABLE `rencana_kegiatan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `realisasi_detail`
--
ALTER TABLE `realisasi_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=238;

--
-- AUTO_INCREMENT untuk tabel `realisasi_kegiatan`
--
ALTER TABLE `realisasi_kegiatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=149;

--
-- AUTO_INCREMENT untuk tabel `rencana_kegiatan`
--
ALTER TABLE `rencana_kegiatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=241;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `realisasi_detail`
--
ALTER TABLE `realisasi_detail`
  ADD CONSTRAINT `realisasi_detail_ibfk_1` FOREIGN KEY (`realisasi_id`) REFERENCES `realisasi_kegiatan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `realisasi_detail_ibfk_2` FOREIGN KEY (`rencana_id`) REFERENCES `rencana_kegiatan` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `realisasi_kegiatan`
--
ALTER TABLE `realisasi_kegiatan`
  ADD CONSTRAINT `realisasi_kegiatan_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `rencana_kegiatan`
--
ALTER TABLE `rencana_kegiatan`
  ADD CONSTRAINT `rencana_kegiatan_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
