<?php
// File: /actions/drop/drop_delete.php
// Hapus beberapa drops berdasarkan ids (API terpisah)

include('../../db.php');
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method request tidak valid']);
    exit;
}

$ids = isset($_POST['ids']) ? $_POST['ids'] : null;
if (empty($ids)) {
    $input = json_decode(file_get_contents('php://input'), true);
    $ids = $input['ids'] ?? null;
}

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'ID tidak ditemukan atau tidak valid']);
    exit;
}

if (is_string($ids)) {
    $idsArray = array_map('intval', explode(',', $ids));
} else if (is_array($ids)) {
    $idsArray = array_map('intval', $ids);
} else {
    echo json_encode(['success' => false, 'message' => 'Format ID tidak valid']);
    exit;
}

$idsArray = array_filter($idsArray, function ($id) {
    return $id > 0; });

if (empty($idsArray)) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada ID valid untuk dihapus']);
    exit;
}

$conn->begin_transaction();

try {
    $deletedCount = 0;
    $totalItemsDeleted = 0;

    foreach ($idsArray as $id) {
        // Hitung items
        $stmt_cnt = $conn->prepare("SELECT COUNT(*) as item_count FROM drop_items WHERE drop_id = ?");
        $stmt_cnt->bind_param("i", $id);
        $stmt_cnt->execute();
        $cnt = intval($stmt_cnt->get_result()->fetch_assoc()['item_count'] ?? 0);
        $stmt_cnt->close();

        // delete payments
        $stmt_p = $conn->prepare("DELETE FROM payments WHERE drop_id = ?");
        $stmt_p->bind_param("i", $id);
        $stmt_p->execute();
        $stmt_p->close();

        // delete deadlines
        $stmt_dead = $conn->prepare("DELETE FROM deadlines WHERE drop_id = ?");
        $stmt_dead->bind_param("i", $id);
        $stmt_dead->execute();
        $stmt_dead->close();

        // delete items
        $stmt_items = $conn->prepare("DELETE FROM drop_items WHERE drop_id = ?");
        $stmt_items->bind_param("i", $id);
        $stmt_items->execute();
        $stmt_items->close();

        // delete drop
        $stmt_drop = $conn->prepare("DELETE FROM drops WHERE id_drop = ?");
        $stmt_drop->bind_param("i", $id);
        $stmt_drop->execute();
        if ($stmt_drop->affected_rows > 0)
            $deletedCount++;
        $stmt_drop->close();

        $totalItemsDeleted += $cnt;
    }

    $conn->commit();

    $message = "Berhasil menghapus {$deletedCount} pesanan";
    if ($totalItemsDeleted > 0)
        $message .= " dengan total {$totalItemsDeleted} item";

    echo json_encode([
        'success' => true,
        'message' => $message,
        'deleted_orders' => $deletedCount,
        'deleted_items' => $totalItemsDeleted
    ]);
    exit;

} catch (Exception $e) {
    $conn->rollback();
    error_log("DELETE ERROR (actions/drop/drop_delete.php): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus data: ' . $e->getMessage()]);
    exit;
}
