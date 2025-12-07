<?php
// File: /actions/drop/get_item.php
// Ambil detail item untuk edit

include('../../db.php');
header('Content-Type: application/json');

$item_id = intval($_POST['item_id'] ?? 0);
$drop_id = intval($_POST['drop_id'] ?? 0);

if ($item_id <= 0 || $drop_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

$stmt = $conn->prepare("
    SELECT di.id_item, di.drop_id, di.brand, di.service_id, di.price, di.notes
    FROM drop_items di
    WHERE di.id_item = ? AND di.drop_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $item_id, $drop_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan']);
    exit;
}

$item = $result->fetch_assoc();
$stmt->close();

echo json_encode([
    'success' => true,
    'id_item' => $item['id_item'],
    'drop_id' => $item['drop_id'],
    'brand' => $item['brand'],
    'service_id' => $item['service_id'],
    'price' => floatval($item['price']),
    'notes' => $item['notes'] ?? ''
]);
exit;