<?php
include('../db.php');
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['ids']) || !is_array($data['ids'])) {
    echo json_encode(["success" => false, "message" => "Data ID tidak valid"]);
    exit;
}

$ids = array_map('intval', $data['ids']);
$idList = implode(',', $ids);

// Ambil customer_id terkait drop yang akan dihapus
$getCustomers = $conn->query("SELECT DISTINCT customer_id FROM drops WHERE id_drop IN ($idList)");
$customerIds = [];

while ($row = $getCustomers->fetch_assoc()) {
    $customerIds[] = $row['customer_id'];
}

// Hapus dari tabel drops
$deleteDrops = $conn->query("DELETE FROM drops WHERE id_drop IN ($idList)");

if ($deleteDrops) {
    // Hapus customer terkait (jika tidak digunakan di drop lain)
    foreach ($customerIds as $cid) {
        $check = $conn->query("SELECT COUNT(*) AS total FROM drops WHERE customer_id = $cid");
        $result = $check->fetch_assoc();
        if ($result['total'] == 0) {
            $conn->query("DELETE FROM customers WHERE id_customer = $cid");
        }
    }

    echo json_encode(["success" => true, "message" => "Data dan customer terkait berhasil dihapus"]);
} else {
    echo json_encode(["success" => false, "message" => "Gagal menghapus data drops"]);
}
?>