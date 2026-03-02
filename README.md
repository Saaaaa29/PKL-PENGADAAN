# SIPEKA - Sistem Pengadaan Kegiatan
**Web-based Procurement Budget Dashboard**

## 🚀 Cara Instalasi di XAMPP

### 1. Copy Project
Salin folder `procurement` ke:
```
C:\xampp\htdocs\procurement
```

### 2. Import Database
1. Buka **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Buat database baru bernama `procurement_db` (atau biarkan SQL yang buat otomatis)
3. Klik tab **Import** → pilih file `database.sql` → klik **Go**

### 3. Konfigurasi Database
Edit file `config/database.php` jika perlu:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');     // username MySQL Anda
define('DB_PASS', '');         // password MySQL Anda (default XAMPP: kosong)
define('DB_NAME', 'procurement_db');
```

### 4. Jalankan Aplikasi
Buka browser: **http://localhost/procurement**

### 5. Login Default
| Field    | Value      |
|----------|------------|
| Username | `admin`    |
| Password | `admin123` |

> ⚠️ **Ganti password setelah login pertama!**

---

## 📁 Struktur Folder
```
procurement/
├── config/
│   ├── app.php          # Konfigurasi aplikasi & konstanta
│   └── database.php     # Konfigurasi koneksi MySQL
├── includes/
│   ├── functions.php    # Fungsi helper global
│   ├── header.php       # Template header HTML
│   ├── sidebar.php      # Template sidebar navigasi
│   └── footer.php       # Template footer & JS
├── modules/
│   ├── auth/
│   │   ├── login.php    # Halaman login
│   │   └── logout.php   # Proses logout
│   ├── rencana/
│   │   ├── index.php    # Daftar rencana kegiatan
│   │   ├── form.php     # Tambah/edit rencana
│   │   ├── hapus.php    # Hapus rencana
│   │   ├── print.php    # Cetak rencana (tabel jadwal)
│   │   └── export.php   # Export Excel
│   ├── realisasi/
│   │   ├── index.php    # Daftar realisasi
│   │   ├── form.php     # Tambah/edit realisasi (multi-item)
│   │   ├── detail.php   # Detail realisasi
│   │   ├── hapus.php    # Hapus realisasi
│   │   ├── print.php    # Cetak realisasi
│   │   └── export.php   # Export Excel
│   ├── laporan/
│   │   ├── index.php    # Laporan rencana vs realisasi
│   │   ├── print.php    # Cetak laporan
│   │   └── export.php   # Export laporan ke Excel
│   └── users/
│       ├── index.php    # Manajemen user
│       ├── proses.php   # Tambah/edit user
│       └── hapus.php    # Hapus user
├── assets/
│   ├── css/style.css    # Custom stylesheet
│   └── js/app.js        # Custom JavaScript
├── index.php            # Dashboard utama
├── database.sql         # File SQL untuk import
└── README.md            # Dokumentasi ini
```

---

## ✨ Fitur Utama

### 🗓️ Rencana Kegiatan
- Input rencana dengan volume, satuan, nilai satuan
- **Nilai anggaran otomatis** = volume × nilai satuan
- **Metode pengadaan otomatis** berdasarkan nilai:
  - ≤ Rp 15 juta → Pembelian Langsung
  - ≤ Rp 600 juta → Tender Terbatas  
  - > Rp 600 juta → Tender Umum
  - E-Purchasing & Swakelola dipilih manual
- Jadwal per bulan (Januari - Desember)
- Cetak tabel jadwal & export Excel

### ✅ Realisasi Kegiatan
- **Pilih multi rencana** dalam satu nomor kontrak
- **Tambah item baru** di luar rencana (ad-hoc)
- Edit detail item (volume, nilai bisa berbeda dari rencana)
- Metode pengadaan auto berdasarkan total semua item
- Tanggal selesai opsional (untuk barang tidak perlu)
- Status: Proses / Selesai / Batal

### 📊 Laporan
- Perbandingan rencana vs realisasi
- Selisih biaya & persentase serapan
- Grafik batang & donut interaktif (Chart.js)
- Filter per tahun & jenis pengadaan
- Export Excel & cetak PDF

### 🔐 Keamanan
- Login/logout dengan session
- Password di-hash dengan bcrypt
- Role-based access (admin/user)

---

## 🛠️ Tech Stack
- **Backend**: PHP Native (tanpa framework)
- **Database**: MySQL via MySQLi
- **Frontend**: Bootstrap 5
- **Tabel**: DataTables
- **Grafik**: Chart.js
- **Icons**: Bootstrap Icons

---

## ❓ Troubleshooting

**Login gagal:**
- Pastikan database sudah diimport
- Cek konfigurasi di `config/database.php`

**Halaman blank:**
- Aktifkan `display_errors` di `php.ini`
- Cek error log XAMPP

**Gambar/style tidak muncul:**
- Pastikan `BASE_URL` di `config/app.php` sesuai dengan nama folder project
- Default: `/procurement` (jika folder bernama `procurement` di htdocs)
