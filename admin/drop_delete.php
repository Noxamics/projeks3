<?php
include('../db.php');
header('Content-Type: application/json');

// Error reporting - TIDAK DIUBAH
error_reporting(E_ALL);
ini_set('display_errors', 0); // Matikan display_errors untuk JSON response

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari request - TIDAK DIUBAH
    $ids = isset($_POST['ids']) ? $_POST['ids'] : '';

    if (empty($ids)) {
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = isset($input['ids']) ? $input['ids'] : '';
    }

    if (empty($ids)) {
        echo json_encode([
            'success' => false,
            'message' => 'ID tidak ditemukan atau tidak valid'
        ]);
        exit;
    }

    // Parse IDs - TIDAK DIUBAH
    if (is_string($ids)) {
        $idsArray = array_map('intval', explode(',', $ids));
    } else if (is_array($ids)) {
        $idsArray = array_map('intval', $ids);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Format ID tidak valid'
        ]);
        exit;
    }

    $idsArray = array_filter($idsArray, function ($id) {
        return $id > 0;
    });

    if (empty($idsArray)) {
        echo json_encode([
            'success' => false,
            'message' => 'Tidak ada ID valid untuk dihapus'
        ]);
        exit;
    }

    // Mulai transaksi - TIDAK DIUBAH
    $conn->begin_transaction();

    try {
        $deletedCount = 0;
        $totalItemsDeleted = 0; // NEW: track total items yang dihapus

        foreach ($idsArray as $id) {
            // NEW: Hitung jumlah items sebelum hapus (untuk reporting)
            $stmt_count = $conn->prepare("SELECT COUNT(*) as item_count FROM drop_items WHERE drop_id = ?");
            if (!$stmt_count) {
                throw new Exception("Prepare count items failed: " . $conn->error);
            }
            $stmt_count->bind_param("i", $id);
            $stmt_count->execute();
            $count_result = $stmt_count->get_result()->fetch_assoc();
            $item_count = $count_result['item_count'];
            $stmt_count->close();

            // 1. Hapus data terkait dari tabel 'payments' - TIDAK DIUBAH
            $stmt_payment = $conn->prepare("DELETE FROM payments WHERE drop_id = ?");
            if (!$stmt_payment) {
                throw new Exception("Prepare payments failed: " . $conn->error);
            }
            $stmt_payment->bind_param("i", $id);
            if (!$stmt_payment->execute()) {
                throw new Exception("Delete payments failed for ID {$id}: " . $stmt_payment->error);
            }
            $stmt_payment->close();

            // 2. Hapus data terkait dari tabel 'deadlines' - TIDAK DIUBAH
            $stmt_deadline = $conn->prepare("DELETE FROM deadlines WHERE drop_id = ?");
            if (!$stmt_deadline) {
                throw new Exception("Prepare deadlines failed: " . $conn->error);
            }
            $stmt_deadline->bind_param("i", $id);
            if (!$stmt_deadline->execute()) {
                throw new Exception("Delete deadlines failed for ID {$id}: " . $stmt_deadline->error);
            }
            $stmt_deadline->close();

            // 3. NEW: Hapus SEMUA ITEMS dari tabel 'drop_items'
            $stmt_items = $conn->prepare("DELETE FROM drop_items WHERE drop_id = ?");
            if (!$stmt_items) {
                throw new Exception("Prepare drop_items failed: " . $conn->error);
            }
            $stmt_items->bind_param("i", $id);
            if (!$stmt_items->execute()) {
                throw new Exception("Delete drop_items failed for ID {$id}: " . $stmt_items->error);
            }
            $totalItemsDeleted += $item_count; // NEW: tambahkan ke counter
            $stmt_items->close();

            // 4. Hapus data utama dari tabel 'drops' - TIDAK DIUBAH
            $stmt_drop = $conn->prepare("DELETE FROM drops WHERE id_drop = ?");
            if (!$stmt_drop) {
                throw new Exception("Prepare drops failed: " . $conn->error);
            }
            $stmt_drop->bind_param("i", $id);
            if (!$stmt_drop->execute()) {
                throw new Exception("Delete drops failed for ID {$id}: " . $stmt_drop->error);
            }

            if ($stmt_drop->affected_rows > 0) {
                $deletedCount++;
            }
            $stmt_drop->close();
        }

        // Commit transaksi - TIDAK DIUBAH
        $conn->commit();

        // NEW: Message yang lebih informatif
        $message = "Berhasil menghapus {$deletedCount} pesanan";
        if ($totalItemsDeleted > 0) {
            $message .= " dengan total {$totalItemsDeleted} item";
        }

        echo json_encode([
            'success' => true,
            'message' => $message,
            'deleted_orders' => $deletedCount,
            'deleted_items' => $totalItemsDeleted // NEW: info tambahan
        ]);

    } catch (Exception $e) {
        // Rollback jika terjadi error - TIDAK DIUBAH
        $conn->rollback();

        // Log error untuk debugging
        error_log("DELETE ERROR: " . $e->getMessage());

        echo json_encode([
            'success' => false,
            'message' => 'Gagal menghapus data: ' . $e->getMessage()
        ]);
    }

    $conn->close();

} else {
    echo json_encode([
        'success' => false,
        'message' => 'Method request tidak valid'
    ]);
}
?>