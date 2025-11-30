<?php
include('../db.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * FUNGSI PERBAIKAN: Mengonversi dan memvalidasi tanggal dari format DD-MM-YYYY atau YYYY-MM-DD ke YYYY-MM-DD (format DB).
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
    $id_drop = intval($_POST['id_drop'] ?? 0); 
    $customer_name = trim($_POST['customer_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $service_id = intval($_POST['service_id'] ?? 0); 
    $employee_id = intval($_POST['employee_id'] ?? 0); 
    $status_id = intval($_POST['status_id'] ?? 1); 
    $payment_status = $_POST['payment_status'] ?? 'Belum Lunas';
    $payment_method = trim($_POST['payment_method'] ?? 'Tunai');
    $amount_paid = floatval($_POST['amount_paid'] ?? 0);
    $note = trim($_POST['note'] ?? '');
    
    $input_trans_date = $_POST['tanggal_masuk'] ?? '';
    $input_est_finish_date = $_POST['tanggal_selesai'] ?? '';
    // ✅ PENTING: Input payment_date dari form DIABAIKAN, logika sepenuhnya di backend

    try {
        // ===========================================
        // DATE CONVERSION & VALIDATION
        // ===========================================
        
        $trans_date = convertDateToDbFormat($input_trans_date);
        if ($trans_date === false) {
             throw new Exception("Format tanggal masuk tidak valid. Harap gunakan format DD-MM-YYYY atau YYYY-MM-DD.");
        }
        if ($trans_date === null) {
            throw new Exception("Tanggal Masuk wajib diisi.");
        }

        $est_finish_date = convertDateToDbFormat($input_est_finish_date);
        if ($est_finish_date === false) {
             throw new Exception("Format tanggal selesai tidak valid. Harap gunakan format DD-MM-YYYY atau YYYY-MM-DD.");
        }
        
        if ($est_finish_date === null) {
            $stmt_old_date = $conn->prepare("SELECT est_finish_date FROM drops WHERE id_drop = ?");
            $stmt_old_date->bind_param("i", $id_drop);
            $stmt_old_date->execute();
            $result_old_date = $stmt_old_date->get_result();
            $old_date = $result_old_date->fetch_assoc();
            $stmt_old_date->close();

            if ($old_date && $old_date['est_finish_date']) {
                $est_finish_date = $old_date['est_finish_date'];
            } else {
                $est_finish_date = date('Y-m-d', strtotime($trans_date . ' +3 days'));
            }
        }
        // ===========================================

        // ===============================
        // AMBIL PAYMENT LAMA (PENTING)
        // ===============================
        $stmt_old_payment = $conn->prepare("SELECT status, payment_date FROM payments WHERE drop_id = ?");
        if (!$stmt_old_payment) throw new Exception("Prepare failed (select old payment): " . $conn->error);
        $stmt_old_payment->bind_param("i", $id_drop);
        $stmt_old_payment->execute();
        $oldPaymentRes = $stmt_old_payment->get_result();
        $oldPayment = $oldPaymentRes->fetch_assoc();
        $stmt_old_payment->close();

        $old_payment_status = $oldPayment['status'] ?? null;
        $old_payment_date = $oldPayment['payment_date'] ?? null;

        // ✅ LOGIKA TANGGAL PEMBAYARAN (FINAL - EDIT) - TANGGAL TERKUNCI
        // Aturan KETAT:
        // 1. Jika sekarang Lunas DAN sebelumnya BUKAN Lunas => set payment_date = TODAY (tanggal pertama kali lunas)
        // 2. Jika sekarang Lunas DAN sebelumnya SUDAH Lunas => JANGAN UBAH payment_date (TERKUNCI di tanggal pertama)
        // 3. Jika sekarang BUKAN Lunas => payment_date = NULL (hapus tanggal)
        
        if ($payment_status === 'Lunas') {
            if ($old_payment_status !== 'Lunas') {
                // Baru diubah ke Lunas sekarang => set tanggal hari ini (pertama kali lunas)
                $payment_date = date('Y-m-d');
            } else {
                // Sudah Lunas sebelumnya => KUNCI tanggal lama, JANGAN DIUBAH
                $payment_date = $old_payment_date;
            }
        } else {
            // Bukan Lunas => kosongkan tanggal pembayaran
            $payment_date = null;
        }

        if ($id_drop === 0) {
            die("<script>alert('Error: ID Drop tidak valid.'); window.history.back();</script>");
        }

        $conn->begin_transaction();

        // Ambil customer_id
        $stmt_drop_cust = $conn->prepare("SELECT customer_id FROM drops WHERE id_drop = ?");
        if (!$stmt_drop_cust) throw new Exception("Prepare failed (select drop customer): " . $conn->error);
        $stmt_drop_cust->bind_param("i", $id_drop);
        $stmt_drop_cust->execute();
        $result_drop_cust = $stmt_drop_cust->get_result();
        if ($result_drop_cust->num_rows === 0) throw new Exception("Data order tidak ditemukan.");
        $customer_id = $result_drop_cust->fetch_assoc()['customer_id'];
        $stmt_drop_cust->close();

        // 1. UPDATE CUSTOMER
        $stmt_update_cust = $conn->prepare("UPDATE customers SET name = ?, phone = ? WHERE id_customer = ?");
        if (!$stmt_update_cust) throw new Exception("Prepare failed (update customer): " . $conn->error);
        $stmt_update_cust->bind_param("ssi", $customer_name, $phone_number, $customer_id);
        if (!$stmt_update_cust->execute()) throw new Exception("Update customer failed: " . $stmt_update_cust->error);
        $stmt_update_cust->close();
        
        // 2. UPDATE DROPS
        $stmt = $conn->prepare("
            UPDATE drops 
            SET service_id = ?, employee_id = ?, brand = ?, trans_date = ?, status_id = ?, note = ?, est_finish_date = ?
            WHERE id_drop = ?
        ");
        if (!$stmt) throw new Exception("Prepare failed (update drops): " . $conn->error);
        $stmt->bind_param("iisssssi", $service_id, $employee_id, $brand, $trans_date, $status_id, $note, $est_finish_date, $id_drop);
        if (!$stmt->execute()) throw new Exception("Update drops failed: " . $stmt->error);
        $stmt->close();

        // 3. UPDATE/INSERT PAYMENTS
        $stmt_check = $conn->prepare("SELECT id_payment FROM payments WHERE drop_id = ?");
        if (!$stmt_check) throw new Exception("Prepare failed (select payment): " . $conn->error);
        $stmt_check->bind_param("i", $id_drop);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $payment = $result_check->fetch_assoc();
        $stmt_check->close();

        if ($payment) {
            // UPDATE PAYMENT
            if (is_null($payment_date)) {
                $stmt = $conn->prepare("
                    UPDATE payments 
                    SET amount_paid = ?, payment_method = ?, payment_date = NULL, status = ?
                    WHERE drop_id = ?
                ");
                if (!$stmt) throw new Exception("Prepare failed (update payments - NULL date): " . $conn->error);
                $stmt->bind_param("dssi", $amount_paid, $payment_method, $payment_status, $id_drop);
            } else {
                $stmt = $conn->prepare("
                    UPDATE payments 
                    SET amount_paid = ?, payment_method = ?, payment_date = ?, status = ?
                    WHERE drop_id = ?
                ");
                if (!$stmt) throw new Exception("Prepare failed (update payments): " . $conn->error);
                $stmt->bind_param("dsssi", $amount_paid, $payment_method, $payment_date, $payment_status, $id_drop);
            }
            if (!$stmt->execute()) throw new Exception("Update payment failed: " . $stmt->error);
            $stmt->close();

        } elseif ($amount_paid > 0 || $payment_status === 'Lunas') {
            // INSERT PAYMENT
            if (is_null($payment_date)) {
                $stmt = $conn->prepare("
                    INSERT INTO payments (drop_id, amount_paid, payment_method, payment_date, status)
                    VALUES (?, ?, ?, NULL, ?)
                ");
                if (!$stmt) throw new Exception("Prepare failed (insert payment - NULL date): " . $conn->error);
                $stmt->bind_param("idss", $id_drop, $amount_paid, $payment_method, $payment_status);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO payments (drop_id, amount_paid, payment_method, payment_date, status)
                    VALUES (?, ?, ?, ?, ?)
                ");
                if (!$stmt) throw new Exception("Prepare failed (insert payment): " . $conn->error);
                $stmt->bind_param("idsss", $id_drop, $amount_paid, $payment_method, $payment_date, $payment_status);
            }
            if (!$stmt->execute()) throw new Exception("Insert payment failed: " . $stmt->error);
            $stmt->close();
        }

        // 4. UPDATE DEADLINES
        $stmt = $conn->prepare("UPDATE deadlines SET deadline_date = ?, status_id = ? WHERE drop_id = ?");
        if (!$stmt) throw new Exception("Prepare failed (update deadlines): " . $conn->error);
        $stmt->bind_param("sii", $est_finish_date, $status_id, $id_drop); 
        if (!$stmt->execute()) throw new Exception("Update deadlines failed: " . $stmt->error);
        $stmt->close();
        
        $conn->commit();

        echo "
        <script>
            sessionStorage.setItem('showSuccess', 'true');
            sessionStorage.setItem('successMessage', 'Data berhasil diperbarui!');
            window.location.href = 'drop.php';
        </script>";
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("DROP EDIT ERROR: " . $e->getMessage());
        
        echo "
        <script>
            alert('Gagal mengupdate data: " . addslashes($e->getMessage()) . "');
            window.history.back();
        </script>";
        exit;
    }
}
?>