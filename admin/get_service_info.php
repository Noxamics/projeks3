<?php
include('../db.php');
header('Content-Type: application/json; charset=utf-8');

$id_service = $_GET['id_service'] ?? '';

if (!$id_service) {
    echo json_encode(["error" => "ID layanan tidak dikirim"]);
    exit;
}

$sql = "SELECT price_min, price_max, duration FROM services WHERE id_service = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_service);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Format harga biar lebih rapi (misal: Rp50.000 - Rp80.000)
    $row['formatted_price'] = "Rp" . number_format($row['price_min'], 0, ',', '.') . " - Rp" . number_format($row['price_max'], 0, ',', '.');
    echo json_encode($row);
} else {
    echo json_encode(["error" => "Data tidak ditemukan"]);
}
