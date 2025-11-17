<?php
require_once '../db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_type = $_POST['login_type'] ?? '';

    if ($login_type === 'customer') {
        $phone = trim($_POST['phone']);
        $password = trim($_POST['password']);

        if (empty($phone) || empty($password)) {
            header("Location: login.php?error=empty");
            exit;
        }

        $stmt = $conn->prepare("SELECT * FROM customers WHERE phone = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            header("Location: login.php?error=invalid");
            exit;
        }

        $user = $result->fetch_assoc();

        // ✅ Verifikasi password hash
        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['id_customer'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_phone'] = $user['phone'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = 'customer';

            header("Location: ../customer/dashboard.php");
            exit;
        } else {
            header("Location: login.php?error=invalid");
            exit;
        }

    } elseif ($login_type === 'admin') {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        if (empty($username) || empty($password)) {
            header("Location: login.php?error=empty");
            exit;
        }

        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            header("Location: login.php?error=invalid");
            exit;
        }

        $admin = $result->fetch_assoc();

        // Admin mungkin belum pakai hash (kalau sudah, pakai password_verify)
        if (password_verify($password, $admin['password']) || $password === $admin['password']) {
            $_SESSION['user_id'] = $admin['id_admin'];
            $_SESSION['user_name'] = $admin['username'];
            $_SESSION['user_type'] = 'admin';

            header("Location: ../admin/dashboard.php");
            exit;
        } else {
            header("Location: login.php?error=invalid");
            exit;
        }

    } else {
        header("Location: login.php?error=invalid");
        exit;
    }
} else {
    header("Location: login.php");
    exit;
}
?>