<?php
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_drop = intval($_POST['id_drop']);
    $status_id = intval($_POST['status_id']);

    if ($id_drop > 0 && $status_id > 0) {
        $query = $conn->prepare("UPDATE drops SET status_id = ? WHERE id_drop = ?");
        $query->bind_param("ii", $status_id, $id_drop);

        if ($query->execute()) {
            echo json_encode(['success' => true, 'message' => 'Status berhasil diperbarui!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status.']);
        }
        $query->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
}
?>