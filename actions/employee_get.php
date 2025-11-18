<?php
include('../db.php');

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

$result = $conn->query("SELECT * FROM employees WHERE id_employee = $id");

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode(['success' => true, 'employee' => $data]);
} else {
    echo json_encode(['success' => false]);
}
?>