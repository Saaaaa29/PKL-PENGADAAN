<?php
/**
 * config/database.php
 * Konfigurasi koneksi database MySQL
 * Sesuaikan dengan setting XAMPP Anda
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // username MySQL default XAMPP
define('DB_PASS', '');            // password MySQL default XAMPP (kosong)
define('DB_NAME', 'procurement_db');
define('DB_CHARSET', 'utf8mb4');

/**
 * Membuat koneksi ke database menggunakan MySQLi
 * Mengembalikan object koneksi atau mati jika gagal
 */
function getDB() {
    static $conn = null; // singleton - koneksi dibuat sekali saja

    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            die("Koneksi database gagal: " . $conn->connect_error);
        }

        // Set charset agar karakter Indonesia tampil dengan benar
        $conn->set_charset(DB_CHARSET);
    }

    return $conn;
}
