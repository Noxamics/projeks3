<?php
include('../db.php');

$id = $_GET['id'] ?? 0;

// Query utama - TIDAK DIUBAH dari versi asli
$query = $conn->prepare("
    SELECT d.order_code, c.name, c.phone, s.service_name, di.brand, di.price, 
           p.amount_paid, p.payment_method, p.status, d.trans_date, d.total_amount
    FROM drops d
    JOIN customers c ON d.customer_id = c.id_customer
    JOIN drop_items di ON di.drop_id = d.id_drop
    JOIN services s ON di.service_id = s.id_service
    JOIN payments p ON p.drop_id = d.id_drop
    WHERE d.id_drop = ?
    LIMIT 1
");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result()->fetch_assoc();
$query->close();

if (!$result) {
    die("Data tidak ditemukan untuk ID: " . htmlspecialchars($id));
}

// NEW: Query untuk mendapatkan SEMUA items
$query_items = $conn->prepare("
    SELECT 
        di.brand,
        di.price,
        s.service_name,
        s.category
    FROM drop_items di
    JOIN services s ON di.service_id = s.id_service
    WHERE di.drop_id = ?
    ORDER BY di.item_order ASC
");
$query_items->bind_param("i", $id);
$query_items->execute();
$items_result = $query_items->get_result();
$items = [];
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}
$query_items->close();

// Hitung total - GUNAKAN total_amount dari database
$price = $result['total_amount']; // NEW: gunakan total dari semua items
$amount_paid = $result['amount_paid'];
$kembalian = $amount_paid - $price;

// Format nomor telepon - TIDAK DIUBAH
$phone = preg_replace('/^0/', '62', $result['phone']);

// NEW: Format items untuk WhatsApp message
$items_text = "";
if (count($items) > 1) {
    // Multi-item: tampilkan list
    foreach ($items as $index => $item) {
        $item_num = $index + 1;
        $items_text .= "\n   {$item_num}. {$item['brand']} - " . ucfirst($item['category']) . " " . ucfirst($item['service_name']) . " (Rp" . number_format($item['price'], 0, ',', '.') . ")";
    }
} else {
    // Single item: format seperti biasa
    $items_text = "\n🧼 *Layanan* : {$result['service_name']}\n📦 *Barang* : {$result['brand']}";
}

// Pesan WhatsApp - UPDATED untuk multi-item
$link_struk = "http://localhost/PROJEKS3/admin/cetak_struk.php?id=" . $id;

if (count($items) > 1) {
    // Multi-item message
    $pesan = "Halo *{$result['name']}*, 👋
Terima kasih telah mempercayakan layanan laundry di *SengkuClean*. Berikut detail pesanan Anda:

🧾 *Kode Order* : {$result['order_code']}
📅 *Tanggal Masuk* : {$result['trans_date']}
📦 *Jumlah Item* : " . count($items) . " items

*Detail Pesanan:*{$items_text}

💰 *Total Harga* : Rp" . number_format($price, 0, ',', '.') . "
💵 *Jumlah Dibayar* : Rp" . number_format($amount_paid, 0, ',', '.') . "
💸 *Kembalian* : Rp" . number_format(max($kembalian, 0), 0, ',', '.') . "
💳 *Metode Pembayaran* : {$result['payment_method']}
🔌 *Status* : *" . ucfirst($result['status']) . "*

📲 Anda dapat melihat struk lengkap melalui link berikut:
{$link_struk}

Jika ada pertanyaan, Anda dapat membalas pesan ini kapan saja 😊
Salam hangat dari *SengkuClean* 💧";
} else {
    // Single item message - DESAIN ASLI DIPERTAHANKAN
    $pesan = "Halo *{$result['name']}*, 👋
Terima kasih telah mempercayakan layanan laundry di *SengkuClean*. Berikut detail pesanan Anda:

🧾 *Kode Order* : {$result['order_code']}
📦 *Barang* : {$result['brand']}
🧼 *Layanan* : {$result['service_name']}
📅 *Tanggal Masuk* : {$result['trans_date']}
💳 *Metode Pembayaran* : {$result['payment_method']}
🔌 *Status* : *" . ucfirst($result['status']) . "*

💰 *Total Harga* : Rp" . number_format($price, 0, ',', '.') . "
💵 *Jumlah Dibayar* : Rp" . number_format($amount_paid, 0, ',', '.') . "
💸 *Kembalian* : Rp" . number_format(max($kembalian, 0), 0, ',', '.') . "

📲 Anda dapat melihat struk lengkap melalui link berikut:
{$link_struk}

Jika ada pertanyaan, Anda dapat membalas pesan ini kapan saja 😊
Salam hangat dari *SengkuClean* 💧";
}

$pesan = str_replace("\r", "", $pesan);
$wa_desktop = "https://wa.me/send?phone={$phone}&text=" . rawurlencode($pesan);
$wa_web = "https://web.whatsapp.com/send?phone={$phone}&text=" . rawurlencode($pesan);
$wa_url = (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false) ? $wa_desktop : $wa_web;
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
            // Cetak otomatis
            window.print();
            // Setelah cetak, buka WhatsApp
            setTimeout(() => {
                window.open(waUrl, '_blank');
            }, 1500);
        }
    </script>
</head>

<body onload="sendWhatsApp()">

    <div class="struk-container">
        <!-- HEADER - DESAIN ASLI DIPERTAHANKAN -->
        <div class="struk-header">
            <h2>SengkuClean</h2>
            <p>Kebersihan adalah sebagian dari kenyamanan ✨</p>
        </div>

        <hr class="divider">

        <!-- INFO PELANGGAN - DESAIN ASLI DIPERTAHANKAN -->
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

        <!-- NEW: DETAIL ITEMS (HANYA JIKA MULTI-ITEM) -->
        <?php if (count($items) > 1): ?>
            <div style="margin: 15px 0;">
                <strong style="font-size: 14px; color: #004d9d;">Detail Pesanan (<?= count($items) ?> Items):</strong>
                <table style="width: 100%; margin-top: 10px; font-size: 12px;">
                    <?php foreach ($items as $index => $item): ?>
                        <tr style="border-bottom: 1px dashed #ddd;">
                            <td style="padding: 5px 0; width: 60%;">
                                <?= $index + 1 ?>. <?= htmlspecialchars($item['brand']) ?><br>
                                <small style="color: #666;"><?= ucfirst(htmlspecialchars($item['category'])) ?> - <?= ucfirst(htmlspecialchars($item['service_name'])) ?></small>
                            </td>
                            <td style="padding: 5px 0; text-align: right; font-weight: 600;">
                                Rp<?= number_format($item['price'], 0, ',', '.') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <hr class="divider">
        <?php else: ?>
            <!-- SINGLE ITEM - TAMPILAN ASLI DIPERTAHANKAN -->
            <table class="struk-table">
                <tr>
                    <td>Barang</td>
                    <td>: <?= htmlspecialchars($result['brand']) ?></td>
                </tr>
                <tr>
                    <td>Layanan</td>
                    <td>: <?= htmlspecialchars($result['service_name']) ?></td>
                </tr>
            </table>

            <hr class="divider">
        <?php endif; ?>

        <!-- HARGA - DESAIN ASLI DIPERTAHANKAN -->
        <div class="price-section">
            <p><span>Harga</span><span>Rp<?= number_format($price, 0, ',', '.') ?></span></p>
            <p><span>Dibayar</span><span>Rp<?= number_format($amount_paid, 0, ',', '.') ?></span></p>
            <p class="kembalian">
                <span>Kembalian</span><span>Rp<?= number_format(max($kembalian, 0), 0, ',', '.') ?></span>
            </p>
        </div>

        <hr class="divider">

        <!-- FOOTER - DESAIN ASLI DIPERTAHANKAN -->
        <div class="footer">
            <p>Terima kasih telah menggunakan <strong>SengkuClean</strong> 💧</p>
            <p class="small">Struk ini sah tanpa tanda tangan</p>
        </div>
    </div>

</body>

</html>