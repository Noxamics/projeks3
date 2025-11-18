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
                window.location.href = "dashboard.php";
            }, 2000);
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
                $category_query = "SELECT DISTINCT category FROM services WHERE category IS NOT NULL AND category != ''";
                $category_result = mysqli_query($conn, $category_query);
                
                while($category = mysqli_fetch_assoc($category_result)) {
                    echo "<button onclick=\"filterByCategory('{$category['category']}')\">{$category['category']}</button>";
                }
                ?>
            </div>

            <div class="timeline-body" id="orderTimeline">
                <?php
                // Query LENGKAP untuk mendapatkan SEMUA data
                $timeline_query = "
                    SELECT 
                        d.id_drop,
                        d.order_code,
                        d.trans_date,
                        d.est_finish_date,
                        d.status_id,
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
                        di.service_id as drop_item_service_id,
                        st.status_name,
                        p.status as payment_status,
                        p.payment_method,
                        p.payment_date,
                        p.amount_paid,
                        e.name as employee_name
                    FROM drops d
                    JOIN customers c ON d.customer_id = c.id_customer
                    JOIN drop_items di ON d.id_drop = di.drop_id
                    JOIN services s ON di.service_id = s.id_service
                    JOIN statuses st ON d.status_id = st.id_status
                    LEFT JOIN payments p ON d.id_drop = p.drop_id
                    LEFT JOIN employees e ON d.employee_id = e.id_employee
                    WHERE d.est_finish_date >= CURDATE() -- TAMBAHKAN INI: hanya tampilkan yang belum lewat deadline
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
                        
                        // Format harga
                        $price_display = '';
                        if ($order['price_min'] == $order['price_max']) {
                            $price_display = 'Rp ' . number_format($order['price_min'], 0, ',', '.');
                        } else {
                            $price_display = 'Rp ' . number_format($order['price_min'], 0, ',', '.') . ' - Rp ' . number_format($order['price_max'], 0, ',', '.');
                        }
                        
                        echo "
                        <div class='order-item' 
                             data-id-drop='" . htmlspecialchars($order['id_drop']) . "'
                            data-category='" . strtolower($order['category']) . "' 
                            data-order-code='" . htmlspecialchars($order['order_code']) . "'
                            data-customer='" . htmlspecialchars($order['customer_name']) . "'
                            data-phone='" . htmlspecialchars($order['phone_number']) . "'
                            data-service='" . htmlspecialchars($order['service_name']) . "'
                            data-service-id='" . htmlspecialchars($order['drop_item_service_id']) . "'
                            data-category-full='" . htmlspecialchars($order['category']) . "'
                            data-brand='" . htmlspecialchars($order['brand']) . "'
                            data-trans-date='" . htmlspecialchars($order['trans_date']) . "'
                            data-est-date='" . htmlspecialchars($order['est_finish_date']) . "'
                            data-status-id='" . htmlspecialchars($order['status_id']) . "'
                            data-price-min='" . htmlspecialchars($order['price_min']) . "'
                            data-price-max='" . htmlspecialchars($order['price_max']) . "'
                            data-price-display='" . htmlspecialchars($price_display) . "'
                            data-duration='" . htmlspecialchars($order['duration']) . "'
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

        <!-- UBAH ACTION MENJADI dashboard_edit.php -->
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
                <input type="text" id="edit_price_display" name="price_display" style="background:#f9f9f9;" readonly>
                <input type="hidden" name="price_min" id="edit_price_min">
                <input type="hidden" name="price_max" id="edit_price_max">
            </div>

            <div>
                <label>Estimasi Selesai</label>
                <input type="text" id="edit_estimate_desc" name="estimate_desc" placeholder="Contoh: 2 Hari" readonly style="background:#f9f9f9;">
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

// Update service info saat ganti layanan
function updateServiceInfo(serviceId) {
    if (!serviceId) return;
    
    fetch(`get_service_info.php?id_service=${serviceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }
            
            // Update harga
            document.getElementById('edit_price_min').value = data.price_min;
            document.getElementById('edit_price_max').value = data.price_max;
            
            let priceDisplay = '';
            if (data.price_min == data.price_max) {
                priceDisplay = formatRupiah(data.price_min);
            } else {
                priceDisplay = formatRupiah(data.price_min) + ' - ' + formatRupiah(data.price_max);
            }
            document.getElementById('edit_price_display').value = priceDisplay;
            
            // Update durasi
            document.getElementById('edit_estimate_desc').value = data.duration || '';
        })
        .catch(error => console.error('Error:', error));
}

// PERBAIKI function openEditModal - tambahkan debug dan pastikan service_id benar
function openEditModal(orderElement) {
    console.log('Opening edit modal...');
    
    // Debug detail untuk cap cleaning
    console.log('=== DEBUG CAP CLEANING ===');
    console.log('Order Element:', orderElement);
    console.log('All datasets:', orderElement.dataset);
    console.log('Service ID from dataset:', orderElement.dataset.serviceId);
    console.log('Service Name from dataset:', orderElement.dataset.service);
    console.log('Category from dataset:', orderElement.dataset.categoryFull);
    
    // Ambil semua data dari data attributes
    const data = {
        id_drop: orderElement.dataset.idDrop,
        customer_name: orderElement.dataset.customer,
        phone_number: orderElement.dataset.phone,
        brand: orderElement.dataset.brand,
        service_id: orderElement.dataset.serviceId, // PASTIKAN INI TERISI
        trans_date: orderElement.dataset.transDate,
        est_date: orderElement.dataset.estDate,
        status_id: orderElement.dataset.statusId,
        price_min: orderElement.dataset.priceMin,
        price_max: orderElement.dataset.priceMax,
        price_display: orderElement.dataset.priceDisplay,
        duration: orderElement.dataset.duration,
        payment_status: orderElement.dataset.paymentStatus || 'Belum Lunas',
        payment_method: orderElement.dataset.paymentMethod || 'Tunai',
        payment_date: orderElement.dataset.paymentDate || '',
        amount_paid: orderElement.dataset.amountPaid || '0',
        employee_id: orderElement.dataset.employeeId || '',
        employee_name: orderElement.dataset.employeeName || ''
    };
    
    console.log('Processed Data:', data);
    
    // ISI SEMUA FIELD FORM
    document.getElementById('edit_id_drop').value = data.id_drop;
    document.getElementById('edit_customer_name').value = data.customer_name;
    document.getElementById('edit_customer_phone').value = data.phone_number;
    document.getElementById('edit_brand').value = data.brand;
    
    // PERBAIKI BAGIAN SERVICE - lebih robust
    const serviceSelect = document.getElementById('edit_service_id');
    if (data.service_id && data.service_id !== 'null' && data.service_id !== '') {
        serviceSelect.value = data.service_id;
        console.log('✅ Service ID set to:', data.service_id);
        console.log('✅ Selected option:', serviceSelect.options[serviceSelect.selectedIndex]?.text);
        
        // Update harga dan durasi berdasarkan service yang dipilih
        updateServiceInfo(data.service_id);
    } else {
        console.warn('❌ Service ID tidak valid:', data.service_id);
        // Fallback: coba cari berdasarkan nama service
        for (let option of serviceSelect.options) {
            if (option.text.toLowerCase().includes(data.service_name?.toLowerCase())) {
                option.selected = true;
                console.log('🔄 Fallback: Service found by name:', option.text);
                updateServiceInfo(option.value);
                break;
            }
        }
    }
    
    // Isi tanggal
    document.getElementById('edit_tanggal_masuk').value = data.trans_date;
    document.getElementById('edit_tanggal_selesai').value = data.est_date;
    document.getElementById('edit_statusSelect').value = data.status_id;
    
    // Isi harga & durasi (sebagai backup jika updateServiceInfo gagal)
    document.getElementById('edit_price_min').value = data.price_min;
    document.getElementById('edit_price_max').value = data.price_max;
    document.getElementById('edit_price_display').value = data.price_display;
    document.getElementById('edit_estimate_desc').value = data.duration;
    
    // Isi pembayaran
    document.getElementById('edit_payment_status').value = data.payment_status;
    document.getElementById('edit_payment_method').value = data.payment_method;
    document.getElementById('edit_payment_date').value = data.payment_date ? data.payment_date.split(' ')[0] : '';
    document.getElementById('edit_amount_paid').value = data.amount_paid;
    document.getElementById('edit_amount_paid_display').value = formatRupiah(data.amount_paid);
    
    // Isi karyawan
    if (data.employee_id && data.employee_id !== 'null') {
        document.getElementById('edit_employee_id').value = data.employee_id;
    }
    
    // Tampilkan modal
    document.getElementById('editModal').style.display = 'block';
}

// PERBAIKI function updateServiceInfo - tambahkan error handling
function updateServiceInfo(serviceId) {
    if (!serviceId || serviceId === 'null') {
        console.warn('Service ID tidak valid untuk updateServiceInfo:', serviceId);
        return;
    }
    
    console.log('🔄 Updating service info for ID:', serviceId);
    
    fetch(`get_service_info.php?id_service=${serviceId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                console.error('Error from server:', data.error);
                return;
            }
            
            console.log('✅ Service info received:', data);
            
            // Update harga
            document.getElementById('edit_price_min').value = data.price_min;
            document.getElementById('edit_price_max').value = data.price_max;
            
            let priceDisplay = '';
            if (data.price_min == data.price_max) {
                priceDisplay = formatRupiah(data.price_min);
            } else {
                priceDisplay = formatRupiah(data.price_min) + ' - ' + formatRupiah(data.price_max);
            }
            document.getElementById('edit_price_display').value = priceDisplay;
            
            // Update durasi
            document.getElementById('edit_estimate_desc').value = data.duration || '';
            
        })
        .catch(error => {
            console.error('❌ Error fetching service info:', error);
            // Tetap gunakan harga dari data attributes sebagai fallback
        });
}

// Tutup modal
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Setup double click handler
document.addEventListener('DOMContentLoaded', function() {
    const orderItems = document.querySelectorAll('.order-item');
    
    orderItems.forEach(item => {
        item.style.cursor = 'pointer';
        item.addEventListener('dblclick', function() {
            openEditModal(this);
        });
    });
    
    // Format nominal pembayaran
    const amountInput = document.getElementById('edit_amount_paid_display');
    if (amountInput) {
        amountInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9]/g, '');
            e.target.value = formatRupiah(value);
            document.getElementById('edit_amount_paid').value = value;
        });
    }
    
    // Close modal dengan klik di luar
    window.onclick = function(e) {
        const modal = document.getElementById('editModal');
        if (e.target === modal) {
            closeEditModal();
        }
    }
});
</script>

<!-- Load external JavaScript -->
<script src="../js/dashboard.js"></script>

<?php 
include('../partials/footer.php'); 
?>