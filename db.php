<?php
// File: db.php
// Konfigurasi Database untuk Hosting

// ===== KREDENSIAL DATABASE HOSTING =====
$host = 'localhost';
$username = 'mifmyho2_sengkuclean';
$password = 'MIF@2025';
$database = 'mifmyho2_sengkuclean';

// ===== TIMEZONE & ERROR REPORTING =====
date_default_timezone_set('Asia/Jakarta');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

// ===== START SESSION (jika belum dimulai) =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== MEMBUAT KONEKSI =====
try {
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        error_log("Database Connection Failed: " . $conn->connect_error);
        die("Maaf, terjadi kesalahan koneksi database. Silakan coba lagi nanti.");
    }

    $conn->set_charset("utf8mb4");

} catch (Exception $e) {
    error_log("Database Exception: " . $e->getMessage());
    die("Maaf, terjadi kesalahan sistem. Silakan hubungi administrator.");
}

// ===== FUNGSI HELPER (dengan function_exists check) =====

// ✅ Cek apakah fungsi sudah ada sebelum mendeklarasikan
if (!function_exists('escape_string')) {
    function escape_string($value)
    {
        global $conn;
        return $conn->real_escape_string($value);
    }
}

if (!function_exists('close_connection')) {
    function close_connection()
    {
        global $conn;
        if ($conn) {
            $conn->close();
        }
    }
}