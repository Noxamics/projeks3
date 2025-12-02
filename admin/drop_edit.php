<?php
// ==========================================
// FILE: drop_edit.php - FIXED VERSION
// ==========================================

include('../db.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// DEBUG: Log semua info request
error_log("===========================================");
error_log("DROP EDIT - REQUEST INFO:");
error_log("METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("POST DATA: " . json_encode($_POST));
error_log("===========================================");

if (isset($_POST['payment_date'])) {
    error_log("✅ payment_date EXISTS in POST: '" . $_POST['payment_date'] . "'");
} else {
    error_log("❌ payment_date NOT FOUND in POST!");
}

/**
 * Fungsi konversi tanggal ke format database (YYYY-MM-DD)
 */
function convertDateToDbFormatEdit($input_date) {
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
    
    // DEBUG: Log ID Drop
    error_log("ID Drop from POST: " . isset($_POST['id_drop']) ? $_POST['id_drop'] : 'NOT SET');
    
    $id_drop = isset($_POST['id_drop']) ? intval($_POST['id_drop']) : 0;
    
    // VALIDASI ID DROP TERLEBIH DAHULU
    if ($id_drop <= 0) {
        error_log("❌ INVALID ID Drop: " . $id_drop);
        die("<script>alert('Error: ID Drop tidak valid. ID: " . htmlspecialchars($_POST['id_drop'] ?? '') . "'); window.history.back();</script>");
    }

    error_log("✅ Valid ID Drop: " . $id_drop);

    // Ambil data dari form
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

    try {
        // ===========================================
        // DATE CONVERSION & VALIDATION
        // ===========================================
        
        $trans_date = convertDateToDbFormatEdit($input_trans_date);
        if ($trans_date === false) {
             throw new Exception("Format tanggal masuk tidak valid. Gunakan format DD-MM-YYYY atau YYYY-MM-DD.");
        }
        if ($trans_date === null) {
            throw new Exception("Tanggal Masuk wajib diisi.");
        }

        $est_finish_date = convertDateToDbFormatEdit($input_est_finish_date);
        if ($est_finish_date === false) {
             throw new Exception("Format tanggal selesai tidak valid. Gunakan format DD-MM-YYYY atau YYYY-MM-DD.");
        }
        
        if ($est_finish_date === null) {
            $stmt_old_date = $conn->prepare("SELECT est_finish_date FROM drops WHERE id_drop = ?");
            if (!$stmt_old_date) throw new Exception("Prepare failed: " . $conn->error);
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
        // AMBIL PAYMENT LAMA (PENTING UNTUK LOCKING)
        // ===========================================
        
        $stmt_old_payment = $conn->prepare("SELECT status, payment_date FROM payments WHERE drop_id = ?");
        if (!$stmt_old_payment) throw new Exception("Prepare failed (select old payment): " . $conn->error);
        $stmt_old_payment->bind_param("i", $id_drop);
        if (!$stmt_old_payment->execute()) throw new Exception("Execute failed: " . $stmt_old_payment->error);
        
        $oldPaymentRes = $stmt_old_payment->get_result();
        $oldPayment = $oldPaymentRes->fetch_assoc();
        $stmt_old_payment->close();

        $old_payment_status = $oldPayment['status'] ?? null;
        $old_payment_date = $oldPayment['payment_date'] ?? null;

        error_log("=== PAYMENT DATE LOGIC (EDIT) ===");
        error_log("Old Payment Status: " . ($old_payment_status ?? 'NULL'));
        error_log("Old Payment Date: " . ($old_payment_date ?? 'NULL'));
        error_log("New Payment Status: " . $payment_status);

        // ===========================================
        // LOGIKA PAYMENT DATE (EDIT)
        // ===========================================
        
        $input_payment_date = trim($_POST['payment_date'] ?? '');
        error_log("Input Payment Date from POST: '" . $input_payment_date . "'");
        
        if ($payment_status === 'Lunas') {
            if ($old_payment_status === 'Lunas') {
                // SUDAH Lunas sebelumnya - LOCK tanggal lama
                $payment_date = $old_payment_date;
                error_log("🔐 LOCKED: Menggunakan tanggal lama (sudah Lunas): " . $payment_date);
            } else {
                // BARU diubah ke Lunas - set tanggal baru
                if (!empty($input_payment_date)) {
                    $payment_date = convertDateToDbFormatEdit($input_payment_date);
                    if ($payment_date === false) {
                        throw new Exception("Format tanggal pembayaran tidak valid.");
                    }
                    error_log("✅ Menggunakan tanggal dari form (baru Lunas): " . $payment_date);
                } else {
                    $payment_date = date('Y-m-d');
                    error_log("⚠️ Kosong, menggunakan hari ini (baru Lunas): " . $payment_date);
                }
            }
        } else {
            // Bukan Lunas - clear tanggal
            $payment_date = null;
            error_log("ℹ️ Bukan Lunas - payment_date set to NULL");
        }
        
        error_log("FINAL payment_date: " . ($payment_date ?? 'NULL'));

        // ===========================================
        // BEGIN TRANSACTION
        // ===========================================

        $conn->begin_transaction();

        // ===========================================
        // 1. AMBIL CUSTOMER_ID & CECK DROP EXIST
        // ===========================================
        
        $stmt_drop_cust = $conn->prepare("SELECT customer_id FROM drops WHERE id_drop = ?");
        if (!$stmt_drop_cust) throw new Exception("Prepare failed (select drop customer): " . $conn->error);
        $stmt_drop_cust->bind_param("i", $id_drop);
        if (!$stmt_drop_cust->execute()) throw new Exception("Execute failed: " . $stmt_drop_cust->error);
        
        $result_drop_cust = $stmt_drop_cust->get_result();
        if ($result_drop_cust->num_rows === 0) {
            throw new Exception("Data order dengan ID " . $id_drop . " tidak ditemukan di database.");
        }
        
        $customer_id = $result_drop_cust->fetch_assoc()['customer_id'];
        $stmt_drop_cust->close();

        error_log("✅ Found customer_id: " . $customer_id);

        // ===========================================
        // 2. UPDATE CUSTOMER
        // ===========================================
        
        $stmt_update_cust = $conn->prepare("UPDATE customers SET name = ?, phone = ? WHERE id_customer = ?");
        if (!$stmt_update_cust) throw new Exception("Prepare failed (update customer): " . $conn->error);
        $stmt_update_cust->bind_param("ssi", $customer_name, $phone_number, $customer_id);
        if (!$stmt_update_cust->execute()) throw new Exception("Update customer failed: " . $stmt_update_cust->error);
        $stmt_update_cust->close();
        
        error_log("✅ Customer updated");

        // ===========================================
        // 3. UPDATE DROPS
        // ===========================================
        
        $stmt = $conn->prepare("
            UPDATE drops 
            SET service_id = ?, employee_id = ?, brand = ?, trans_date = ?, status_id = ?, note = ?, est_finish_date = ?
            WHERE id_drop = ?
        ");
        if (!$stmt) throw new Exception("Prepare failed (update drops): " . $conn->error);
        $stmt->bind_param("iisssssi", $service_id, $employee_id, $brand, $trans_date, $status_id, $note, $est_finish_date, $id_drop);
        if (!$stmt->execute()) throw new Exception("Update drops failed: " . $stmt->error);
        $stmt->close();

        error_log("✅ Drops updated");

        // ===========================================
        // 4. UPDATE/INSERT PAYMENTS
        // ===========================================
        
        $stmt_check = $conn->prepare("SELECT id_payment FROM payments WHERE drop_id = ?");
        if (!$stmt_check) throw new Exception("Prepare failed (select payment): " . $conn->error);
        $stmt_check->bind_param("i", $id_drop);
        if (!$stmt_check->execute()) throw new Exception("Execute failed: " . $stmt_check->error);
        
        $result_check = $stmt_check->get_result();
        $payment = $result_check->fetch_assoc();
        $stmt_check->close();

        error_log("=== UPDATING PAYMENTS TABLE ===");
        error_log("drop_id: " . $id_drop);
        error_log("amount_paid: " . $amount_paid);
        error_log("payment_method: " . $payment_method);
        error_log("payment_date: " . ($payment_date ?? 'NULL'));
        error_log("status: " . $payment_status);

        if ($payment) {
            // UPDATE existing payment
            error_log("📝 Updating existing payment record");
            
            if (is_null($payment_date)) {
                $stmt = $conn->prepare("
                    UPDATE payments 
                    SET amount_paid = ?, payment_method = ?, payment_date = NULL, status = ?
                    WHERE drop_id = ?
                ");
                if (!$stmt) throw new Exception("Prepare failed (update payments - NULL): " . $conn->error);
                $stmt->bind_param("dssi", $amount_paid, $payment_method, $payment_status, $id_drop);
            } else {
                $stmt = $conn->prepare("
                    UPDATE payments 
                    SET amount_paid = ?, payment_method = ?, payment_date = STR_TO_DATE(?, '%Y-%m-%d'), status = ?
                    WHERE drop_id = ?
                ");
                if (!$stmt) throw new Exception("Prepare failed (update payments): " . $conn->error);
                $stmt->bind_param("dsssi", $amount_paid, $payment_method, $payment_date, $payment_status, $id_drop);
            }
            if (!$stmt->execute()) throw new Exception("Update payment failed: " . $stmt->error);
            error_log("✅ Payment record UPDATED successfully!");
            $stmt->close();

        } else {
            // INSERT new payment jika ada amount atau status Lunas
            if ($amount_paid > 0 || $payment_status === 'Lunas') {
                error_log("📝 Inserting new payment record");
                
                if (is_null($payment_date)) {
                    $stmt = $conn->prepare("
                        INSERT INTO payments (drop_id, amount_paid, payment_method, payment_date, status)
                        VALUES (?, ?, ?, NULL, ?)
                    ");
                    if (!$stmt) throw new Exception("Prepare failed (insert payments - NULL): " . $conn->error);
                    $stmt->bind_param("idss", $id_drop, $amount_paid, $payment_method, $payment_status);
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO payments (drop_id, amount_paid, payment_method, payment_date, status)
                        VALUES (?, ?, ?, STR_TO_DATE(?, '%Y-%m-%d'), ?)
                    ");
                    if (!$stmt) throw new Exception("Prepare failed (insert payments): " . $conn->error);
                    $stmt->bind_param("idsss", $id_drop, $amount_paid, $payment_method, $payment_date, $payment_status);
                }
                if (!$stmt->execute()) throw new Exception("Insert payment failed: " . $stmt->error);
                error_log("✅ Payment record INSERTED successfully!");
                $stmt->close();
            } else {
                error_log("⏭️ Skipping payment insert - amount 0 dan status tidak Lunas");
            }
        }

        // ===========================================
        // 5. UPDATE DEADLINES
        // ===========================================
        
        $stmt = $conn->prepare("UPDATE deadlines SET deadline_date = ?, status_id = ? WHERE drop_id = ?");
        if (!$stmt) throw new Exception("Prepare failed (update deadlines): " . $conn->error);
        $stmt->bind_param("sii", $est_finish_date, $status_id, $id_drop); 
        if (!$stmt->execute()) throw new Exception("Update deadlines failed: " . $stmt->error);
        $stmt->close();

        error_log("✅ Deadlines updated");
        
        // ===========================================
        // COMMIT TRANSAKSI
        // ===========================================
        
        $conn->commit();
        error_log("✅✅✅ EDIT Transaction committed successfully!");

        // Response success
        echo "
        <script>
            sessionStorage.setItem('showSuccess', 'true');
            sessionStorage.setItem('successMessage', 'Data berhasil diperbarui!');
            window.location.href = 'drop.php';
        </script>";
        exit;

    } catch (Exception $e) {
        // ERROR HANDLING
        $conn->rollback();
        error_log("❌ DROP EDIT ERROR: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        $errorMsg = $e->getMessage();
        echo "
        <script>
            alert('Gagal mengupdate data: " . addslashes($errorMsg) . "');
            window.history.back();
        </script>";
        exit;
    }
} else {
    // Method bukan POST
    error_log("❌ Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    die("<script>alert('Invalid request method'); window.history.back();</script>");
}
?>