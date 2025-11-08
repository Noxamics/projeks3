<?php
include("../db.php");

$data = json_decode(file_get_contents("php://input"), true);

$ids = $data['ids'] ?? [];

if (count($ids) === 0) {
    echo json_encode(['success' => false]);
    exit;
}

$idList = implode(",", array_map('intval', $ids));

$sql = "DELETE FROM drops WHERE id_drop IN ($idList)";
$result = $conn->query($sql);

echo json_encode(['success' => $result]);
?>