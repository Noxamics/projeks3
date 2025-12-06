<?php
// File: /actions/drop/get_customer_orders.php
// Get all orders by customer ID

include('../../db.php');
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = intval($_POST['customer_id'] ?? 0);

    if ($customer_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Customer ID tidak valid'
        ]);
        exit;
    }

    try {
        // ===== AMBIL DATA CUSTOMER =====
        $stmt_cust = $conn->prepare("SELECT name, phone FROM customers WHERE id_customer = ?");
        if (!$stmt_cust)
            throw new Exception("Prepare customer failed: " . $conn->error);

        $stmt_cust->bind_param("i", $customer_id);
        $stmt_cust->execute();
        $result_cust = $stmt_cust->get_result();

        if ($result_cust->num_rows === 0) {
            throw new Exception("Customer tidak ditemukan");
        }

        $customer = $result_cust->fetch_assoc();
        $stmt_cust->close();

        // ===== AMBIL SEMUA PESANAN CUSTOMER =====
        $stmt_orders = $conn->prepare("
            SELECT 
                d.id_drop,
                d.order_code,
                d.brand,
                d.trans_date,
                d.est_finish_date,
                d.status_id,
                d.service_id,
                d.employee_id,
                d.note,
                d.total_amount,
                s.service_name,
                p.amount_paid,
                p.payment_method,
                p.payment_date,
                p.status as payment_status
            FROM drops d
            JOIN services s ON d.service_id = s.id_service
            LEFT JOIN payments p ON d.id_drop = p.drop_id
            WHERE d.customer_id = ?
            ORDER BY d.trans_date DESC
        ");

        if (!$stmt_orders)
            throw new Exception("Prepare orders failed: " . $conn->error);

        $stmt_orders->bind_param("i", $customer_id);
        $stmt_orders->execute();
        $result_orders = $stmt_orders->get_result();

        $orders = [];
        while ($row = $result_orders->fetch_assoc()) {
            $orders[] = [
                'id_drop' => $row['id_drop'],
                'order_code' => $row['order_code'],
                'brand' => $row['brand'],
                'trans_date' => $row['trans_date'],
                'est_finish_date' => $row['est_finish_date'],
                'status_id' => $row['status_id'],
                'service_name' => $row['service_name'],
                'total_amount' => floatval($row['total_amount']),
                'amount_paid' => floatval($row['amount_paid'] ?? 0),
                'payment_method' => $row['payment_method'] ?? '-',
                'payment_date' => $row['payment_date'] ?? '-',
                'payment_status' => $row['payment_status'] ?? 'Belum Lunas',
                'employee_id' => $row['employee_id'],
                'service_id' => $row['service_id'],
                'note' => $row['note']
            ];
        }
        $stmt_orders->close();

        // ===== AMBIL DAFTAR STATUS =====
        $stmt_status = $conn->prepare("SELECT id_status, status_name FROM statuses ORDER BY id_status ASC");
        if (!$stmt_status)
            throw new Exception("Prepare status failed: " . $conn->error);

        $stmt_status->execute();
        $result_status = $stmt_status->get_result();

        $statuses = [];
        while ($row = $result_status->fetch_assoc()) {
            $statuses[] = [
                'id_status' => $row['id_status'],
                'status_name' => $row['status_name']
            ];
        }
        $stmt_status->close();

        echo json_encode([
            'success' => true,
            'phone' => $customer['phone'],
            'orders' => $orders,
            'statuses' => $statuses
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