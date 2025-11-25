<?php
// projeks3/actions/karyawan/edit.php
include('../../db.php');

$id = intval($_POST['id_employee'] ?? 0);
$employee_code = trim($_POST['employee_code'] ?? '');
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$join_date = $_POST['join_date'] ?? null;
$status = $_POST['status'] ?? 'Aktif';

if (!$id || !$employee_code || !$name || !$phone) {
    echo "error:missing";
    exit;
}

// duplicate check excluding current
$chk = $conn->prepare("SELECT COUNT(*) FROM employees WHERE (employee_code = ? OR phone = ?) AND id_employee <> ?");
$chk->bind_param("ssi", $employee_code, $phone, $id);
$chk->execute();
$chk->bind_result($cnt);
$chk->fetch();
$chk->close();
if ($cnt > 0) {
    echo "error:duplicate";
    exit;
}

// handle photo upload
$photoName = null;
if (!empty($_FILES['photo']['name'])) {
    $upDir = __DIR__ . '/../../uploads/employee/';
    if (!is_dir($upDir))
        mkdir($upDir, 0755, true);
    $tmp = $_FILES['photo']['tmp_name'];
    $orig = basename($_FILES['photo']['name']);
    $photoName = time() . '_' . preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $orig);
    move_uploaded_file($tmp, $upDir . $photoName);

    // delete old
    $oldQ = $conn->prepare("SELECT photo FROM employees WHERE id_employee = ?");
    $oldQ->bind_param("i", $id);
    $oldQ->execute();
    $oldQ->bind_result($oldPhoto);
    $oldQ->fetch();
    $oldQ->close();
    if ($oldPhoto) {
        $oldPath = __DIR__ . '/../../uploads/employee/' . $oldPhoto;
        if (file_exists($oldPath))
            @unlink($oldPath);
    }
}

// build update query
if (!empty($password)) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    if ($photoName) {
        $stmt = $conn->prepare("UPDATE employees SET employee_code=?, name=?, phone=?, password=?, join_date=?, status=?, photo=? WHERE id_employee=?");
        $stmt->bind_param("sssssssi", $employee_code, $name, $phone, $hash, $join_date, $status, $photoName, $id);
    } else {
        $stmt = $conn->prepare("UPDATE employees SET employee_code=?, name=?, phone=?, password=?, join_date=?, status=? WHERE id_employee=?");
        $stmt->bind_param("ssssssi", $employee_code, $name, $phone, $hash, $join_date, $status, $id);
    }
} else {
    if ($photoName) {
        $stmt = $conn->prepare("UPDATE employees SET employee_code=?, name=?, phone=?, join_date=?, status=?, photo=? WHERE id_employee=?");
        $stmt->bind_param("ssssssi", $employee_code, $name, $phone, $join_date, $status, $photoName, $id);
    } else {
        $stmt = $conn->prepare("UPDATE employees SET employee_code=?, name=?, phone=?, join_date=?, status=? WHERE id_employee=?");
        $stmt->bind_param("sssssi", $employee_code, $name, $phone, $join_date, $status, $id);
    }
}

$ok = $stmt->execute();
$stmt->close();

echo $ok ? "success" : "error";
