<?php
// File: /actions/drop/auto_update_status_queue.php
// Auto-update status dari "Barang Baru Masuk" ke "Menunggu Antrian" setelah 1 hari

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error_log');

// Include database connection
$db_path = __DIR__ . '/../../db.php';
if (!file_exists($db_path)) {
    error_log("AUTO_STATUS: db.php not found");
    exit;
}

require_once $db_path;

if (!isset($conn) || !$conn) {
    error_log("AUTO_STATUS: Database connection failed");
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Get status IDs
    $status_query = "
        SELECT id_status, status_name 
        FROM statuses 
        WHERE status_name IN ('Barang Baru Masuk', 'Menunggu Antrian')
    ";

    $result = $conn->query($status_query);
    $statuses = [];

    while ($row = $result->fetch_assoc()) {
        $statuses[$row['status_name']] = $row['id_status'];
    }

    if (!isset($statuses['Barang Baru Masuk']) || !isset($statuses['Menunggu Antrian'])) {
        throw new Exception("Status 'Barang Baru Masuk' atau 'Menunggu Antrian' tidak ditemukan");
    }

    $barang_baru_id = $statuses['Barang Baru Masuk'];
    $menunggu_antrian_id = $statuses['Menunggu Antrian'];

    // Find items with "Barang Baru Masuk" status older than 1 day
    $query = "
        SELECT 
            di.id_item,
            di.drop_id,
            d.trans_date,
            DATEDIFF(CURDATE(), d.trans_date) as days_old
        FROM drop_items di
        INNER JOIN drops d ON di.drop_id = d.id_drop
        WHERE di.status_id = ?
        AND DATEDIFF(CURDATE(), d.trans_date) > 1
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $barang_baru_id);
    $stmt->execute();
    $items = $stmt->get_result();
    $stmt->close();

    $updated_count = 0;
    $item_ids = [];
    $drop_ids = [];

    // Collect items to update
    while ($item = $items->fetch_assoc()) {
        $item_ids[] = $item['id_item'];
        $drop_ids[] = $item['drop_id'];
    }

    if (count($item_ids) > 0) {
        // Update drop_items status
        $placeholders = implode(',', array_fill(0, count($item_ids), '?'));
        $update_query = "
            UPDATE drop_items 
            SET status_id = ? 
            WHERE id_item IN ($placeholders)
        ";

        $stmt = $conn->prepare($update_query);
        if (!$stmt) {
            throw new Exception("Prepare update failed: " . $conn->error);
        }

        // Bind parameters
        $types = str_repeat('i', count($item_ids) + 1);
        $params = array_merge([$menunggu_antrian_id], $item_ids);
        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            throw new Exception("Execute update failed: " . $stmt->error);
        }

        $updated_count = $stmt->affected_rows;
        $stmt->close();

        // Update deadlines for affected drops
        if (count($drop_ids) > 0) {
            $unique_drops = array_unique($drop_ids);
            $drop_placeholders = implode(',', array_fill(0, count($unique_drops), '?'));

            $deadline_query = "
                UPDATE deadlines 
                SET status_id = ? 
                WHERE drop_id IN ($drop_placeholders)
            ";

            $stmt = $conn->prepare($deadline_query);
            if ($stmt) {
                $types = str_repeat('i', count($unique_drops) + 1);
                $params = array_merge([$menunggu_antrian_id], $unique_drops);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // Commit transaction
    $conn->commit();

    // Log success
    $log_message = sprintf(
        "[%s] AUTO_STATUS: Successfully updated %d items from 'Barang Baru Masuk' to 'Menunggu Antrian'",
        date('Y-m-d H:i:s'),
        $updated_count
    );
    error_log($log_message);

    echo json_encode([
        'success' => true,
        'updated_count' => $updated_count,
        'message' => "Auto-update completed: {$updated_count} items updated"
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }

    error_log("AUTO_STATUS ERROR: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
?>