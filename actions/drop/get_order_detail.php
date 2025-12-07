<?php
// File: /actions/drop/get_order_detail.php
// Get full order details untuk edit modal (multi-item support)
// FIXED: status_id sekarang ada di drop_items, bukan di drops

include('../../db.php');
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

// Support both GET and POST
$drop_id = 0;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $drop_id = intval($_GET['drop_id'] ?? 0);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $drop_id = intval($_POST['drop_id'] ?? 0);
}

if ($drop_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Drop ID tidak valid'
    ]);
    exit;
}

try {
    // ===========================================
    // 1. GET DROP DATA (TANPA status_id)
    // ===========================================

    $stmt_drop = $conn->prepare("
        SELECT 
            d.id_drop,
            d.order_code,
            d.customer_id,
            d.employee_id,
            d.note,
            d.trans_date,
            d.est_finish_date,
            d.total_amount,
            c.name as customer_name,
            c.phone as customer_phone,
            e.name as employee_name
        FROM drops d
        JOIN customers c ON d.customer_id = c.id_customer
        LEFT JOIN employees e ON d.employee_id = e.id_employee
        WHERE d.id_drop = ?
    ");

    if (!$stmt_drop) {
        throw new Exception("Prepare failed (get drop): " . $conn->error);
    }

    $stmt_drop->bind_param("i", $drop_id);
    $stmt_drop->execute();
    $result_drop = $stmt_drop->get_result();

    if ($result_drop->num_rows === 0) {
        throw new Exception("Pesanan tidak ditemukan");
    }

    $drop_data = $result_drop->fetch_assoc();
    $stmt_drop->close();

    // ===========================================
    // 2. GET ITEMS (Multi-item) - DENGAN status_id per item
    // ===========================================

    $stmt_items = $conn->prepare("
        SELECT 
            di.id_item as item_id,
            di.service_id,
            di.brand,
            di.price,
            di.quantity,
            di.item_order,
            di.notes,
            di.status_id,
            s.duration,
            s.service_name,
            s.category,
            s.price_min
        FROM drop_items di
        JOIN services s ON di.service_id = s.id_service
        WHERE di.drop_id = ?
        ORDER BY di.item_order ASC
    ");

    if (!$stmt_items) {
        throw new Exception("Prepare failed (get items): " . $conn->error);
    }

    $stmt_items->bind_param("i", $drop_id);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();

    $items = [];
    $total_calculated = 0;
    $first_status_id = 1; // Default status jika tidak ada items

    if ($result_items->num_rows > 0) {
        // Multi-item order
        $idx = 0;
        while ($row = $result_items->fetch_assoc()) {
            // Ambil status dari item pertama untuk compatibility
            if ($idx === 0) {
                $first_status_id = intval($row['status_id']);
            }

            $items[] = [
                'item_id' => intval($row['item_id']),
                'service_id' => intval($row['service_id']),
                'brand' => $row['brand'],
                'price' => floatval($row['price']),
                'duration' => intval($row['duration']),
                'notes' => $row['notes'] ?? '',
                'service_name' => ucfirst($row['category']) . " - " . ucfirst($row['service_name']),
                'trans_date' => $drop_data['trans_date'],
                'est_finish_date' => $drop_data['est_finish_date'],
                'status_id' => intval($row['status_id']),
                'quantity' => intval($row['quantity'])
            ];
            $total_calculated += floatval($row['price']);
            $idx++;
        }
    } else {
        // Fallback: Jika tidak ada items, buat dari data drops (backward compatibility)
        $stmt_service = $conn->prepare("
            SELECT 
                s.id_service,
                s.service_name,
                s.category,
                s.price_min,
                s.duration
            FROM services s
            WHERE s.id_service = (SELECT service_id FROM drops WHERE id_drop = ?)
        ");

        if ($stmt_service) {
            $stmt_service->bind_param("i", $drop_id);
            $stmt_service->execute();
            $result_service = $stmt_service->get_result();

            if ($result_service->num_rows > 0) {
                $service = $result_service->fetch_assoc();

                // Get brand from drops table
                $stmt_brand = $conn->prepare("SELECT brand FROM drops WHERE id_drop = ?");
                $stmt_brand->bind_param("i", $drop_id);
                $stmt_brand->execute();
                $result_brand = $stmt_brand->get_result();
                $brand_data = $result_brand->fetch_assoc();
                $stmt_brand->close();

                $items[] = [
                    'item_id' => 0, // 0 means this is from drops table, not drop_items
                    'service_id' => intval($service['id_service']),
                    'brand' => $brand_data['brand'] ?? '',
                    'price' => floatval($drop_data['total_amount']),
                    'duration' => intval($service['duration']),
                    'notes' => '',
                    'service_name' => ucfirst($service['category']) . " - " . ucfirst($service['service_name']),
                    'trans_date' => $drop_data['trans_date'],
                    'est_finish_date' => $drop_data['est_finish_date'],
                    'status_id' => 1, // Default status untuk backward compatibility
                    'quantity' => 1
                ];
                $total_calculated = floatval($drop_data['total_amount']);
            }
            $stmt_service->close();
        }
    }
    $stmt_items->close();

    // ===========================================
    // 3. GET PAYMENT DATA
    // ===========================================

    $stmt_payment = $conn->prepare("
        SELECT 
            status,
            payment_method,
            amount_paid,
            payment_date
        FROM payments
        WHERE drop_id = ?
    ");

    if (!$stmt_payment) {
        throw new Exception("Prepare failed (get payment): " . $conn->error);
    }

    $stmt_payment->bind_param("i", $drop_id);
    $stmt_payment->execute();
    $result_payment = $stmt_payment->get_result();

    $payment_data = [
        'status' => 'Belum Lunas',
        'method' => 'Tunai',
        'amount_paid' => 0,
        'date' => null
    ];

    if ($result_payment->num_rows > 0) {
        $payment = $result_payment->fetch_assoc();
        $payment_data = [
            'status' => $payment['status'] ?? 'Belum Lunas',
            'method' => $payment['payment_method'] ?? 'Tunai',
            'amount_paid' => floatval($payment['amount_paid'] ?? 0),
            'date' => $payment['payment_date']
        ];
    }
    $stmt_payment->close();

    // ===========================================
    // 4. BUILD RESPONSE
    // ===========================================

    echo json_encode([
        'success' => true,
        'drop_id' => intval($drop_data['id_drop']),
        'order_code' => $drop_data['order_code'],
        'customer' => [
            'id' => intval($drop_data['customer_id']),
            'name' => $drop_data['customer_name'],
            'phone' => $drop_data['customer_phone']
        ],
        'employee_id' => intval($drop_data['employee_id']),
        'employee_name' => $drop_data['employee_name'] ?? '',
        'note' => $drop_data['note'] ?? '',
        'trans_date' => $drop_data['trans_date'],
        'est_finish_date' => $drop_data['est_finish_date'],
        'status_id' => $first_status_id, // Status dari item pertama untuk compatibility
        'items' => $items,
        'payment' => $payment_data,
        'total_amount' => floatval($drop_data['total_amount']),
        'total_calculated' => $total_calculated,
        'item_count' => count($items)
    ]);

} catch (Exception $e) {
    error_log("GET DROP DETAIL ERROR: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();