<?php
// File: /actions/drop/drop_update_status.php
// Handler untuk update status pesanan

include('../../db.php');
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_drop = isset($_POST['id_drop']) ? intval($_POST['id_drop']) : 0;
    $status_id = isset($_POST['status_id']) ? intval($_POST['status_id']) : 0;

    // Validasi input
    if ($id_drop <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'ID Drop tidak valid'
        ]);
        exit;
    }

    if ($status_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Status ID tidak valid'
        ]);
        exit;
    }

    // Mulai transaksi
    $conn->begin_transaction();

    try {
        // 1. Cek apakah drop_id ada
        $stmt_check = $conn->prepare("SELECT id_drop FROM drops WHERE id_drop = ?");
        if (!$stmt_check) {
            throw new Exception("Prepare check failed: " . $conn->error);
        }
        $stmt_check->bind_param("i", $id_drop);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows === 0) {
            throw new Exception("Data order tidak ditemukan");
        }
        $stmt_check->close();

        // 2. Update status di tabel drops
        $stmt_drop = $conn->prepare("UPDATE drops SET status_id = ? WHERE id_drop = ?");
        if (!$stmt_drop) {
            throw new Exception("Prepare update drops failed: " . $conn->error);
        }
        $stmt_drop->bind_param("ii", $status_id, $id_drop);
        if (!$stmt_drop->execute()) {
            throw new Exception("Update drops failed: " . $stmt_drop->error);
        }
        $stmt_drop->close();

        // 3. Update status di tabel deadlines
        $stmt_deadline = $conn->prepare("UPDATE deadlines SET status_id = ? WHERE drop_id = ?");
        if (!$stmt_deadline) {
            throw new Exception("Prepare update deadlines failed: " . $conn->error);
        }
        $stmt_deadline->bind_param("ii", $status_id, $id_drop);
        if (!$stmt_deadline->execute()) {
            throw new Exception("Update deadlines failed: " . $stmt_deadline->error);
        }
        $stmt_deadline->close();

        // 4. Jika status adalah "Barang Telah Diambil" (id_status = 6), set actual_finish_date
        if ($status_id == 6) {
            $today = date('Y-m-d');
            $stmt_finish = $conn->prepare("UPDATE drops SET actual_finish_date = ? WHERE id_drop = ?");
            if (!$stmt_finish) {
                throw new Exception("Prepare update finish date failed: " . $conn->error);
            }
            $stmt_finish->bind_param("si", $today, $id_drop);
            if (!$stmt_finish->execute()) {
                throw new Exception("Update finish date failed: " . $stmt_finish->error);
            }
            $stmt_finish->close();
        }

        // Commit transaksi
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => '✅ Status berhasil diubah'
        ]);

    } catch (Exception $e) {
        // Rollback jika terjadi error
        $conn->rollback();

        // Log error untuk debugging
        error_log("UPDATE STATUS ERROR: " . $e->getMessage());

        echo json_encode([
            'success' => false,
            'message' => 'Gagal mengubah status: ' . $e->getMessage()
        ]);
    }

    $conn->close();

} else {
    echo json_encode([
        'success' => false,
        'message' => 'Method request tidak valid'
    ]);
}