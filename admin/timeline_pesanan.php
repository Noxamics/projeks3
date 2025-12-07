<?php
// Perbaikan path include
include('../partials/headerAdmin.php');
include('../db.php');
?>

<?php
if (isset($_GET['success'])) {
    echo '<div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            ✅ Data berhasil diupdate!
          </div>';

    // AUTO-REDIRECT setelah 2 detik
    echo '<script>
            setTimeout(function() {
                window.location.href = "timeline_pesanan.php";
            }, 1500);
          </script>';
}

if (isset($_GET['error'])) {
    $error_message = urldecode($_GET['error']);
    echo '<div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            ❌ ' . htmlspecialchars($error_message) . '
          </div>';
}
?>

<?php
// ✅ FILTER: Query deadline TANPA status "Barang Telah Diambil"
$currentMonth = date('Y-m');
$deadlines_query = "
    SELECT 
        dl.deadline_date,
        d.order_code,
        c.name as customer_name,
        s.service_name,
        di.brand,
        st.status_name
    FROM deadlines dl
    JOIN drops d ON dl.drop_id = d.id_drop
    JOIN customers c ON d.customer_id = c.id_customer
    JOIN services s ON d.service_id = s.id_service
    JOIN drop_items di ON d.id_drop = di.drop_id
    JOIN statuses st ON dl.status_id = st.id_status
    WHERE DATE_FORMAT(dl.deadline_date, '%Y-%m') = '$currentMonth'
    AND st.status_name != 'Barang Telah Diambil'
    ORDER BY dl.deadline_date
";
$deadlines_result = mysqli_query($conn, $deadlines_query);
$deadlines = [];

while ($deadline = mysqli_fetch_assoc($deadlines_result)) {
    $deadlines[] = $deadline;
}
?>

<link rel="stylesheet" href="../css/timeline_pesanan.css">
<link rel="stylesheet" href="../css/timeline_pesanan-responsive.css">
<link rel="stylesheet" href="../css/drop/drop_modal.css">
<link rel="stylesheet" href="../css/drop/drop_form.css">

<main class="dashboard-container">

    <div class="dashboard-content">
        <!-- TIMELINE SECTION -->
        <div class="timeline-card">
            <h2>Timeline Pesanan</h2>

            <div class="filter-row">
                <select id="sortFilter" onchange="sortOrders()">
                    <option value="newest">Tanggal Terbaru</option>
                    <option value="oldest">Tanggal Terlama</option>
                    <option value="deadline-critical">🔴 Deadline Kritis (≤2 hari)</option>
                    <option value="deadline-warning">🟡 Deadline Mendesak (3-5 hari)</option>
                    <option value="deadline-safe">🟢 Deadline Aman (≥6 hari)</option>
                </select>
                <div class="search-box">
                    <input type="text" id="searchOrder" placeholder="Cari pesanan...">
                    <button onclick="searchOrders()">🔍</button>
                </div>
            </div>

            <div class="filter-type">
                <button class="active" onclick="filterByCategory('all')">All</button>
                <?php
                $category_query = "SELECT DISTINCT category FROM services WHERE category IS NOT NULL AND category != ''";
                $category_result = mysqli_query($conn, $category_query);

                while ($category = mysqli_fetch_assoc($category_result)) {
                    echo "<button onclick=\"filterByCategory('{$category['category']}')\">{$category['category']}</button>";
                }
                ?>
            </div>

            <div class="timeline-body" id="orderTimeline">
                <?php
                // ✅ FILTER: Query timeline TANPA status "Barang Telah Diambil"
                $timeline_query = "
                SELECT 
                    d.id_drop,
                    d.order_code,
                    d.trans_date,
                    d.est_finish_date,
                    dl.status_id,
                    d.employee_id,
                    c.id_customer,
                    c.name as customer_name,
                    c.phone as phone_number,
                    s.id_service,
                    s.service_name,
                    s.category,
                    s.price_min,
                    s.price_max,
                    s.duration,
                    di.brand,
                    di.fixed_price,
                    di.service_id as drop_item_service_id,
                    st.status_name,
                    p.status as payment_status,
                    p.payment_method,
                    p.payment_date,
                    p.amount_paid,
                    e.name as employee_name,
                    COALESCE(di.fixed_price, s.price_min) as actual_price
                FROM drops d
                JOIN customers c ON d.customer_id = c.id_customer
                JOIN drop_items di ON d.id_drop = di.drop_id
                JOIN services s ON di.service_id = s.id_service
                JOIN deadlines dl ON d.id_drop = dl.drop_id
                JOIN statuses st ON dl.status_id = st.id_status
                LEFT JOIN payments p ON d.id_drop = p.drop_id
                LEFT JOIN employees e ON d.employee_id = e.id_employee
                WHERE d.est_finish_date >= CURDATE()
                AND st.status_name != 'Barang Telah Diambil'
                ORDER BY d.trans_date DESC 
                LIMIT 10
            ";
                $timeline_result = mysqli_query($conn, $timeline_query);

                if (mysqli_num_rows($timeline_result) > 0) {
                    while ($order = mysqli_fetch_assoc($timeline_result)) {
                        $status_class = '';
                        switch ($order['status_name']) {
                            case 'Menunggu':
                                $status_class = 'status-waiting';
                                break;
                            case 'Diproses':
                                $status_class = 'status-process';
                                break;
                            case 'Selesai':
                                $status_class = 'status-done';
                                break;
                            default:
                                $status_class = 'status-waiting';
                        }

                        $est_date_formatted = date('d M Y', strtotime($order['est_finish_date']));

                        // Format harga - gunakan actual_price (fixed_price) untuk display
                        $actual_price = $order['actual_price'] ?? $order['price_min'];
                        $price_display = 'Rp ' . number_format($actual_price, 0, ',', '.');

                        $actual_service_id = $order['drop_item_service_id'] ?: $order['id_service'];

                        echo "
                        <div class='order-item' 
                            data-id-drop='" . htmlspecialchars($order['id_drop']) . "'
                            data-category='" . strtolower($order['category']) . "' 
                            data-order-code='" . htmlspecialchars($order['order_code']) . "'
                            data-customer='" . htmlspecialchars($order['customer_name']) . "'
                            data-phone='" . htmlspecialchars($order['phone_number']) . "'
                            data-service='" . htmlspecialchars($order['service_name']) . "'
                            data-service-id='" . htmlspecialchars($actual_service_id) . "'
                            data-category-full='" . htmlspecialchars($order['category']) . "'
                            data-brand='" . htmlspecialchars($order['brand']) . "'
                            data-trans-date='" . htmlspecialchars($order['trans_date']) . "'
                            data-est-date='" . htmlspecialchars($order['est_finish_date']) . "'
                            data-status-id='" . htmlspecialchars($order['status_id']) . "'
                            data-price-min='" . htmlspecialchars($order['price_min']) . "'
                            data-price-max='" . htmlspecialchars($order['price_max']) . "'
                            data-fixed-price='" . htmlspecialchars($actual_price) . "'
                            data-price-display='" . htmlspecialchars($price_display) . "'
                            data-duration='" . htmlspecialchars($order['duration']) . " hari'
                            data-payment-status='" . htmlspecialchars($order['payment_status'] ?? 'Belum Lunas') . "'
                            data-payment-method='" . htmlspecialchars($order['payment_method'] ?? 'Tunai') . "'
                            data-payment-date='" . htmlspecialchars($order['payment_date'] ?? '') . "'
                            data-amount-paid='" . htmlspecialchars($order['amount_paid'] ?? '0') . "'
                            data-employee-id='" . htmlspecialchars($order['employee_id'] ?? '') . "'
                            data-employee-name='" . htmlspecialchars($order['employee_name'] ?? '') . "'>
                            <div class='order-header'>
                                <span class='order-id'>{$order['order_code']}</span>
                                <span class='order-status {$status_class}'>{$order['status_name']}</span>
                            </div>
                            <div class='order-details'>
                                <strong>{$order['customer_name']}</strong> - {$order['service_name']} ({$order['brand']})
                            </div>
                            <div class='order-time'>
                                📅 Estimasi Selesai: {$est_date_formatted}
                            </div>
                            <div class='order-hint' style='font-size: 11px; color: #999; margin-top: 5px;'>
                                💡 Double klik untuk edit
                            </div>
                        </div>
                        ";
                    }
                } else {
                    echo "<p>Tidak ada pesanan</p>";
                }
                ?>
            </div>
        </div>

        <!-- CALENDAR & DEADLINE SECTION -->
        <div class="right-sidebar">
            <div class="calendar-card">
                <div class="calendar-header">
                    <h3><?php echo date('F Y'); ?></h3>
                    <div class="calendar-nav">
                        <button onclick="changeMonth(-1)">‹</button>
                        <button onclick="changeMonth(1)">›</button>
                    </div>
                </div>

                <div class="calendar-grid" id="calendarGrid"></div>

                <div class="deadline-section">
                    <h4>Deadline Mendatang</h4>
                    <div id="deadlineList"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- POPUP FORM EDIT BARANG -->
    <div class="modal" id="editModal">
        <div class="modal-content large">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Barang</h2>

            <form method="POST" action="dashboard_edit.php" class="grid-form" id="editForm">
                <input type="hidden" name="id_drop" id="edit_id_drop">

                <div>
                    <label>Nama Pelanggan</label>
                    <input type="text" id="edit_customer_name" name="customer_name" required>
                </div>
                <div>
                    <label>No. Handphone</label>
                    <input type="text" id="edit_customer_phone" name="phone_number" required>
                </div>

                <div>
                    <label>Brand / Merk</label>
                    <input type="text" id="edit_brand" name="brand" required>
                </div>

                <div>
                    <label for="edit_service_id">Layanan</label>
                    <select name="service_id" id="edit_service_id" required onchange="updateServiceInfo(this.value)">
                        <option value="">-- Pilih Layanan --</option>
                        <?php
                        $order = "FIELD(category, 'cleaning', 'reglue', 'repaint', 'bag', 'cap'), service_name";
                        $query = "SELECT id_service, category, service_name FROM services ORDER BY $order";
                        $result = mysqli_query($conn, $query);
                        while ($row = mysqli_fetch_assoc($result)) {
                            $displayName = ucfirst($row['category']) . " - " . ucfirst($row['service_name']);
                            echo "<option value='{$row['id_service']}' data-category='{$row['category']}' data-service='{$row['service_name']}'>{$displayName}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <label>Harga</label>
                    <input type="text" id="edit_fixed_price" name="fixed_price" placeholder="Rp 0">
                </div>

                <div>
                    <label>Estimasi Selesai</label>
                    <input type="text" id="edit_estimate_desc" name="estimate_desc" placeholder="Contoh: 2 Hari"
                        readonly style="background:#f9f9f9;">
                </div>

                <div>
                    <label>Tgl. Transaksi</label>
                    <input type="date" id="edit_tanggal_masuk" name="tanggal_masuk" required>
                </div>
                <div>
                    <label>Estimasi Selesai</label>
                    <input type="date" id="edit_tanggal_selesai" name="tanggal_selesai" required>
                </div>

                <div>
                    <label>Status</label>
                    <select name="status_id" id="edit_statusSelect" required>
                        <option value="">Pilih Status</option>
                        <?php
                        $st = $conn->query("SELECT * FROM statuses ORDER BY id_status ASC");
                        while ($s = $st->fetch_assoc()) {
                            echo "<option value='{$s['id_status']}'>{$s['status_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label>Status Pembayaran</label>
                    <select name="payment_status" id="edit_payment_status">
                        <option value="Belum Lunas">Belum Lunas</option>
                        <option value="Lunas">Lunas</option>
                    </select>
                </div>

                <div>
                    <label>Tanggal Pembayaran</label>
                    <input type="date" id="edit_payment_date" name="payment_date">
                </div>

                <div>
                    <label>Metode Pembayaran</label>
                    <select name="payment_method" id="edit_payment_method">
                        <option value="Tunai">Tunai</option>
                        <option value="Transfer">Transfer</option>
                        <option value="QRIS">QRIS</option>
                    </select>
                </div>

                <div>
                    <label for="edit_amount_paid">Nominal Pembayaran</label>
                    <input type="text" id="edit_amount_paid_display" placeholder="Masukkan nominal pembayaran">
                    <input type="hidden" name="amount_paid" id="edit_amount_paid">
                </div>

                <div>
                    <label>Karyawan</label>
                    <select name="employee_id" id="edit_employee_id" required>
                        <option value="">-- Pilih Karyawan --</option>
                        <?php
                        $emp_query = "SELECT id_employee, name FROM employees WHERE status = 'Aktif' ORDER BY name";
                        $emp_result = mysqli_query($conn, $emp_query);
                        while ($emp = mysqli_fetch_assoc($emp_result)) {
                            echo "<option value='{$emp['id_employee']}'>{$emp['name']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="full-width">
                    <button type="submit" class="save-btn">💾 Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

</main>

<!-- INCLUDE MODAL EDIT -->
<?php include('../modal/drop/modal_edit_item.php'); ?>

<script>
    // Data deadlines
    const deadlinesData = <?php echo json_encode($deadlines); ?>;
    console.log('Deadlines loaded:', deadlinesData.length);

    // Format Rupiah
    function formatRupiah(angka) {
        if (!angka || angka == 0) return 'Rp 0';
        return 'Rp ' + parseInt(angka).toLocaleString('id-ID');
    }

    // Parse Rupiah ke angka
    function parseRupiah(rupiah) {
        return parseInt(rupiah.replace(/[^0-9]/g, '')) || 0;
    }

    // Tutup modal
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    
</script>

<!-- Load external JavaScript -->
<script src="../js/timeline_pesanan.js"></script>
<script src="../js/drop/drop_helpers.js"></script>
<script src="../js/drop/drop_modal_edit.js"></script>

<?php include_once "../partials/footer.php"; ?>