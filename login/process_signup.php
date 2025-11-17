<?php
require_once '../db.php';

// Ambil data dari form signup
$name = trim($_POST['name']);
$phone = trim($_POST['phone']);
$email = trim($_POST['email']);
$password = trim($_POST['password']);
$now = date('Y-m-d H:i:s');

// Cek apakah nomor HP sudah terdaftar
$stmt = $conn->prepare("SELECT id_customer, password FROM customers WHERE phone = ?");
$stmt->bind_param("s", $phone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Jika nomor sudah ada, cek apakah password sudah diisi sebelumnya
    $existing = $result->fetch_assoc();

    if (!empty($existing['password'])) {
        // Sudah pernah signup → tolak
        header("Location: signup.php?error=exists");
        exit;
    } else {
        // Belum punya password → update data customer
        $update = $conn->prepare("UPDATE customers SET name=?, email=?, password=?, updated_at=? WHERE id_customer=?");
        $update->bind_param("ssssi", $name, $email, $password, $now, $existing['id_customer']);

        if ($update->execute()) {
            header("Location: login.php?success=registered");
            exit;
        } else {
            header("Location: signup.php?error=failed");
            exit;
        }
    }
} else {
    // Nomor baru → insert customer baru
    $insert = $conn->prepare("INSERT INTO customers (name, phone, email, password, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
    $insert->bind_param("ssssss", $name, $phone, $email, $password, $now, $now);

    if ($insert->execute()) {
        header("Location: login.php?success=registered");
        exit;
    } else {
        header("Location: signup.php?error=failed");
        exit;
    }
}
