<?php
include('../db.php');
session_start();

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    error_log("=== DROP ADD - NEW REQUEST ===");
    error_log("POST DATA: " . print_r($_POST, true));

    // ========================================
    // HELPER FUNCTION
    // ========================================
    function convertDateToDbFormat($input_date) {
        if (empty($input_date) || trim($input_date) === '') {
            return null;
        }
        
        $input_date = trim($input_date);
        
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input_date)) {
            $dt = DateTime::createFromFormat('Y-m-d', $input_date);
            if ($dt && $dt->format('Y-m-d') === $input_date) {
                return $input_date;
            }
        }
        
        $cleaned = str_replace('/', '-', $input_date);
        $dt = DateTime::createFromFormat('d-m-Y', $cleaned);
        if ($dt && $dt->format('d-m-Y') === $cleaned) {
            return $dt->format('Y-m-d');
        }
        
        return false;
    }

    // ========================================
    // VALIDASI FORM DATA
    // ========================================
    $required_fields = [
        'customer_name' => 'Nama pelanggan',
        'phone_number' => 'Nomor HP',
        'brand' => 'Brand/Merk',
        'service_id' => 'Layanan',
        'employee_id' => 'Karyawan',
        'payment_status' => 'Status pembayaran',
        'payment_method' => 'Metode pembayaran'
    ];

    foreach ($required_fields as $field => $label) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$label' wajib diisi!");
        }
    }

    // ========================================
    // EXTRACT & CLEAN DATA
    // ========================================
    $customer_name = trim($_POST['customer_name']);
    $phone_number = trim($_POST['phone_number']);
    $brand = trim($_POST['brand']);
    $service_id = intval($_POST['service_id']);
    $employee_id = intval($_POST['employee_id']);
    $status_id = intval($_POST['status_id'] ?? 1);
    $note = trim($_POST['note'] ?? '');
    
    // Financial data
    $price_min = floatval($_POST['price_min'] ?? 0);
    $amount_paid = floatval($_POST['amount_paid'] ?? 0);
    $payment_method = trim($_POST['payment_method']);
    $payment_status = trim($_POST['payment_status']);

    // ========================================
    // DATE CONVERSION
    // ========================================
    
    $trans_date = convertDateToDbFormat($_POST['tanggal_masuk'] ?? '');
    if ($trans_date === false) {
        throw new Exception("Format tanggal transaksi tidak valid");
    }
    if ($trans_date === null) {
        $trans_date = date('Y-m-d');
    }
    
    $est_finish_date = convertDateToDbFormat($_POST['tanggal_selesai'] ?? '');
    if ($est_finish_date === false) {
        throw new Exception("Format tanggal estimasi selesai tidak valid");
    }
    if ($est_finish_date === null) {
        $est_finish_date = date('Y-m-d', strtotime($trans_date . ' +3 days'));
    }
    
    $payment_date = null;
    
    error_log("Payment Status: $payment_status");
    error_log("Payment Date Input: " . ($_POST['payment_date'] ?? 'EMPTY'));
    
    if ($payment_status === 'Lunas') {
        $input_payment_date = trim($_POST['payment_date'] ?? '');
        
        if (!empty($input_payment_date)) {
            $payment_date = convertDateToDbFormat($input_payment_date);
            if ($payment_date === false) {
                throw new Exception("Format tanggal pembayaran tidak valid");
            }
            error_log("✅ Payment date from form: $payment_date");
        } else {
            $payment_date = date('Y-m-d');
            error_log("⚠️ Payment date auto-set to today: $payment_date");
        }
    } else {
        $payment_date = null;
        error_log("ℹ️ Payment date set to NULL (status: $payment_status)");
    }

    // ========================================
    // VALIDATION
    // ========================================
    if ($service_id <= 0) {
        throw new Exception("Layanan tidak valid");
    }
    if ($employee_id <= 0) {
        throw new Exception("Karyawan tidak valid");
    }
    if (strlen($phone_number) < 10) {
        throw new Exception("Nomor HP tidak valid (minimal 10 digit)");
    }

    // ========================================
    // BEGIN TRANSACTION
    // ========================================
    $conn->begin_transaction();
    
    // ========================================
    // 1. CHECK/INSERT CUSTOMER - FIX PASSWORD ISSUE
    // ========================================
    $customer_id = null;
    
    $stmt = $conn->prepare("SELECT id_customer FROM customers WHERE phone = ?");
    $stmt->bind_param("s", $phone_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Existing customer - update name only
        $customer_id = $result->fetch_assoc()['id_customer'];
        
        $stmt_update = $conn->prepare("UPDATE customers SET name = ? WHERE id_customer = ?");
        $stmt_update->bind_param("si", $customer_name, $customer_id);
        $stmt_update->execute();
        $stmt_update->close();
        
        error_log("✅ Customer updated: ID $customer_id");
    } else {
        // New customer - INSERT DENGAN HANDLING PASSWORD
        // Cek apakah tabel customers punya kolom password
        $check_columns = $conn->query("SHOW COLUMNS FROM customers LIKE 'password'");
        
        if ($check_columns->num_rows > 0) {
            // Tabel customers PUNYA kolom password - insert dengan default
            error_log("⚠️ Table customers has password column, setting default");
            
            $stmt_insert = $conn->prepare("INSERT INTO customers (name, phone, password) VALUES (?, ?, '')");
            $stmt_insert->bind_param("ss", $customer_name, $phone_number);
        } else {
            // Tabel customers TIDAK punya kolom password - insert normal
            error_log("✅ Table customers does not have password column");
            
            $stmt_insert = $conn->prepare("INSERT INTO customers (name, phone) VALUES (?, ?)");
            $stmt_insert->bind_param("ss", $customer_name, $phone_number);
        }
        
        $stmt_insert->execute();
        $customer_id = $stmt_insert->insert_id;
        $stmt_insert->close();
        
        error_log("✅ New customer created: ID $customer_id");
    }
    $stmt->close();

    // ========================================
    // 2. GENERATE ORDER CODE
    // ========================================
    $order_code = "ORD" . date("ym") . "-" . str_pad(rand(1, 9999), 4, "0", STR_PAD_LEFT);
    error_log("Order code: $order_code");

    // ========================================
    // 3. INSERT DROPS
    // ========================================
    $sql_drop = "
        INSERT INTO drops (
            order_code, customer_id, service_id, brand, 
            trans_date, est_finish_date, status_id, employee_id, note
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt_drop = $conn->prepare($sql_drop);
    if (!$stmt_drop) {
        throw new Exception("Prepare failed (drops): " . $conn->error);
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
    error_log("✅ Drop inserted: ID $drop_id");

    // ========================================
    // 4. INSERT DROP_ITEMS
    // ========================================
    $stmt_item = $conn->prepare("
        INSERT INTO drop_items (drop_id, service_id, brand, price)
        VALUES (?, ?, ?, ?)
    ");
    $stmt_item->bind_param("iisd", $drop_id, $service_id, $brand, $price_min);
    
    if (!$stmt_item->execute()) {
        throw new Exception("Insert drop_items failed: " . $stmt_item->error);
    }
    $stmt_item->close();
    error_log("✅ Drop items inserted");

    // ========================================
    // 5. INSERT DEADLINES
    // ========================================
    $stmt_deadline = $conn->prepare("
        INSERT INTO deadlines (drop_id, deadline_date, status_id)
        VALUES (?, ?, ?)
    ");
    $stmt_deadline->bind_param("isi", $drop_id, $est_finish_date, $status_id);
    
    if (!$stmt_deadline->execute()) {
        throw new Exception("Insert deadlines failed: " . $stmt_deadline->error);
    }
    $stmt_deadline->close();
    error_log("✅ Deadline inserted");

    // ========================================
    // 6. INSERT PAYMENTS
    // ========================================
    error_log("=== INSERTING PAYMENTS ===");
    error_log("Payment date value: " . ($payment_date ?? 'NULL'));
    
    $sql_payment = "
        INSERT INTO payments (drop_id, amount_paid, payment_method, payment_date, status)
        VALUES (?, ?, ?, ?, ?)
    ";
    
    $stmt_payment = $conn->prepare($sql_payment);
    if (!$stmt_payment) {
        throw new Exception("Prepare failed (payments): " . $conn->error);
    }
    
    $stmt_payment->bind_param(
        "idsss",
        $drop_id,
        $amount_paid,
        $payment_method,
        $payment_date,
        $payment_status
    );
    
    if (!$stmt_payment->execute()) {
        throw new Exception("Insert payments failed: " . $stmt_payment->error);
    }
    $stmt_payment->close();
    error_log("✅ Payment inserted successfully");

    // ========================================
    // COMMIT TRANSACTION
    // ========================================
    $conn->commit();
    error_log("✅ TRANSACTION COMMITTED");

    // ========================================
    // SUCCESS RESPONSE
    // ========================================
    echo json_encode([
        'success' => true,
        'drop_id' => $drop_id,
        'order_code' => $order_code,
        'message' => 'Pesanan berhasil disimpan!'
    ]);
    
    error_log("✅ SUCCESS - Drop ID: $drop_id");

} catch (Exception $e) {
    // ========================================
    // ERROR HANDLING
    // ========================================
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    
    error_log("❌ ERROR: " . $e->getMessage());
    error_log("Stack: " . $e->getTraceAsString());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>