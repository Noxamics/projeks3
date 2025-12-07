<?php
include('../partials/headerAdmin.php');
include('../db.php'); // koneksi database
?>
<link rel="stylesheet" href="../css/drop.css">

<main class="drop-page">
    <div class="drop-container">
        <h1 class="title">Drop</h1>

        <div class="top-bar">
            <div class="left-bar">
                <button class="add-btn" id="openAddModal">Tambah Barang</button>

                <form method="GET" action="" class="search-box">
                    <input type="text" name="search" placeholder="Cari data..."
                        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    <button type="submit" class="search-btn">
                        <img src="../a/assets/pencarian.png" alt="Cari" />
                    </button>
                </form>
            </div>

            <div class="right-tools">
                <form method="GET" id="filterForm">
                    <select id="sort" name="sort" class="filter-select"
                        onchange="document.getElementById('filterForm').submit()">
                        <option value="" disabled selected hidden>-- Pilih --</option>
                        <option value="nama_asc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'nama_asc') ? 'selected' : '' ?>>Nama (A-Z)</option>
                        <option value="nama_desc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'nama_desc') ? 'selected' : '' ?>>Nama (Z-A)</option>
                        <option value="tanggal_desc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'tanggal_desc') ? 'selected' : '' ?>>Tanggal Terlama</option>
                        <option value="tanggal_asc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'tanggal_asc') ? 'selected' : '' ?>>Tanggal Terbaru</option>
                    </select>
                </form>
                
                <button class="print-btn" id="printSelectedBtn" title="Cetak Struk Terpilih" style="margin-right: 10px;">
                    Cetak Struk
                </button>

                <button class="delete-btn" title="Hapus">
                    <img src="../a/svg/trash.svg" alt="Hapus" class="delete-icon">
                </button>
            </div>
        </div>
        
        <!-- MODAL ADD -->
        <div class="modal" id="addModal" style="display:none;">
            <div class="modal-content large">
                <span class="close" data-target="addModal">&times;</span>
                <h2>Tambah Barang</h2>

                <form method="POST" action="drop_add.php" class="grid-form" id="addForm">
                    <div>
                        <label>Nama Pelanggan</label>
                        <input type="text" id="customer_name" name="customer_name" required>
                    </div>
                    <div>
                        <label>No. Handphone</label>
                        <input type="text" id="customer_phone" name="phone_number" required>
                    </div>

                    <div>
                        <label>Brand / Merk</label>
                        <input type="text" name="brand" required>
                    </div>

                    <div>
                        <label for="service_id">Layanan</label>
                        <select name="service_id" id="service_id" required>
                            <option value="">-- Pilih Layanan --</option>
                            <?php
                            $order = "FIELD(category, 'cleaning', 'reglue', 'repaint', 'bag', 'cap'), service_name";
                            $query = "SELECT id_service, category, service_name, price_min, duration FROM services ORDER BY $order";
                            $result = mysqli_query($conn, $query);
                            while ($r = mysqli_fetch_assoc($result)) {
                                $displayName = ucfirst($r['category']) . " - " . ucfirst($r['service_name']);
                                echo "<option value='{$r['id_service']}' data-price='{$r['price_min']}' data-duration='{$r['duration']}'>{$displayName}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label>Harga (min)</label>
                        <input type="text" id="price_display" name="price_display" style="background:#f9f9f9;" readonly value="Rp 0">
                        <input type="hidden" name="price_min" id="price_min" value="0">
                        <input type="hidden" name="price_max" id="price_max" value="0">
                    </div>

                    <div>
                        <label>Estimasi Selesai</label>
                        <input type="text" id="estimate_desc" name="estimate_desc" placeholder="Contoh: 2 Hari" readonly style="background:#f9f9f9;" value="-">
                        <input type="hidden" id="duration" name="duration" value="0">
                    </div>

                    <div>
                        <label>Tgl. Transaksi</label>
                        <input type="date" id="tanggal_masuk" name="tanggal_masuk" value="<?= date('Y-m-d') ?>">
                    </div>

                    <div>
                        <label for="tanggal_selesai">Tanggal Estimasi Selesai</label>
                        <input type="date" id="tanggal_selesai" name="tanggal_selesai" readonly style="background:#f9f9f9;"> 
                    </div>

                    <div>
                        <label>Status</label>
                        <select name="status_id" id="statusSelect" required>
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
                        <select name="payment_status" id="payment_status">
                            <option value="Belum Lunas">Belum Lunas</option>
                            <option value="Lunas">Lunas</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>

                    <div>
                        <label>Tanggal Pembayaran</label>
                        <input 
                            type="date" 
                            id="payment_date_display" 
                            readonly 
                            disabled
                            style="background:#f9f9f9; cursor: not-allowed;">
                        <input 
                            type="hidden" 
                            id="payment_date_hidden" 
                            name="payment_date">
                    </div>

                    <div>
                        <label>Metode Pembayaran</label>
                        <select name="payment_method" required>
                            <option value="Tunai">Tunai</option>
                            <option value="Transfer">Transfer</option>
                            <option value="QRIS">QRIS</option>
                            <option value="Debit">Debit</option>
                            <option value="Credit">Credit</option>
                        </select>
                    </div>

                    <div>
                        <label for="amount_paid">Nominal Pembayaran</label>
                        <input type="text" id="amount_paid_display" placeholder="Masukkan nominal pembayaran">
                        <input type="hidden" name="amount_paid" id="amount_paid">
                    </div>

                    <div>
                        <label>Karyawan</label>
                        <?php
                        $activeEmployees = $conn->query("SELECT id_employee, name FROM employees WHERE status = 'Aktif'");
                        $employeeCount = $activeEmployees->num_rows;

                        if ($employeeCount === 0) {
                            echo "<input type='text' value='Tidak ada karyawan aktif' readonly style='background:#f9f9f9; color:#888;'>";
                        } elseif ($employeeCount === 1) {
                            $emp = $activeEmployees->fetch_assoc();
                            echo "
                                <input type='hidden' name='employee_id' value='{$emp['id_employee']}'>
                                <input type='text' value='{$emp['name']}' readonly style='background:#f9f9f9;'>
                            ";
                        } else {
                            echo "<select name='employee_id' required>
                                <option value=''>-- Pilih Karyawan --</option>";
                            $activeEmployees->data_seek(0);
                            while ($emp = $activeEmployees->fetch_assoc()) {
                                echo "<option value='{$emp['id_employee']}'>{$emp['name']}</option>";
                            }
                            echo "</select>";
                        }
                        ?>
                    </div>

                    <div class="full-width">
                        <label>Catatan Pelanggan</label>
                        <textarea name="note" rows="3" placeholder="Contoh: Jangan dicampur warna lain."
                        style="width:100%; padding:10px; border-radius:8px; border:1px solid #ccc;"></textarea>
                    </div>

                    <div class="full-width" style="display: flex; gap: 10px; justify-content: center;">
                        <button type="submit" class="save-btn" id="saveOnlyBtn">Simpan</button>
                        <button type="button" class="print-btn" id="saveAndPrintBtn">Simpan & Cetak Struk</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-container">
            <table id="dropTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>ID</th>
                        <th>Customer Name</th>
                        <th>Brand/Merk</th>
                        <th>Layanan</th>
                        <th>Tgl. Transaksi</th>
                        <th>Estimasi Selesai</th>
                        <th>Proses</th>
                        <th>Pembayaran</th>
                        <th>Karyawan</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
                    $sort = isset($_GET['sort']) ? $_GET['sort'] : '';
                    $sql = "
SELECT 
    d.id_drop,
    d.order_code,
    d.customer_id,
    d.service_id,
    d.brand,
    d.trans_date,
    d.est_finish_date,
    d.status_id,
    d.employee_id,
    d.note,
    c.name,
    c.phone,
    s.service_name,
    s.category,
    s.price_min, 
    s.duration,
    e.name AS employee_name,
    st.status_name,
    p.payment_method,
    p.payment_date,
    p.amount_paid,
    p.status AS pay_status
FROM drops d
JOIN customers c ON d.customer_id = c.id_customer
JOIN services s ON d.service_id = s.id_service
LEFT JOIN employees e ON d.employee_id = e.id_employee
LEFT JOIN statuses st ON d.status_id = st.id_status
LEFT JOIN payments p ON d.id_drop = p.drop_id
WHERE 
    c.name LIKE '%$search%' OR
    d.brand LIKE '%$search%' OR
    s.service_name LIKE '%$search%' OR
    s.category LIKE '%$search%' OR
    st.status_name LIKE '%$search%' OR
    e.name LIKE '%$search%'
";

                    switch ($sort) {
                        case 'nama_asc':
                            $sql .= " ORDER BY c.name ASC";
                            break;
                        case 'nama_desc':
                            $sql .= " ORDER BY c.name DESC";
                            break;
                        case 'tanggal_asc':
                            $sql .= " ORDER BY d.trans_date ASC";
                            break;
                        case 'tanggal_desc':
                            $sql .= " ORDER BY d.trans_date DESC";
                            break;
                        default:
                            $sql .= " ORDER BY d.trans_date DESC";
                    }

                    $result = $conn->query($sql);
                    while ($row = $result->fetch_assoc()) {
                        $duration = intval($row['duration']); 
                        $estimate_desc = ($duration > 0) ? "{$duration} Hari" : "-";
                        
                        echo "<tr class='data-row'
    data-id_drop='{$row['id_drop']}'
    data-customer_name=\"" . htmlspecialchars($row['name'], ENT_QUOTES) . "\"
    data-phone_number=\"" . htmlspecialchars($row['phone'], ENT_QUOTES) . "\"
    data-brand=\"" . htmlspecialchars($row['brand'], ENT_QUOTES) . "\"
    data-service_id='{$row['service_id']}'
    data-employee_id='{$row['employee_id']}'
    data-price_min='{$row['price_min']}'
    data-duration='{$duration}'
    data-estimate_desc='{$estimate_desc}'
    data-tanggal_masuk='{$row['trans_date']}'
    data-tanggal_selesai='{$row['est_finish_date']}'
    data-status_id='{$row['status_id']}'
    data-payment_status='" . htmlspecialchars($row['pay_status'] ?? '', ENT_QUOTES) . "'
    data-payment_date='" . htmlspecialchars($row['payment_date'] ?? '', ENT_QUOTES) . "'
    data-payment_method='" . htmlspecialchars($row['payment_method'] ?? '', ENT_QUOTES) . "'
    data-amount_paid='" . htmlspecialchars($row['amount_paid'] ?? '', ENT_QUOTES) . "'
    data-note=\"" . htmlspecialchars($row['note'] ?? '', ENT_QUOTES) . "\">";
                        
                        echo "<td><input type='checkbox' class='row-checkbox' value='{$row['id_drop']}'></td>";
                        echo "<td>" . htmlspecialchars($row['order_code']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['brand']) . "</td>";
                        echo "<td><span class='service-text'><span class='light'>" . ucfirst(htmlspecialchars($row['category'])) . "</span> <span class='bold'>" . htmlspecialchars($row['service_name']) . "</span></span></td>";
                        echo "<td>" . date('d F Y', strtotime($row['trans_date'])) . "</td>";
                        echo "<td>" . date('d F Y', strtotime($row['est_finish_date'])) . "</td>";
                        echo "<td>
                        <select class='status-dropdown' data-id='{$row['id_drop']}'>
                            <option value='' disabled>Pilih Status</option>";

                        $st2 = $conn->query("SELECT * FROM statuses ORDER BY id_status ASC");
                        while ($s2 = $st2->fetch_assoc()) {
                            $selected = ($s2['id_status'] == $row['status_id']) ? 'selected' : '';
                            echo "<option value='{$s2['id_status']}' {$selected}>{$s2['status_name']}</option>";
                        }

                        echo "</select></td>";

                        echo "<td>" . htmlspecialchars($row['pay_status'] ?? '-') . "</td>";
                        echo "<td>" . htmlspecialchars($row['employee_name'] ?? '-') . "</td>";
                        echo "<td>" . (!empty($row['note']) ? htmlspecialchars($row['note']) : '-') . "</td>";
                        echo "</tr>";
                    }

                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div id="successModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 400px; text-align: center;">
            <p id="successMessage" style="font-size: 16px; font-weight: 600; color: #004d9d;">Data berhasil disimpan</p>
            <button id="closeSuccess" class="save-btn" style="width:auto; margin-top:15px;">OK</button>
        </div>
    </div>

    <!-- MODAL EDIT -->
    <div class="modal" id="editModal" style="display:none;">
        <div class="modal-content large">
            <span class="close" data-target="editModal">&times;</span>
            <h2>Edit Barang</h2>

            <form method="POST" action="drop_edit.php" class="grid-form" id="editForm">
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
                    <select name="service_id" id="edit_service_id" required>
                        <option value="">-- Pilih Layanan --</option>
                        <?php
                        $order = "FIELD(category, 'cleaning', 'reglue', 'repaint', 'bag', 'cap'), service_name";
                        $query = "SELECT id_service, category, service_name, price_min, duration FROM services ORDER BY $order";
                        $result = mysqli_query($conn, $query);
                        while ($r = mysqli_fetch_assoc($result)) {
                            $displayName = ucfirst($r['category']) . " - " . ucfirst($r['service_name']);
                            echo "<option value='{$r['id_service']}' data-price='{$r['price_min']}' data-duration='{$r['duration']}'>{$displayName}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <label>Harga (min)</label>
                    <input type="text" id="edit_price_display" name="price_display" style="background:#f9f9f9;" readonly value="Rp 0">
                    <input type="hidden" name="price_min" id="edit_price_min" value="0">
                    <input type="hidden" name="price_max" id="edit_price_max" value="0">
                </div>

                <div>
                    <label>Estimasi Selesai</label>
                    <input type="text" id="edit_estimate_desc" name="estimate_desc" placeholder="Contoh: 2 Hari" readonly style="background:#f9f9f9;" value="-">
                    <input type="hidden" id="edit_duration" name="duration" value="0">
                </div>

                <div>
                    <label>Tgl. Transaksi</label>
                    <input type="date" id="edit_tanggal_masuk" name="tanggal_masuk">
                </div>
                <div>
                    <label>Tanggal Estimasi Selesai</label>
                    <input type="date" id="edit_tanggal_selesai" name="tanggal_selesai" readonly style="background:#f9f9f9;">
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
                        <option value="Pending">Pending</option>
                    </select>
                </div>

                <div>
                    <label>Tanggal Pembayaran</label>
                    <input 
                        type="date" 
                        id="edit_payment_date_display" 
                        readonly 
                        disabled
                        style="background:#f9f9f9; cursor: not-allowed;">
                    <input 
                        type="hidden" 
                        id="edit_payment_date_hidden" 
                        name="payment_date">
                </div>

                <div>
                    <label>Metode Pembayaran</label>
                    <select name="payment_method" id="edit_payment_method">
                        <option value="Tunai">Tunai</option>
                        <option value="Transfer">Transfer</option>
                        <option value="QRIS">QRIS</option>
                        <option value="Debit">Debit</option>
                        <option value="Credit">Credit</option>
                    </select>
                </div>

                <div>
                    <label for="edit_amount_paid">Nominal Pembayaran</label>
                    <input type="text" id="edit_amount_paid_display" placeholder="Masukkan nominal pembayaran">
                    <input type="hidden" name="amount_paid" id="edit_amount_paid">
                </div>

                <div>
                    <label>Karyawan</label>
                    <?php
                    $activeEmployees = $conn->query("SELECT id_employee, name FROM employees WHERE status = 'Aktif'");
                    $employeeCount = $activeEmployees->num_rows;

                    if ($employeeCount === 0) {
                        echo "<input type='text' value='Tidak ada karyawan aktif' readonly style='background:#f9f9f9; color:#888;'>";
                    } elseif ($employeeCount === 1) {
                        $emp = $activeEmployees->fetch_assoc();
                        echo "
                            <input type='hidden' name='employee_id' id='edit_employee_id_hidden' value='{$emp['id_employee']}'>
                            <input type='text' value='{$emp['name']}' readonly style='background:#f9f9f9;'>
                        ";
                    } else {
                        echo "<select name='employee_id' id='edit_employee_id' required>";
                        echo "<option value=''>-- Pilih Karyawan --</option>";
                        $activeEmployees->data_seek(0);
                        while ($emp = $activeEmployees->fetch_assoc()) {
                            echo "<option value='{$emp['id_employee']}'>{$emp['name']}</option>";
                        }
                        echo "</select>";
                    }
                    ?>
                </div>

                <div class="full-width">
                    <label>Catatan Pelanggan</label>
                    <textarea name="note" id="edit_note" rows="3" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ccc;"></textarea>
                </div>

                <div class="full-width">
                    <button type="submit" class="save-btn">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</main>

<div id="confirmDeleteModal" class="confirm-modal" style="display:none;">
    <div class="confirm-content">
        <h3>Yakin ingin menghapus data ini?</h3>
        <p class="warning-text">Data yang dihapus tidak dapat dikembalikan!</p>
        <div class="confirm-actions">
            <button id="confirmOk" class="btn-ok">OK</button>
            <button id="confirmCancel" class="btn-cancel">Cancel</button>
        </div>
    </div>
</div>

<?php include_once "../partials/footer.php"; ?>

<script>
// ============================================================
// DROP.PHP - FIXED JAVASCRIPT SECTION
// Ganti SELURUH bagian <script> di drop.php dengan kode ini
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    console.log("✅ Page loaded, initializing...");

    // ===== HELPER FUNCTIONS =====
    const formatRupiah = (angka) => {
        if (typeof angka !== 'number') return 'Rp 0';
        return 'Rp ' + angka.toLocaleString('id-ID');
    };

    const parseRupiah = (rupiah) => {
        return parseInt(rupiah.replace(/[^0-9]/g, '') || 0);
    };

    function showModal(id) {
        const m = document.getElementById(id);
        if (m) m.style.display = 'block';
    }
    
    function hideModal(id) {
        const m = document.getElementById(id);
        if (m) m.style.display = 'none';
    }
    
    // ===== PAYMENT DATE FUNCTIONS =====
    
    function setPaymentDate(displayId, hiddenId, date) {
        const display = document.getElementById(displayId);
        const hidden = document.getElementById(hiddenId);
        
        console.log(`[setPaymentDate] displayId: ${displayId}, hiddenId: ${hiddenId}, date: ${date}`);
        
        if (display && hidden) {
            display.value = date || '';
            hidden.value = date || '';
            hidden.dataset.originalDate = date || '';
            
            console.log(`✅ Display value: "${display.value}"`);
            console.log(`✅ Hidden value: "${hidden.value}"`);
        } else {
            console.error(`❌ Field not found! Display: ${display}, Hidden: ${hidden}`);
        }
    }

    function handlePaymentStatusChange(statusValue, displayId, hiddenId, existingDate = null) {
        console.log(`=== handlePaymentStatusChange ===`);
        console.log(`Status: ${statusValue}`);
        console.log(`Existing date: ${existingDate}`);
        
        const hiddenField = document.getElementById(hiddenId);
        
        if (statusValue === 'Lunas') {
            if (existingDate) {
                setPaymentDate(displayId, hiddenId, existingDate);
                console.log(`✅ Using existing date (locked): ${existingDate}`);
            } else {
                const today = new Date().toISOString().split('T')[0];
                setPaymentDate(displayId, hiddenId, today);
                console.log(`✅ Using today's date: ${today}`);
            }
        } else {
            setPaymentDate(displayId, hiddenId, '');
            if (hiddenField) {
                hiddenField.dataset.originalDate = '';
            }
            console.log(`⚠️ Payment date cleared (status not Lunas)`);
        }
    }

    // ===== OPEN ADD MODAL =====
    
    document.getElementById('openAddModal').addEventListener('click', function() {
        console.log("Opening ADD modal...");
        
        document.getElementById('addForm').reset();
        document.getElementById('tanggal_masuk').value = new Date().toISOString().split('T')[0];
        document.getElementById('price_display').value = 'Rp 0';
        document.getElementById('price_min').value = '0';
        document.getElementById('price_max').value = '0';
        document.getElementById('estimate_desc').value = '-';
        document.getElementById('duration').value = '0';
        document.getElementById('tanggal_selesai').value = '';
        document.getElementById('amount_paid_display').value = ''; 
        document.getElementById('amount_paid').value = '0';
        
        setPaymentDate('payment_date_display', 'payment_date_hidden', '');
        
        showModal('addModal');
        
        const payStatus = document.getElementById('payment_status');
        if (payStatus) {
            handlePaymentStatusChange(payStatus.value, 'payment_date_display', 'payment_date_hidden');
        }
    });

    // Close buttons
    document.querySelectorAll('.close').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const target = btn.getAttribute('data-target');
            if (target) hideModal(target);
        });
    });

    // ===== SERVICE LOGIC =====

    const serviceSelectAdd = document.getElementById('service_id');
    const priceDisplayAdd = document.getElementById('price_display');
    const priceMinInputAdd = document.getElementById('price_min');
    const estimateDescAdd = document.getElementById('estimate_desc');
    const durationInputAdd = document.getElementById('duration');
    const tanggalMasukAdd = document.getElementById('tanggal_masuk');
    const tanggalSelesaiAdd = document.getElementById('tanggal_selesai');

    const fetchDynamicEstimate = async (serviceId, transDate, excludeDropId = 0) => {
        try {
            const formData = new FormData();
            formData.append('service_id', serviceId);
            formData.append('trans_date', transDate);
            formData.append('exclude_drop_id', excludeDropId);
            
            const response = await fetch('get_dynamic_estimate.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) throw new Error('Network response was not ok');
            return await response.json();
        } catch (error) {
            console.error('Error fetching dynamic estimate:', error);
            return null;
        }
    };

    const updateServiceDataDynamic = async (serviceSelect, priceDisplay, priceMinInput, estimateDesc, durationInput, tanggalMasuk, tanggalSelesai, excludeDropId = 0) => {
        const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
        
        if (selectedOption && selectedOption.value) {
            const serviceId = selectedOption.value;
            const transDate = tanggalMasuk.value;
            
            estimateDesc.value = 'Menghitung...';
            durationInput.value = '0';
            tanggalSelesai.value = '';
            
            const result = await fetchDynamicEstimate(serviceId, transDate, excludeDropId);
            
            if (result && result.success) {
                priceDisplay.value = formatRupiah(result.price_min);
                priceMinInput.value = result.price_min;
                durationInput.value = result.duration;
                estimateDesc.value = result.estimate_desc;
                tanggalSelesai.value = result.estimate_date;
                if (result.queue_info) console.log(result.queue_info);
            } else {
                const priceMin = parseFloat(selectedOption.getAttribute('data-price') || 0);
                const duration = parseInt(selectedOption.getAttribute('data-duration') || 0);
                priceDisplay.value = formatRupiah(priceMin);
                priceMinInput.value = priceMin;
                durationInput.value = duration;
                estimateDesc.value = (duration > 0) ? `${duration} Hari` : '-';
                const date = new Date(transDate);
                date.setDate(date.getDate() + duration);
                tanggalSelesai.value = date.toISOString().split('T')[0];
            }
        } else {
            priceDisplay.value = 'Rp 0';
            priceMinInput.value = '0';
            durationInput.value = '0';
            estimateDesc.value = '-';
            tanggalSelesai.value = '';
        }
    };

    if (serviceSelectAdd) {
        serviceSelectAdd.addEventListener('change', () => {
            updateServiceDataDynamic(serviceSelectAdd, priceDisplayAdd, priceMinInputAdd, estimateDescAdd, durationInputAdd, tanggalMasukAdd, tanggalSelesaiAdd);
        });
    }
    
    if (tanggalMasukAdd) {
        tanggalMasukAdd.addEventListener('change', () => {
            if (serviceSelectAdd.value) {
                updateServiceDataDynamic(serviceSelectAdd, priceDisplayAdd, priceMinInputAdd, estimateDescAdd, durationInputAdd, tanggalMasukAdd, tanggalSelesaiAdd);
            }
        });
    }
    
    // ===== RUPIAH FORMATTING =====

    const setupRupiahInput = (displayInputId, hiddenInputId) => {
        const displayInput = document.getElementById(displayInputId);
        const hiddenInput = document.getElementById(hiddenInputId);

        if (displayInput && hiddenInput) {
            if (hiddenInput.value && parseFloat(hiddenInput.value) > 0) {
                displayInput.value = formatRupiah(parseFloat(hiddenInput.value));
            } else {
                displayInput.value = '';
            }

            displayInput.addEventListener('input', function(e) {
                let value = e.target.value;
                const numericValue = parseRupiah(value);
                hiddenInput.value = numericValue;
                e.target.value = formatRupiah(numericValue);
            });
            
            displayInput.addEventListener('blur', function(e) {
                 e.target.value = formatRupiah(parseRupiah(e.target.value));
            });
        }
    };

    setupRupiahInput('amount_paid_display', 'amount_paid');
    
    // ===== EVENT LISTENERS PAYMENT STATUS =====
    
    const payStatusAdd = document.getElementById('payment_status');
    if (payStatusAdd) {
        console.log("✅ ADD payment status listener attached");
        payStatusAdd.addEventListener('change', function() {
            console.log("📍 ADD: Payment status changed to:", this.value);
            handlePaymentStatusChange(this.value, 'payment_date_display', 'payment_date_hidden');
        });
    }
    
    const payStatusEdit = document.getElementById('edit_payment_status');
    if (payStatusEdit) {
        console.log("✅ EDIT payment status listener attached");
        payStatusEdit.addEventListener('change', function() {
            console.log("📍 EDIT: Payment status changed to:", this.value);
            const hiddenField = document.getElementById('edit_payment_date_hidden');
            const existingDate = hiddenField ? hiddenField.dataset.lockedDate : null;
            handlePaymentStatusChange(this.value, 'edit_payment_date_display', 'edit_payment_date_hidden', existingDate);
        });
    }

    // ===== EDIT MODAL - DOUBLE CLICK =====

    document.querySelectorAll('.data-row').forEach(function(row) {
        row.addEventListener('dblclick', async function() {
            console.log("Opening EDIT modal...");
            
            const id = row.dataset.id_drop || '';
            if (!id) return;

            document.getElementById('edit_id_drop').value = id;
            document.getElementById('edit_customer_name').value = row.dataset.customer_name || '';
            document.getElementById('edit_customer_phone').value = row.dataset.phone_number || '';
            document.getElementById('edit_brand').value = row.dataset.brand || '';
            
            const serviceSelectEdit = document.getElementById('edit_service_id');
            const priceDisplayEdit = document.getElementById('edit_price_display');
            const priceMinInputEdit = document.getElementById('edit_price_min');
            const estimateDescEdit = document.getElementById('edit_estimate_desc');
            const durationInputEdit = document.getElementById('edit_duration'); 
            const tanggalMasukEdit = document.getElementById('edit_tanggal_masuk');
            const tanggalSelesaiEdit = document.getElementById('edit_tanggal_selesai');

            serviceSelectEdit.value = row.dataset.service_id || '';
            priceMinInputEdit.value = row.dataset.price_min || '0';
            priceDisplayEdit.value = formatRupiah(parseFloat(row.dataset.price_min || 0));
            durationInputEdit.value = row.dataset.duration || '0'; 
            estimateDescEdit.value = row.dataset.estimate_desc || '-'; 
            tanggalMasukEdit.value = row.dataset.tanggal_masuk || '';
            tanggalSelesaiEdit.value = row.dataset.tanggal_selesai || ''; 
            document.getElementById('edit_statusSelect').value = row.dataset.status_id || '';

            const payStatusValue = row.dataset.payment_status || 'Belum Lunas';
            const paymentDateValue = row.dataset.payment_date || '';
            
            console.log("Edit data - Payment Status:", payStatusValue);
            console.log("Edit data - Payment Date:", paymentDateValue);
            
            document.getElementById('edit_payment_status').value = payStatusValue;
            document.getElementById('edit_payment_method').value = row.dataset.payment_method || 'Tunai';
            
            setPaymentDate('edit_payment_date_display', 'edit_payment_date_hidden', paymentDateValue);
            
            const editPayDateHidden = document.getElementById('edit_payment_date_hidden');
            if (editPayDateHidden) {
                editPayDateHidden.dataset.lockedDate = paymentDateValue;
                console.log("🔐 Locked date:", paymentDateValue);
            }
            
            const amountPaidValue = parseFloat(row.dataset.amount_paid || 0);
            document.getElementById('edit_amount_paid').value = amountPaidValue;
            document.getElementById('edit_amount_paid_display').value = amountPaidValue > 0 ? formatRupiah(amountPaidValue) : '';
            document.getElementById('edit_note').value = row.dataset.note || '';

            const empEl = document.getElementById('edit_employee_id');
            if (empEl) {
                 empEl.value = row.dataset.employee_id || ''; 
            } else {
                const empHiddenEl = document.getElementById('edit_employee_id_hidden');
                if (empHiddenEl) empHiddenEl.value = row.dataset.employee_id || '';
            }

            setupRupiahInput('edit_amount_paid_display', 'edit_amount_paid');
            
            const runUpdateEdit = async () => {
                await updateServiceDataDynamic(serviceSelectEdit, priceDisplayEdit, priceMinInputEdit, estimateDescEdit, durationInputEdit, tanggalMasukEdit, tanggalSelesaiEdit, id);
            };

            serviceSelectEdit.onchange = runUpdateEdit;
            tanggalMasukEdit.onchange = runUpdateEdit;

            showModal('editModal');
        });
    });
    
    // ===== SAVE ONLY BUTTON =====
    
    document.getElementById('saveOnlyBtn').addEventListener('click', function(e) {
        e.preventDefault();
        console.log("=== SAVE ONLY CLICKED ===");
        
        const form = document.getElementById('addForm');
        
        if (!form.checkValidity()) {
            console.error("❌ Form validation failed");
            form.reportValidity();
            return;
        }
        
        console.log("✅ Form valid, submitting...");
        
        const paymentStatus = document.getElementById('payment_status').value;
        const paymentDateHidden = document.getElementById('payment_date_hidden');
        
        if (paymentStatus === 'Lunas' && !paymentDateHidden.value) {
            const today = new Date().toISOString().split('T')[0];
            paymentDateHidden.value = today;
            console.log("⚠️ Auto-set payment_date to today:", today);
        }
        
        const formData = new FormData(form);
        
        console.log("=== FORM DATA BEING SENT ===");
        for (let [key, value] of formData.entries()) {
            console.log(`${key}: ${value}`);
        }
        
        fetch('drop_add.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log("📡 Response status:", response.status);
            
            if (!response.ok) {
                return response.text().then(text => {
                    console.error("❌ Response text:", text);
                    throw new Error(`HTTP ${response.status}: ${text.substring(0, 200)}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log("📥 Response data:", data);
            
            if (data.success && data.drop_id) {
                console.log("✅ Pesanan berhasil disimpan!");
                console.log("Drop ID:", data.drop_id);
                console.log("Order Code:", data.order_code);
                
                hideModal('addModal');
                
                sessionStorage.setItem('showSuccess', 'true');
                sessionStorage.setItem('successMessage', 'Pesanan berhasil ditambahkan!');
                
                setTimeout(() => {
                    console.log("🔄 Refreshing page...");
                    window.location.reload();
                }, 500);
                
            } else {
                console.error("❌ Server returned error:", data.message);
                alert("Gagal menyimpan pesanan: " + (data.message || "Unknown error"));
            }
        })
        .catch(error => {
            console.error("❌ Error:", error);
            alert("Terjadi kesalahan: " + error.message);
        });
    });
    
    // ===== SAVE & PRINT BUTTON =====

    document.getElementById('saveAndPrintBtn').addEventListener('click', function(e) {
        e.preventDefault();
        console.log("=== SAVE & PRINT CLICKED ===");
        
        const form = document.getElementById('addForm');
        
        if (!form.checkValidity()) {
            console.error("❌ Form validation failed");
            form.reportValidity();
            return;
        }
        
        console.log("✅ Form valid, submitting...");
        
        const paymentStatus = document.getElementById('payment_status').value;
        const paymentDateHidden = document.getElementById('payment_date_hidden');
        
        if (paymentStatus === 'Lunas' && !paymentDateHidden.value) {
            const today = new Date().toISOString().split('T')[0];
            paymentDateHidden.value = today;
            console.log("⚠️ Auto-set payment_date to today:", today);
        }
        
        const formData = new FormData(form);
        
        console.log("=== FORM DATA BEING SENT ===");
        for (let [key, value] of formData.entries()) {
            console.log(`${key}: ${value}`);
        }
        
        fetch('drop_add.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log("📍 Response status:", response.status);
            
            if (!response.ok) {
                return response.text().then(text => {
                    console.error("❌ Response text:", text);
                    throw new Error(`HTTP ${response.status}: ${text.substring(0, 200)}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log("📥 Response data:", data);
            
            if (data.success && data.drop_id) {
                console.log("✅ Pesanan berhasil disimpan!");
                
                hideModal('addModal');
                
                setTimeout(() => {
                    const drop_id = data.drop_id;
                    const cetak_url = `cetak_struk.php?id=${drop_id}`;
                    
                    console.log("📄 Opening print window:", cetak_url);
                    
                    const printWindow = window.open(
                        cetak_url,
                        'CetakStruk',
                        'width=600,height=800,resizable=yes,scrollbars=yes'
                    );
                    
                    if (printWindow) {
                        console.log("✅ Print window opened");
                    } else {
                        console.warn("⚠️ Print window blocked");
                        alert('Window cetak diblokir browser. Silakan allow popup.');
                    }
                    
                    setTimeout(() => {
                        sessionStorage.setItem('showSuccess', 'true');
                        sessionStorage.setItem('successMessage', 'Pesanan berhasil ditambahkan!');
                        window.location.reload();
                    }, 1000);
                    
                }, 500);
                
            } else {
                console.error("❌ Server error:", data.message);
                alert("Gagal menyimpan pesanan: " + (data.message || "Unknown error"));
            }
        })
        .catch(error => {
            console.error("❌ Error:", error);
            alert("Terjadi kesalahan: " + error.message);
        });
    });
    
    // ===== PRINT SELECTED =====
    
    document.getElementById('printSelectedBtn').addEventListener('click', function() {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        const ids = Array.from(checked).map(cb => cb.value);
        if (ids.length === 0) {
            alert('Pilih setidaknya satu data untuk dicetak struknya.');
            return;
        }
        
        console.log("📄 Opening print for IDs:", ids);
        window.open('cetak_struk.php?id=' + ids.join(','), '_blank');
    });

    // ===== SUCCESS MODAL =====

    if (sessionStorage.getItem('showSuccess') === 'true') {
        document.getElementById('successMessage').textContent = sessionStorage.getItem('successMessage');
        showModal('successModal');
        sessionStorage.removeItem('showSuccess');
        sessionStorage.removeItem('successMessage');
    }
    
    document.getElementById('closeSuccess').addEventListener('click', function() {
        hideModal('successModal');
    });

    // ===== DELETE LOGIC =====

    document.querySelector('.delete-btn').addEventListener('click', function() {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        if (checked.length === 0) {
            alert('Pilih setidaknya satu data untuk dihapus.');
            return;
        }
        showModal('confirmDeleteModal');
    });
    
    document.getElementById('confirmCancel').addEventListener('click', function() {
        hideModal('confirmDeleteModal');
    });

    document.getElementById('confirmOk').addEventListener('click', function() {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        const ids = Array.from(checked).map(cb => cb.value);
        hideModal('confirmDeleteModal');

        fetch('drop_delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ids=' + ids.join(',')
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                sessionStorage.setItem('showSuccess', 'true');
                sessionStorage.setItem('successMessage', data.message || 'Data berhasil dihapus.');
                window.location.reload();
            } else {
                alert('Gagal menghapus data: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghapus data: ' + error.message);
        });
    });
    
    // ===== SELECT ALL CHECKBOX =====
    
    document.getElementById('selectAll').addEventListener('change', function() {
        document.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });
    });
    
    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const allChecked = Array.from(document.querySelectorAll('.row-checkbox'))
                .every(checkbox => checkbox.checked);
            document.getElementById('selectAll').checked = allChecked;
        });
    });
    
    // ===== UPDATE STATUS =====
    
    document.querySelectorAll('.status-dropdown').forEach(select => {
        select.addEventListener('change', function() {
            const idDrop = this.getAttribute('data-id');
            const newStatusId = this.value;
            
            if (!idDrop || !newStatusId) {
                alert('Data tidak valid');
                return;
            }

            if (!confirm('Apakah Anda yakin ingin mengubah status pesanan ini?')) {
                this.value = this.dataset.oldValue || this.value;
                return;
            }

            const oldValue = this.dataset.oldValue || this.value;
            this.dataset.oldValue = oldValue;

            fetch('drop_update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id_drop=${idDrop}&status_id=${newStatusId}`
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    sessionStorage.setItem('showSuccess', 'true');
                    sessionStorage.setItem('successMessage', data.message || 'Status berhasil diubah.');
                    window.location.reload();
                } else {
                    alert('Gagal mengubah status: ' + (data.message || 'Unknown error'));
                    this.value = oldValue;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengubah status: ' + error.message);
                this.value = oldValue;
            });
        });
        
        select.dataset.oldValue = select.value;
    });
    
    console.log("✅ All event listeners initialized");
});
</script>