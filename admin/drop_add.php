<?php
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $customer_name = $_POST['customer_name'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $trans_date = $_POST['tanggal_masuk'];
    $est_finish_date = $_POST['tanggal_selesai'];
    $service_id = $_POST['service_id'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    $brand = $_POST['brand'] ?? '';
    $price = $_POST['price'] ?? 0;
    $payment_status = $_POST['payment_status'] ?? 'Belum Lunas';
    $status_id = $_POST['status_id'] ?? 1; // default status pertama misalnya “Barang Baru Masuk”
    $estimate_desc = $_POST['estimate_desc'] ?? '';
    $payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : null;

    // 🔹 1. Simpan customer baru
    $stmt_customer = $conn->prepare("INSERT INTO customers (name, phone) VALUES (?, ?)");
    $stmt_customer->bind_param("ss", $customer_name, $phone_number);
    $stmt_customer->execute();
    $customer_id = $stmt_customer->insert_id;

    // 🔹 2. Simpan data utama drop
    // Buat kode order unik
    $order_code = "ORD" . date("ym") . "-" . str_pad(rand(1, 9999), 4, "0", STR_PAD_LEFT);

    $stmt_drop = $conn->prepare("
        INSERT INTO drops (
            order_code,
            customer_id,
            service_id,
            brand,
            trans_date,
            est_finish_date,
            status_id,
            payment_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt_drop->bind_param(
        "siisssis",
        $order_code,
        $customer_id,
        $service_id,
        $brand,
        $trans_date,
        $est_finish_date,
        $status_id,
        $payment_status
    );

    $stmt_drop->execute();
    $drop_id = $stmt_drop->insert_id;

    // 🔹 3. Simpan data barang ke drop_items
    $stmt_item = $conn->prepare("
        INSERT INTO drop_items (drop_id, service_id, brand, price)
        VALUES (?, ?, ?, ?)
    ");
    $stmt_item->bind_param("iisd", $drop_id, $service_id, $brand, $price);
    $stmt_item->execute();

    // 🔹 4. Simpan deadline estimasi
    // Ambil nama status dari tabel statuses
    $stmt_status = $conn->prepare("SELECT status_name FROM statuses WHERE id_status = ?");
    $stmt_status->bind_param("i", $status_id);
    $stmt_status->execute();
    $result_status = $stmt_status->get_result();
    $row_status = $result_status->fetch_assoc();
    $status_name = $row_status['status_name'] ?? 'Barang Baru Masuk';
    $stmt_status->close();

    $stmt_deadline = $conn->prepare("
        INSERT INTO deadlines (drop_id, deadline_date, status_id)
        VALUES (?, ?, ?)
    ");
    $stmt_deadline->bind_param("isi", $drop_id, $est_finish_date, $status_id);
    $stmt_deadline->execute();

    // 🔹 5. Simpan pembayaran (jika ada)
    $amount_paid = ($payment_status === 'Lunas') ? $price : 0;
    if (trim($payment_method) === '') {
        $payment_method = 'Tunai';
    }

    $stmt_payment = $conn->prepare("
        INSERT INTO payments (drop_id, amount_paid, payment_method, payment_date)
        VALUES (?, ?, ?, ?)
    ");
    $stmt_payment->bind_param("idss", $drop_id, $amount_paid, $payment_method, $payment_date);
    $stmt_payment->execute();

    // 🔹 Tutup semua statement
    $stmt_customer->close();
    $stmt_drop->close();
    $stmt_item->close();
    $stmt_deadline->close();
    $stmt_payment->close();
    $conn->close();

    // 🔹 Redirect ke drop.php dan tampilkan modal sukses
    echo "
    <script>
        sessionStorage.setItem('showSuccess', 'true');
        window.location.href = 'drop.php';
    </script>";
    exit;
}
?>