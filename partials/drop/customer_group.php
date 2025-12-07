<?php
// File: /partials/drop/customer_group.php
// Display customer group dengan semua items - INCLUDE ALL STATUS

$customer_id = $customer['id_customer'];
$customer_name = htmlspecialchars($customer['name']);
$customer_phone = htmlspecialchars($customer['phone']);

// Query untuk mendapatkan semua orders dari customer ini
// PENTING: TIDAK filter status_id != 6, ambil SEMUA status
$sql_orders = "
    SELECT 
        di.id_item, di.drop_id, d.order_code, di.brand, di.price,
        di.notes as item_note, di.item_order,
        di.status_id,
        s.service_name, s.category,
        st.status_name,
        d.trans_date, d.est_finish_date,
        p.status as payment_status,
        e.name as employee_name
    FROM drop_items di
    INNER JOIN drops d ON di.drop_id = d.id_drop
    INNER JOIN services s ON di.service_id = s.id_service
    INNER JOIN statuses st ON di.status_id = st.id_status
    LEFT JOIN payments p ON d.id_drop = p.drop_id
    LEFT JOIN employees e ON d.employee_id = e.id_employee
    WHERE d.customer_id = ?
";
// ☝️ TIDAK ADA filter "AND di.status_id != 6" - ambil semua status!

// Tambahkan sorting berdasarkan parameter dari GET
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';
switch ($sort) {
    case 'tanggal_asc':
        $sql_orders .= " ORDER BY d.trans_date ASC, di.item_order ASC";
        break;
    case 'tanggal_desc':
        $sql_orders .= " ORDER BY d.trans_date DESC, di.item_order ASC";
        break;
    default:
        $sql_orders .= " ORDER BY d.trans_date DESC, di.item_order ASC";
}

$stmt_orders = $conn->prepare($sql_orders);
if (!$stmt_orders) {
    error_log("Prepare statement failed: " . $conn->error);
    return;
}

$stmt_orders->bind_param("i", $customer_id);
$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();

// Jika tidak ada order untuk customer ini, skip
if ($result_orders->num_rows === 0) {
    $stmt_orders->close();
    return;
}

$total_orders = $result_orders->num_rows;
$total_amount = 0;
$orders_data = [];

while ($order = $result_orders->fetch_assoc()) {
    $total_amount += floatval($order['price']);
    $orders_data[] = $order;
}

$stmt_orders->close();
?>

<!-- CUSTOMER GROUP HEADER -->
<div class='customer-group-header'>
    <div class='customer-info-text'>
        <input type='checkbox' class='customer-checkbox' data-customer-id='<?= $customer_id ?>'>
        <span class='customer-name'><?= $customer_name ?></span>
        <span class='customer-phone'>📱 <?= $customer_phone ?></span>
    </div>
    <div style='display: flex; gap: 12px; align-items: center;'>
        <span class='order-count-badge'><?= $total_orders ?> Pesanan</span>
        <div class='customer-actions'>
            <button class='customer-action-btn print-all-btn' data-customer-id='<?= $customer_id ?>'
                title='Cetak Semua Struk Customer Ini'>
                🖨️ Cetak Semua
            </button>
            <button class='customer-action-btn delete-customer-btn' data-customer-id='<?= $customer_id ?>'
                title='Hapus Semua Pesanan Customer Ini'>
                <img src='../a/svg/trash.svg' alt='Hapus'
                    style='width: 16px; height: 16px; filter: brightness(0) invert(1);'>
                Hapus Semua
            </button>
        </div>
    </div>
</div>

<!-- ORDERS CONTAINER -->
<div class='customer-orders-container' data-customer-id='<?= $customer_id ?>'>
    <!-- HEADER -->
    <div class='orders-grid-header'>
        <div class='header-cell center'>#</div>
        <div class='header-cell'>ID Order</div>
        <div class='header-cell'>Brand/Item</div>
        <div class='header-cell'>Layanan</div>
        <div class='header-cell center'>Harga</div>
        <div class='header-cell center'>Tgl. Masuk</div>
        <div class='header-cell center'>Est. Selesai</div>
        <div class='header-cell center'>Status</div>
        <div class='header-cell center'>Pembayaran</div>
        <div class='header-cell center'>Karyawan</div>
        <div class='header-cell'>Catatan</div>
        <div class='header-cell center'>Aksi</div>
    </div>

    <?php foreach ($orders_data as $index => $order):
        $item_number = $index + 1;
        $id_item = $order['id_item'];
        $drop_id = $order['drop_id'];
        $order_code = htmlspecialchars($order['order_code']);
        $brand = htmlspecialchars($order['brand']);
        $item_note = htmlspecialchars($order['item_note'] ?? '');
        $note_display = !empty($item_note) ?
            (strlen($item_note) > 30 ? substr($item_note, 0, 30) . '...' : $item_note) :
            '-';
        $note_class = !empty($item_note) ? 'note-cell' : 'note-cell empty';
        $price = number_format($order['price'], 0, ',', '.');
        $trans_date = date('d M Y', strtotime($order['trans_date']));
        $est_date = date('d M Y', strtotime($order['est_finish_date']));
        $payment_status = $order['payment_status'] ?? 'Belum Lunas';
        $employee_name = $order['employee_name'] ?? '-';

        // Check if completed (status 6)
        $is_completed = ($order['status_id'] == 6);
        $completed_class = $is_completed ? 'item-completed' : '';
        $data_status = $is_completed ? 'completed' : 'active';

        $paymentColor = match ($payment_status) {
            'Lunas' => 'color: #059669; font-weight: 600;',
            'Pending' => 'color: #f59e0b; font-weight: 600;',
            default => 'color: #dc2626; font-weight: 600;'
        };
        ?>

        <div class='order-item-row <?= $completed_class ?>' data-drop-id='<?= $drop_id ?>' data-item-id='<?= $id_item ?>'
            data-customer-id='<?= $customer_id ?>' data-price='<?= $order['price'] ?>' data-status='<?= $data_status ?>'>
            <div class='order-item-grid'>
                <div class='order-item-cell center'>
                    <input type='checkbox' class='item-checkbox' data-drop-id='<?= $drop_id ?>'
                        data-customer-id='<?= $customer_id ?>'>
                </div>
                <div class='order-item-cell'>
                    <span class='item-number-badge'>#<?= $item_number ?></span><br>
                    <small style='color: #6366f1; font-weight: 600;'><?= $order_code ?></small>
                </div>
                <div class='order-item-cell'><?= $brand ?></div>
                <div class='order-item-cell'>
                    <span style='font-size: 10px; color: #64748b;'><?= ucfirst($order['category']) ?></span><br>
                    <span style='font-weight: 600;'><?= $order['service_name'] ?></span>
                </div>
                <div class='order-item-cell center' style='font-weight: 700; color: #059669;' data-price-display>
                    Rp<?= $price ?>
                </div>
                <div class='order-item-cell center' style='font-size: 11px;'><?= $trans_date ?></div>
                <div class='order-item-cell center' style='font-size: 11px;'><?= $est_date ?></div>
                <div class='order-item-cell center'>
                    <!-- Status dropdown with proper data attributes -->
                    <select class='item-status-select' data-item-id='<?= $id_item ?>' data-drop-id='<?= $drop_id ?>'
                        data-current-status='<?= $order['status_id'] ?>' data-old-status='<?= $order['status_id'] ?>'
                        data-item-brand='<?= $brand ?>' <?= $is_completed ? 'disabled' : '' ?>>
                        <?php
                        $st2 = $conn->query("SELECT * FROM statuses ORDER BY id_status ASC");
                        while ($s2 = $st2->fetch_assoc()) {
                            $selected = ($s2['id_status'] == $order['status_id']) ? 'selected' : '';
                            echo "<option value='{$s2['id_status']}' {$selected}>{$s2['status_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class='order-item-cell center' style='<?= $paymentColor ?> font-size: 11px;'>
                    <?= $payment_status ?>
                </div>
                <div class='order-item-cell center' style='font-size: 10px;'>
                    <?= $employee_name ?>
                </div>
                <div class='order-item-cell'>
                    <div class='<?= $note_class ?>' title='<?= $item_note ?>' data-note='<?= $item_note ?>'
                        data-item-id='<?= $id_item ?>' data-drop-id='<?= $drop_id ?>'>
                        <?= $note_display ?>
                    </div>
                </div>
                <div class='order-item-cell center'>
                    <div style='display: flex; gap: 6px; justify-content: center; align-items: center;'>
                        <!-- Tombol Cetak Struk -->
                        <button class='print-item-btn' data-drop-id='<?= $drop_id ?>' 
                            title='Cetak Struk Pesanan Ini'
                            style='background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); 
                                   color: white; border: none; padding: 6px 10px; 
                                   border-radius: 6px; cursor: pointer; 
                                   font-size: 11px; font-weight: 600;
                                   display: flex; align-items: center; gap: 4px;
                                   transition: all 0.3s ease;'>
                            🖨️ Cetak
                        </button>
                        
                        <!-- Tombol Hapus -->
                        <button class='delete-item-btn' data-item-id='<?= $id_item ?>' data-drop-id='<?= $drop_id ?>'
                            data-customer-id='<?= $customer_id ?>' title='Hapus Item Ini' 
                            <?= $is_completed ? 'disabled' : '' ?>
                            style='background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); 
                                   color: white; border: none; padding: 6px 10px; 
                                   border-radius: 6px; cursor: pointer;
                                   display: flex; align-items: center; justify-content: center;
                                   transition: all 0.3s ease;'>
                            <img src='../a/svg/trash.svg' alt='Hapus' 
                                style='width: 14px; height: 14px; filter: brightness(0) invert(1);'>
                        </button>
                    </div>
                </div>
            </div>

            <?php if ($is_completed): ?>
                <!-- Completed Badge Overlay -->
                <div class='completed-badge-overlay'>✓ DIAMBIL</div>
            <?php endif; ?>
        </div>

    <?php endforeach; ?>

    <!-- TOTAL ROW -->
    <div class='customer-total-row'>
        <span class='total-label'>Total Harga:</span>
        <span class='total-amount'>Rp<?= number_format($total_amount, 0, ',', '.') ?></span>
    </div>
</div>