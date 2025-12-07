<?php
// File: /admin/logout.php
// Fungsi: Logout admin dan hapus session

session_start();

// Hapus semua session variables
$_SESSION = array();

// Hapus session cookie jika ada
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy session
session_destroy();

// Redirect ke halaman login dengan pesan sukses
header("Location: ../login/login.php?success=logout");
exit();
?>