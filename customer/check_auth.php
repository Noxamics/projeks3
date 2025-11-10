<?php
// customer/check_auth.php - Middleware autentikasi untuk customer
require_once '../db.php';

// Fungsi untuk cek apakah user sudah login
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

// Fungsi untuk cek apakah user adalah customer
function isCustomer()
{
    return isLoggedIn() && $_SESSION['user_type'] === 'customer';
}

// Fungsi untuk require customer (redirect jika bukan customer)
function requireCustomer()
{
    if (!isLoggedIn()) {
        header("Location: ../login/login.php?error=unauthorized");
        exit();
    }

    if ($_SESSION['user_type'] !== 'customer') {
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
        'phone' => $_SESSION['user_phone'] ?? '',
        'login_time' => $_SESSION['login_time'] ?? null
    ];
}

// Panggil requireCustomer() untuk proteksi halaman
requireCustomer();
?>