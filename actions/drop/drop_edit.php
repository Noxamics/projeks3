<?php
// File: /actions/drop/drop_edit.php
// DEBUGGING VERSION - Tambahkan error logging yang lebih detail

// ===== SETUP =====
error_reporting(E_ALL);
ini_set('display_errors', 1); // UBAH JADI 1 untuk debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error.log');

// Fungsi helper untuk logging
function debug_log($message, $data = null)
{
    $log_message = "[" . date('Y-m-d H:i:s') . "] " . $message;
    if ($data !== null) {
        $log_message .= "\n" . print_r($data, true);
    }
    error_log($log_message);
}

session_start();

// Set header JSON SEBELUM output apapun
header('Content-Type: application/json; charset=utf-8');

debug_log("=== DROP_EDIT START ===");

// ===== INCLUDE DB CONNECTION =====
$db_path = __DIR__ . '/../../db.php';
debug_log("Checking db.php at: " . $db_path);

if (!file_exists($db_path)) {
    $error = [
        'success' => false,
        'message' => 'File db.php tidak ditemukan di: ' . $db_path
    ];
    debug_log("ERROR: db.php not found", $error);
    echo json_encode($error);
    exit;
}

require_once $db_path;
debug_log("db.php included successfully");

// ===== CHECK CONNECTION =====
if (!isset($conn) || !$conn) {
    $error = [
        'success' => false,
        'message' => 'Koneksi database gagal'
    ];
    debug_log("ERROR: Database connection failed", $error);
    echo json_encode($error);
    exit;
}

debug_log("Database connected successfully");

// ===== VALIDATE REQUEST METHOD =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $error = [
        'success' => false,
        'message' => 'Invalid request method'
    ];
    debug_log("ERROR: Invalid request method", $error);
    echo json_encode($error);
    exit;
}

debug_log("POST data received", $_POST);

try {
    // ===== VALIDATE REQUIRED FIELDS =====
    debug_log("Step 1: Validating required fields");

    if (empty($_POST['drop_id']) || intval($_POST['drop_id']) <= 0) {
        throw new Exception('ID Drop tidak valid');
    }

    if (empty($_POST['items']) || !is_array($_POST['items'])) {
        throw new Exception('Minimal harus ada 1 item pesanan');
    }

    if (empty($_POST['employee_id']) || intval($_POST['employee_id']) <= 0) {
        throw new Exception('Karyawan harus dipilih');
    }

    debug_log("Validation passed");

    // Start transaction
    debug_log("Step 2: Starting transaction");
    $conn->begin_transaction();

    // === 1. GET DROP ID & CURRENT DATA ===
    $drop_id = intval($_POST['drop_id']);
    debug_log("Drop ID: " . $drop_id);

    debug_log("Step 3: Fetching existing drop data");
    $stmt = $conn->prepare("
        SELECT d.*, c.name as customer_name, c.phone as customer_phone, c.id_customer
        FROM drops d
        JOIN customers c ON d.customer_id = c.id_customer
        WHERE d.id_drop = ?
    ");

    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }

    $stmt->bind_param("i", $drop_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Data pesanan tidak ditemukan");
    }

    $existing_drop = $result->fetch_assoc();
    debug_log("Existing drop data", $existing_drop);
    $stmt->close();

    // === 2. GET CUSTOMER DATA ===
    debug_log("Step 4: Processing customer data");

    $customer_name = !empty($_POST['customer_name']) ? trim($_POST['customer_name']) : $existing_drop['customer_name'];
    $phone_number = !empty($_POST['phone_number']) ? trim($_POST['phone_number']) : $existing_drop['customer_phone'];

    debug_log("Customer info", [
        'name' => $customer_name,
        'phone' => $phone_number
    ]);

    if (empty($customer_name)) {
        throw new Exception('Nama pelanggan wajib diisi');
    }

    if (empty($phone_number)) {
        throw new Exception('Nomor HP wajib diisi');
    }

    // Check if phone number changed
    if ($phone_number !== $existing_drop['customer_phone']) {
        debug_log("Phone number changed, checking for existing customer");

        $stmt = $conn->prepare("SELECT id_customer FROM customers WHERE phone = ?");
        $stmt->bind_param("s", $phone_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            $customer_id = $customer['id_customer'];
            debug_log("Found existing customer with ID: " . $customer_id);
            $stmt->close();

            // Update name if different
            $stmt = $conn->prepare("UPDATE customers SET name = ? WHERE id_customer = ?");
            $stmt->bind_param("si", $customer_name, $customer_id);
            $stmt->execute();
            $stmt->close();
        } else {
            debug_log("Creating new customer");
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO customers (name, phone) VALUES (?, ?)");
            $stmt->bind_param("ss", $customer_name, $phone_number);
            $stmt->execute();
            $customer_id = $conn->insert_id;
            $stmt->close();

            debug_log("New customer created with ID: " . $customer_id);

            if (!$customer_id) {
                throw new Exception("Gagal membuat customer baru");
            }
        }
    } else {
        $customer_id = $existing_drop['id_customer'];
        debug_log("Using existing customer ID: " . $customer_id);

        if ($customer_name !== $existing_drop['customer_name']) {
            debug_log("Updating customer name");
            $stmt = $conn->prepare("UPDATE customers SET name = ? WHERE id_customer = ?");
            $stmt->bind_param("si", $customer_name, $customer_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // === 3. GET DATA FROM FORM ===
    debug_log("Step 5: Processing form data");

    $employee_id = intval($_POST['employee_id']);
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $payment_status = $_POST['payment_status'] ?? 'Belum Lunas';
    $payment_method = $_POST['payment_method'] ?? 'Tunai';
    $amount_paid = floatval($_POST['amount_paid'] ?? 0);
    $payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : NULL;
    $note = trim($_POST['note'] ?? '');
    $items = $_POST['items'];

    debug_log("Form data", [
        'employee_id' => $employee_id,
        'total_amount' => $total_amount,
        'payment_status' => $payment_status,
        'item_count' => count($items)
    ]);

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

    debug_log("Calculated dates", [
        'trans_date' => $trans_date,
        'max_duration' => $max_duration,
        'est_finish_date' => $est_finish_date
    ]);

    // === 4. UPDATE DROP ORDER ===
    debug_log("Step 6: Updating drop order");

    $total_items = count($items);

    $stmt = $conn->prepare("
        UPDATE drops SET
            customer_id = ?, 
            employee_id = ?,
            service_id = ?,
            brand = ?,
            trans_date = ?,
            est_finish_date = ?,
            total_amount = ?,
            total_items = ?,
            note = ?
        WHERE id_drop = ?
    ");

    if (!$stmt) {
        throw new Exception("Prepare UPDATE drops failed: " . $conn->error);
    }

    // Type definition: i=integer, s=string, d=double
    // Parameters: customer_id(i), employee_id(i), service_id(i), brand(s), 
    //             trans_date(s), est_finish_date(s), total_amount(d), total_items(i), note(s), drop_id(i)
    //             Total: 10 parameters = "iiisspdzsi" -> wait, let me recount
    //             1=i, 2=i, 3=i, 4=s, 5=s, 6=s, 7=d, 8=i, 9=s, 10=i = "iiisssdisi"
    $stmt->bind_param(
        "iiisssdisi",  // FIX: Added one more 's' for est_finish_date
        $customer_id,
        $employee_id,
        $first_service_id,
        $first_brand,
        $trans_date,
        $est_finish_date,
        $total_amount,
        $total_items,
        $note,
        $drop_id
    );

    if (!$stmt->execute()) {
        throw new Exception("Execute UPDATE drops failed: " . $stmt->error);
    }

    debug_log("Drop order updated successfully");
    $stmt->close();

    // === 5. DELETE OLD ITEMS ===
    debug_log("Step 7: Deleting old items");

    $stmt = $conn->prepare("DELETE FROM drop_items WHERE drop_id = ?");
    $stmt->bind_param("i", $drop_id);
    $stmt->execute();
    $deleted_count = $stmt->affected_rows;
    debug_log("Deleted " . $deleted_count . " old items");
    $stmt->close();

    // === 6. INSERT NEW ITEMS ===
    debug_log("Step 8: Inserting new items");

    $item_order = 1;

    foreach ($items as $idx => $item) {
        debug_log("Processing item #" . ($idx + 1), $item);

        $brand = trim($item['brand']);
        $service_id = intval($item['service_id']);
        $price = floatval($item['price'] ?? 0);
        $item_notes = trim($item['notes'] ?? '');
        $status_id = intval($item['status_id'] ?? 1); // Default: Antrian

        $stmt = $conn->prepare("
            INSERT INTO drop_items (
                drop_id,
                brand,
                service_id,
                price,
                status_id,
                notes,
                item_order
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            throw new Exception("Prepare INSERT drop_items failed: " . $conn->error);
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
            throw new Exception("Execute INSERT drop_items failed for item #" . $item_order . ": " . $stmt->error);
        }

        debug_log("Item #" . $item_order . " inserted with ID: " . $conn->insert_id);
        $stmt->close();
        $item_order++;
    }

    // === 7. UPDATE PAYMENT ===
    debug_log("Step 9: Updating payment");

    $stmt = $conn->prepare("SELECT id_payment FROM payments WHERE drop_id = ?");
    $stmt->bind_param("i", $drop_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        debug_log("Updating existing payment");
        $stmt->close();

        $stmt = $conn->prepare("
            UPDATE payments SET
                amount_paid = ?,
                payment_method = ?,
                payment_date = ?,
                status = ?
            WHERE drop_id = ?
        ");
        $stmt->bind_param("dsssi", $amount_paid, $payment_method, $payment_date, $payment_status, $drop_id);
    } else {
        debug_log("Creating new payment");
        $stmt->close();

        $stmt = $conn->prepare("
            INSERT INTO payments (
                drop_id,
                amount_paid,
                payment_method,
                payment_date,
                status
            ) VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("idsss", $drop_id, $amount_paid, $payment_method, $payment_date, $payment_status);
    }

    if (!$stmt->execute()) {
        throw new Exception("Payment update failed: " . $stmt->error);
    }

    debug_log("Payment updated successfully");
    $stmt->close();

    // === 8. UPDATE DEADLINE ===
    debug_log("Step 10: Updating deadline");

    if ($est_finish_date) {
        $first_status_id = intval($first_item['status_id'] ?? 1);

        $stmt = $conn->prepare("SELECT id_deadline FROM deadlines WHERE drop_id = ?");
        $stmt->bind_param("i", $drop_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            debug_log("Updating existing deadline");
            $stmt->close();

            $stmt = $conn->prepare("
                UPDATE deadlines SET
                    deadline_date = ?,
                    status_id = ?
                WHERE drop_id = ?
            ");
            $stmt->bind_param("sii", $est_finish_date, $first_status_id, $drop_id);
        } else {
            debug_log("Creating new deadline");
            $stmt->close();

            $stmt = $conn->prepare("
                INSERT INTO deadlines (
                    drop_id,
                    deadline_date,
                    status_id
                ) VALUES (?, ?, ?)
            ");
            $stmt->bind_param("isi", $drop_id, $est_finish_date, $first_status_id);
        }

        if (!$stmt->execute()) {
            throw new Exception("Deadline update failed: " . $stmt->error);
        }

        debug_log("Deadline updated successfully");
        $stmt->close();
    }

    // === 9. COMMIT TRANSACTION ===
    debug_log("Step 11: Committing transaction");
    $conn->commit();
    debug_log("Transaction committed successfully");

    // === 10. SUCCESS RESPONSE ===
    $response = [
        'success' => true,
        'message' => "Pesanan berhasil diupdate",
        'drop_id' => $drop_id,
        'customer_id' => $customer_id,
        'item_count' => count($items)
    ];

    debug_log("SUCCESS", $response);
    echo json_encode($response);

} catch (Exception $e) {
    // Rollback on error
    if (isset($conn)) {
        $conn->rollback();
        debug_log("Transaction rolled back");
    }

    $error_message = $e->getMessage();
    debug_log("EXCEPTION CAUGHT: " . $error_message);
    debug_log("Stack trace: " . $e->getTraceAsString());

    echo json_encode([
        'success' => false,
        'message' => $error_message,
        'debug_trace' => $e->getTraceAsString() // Hapus ini di production
    ]);

} finally {
    // Don't close $stmt here - it's already closed in the try block
    // Only close connection
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
    debug_log("=== DROP_EDIT END ===\n");
}
?>