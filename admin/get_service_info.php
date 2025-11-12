<?php
include('../db.php');
header('Content-Type: application/json');

$id = intval($_GET['id_service'] ?? 0);

if ($id <= 0) {
    echo json_encode(['error' => 'ID service tidak valid']);
    exit;
}

$stmt = $conn->prepare("
    SELECT price_min, price_max, duration 
    FROM services 
    WHERE id_service = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode($data);
} else {
    echo json_encode(['error' => 'Data layanan tidak ditemukan']);
}

$stmt->close();
?>