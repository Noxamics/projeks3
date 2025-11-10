<?php
// admin/check_auth.php - Middleware autentikasi untuk admin
require_once '../db.php';

// Fungsi untuk cek apakah user sudah login
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

// Fungsi untuk cek apakah user adalah admin
function isAdmin()
{
    return isLoggedIn() && $_SESSION['user_type'] === 'admin';
}

// Fungsi untuk require admin (redirect jika bukan admin)
function requireAdmin()
{
    if (!isLoggedIn()) {
        header("Location: ../login/login.php?error=unauthorized");
        exit();
    }

    if ($_SESSION['user_type'] !== 'admin') {
        header("Location: ../login/login.php?error=unauthorized");
        exit();
    }
}

// Fungsi untuk get user data
function getUserData()
{
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'type' => $_SESSION['user_type'],
        'name' => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'username' => $_SESSION['username'] ?? '',
        'login_time' => $_SESSION['login_time'] ?? null
    ];
}

// Panggil requireAdmin() untuk proteksi halaman
requireAdmin();
?>