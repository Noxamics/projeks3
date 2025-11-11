<?php
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 🔹 Ambil data dari form
    $customer_name = $_POST['customer_name'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $trans_date = $_POST['tanggal_masuk'];
    $est_finish_date = $_POST['tanggal_selesai'];
    $service_id = $_POST['service_id'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    $brand = $_POST['brand'] ?? '';
    $price = $_POST['price'] ?? 0;
    $amount_paid = $_POST['amount_paid'] ?? 0;
    $payment_status = $_POST['payment_status'] ?? 'Belum Lunas'; // enum di payments
    $status_id = $_POST['status_id'] ?? 1; // default status awal
    $estimate_desc = $_POST['estimate_desc'] ?? '';
    $payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : null;

    // 🔹 Jika statusnya "Lunas", isi otomatis payment_date hari ini
    if ($payment_status === 'Lunas' && empty($payment_date)) {
        $payment_date = date('Y-m-d H:i:s');
    }

    // 🔹 1. Simpan customer baru
    $stmt_customer = $conn->prepare("INSERT INTO customers (name, phone) VALUES (?, ?)");
    $stmt_customer->bind_param("ss", $customer_name, $phone_number);
    $stmt_customer->execute();
    $customer_id = $stmt_customer->insert_id;
    $stmt_customer->close();

    // 🔹 2. Buat kode unik order dan simpan data utama ke tabel drops (sementara payment_id = NULL)
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
            payment_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NULL)
    ");
    $stmt_drop->bind_param(
        "siisssi",
        $order_code,
        $customer_id,
        $service_id,
        $brand,
        $trans_date,
        $est_finish_date,
        $status_id
    );
    $stmt_drop->execute();
    $drop_id = $stmt_drop->insert_id;
    $stmt_drop->close();

    // 🔹 3. Simpan detail barang ke tabel drop_items
    $stmt_item = $conn->prepare("
        INSERT INTO drop_items (drop_id, service_id, brand, price)
        VALUES (?, ?, ?, ?)
    ");
    $stmt_item->bind_param("iisd", $drop_id, $service_id, $brand, $price);
    $stmt_item->execute();
    $stmt_item->close();

    // 🔹 4. Simpan deadline estimasi
    $stmt_deadline = $conn->prepare("
        INSERT INTO deadlines (drop_id, deadline_date, status_id)
        VALUES (?, ?, ?)
    ");
    $stmt_deadline->bind_param("isi", $drop_id, $est_finish_date, $status_id);
    $stmt_deadline->execute();
    $stmt_deadline->close();

    // 🔹 5. Simpan data pembayaran ke tabel payments
    if (trim($payment_method) === '') {
        $payment_method = 'Tunai';
    }

    if ($payment_date === null) {
        // jika belum lunas (tanggal kosong)
        $stmt_payment = $conn->prepare("
            INSERT INTO payments (drop_id, amount_paid, payment_method, payment_date, status)
            VALUES (?, ?, ?, NULL, ?)
        ");
        $stmt_payment->bind_param("idss", $drop_id, $amount_paid, $payment_method, $payment_status);
    } else {
        // jika sudah lunas (tanggal ada)
        $stmt_payment = $conn->prepare("
            INSERT INTO payments (drop_id, amount_paid, payment_method, payment_date, status)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt_payment->bind_param("idsss", $drop_id, $amount_paid, $payment_method, $payment_date, $payment_status);
    }

    $stmt_payment->execute();
    $payment_id = $stmt_payment->insert_id;
    $stmt_payment->close();

    // 🔹 6. Update drops dengan payment_id yang baru saja dibuat
    $stmt_update_drop = $conn->prepare("UPDATE drops SET payment_id = ? WHERE id_drop = ?");
    $stmt_update_drop->bind_param("ii", $payment_id, $drop_id);
    $stmt_update_drop->execute();
    $stmt_update_drop->close();

    // 🔹 Tutup koneksi
// 🔹 Tutup koneksi
    $conn->close();

    // 🔹 Jika mode cetak (via fetch), kirim JSON
    if (isset($_GET['print'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'drop_id' => $drop_id,
            'message' => 'Data berhasil disimpan dan siap dicetak.'
        ]);
        exit;
    }

    // 🔹 Redirect ke halaman drops biasa (mode Simpan saja)
    echo "
<script>
    sessionStorage.setItem('showSuccess', 'true');
    window.location.href = 'drop.php';
</script>";
    exit;

}
?>