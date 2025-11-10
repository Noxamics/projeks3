<?php
// Path yang benar untuk struktur: admin/drop/get_service_info.php -> ../../db.php
include('../db.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Log untuk debugging
error_log("get_service_info.php called");

if (!isset($_GET['id_service'])) {
    echo json_encode(['error' => 'ID service tidak ditemukan']);
    exit;
}

$id_service = intval($_GET['id_service']);
error_log("Service ID requested: " . $id_service);

// Cek koneksi database
if (!$conn) {
    echo json_encode(['error' => 'Koneksi database gagal']);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        id_service,
        service_name,
        category,
        price_min,
        price_max,
        duration
    FROM services 
    WHERE id_service = ?
");

if (!$stmt) {
    echo json_encode(['error' => 'Query error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $id_service);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    error_log("Data found: " . json_encode($data));
    echo json_encode($data);
} else {
    error_log("No data found for service ID: " . $id_service);
    echo json_encode(['error' => 'Service tidak ditemukan']);
}

$stmt->close();
$conn->close();
?>