<?php
include('../db.php');
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$drop_id = intval($data['drop_id'] ?? 0);
$payment_status = $data['payment_status'] ?? '';
$payment_date = $data['payment_date'] ?? null;

if ($drop_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid drop ID']);
    exit;
}

$sql = "UPDATE payments SET status = ?, payment_date = ? WHERE drop_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $payment_status, $payment_date, $drop_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}