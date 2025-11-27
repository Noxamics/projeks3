<?php
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_drop = intval($_POST['id_drop'] ?? 0);
    $customer_name = trim($_POST['customer_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $service_id = intval($_POST['service_id'] ?? 0);
    $employee_id = intval($_POST['employee_id'] ?? 0);
    $trans_date = $_POST['tanggal_masuk'] ?? '';
    $est_finish_date = $_POST['tanggal_selesai'] ?? '';
    $status_id = intval($_POST['status_id'] ?? 1);
    $payment_status = $_POST['payment_status'] ?? 'Belum Lunas';
    $payment_method = trim($_POST['payment_method'] ?? 'Tunai');
    $payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : null;
    $amount_paid = floatval($_POST['amount_paid'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    // Jika status pembayaran = Lunas -> isi otomatis hari ini, selain itu null
    if ($payment_status === "Lunas") {
        $payment_date = date("Y-m-d");
    } else {
        $payment_date = null;
    }

    $conn->begin_transaction();

    try {
        // Ambil customer_id dari drops
        $stmt = $conn->prepare("SELECT customer_id FROM drops WHERE id_drop = ?");
        if (!$stmt) throw new Exception("Prepare failed (select drops): " . $conn->error);
        $stmt->bind_param("i", $id_drop);
        $stmt->execute();
        $result = $stmt->get_result();
        $drop_data = $result->fetch_assoc();
        $customer_id = $drop_data['customer_id'];
        $stmt->close();

        // Update customer
        $stmt = $conn->prepare("UPDATE customers SET name = ?, phone = ? WHERE id_customer = ?");
        if (!$stmt) throw new Exception("Prepare failed (update customers): " . $conn->error);
        $stmt->bind_param("ssi", $customer_name, $phone_number, $customer_id);
        $stmt->execute();
        $stmt->close();

        // Update drops (tambahkan note)
        $stmt = $conn->prepare("
            UPDATE drops 
            SET service_id = ?, employee_id = ?, brand = ?, trans_date = ?, est_finish_date = ?, status_id = ?, note = ?
            WHERE id_drop = ?
        ");
        if (!$stmt) throw new Exception("Prepare failed (update drops): " . $conn->error);
        $stmt->bind_param("iisssisi", $service_id, $employee_id, $brand, $trans_date, $est_finish_date, $status_id, $note, $id_drop);
        $stmt->execute();
        $stmt->close();

        // Cek payment
        $stmt = $conn->prepare("SELECT id_payment FROM payments WHERE drop_id = ?");
        if (!$stmt) throw new Exception("Prepare failed (select payments): " . $conn->error);
        $stmt->bind_param("i", $id_drop);
        $stmt->execute();
        $result = $stmt->get_result();
        $payment = $result->fetch_assoc();
        $stmt->close();

        if ($payment) {
            // update existing payment
            if ($payment_date === null) {
                $stmt = $conn->prepare("UPDATE payments SET amount_paid = ?, payment_method = ?, payment_date = NULL, status = ? WHERE drop_id = ?");
                $stmt->bind_param("dssi", $amount_paid, $payment_method, $payment_status, $id_drop);
            } else {
                $stmt = $conn->prepare("UPDATE payments SET amount_paid = ?, payment_method = ?, payment_date = ?, status = ? WHERE drop_id = ?");
                $stmt->bind_param("dsssi", $amount_paid, $payment_method, $payment_date, $payment_status, $id_drop);
            }
            $stmt->execute();
            $stmt->close();
        } else {
            // insert new payment
            if ($payment_date === null) {
                $stmt = $conn->prepare("INSERT INTO payments (drop_id, amount_paid, payment_method, payment_date, status) VALUES (?, ?, ?, NULL, ?)");
                $stmt->bind_param("idss", $id_drop, $amount_paid, $payment_method, $payment_status);
            } else {
                $stmt = $conn->prepare("INSERT INTO payments (drop_id, amount_paid, payment_method, payment_date, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("idsss", $id_drop, $amount_paid, $payment_method, $payment_date, $payment_status);
            }
            $stmt->execute();
            $stmt->close();
        }

        // Update deadlines
        $stmt = $conn->prepare("UPDATE deadlines SET deadline_date = ?, status_id = ? WHERE drop_id = ?");
        $stmt->bind_param("sii", $est_finish_date, $status_id, $id_drop);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        echo "<script>
            sessionStorage.setItem('showSuccess', 'true');
            sessionStorage.setItem('successMessage', 'Data berhasil diperbarui!');
            window.location.href = 'drop.php';
        </script>";
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("DROP EDIT ERROR: " . $e->getMessage());
        echo "<script>alert('Gagal mengupdate data: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit;
    }
}
?>
