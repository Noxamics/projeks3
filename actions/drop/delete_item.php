<?php
// File: /actions/drop/delete_item.php
// Handler untuk delete single item dari pesanan (API terpisah)

include('../../db.php');
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
$drop_id = isset($_POST['drop_id']) ? intval($_POST['drop_id']) : 0;

if ($item_id <= 0 || $drop_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

try {
    $conn->begin_transaction();

    // count items
    $stmt_count = $conn->prepare("SELECT COUNT(*) as item_count FROM drop_items WHERE drop_id = ?");
    $stmt_count->bind_param("i", $drop_id);
    $stmt_count->execute();
    $item_count = intval($stmt_count->get_result()->fetch_assoc()['item_count'] ?? 0);
    $stmt_count->close();

    if ($item_count <= 1) {
        // delete payments
        $stmt_p = $conn->prepare("DELETE FROM payments WHERE drop_id = ?");
        $stmt_p->bind_param("i", $drop_id);
        $stmt_p->execute();
        $stmt_p->close();

        // delete deadlines
        $stmt_dead = $conn->prepare("DELETE FROM deadlines WHERE drop_id = ?");
        $stmt_dead->bind_param("i", $drop_id);
        $stmt_dead->execute();
        $stmt_dead->close();

        // delete items
        $stmt_items = $conn->prepare("DELETE FROM drop_items WHERE drop_id = ?");
        $stmt_items->bind_param("i", $drop_id);
        $stmt_items->execute();
        $stmt_items->close();

        // delete drop
        $stmt_drop = $conn->prepare("DELETE FROM drops WHERE id_drop = ?");
        $stmt_drop->bind_param("i", $drop_id);
        $stmt_drop->execute();
        $stmt_drop->close();

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Item terakhir dihapus — pesanan juga dihapus.',
            'last_item' => true,
            'drop_id' => $drop_id
        ]);
        exit;
    }

    // delete the item
    $stmt_del = $conn->prepare("DELETE FROM drop_items WHERE id_item = ? AND drop_id = ?");
    $stmt_del->bind_param("ii", $item_id, $drop_id);
    $stmt_del->execute();
    $affected = $stmt_del->affected_rows;
    $stmt_del->close();

    if ($affected === 0)
        throw new Exception("Item tidak ditemukan atau sudah dihapus");

    // reindex
    $stmt_re = $conn->prepare("SELECT id_item FROM drop_items WHERE drop_id = ? ORDER BY item_order ASC");
    $stmt_re->bind_param("i", $drop_id);
    $stmt_re->execute();
    $res_re = $stmt_re->get_result();
    $order = 1;
    $stmt_update = $conn->prepare("UPDATE drop_items SET item_order = ? WHERE id_item = ?");
    while ($r = $res_re->fetch_assoc()) {
        $stmt_update->bind_param("ii", $order, $r['id_item']);
        $stmt_update->execute();
        $order++;
    }
    $stmt_re->close();
    $stmt_update->close();

    // new total
    $stmt_total = $conn->prepare("SELECT SUM(price) as total FROM drop_items WHERE drop_id = ?");
    $stmt_total->bind_param("i", $drop_id);
    $stmt_total->execute();
    $new_total = floatval($stmt_total->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt_total->close();

    // estimate
    $stmt_est = $conn->prepare("
        SELECT MAX(s.duration) as max_duration, d.trans_date
        FROM drop_items di
        JOIN services s ON di.service_id = s.id_service
        JOIN drops d ON di.drop_id = d.id_drop
        WHERE di.drop_id = ?
        GROUP BY d.trans_date
    ");
    $stmt_est->bind_param("i", $drop_id);
    $stmt_est->execute();
    $res_est = $stmt_est->get_result();
    $max_duration = 0;
    $new_est_date = null;
    if ($res_est->num_rows > 0) {
        $row_est = $res_est->fetch_assoc();
        $max_duration = intval($row_est['max_duration'] ?? 0);
        $trans_date = $row_est['trans_date'];
        $date = new DateTime($trans_date);
        $date->modify("+{$max_duration} days");
        $new_est_date = $date->format('Y-m-d');
    }
    $stmt_est->close();

    // update drops
    $stmt_up = $conn->prepare("UPDATE drops SET total_amount = ?, est_finish_date = ? WHERE id_drop = ?");
    $stmt_up->bind_param("dsi", $new_total, $new_est_date, $drop_id);
    $stmt_up->execute();
    $stmt_up->close();

    // update deadlines
    if ($new_est_date !== null) {
        $stmt_dead = $conn->prepare("UPDATE deadlines SET deadline_date = ? WHERE drop_id = ?");
        $stmt_dead->bind_param("si", $new_est_date, $drop_id);
        $stmt_dead->execute();
        $stmt_dead->close();
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Item berhasil dihapus',
        'last_item' => false,
        'drop_id' => $drop_id,
        'new_total' => $new_total,
        'remaining_items' => $item_count - 1,
        'new_estimate_date' => $new_est_date,
        'max_duration' => $max_duration
    ]);
    exit;

} catch (Exception $e) {
    $conn->rollback();
    error_log("DELETE ITEM ERROR (actions): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
