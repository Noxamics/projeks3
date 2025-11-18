<?php
include('../db.php');

$id = $_GET['id'] ?? 0;

$query = $conn->prepare("
    SELECT d.order_code, c.name, c.phone, s.service_name, di.brand, di.price, 
           p.amount_paid, p.payment_method, p.status, d.trans_date
    FROM drops d
    JOIN customers c ON d.customer_id = c.id_customer
    JOIN drop_items di ON di.drop_id = d.id_drop
    JOIN services s ON di.service_id = s.id_service
    JOIN payments p ON p.drop_id = d.id_drop
    WHERE d.id_drop = ?
");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result()->fetch_assoc();
$query->close();

if (!$result) {
    die("Data tidak ditemukan untuk ID: " . htmlspecialchars($id));
}

$price = $result['price'];
$amount_paid = $result['amount_paid'];
$kembalian = $amount_paid - $price;

// 🔹 Format nomor telepon ke format internasional (Indonesia)
$phone = preg_replace('/^0/', '62', $result['phone']);

// 🔹 Pesan WhatsApp
$link_struk = "http://localhost/PROJEKS3/admin/cetak_struk.php?id=" . $id;
$pesan = "Halo *{$result['name']}*,%0A"
    . "Berikut detail pesanan Anda di *SengkuClean*:%0A%0A"
    . "🧾 *Kode Order:* {$result['order_code']}%0A"
    . "📦 *Barang:* {$result['brand']}%0A"
    . "🧼 *Layanan:* {$result['service_name']}%0A"
    . "💰 *Total:* Rp" . number_format($price, 0, ',', '.') . "%0A"
    . "💵 *Dibayar:* Rp" . number_format($amount_paid, 0, ',', '.') . "%0A"
    . "💸 *Kembalian:* Rp" . number_format(max($kembalian, 0), 0, ',', '.') . "%0A"
    . "📅 *Tanggal Masuk:* {$result['trans_date']}%0A"
    . "📲 *Cek struk Anda di link berikut:* %0A{$link_struk}%0A%0A"
    . "Terima kasih telah menggunakan layanan *SengkuClean* 💧";

$pesan = str_replace("\r", "", $pesan);
$wa_url = "https://wa.me/{$phone}?text=" . rawurlencode($pesan);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak Struk</title>
    <link rel="stylesheet" href="../css/struk.css">
    <script>
        function sendWhatsApp() {
            const waUrl = "<?= $wa_url ?>";
            // 🔹 Cetak otomatis
            window.print();
            // 🔹 Setelah cetak, buka WhatsApp
            setTimeout(() => {
                window.open(waUrl, '_blank');
            }, 1500);
        }
    </script>
</head>

<body onload="sendWhatsApp()">

    <div class="struk-container">
        <div class="struk-header">
            <h2>SengkuClean</h2>
            <p>Kebersihan adalah sebagian dari kenyamanan ✨</p>
        </div>

        <hr class="divider">

        <table class="struk-table">
            <tr>
                <td>Kode Order</td>
                <td>: <?= htmlspecialchars($result['order_code']) ?></td>
            </tr>
            <tr>
                <td>Nama</td>
                <td>: <?= htmlspecialchars($result['name']) ?></td>
            </tr>
            <tr>
                <td>Telepon</td>
                <td>: <?= htmlspecialchars($result['phone']) ?></td>
            </tr>
            <tr>
                <td>Barang</td>
                <td>: <?= htmlspecialchars($result['brand']) ?></td>
            </tr>
            <tr>
                <td>Layanan</td>
                <td>: <?= htmlspecialchars($result['service_name']) ?></td>
            </tr>
            <tr>
                <td>Tanggal Masuk</td>
                <td>: <?= htmlspecialchars($result['trans_date']) ?></td>
            </tr>
            <tr>
                <td>Metode Bayar</td>
                <td>: <?= htmlspecialchars($result['payment_method']) ?></td>
            </tr>
            <tr>
                <td>Status</td>
                <td>: <?= htmlspecialchars(ucfirst($result['status'])) ?></td>
            </tr>
        </table>

        <hr class="divider">

        <div class="price-section">
            <p><span>Harga</span><span>Rp<?= number_format($price, 0, ',', '.') ?></span></p>
            <p><span>Dibayar</span><span>Rp<?= number_format($amount_paid, 0, ',', '.') ?></span></p>
            <p class="kembalian">
                <span>Kembalian</span><span>Rp<?= number_format(max($kembalian, 0), 0, ',', '.') ?></span>
            </p>
        </div>

        <hr class="divider">

        <div class="footer">
            <p>Terima kasih telah menggunakan <strong>SengkuClean</strong> 💧</p>
            <p class="small">Struk ini sah tanpa tanda tangan</p>
        </div>
    </div>

</body>

</html>