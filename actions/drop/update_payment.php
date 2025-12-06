<?php
include('../db.php');
header('Content-Type: application/json');

// Ambil data JSON dari request
$data = json_decode(file_get_contents('php://input'), true);

$drop_id = intval($data['drop_id'] ?? 0);
$payment_status = $data['payment_status'] ?? '';
$payment_date = $data['payment_date'] ?? null;

// Validasi input
if ($drop_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid drop ID']);
    exit;
}

if (empty($payment_status)) {
    echo json_encode(['success' => false, 'message' => 'Status pembayaran tidak boleh kosong']);
    exit;
}

try {
    $conn->begin_transaction();

    // Cek apakah payment sudah ada
    $stmt_check = $conn->prepare("SELECT id_payment FROM payments WHERE drop_id = ?");
    $stmt_check->bind_param("i", $drop_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        // UPDATE payment yang sudah ada
        if ($payment_date === null) {
            $sql = "UPDATE payments SET status = ?, payment_date = NULL WHERE drop_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $payment_status, $drop_id);
        } else {
            $sql = "UPDATE payments SET status = ?, payment_date = ? WHERE drop_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $payment_status, $payment_date, $drop_id);
        }
    } else {
        // INSERT payment baru
        if ($payment_date === null) {
            $sql = "INSERT INTO payments (drop_id, amount_paid, payment_method, status, payment_date) 
                    VALUES (?, 0, 'Tunai', ?, NULL)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $drop_id, $payment_status);
        } else {
            $sql = "INSERT INTO payments (drop_id, amount_paid, payment_method, status, payment_date) 
                    VALUES (?, 0, 'Tunai', ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $drop_id, $payment_status, $payment_date);
        }
    }

    if ($stmt->execute()) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Status pembayaran berhasil diupdate']);
    } else {
        throw new Exception($stmt->error);
    }

    $stmt->close();
    $stmt_check->close();

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>