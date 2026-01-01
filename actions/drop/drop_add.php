<?php
// File: /actions/drop/drop_add.php
// Handle adding new drop order with multiple items
// ✅ FIXED: Error handling, type validation, and date processing

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

// ===== FUNCTION: Generate Unique Order Code =====
function generateUniqueOrderCode($conn, $max_attempts = 10)
{
    $date_code = date('ym');

    for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
        $random_digits = mt_rand(1000, 9999);
        $order_code = "ORD{$date_code}-{$random_digits}";

        $stmt = $conn->prepare("SELECT id_drop FROM drops WHERE order_code = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param("s", $order_code);
        $stmt->execute();
        $check_result = $stmt->get_result();
        $stmt->close();

        if ($check_result->num_rows === 0) {
            return $order_code;
        }
    }

    // Fallback jika semua random gagal
    $timestamp = time();
    $fallback_num = ($timestamp % 9000) + 1000;
    return "ORD{$date_code}-{$fallback_num}";
}

// ===== FUNCTION: Get Employee Data =====
function getEmployeeData($conn, $employee_id)
{
    $stmt = $conn->prepare("
        SELECT id_employee, name, employee_code 
        FROM employees 
        WHERE id_employee = ?
    ");

    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        return null;
    }

    $employee = $result->fetch_assoc();
    $stmt->close();

    return $employee;
}

// ===== FUNCTION: Validate Date Format =====
function validateDateFormat($date_string)
{
    if (empty($date_string)) {
        return null;
    }

    // Check if date is in YYYY-MM-DD format
    $d = DateTime::createFromFormat('Y-m-d', $date_string);
    if ($d && $d->format('Y-m-d') === $date_string) {
        return $date_string;
    }

    return null;
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
    // ===== LOG RECEIVED DATA FOR DEBUGGING =====
    error_log("=== DROP_ADD: Request received ===");
    error_log("POST Data: " . print_r($_POST, true));

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
    error_log("✓ Transaction started");

    // === 1. GET EMPLOYEE DATA (KASIR) ===
    $employee_id = intval($_POST['employee_id']);
    $employee_data = getEmployeeData($conn, $employee_id);

    if (!$employee_data) {
        throw new Exception('Karyawan tidak ditemukan dengan ID: ' . $employee_id);
    }

    $employee_name = $employee_data['name'];
    $employee_code = $employee_data['employee_code'];

    error_log("✓ Employee found - ID: {$employee_id}, Name: {$employee_name}, Code: {$employee_code}");

    // === 2. CHECK OR CREATE CUSTOMER ===
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
        error_log("✓ Customer updated - ID: {$customer_id}");
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
        $stmt3->close();

        if (!$customer_id || $customer_id <= 0) {
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
            }
            $stmt4->close();

            if (!$customer_id || $customer_id <= 0) {
                throw new Exception("Gagal mendapatkan ID customer baru");
            }
        }
        error_log("✓ Customer created - ID: {$customer_id}");
    }

    // === 3. GET DATA FROM FORM ===
    $payment_status = isset($_POST['payment_status']) ? trim($_POST['payment_status']) : 'Belum Lunas';
    $payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : 'Tunai';
    $note = isset($_POST['note']) ? trim($_POST['note']) : '';
    $items = $_POST['items'];

    error_log("Payment Status: {$payment_status}, Method: {$payment_method}");

    // Array untuk menyimpan order codes yang berhasil dibuat
    $created_order_codes = [];
    $total_amount_all = 0;
    $first_drop_id = null;

    // === 4. LOOP SETIAP ITEM - BUAT DROP TERPISAH ===
    $item_number = 1;

    foreach ($items as $item) {
        error_log("--- Processing Item #{$item_number} ---");

        // Generate UNIQUE order code untuk setiap item
        $order_code = generateUniqueOrderCode($conn);
        error_log("Generated order code: {$order_code}");

        // Validate and sanitize item data
        $brand = isset($item['brand']) ? trim($item['brand']) : '';
        if (empty($brand)) {
            throw new Exception("Item #{$item_number}: Brand tidak boleh kosong");
        }

        $service_id = isset($item['service_id']) ? intval($item['service_id']) : 0;
        if ($service_id <= 0) {
            throw new Exception("Item #{$item_number}: Service ID tidak valid");
        }

        $price = isset($item['price']) ? floatval($item['price']) : 0;
        if ($price < 0) {
            throw new Exception("Item #{$item_number}: Harga tidak valid");
        }

        $status_id = isset($item['status_id']) ? intval($item['status_id']) : 0;
        if ($status_id <= 0) {
            throw new Exception("Item #{$item_number}: Status ID tidak valid");
        }

        $item_notes = isset($item['notes']) ? trim($item['notes']) : '';

        // Validate transaction date
        $trans_date = isset($item['trans_date']) ? $item['trans_date'] : date('Y-m-d');
        $trans_date = validateDateFormat($trans_date);
        if (!$trans_date) {
            $trans_date = date('Y-m-d');
            error_log("Item #{$item_number}: Invalid trans_date, using today");
        }

        $duration = isset($item['duration']) ? intval($item['duration']) : 0;

        // Hitung estimasi selesai
        $est_finish_date = NULL;
        if ($duration > 0) {
            try {
                $date = new DateTime($trans_date);
                $date->modify("+{$duration} days");
                $est_finish_date = $date->format('Y-m-d');
                error_log("Item #{$item_number}: Estimated finish: {$est_finish_date}");
            } catch (Exception $e) {
                error_log("Item #{$item_number}: Error calculating est_finish_date: " . $e->getMessage());
                $est_finish_date = NULL;
            }
        }

        // === INSERT DROP ===
        $stmt_drop = $conn->prepare("
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
                received_by,
                received_by_name,
                received_by_code,
                received_at,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, NOW(), NOW())
        ");

        if (!$stmt_drop) {
            throw new Exception("Database prepare error for drops: " . $conn->error);
        }

        // Bind parameters: s=string, i=integer, d=double
        $stmt_drop->bind_param(
            "siiisssdsiss",
            $order_code,        // s - string
            $customer_id,       // i - integer
            $employee_id,       // i - integer
            $service_id,        // i - integer
            $brand,             // s - string
            $trans_date,        // s - string (date)
            $est_finish_date,   // s - string (date, nullable)
            $price,             // d - double
            $note,              // s - string
            $employee_id,       // i - integer (received_by)
            $employee_name,     // s - string (received_by_name)
            $employee_code      // s - string (received_by_code)
        );

        if (!$stmt_drop->execute()) {
            $drop_error = $stmt_drop->error;
            $stmt_drop->close();
            throw new Exception("Gagal menyimpan pesanan item #{$item_number}: " . $drop_error);
        }

        $drop_id = $stmt_drop->insert_id;
        $stmt_drop->close();

        error_log("✓ Drop inserted - Item #{$item_number}, ID: {$drop_id}, Code: {$order_code}");

        // Store first drop_id for printing
        if ($first_drop_id === null) {
            $first_drop_id = $drop_id;
        }

        // Fallback to get drop_id if insert_id fails
        if (!$drop_id || $drop_id <= 0) {
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

                if ($first_drop_id === null) {
                    $first_drop_id = $drop_id;
                }
            }
            $stmt_manual->close();

            if (!$drop_id || $drop_id <= 0) {
                throw new Exception("Gagal mendapatkan ID pesanan item #{$item_number}");
            }
        }

        // === INSERT DROP ITEM ===
        $stmt_item = $conn->prepare("
            INSERT INTO drop_items (
                drop_id,
                brand,
                service_id,
                price,
                status_id,
                notes,
                item_order,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
        ");

        if (!$stmt_item) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt_item->bind_param(
            "isidis",
            $drop_id,
            $brand,
            $service_id,
            $price,
            $status_id,
            $item_notes
        );

        if (!$stmt_item->execute()) {
            throw new Exception("Gagal menyimpan detail item #{$item_number}: " . $stmt_item->error);
        }

        $stmt_item->close();
        error_log("✓ Drop item inserted for Item #{$item_number}");

        // === INSERT PAYMENT (PER ITEM) ===
        $amount_paid_item = ($payment_status === 'Lunas') ? $price : 0;

        // Validate payment date
        $payment_date = NULL;
        if ($payment_status === 'Lunas' && isset($_POST['payment_date'])) {
            $payment_date = validateDateFormat($_POST['payment_date']);
        }

        $stmt_payment = $conn->prepare("
            INSERT INTO payments (
                drop_id,
                amount_paid,
                payment_method,
                payment_date,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");

        if (!$stmt_payment) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt_payment->bind_param(
            "idsss",
            $drop_id,
            $amount_paid_item,
            $payment_method,
            $payment_date,
            $payment_status
        );

        $stmt_payment->execute();
        $stmt_payment->close();
        error_log("✓ Payment record inserted for Item #{$item_number}");

        // === INSERT DEADLINE ===
        if ($est_finish_date) {
            $stmt_deadline = $conn->prepare("
                INSERT INTO deadlines (
                    drop_id,
                    deadline_date,
                    status_id,
                    created_at
                ) VALUES (?, ?, ?, NOW())
            ");

            if (!$stmt_deadline) {
                throw new Exception("Database error: " . $conn->error);
            }

            $stmt_deadline->bind_param("isi", $drop_id, $est_finish_date, $status_id);
            $stmt_deadline->execute();
            $stmt_deadline->close();
            error_log("✓ Deadline inserted for Item #{$item_number}");
        }

        // Simpan order code yang berhasil
        $created_order_codes[] = $order_code;
        $total_amount_all += $price;
        $item_number++;
    }

    // === COMMIT TRANSACTION ===
    $conn->commit();
    error_log("✓ Transaction committed successfully");
    error_log("✓ Total items created: " . count($created_order_codes));

    // === SUCCESS RESPONSE ===
    sendJsonResponse([
        'success' => true,
        'message' => "Berhasil menambahkan " . count($items) . " pesanan oleh {$employee_name}",
        'customer_id' => $customer_id,
        'drop_id' => $first_drop_id, // For printing
        'order_codes' => $created_order_codes,
        'total_items' => count($items),
        'total_amount' => $total_amount_all,
        'first_order_code' => $created_order_codes[0] ?? null,
        'received_by' => [
            'id' => $employee_id,
            'name' => $employee_name,
            'code' => $employee_code
        ]
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
        error_log("✗ Transaction rolled back");
    }

    error_log("DROP_ADD ERROR: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

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