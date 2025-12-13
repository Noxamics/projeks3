<?php
// File: /admin/check_auth.php

// Cek apakah user sudah login dan merupakan admin
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
//     header("Location: ../login/login.php?error=unauthorized");
//     exit();
// }

// // Fungsi untuk mendapatkan data admin yang sedang login
// function getAdminData()
// {
//     global $conn;

//     if (!isset($_SESSION['user_id'])) {
//         return null;
//     }

//     $admin_id = $_SESSION['user_id'];

//     // ✅ PERBAIKAN: Gunakan full_name, bukan name
//     $stmt = $conn->prepare("SELECT id_admin, full_name as name, email FROM admin WHERE id_admin = ?");

//     if (!$stmt) {
//         error_log("getAdminData prepare failed: " . $conn->error);
//         return null;
//     }

//     $stmt->bind_param("i", $admin_id);
//     $stmt->execute();
//     $result = $stmt->get_result();

//     if ($result->num_rows > 0) {
//         return $result->fetch_assoc();
//     }

//     // Jika data tidak ditemukan, hapus session dan redirect
//     session_destroy();
//     header("Location: ../login/login.php?error=session_expired");
//     exit();
// }

// // Fungsi untuk cek apakah admin masih valid
// function validateAdminSession()
// {
//     global $conn;

//     if (!isset($_SESSION['user_id'])) {
//         return false;
//     }

//     $admin_id = $_SESSION['user_id'];

//     $stmt = $conn->prepare("SELECT id_admin FROM admin WHERE id_admin = ?");

//     if (!$stmt) {
//         error_log("validateAdminSession prepare failed: " . $conn->error);
//         return false;
//     }

//     $stmt->bind_param("i", $admin_id);
//     $stmt->execute();
//     $result = $stmt->get_result();

//     if ($result->num_rows === 0) {
//         session_destroy();
//         return false;
//     }

//     return true;
// }

// // Validasi session admin
// if (!validateAdminSession()) {
//     header("Location: ../login/login.php?error=session_invalid");
//     exit();
// }
?>