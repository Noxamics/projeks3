<?php
// File: db.php
// Database Configuration for Hosting Environment
// Version: 2.0 - Clean & Optimized

// ==================== DATABASE CREDENTIALS ====================
$host = 'localhost';
$username = 'mifmyho2_sengkuclean';
$password = 'MIF@2025';
$database = 'mifmyho2_sengkuclean';

// ==================== ENVIRONMENT SETUP ====================
date_default_timezone_set('Asia/Jakarta');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors from users
ini_set('log_errors', 1);     // Log errors for debugging
ini_set('error_log', __DIR__ . '/error_log.txt');

// ==================== SESSION MANAGEMENT ====================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================== DATABASE CONNECTION ====================
try {
    // Create connection
    $conn = new mysqli($host, $username, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        error_log("Database Connection Failed: " . $conn->connect_error);
        throw new Exception("Database connection failed");
    }

    // Set charset to UTF-8
    $conn->set_charset("utf8mb4");

    // Log successful connection (optional - comment out in production)
    // error_log("Database connected successfully at " . date('Y-m-d H:i:s'));

} catch (Exception $e) {
    error_log("Database Exception: " . $e->getMessage());

    // User-friendly error message
    die("Maaf, terjadi kesalahan koneksi database. Silakan coba lagi nanti.");
}

// ==================== HELPER FUNCTIONS ====================

if (!function_exists('escape_string')) {
    /**
     * Escape string untuk mencegah SQL injection
     * @param string $value - String yang akan di-escape
     * @return string - String yang sudah aman
     */
    function escape_string($value)
    {
        global $conn;
        return $conn->real_escape_string($value);
    }
}

if (!function_exists('close_connection')) {
    /**
     * Tutup koneksi database
     */
    function close_connection()
    {
        global $conn;
        if ($conn && $conn instanceof mysqli) {
            $conn->close();
        }
    }
}

if (!function_exists('check_connection')) {
    /**
     * Cek status koneksi database
     * @return bool - true jika terkoneksi, false jika tidak
     */
    function check_connection()
    {
        global $conn;
        return $conn && $conn instanceof mysqli && $conn->ping();
    }
}

if (!function_exists('get_last_insert_id')) {
    /**
     * Dapatkan ID terakhir yang di-insert
     * @return int - Last insert ID
     */
    function get_last_insert_id()
    {
        global $conn;
        return $conn->insert_id;
    }
}

if (!function_exists('execute_query')) {
    /**
     * Execute query dengan error handling
     * @param string $query - SQL query
     * @return mysqli_result|bool - Result atau false jika error
     */
    function execute_query($query)
    {
        global $conn;
        $result = $conn->query($query);

        if (!$result) {
            error_log("Query Error: " . $conn->error . " | Query: " . $query);
            return false;
        }

        return $result;
    }
}

// ==================== CONFIGURATION COMPLETE ====================
// Connection is ready to use via global $conn variable