<?php
include('../db.php');
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

error_log("===========================================");
error_log("DROP ADD - POST DATA RECEIVED:");
error_log(print_r($_POST, true));
error_log("===========================================");

if (isset($_POST['payment_date'])) {
    error_log("✅ payment_date EXISTS in POST: '" . $_POST['payment_date'] . "'");
} else {
    error_log("❌ payment_date NOT FOUND in POST!");
}

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

    // Ambil data dari form
    $customer_name = trim($_POST['customer_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $input_trans_date = $_POST['tanggal_masuk'] ?? '';
    $input_est_finish_date = $_POST['tanggal_selesai'] ?? '';

    try {
        // ===========================================
        // DATE CONVERSION & VALIDATION
        // ===========================================
        
        // 1. Tanggal Masuk (trans_date)
        $trans_date = convertDateToDbFormat($input_trans_date);
        if ($trans_date === false) {
             throw new Exception("Format tanggal masuk tidak valid. Gunakan format DD-MM-YYYY atau YYYY-MM-DD.");
        }
        if ($trans_date === null) {
            $trans_date = date('Y-m-d'); // Default hari ini
        }

        // 2. Tanggal Selesai (est_finish_date)
        $est_finish_date = convertDateToDbFormat($input_est_finish_date);
        if ($est_finish_date === false) {
             throw new Exception("Format tanggal selesai tidak valid. Gunakan format DD-MM-YYYY atau YYYY-MM-DD.");
        }
        if ($est_finish_date === null) {
            $est_finish_date = date('Y-m-d', strtotime($trans_date . ' +3 days'));
        }

        // ===========================================
        // AMBIL DATA LAINNYA
        // ===========================================

        $service_id = intval($_POST['service_id'] ?? 0);
        $brand = trim($_POST['brand'] ?? '');
        $price_min = floatval($_POST['price_min'] ?? 0);
        $amount_paid = floatval($_POST['amount_paid'] ?? 0);
        $payment_method = trim($_POST['payment_method'] ?? 'Tunai');
        $payment_status = $_POST['payment_status'] ?? 'Belum Lunas';
        $status_id = intval($_POST['status_id'] ?? 1);
        $employee_id = intval($_POST['employee_id'] ?? 0);
        $note = trim($_POST['note'] ?? '');

        // ===========================================
        // LOGIKA PAYMENT DATE
        // ===========================================
        
        $input_payment_date = trim($_POST['payment_date'] ?? '');
        
        error_log("=== PAYMENT DATE LOGIC (ADD) ===");
        error_log("Payment Status from POST: " . $payment_status);
        error_log("Input Payment Date from POST: '" . $input_payment_date . "'");
        
        if ($payment_status === 'Lunas') {
            // Status Lunas - WAJIB ada tanggal
            if (!empty($input_payment_date)) {
                // Validasi format
                $payment_date = convertDateToDbFormat($input_payment_date);
                if ($payment_date === false) {
                    throw new Exception("Format tanggal pembayaran tidak valid. Gunakan format YYYY-MM-DD.");
                }
                error_log("✅ Payment date dari form: " . $payment_date);
            } else {
                // Jika kosong, gunakan hari ini
                $payment_date = date('Y-m-d');
                error_log("⚠️ Payment date kosong, menggunakan hari ini: " . $payment_date);
            }
        } else {
            // Status bukan Lunas - tanggal NULL
            $payment_date = null;
            error_log("ℹ️ Payment date set to NULL (status: " . $payment_status . ")");
        }
        
        error_log("FINAL payment_date: " . ($payment_date ?? 'NULL'));

        // ===========================================
        // VALIDASI DATA WAJIB
        // ===========================================

        if (empty($customer_name) || empty($phone_number)) {
            die("<script>alert('Nama dan nomor HP customer wajib diisi!'); window.history.back();</script>");
        }
        if ($service_id <= 0) {
            die("<script>alert('Layanan harus dipilih!'); window.history.back();</script>");
        }
        if ($employee_id <= 0) {
            die("<script>alert('Karyawan harus dipilih!'); window.history.back();</script>");
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
            // Customer sudah ada - update nama
            $customer_id = $result->fetch_assoc()['id_customer'];
            $stmt_update = $conn->prepare("UPDATE customers SET name = ? WHERE id_customer = ?");
            if (!$stmt_update) throw new Exception("Prepare failed (update customer): " . $conn->error);
            $stmt_update->bind_param("si", $customer_name, $customer_id);
            $stmt_update->execute();
            $stmt_update->close();
        } else {
            // Customer baru - insert
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
        // 3. SIMPAN KE DROPS
        // ===========================================
        
        $sql_drop = "
            INSERT INTO drops (
                order_code, customer_id, service_id, brand, 
                trans_date, est_finish_date, status_id, employee_id, note
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt_drop = $conn->prepare($sql_drop);
        if (!$stmt_drop) {
            throw new Exception("Prepare failed (insert drops): " . $conn->error);
        }

        $stmt_drop->bind_param(
            "siisssiis",
            $order_code,
            $customer_id,
            $service_id,
            $brand,
            $trans_date,
            $est_finish_date,
            $status_id,
            $employee_id,
            $note
        );

        if (!$stmt_drop->execute()) {
            throw new Exception("Insert drops failed: " . $stmt_drop->error);
        }

        $drop_id = $stmt_drop->insert_id;
        $stmt_drop->close();

        // ===========================================
        // 4. SIMPAN KE DROP_ITEMS
        // ===========================================
        
        $stmt_item = $conn->prepare("
            INSERT INTO drop_items (drop_id, service_id, brand, price)
            VALUES (?, ?, ?, ?)
        ");
        if (!$stmt_item) throw new Exception("Prepare failed (insert drop_items): " . $conn->error);
        $stmt_item->bind_param("iisd", $drop_id, $service_id, $brand, $price_min);
        if (!$stmt_item->execute()) throw new Exception("Insert drop_items failed: " . $stmt_item->error);
        $stmt_item->close();

        // ===========================================
        // 5. SIMPAN KE DEADLINES
        // ===========================================
        
        $sql_deadline = "INSERT INTO deadlines (drop_id, deadline_date, status_id) VALUES (?, ?, ?)";
        $stmt_deadline = $conn->prepare($sql_deadline);
        if (!$stmt_deadline) throw new Exception("Prepare failed (insert deadlines): " . $conn->error);
        $stmt_deadline->bind_param("isi", $drop_id, $est_finish_date, $status_id);
        if (!$stmt_deadline->execute()) throw new Exception("Insert deadlines failed: " . $stmt_deadline->error);
        $stmt_deadline->close();

        // ===========================================
        // 6. SIMPAN KE PAYMENTS
        // ===========================================
        
        error_log("=== INSERTING TO PAYMENTS TABLE ===");
        error_log("drop_id: " . $drop_id);
        error_log("amount_paid: " . $amount_paid);
        error_log("payment_method: " . $payment_method);
        error_log("payment_date: " . ($payment_date ?? 'NULL'));
        error_log("status: " . $payment_status);
        
        if ($payment_date === null) {
            $stmt_payment = $conn->prepare("
                INSERT INTO payments (drop_id, amount_paid, payment_method, payment_date, status)
                VALUES (?, ?, ?, NULL, ?)
            ");
            if (!$stmt_payment) throw new Exception("Prepare failed (insert payments 1): " . $conn->error);
            $stmt_payment->bind_param("idss", $drop_id, $amount_paid, $payment_method, $payment_status);
        } else {
            $stmt_payment = $conn->prepare("
                INSERT INTO payments (drop_id, amount_paid, payment_method, payment_date, status)
                VALUES (?, ?, ?, STR_TO_DATE(?, '%Y-%m-%d'), ?)
            ");
            if (!$stmt_payment) throw new Exception("Prepare failed (insert payments 2): " . $conn->error);
            $stmt_payment->bind_param("idsss", $drop_id, $amount_paid, $payment_method, $payment_date, $payment_status);
        }

        if (!$stmt_payment->execute()) {
            throw new Exception("Insert payments failed: " . $stmt_payment->error);
        }
        
        error_log("✅ Payment record inserted successfully!");
        $stmt_payment->close();

        // ===========================================
        // COMMIT TRANSAKSI
        // ===========================================
        
        $conn->commit();
        error_log("✅ Transaction committed successfully!");

        // ===========================================
        // RESPONSE - UPDATED FOR SAVE & PRINT
        // ===========================================
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'drop_id' => $drop_id,
            'order_code' => $order_code,
            'message' => 'Pesanan berhasil disimpan!'
        ]);
        error_log("✅ JSON Response sent with drop_id: " . $drop_id);
        exit;

    } catch (Exception $e) {
        // ===========================================
        // ERROR HANDLING
        // ===========================================
        
        $conn->rollback();

        error_log("❌ DROP ADD ERROR: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());

        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        error_log("❌ JSON Error Response sent");
        exit;
    }
}
?>