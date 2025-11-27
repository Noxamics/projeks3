<?php
include('../db.php');
session_start();

// debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil data dari form
    $customer_name = trim($_POST['customer_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $trans_date = $_POST['tanggal_masuk'] ?? date('Y-m-d');
    $est_finish_date = $_POST['tanggal_selesai'] ?? date('Y-m-d', strtotime('+3 days'));
    $service_id = intval($_POST['service_id'] ?? 0);
    $brand = trim($_POST['brand'] ?? '');
    $price_min = floatval($_POST['price_min'] ?? 0);
    $amount_paid = floatval($_POST['amount_paid'] ?? 0);
    $payment_method = trim($_POST['payment_method'] ?? 'Tunai');
    $payment_status = $_POST['payment_status'] ?? 'Belum Lunas';
    $status_id = intval($_POST['status_id'] ?? 1);
    $payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : null;
    $employee_id = intval($_POST['employee_id'] ?? 0);

    // NOTE Pelanggan
    $note = trim($_POST['note'] ?? '');

    // Jika status pembayaran = Lunas -> isi otomatis hari ini, selain itu null
    if ($payment_status === "Lunas") {
        $payment_date = date("Y-m-d");
    } else {
        $payment_date = null;
    }

    // Validasi
    if (empty($customer_name) || empty($phone_number)) {
        die("<script>alert('Nama dan nomor HP customer wajib diisi!'); window.history.back();</script>");
    }
    if ($service_id <= 0) {
        die("<script>alert('Layanan harus dipilih!'); window.history.back();</script>");
    }
    if ($employee_id <= 0) {
        die("<script>alert('Karyawan harus dipilih!'); window.history.back();</script>");
    }

    try {
        $conn->begin_transaction();

        // Cek/insert customer
        $stmt_check = $conn->prepare("SELECT id_customer FROM customers WHERE phone = ?");
        if (!$stmt_check) throw new Exception("Prepare failed (check customer): " . $conn->error);
        $stmt_check->bind_param("s", $phone_number);
        $stmt_check->execute();
        $res = $stmt_check->get_result();
        if ($res->num_rows > 0) {
            $customer_id = $res->fetch_assoc()['id_customer'];
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

        // generate kode order
        $order_code = "ORD" . date("ym") . "-" . str_pad(rand(1, 9999), 4, "0", STR_PAD_LEFT);

        // insert drops (tambah note)
        $sql_drop = "
            INSERT INTO drops (
                order_code, customer_id, service_id, brand, 
                trans_date, est_finish_date, status_id, employee_id, note
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt_drop = $conn->prepare($sql_drop);
        if (!$stmt_drop) throw new Exception("Prepare failed (insert drops): " . $conn->error);

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

        if (!$stmt_drop->execute()) throw new Exception("Insert drops failed: " . $stmt_drop->error);
        $drop_id = $stmt_drop->insert_id;
        $stmt_drop->close();

        // insert drop_items
        $stmt_item = $conn->prepare("INSERT INTO drop_items (drop_id, service_id, brand, price) VALUES (?, ?, ?, ?)");
        if (!$stmt_item) throw new Exception("Prepare failed (insert drop_items): " . $conn->error);
        $stmt_item->bind_param("iisd", $drop_id, $service_id, $brand, $price_min);
        if (!$stmt_item->execute()) throw new Exception("Insert drop_items failed: " . $stmt_item->error);
        $stmt_item->close();

        // insert deadlines
        $stmt_deadline = $conn->prepare("INSERT INTO deadlines (drop_id, deadline_date, status_id) VALUES (?, ?, ?)");
        if (!$stmt_deadline) throw new Exception("Prepare failed (insert deadlines): " . $conn->error);
        $stmt_deadline->bind_param("isi", $drop_id, $est_finish_date, $status_id);
        if (!$stmt_deadline->execute()) throw new Exception("Insert deadlines failed: " . $stmt_deadline->error);
        $stmt_deadline->close();

        // insert payments
        if ($payment_date === null) {
            $stmt_payment = $conn->prepare("INSERT INTO payments (drop_id, amount_paid, payment_method, payment_date, status) VALUES (?, ?, ?, NULL, ?)");
            if (!$stmt_payment) throw new Exception("Prepare failed (insert payments): " . $conn->error);
            $stmt_payment->bind_param("idss", $drop_id, $amount_paid, $payment_method, $payment_status);
        } else {
            $stmt_payment = $conn->prepare("INSERT INTO payments (drop_id, amount_paid, payment_method, payment_date, status) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt_payment) throw new Exception("Prepare failed (insert payments): " . $conn->error);
            $stmt_payment->bind_param("idsss", $drop_id, $amount_paid, $payment_method, $payment_date, $payment_status);
        }
        if (!$stmt_payment->execute()) throw new Exception("Insert payments failed: " . $stmt_payment->error);
        $stmt_payment->close();

        $conn->commit();

        // redirect back
        echo "
        <script>
            sessionStorage.setItem('showSuccess', 'true');
            sessionStorage.setItem('successMessage', 'Data berhasil disimpan!');
            window.location.href = 'drop.php';
        </script>";
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("DROP ADD ERROR: " . $e->getMessage());
        echo "<script>alert('Gagal menyimpan data: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit;
    }
}
?>
