<?php // <-- TIDAK ADA SPASI SEBELUM INI

// 1. Require db.php dulu (ini akan start session)
require_once '../db.php';

// 2. Baru require check_auth
require_once 'check_auth.php';

// 3. Ambil data admin
$adminData = getAdminData();
$adminName = $adminData['name'];
$adminEmail = $adminData['email'];
$adminId = $adminData['id_admin'];

// 4. Baru include header (yang mungkin ada output HTML)
include('../partials/headerAdmin.php');
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
<link rel="stylesheet" href="../css/drop/drop_base.css">
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
                            data-drop-id='" . htmlspecialchars($order['id_drop']) . "'
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

    <!-- ORDER DETAIL MODAL -->
    <div id="orderDetailModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeOrderDetail()">&times;</span>
            <h2>Detail Pesanan</h2>
            <div style="padding: 20px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 10px; font-weight: bold; width: 40%;">Kode Order:</td>
                        <td id="modal-order-code" style="padding: 10px;">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; font-weight: bold;">Pelanggan:</td>
                        <td id="modal-customer" style="padding: 10px;">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; font-weight: bold;">Kategori:</td>
                        <td id="modal-category" style="padding: 10px;">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; font-weight: bold;">Layanan:</td>
                        <td id="modal-service" style="padding: 10px;">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; font-weight: bold;">Brand:</td>
                        <td id="modal-brand" style="padding: 10px;">-</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; font-weight: bold;">Estimasi Selesai:</td>
                        <td id="modal-est-date" style="padding: 10px;">-</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

</main>

<!-- INCLUDE MODAL EDIT DARI FOLDER MODAL/DROP -->
<?php include('../modal/drop/modal_edit_item.php'); ?>

<script>
    // Data deadlines dari PHP
    const deadlinesData = <?php echo json_encode($deadlines); ?>;
    console.log('✅ Deadlines loaded:', deadlinesData.length);

    // Tutup modal detail
    function closeOrderDetail() {
        const modal = document.getElementById('orderDetailModal');
        if (modal) modal.style.display = 'none';
    }

    // ✅ FUNGSI UNTUK MEMBUKA EDIT MODAL - IMPROVED
    function openEditModal(element) {
        // Cegah modal ADD terbuka
        const addModal = document.getElementById('addModal');
        if (addModal) {
            addModal.style.display = 'none';
        }

        const dropId = element.getAttribute('data-drop-id') || element.getAttribute('data-id-drop');

        if (!dropId) {
            console.error('❌ Drop ID tidak ditemukan pada element:', element);
            alert('Error: ID pesanan tidak ditemukan');
            return;
        }

        console.log('✏️ Opening EDIT modal for Drop ID:', dropId);

        // Pastikan fungsi loadEditModal sudah tersedia
        if (typeof loadEditModal === 'function') {
            // Tutup semua modal yang mungkin terbuka
            document.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = 'none';
            });

            // Buka modal edit
            loadEditModal(dropId);
        } else {
            console.error('❌ Fungsi loadEditModal tidak ditemukan');
            alert('Error: Fungsi edit modal tidak tersedia. Pastikan file drop_modal_edit.js sudah dimuat.');
        }
    }

    // Make it globally accessible
    window.openEditModal = openEditModal;

    // File: Tambahkan script ini di timeline_pesanan.php SEBELUM closing </body>

    // ===== PREVENT ADD MODAL FROM OPENING =====
    (function () {
        'use strict';

        console.log('🛡️ ADD Modal Prevention Script loaded');

        // Function to force close ADD modal
        function forceCloseAddModal() {
            const addModal = document.getElementById('addModal');
            if (addModal && addModal.style.display !== 'none') {
                console.warn('⚠️ ADD Modal detected - Force closing...');
                addModal.style.display = 'none';
                addModal.classList.remove('show');
                document.body.style.overflow = '';
            }
        }

        // Check on load
        document.addEventListener('DOMContentLoaded', function () {
            forceCloseAddModal();

            // Monitor for ADD modal being opened
            const addModalObserver = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        const addModal = document.getElementById('addModal');
                        if (addModal && addModal.style.display !== 'none') {
                            console.warn('⚠️ ADD Modal opened unexpectedly - Auto closing...');
                            forceCloseAddModal();
                        }
                    }
                });
            });

            // Start observing ADD modal
            const addModal = document.getElementById('addModal');
            if (addModal) {
                addModalObserver.observe(addModal, {
                    attributes: true,
                    attributeFilter: ['style', 'class']
                });
                console.log('✅ ADD Modal observer active');
            }

            // Override showModal untuk addModal
            const originalShowModal = window.showModal;
            if (typeof originalShowModal === 'function') {
                window.showModal = function (modalId) {
                    if (modalId === 'addModal') {
                        console.warn('⚠️ Attempt to open ADD Modal blocked');
                        return false;
                    }
                    return originalShowModal(modalId);
                };
            }
        });

        // Check periodically (fallback)
        setInterval(forceCloseAddModal, 1000);

        console.log('✅ ADD Modal prevention active');
    })();
</script>

<!-- Load external JavaScript - URUTAN PENTING! -->
<!-- 1. Drop helpers harus dimuat pertama -->
<script src="../js/drop/drop_helpers.js"></script>

<!-- 2. Timeline pesanan -->
<script src="../js/timeline_pesanan.js"></script>


<script src="../js/drop/drop_modal.js"></script>
<script src="../js/drop/drop_modal_edit.js"></script>
<script src="../js/drop/drop_main.js"></script>
<script src="../js/drop/drop_status_change.js"></script>

<!-- 4. Pastikan tidak ada script yang membuka modal ADD -->
<script>
    // Prevent accidental ADD modal opening
    document.addEventListener('DOMContentLoaded', function () {
        // Tutup semua modal saat load
        document.querySelectorAll('.modal').forEach(modal => {
            if (modal.id !== 'orderDetailModal') {
                modal.style.display = 'none';
            }
        });

        console.log('✅ Timeline page loaded - Only EDIT modal allowed');
    });
</script>

<?php include_once "../partials/footer.php"; ?>