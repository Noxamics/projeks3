<?php
// FILE: get_order_detail.php
include('../db.php');
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $drop_id = intval($_POST['drop_id'] ?? 0);

    if ($drop_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Drop ID tidak valid'
        ]);
        exit;
    }

    try {
        // ===== AMBIL DETAIL PESANAN =====
        $stmt = $conn->prepare("
            SELECT 
                d.id_drop,
                d.brand,
                d.trans_date,
                d.est_finish_date,
                d.status_id,
                d.service_id,
                d.employee_id,
                d.note,
                d.total_amount,
                s.service_name,
                s.price_min,
                s.duration,
                p.amount_paid,
                p.payment_method,
                p.payment_date,
                p.status as payment_status
            FROM drops d
            JOIN services s ON d.service_id = s.id_service
            LEFT JOIN payments p ON d.id_drop = p.drop_id
            WHERE d.id_drop = ?
        ");
        
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        
        $stmt->bind_param("i", $drop_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Pesanan tidak ditemukan");
        }
        
        $order = $result->fetch_assoc();
        $stmt->close();

        echo json_encode([
            'success' => true,
            'order' => [
                'id_drop' => $order['id_drop'],
                'brand' => $order['brand'],
                'trans_date' => $order['trans_date'],
                'est_finish_date' => $order['est_finish_date'],
                'status_id' => $order['status_id'],
                'service_id' => $order['service_id'],
                'service_name' => $order['service_name'],
                'price_min' => floatval($order['price_min']),
                'duration' => intval($order['duration']),
                'employee_id' => $order['employee_id'],
                'note' => $order['note'] ?? '',
                'total_amount' => floatval($order['total_amount']),
                'amount_paid' => floatval($order['amount_paid'] ?? 0),
                'payment_method' => $order['payment_method'] ?? 'Tunai',
                'payment_date' => $order['payment_date'] ?? '',
                'payment_status' => $order['payment_status'] ?? 'Belum Lunas'
            ]
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
?>