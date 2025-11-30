<?php
include('../db.php');
session_start();

// Tambahkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * FUNGSI PERBAIKAN: Mengonversi dan memvalidasi tanggal dari format DD-MM-YYYY atau YYYY-MM-DD ke YYYY-MM-DD (format DB).
 * @param string $input_date Tanggal.
 * @return string|null|false Format YYYY-MM-DD, NULL jika input kosong, atau FALSE jika input tidak valid.
 */
function convertDateToDbFormat($input_date) {
    if (empty($input_date) || trim($input_date) === '') {
        return null; // Return NULL jika kosong
    }
    $input_date = trim($input_date);
    
    // Secara eksplisit anggap invalid jika input hanya berupa angka 4 digit (hanya tahun)
    if (is_numeric($input_date) && strlen($input_date) === 4) {
         return false;
    }
    
    // Ganti pemisah '/' menjadi '-' untuk konsistensi validasi
    $cleaned_date = str_replace('/', '-', $input_date);
    
    // 1. Coba format DD-MM-YYYY (d-m-Y)
    $dt_dmy = DateTime::createFromFormat('d-m-Y', $cleaned_date);
    if ($dt_dmy && $dt_dmy->format('d-m-Y') === $cleaned_date) {
        return $dt_dmy->format('Y-m-d');
    }

    // 2. Coba format YYYY-MM-DD (Y-m-d)
    $dt_ymd = DateTime::createFromFormat('Y-m-d', $cleaned_date);
    if ($dt_ymd && $dt_ymd->format('Y-m-d') === $cleaned_date) {
        return $dt_ymd->format('Y-m-d');
    }
    
    // Jika semua validasi gagal
    return false; 
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil data dari form
    $customer_name = trim($_POST['customer_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    
    // Input mentah dari form
    $input_trans_date = $_POST['tanggal_masuk'] ?? '';
    $input_est_finish_date = $_POST['tanggal_selesai'] ?? '';
    // ✅ INPUT PAYMENT_DATE DIABAIKAN - Logika sepenuhnya di backend

    try {
        // ===========================================
        // DATE CONVERSION & VALIDATION
        // ===========================================
        
        // 1. Tanggal Masuk (trans_date) - Wajib, default Hari Ini jika kosong
        $trans_date = convertDateToDbFormat($input_trans_date);
        if ($trans_date === false) {
             throw new Exception("Format tanggal masuk ('Tanggal Masuk') tidak valid. Harap gunakan format DD-MM-YYYY atau YYYY-MM-DD.");
        }
        if ($trans_date === null) {
            $trans_date = date('Y-m-d'); // Default ke hari ini
        }

        // 2. Tanggal Selesai (est_finish_date) - Wajib karena kolom DB NOT NULL.
        $est_finish_date = convertDateToDbFormat($input_est_finish_date);
        if ($est_finish_date === false) {
             throw new Exception("Format tanggal selesai ('Tanggal Selesai') tidak valid. Harap gunakan format DD-MM-YYYY atau YYYY-MM-DD.");
        }
        // ✅ PERBAIKAN UTAMA: Jika input kosong (NULL), default ke 3 hari dari tanggal masuk
        if ($est_finish_date === null) {
            $est_finish_date = date('Y-m-d', strtotime($trans_date . ' +3 days'));
        }

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

        
        // ✅ LOGIKA TANGGAL PEMBAYARAN (FINAL - ADD) - TANGGAL TERKUNCI
        // Pada add: set tanggal HANYA saat status Lunas, jika bukan Lunas = NULL
        // Input dari form DIABAIKAN sepenuhnya
        if ($payment_status === 'Lunas') {
            // Set tanggal pembayaran = hari ini saat pertama kali di-set Lunas
            $payment_date = date('Y-m-d');
        } else {
            // Jika bukan Lunas, tanggal pembayaran NULL
            $payment_date = null;
        }



        // Validasi data wajib
        if (empty($customer_name) || empty($phone_number)) {
            die("<script>alert('Nama dan nomor HP customer wajib diisi!'); window.history.back();</script>");
        }
        if ($service_id <= 0) {
            die("<script>alert('Layanan harus dipilih!'); window.history.back();</script>");
        }
        if ($employee_id <= 0) {
            die("<script>alert('Karyawan harus dipilih!'); window.history.back();</script>");
        }


        $conn->begin_transaction();

        // 1. CEK/SIMPAN CUSTOMER
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

        // 2. GENERATE ORDER CODE
        $order_code = "ORD" . date("ym") . "-" . str_pad(rand(1, 9999), 4, "0", STR_PAD_LEFT);

        // 3. SIMPAN KE DROPS
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

        // est_finish_date dijamin berupa tanggal YYYY-MM-DD
        $stmt_drop->bind_param(
            "siisssiis",
            $order_code,
            $customer_id,
            $service_id,
            $brand,
            $trans_date, // YYYY-MM-DD
            $est_finish_date, // YYYY-MM-DD (DULU BISA NULL, SEKARANG WAJIB DATE)
            $status_id,
            $employee_id,
            $note
        );

        if (!$stmt_drop->execute()) {
            throw new Exception("Insert drops failed: " . $stmt_drop->error);
        }

        $drop_id = $stmt_drop->insert_id;
        $stmt_drop->close();

        // 4. SIMPAN KE DROP_ITEMS
        $stmt_item = $conn->prepare("
            INSERT INTO drop_items (drop_id, service_id, brand, price)
            VALUES (?, ?, ?, ?)
        ");
        if (!$stmt_item) throw new Exception("Prepare failed (insert drop_items): " . $conn->error);
        $stmt_item->bind_param("iisd", $drop_id, $service_id, $brand, $price_min);
        if (!$stmt_item->execute()) throw new Exception("Insert drop_items failed: " . $stmt_item->error);
        $stmt_item->close();

        // 5. SIMPAN KE DEADLINES
        // est_finish_date (deadline_date) dijamin BUKAN NULL
        $sql_deadline = "INSERT INTO deadlines (drop_id, deadline_date, status_id) VALUES (?, ?, ?)";
        $stmt_deadline = $conn->prepare($sql_deadline);
        if (!$stmt_deadline) throw new Exception("Prepare failed (insert deadlines): " . $conn->error);
        $stmt_deadline->bind_param("isi", $drop_id, $est_finish_date, $status_id);
        if (!$stmt_deadline->execute()) throw new Exception("Insert deadlines failed: " . $stmt_deadline->error);
        $stmt_deadline->close();


        // 6. SIMPAN KE PAYMENTS
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
                VALUES (?, ?, ?, ?, ?)
            ");
            if (!$stmt_payment) throw new Exception("Prepare failed (insert payments 2): " . $conn->error);
            $stmt_payment->bind_param("idsss", $drop_id, $amount_paid, $payment_method, $payment_date, $payment_status);
        }

        if (!$stmt_payment->execute()) {
            throw new Exception("Insert payments failed: " . $stmt_payment->error);
        }
        $stmt_payment->close();

        // COMMIT TRANSAKSI
        $conn->commit();

        // Response untuk print request
        if (isset($_GET['print']) && $_GET['print'] == '1') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'drop_id' => $drop_id, 'message' => 'Data berhasil disimpan!']);
            exit;
        }

        // Redirect normal
        echo "
        <script>
            sessionStorage.setItem('showSuccess', 'true');
            sessionStorage.setItem('successMessage', 'Data berhasil disimpan!');
            window.location.href = 'drop.php';
        </script>";
        exit;

    } catch (Exception $e) {
        $conn->rollback();

        // Log error detail untuk debugging server
        error_log("DROP ADD ERROR: " . $e->getMessage());

        if (isset($_GET['print']) && $_GET['print'] == '1') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }

        // Alert untuk request biasa
        echo "
        <script>
            alert('Gagal menyimpan data: " . addslashes($e->getMessage()) . "');
            window.history.back();
        </script>";
        exit;
    }
}
?>