<?php
include('../db.php'); // sesuaikan path kalau beda folder

header('Content-Type: application/json; charset=utf-8');

$keyword = $_GET['keyword'] ?? '';

if (strlen($keyword) < 2) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT id_customer, name AS nama, phone AS no_hp 
        FROM customers 
        WHERE name LIKE ? OR phone LIKE ?
        LIMIT 10";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["error" => "Query gagal: " . $conn->error]);
    exit;
}

$param = "%$keyword%";
$stmt->bind_param("ss", $param, $param);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
