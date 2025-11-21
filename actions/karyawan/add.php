<?php
// projeks3/actions/karyawan/add.php
include('../../db.php');

$employee_code = trim($_POST['employee_code'] ?? '');
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$join_date = $_POST['join_date'] ?? null;
$status = $_POST['status'] ?? 'Aktif';

if (!$employee_code || !$name || !$phone || !$password) {
    echo "error:missing";
    exit;
}

// duplicate check
$chk = $conn->prepare("SELECT COUNT(*) FROM employees WHERE employee_code = ? OR phone = ?");
$chk->bind_param("ss", $employee_code, $phone);
$chk->execute();
$chk->bind_result($cnt);
$chk->fetch();
$chk->close();
if ($cnt > 0) {
    echo "error:duplicate";
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

// photo
$photoName = null;
if (!empty($_FILES['photo']['name'])) {
    $upDir = __DIR__ . '/../../uploads/employee/';
    if (!is_dir($upDir))
        mkdir($upDir, 0755, true);
    $tmp = $_FILES['photo']['tmp_name'];
    $orig = basename($_FILES['photo']['name']);
    $photoName = time() . '_' . preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $orig);
    move_uploaded_file($tmp, $upDir . $photoName);
}

$stmt = $conn->prepare("INSERT INTO employees (employee_code, name, phone, password, join_date, status, photo) VALUES (?,?,?,?,?,?,?)");
$stmt->bind_param("sssssss", $employee_code, $name, $phone, $hash, $join_date, $status, $photoName);
$ok = $stmt->execute();
$stmt->close();

echo $ok ? "success" : "error";
