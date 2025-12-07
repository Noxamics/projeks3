<?php
// File: admin/drop.php - COMPLETE VERSION
// Handle AJAX requests untuk delete operations

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    include('../db.php');
    error_reporting(E_ALL);
    ini_set('display_errors', 0);

    $action = $_POST['action'];

    // ===== DELETE SINGLE ITEM =====
    if ($action === 'delete_single_item') {
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        $drop_id = isset($_POST['drop_id']) ? intval($_POST['drop_id']) : 0;

        if ($item_id <= 0 || $drop_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
            exit;
        }

        try {
            $conn->begin_transaction();

            // Hitung SEMUA item di drop ini
            $stmt_count = $conn->prepare("SELECT COUNT(*) as item_count FROM drop_items WHERE drop_id = ?");
            $stmt_count->bind_param("i", $drop_id);
            $stmt_count->execute();
            $item_count = intval($stmt_count->get_result()->fetch_assoc()['item_count'] ?? 0);
            $stmt_count->close();

            // Jika item terakhir -> hapus seluruh drop
            if ($item_count <= 1) {
                $stmt_p = $conn->prepare("DELETE FROM payments WHERE drop_id = ?");
                $stmt_p->bind_param("i", $drop_id);
                $stmt_p->execute();
                $stmt_p->close();

                $stmt_d = $conn->prepare("DELETE FROM deadlines WHERE drop_id = ?");
                $stmt_d->bind_param("i", $drop_id);
                $stmt_d->execute();
                $stmt_d->close();

                $stmt_i = $conn->prepare("DELETE FROM drop_items WHERE drop_id = ?");
                $stmt_i->bind_param("i", $drop_id);
                $stmt_i->execute();
                $stmt_i->close();

                $stmt_drop = $conn->prepare("DELETE FROM drops WHERE id_drop = ?");
                $stmt_drop->bind_param("i", $drop_id);
                $stmt_drop->execute();
                $stmt_drop->close();

                $conn->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Item terakhir dihapus — pesanan juga dihapus.',
                    'last_item' => true,
                    'drop_id' => $drop_id
                ]);
                exit;
            }

            // Hapus item saja
            $stmt_del = $conn->prepare("DELETE FROM drop_items WHERE id_item = ? AND drop_id = ?");
            $stmt_del->bind_param("ii", $item_id, $drop_id);
            $stmt_del->execute();
            $affected = $stmt_del->affected_rows;
            $stmt_del->close();

            if ($affected === 0) {
                throw new Exception("Item tidak ditemukan atau sudah dihapus");
            }

            // Reindex item_order
            $stmt_re = $conn->prepare("SELECT id_item FROM drop_items WHERE drop_id = ? ORDER BY item_order ASC");
            $stmt_re->bind_param("i", $drop_id);
            $stmt_re->execute();
            $res_re = $stmt_re->get_result();
            $order = 1;
            $stmt_update_order = $conn->prepare("UPDATE drop_items SET item_order = ? WHERE id_item = ?");
            while ($r = $res_re->fetch_assoc()) {
                $stmt_update_order->bind_param("ii", $order, $r['id_item']);
                $stmt_update_order->execute();
                $order++;
            }
            $stmt_re->close();
            $stmt_update_order->close();

            // Recalculate total
            $stmt_total = $conn->prepare("SELECT SUM(price) as total FROM drop_items WHERE drop_id = ?");
            $stmt_total->bind_param("i", $drop_id);
            $stmt_total->execute();
            $new_total = floatval($stmt_total->get_result()->fetch_assoc()['total'] ?? 0);
            $stmt_total->close();

            // Recalculate estimate
            $stmt_est = $conn->prepare("
                SELECT MAX(s.duration) as max_duration, d.trans_date
                FROM drop_items di
                JOIN services s ON di.service_id = s.id_service
                JOIN drops d ON di.drop_id = d.id_drop
                WHERE di.drop_id = ?
                GROUP BY d.trans_date
            ");
            $stmt_est->bind_param("i", $drop_id);
            $stmt_est->execute();
            $res_est = $stmt_est->get_result();
            $max_duration = 0;
            $new_est_date = null;
            if ($res_est->num_rows > 0) {
                $row_est = $res_est->fetch_assoc();
                $max_duration = intval($row_est['max_duration'] ?? 0);
                $trans_date = $row_est['trans_date'];
                $date = new DateTime($trans_date);
                $date->modify("+{$max_duration} days");
                $new_est_date = $date->format('Y-m-d');
            }
            $stmt_est->close();

            // Update drops
            $stmt_up = $conn->prepare("UPDATE drops SET total_amount = ?, est_finish_date = ? WHERE id_drop = ?");
            $stmt_up->bind_param("dsi", $new_total, $new_est_date, $drop_id);
            $stmt_up->execute();
            $stmt_up->close();

            // Update deadlines
            if ($new_est_date !== null) {
                $stmt_dead = $conn->prepare("UPDATE deadlines SET deadline_date = ? WHERE drop_id = ?");
                $stmt_dead->bind_param("si", $new_est_date, $drop_id);
                $stmt_dead->execute();
                $stmt_dead->close();
            }

            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Item berhasil dihapus',
                'last_item' => false,
                'drop_id' => $drop_id,
                'new_total' => $new_total,
                'remaining_items' => $item_count - 1
            ]);
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            error_log("DELETE SINGLE ITEM ERROR: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    // ===== DELETE ALL CUSTOMER ORDERS =====
    if ($action === 'delete_all_customer') {
        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        if ($customer_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID customer tidak valid']);
            exit;
        }

        try {
            $conn->begin_transaction();

            $stmt_d = $conn->prepare("SELECT id_drop FROM drops WHERE customer_id = ?");
            $stmt_d->bind_param("i", $customer_id);
            $stmt_d->execute();
            $res_d = $stmt_d->get_result();
            $drop_ids = [];
            while ($r = $res_d->fetch_assoc())
                $drop_ids[] = intval($r['id_drop']);
            $stmt_d->close();

            if (empty($drop_ids)) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Tidak ada pesanan ditemukan']);
                exit;
            }

            $deleted_drops = 0;
            $deleted_items = 0;

            foreach ($drop_ids as $did) {
                $stmt_cnt = $conn->prepare("SELECT COUNT(*) as cnt FROM drop_items WHERE drop_id = ?");
                $stmt_cnt->bind_param("i", $did);
                $stmt_cnt->execute();
                $cnt = intval($stmt_cnt->get_result()->fetch_assoc()['cnt'] ?? 0);
                $stmt_cnt->close();

                $stmt_p = $conn->prepare("DELETE FROM payments WHERE drop_id = ?");
                $stmt_p->bind_param("i", $did);
                $stmt_p->execute();
                $stmt_p->close();

                $stmt_dead = $conn->prepare("DELETE FROM deadlines WHERE drop_id = ?");
                $stmt_dead->bind_param("i", $did);
                $stmt_dead->execute();
                $stmt_dead->close();

                $stmt_it = $conn->prepare("DELETE FROM drop_items WHERE drop_id = ?");
                $stmt_it->bind_param("i", $did);
                $stmt_it->execute();
                $stmt_it->close();

                $stmt_drop = $conn->prepare("DELETE FROM drops WHERE id_drop = ?");
                $stmt_drop->bind_param("i", $did);
                $stmt_drop->execute();
                if ($stmt_drop->affected_rows > 0)
                    $deleted_drops++;
                $stmt_drop->close();

                $deleted_items += $cnt;
            }

            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => "Berhasil menghapus {$deleted_drops} pesanan dengan total {$deleted_items} item",
                'deleted_drops' => $deleted_drops,
                'deleted_items' => $deleted_items
            ]);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            error_log("DELETE ALL CUSTOMER ERROR: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Action tidak dikenali']);
    exit;
}

// ===== LOAD HEADER =====
include('../partials/headerAdmin.php');
include('../db.php');

// ===== QUERY DATA - INCLUDE SEMUA STATUS =====
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';

$sql_customers = "
    SELECT DISTINCT c.id_customer, c.name, c.phone
    FROM customers c
    INNER JOIN drops d ON c.id_customer = d.customer_id
    INNER JOIN drop_items di ON d.id_drop = di.drop_id
";

// WHERE clause untuk search
$where_added = false;
if (!empty($search)) {
    $esc = $conn->real_escape_string($search);
    $sql_customers .= " WHERE (c.name LIKE '%$esc%' OR c.phone LIKE '%$esc%')";
    $where_added = true;
}

// Sorting
switch ($sort) {
    case 'nama_asc':
        $sql_customers .= " ORDER BY c.name ASC";
        break;
    case 'nama_desc':
        $sql_customers .= " ORDER BY c.name DESC";
        break;
    case 'tanggal_asc':
        $sql_customers .= " ORDER BY (SELECT MIN(d2.trans_date) FROM drops d2 WHERE d2.customer_id = c.id_customer) ASC";
        break;
    case 'tanggal_desc':
        $sql_customers .= " ORDER BY (SELECT MAX(d2.trans_date) FROM drops d2 WHERE d2.customer_id = c.id_customer) DESC";
        break;
    default:
        $sql_customers .= " ORDER BY c.name ASC";
}

$result_customers = $conn->query($sql_customers);

// ===== HITUNG STATISTIK =====
$stats_query = "
    SELECT 
        COUNT(CASE WHEN di.status_id != 6 THEN 1 END) as active_count,
        COUNT(CASE WHEN di.status_id = 6 THEN 1 END) as completed_count
    FROM drop_items di
";

$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Set default values jika query gagal
$active_count = isset($stats['active_count']) ? intval($stats['active_count']) : 0;
$completed_count = isset($stats['completed_count']) ? intval($stats['completed_count']) : 0;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Drop</title>
    <link rel="stylesheet" href="../css/drop/drop.css">
</head>

<body>

    <main class="drop-page">
        <div class="drop-container">
            <h1 class="title">📦 Manajemen Drop</h1>

            <!-- TOP BAR -->
            <div class="top-bar">
                <div class="left-bar">
                    <button class="add-btn" id="openAddModal">➕ Tambah Pesanan</button>
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
                        <?php if (!empty($search)): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <?php endif; ?>
                        <select id="sort" name="sort" class="filter-select"
                            onchange="document.getElementById('filterForm').submit()">
                            <option value="" disabled selected hidden>🔍 Filter Data</option>
                            <option value="nama_asc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'nama_asc') ? 'selected' : '' ?>>Nama (A-Z)</option>
                            <option value="nama_desc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'nama_desc') ? 'selected' : '' ?>>Nama (Z-A)</option>
                            <option value="tanggal_desc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'tanggal_desc') ? 'selected' : '' ?>>Tanggal Terbaru</option>
                            <option value="tanggal_asc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'tanggal_asc') ? 'selected' : '' ?>>Tanggal Terlama</option>
                        </select>
                    </form>

                    <button class="print-btn" id="printSelectedBtn" title="Cetak Struk Terpilih">
                        🖨️ Cetak Struk
                    </button>

                    <button class="delete-btn" id="deleteBtn" title="Hapus Data Terpilih">
                        <img src="../a/svg/trash.svg" alt="Hapus" class="delete-icon">
                        Hapus
                    </button>
                </div>
            </div>

            <!-- TOGGLE & STATISTICS BAR -->
            <div class="stats-bar">
                <div class="stats-badges">
                    <div class="stat-badge stat-active">
                        <span class="stat-icon">📋</span>
                        <span class="stat-number"><?= $active_count ?></span>
                        <span class="stat-label">Aktif</span>
                    </div>
                    <div class="stat-badge stat-completed">
                        <span class="stat-icon">✓</span>
                        <span class="stat-number"><?= $completed_count ?></span>
                        <span class="stat-label">Selesai</span>
                    </div>
                </div>

                <!-- TOGGLE COMPLETED ITEMS -->
                <div class="toggle-completed-container">
                    <label class="toggle-switch">
                        <input type="checkbox" id="toggleCompleted" checked>
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="toggle-label">Tampilkan barang yang sudah diambil</span>
                </div>
            </div>

<<<<<<< HEAD
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
                }
            });
            <!-- TABLE CONTAINER -->
            <div class="table-container">
                <?php
                if ($result_customers->num_rows === 0) {
                    echo '<div class="empty-state">
                    <div class="empty-state-icon">🔭</div>
                    <div class="empty-state-text">Belum ada pesanan</div>
                </div>';
                } else {
                    while ($customer = $result_customers->fetch_assoc()) {
                        include('../partials/drop/customer_group.php');
                    }
                }
                ?>
            </div>

            <!-- MODALS -->
            <?php
            include('../modal/drop/modal_add.php');
            include('../modal/drop/modal_edit_item.php');
            include('../modal/drop/modal_confirm_delete.php');
            include('../modal/drop/modal_succes.php');
            ?>
        </div>
    </main>

    <?php include_once "../partials/footer.php"; ?>

    <!-- SCRIPTS -->
    <script src="../js/drop/drop_helpers.js"></script>
    <script src="../js/drop/drop_modal.js"></script>
    <script src="../js/drop/drop_modal_edit.js"></script>
    <script src="../js/drop/drop_main.js"></script>
    <script src="../js/drop/drop_delete.js"></script>
    <script src="../js/drop/drop_toggle.js"></script>
    <script src="../js/drop/drop_status_change.js"></script>
    <script src="../js/drop/print_handler.js"></script>

</body>

</html>