<?php
include('../db.php');
date_default_timezone_set('Asia/Jakarta');

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

$id = intval($_POST['id_employee'] ?? 0);
if ($id <= 0) {
    echo json_encode(['error' => 'ID karyawan tidak valid']);
    exit;
}

$today = date('Y-m-d');
$now = date('H:i:s');

// 🔍 Cek apakah sudah absen hari ini
$cek = $conn->prepare("SELECT * FROM attendances WHERE employee_id = ? AND attendance_date = ?");
$cek->bind_param("is", $id, $today);
$cek->execute();
$data = $cek->get_result()->fetch_assoc();

if (!$data) {
    // ✅ CHECK IN
    $stmt = $conn->prepare("INSERT INTO attendances (employee_id, attendance_date, check_in, status) VALUES (?, ?, ?, 'Hadir')");
    $stmt->bind_param("iss", $id, $today, $now);
    $stmt->execute();

    // 🔄 Update status ke “Aktif”
    $update = $conn->prepare("UPDATE employees SET status = 'Aktif' WHERE id_employee = ?");
    $update->bind_param("i", $id);
    $update->execute();

    echo json_encode(['status' => 'checkin']);
} elseif ($data && !$data['check_out']) {
    // ✅ CHECK OUT
    $check_in = new DateTime($data['check_in']);
    $check_out = new DateTime($now);
    $diff = $check_in->diff($check_out);
    $hours = $diff->h + ($diff->i / 60);

    $stmt = $conn->prepare("UPDATE attendances SET check_out=?, work_hours=? WHERE id_attendance=?");
    $stmt->bind_param("sdi", $now, $hours, $data['id_attendance']);
    $stmt->execute();

    // 🔄 Update status ke “Non-Aktif”
    $update = $conn->prepare("UPDATE employees SET status = 'Non-Aktif' WHERE id_employee = ?");
    $update->bind_param("i", $id);
    $update->execute();

    echo json_encode(['status' => 'checkout', 'hours' => round($hours, 2)]);
} else {
    echo json_encode(['status' => 'done']);
}
?>