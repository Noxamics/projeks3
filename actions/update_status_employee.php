<?php
include('../db.php');

$id = intval($_POST['id_employee'] ?? 0);
$status = $_POST['status'] ?? '';

if ($id > 0 && in_array($status, ['Aktif', 'Cuti'])) {
    $stmt = $conn->prepare("UPDATE employees SET status = ? WHERE id_employee = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>