<?php
// File: /actions/drop/drop_update_item_status.php
// Handle updating individual item status - WITH EMPLOYEE TRACKING

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
    if (empty($_POST['item_id']) || intval($_POST['item_id']) <= 0) {
        throw new Exception('ID Item tidak valid');
    }

    if (empty($_POST['status_id']) || intval($_POST['status_id']) <= 0) {
        throw new Exception('Status harus dipilih');
    }

    $item_id = intval($_POST['item_id']);
    $status_id = intval($_POST['status_id']);
    $employee_id = !empty($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;

    // Start transaction
    $conn->begin_transaction();

    // Check if item exists and get drop_id
    $stmt = $conn->prepare("SELECT drop_id FROM drop_items WHERE id_item = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Item tidak ditemukan");
    }

    $item_data = $result->fetch_assoc();
    $drop_id = $item_data['drop_id'];
    $stmt->close();

    // Update item status
    $stmt = $conn->prepare("UPDATE drop_items SET status_id = ? WHERE id_item = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("ii", $status_id, $item_id);
    if (!$stmt->execute()) {
        throw new Exception("Gagal update status: " . $stmt->error);
    }

    $affected_rows = $stmt->affected_rows;
    $stmt->close();

    if ($affected_rows === 0) {
        throw new Exception("Tidak ada perubahan data atau item tidak ditemukan");
    }

    // ========== TRACKING KARYAWAN BERDASARKAN STATUS ==========
    if ($employee_id > 0) {
        try {
            // Mapping status_id ke tracking field
            $tracking_field = null;
            $time_field = null;
            $action_name = '';

            switch ($status_id) {
                case 1: // Barang Baru Masuk
                    $tracking_field = 'received_by';
                    $time_field = 'received_at';
                    $action_name = 'menerima';
                    break;

                case 3: // Sedang Dikerjakan
                    $tracking_field = 'processed_by';
                    $time_field = 'processed_at';
                    $action_name = 'mengerjakan';
                    break;

                case 5: // Barang Siap Diambil
                    $tracking_field = 'packed_by';
                    $time_field = 'packed_at';
                    $action_name = 'packing';
                    break;

                case 6: // Barang Telah Diambil
                    $tracking_field = 'released_by';
                    $time_field = 'released_at';
                    $action_name = 'mengeluarkan';
                    break;
            }

            // Update tracking jika ada mapping
            if ($tracking_field && $time_field) {
                // Ambil data karyawan
                $stmt_emp = $conn->prepare("SELECT name, employee_code FROM employees WHERE id_employee = ?");
                $stmt_emp->bind_param("i", $employee_id);
                $stmt_emp->execute();
                $result_emp = $stmt_emp->get_result();

                if ($result_emp->num_rows > 0) {
                    $emp_data = $result_emp->fetch_assoc();
                    $stmt_emp->close();

                    $name_field = $tracking_field . '_name';
                    $code_field = $tracking_field . '_code';

                    $sql_track = "UPDATE drops 
                      SET {$tracking_field} = ?, 
                          {$name_field} = ?,
                          {$code_field} = ?,
                          {$time_field} = NOW() 
                      WHERE id_drop = ?";

                    $stmt_track = $conn->prepare($sql_track);
                    $stmt_track->bind_param(
                        "issi",
                        $employee_id,
                        $emp_data['name'],
                        $emp_data['employee_code'],
                        $drop_id
                    );

                    if ($stmt_track->execute()) {
                        error_log("✓ TRACKING WITH NAME - {$tracking_field}: {$emp_data['name']} ({$emp_data['employee_code']})");
                    }

                    $stmt_track->close();
                } else {
                    $stmt_emp->close();
                }
            }

        } catch (Exception $track_error) {
            error_log("✗ TRACKING ERROR - " . $track_error->getMessage());
        }
    } else {
        error_log("⚠ TRACKING SKIPPED - No employee_id provided for drop {$drop_id}");
    }

    // Update deadline status if exists
    $stmt = $conn->prepare("UPDATE deadlines SET status_id = ? WHERE drop_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $status_id, $drop_id);
        $stmt->execute();
        $stmt->close();
    }

    // Check if all items have status "Diambil" (status_id = 6)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_items, 
               SUM(CASE WHEN status_id = 6 THEN 1 ELSE 0 END) as taken_items
        FROM drop_items 
        WHERE drop_id = ?
    ");

    $stmt->bind_param("i", $drop_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item_counts = $result->fetch_assoc();
    $stmt->close();

    $actual_finish_date_updated = false;
    $payment_updated = false;

    // If ALL items are "Diambil", set actual_finish_date to TODAY
    if ($item_counts['total_items'] == $item_counts['taken_items'] && $item_counts['total_items'] > 0) {
        $today = date('Y-m-d');

        $stmt = $conn->prepare("UPDATE drops SET actual_finish_date = ? WHERE id_drop = ?");
        if ($stmt) {
            $stmt->bind_param("si", $today, $drop_id);
            $stmt->execute();
            $actual_finish_date_updated = $stmt->affected_rows > 0;
            $stmt->close();
        }

        // Auto-update payment to Lunas
        $stmt = $conn->prepare("
            UPDATE payments 
            SET status = 'Lunas', 
                payment_date = COALESCE(payment_date, NOW())
            WHERE drop_id = ? AND status = 'Belum Lunas'
        ");

        if ($stmt) {
            $stmt->bind_param("i", $drop_id);
            $stmt->execute();
            $payment_updated = $stmt->affected_rows > 0;
            $stmt->close();
        }
    }

    // Commit transaction
    $conn->commit();

    // Get updated status name
    $stmt = $conn->prepare("SELECT status_name FROM statuses WHERE id_status = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("i", $status_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Status tidak valid");
    }

    $status_data = $result->fetch_assoc();
    $status_name = $status_data['status_name'] ?? 'Unknown';
    $stmt->close();

    // Success response
    $response = [
        'success' => true,
        'message' => "Status berhasil diupdate menjadi: {$status_name}",
        'item_id' => $item_id,
        'status_id' => $status_id,
        'status_name' => $status_name,
        'drop_id' => $drop_id,
        'tracking_updated' => $tracking_updated
    ];

    // Add tracking info
    if ($tracking_updated && $tracking_field) {
        $response['tracking_field'] = $tracking_field;
        $response['tracked_employee'] = $employee_id;
    }

    // Add info if actual_finish_date was set
    if ($actual_finish_date_updated) {
        $response['message'] .= " (Tanggal selesai aktual dicatat: " . date('d M Y') . ")";
        $response['actual_finish_date_updated'] = true;
    }

    // Add info if payment was auto-updated
    if ($payment_updated) {
        $response['message'] .= " (Pembayaran otomatis diubah menjadi Lunas)";
        $response['payment_updated'] = true;
    }

    echo json_encode($response);

} catch (Exception $e) {
    // Rollback on error
    if (isset($conn)) {
        $conn->rollback();
    }

    error_log("DROP_UPDATE_ITEM_STATUS ERROR: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
?>