<?php
// File: /actions/drop/drop_add.php
// Handle adding new drop order with multiple items - FIXED VERSION

// ===== CRITICAL: NO OUTPUT BEFORE THIS =====
ob_start();

// ===== SETUP =====
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error_log');

// ===== START SESSION =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== FUNCTION: Send JSON Response =====
function sendJsonResponse($data)
{
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== VALIDATE REQUEST METHOD =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

// ===== INCLUDE DB CONNECTION =====
$db_path = __DIR__ . '/../../db.php';

if (!file_exists($db_path)) {
    error_log("DROP_ADD ERROR: db.php not found at: " . $db_path);
    sendJsonResponse([
        'success' => false,
        'message' => 'File konfigurasi database tidak ditemukan'
    ]);
}

require_once $db_path;

// ===== VALIDATE DATABASE CONNECTION =====
if (!isset($conn)) {
    error_log("DROP_ADD ERROR: \$conn variable not set");
    sendJsonResponse([
        'success' => false,
        'message' => 'Variable koneksi database tidak terdefinisi'
    ]);
}

if (!$conn || !($conn instanceof mysqli)) {
    error_log("DROP_ADD ERROR: \$conn is not a valid mysqli object");
    sendJsonResponse([
        'success' => false,
        'message' => 'Koneksi database tidak valid'
    ]);
}

if ($conn->connect_error) {
    error_log("DROP_ADD ERROR: Database connection failed: " . $conn->connect_error);
    sendJsonResponse([
        'success' => false,
        'message' => 'Koneksi database gagal: ' . $conn->connect_error
    ]);
}

// ===== MAIN PROCESS =====
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

    if (!preg_match('/^[0-9+\-\s()]+$/', $phone_number)) {
        throw new Exception('Format nomor HP tidak valid');
    }

    $stmt = $conn->prepare("SELECT id_customer FROM customers WHERE phone = ?");
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }

    $stmt->bind_param("s", $phone_number);
    if (!$stmt->execute()) {
        throw new Exception("Database execute error: " . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $customer = $result->fetch_assoc();
        $customer_id = $customer['id_customer'];
        $stmt->close();

        $stmt2 = $conn->prepare("UPDATE customers SET name = ? WHERE id_customer = ?");
        if (!$stmt2) {
            throw new Exception("Database prepare error on update: " . $conn->error);
        }
        $stmt2->bind_param("si", $customer_name, $customer_id);
        if (!$stmt2->execute()) {
            throw new Exception("Gagal update customer: " . $stmt2->error);
        }
        $stmt2->close();
    } else {
        $stmt->close();

        $stmt3 = $conn->prepare("INSERT INTO customers (name, phone) VALUES (?, ?)");
        if (!$stmt3) {
            throw new Exception("Database prepare error on insert: " . $conn->error);
        }

        $stmt3->bind_param("ss", $customer_name, $phone_number);

        if (!$stmt3->execute()) {
            $error_msg = $stmt3->error;
            $stmt3->close();

            if (strpos($error_msg, 'Duplicate entry') !== false) {
                throw new Exception("Nomor HP sudah terdaftar: " . $phone_number);
            }

            throw new Exception("Gagal membuat customer baru: " . $error_msg);
        }

        $customer_id = $stmt3->insert_id;
        $affected_rows = $stmt3->affected_rows;

        error_log("Customer insert - insert_id: {$customer_id}, affected_rows: {$affected_rows}");

        $stmt3->close();

        if (!$customer_id || $customer_id <= 0) {
            error_log("insert_id failed, trying manual query for phone: {$phone_number}");

            $stmt4 = $conn->prepare("SELECT id_customer FROM customers WHERE phone = ? ORDER BY id_customer DESC LIMIT 1");
            if (!$stmt4) {
                throw new Exception("Failed to retrieve customer ID: " . $conn->error);
            }

            $stmt4->bind_param("s", $phone_number);
            $stmt4->execute();
            $result4 = $stmt4->get_result();

            if ($result4->num_rows > 0) {
                $row4 = $result4->fetch_assoc();
                $customer_id = $row4['id_customer'];
                error_log("Manual query found customer_id: {$customer_id}");
            }
            $stmt4->close();

            if (!$customer_id || $customer_id <= 0) {
                throw new Exception("Gagal mendapatkan ID customer baru. insert_id={$customer_id}, affected_rows={$affected_rows}");
            }
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

    $first_item = reset($items);
    $trans_date = $first_item['trans_date'] ?? date('Y-m-d');
    $first_service_id = intval($first_item['service_id'] ?? 0);
    $first_brand = trim($first_item['brand'] ?? '');

    $max_duration = 0;
    foreach ($items as $item) {
        $duration = intval($item['duration'] ?? 0);
        if ($duration > $max_duration) {
            $max_duration = $duration;
        }
    }

    $est_finish_date = NULL;
    if ($max_duration > 0) {
        $date = new DateTime($trans_date);
        $date->modify("+{$max_duration} days");
        $est_finish_date = $date->format('Y-m-d');
    }

    // === GENERATE ORDER CODE ===
    $date_code = date('ym');
    $max_attempts = 10;
    $order_code = null;

    for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
        $random_digits = mt_rand(1000, 9999);
        $temp_order_code = "ORD{$date_code}-{$random_digits}";

        $stmt5 = $conn->prepare("SELECT id_drop FROM drops WHERE order_code = ?");
        if (!$stmt5) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt5->bind_param("s", $temp_order_code);
        $stmt5->execute();
        $check_result = $stmt5->get_result();
        $stmt5->close();

        if ($check_result->num_rows === 0) {
            $order_code = $temp_order_code;
            break;
        }
    }

    if ($order_code === null) {
        $timestamp = time();
        $fallback_num = ($timestamp % 9000) + 1000;
        $order_code = "ORD{$date_code}-{$fallback_num}";
    }

    // === 3. INSERT DROP ORDER ===
    $total_items = count($items);

    $stmt6 = $conn->prepare("
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

    if (!$stmt6) {
        throw new Exception("Database prepare error for drops: " . $conn->error);
    }

    $stmt6->bind_param(
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

    if (!$stmt6->execute()) {
        $drop_error = $stmt6->error;
        $stmt6->close();
        throw new Exception("Gagal menyimpan pesanan: " . $drop_error);
    }

    $drop_id = $stmt6->insert_id;
    $drop_affected = $stmt6->affected_rows;

    error_log("Drop insert - insert_id: {$drop_id}, affected_rows: {$drop_affected}, order_code: {$order_code}");

    $stmt6->close();

    // FIX: Manual query if insert_id fails
    if (!$drop_id || $drop_id <= 0) {
        error_log("Drop insert_id failed, trying manual query for order_code: {$order_code}");

        $stmt_manual = $conn->prepare("SELECT id_drop FROM drops WHERE order_code = ? ORDER BY id_drop DESC LIMIT 1");
        if (!$stmt_manual) {
            throw new Exception("Failed to retrieve drop ID: " . $conn->error);
        }

        $stmt_manual->bind_param("s", $order_code);
        $stmt_manual->execute();
        $result_manual = $stmt_manual->get_result();

        if ($result_manual->num_rows > 0) {
            $row_manual = $result_manual->fetch_assoc();
            $drop_id = $row_manual['id_drop'];
            error_log("Manual query found drop_id: {$drop_id}");
        }
        $stmt_manual->close();

        if (!$drop_id || $drop_id <= 0) {
            throw new Exception("Gagal mendapatkan ID pesanan. insert_id={$drop_id}, affected_rows={$drop_affected}");
        }
    }

    // ========== TAMBAHKAN KODE INI (TRACKING KASIR) ==========
// Update tracking: Kasir yang menerima order
    if ($drop_id > 0 && $employee_id > 0) {
        try {
            // Cek apakah kolom tracking sudah ada
            $check_column = $conn->query("SHOW COLUMNS FROM drops LIKE 'received_by'");

            if ($check_column && $check_column->num_rows > 0) {
                // Kolom ada, lakukan update tracking
                $stmt_track = $conn->prepare("UPDATE drops SET received_by = ?, received_at = NOW() WHERE id_drop = ?");

                if ($stmt_track) {
                    $stmt_track->bind_param("ii", $employee_id, $drop_id);

                    if ($stmt_track->execute()) {
                        error_log("TRACKING SUCCESS - Drop ID: {$drop_id}, Received by Employee: {$employee_id}");
                    } else {
                        error_log("TRACKING WARNING - Failed to update: " . $stmt_track->error);
                    }

                    $stmt_track->close();
                }
            } else {
                error_log("TRACKING WARNING - Column 'received_by' not found in drops table. Run ALTER TABLE first.");
            }

        } catch (Exception $track_error) {
            // Jangan throw error, hanya log agar tidak mengganggu flow utama
            error_log("TRACKING ERROR - " . $track_error->getMessage());
        }
    }

    // === 4. INSERT ITEMS ===
    $item_order = 1;

    foreach ($items as $item) {
        $brand = trim($item['brand']);
        $service_id = intval($item['service_id']);
        $price = floatval($item['price'] ?? 0);
        $status_id = intval($item['status_id']);
        $item_notes = trim($item['notes'] ?? '');

        $stmt7 = $conn->prepare("
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

        if (!$stmt7) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt7->bind_param(
            "isidisi",
            $drop_id,
            $brand,
            $service_id,
            $price,
            $status_id,
            $item_notes,
            $item_order
        );

        if (!$stmt7->execute()) {
            throw new Exception("Gagal menyimpan item: " . $stmt7->error);
        }

        $stmt7->close();
        $item_order++;
    }

    // === 5. INSERT PAYMENT ===
    $stmt8 = $conn->prepare("
        INSERT INTO payments (
            drop_id,
            amount_paid,
            payment_method,
            payment_date,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt8) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt8->bind_param(
        "idsss",
        $drop_id,
        $amount_paid,
        $payment_method,
        $payment_date,
        $payment_status
    );

    $stmt8->execute();
    $stmt8->close();

    // === 6. INSERT DEADLINE ===
    if ($est_finish_date) {
        $first_status_id = intval($first_item['status_id']);

        $stmt9 = $conn->prepare("
            INSERT INTO deadlines (
                drop_id,
                deadline_date,
                status_id,
                created_at
            ) VALUES (?, ?, ?, NOW())
        ");

        if (!$stmt9) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt9->bind_param("isi", $drop_id, $est_finish_date, $first_status_id);
        $stmt9->execute();
        $stmt9->close();
    }

    // === 7. COMMIT TRANSACTION ===
    $conn->commit();

    // === 8. SUCCESS RESPONSE ===
    sendJsonResponse([
        'success' => true,
        'message' => "Pesanan berhasil ditambahkan",
        'drop_id' => $drop_id,
        'order_code' => $order_code,
        'customer_id' => $customer_id,
        'item_count' => count($items)
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
    }

    error_log("DROP_ADD ERROR: " . $e->getMessage());

    sendJsonResponse([
        'success' => false,
        'message' => $e->getMessage()
    ]);

} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
    ob_end_flush();
}