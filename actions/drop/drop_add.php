<?php
// File: /actions/drop/drop_add.php
// Handle adding new drop order with multiple items - FIXED VERSION

// ===== SETUP =====
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error.log');

session_start();

// Set header JSON SEBELUM output apapun
header('Content-Type: application/json; charset=utf-8');

// ===== INCLUDE DB CONNECTION =====
$db_path = __DIR__ . '/../../db.php';
if (!file_exists($db_path)) {
    echo json_encode([
        'success' => false,
        'message' => 'File db.php tidak ditemukan di: ' . $db_path
    ]);
    exit;
}

require_once $db_path;

// ===== CHECK CONNECTION =====
if (!isset($conn) || !$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database gagal'
    ]);
    exit;
}

// ===== CHECK LOGIN (OPTIONAL - comment jika tidak pakai login) =====
// if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Unauthorized access - Please login first'
//     ]);
//     exit;
// }

// ===== VALIDATE REQUEST METHOD =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

try {
    // ===== VALIDATE REQUIRED FIELDS =====
    if (empty($_POST['customer_name'])) {
        throw new Exception('Nama pelanggan wajib diisi');
    }

    if (empty($_POST['phone_number'])) {
        throw new Exception('Nomor HP wajib diisi');
    }

    if (empty($_POST['items']) || !is_array($_POST['items'])) {
        throw new Exception('Minimal harus ada 1 item pesanan');
    }

    if (empty($_POST['employee_id']) || intval($_POST['employee_id']) <= 0) {
        throw new Exception('Karyawan harus dipilih');
    }

    // Start transaction
    $conn->begin_transaction();

    // === 1. CHECK OR CREATE CUSTOMER ===
    $customer_name = trim($_POST['customer_name']);
    $phone_number = trim($_POST['phone_number']);

    // Check if customer exists by phone
    $stmt = $conn->prepare("SELECT id_customer FROM customers WHERE phone = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("s", $phone_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Customer exists
        $customer = $result->fetch_assoc();
        $customer_id = $customer['id_customer'];
        $stmt->close();

        // Update name if different
        $stmt = $conn->prepare("UPDATE customers SET name = ? WHERE id_customer = ?");
        $stmt->bind_param("si", $customer_name, $customer_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt->close();

        // Create new customer
        $stmt = $conn->prepare("INSERT INTO customers (name, phone) VALUES (?, ?)");
        $stmt->bind_param("ss", $customer_name, $phone_number);
        $stmt->execute();
        $customer_id = $conn->insert_id;
        $stmt->close();

        if (!$customer_id) {
            throw new Exception("Gagal membuat customer baru");
        }
    }

    // === 2. GET DATA FROM FORM ===
    $employee_id = intval($_POST['employee_id']);
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $payment_status = $_POST['payment_status'] ?? 'Belum Lunas';
    $payment_method = $_POST['payment_method'] ?? 'Tunai';
    $amount_paid = floatval($_POST['amount_paid'] ?? 0);
    $payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : NULL;
    $note = trim($_POST['note'] ?? '');
    $items = $_POST['items'];

    // Get first item data
    $first_item = reset($items);
    $trans_date = $first_item['trans_date'] ?? date('Y-m-d');
    $first_service_id = intval($first_item['service_id'] ?? 0);
    $first_brand = trim($first_item['brand'] ?? '');

    // Calculate max duration
    $max_duration = 0;
    foreach ($items as $item) {
        $duration = intval($item['duration'] ?? 0);
        if ($duration > $max_duration) {
            $max_duration = $duration;
        }
    }

    // Calculate est_finish_date
    $est_finish_date = NULL;
    if ($max_duration > 0) {
        $date = new DateTime($trans_date);
        $date->modify("+{$max_duration} days");
        $est_finish_date = $date->format('Y-m-d');
    }

    // Generate unique order code with timestamp to prevent duplicates
    $date_code = date('ym');
    $timestamp = time();
    $random = mt_rand(100, 999);

    // Try to get max number for nice sequential codes
    $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(order_code, 8) AS UNSIGNED)) as max_num FROM drops WHERE order_code LIKE ?");
    $pattern = "ORD{$date_code}-%";
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $next_num = ($row['max_num'] ?? 0) + 1;
    $stmt->close();

    // Create order code with fallback to timestamp if duplicate
    $order_code = sprintf("ORD%s-%04d", $date_code, $next_num);

    // Check if order code already exists
    $stmt = $conn->prepare("SELECT id_drop FROM drops WHERE order_code = ?");
    $stmt->bind_param("s", $order_code);
    $stmt->execute();
    $check_result = $stmt->get_result();

    // If duplicate, use timestamp-based code
    if ($check_result->num_rows > 0) {
        $order_code = sprintf("ORD%s-%d%03d", $date_code, $timestamp % 100000, $random);
    }
    $stmt->close();

    // === 3. INSERT DROP ORDER ===
    $total_items = count($items);

    $stmt = $conn->prepare("
        INSERT INTO drops (
            order_code,
            customer_id, 
            employee_id,
            service_id,
            brand,
            trans_date,
            est_finish_date,
            total_amount,
            total_items,
            note,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param(
        "siiissssis",
        $order_code,
        $customer_id,
        $employee_id,
        $first_service_id,
        $first_brand,
        $trans_date,
        $est_finish_date,
        $total_amount,
        $total_items,
        $note
    );

    if (!$stmt->execute()) {
        throw new Exception("Gagal menyimpan pesanan: " . $stmt->error);
    }

    $drop_id = $conn->insert_id;
    $stmt->close();

    if (!$drop_id) {
        throw new Exception('Gagal mendapatkan ID pesanan');
    }

    // === 4. INSERT ITEMS ===
    $item_order = 1;

    foreach ($items as $item) {
        $brand = trim($item['brand']);
        $service_id = intval($item['service_id']);
        $price = floatval($item['price'] ?? 0);
        $status_id = intval($item['status_id']);
        $item_notes = trim($item['notes'] ?? '');

        $stmt = $conn->prepare("
            INSERT INTO drop_items (
                drop_id,
                brand,
                service_id,
                price,
                status_id,
                notes,
                item_order,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param(
            "isidisi",
            $drop_id,
            $brand,
            $service_id,
            $price,
            $status_id,
            $item_notes,
            $item_order
        );

        if (!$stmt->execute()) {
            throw new Exception("Gagal menyimpan item: " . $stmt->error);
        }

        $stmt->close();
        $item_order++;
    }

    // === 5. INSERT PAYMENT ===
    $stmt = $conn->prepare("
        INSERT INTO payments (
            drop_id,
            amount_paid,
            payment_method,
            payment_date,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param(
        "idsss",
        $drop_id,
        $amount_paid,
        $payment_method,
        $payment_date,
        $payment_status
    );

    $stmt->execute();
    $stmt->close();

    // === 6. INSERT DEADLINE ===
    if ($est_finish_date) {
        // Get first item status
        $first_status_id = intval($first_item['status_id']);

        $stmt = $conn->prepare("
            INSERT INTO deadlines (
                drop_id,
                deadline_date,
                status_id,
                created_at
            ) VALUES (?, ?, ?, NOW())
        ");

        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param("isi", $drop_id, $est_finish_date, $first_status_id);
        $stmt->execute();
        $stmt->close();
    }

    // === 7. COMMIT TRANSACTION ===
    $conn->commit();

    // === 8. SUCCESS RESPONSE ===
    echo json_encode([
        'success' => true,
        'message' => "Pesanan berhasil ditambahkan",
        'drop_id' => $drop_id,
        'order_code' => $order_code,
        'customer_id' => $customer_id,
        'item_count' => count($items)
    ]);

} catch (Exception $e) {
    // Rollback on error
    if (isset($conn)) {
        $conn->rollback();
    }

    // Log error
    error_log("DROP_ADD ERROR: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>