<?php
include('../db.php');
header('Content-Type: application/json');

$keyword = trim($_GET['keyword'] ?? '');

if (strlen($keyword) < 2) {
    echo json_encode([]);
    exit;
}

$keyword = $conn->real_escape_string($keyword);

$query = "
    SELECT id_customer, name AS nama, phone AS no_hp 
    FROM customers 
    WHERE name LIKE '%$keyword%' OR phone LIKE '%$keyword%'
    ORDER BY name ASC
    LIMIT 10
";

$result = $conn->query($query);
$customers = [];

while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}

echo json_encode($customers);
?>