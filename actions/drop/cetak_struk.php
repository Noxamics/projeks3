<?php
// File: /actions/drop/cetak_struk.php
// Receipt printing page with WhatsApp integration - FIXED VERSION

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Include database connection
$db_path = __DIR__ . '/../../db.php';
if (!file_exists($db_path)) {
    die("Error: Database connection file not found at: " . htmlspecialchars($db_path));
}

require_once $db_path;

// Check connection
if (!isset($conn) || !$conn) {
    die("Error: Database connection failed");
}

// Get drop ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("Error: Invalid order ID");
}

// Query to get order data with all items
$query = $conn->prepare("
    SELECT 
        d.id_drop,
        d.order_code,
        d.trans_date,
        d.est_finish_date,
        d.total_amount,
        d.total_items,
        d.note,
        c.name as customer_name,
        c.phone as customer_phone,
        e.name as employee_name,
        p.amount_paid,
        p.payment_method,
        p.status as payment_status,
        p.payment_date
    FROM drops d
    JOIN customers c ON d.customer_id = c.id_customer
    LEFT JOIN employees e ON d.employee_id = e.id_employee
    LEFT JOIN payments p ON p.drop_id = d.id_drop
    WHERE d.id_drop = ?
");

if (!$query) {
    die("Database error: " . $conn->error);
}

$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result()->fetch_assoc();
$query->close();

if (!$result) {
    die("Data tidak ditemukan untuk ID: " . htmlspecialchars($id));
}

// Get all items for this order
$items_query = $conn->prepare("
    SELECT 
        di.brand,
        di.price,
        di.notes,
        s.service_name,
        s.category,
        st.status_name
    FROM drop_items di
    JOIN services s ON di.service_id = s.id_service
    LEFT JOIN statuses st ON di.status_id = st.id_status
    WHERE di.drop_id = ?
    ORDER BY di.item_order ASC
");

$items_query->bind_param("i", $id);
$items_query->execute();
$items_result = $items_query->get_result();
$items = [];
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}
$items_query->close();

// Calculate totals
$total_price = $result['total_amount'];
$amount_paid = $result['amount_paid'] ?? 0;
$kembalian = $amount_paid - $total_price;

// Format phone number to international format (Indonesia)
$phone = preg_replace('/[^0-9]/', '', $result['customer_phone']);
$phone = preg_replace('/^0/', '62', $phone);

// Build items list for WhatsApp message
$items_list = "";
foreach ($items as $idx => $item) {
    $num = $idx + 1;
    $service_display = ucfirst($item['category']) . " - " . ucfirst($item['service_name']);
    $items_list .= "\n{$num}. {$item['brand']} - {$service_display}\n   Rp" . number_format($item['price'], 0, ',', '.');
}

// WhatsApp message
$link_struk = "http://localhost/PROJEKS3/actions/drop/cetak_struk.php?id=" . $id;
$pesan = "Halo *{$result['customer_name']}*, 👋
Terima kasih telah mempercayakan layanan kami di *SengkuClean*. Berikut detail pesanan Anda:

🧾 *Kode Order* : {$result['order_code']}
📅 *Tanggal Masuk* : {$result['trans_date']}
" . ($result['est_finish_date'] ? "⏰ *Estimasi Selesai* : {$result['est_finish_date']}\n" : "") . "
📦 *Detail Pesanan* :{$items_list}

💳 *Metode Pembayaran* : {$result['payment_method']}
📌 *Status Pembayaran* : *" . ucfirst($result['payment_status']) . "*

💰 *Total Harga* : Rp" . number_format($total_price, 0, ',', '.') . "
💵 *Jumlah Dibayar* : Rp" . number_format($amount_paid, 0, ',', '.') . "
💸 *Kembalian* : Rp" . number_format(max($kembalian, 0), 0, ',', '.') . "

📲 Anda dapat melihat struk lengkap melalui link berikut:
{$link_struk}

Jika ada pertanyaan, Anda dapat membalas pesan ini kapan saja 😊
Salam hangat dari *SengkuClean* 💧";

$pesan = str_replace("\r", "", $pesan);
$wa_desktop = "https://wa.me/{$phone}?text=" . rawurlencode($pesan);
$wa_web = "https://web.whatsapp.com/send?phone={$phone}&text=" . rawurlencode($pesan);

// Detect if mobile
$is_mobile = preg_match('/(android|iphone|ipad|mobile)/i', $_SERVER['HTTP_USER_AGENT'] ?? '');
$wa_url = $is_mobile ? $wa_desktop : $wa_web;

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Struk - <?= htmlspecialchars($result['order_code']) ?></title>
    <link rel="stylesheet" href="../../css/drop/struk.css">
    <script>
        let whatsappOpened = false;

        function sendWhatsApp() {
            const waUrl = "<?= $wa_url ?>";

            // Cetak otomatis setelah halaman load
            setTimeout(() => {
                window.print();
            }, 500);

            // Buka WhatsApp setelah dialog cetak muncul
            setTimeout(() => {
                if (!whatsappOpened) {
                    whatsappOpened = true;
                    window.open(waUrl, '_blank');
                }
            }, 2000);
        }

        // Handle after print
        window.onafterprint = function () {
            // Optional: redirect or close window
            // window.close(); // Uncomment jika ingin auto close
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
                <td>: <?= htmlspecialchars($result['customer_name']) ?></td>
            </tr>
            <tr>
                <td>Telepon</td>
                <td>: <?= htmlspecialchars($result['customer_phone']) ?></td>
            </tr>
            <tr>
                <td>Tanggal Masuk</td>
                <td>: <?= htmlspecialchars($result['trans_date']) ?></td>
            </tr>
            <?php if ($result['est_finish_date']): ?>
                <tr>
                    <td>Est. Selesai</td>
                    <td>: <?= htmlspecialchars($result['est_finish_date']) ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <td>Karyawan</td>
                <td>: <?= htmlspecialchars($result['employee_name'] ?? '-') ?></td>
            </tr>
        </table>

        <hr class="divider">

        <div class="items-section">
            <h3>Detail Pesanan</h3>
            <?php foreach ($items as $idx => $item): ?>
                <div class="item-row">
                    <div class="item-header">
                        <span class="item-number"><?= $idx + 1 ?></span>
                        <span class="item-brand"><?= htmlspecialchars($item['brand']) ?></span>
                    </div>
                    <div class="item-details">
                        <span class="item-service">
                            <?= htmlspecialchars(ucfirst($item['category']) . ' - ' . ucfirst($item['service_name'])) ?>
                        </span>
                        <?php if ($item['status_name']): ?>
                            <span class="item-status"><?= htmlspecialchars($item['status_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="item-price">Rp<?= number_format($item['price'], 0, ',', '.') ?></div>
                    <?php if ($item['notes']): ?>
                        <div class="item-notes">💬 <?= htmlspecialchars($item['notes']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($result['note']): ?>
            <div class="order-notes">
                <strong>Catatan Pesanan:</strong><br>
                <?= nl2br(htmlspecialchars($result['note'])) ?>
            </div>
        <?php endif; ?>

        <hr class="divider">

        <div class="price-section">
            <p><span>Total Item</span><span><?= count($items) ?> item</span></p>
            <p><span>Total Harga</span><span>Rp<?= number_format($total_price, 0, ',', '.') ?></span></p>
            <p><span>Metode Bayar</span><span><?= htmlspecialchars($result['payment_method']) ?></span></p>
            <p><span>Dibayar</span><span>Rp<?= number_format($amount_paid, 0, ',', '.') ?></span></p>
            <p class="kembalian">
                <span>Kembalian</span>
                <span>Rp<?= number_format(max($kembalian, 0), 0, ',', '.') ?></span>
            </p>
            <p class="payment-status <?= strtolower($result['payment_status']) ?>">
                <span>Status</span>
                <span><?= htmlspecialchars(ucfirst($result['payment_status'])) ?></span>
            </p>
        </div>

        <hr class="divider">

        <div class="footer">
            <p>Terima kasih telah menggunakan <strong>SengkuClean</strong> 💧</p>
            <p class="small">Struk ini sah tanpa tanda tangan</p>
            <p class="small">Dicetak: <?= date('d/m/Y H:i') ?> WIB</p>
        </div>
    </div>

    <!-- Action buttons for non-print view -->
    <div class="action-buttons no-print">
        <button onclick="window.print()" class="btn-print">🖨️ Cetak Ulang</button>
        <button onclick="window.open('<?= $wa_url ?>', '_blank')" class="btn-whatsapp">📱 Kirim WhatsApp</button>
        <button onclick="window.close()" class="btn-close">✖️ Tutup</button>
    </div>

</body>

</html>