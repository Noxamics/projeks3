<?php
include('../db.php');
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

error_log("===========================================");
error_log("DROP ADD MULTI-ITEM WITH NOTES - POST DATA RECEIVED:");
error_log(print_r($_POST, true));
error_log("===========================================");

/**
 * Fungsi konversi tanggal ke format database (YYYY-MM-DD)
 */
function convertDateToDbFormat($input_date) {
    if (empty($input_date) || trim($input_date) === '') {
        return null;
    }
    $input_date = trim($input_date);
    
    if (is_numeric($input_date) && strlen($input_date) === 4) {
         return false;
    }
    
    $cleaned_date = str_replace('/', '-', $input_date);
    
    $dt_dmy = DateTime::createFromFormat('d-m-Y', $cleaned_date);
    if ($dt_dmy && $dt_dmy->format('d-m-Y') === $cleaned_date) {
        return $dt_dmy->format('Y-m-d');
    }

    $dt_ymd = DateTime::createFromFormat('Y-m-d', $cleaned_date);
    if ($dt_ymd && $dt_ymd->format('Y-m-d') === $cleaned_date) {
        return $dt_ymd->format('Y-m-d');
    }
    
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil data customer
    $customer_name = trim($_POST['customer_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $employee_id = intval($_POST['employee_id'] ?? 0);
    $note = trim($_POST['note'] ?? ''); // General order note
    
    // Ambil data multi-item
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $payment_status = $_POST['payment_status'] ?? 'Belum Lunas';
    $payment_method = trim($_POST['payment_method'] ?? 'Tunai');
    $amount_paid = floatval($_POST['amount_paid'] ?? 0);
    
    // Ambil items array
    $items = $_POST['items'] ?? [];

    try {
        // ===========================================
        // VALIDASI DATA WAJIB
        // ===========================================

        if (empty($customer_name) || empty($phone_number)) {
            throw new Exception('Nama dan nomor HP customer wajib diisi!');
        }

        if (empty($items) || !is_array($items)) {
            throw new Exception('Tidak ada item dalam pesanan!');
        }

        if ($employee_id <= 0) {
            throw new Exception('Karyawan harus dipilih!');
        }

        // ===========================================
        // VALIDASI & PROSES ITEMS
        // ===========================================
        
        $processed_items = [];
        $max_duration = 0;
        $max_est_date = null;
        $earliest_trans_date = null;
        $calculated_total = 0;

        foreach ($items as $index => $item) {
            $service_id = intval($item['service_id'] ?? 0);
            $brand = trim($item['brand'] ?? '');
            $price = floatval($item['price'] ?? 0);
            $duration = intval($item['duration'] ?? 0);
            $status_id = intval($item['status_id'] ?? 1);
            $item_note = trim($item['notes'] ?? ''); // Individual item note
            $input_trans_date = $item['trans_date'] ?? '';
            $input_est_date = $item['est_finish_date'] ?? '';

            if ($service_id <= 0) {
                throw new Exception("Item #{$index}: Layanan harus dipilih!");
            }

            if (empty($brand)) {
                throw new Exception("Item #{$index}: Brand/Merk harus diisi!");
            }

            // Convert dates
            $trans_date = convertDateToDbFormat($input_trans_date);
            if ($trans_date === false) {
                throw new Exception("Item #{$index}: Format tanggal transaksi tidak valid.");
            }
            if ($trans_date === null) {
                $trans_date = date('Y-m-d');
            }

            $est_finish_date = convertDateToDbFormat($input_est_date);
            if ($est_finish_date === false) {
                throw new Exception("Item #{$index}: Format tanggal estimasi tidak valid.");
            }
            if ($est_finish_date === null) {
                $est_finish_date = date('Y-m-d', strtotime($trans_date . " +{$duration} days"));
            }

            // Track max duration and dates
            if ($duration > $max_duration) {
                $max_duration = $duration;
            }

            if ($max_est_date === null || $est_finish_date > $max_est_date) {
                $max_est_date = $est_finish_date;
            }

            if ($earliest_trans_date === null || $trans_date < $earliest_trans_date) {
                $earliest_trans_date = $trans_date;
            }

            $calculated_total += $price;

            $processed_items[] = [
                'service_id' => $service_id,
                'brand' => $brand,
                'price' => $price,
                'duration' => $duration,
                'status_id' => $status_id,
                'trans_date' => $trans_date,
                'est_finish_date' => $est_finish_date,
                'notes' => $item_note // Store individual note
            ];
        }

        error_log("📦 Processed " . count($processed_items) . " items");
        error_log("💰 Total Amount: Rp" . number_format($total_amount, 0, ',', '.'));

        // ===========================================
        // PAYMENT DATE LOGIC
        // ===========================================
        
        $input_payment_date = trim($_POST['payment_date'] ?? '');
        
        if ($payment_status === 'Lunas') {
            if (!empty($input_payment_date)) {
                $payment_date = convertDateToDbFormat($input_payment_date);
                if ($payment_date === false) {
                    throw new Exception("Format tanggal pembayaran tidak valid.");
                }
            } else {
                $payment_date = date('Y-m-d');
            }
        } else {
            $payment_date = null;
        }

        // ===========================================
        // BEGIN TRANSACTION
        // ===========================================

        $conn->begin_transaction();
        
        // ===========================================
        // 1. CEK/SIMPAN CUSTOMER
        // ===========================================
        
        $customer_id = null;
        $stmt_check = $conn->prepare("SELECT id_customer FROM customers WHERE phone = ?");
        if (!$stmt_check) throw new Exception("Prepare failed (check customer): " . $conn->error);
        $stmt_check->bind_param("s", $phone_number);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($result->num_rows > 0) {
            $customer_id = $result->fetch_assoc()['id_customer'];
            $stmt_update = $conn->prepare("UPDATE customers SET name = ? WHERE id_customer = ?");
            if (!$stmt_update) throw new Exception("Prepare failed (update customer): " . $conn->error);
            $stmt_update->bind_param("si", $customer_name, $customer_id);
            $stmt_update->execute();
            $stmt_update->close();
        } else {
            $stmt_customer = $conn->prepare("INSERT INTO customers (name, phone) VALUES (?, ?)");
            if (!$stmt_customer) throw new Exception("Prepare failed (insert customer): " . $conn->error);
            $stmt_customer->bind_param("ss", $customer_name, $phone_number);
            if (!$stmt_customer->execute()) throw new Exception("Insert customer failed: " . $stmt_customer->error);
            $customer_id = $stmt_customer->insert_id;
            $stmt_customer->close();
        }
        $stmt_check->close();
        
        if (is_null($customer_id)) {
            throw new Exception("Gagal mendapatkan ID customer.");
        }

        // ===========================================
        // 2. GENERATE ORDER CODE
        // ===========================================
        
        $order_code = "ORD" . date("ym") . "-" . str_pad(rand(1, 9999), 4, "0", STR_PAD_LEFT);

        // ===========================================
        // 3. SIMPAN KE DROPS (use first item data)
        // ===========================================
        
        $first_item = $processed_items[0];
        $main_service_id = $first_item['service_id'];
        $main_status_id = $first_item['status_id'];
        
        $sql_drop = "
            INSERT INTO drops (
                order_code, customer_id, service_id, brand, 
                trans_date, est_finish_date, status_id, employee_id, notes, total_amount
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt_drop = $conn->prepare($sql_drop);
        if (!$stmt_drop) {
            throw new Exception("Prepare failed (insert drops): " . $conn->error);
        }

        $stmt_drop->bind_param(
            "siisssiisd",
            $order_code,
            $customer_id,
            $main_service_id,
            $first_item['brand'],
            $earliest_trans_date,
            $max_est_date,
            $main_status_id,
            $employee_id,
            $note, // General note
            $total_amount
        );

        if (!$stmt_drop->execute()) {
            throw new Exception("Insert drops failed: " . $stmt_drop->error);
        }

        $drop_id = $stmt_drop->insert_id;
        $stmt_drop->close();

        error_log("✅ Drop created with ID: {$drop_id}");

        // ===========================================
        // 4. SIMPAN SEMUA ITEMS KE DROP_ITEMS (WITH NOTES)
        // ===========================================
        
        $stmt_item = $conn->prepare("
            INSERT INTO drop_items (drop_id, service_id, brand, price, quantity, item_order, notes)
            VALUES (?, ?, ?, ?, 1, ?, ?)
        ");
        if (!$stmt_item) throw new Exception("Prepare failed (insert drop_items): " . $conn->error);

        foreach ($processed_items as $order => $item) {
            $item_order = $order + 1;
            $stmt_item->bind_param(
                "iisdis", 
                $drop_id, 
                $item['service_id'], 
                $item['brand'], 
                $item['price'],
                $item_order,
                $item['notes'] // Individual note per item
            );
            if (!$stmt_item->execute()) {
                throw new Exception("Insert drop_item failed: " . $stmt_item->error);
            }
        }
        $stmt_item->close();

        error_log("✅ " . count($processed_items) . " items inserted with individual notes");

        // ===========================================
        // 5. SIMPAN KE DEADLINES
        // ===========================================
        
        $sql_deadline = "INSERT INTO deadlines (drop_id, deadline_date, status_id) VALUES (?, ?, ?)";
        $stmt_deadline = $conn->prepare($sql_deadline);
        if (!$stmt_deadline) throw new Exception("Prepare failed (insert deadlines): " . $conn->error);
        $stmt_deadline->bind_param("isi", $drop_id, $max_est_date, $main_status_id);
        if (!$stmt_deadline->execute()) throw new Exception("Insert deadlines failed: " . $stmt_deadline->error);
        $stmt_deadline->close();

        // ===========================================
        // 6. SIMPAN KE PAYMENTS
        // ===========================================
        
        if ($payment_date === null) {
            $stmt_payment = $conn->prepare("
                INSERT INTO payments (drop_id, amount_paid, payment_method, payment_date, status)
                VALUES (?, ?, ?, NULL, ?)
            ");
            if (!$stmt_payment) throw new Exception("Prepare failed (insert payments): " . $conn->error);
            $stmt_payment->bind_param("idss", $drop_id, $amount_paid, $payment_method, $payment_status);
        } else {
            $stmt_payment = $conn->prepare("
                INSERT INTO payments (drop_id, amount_paid, payment_method, payment_date, status)
                VALUES (?, ?, ?, STR_TO_DATE(?, '%Y-%m-%d'), ?)
            ");
            if (!$stmt_payment) throw new Exception("Prepare failed (insert payments): " . $conn->error);
            $stmt_payment->bind_param("idsss", $drop_id, $amount_paid, $payment_method, $payment_date, $payment_status);
        }

        if (!$stmt_payment->execute()) {
            throw new Exception("Insert payments failed: " . $stmt_payment->error);
        }
        $stmt_payment->close();

        // ===========================================
        // COMMIT TRANSAKSI
        // ===========================================
        
        $conn->commit();
        error_log("✅✅✅ Multi-Item with Notes Transaction committed successfully!");

        // ===========================================
        // RESPONSE
        // ===========================================
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'drop_id' => $drop_id,
            'order_code' => $order_code,
            'total_items' => count($processed_items),
            'total_amount' => $total_amount,
            'message' => 'Pesanan dengan ' . count($processed_items) . ' item berhasil disimpan!'
        ]);
        exit;

    } catch (Exception $e) {
        // ===========================================
        // ERROR HANDLING
        // ===========================================
        
        $conn->rollback();

        error_log("❌ DROP ADD MULTI-ITEM ERROR: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());

        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}
?>