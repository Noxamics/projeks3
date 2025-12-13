<?php
// File: /actions/drop/drop_edit.php
// UPDATED VERSION - With actual_finish_date support

// ===== SETUP =====
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error.log');

session_start();
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

    // === 1. GET DROP ID & CURRENT DATA ===
    $drop_id = intval($_POST['drop_id']);

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
    $stmt = null;

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
            $stmt2 = null;

            // Update name if different
            $stmt3 = $conn->prepare("UPDATE customers SET name = ? WHERE id_customer = ?");
            $stmt3->bind_param("si", $customer_name, $customer_id);
            $stmt3->execute();
            $stmt3->close();
            $stmt3 = null;
        } else {
            $stmt2->close();
            $stmt2 = null;

            $stmt4 = $conn->prepare("INSERT INTO customers (name, phone) VALUES (?, ?)");
            $stmt4->bind_param("ss", $customer_name, $phone_number);
            $stmt4->execute();
            $customer_id = $conn->insert_id;
            $stmt4->close();
            $stmt4 = null;

            if (!$customer_id) {
                throw new Exception("Gagal membuat customer baru");
            }
        }
    } else {
        $customer_id = $existing_drop['id_customer'];

        if ($customer_name !== $existing_drop['customer_name']) {
            $stmt5 = $conn->prepare("UPDATE customers SET name = ? WHERE id_customer = ?");
            $stmt5->bind_param("si", $customer_name, $customer_id);
            $stmt5->execute();
            $stmt5->close();
            $stmt5 = null;
        }
    }

    // === 3. GET FORM DATA ===
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
    $stmt6 = null;

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
        try {
            foreach ($tracking_updates as $field => $time_field) {
                // Cek apakah kolom sudah ada
                $check = $conn->query("SHOW COLUMNS FROM drops LIKE '{$field}'");

                if ($check && $check->num_rows > 0) {
                    // Cek apakah sudah terisi (jangan overwrite jika sudah ada)
                    $check_value = $conn->prepare("SELECT {$field} FROM drops WHERE id_drop = ?");
                    $check_value->bind_param("i", $drop_id);
                    $check_value->execute();
                    $result_check = $check_value->get_result();
                    $row_check = $result_check->fetch_assoc();
                    $check_value->close();

                    // Hanya update jika masih NULL
                    if (empty($row_check[$field])) {
                        $sql_track = "UPDATE drops SET {$field} = ?, {$time_field} = NOW() WHERE id_drop = ?";
                        $stmt_track = $conn->prepare($sql_track);

                        if ($stmt_track) {
                            $stmt_track->bind_param("ii", $employee_id, $drop_id);

                            if ($stmt_track->execute()) {
                                error_log("✓ TRACKING EDIT - Drop: {$drop_id} | Field: {$field} | Employee: {$employee_id}");
                            }

                            $stmt_track->close();
                        }
                    }
                }
            }
        } catch (Exception $track_error) {
            error_log("⚠ TRACKING EDIT WARNING - " . $track_error->getMessage());
        }
    }

    // === 6. DELETE OLD ITEMS ===
    $stmt7 = $conn->prepare("DELETE FROM drop_items WHERE drop_id = ?");
    $stmt7->bind_param("i", $drop_id);
    $stmt7->execute();
    $stmt7->close();
    $stmt7 = null;

    // === 7. INSERT NEW ITEMS ===
    $item_order = 1;

    foreach ($items as $item) {
        $brand = trim($item['brand']);
        $service_id = intval($item['service_id']);
        $price = floatval($item['price'] ?? 0);
        $item_notes = trim($item['notes'] ?? '');
        $status_id = intval($item['status_id'] ?? 1);

        $stmt8 = $conn->prepare("
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
        $stmt8 = null;
        $item_order++;
    }

    // === 8. UPDATE PAYMENT ===
    $stmt9 = $conn->prepare("SELECT id_payment FROM payments WHERE drop_id = ?");
    $stmt9->bind_param("i", $drop_id);
    $stmt9->execute();
    $result9 = $stmt9->get_result();

    if ($result9->num_rows > 0) {
        $stmt9->close();
        $stmt9 = null;

        $stmt10 = $conn->prepare("
            UPDATE payments SET
                amount_paid = ?,
                payment_method = ?,
                payment_date = ?,
                status = ?
            WHERE drop_id = ?
        ");
        $stmt10->bind_param("dsssi", $amount_paid, $payment_method, $payment_date, $payment_status, $drop_id);
        $stmt10->execute();
        $stmt10->close();
        $stmt10 = null;
    } else {
        $stmt9->close();
        $stmt9 = null;

        $stmt11 = $conn->prepare("
            INSERT INTO payments (
                drop_id,
                amount_paid,
                payment_method,
                payment_date,
                status
            ) VALUES (?, ?, ?, ?, ?)
        ");
        $stmt11->bind_param("idsss", $drop_id, $amount_paid, $payment_method, $payment_date, $payment_status);
        $stmt11->execute();
        $stmt11->close();
        $stmt11 = null;
    }

    // === 9. UPDATE DEADLINE ===
    if ($est_finish_date) {
        $first_status_id = intval($first_item['status_id'] ?? 1);

        $stmt12 = $conn->prepare("SELECT id_deadline FROM deadlines WHERE drop_id = ?");
        $stmt12->bind_param("i", $drop_id);
        $stmt12->execute();
        $result12 = $stmt12->get_result();

        if ($result12->num_rows > 0) {
            $stmt12->close();
            $stmt12 = null;

            $stmt13 = $conn->prepare("
                UPDATE deadlines SET
                    deadline_date = ?,
                    status_id = ?
                WHERE drop_id = ?
            ");
            $stmt13->bind_param("sii", $est_finish_date, $first_status_id, $drop_id);
            $stmt13->execute();
            $stmt13->close();
            $stmt13 = null;
        } else {
            $stmt12->close();
            $stmt12 = null;

            $stmt14 = $conn->prepare("
                INSERT INTO deadlines (
                    drop_id,
                    deadline_date,
                    status_id
                ) VALUES (?, ?, ?)
            ");
            $stmt14->bind_param("isi", $drop_id, $est_finish_date, $first_status_id);
            $stmt14->execute();
            $stmt14->close();
            $stmt14 = null;
        }
    }

    // === 10. COMMIT TRANSACTION ===
    $conn->commit();

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

    echo json_encode($response);

} catch (Exception $e) {
    // Rollback on error
    if (isset($conn)) {
        $conn->rollback();
    }

    error_log("DROP_EDIT ERROR: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

} finally {
    // Close connection only (all stmts already closed per section)
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}