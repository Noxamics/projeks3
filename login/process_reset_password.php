<?php
session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi session
    if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
        header("Location: forgot_password.php?error=unauthorized");
        exit();
    }

    if (!isset($_SESSION['forgot_password'])) {
        header("Location: forgot_password.php?error=session_expired");
        exit();
    }

    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    $customer_id = $_SESSION['forgot_password']['customer_id'];
    $phone = $_SESSION['forgot_password']['phone'];

    // Validasi input
    if (empty($new_password) || empty($confirm_password)) {
        header("Location: reset_password.php?error=empty");
        exit();
    }

    // Cek panjang password
    if (strlen($new_password) < 6) {
        header("Location: reset_password.php?error=weak_password");
        exit();
    }

    // Cek kecocokan password
    if ($new_password !== $confirm_password) {
        header("Location: reset_password.php?error=password_mismatch");
        exit();
    }

    // Hash password baru
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password di database
    $stmt = $conn->prepare("UPDATE customers SET password = ?, otp_code = NULL, otp_expires_at = NULL, updated_at = CURRENT_TIMESTAMP WHERE id_customer = ?");
    $stmt->bind_param("si", $hashed_password, $customer_id);

    if ($stmt->execute()) {
        // Hapus semua session
        unset($_SESSION['forgot_password']);
        unset($_SESSION['otp_verified']);

        // Log success
        error_log("Password berhasil direset untuk customer ID: {$customer_id}");

        // Redirect ke login dengan pesan sukses
        header("Location: login.php?success=password_reset");
        exit();
    } else {
        // Gagal update
        error_log("Gagal update password untuk customer ID: {$customer_id}");
        header("Location: reset_password.php?error=database");
        exit();
    }

} else {
    header("Location: reset_password.php");
    exit();
}
?>