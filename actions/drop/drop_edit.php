<?php
// File: /actions/drop/drop_edit.php
// FIXED VERSION - With actual_finish_date support and missing function

// ===== SETUP =====
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error.log');

session_start();
header('Content-Type: application/json; charset=utf-8');

// ===== HELPER FUNCTION: Get Employee Data =====
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

// ===== HELPER FUNCTION: Validate Date Format =====
function validateDateFormat($date_string)
{
    if (empty($date_string)) {
        return null;
    }

    $d = DateTime::createFromFormat('Y-m-d', $date_string);
    if ($d && $d->format('Y-m-d') === $date_string) {
        return $date_string;
    }

    return null;
}

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

// ===== VALIDATE REQUEST METHOD =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

try {
    error_log("=== DROP_EDIT: Request received ===");

    // ===== VALIDATE REQUIRED FIELDS =====
    if (empty($_POST['drop_id']) || intval($_POST['drop_id']) <= 0) {
        throw new Exception('ID Drop tidak valid');
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

    // === 1. GET DROP ID & CURRENT DATA ===
    $drop_id = intval($_POST['drop_id']);
    error_log("Processing drop_id: {$drop_id}");

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
    $stmt->close();
    error_log("✓ Existing drop found");

    // === 2. PROCESS CUSTOMER DATA ===
    $customer_name = !empty($_POST['customer_name']) ? trim($_POST['customer_name']) : $existing_drop['customer_name'];
    $phone_number = !empty($_POST['phone_number']) ? trim($_POST['phone_number']) : $existing_drop['customer_phone'];

    if (empty($customer_name)) {
        throw new Exception('Nama pelanggan wajib diisi');
    }

    if (empty($phone_number)) {
        throw new Exception('Nomor HP wajib diisi');
    }

    // Check if phone number changed
    if ($phone_number !== $existing_drop['customer_phone']) {
        $stmt2 = $conn->prepare("SELECT id_customer FROM customers WHERE phone = ?");
        $stmt2->bind_param("s", $phone_number);
        $stmt2->execute();
        $result2 = $stmt2->get_result();

        if ($result2->num_rows > 0) {
            $customer = $result2->fetch_assoc();
            $customer_id = $customer['id_customer'];
            $stmt2->close();

            // Update name if different
            $stmt3 = $conn->prepare("UPDATE customers SET name = ? WHERE id_customer = ?");
            $stmt3->bind_param("si", $customer_name, $customer_id);
            $stmt3->execute();
            $stmt3->close();
            error_log("✓ Customer updated (existing)");
        } else {
            $stmt2->close();

            $stmt4 = $conn->prepare("INSERT INTO customers (name, phone) VALUES (?, ?)");
            $stmt4->bind_param("ss", $customer_name, $phone_number);
            $stmt4->execute();
            $customer_id = $conn->insert_id;
            $stmt4->close();

            if (!$customer_id) {
                throw new Exception("Gagal membuat customer baru");
            }
            error_log("✓ New customer created: {$customer_id}");
        }
    } else {
        $customer_id = $existing_drop['id_customer'];

        if ($customer_name !== $existing_drop['customer_name']) {
            $stmt5 = $conn->prepare("UPDATE customers SET name = ? WHERE id_customer = ?");
            $stmt5->bind_param("si", $customer_name, $customer_id);
            $stmt5->execute();
            $stmt5->close();
            error_log("✓ Customer name updated");
        }
    }

    // === 3. GET FORM DATA ===
    $employee_id = intval($_POST['employee_id']);
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $payment_status = isset($_POST['payment_status']) ? trim($_POST['payment_status']) : 'Belum Lunas';
    $payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : 'Tunai';
    $amount_paid = floatval($_POST['amount_paid'] ?? 0);

    // Validate payment date
    $payment_date = NULL;
    if (!empty($_POST['payment_date'])) {
        $payment_date = validateDateFormat($_POST['payment_date']);
    }

    $note = isset($_POST['note']) ? trim($_POST['note']) : '';
    $items = $_POST['items'];

    error_log("Items count: " . count($items));
    error_log("Employee ID: {$employee_id}");
    error_log("Payment status: {$payment_status}");

    // Get first item data
    $first_item = reset($items);
    $trans_date = isset($first_item['trans_date']) ? $first_item['trans_date'] : date('Y-m-d');
    $trans_date = validateDateFormat($trans_date) ?: date('Y-m-d');

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
        try {
            $date = new DateTime($trans_date);
            $date->modify("+{$max_duration} days");
            $est_finish_date = $date->format('Y-m-d');
            error_log("Estimated finish date: {$est_finish_date}");
        } catch (Exception $e) {
            error_log("Error calculating est_finish_date: " . $e->getMessage());
        }
    }

    // === 4. CHECK IF ALL ITEMS ARE "DIAMBIL" (status_id = 6) ===
    $all_items_taken = true;
    foreach ($items as $item) {
        if (intval($item['status_id'] ?? 1) != 6) {
            $all_items_taken = false;
            break;
        }
    }

    // Set actual_finish_date if all items are taken
    $actual_finish_date = NULL;
    if ($all_items_taken) {
        $actual_finish_date = date('Y-m-d');
        error_log("✓ All items taken - Setting actual_finish_date: {$actual_finish_date}");
    }

    // === 5. UPDATE DROP ORDER ===
    $total_items = count($items);

    $stmt6 = $conn->prepare("
        UPDATE drops SET
            customer_id = ?, 
            employee_id = ?,
            service_id = ?,
            brand = ?,
            trans_date = ?,
            est_finish_date = ?,
            actual_finish_date = ?,
            total_amount = ?,
            total_items = ?,
            note = ?
        WHERE id_drop = ?
    ");

    if (!$stmt6) {
        throw new Exception("Prepare UPDATE drops failed: " . $conn->error);
    }

    $stmt6->bind_param(
        "iiissssdisi",
        $customer_id,
        $employee_id,
        $first_service_id,
        $first_brand,
        $trans_date,
        $est_finish_date,
        $actual_finish_date,
        $total_amount,
        $total_items,
        $note,
        $drop_id
    );

    if (!$stmt6->execute()) {
        throw new Exception("Execute UPDATE drops failed: " . $stmt6->error);
    }

    $stmt6->close();
    error_log("✓ Drop updated");

    // ========== TRACKING: UPDATE BERDASARKAN STATUS ITEMS ==========
    // Cek status dari semua items untuk tracking
    $tracking_updates = [];

    foreach ($items as $item) {
        $item_status_id = intval($item['status_id'] ?? 1);

        // Mapping status ke tracking field
        switch ($item_status_id) {
            case 1: // Barang Baru Masuk
                if (!isset($tracking_updates['received_by'])) {
                    $tracking_updates['received_by'] = 'received_at';
                }
                break;

            case 3: // Sedang Dikerjakan
                if (!isset($tracking_updates['processed_by'])) {
                    $tracking_updates['processed_by'] = 'processed_at';
                }
                break;

            case 5: // Barang Siap Diambil
                if (!isset($tracking_updates['packed_by'])) {
                    $tracking_updates['packed_by'] = 'packed_at';
                }
                break;

            case 6: // Barang Telah Diambil
                if (!isset($tracking_updates['released_by'])) {
                    $tracking_updates['released_by'] = 'released_at';
                }
                break;
        }
    }

    // Update tracking fields jika ada
    if (!empty($tracking_updates) && $employee_id > 0) {
        error_log("Updating tracking fields: " . implode(', ', array_keys($tracking_updates)));

        try {
            $emp_data = getEmployeeData($conn, $employee_id);

            if ($emp_data) {
                foreach ($tracking_updates as $field => $time_field) {
                    // Update ID + NAMA + KODE
                    $name_field = $field . '_name';
                    $code_field = $field . '_code';

                    $sql_track = "UPDATE drops 
                        SET {$field} = ?, 
                            {$name_field} = ?,
                            {$code_field} = ?,
                            {$time_field} = NOW() 
                        WHERE id_drop = ?";

                    $stmt_track = $conn->prepare($sql_track);

                    if (!$stmt_track) {
                        error_log("⚠ Failed to prepare tracking update for {$field}");
                        continue;
                    }

                    $stmt_track->bind_param(
                        "issi",
                        $employee_id,
                        $emp_data['name'],
                        $emp_data['employee_code'],
                        $drop_id
                    );

                    if ($stmt_track->execute()) {
                        error_log("✓ Tracking updated: {$field}");
                    } else {
                        error_log("⚠ Failed to execute tracking update for {$field}: " . $stmt_track->error);
                    }

                    $stmt_track->close();
                }
            } else {
                error_log("⚠ Employee data not found for ID: {$employee_id}");
            }
        } catch (Exception $track_error) {
            error_log("⚠ TRACKING EDIT WARNING - " . $track_error->getMessage());
        }
    }

    // === 6. DELETE OLD ITEMS ===
    $stmt7 = $conn->prepare("DELETE FROM drop_items WHERE drop_id = ?");
    if (!$stmt7) {
        throw new Exception("Prepare DELETE drop_items failed: " . $conn->error);
    }
    $stmt7->bind_param("i", $drop_id);
    $stmt7->execute();
    $deleted_count = $stmt7->affected_rows;
    $stmt7->close();
    error_log("✓ Deleted {$deleted_count} old items");

    // === 7. INSERT NEW ITEMS ===
    $item_order = 1;

    foreach ($items as $index => $item) {
        $brand = isset($item['brand']) ? trim($item['brand']) : '';
        $service_id = intval($item['service_id'] ?? 0);
        $price = floatval($item['price'] ?? 0);
        $item_notes = isset($item['notes']) ? trim($item['notes']) : '';
        $status_id = intval($item['status_id'] ?? 1);

        // Validate required fields
        if (empty($brand)) {
            throw new Exception("Item #{$item_order}: Brand tidak boleh kosong");
        }

        if ($service_id <= 0) {
            throw new Exception("Item #{$item_order}: Service ID tidak valid");
        }

        $stmt8 = $conn->prepare("
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

        if (!$stmt8) {
            throw new Exception("Prepare INSERT drop_items failed: " . $conn->error);
        }

        $stmt8->bind_param(
            "isidisi",
            $drop_id,
            $brand,
            $service_id,
            $price,
            $status_id,
            $item_notes,
            $item_order
        );

        if (!$stmt8->execute()) {
            throw new Exception("Execute INSERT drop_items failed: " . $stmt8->error);
        }

        $stmt8->close();
        error_log("✓ Item #{$item_order} inserted");
        $item_order++;
    }

    // === 8. UPDATE PAYMENT ===
    $stmt9 = $conn->prepare("SELECT id_payment FROM payments WHERE drop_id = ?");
    if (!$stmt9) {
        throw new Exception("Prepare SELECT payments failed: " . $conn->error);
    }

    $stmt9->bind_param("i", $drop_id);
    $stmt9->execute();
    $result9 = $stmt9->get_result();

    if ($result9->num_rows > 0) {
        $stmt9->close();

        $stmt10 = $conn->prepare("
            UPDATE payments SET
                amount_paid = ?,
                payment_method = ?,
                payment_date = ?,
                status = ?
            WHERE drop_id = ?
        ");

        if (!$stmt10) {
            throw new Exception("Prepare UPDATE payments failed: " . $conn->error);
        }

        $stmt10->bind_param("dsssi", $amount_paid, $payment_method, $payment_date, $payment_status, $drop_id);
        $stmt10->execute();
        $stmt10->close();
        error_log("✓ Payment updated");
    } else {
        $stmt9->close();

        $stmt11 = $conn->prepare("
            INSERT INTO payments (
                drop_id,
                amount_paid,
                payment_method,
                payment_date,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");

        if (!$stmt11) {
            throw new Exception("Prepare INSERT payments failed: " . $conn->error);
        }

        $stmt11->bind_param("idsss", $drop_id, $amount_paid, $payment_method, $payment_date, $payment_status);
        $stmt11->execute();
        $stmt11->close();
        error_log("✓ Payment inserted");
    }

    // === 9. UPDATE DEADLINE ===
    if ($est_finish_date) {
        $first_status_id = intval($first_item['status_id'] ?? 1);

        $stmt12 = $conn->prepare("SELECT id_deadline FROM deadlines WHERE drop_id = ?");
        if (!$stmt12) {
            throw new Exception("Prepare SELECT deadlines failed: " . $conn->error);
        }

        $stmt12->bind_param("i", $drop_id);
        $stmt12->execute();
        $result12 = $stmt12->get_result();

        if ($result12->num_rows > 0) {
            $stmt12->close();

            $stmt13 = $conn->prepare("
                UPDATE deadlines SET
                    deadline_date = ?,
                    status_id = ?
                WHERE drop_id = ?
            ");

            if (!$stmt13) {
                throw new Exception("Prepare UPDATE deadlines failed: " . $conn->error);
            }

            $stmt13->bind_param("sii", $est_finish_date, $first_status_id, $drop_id);
            $stmt13->execute();
            $stmt13->close();
            error_log("✓ Deadline updated");
        } else {
            $stmt12->close();

            $stmt14 = $conn->prepare("
                INSERT INTO deadlines (
                    drop_id,
                    deadline_date,
                    status_id,
                    created_at
                ) VALUES (?, ?, ?, NOW())
            ");

            if (!$stmt14) {
                throw new Exception("Prepare INSERT deadlines failed: " . $conn->error);
            }

            $stmt14->bind_param("isi", $drop_id, $est_finish_date, $first_status_id);
            $stmt14->execute();
            $stmt14->close();
            error_log("✓ Deadline inserted");
        }
    }

    // === 10. COMMIT TRANSACTION ===
    $conn->commit();
    error_log("✓ Transaction committed successfully");

    // === 11. SUCCESS RESPONSE ===
    $response = [
        'success' => true,
        'message' => "Pesanan berhasil diupdate",
        'drop_id' => $drop_id,
        'customer_id' => $customer_id,
        'item_count' => count($items)
    ];

    if ($actual_finish_date) {
        $response['message'] .= " (Tanggal selesai aktual: " . date('d M Y', strtotime($actual_finish_date)) . ")";
        $response['actual_finish_date'] = $actual_finish_date;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Rollback on error
    if (isset($conn)) {
        $conn->rollback();
        error_log("✗ Transaction rolled back");
    }

    error_log("DROP_EDIT ERROR: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

} finally {
    // Close connection
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}