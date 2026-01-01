<?php
// File: /partials/drop/customer_group.php
// IMPROVED COLOR SCHEME VERSION - Konsisten dengan sistem CSS baru

$customer_id = $customer['id_customer'];
$customer_name = htmlspecialchars($customer['name']);
$customer_phone = htmlspecialchars($customer['phone']);

$filter_date = isset($customer['filter_date']) ? $customer['filter_date'] : null;

if ($filter_date) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_date)) {
        $date_obj = date_create($filter_date);
        if ($date_obj) {
            $filter_date = date_format($date_obj, 'Y-m-d');
        } else {
            error_log("ERROR: Invalid date format for customer {$customer_name}: {$filter_date}");
            $filter_date = null;
        }
    }
}

$sql_orders = "
    SELECT 
        di.id_item, di.drop_id, d.order_code, di.brand, di.price,
        di.notes as item_note, di.item_order,
        di.status_id,
        s.service_name, s.category,
        st.status_name,
        d.trans_date,
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

if ($filter_date) {
    $sql_orders .= " AND DATE(d.trans_date) = ?";
}

$sql_orders .= " ORDER BY d.trans_date DESC, di.item_order ASC";

$stmt_orders = $conn->prepare($sql_orders);
if (!$stmt_orders) {
    error_log("Prepare statement failed: " . $conn->error);
    return;
}

if ($filter_date) {
    $stmt_orders->bind_param("is", $customer_id, $filter_date);
} else {
    $stmt_orders->bind_param("i", $customer_id);
}

$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();

$rows_found = $result_orders->num_rows;

if ($result_orders->num_rows === 0) {
    $stmt_orders->close();
    error_log("SKIPPING customer {$customer_id} - No orders found");
    return;
}

error_log("DISPLAYING customer {$customer_id} with {$rows_found} orders");

$total_orders = $result_orders->num_rows;
$total_amount = 0;
$orders_data = [];

while ($order = $result_orders->fetch_assoc()) {
    $total_amount += floatval($order['price']);
    $orders_data[] = $order;
}

$stmt_orders->close();
?>

<!-- CUSTOMER GROUP HEADER - IMPROVED COLORS -->
<div class='customer-group-header'>
    <div class='customer-info-text'>
        <input type='checkbox' class='customer-checkbox' data-customer-id='<?= $customer_id ?>'>
        <span class='customer-name'>
            <?= $customer_name ?>
        </span>
        <span class='customer-phone'>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-phone"
                viewBox="0 0 16 16" style="vertical-align: middle; margin-right: 4px;">
                <path
                    d="M11 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM5 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                <path d="M8 14a1 1 0 1 0 0-2 1 1 0 0 0 0 2" />
            </svg>
            <?= $customer_phone ?>
        </span>
    </div>
    <div style='display: flex; gap: 12px; align-items: center;'>
        <span class='order-count-badge'>
            <?= $total_orders ?> Pesanan
        </span>
        <div class='customer-actions'>
            <!-- Print All Button - Purple Theme -->
            <button class='customer-action-btn print-all-btn' data-customer-id='<?= $customer_id ?>'
                title='Cetak Semua Struk Customer Ini'>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor"
                    class="bi bi-printer-fill" viewBox="0 0 16 16">
                    <path
                        d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1" />
                    <path
                        d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1" />
                </svg>
                Cetak Semua
            </button>

            <!-- Thermal Print Button - Purple Theme -->
            <button class='customer-action-btn thermal-all-btn' data-customer-id='<?= $customer_id ?>'
                title='Cetak Thermal Semua Struk Customer Ini'>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor"
                    class="bi bi-printer-fill" viewBox="0 0 16 16">
                    <path
                        d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1" />
                    <path
                        d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1" />
                </svg>
                Thermal
            </button>

            <!-- Delete Button - Danger Theme -->
            <button class='customer-action-btn delete-customer-btn' data-customer-id='<?= $customer_id ?>'
                title='Hapus Semua Pesanan Customer Ini'>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor"
                    class="bi bi-trash-fill" viewBox="0 0 16 16">
                    <path
                        d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0" />
                </svg>
                Hapus Semua
            </button>
        </div>
    </div>
</div>

<!-- ORDERS CONTAINER -->
<div class='customer-orders-container' data-customer-id='<?= $customer_id ?>'>
    <!-- HEADER (10 KOLOM) -->
    <div class='orders-grid-header'>
        <div class='header-cell center'>#</div>
        <div class='header-cell'>ID ORDER</div>
        <div class='header-cell'>BRAND/ITEM</div>
        <div class='header-cell'>LAYANAN</div>
        <div class='header-cell center'>HARGA</div>
        <div class='header-cell center'>TGL. MASUK</div>
        <div class='header-cell center'>STATUS</div>
        <div class='header-cell center'>PEMBAYARAN</div>
        <div class='header-cell center'>KARYAWAN</div>
        <div class='header-cell center'>AKSI</div>
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

        $payment_status = $order['payment_status'] ?? 'Belum Lunas';
        $employee_name = $order['employee_name'] ?? '-';

        $is_completed = ($order['status_id'] == 6);
        $completed_class = $is_completed ? 'item-completed' : '';
        $data_status = $is_completed ? 'completed' : 'active';

        // IMPROVED: Payment status colors konsisten
        $paymentColor = match ($payment_status) {
            'Lunas' => 'color: #10b981; font-weight: 600;',     // Success green
            'Pending' => 'color: #f59e0b; font-weight: 600;',    // Warning amber
            default => 'color: #ef4444; font-weight: 600;'       // Danger red
        };
        ?>

        <div class='order-item-row <?= $completed_class ?>' data-drop-id='<?= $drop_id ?>' data-item-id='<?= $id_item ?>'
            data-customer-id='<?= $customer_id ?>' data-price='<?= $order['price'] ?>' data-status='<?= $data_status ?>'>
            <div class='order-item-grid'>

                <!-- 1. CHECKBOX -->
                <div class='order-item-cell center'>
                    <input type='checkbox' class='item-checkbox' data-drop-id='<?= $drop_id ?>'
                        data-customer-id='<?= $customer_id ?>'>
                </div>

                <!-- 2. ID ORDER -->
                <div class='order-item-cell'>
                    <div style='display: flex; flex-direction: column; gap: 2px;'>
                        <span class='item-number-badge'>#<?= $item_number ?></span>
                        <small style='color: #6366f1; font-weight: 600; font-size: 10px;'>
                            <?= $order_code ?>
                        </small>
                    </div>
                </div>

                <!-- 3. BRAND/ITEM -->
                <div class='order-item-cell' title='<?= $brand ?>'>
                    <?= $brand ?>
                </div>

                <!-- 4. LAYANAN -->
                <div class='order-item-cell'>
                    <div style='display: flex; flex-direction: column; gap: 2px;'>
                        <span style='font-size: 9px; color: #64748b; text-transform: uppercase;'>
                            <?= ucfirst($order['category']) ?>
                        </span>
                        <span style='font-weight: 600; font-size: 11px;'>
                            <?= $order['service_name'] ?>
                        </span>
                    </div>
                </div>

                <!-- 5. HARGA - Success Green Color -->
                <div class='order-item-cell center' style='font-weight: 700; color: #10b981; font-size: 12px;'
                    data-price-display>
                    Rp <?= $price ?>
                </div>

                <!-- 6. TGL. MASUK -->
                <div class='order-item-cell center' style='font-size: 11px;'>
                    <?= $trans_date ?>
                </div>

                <!-- 7. STATUS -->
                <div class='order-item-cell center'>
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

                <!-- 8. PEMBAYARAN - Color-coded -->
                <div class='order-item-cell center' style='<?= $paymentColor ?> font-size: 11px;'>
                    <?= $payment_status ?>
                </div>

                <!-- 9. KARYAWAN - Info Color -->
                <div class='order-item-cell center' style='font-size: 11px; color: #3b82f6;'>
                    <?= $employee_name ?>
                </div>

                <!-- 10. AKSI - ICON BUTTONS -->
                <div class='order-item-cell center'>
                    <div class='action-buttons-wrapper'>
                        <!-- Print Button - Purple Theme -->
                        <button class='print-item-btn' data-drop-id='<?= $drop_id ?>' title='Cetak Struk Pesanan Ini'>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-printer-fill" viewBox="0 0 16 16">
                                <path
                                    d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1" />
                                <path
                                    d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1" />
                            </svg>
                        </button>

                        <!-- Thermal Print Button - Purple Theme -->
                        <button class='thermal-print-btn' data-drop-id='<?= $drop_id ?>' title='Cetak Thermal'>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-printer-fill" viewBox="0 0 16 16">
                                <path
                                    d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1" />
                                <path
                                    d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1" />
                            </svg>
                        </button>

                        <!-- Delete Button - Danger Theme -->
                        <button class='delete-item-btn' data-item-id='<?= $id_item ?>' data-drop-id='<?= $drop_id ?>'
                            data-customer-id='<?= $customer_id ?>' title='Hapus Item Ini' <?= $is_completed ? 'disabled' : '' ?>>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-trash-fill" viewBox="0 0 16 16">
                                <path
                                    d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0" />
                            </svg>
                        </button>
                    </div>
                </div>

            </div>

            <?php if ($is_completed): ?>
                <!-- Completed Badge - Success Green Theme -->
                <div class='completed-badge-overlay'>
                    DIAMBIL
                </div>
            <?php endif; ?>
        </div>

    <?php endforeach; ?>

    <!-- TOTAL ROW - Info Blue Theme -->
    <div class='customer-total-row'>
        <span class='total-label'>Total Harga:</span>
        <span class='total-amount'>Rp <?= number_format($total_amount, 0, ',', '.') ?></span>
    </div>
</div>