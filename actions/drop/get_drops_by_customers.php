<?php
// File: /actions/drop/get_drops_by_customers.php
// Helper untuk mendapatkan semua drop_id dari customer_ids (untuk cetak & delete)

include('../../db.php');
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_ids = isset($_POST['customer_ids']) ? $_POST['customer_ids'] : '';

    if (empty($customer_ids)) {
        echo json_encode([
            'success' => false,
            'message' => 'Customer IDs tidak ditemukan'
        ]);
        exit;
    }

    try {
        // Parse customer IDs
        $ids_array = array_map('intval', explode(',', $customer_ids));
        $ids_array = array_filter($ids_array, function ($id) {
            return $id > 0; });

        if (empty($ids_array)) {
            throw new Exception("Tidak ada customer ID yang valid");
        }

        $id_list = implode(',', $ids_array);

        // ===== AMBIL SEMUA DROP_ID (HANYA YANG SEDANG BERJALAN) =====
        $stmt = $conn->prepare("
            SELECT id_drop 
            FROM drops 
            WHERE customer_id IN ($id_list) AND status_id != 6
            ORDER BY id_drop DESC
        ");

        if (!$stmt)
            throw new Exception("Prepare failed: " . $conn->error);

        $stmt->execute();
        $result = $stmt->get_result();

        $drop_ids = [];
        while ($row = $result->fetch_assoc()) {
            $drop_ids[] = $row['id_drop'];
        }
        $stmt->close();

        if (empty($drop_ids)) {
            throw new Exception("Tidak ada pesanan untuk customer yang dipilih");
        }

        echo json_encode([
            'success' => true,
            'drop_ids' => $drop_ids,
            'count' => count($drop_ids)
        ]);

    } catch (Exception $e) {
        error_log("ERROR: " . $e->getMessage());

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

    $conn->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}