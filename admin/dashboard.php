<?php 
// Perbaikan path include
include('../partials/headerAdmin.php'); 
include('../db.php'); 
?>

<?php
// Query untuk mendapatkan semua deadline dalam bulan ini
$currentMonth = date('Y-m');
$deadlines_query = "
    SELECT 
        dl.deadline_date,
        d.order_code,
        c.name as customer_name,
        s.service_name,
        di.brand
    FROM deadlines dl
    JOIN drops d ON dl.drop_id = d.id_drop
    JOIN customers c ON d.customer_id = c.id_customer
    JOIN services s ON d.service_id = s.id_service
    JOIN drop_items di ON d.id_drop = di.drop_id
    WHERE DATE_FORMAT(dl.deadline_date, '%Y-%m') = '$currentMonth'
    ORDER BY dl.deadline_date
";
$deadlines_result = mysqli_query($conn, $deadlines_query);
$deadlines = [];

// Simpan deadline dalam array untuk digunakan di JavaScript
while($deadline = mysqli_fetch_assoc($deadlines_result)) {
    $deadlines[] = $deadline;
}
?>

<link rel="stylesheet" href="../css/dashboard.css">

<main class="dashboard-container">
    <div class="dashboard-header">
        <h1>Dashboard</h1>
    </div>
    
    <div class="dashboard-content">
        <!-- TIMELINE SECTION -->
        <div class="timeline-card">
            <h2>Timeline Pesanan</h2>
            
            <div class="filter-row">
                <select id="sortFilter" onchange="sortOrders()">
                    <option value="newest">Tanggal Terbaru</option>
                    <option value="oldest">Tanggal Terlama</option>
                </select>
                <div class="search-box">
                    <input type="text" id="searchOrder" placeholder="Cari pesanan...">
                    <button onclick="searchOrders()">🔍</button>
                </div>
            </div>

            <div class="filter-type">
                <button class="active" onclick="filterByCategory('all')">All</button>
                <?php
                // Query untuk mendapatkan category dari tabel services
                $category_query = "SELECT DISTINCT category FROM services WHERE category IS NOT NULL AND category != ''";
                $category_result = mysqli_query($conn, $category_query);
                
                while($category = mysqli_fetch_assoc($category_result)) {
                    echo "<button onclick=\"filterByCategory('{$category['category']}')\">{$category['category']}</button>";
                }
                ?>
            </div>

            <div class="timeline-body" id="orderTimeline">
                <?php
                // Query untuk timeline pesanan
                $timeline_query = "
                    SELECT 
                        d.order_code,
                        d.est_finish_date,
                        c.name as customer_name,
                        s.service_name,
                        s.category,
                        di.brand,
                        st.status_name
                    FROM drops d
                    JOIN customers c ON d.customer_id = c.id_customer
                    JOIN services s ON d.service_id = s.id_service
                    JOIN drop_items di ON d.id_drop = di.drop_id
                    JOIN statuses st ON d.status_id = st.id_status
                    ORDER BY d.trans_date DESC 
                    LIMIT 10
                ";
                $timeline_result = mysqli_query($conn, $timeline_query);
                
                if(mysqli_num_rows($timeline_result) > 0) {
                    while($order = mysqli_fetch_assoc($timeline_result)) {
                        $status_class = '';
                        switch($order['status_name']) {
                            case 'Menunggu': $status_class = 'status-waiting'; break;
                            case 'Diproses': $status_class = 'status-process'; break;
                            case 'Selesai': $status_class = 'status-done'; break;
                            default: $status_class = 'status-waiting';
                        }
                        
                        $est_date_formatted = date('d M Y', strtotime($order['est_finish_date']));
                        
                        echo "
                        <div class='order-item' 
                             data-category='" . strtolower($order['category']) . "' 
                             data-order-code='" . htmlspecialchars($order['order_code']) . "'
                             data-customer='" . htmlspecialchars($order['customer_name']) . "'
                             data-service='" . htmlspecialchars($order['service_name']) . "'
                             data-category-full='" . htmlspecialchars($order['category']) . "'
                             data-brand='" . htmlspecialchars($order['brand']) . "'
                             data-est-date='" . $order['est_finish_date'] . "'>
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

                <div class="calendar-grid" id="calendarGrid">
                    <!-- Calendar akan di-generate oleh JavaScript -->
                </div>

                <div class="deadline-section">
                    <h4>Deadline Mendatang</h4>
                    <div id="deadlineList">
                        <!-- Deadline items akan di-generate oleh JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- POPUP FORM EDIT BARANG - Perbaikan -->
    <div class="modal" id="editModal">
        <div class="modal-content large">
            <span class="close">&times;</span>
            <h2>Edit Barang</h2>

            <form method="POST" action="dashboard_edit.php" class="grid-form" id="editForm">
                <input type="hidden" name="id_drop" id="edit_id_drop">

                <!-- Baris 1 -->
                <div>
                    <label>Nama Pelanggan</label>
                    <input type="text" id="edit_customer_name" name="customer_name" required>
                </div>
                <div>
                    <label>No. Handphone</label>
                    <input type="text" id="edit_customer_phone" name="phone_number" required>
                </div>

                <!-- Baris 2 -->
                <div>
                    <label>Brand / Merk</label>
                    <input type="text" id="edit_brand" name="brand" required>
                </div>

                <div>
                    <label for="edit_service_id">Layanan</label>
                    <select name="service_id" id="edit_service_id" required>
                        <option value="">-- Pilih Layanan --</option>
                        <?php
                        $order = "FIELD(category, 'cleaning', 'reglue', 'repaint', 'bag', 'cap'), service_name";
                        $query = "SELECT id_service, category, service_name FROM services ORDER BY $order";
                        $result = mysqli_query($conn, $query);
                        while ($row = mysqli_fetch_assoc($result)) {
                            $displayName = ucfirst($row['category']) . " - " . ucfirst($row['service_name']);
                            echo "<option value='{$row['id_service']}'>{$displayName}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <label>Harga</label>
                    <input type="text" id="edit_price_display" name="price_display" style="background:#f9f9f9;"
                        readonly>
                    <input type="hidden" name="price_min" id="edit_price_min">
                    <input type="hidden" name="price_max" id="edit_price_max">
                </div>

                <div>
                    <label>Estimasi Selesai</label>
                    <input type="text" id="edit_estimate_desc" name="estimate_desc" placeholder="Contoh: 2 Hari"
                        readonly style="background:#f9f9f9;">
                </div>

                <!-- Baris 3 -->
                <div>
                    <label>Tgl. Transaksi</label>
                    <input type="date" id="edit_tanggal_masuk" name="tanggal_masuk">
                </div>
                <div>
                    <label>Estimasi Selesai</label>
                    <input type="date" id="edit_tanggal_selesai" name="tanggal_selesai" style="background:#f9f9f9;">
                </div>

                <!-- Baris 4 -->
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

                <!-- Baris 5 -->
                <div>
                    <label>Tanggal Pembayaran</label>
                    <input type="date" id="edit_payment_date" name="payment_date">
                </div>

                <div>
                    <label>Metode Pembayaran</label>
                    <select name="payment_method" id="edit_payment_method">
                        <option value="">-- Pilih Metode --</option>
                        <option value="Tunai">Tunai</option>
                        <option value="Transfer">Transfer</option>
                    </select>
                </div>

                <div>
                    <label>Nominal Pembayaran</label>
                    <input type="number" name="amount_paid" id="edit_amount_paid"
                        placeholder="Masukkan nominal pembayaran">
                </div>

                <div class="full-width">
                    <button type="submit" class="save-btn">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

</main>

<script>
// PENTING: Embed data PHP sebelum load external JS
const deadlinesData = <?php echo json_encode($deadlines); ?>;
console.log('Deadlines data loaded:', deadlinesData.length, 'items');

  // Ambil elemen modal dan tombol close
  const editModal = document.getElementById("editModal");
  const closeBtn = editModal.querySelector(".close");

  // Fungsi untuk membuka modal
  function openEditModal(data) {
    // isi field data jika perlu
    document.getElementById("edit_customer_name").value = data.customer_name || "";
    document.getElementById("edit_brand").value = data.brand || "";
    // ... isi field lainnya ...

    editModal.style.display = "flex"; // tampilkan modal
  }

  // Tutup modal jika klik tombol X
  closeBtn.onclick = () => {
    editModal.style.display = "none";
  };

  // Tutup modal jika klik di luar area modal
  window.onclick = (e) => {
    if (e.target === editModal) {
      editModal.style.display = "none";
    }
  };

</script>

<!-- Load external JavaScript -->
<script src="../js/dashboard.js"></script>

<?php 
include('../partials/footer.php'); 
?>