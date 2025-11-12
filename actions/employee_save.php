<?php
include('../db.php');

$id = intval($_POST['id_employee'] ?? 0);
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$status = $_POST['status'] ?? 'Aktif';
$join_date = $_POST['join_date'] ?? date('Y-m-d');
$password = $_POST['password'] ?? '';
$photo = $_FILES['photo']['name'] ?? null;

// Upload foto jika ada
if ($photo) {
    $target_dir = "../uploads/employee/";
    $target_file = $target_dir . basename($photo);
    move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);
}

// === MODE EDIT ===
if ($id > 0) {
    // update data utama
    $sql = "UPDATE employees SET name=?, phone=?, status=?, join_date=?";
    $params = [$name, $phone, $status, $join_date];
    $types = "ssss";

    if ($photo) {
        $sql .= ", photo=?";
        $params[] = $photo;
        $types .= "s";
    }

    // jika password diisi → update juga
    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $sql .= ", password=?";
        $params[] = $hashed;
        $types .= "s";
    }

    $sql .= " WHERE id_employee=?";
    $params[] = $id;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

} else {
    // === MODE TAMBAH ===
    // Buat employee_code otomatis
    $last = $conn->query("SELECT employee_code FROM employees ORDER BY id_employee DESC LIMIT 1");
    if ($last->num_rows > 0) {
        $lastCode = $last->fetch_assoc()['employee_code'];
        $num = intval(substr($lastCode, 3)) + 1;
        $employee_code = 'EMP' . str_pad($num, 3, '0', STR_PAD_LEFT);
    } else {
        $employee_code = 'EMP001';
    }

    // Hash password dari input
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Insert data baru
    $stmt = $conn->prepare("
        INSERT INTO employees (employee_code, name, phone, status, join_date, photo, password) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssssss", $employee_code, $name, $phone, $status, $join_date, $photo, $hashed);
    $stmt->execute();
}

header("Location: ../admin/karyawan.php");
exit;
?>