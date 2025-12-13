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

        // Query customer berdasarkan nomor HP
        $stmt = $conn->prepare("SELECT * FROM customers WHERE phone = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            header("Location: login.php?error=invalid");
            exit;
        }

        $user = $result->fetch_assoc();

        // Cek apakah user sudah punya password
        if (empty($user['password'])) {
            header("Location: login.php?error=no_password");
            exit;
        }

        // Verifikasi password - SUPPORT BOTH PLAIN TEXT DAN BCRYPT HASH
        $password_valid = false;

        // Cek apakah password adalah hash bcrypt (dimulai dengan $2y$ atau $2a$)
        if (strpos($user['password'], '$2y$') === 0 || strpos($user['password'], '$2a$') === 0) {
            // Password tersimpan sebagai hash bcrypt - gunakan password_verify
            $password_valid = password_verify($password, $user['password']);
            error_log("Customer login - Hash verification for phone: {$phone}");
        } else {
            // Password tersimpan sebagai plain text - gunakan perbandingan langsung
            $password_valid = ($password === $user['password']);
            error_log("Customer login - Plain text verification for phone: {$phone}");
        }

        if ($password_valid) {
            $_SESSION['user_id'] = $user['id_customer'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_phone'] = $user['phone'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = 'customer';

            error_log("Customer login SUCCESS - Phone: {$phone}, ID: {$user['id_customer']}");

            header("Location: ../customer/dashboard.php");
            exit;
        } else {
            error_log("Customer login FAILED - Wrong password for phone: {$phone}");
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

        // Verifikasi password hash untuk admin (menggunakan bcrypt)
        if (password_verify($password, $admin['password'])) {
            $_SESSION['user_id'] = $admin['id_admin'];
            $_SESSION['user_name'] = $admin['full_name'];
            $_SESSION['user_email'] = $admin['email'];
            $_SESSION['user_type'] = 'admin';

            error_log("Admin login SUCCESS - Username: {$username}, ID: {$admin['id_admin']}");

            header("Location: ../admin/dashboard.php");
            exit;
        } else {
            error_log("Admin login FAILED - Wrong password for username: {$username}");
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