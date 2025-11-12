<?php
include('../db.php');

$id = intval($_POST['id_employee'] ?? 0);

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM employees WHERE id_employee = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: ../admin/karyawan.php");
exit;
?>