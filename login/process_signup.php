<?php
require_once '../db.php';

// Validasi input
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: signup.php");
    exit;
}

// Ambil data dari form signup
$name = trim($_POST['name']);
$phone = trim($_POST['phone']);
$email = !empty($_POST['email']) ? trim($_POST['email']) : null;
$password = trim($_POST['password']);

// Validasi field wajib
if (empty($name) || empty($phone) || empty($password)) {
    header("Location: signup.php?error=empty");
    exit;
}

// Validasi format nomor HP (10-13 digit)
if (!preg_match('/^[0-9]{10,13}$/', $phone)) {
    header("Location: signup.php?error=invalid_phone");
    exit;
}

// Validasi email jika diisi
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: signup.php?error=invalid_email");
    exit;
}

$now = date('Y-m-d H:i:s');

// Cek apakah nomor HP sudah terdaftar
$stmt = $conn->prepare("SELECT id_customer, password FROM customers WHERE phone = ?");
$stmt->bind_param("s", $phone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Jika nomor sudah ada
    $existing = $result->fetch_assoc();

    if (!empty($existing['password'])) {
        // Sudah punya password = sudah terdaftar sepenuhnya
        header("Location: signup.php?error=exists");
        exit;
    } else {
        // Customer sudah ada tapi belum punya password (dibuat via kasir)
        // Update data customer dengan password
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
?>