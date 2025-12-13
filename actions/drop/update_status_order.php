<?php
/**
 * update_status_order.php
 * Mencatat karyawan yang melakukan aktivitas pada pesanan
 * Disesuaikan dengan struktur database drops dan drop_items
 */

require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Ambil data dari request
$dropId = isset($_POST['drop_id']) ? intval($_POST['drop_id']) : 0;
$itemId = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
$statusId = isset($_POST['status_id']) ? intval($_POST['status_id']) : 0;
$employeeId = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;

if ($dropId <= 0 || $statusId <= 0 || $employeeId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

try {
    // Mapping status_id ke tracking field
    // 1 = Barang Baru Masuk -> received_by
    // 2 = Menunggu Antrian -> (tidak ada tracking khusus)
    // 3 = Sedang Dikerjakan -> processed_by
    // 5 = Barang Siap Diambil -> packed_by
    // 6 = Barang Telah Diambil -> released_by

    $trackingField = null;
    $timeField = null;

    switch ($statusId) {
        case 1: // Barang Baru Masuk
            $trackingField = 'received_by';
            $timeField = 'received_at';
            break;

        case 3: // Sedang Dikerjakan
            $trackingField = 'processed_by';
            $timeField = 'processed_at';
            break;

        case 5: // Barang Siap Diambil
            $trackingField = 'packed_by';
            $timeField = 'packed_at';
            break;

        case 6: // Barang Telah Diambil
            $trackingField = 'released_by';
            $timeField = 'released_at';
            break;
    }

    if ($trackingField && $timeField) {
        // Update tracking info di tabel drops
        $stmt = $conn->prepare("UPDATE drops SET $trackingField = ?, $timeField = NOW() WHERE id_drop = ?");
        $stmt->bind_param("ii", $employeeId, $dropId);

        if (!$stmt->execute()) {
            throw new Exception('Gagal update tracking di drops');
        }
        $stmt->close();
    }

    // Update status di drop_items jika item_id ada
    if ($itemId > 0) {
        $stmtItem = $conn->prepare("UPDATE drop_items SET status_id = ? WHERE id_item = ?");
        $stmtItem->bind_param("ii", $statusId, $itemId);

        if (!$stmtItem->execute()) {
            throw new Exception('Gagal update status item');
        }
        $stmtItem->close();
    } else {
        // Update semua items dalam drop ini jika tidak ada item spesifik
        $stmtAllItems = $conn->prepare("UPDATE drop_items SET status_id = ? WHERE drop_id = ?");
        $stmtAllItems->bind_param("ii", $statusId, $dropId);

        if (!$stmtAllItems->execute()) {
            throw new Exception('Gagal update status semua items');
        }
        $stmtAllItems->close();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Tracking berhasil diupdate',
        'tracking_field' => $trackingField,
        'employee_id' => $employeeId,
        'drop_id' => $dropId,
        'status_id' => $statusId
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>