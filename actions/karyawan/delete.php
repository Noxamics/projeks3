<?php
// projeks3/actions/karyawan/delete.php
include('../../db.php');

$id = intval($_POST['id_employee'] ?? 0);
if (!$id) {
    echo "error";
    exit;
}

// get photo and delete file
$q = $conn->prepare("SELECT photo FROM employees WHERE id_employee = ?");
$q->bind_param("i", $id);
$q->execute();
$q->bind_result($photo);
$q->fetch();
$q->close();

if ($photo) {
    $path = __DIR__ . '/../../uploads/employee/' . $photo;
    if (file_exists($path))
        @unlink($path);
}

$stmt = $conn->prepare("DELETE FROM employees WHERE id_employee = ?");
$stmt->bind_param("i", $id);
$ok = $stmt->execute();
$stmt->close();

echo $ok ? "success" : "error";
