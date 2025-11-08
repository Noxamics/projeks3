<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_drop'])) {
    $id = intval($_POST['id_drop']);
    $query = $conn->prepare("DELETE FROM drops WHERE id_drop = ?");
    $query->bind_param("i", $id);

    if ($query->execute()) {
        echo "Data berhasil dihapus.";
    } else {
        echo "Gagal menghapus data: " . $conn->error;
    }

    $query->close();
    $conn->close();
} else {
    echo "Permintaan tidak valid.";
}
?>