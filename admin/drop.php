<?php
include('../partials/headerAdmin.php');
include('../db.php');

// ===== HANDLE EDIT ITEM (AJAX) =====
if (isset($_POST['action']) && $_POST['action'] === 'edit_item') {
  header('Content-Type: application/json');
  error_reporting(E_ALL);
  ini_set('display_errors', 0);
  
  $item_id = intval($_POST['item_id'] ?? 0);
  $drop_id = intval($_POST['drop_id'] ?? 0);
  $brand = trim($_POST['brand'] ?? '');
  $service_id = intval($_POST['service_id'] ?? 0);
  $price = floatval($_POST['price'] ?? 0);
  $notes = trim($_POST['notes'] ?? '');

  if ($item_id <= 0 || $drop_id <= 0 || empty($brand) || $service_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
  }

  try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("
      UPDATE drop_items 
      SET brand = ?, service_id = ?, price = ?, notes = ?
      WHERE id_item = ? AND drop_id = ?
    ");
    $stmt->bind_param("sidiii", $brand, $service_id, $price, $notes, $item_id, $drop_id);
    
    if (!$stmt->execute()) {
      throw new Exception("Update failed: " . $stmt->error);
    }
    $stmt->close();

    $stmt_total = $conn->prepare("SELECT SUM(price) as total FROM drop_items WHERE drop_id = ?");
    $stmt_total->bind_param("i", $drop_id);
    $stmt_total->execute();
    $result_total = $stmt_total->get_result();
    $row_total = $result_total->fetch_assoc();
    $new_total = floatval($row_total['total'] ?? 0);
    $stmt_total->close();

    $stmt_drop = $conn->prepare("UPDATE drops SET total_amount = ? WHERE id_drop = ?");
    $stmt_drop->bind_param("di", $new_total, $drop_id);
    $stmt_drop->execute();
    $stmt_drop->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => '✅ Item berhasil diupdate']);
    exit;

  } catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
  }
}

// ===== HANDLE DELETE ITEM (AJAX) =====
if (isset($_POST['action']) && $_POST['action'] === 'delete_item') {
  header('Content-Type: application/json');
  error_reporting(E_ALL);
  ini_set('display_errors', 0);
  
  $item_id = intval($_POST['item_id'] ?? 0);
  $drop_id = intval($_POST['drop_id'] ?? 0);

  if ($item_id <= 0 || $drop_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
  }

  try {
    $conn->begin_transaction();

    $stmt_count = $conn->prepare("SELECT COUNT(*) as item_count FROM drop_items WHERE drop_id = ?");
    $stmt_count->bind_param("i", $drop_id);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $row_count = $result_count->fetch_assoc();
    $item_count = intval($row_count['item_count']);
    $stmt_count->close();

    if ($item_count <= 1) {
      throw new Exception("Tidak bisa hapus - ini satu-satunya item. Gunakan tombol Hapus di atas!");
    }

    $stmt = $conn->prepare("DELETE FROM drop_items WHERE id_item = ? AND drop_id = ?");
    $stmt->bind_param("ii", $item_id, $drop_id);
    $stmt->execute();
    $stmt->close();

    $stmt_total = $conn->prepare("SELECT SUM(price) as total FROM drop_items WHERE drop_id = ?");
    $stmt_total->bind_param("i", $drop_id);
    $stmt_total->execute();
    $result_total = $stmt_total->get_result();
    $row_total = $result_total->fetch_assoc();
    $new_total = floatval($row_total['total'] ?? 0);
    $stmt_total->close();

    $stmt_drop = $conn->prepare("UPDATE drops SET total_amount = ? WHERE id_drop = ?");
    $stmt_drop->bind_param("di", $new_total, $drop_id);
    $stmt_drop->execute();
    $stmt_drop->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => '✅ Item berhasil dihapus']);
    exit;

  } catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
  }
}

// ===== HANDLE GET ITEM DETAIL (AJAX) =====
if (isset($_POST['action']) && $_POST['action'] === 'get_item') {
  header('Content-Type: application/json');
  
  $item_id = intval($_POST['item_id'] ?? 0);
  $drop_id = intval($_POST['drop_id'] ?? 0);

  if ($item_id <= 0 || $drop_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
  }

  $stmt = $conn->prepare("
    SELECT di.id_item, di.drop_id, di.brand, di.service_id, di.price, di.notes
    FROM drop_items di
    WHERE di.id_item = ? AND di.drop_id = ?
    LIMIT 1
  ");
  $stmt->bind_param("ii", $item_id, $drop_id);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan']);
    exit;
  }
  
  $item = $result->fetch_assoc();
  $stmt->close();

  echo json_encode([
    'success' => true,
    'id_item' => $item['id_item'],
    'drop_id' => $item['drop_id'],
    'brand' => $item['brand'],
    'service_id' => $item['service_id'],
    'price' => floatval($item['price']),
    'notes' => $item['notes'] ?? ''
  ]);
  exit;
}
?>
<link rel="stylesheet" href="../css/drop.css">

<style>
/* ===== TAMBAHAN CSS UNTUK NOTE COLUMN ===== */
.order-item-grid {
  display: grid;
  grid-template-columns: 35px 85px 90px 100px 80px 85px 85px 120px 80px 80px 150px;
  gap: 6px;
  align-items: center;
  padding: 12px 10px;
  font-size: 11px;
  min-width: 1150px;
}

.orders-grid-header {
  display: grid;
  grid-template-columns: 35px 85px 90px 100px 80px 85px 85px 120px 80px 80px 150px;
  gap: 6px;
  background: #f8fafc;
  padding: 10px;
  font-weight: 700;
  font-size: 11px;
  color: #475569;
  border-bottom: 2px solid #e2e8f0;
  position: sticky;
  top: 0;
  z-index: 10;
  min-width: 1150px;
}

.note-cell {
  max-width: 150px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 10px;
  color: #64748b;
  cursor: pointer;
  padding: 4px 8px;
  background: #f8fafc;
  border-radius: 4px;
  border: 1px solid #e2e8f0;
}

.note-cell:hover {
  background: #e0f2fe;
  color: #0369a1;
  border-color: #bae6fd;
}

.note-cell.empty {
  color: #cbd5e1;
  font-style: italic;
}

/* ITEM EDIT MODAL */
.item-edit-modal {
  display: none;
  position: fixed;
  z-index: 5000;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  overflow-y: auto;
}

.item-edit-content {
  background: white;
  border-radius: 12px;
  padding: 25px;
  max-width: 550px;
  margin: 40px auto;
  box-shadow: 0 8px 24px rgba(0,0,0,0.2);
}

.item-edit-header {
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.item-edit-form {
  display: grid;
  gap: 12px;
}

.item-edit-form input, .item-edit-form select, .item-edit-form textarea {
  width: 100%;
  padding: 10px;
  border: 1.5px solid #d6dee9;
  border-radius: 6px;
  font-size: 13px;
  box-sizing: border-box;
  font-family: "Inter", sans-serif;
}

.item-edit-form label {
  display: block;
  font-weight: 600;
  margin-bottom: 4px;
  color: #1e293b;
  font-size: 12px;
}

.item-edit-form textarea {
  resize: vertical;
  min-height: 70px;
}

.item-edit-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 15px;
}

.btn-save-edit, .btn-cancel-edit {
  padding: 9px 18px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  font-size: 12px;
  transition: all 0.2s ease;
}

.btn-save-edit {
  background: #004d9d;
  color: white;
}

.btn-save-edit:hover {
  background: #0056b3;
}

.btn-cancel-edit {
  background: #e0e6ef;
  color: #333;
}

.btn-cancel-edit:hover {
  background: #cfd8e3;
}

.close-edit {
  cursor: pointer;
  font-size: 24px;
  color: #dc2626;
}

.close-edit:hover {
  color: #b91c1c;
}
</style>
<main class="drop-page">
    <div class="drop-container">
        <h1 class="title">📦 Manajemen Drop</h1>

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
                    <select id="sort" name="sort" class="filter-select"
                        onchange="document.getElementById('filterForm').submit()">
                        <option value="" disabled selected hidden>🔍 Filter Data</option>
                        <option value="nama_asc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'nama_asc') ? 'selected' : '' ?>>Nama (A-Z)</option>
                        <option value="nama_desc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'nama_desc') ? 'selected' : '' ?>>Nama (Z-A)</option>
                        <option value="tanggal_desc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'tanggal_desc') ? 'selected' : '' ?>>Tanggal Terlama</option>
                        <option value="tanggal_asc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'tanggal_asc') ? 'selected' : '' ?>>Tanggal Terbaru</option>
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

        <div class="table-container">
            <?php
            $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
            $sort = isset($_GET['sort']) ? $_GET['sort'] : '';
            
            $sql_customers = "
                SELECT DISTINCT c.id_customer, c.name, c.phone
                FROM customers c
                INNER JOIN drops d ON c.id_customer = d.customer_id
                WHERE d.status_id != 6
            ";
            
            if (!empty($search)) {
                $sql_customers .= " AND (c.name LIKE '%$search%' OR c.phone LIKE '%$search%')";
            }
            
            switch ($sort) {
                case 'nama_asc': $sql_customers .= " ORDER BY c.name ASC"; break;
                case 'nama_desc': $sql_customers .= " ORDER BY c.name DESC"; break;
                default: $sql_customers .= " ORDER BY c.name ASC";
            }
            
            $result_customers = $conn->query($sql_customers);
            
            if ($result_customers->num_rows === 0) {
                echo '<div class="empty-state">
                    <div class="empty-state-icon">🔭</div>
                    <div class="empty-state-text">Belum ada pesanan aktif</div>
                </div>';
            } else {
                while ($customer = $result_customers->fetch_assoc()) {
                    $customer_id = $customer['id_customer'];
                    $customer_name = htmlspecialchars($customer['name']);
                    $customer_phone = htmlspecialchars($customer['phone']);
                    
                    $sql_orders = "
                        SELECT 
                            di.id_item, di.drop_id, d.order_code, di.brand, di.price,
                            di.notes as item_note, di.item_order,
                            s.service_name, s.category,
                            d.trans_date, d.est_finish_date, d.status_id,
                            st.status_name,
                            p.status as payment_status,
                            e.name as employee_name
                        FROM drop_items di
                        INNER JOIN drops d ON di.drop_id = d.id_drop
                        INNER JOIN services s ON di.service_id = s.id_service
                        INNER JOIN statuses st ON d.status_id = st.id_status
                        LEFT JOIN payments p ON d.id_drop = p.drop_id
                        LEFT JOIN employees e ON d.employee_id = e.id_employee
                        WHERE d.customer_id = ? AND d.status_id != 6
                    ";
                    
                    switch ($sort) {
                        case 'tanggal_asc': $sql_orders .= " ORDER BY d.trans_date ASC, di.item_order ASC"; break;
                        case 'tanggal_desc': $sql_orders .= " ORDER BY d.trans_date DESC, di.item_order ASC"; break;
                        default: $sql_orders .= " ORDER BY d.trans_date DESC, di.item_order ASC";
                    }
                    
                    $stmt_orders = $conn->prepare($sql_orders);
                    $stmt_orders->bind_param("i", $customer_id);
                    $stmt_orders->execute();
                    $result_orders = $stmt_orders->get_result();
                    
                    if ($result_orders->num_rows === 0) {
                        continue;
                    }
                    
                    $total_orders = $result_orders->num_rows;
                    $total_amount = 0;
                    $orders_data = [];
                    
                    while ($order = $result_orders->fetch_assoc()) {
                        $total_amount += floatval($order['price']);
                        $orders_data[] = $order;
                    }
                    
                    echo "<div class='customer-group-header'>
                        <div class='customer-info-text'>
                            <input type='checkbox' class='customer-checkbox' data-customer-id='{$customer_id}'>
                            <span class='customer-name'>{$customer_name}</span>
                            <span class='customer-phone'>📱 {$customer_phone}</span>
                        </div>
                        <div style='display: flex; gap: 12px; align-items: center;'>
                            <span class='order-count-badge'>{$total_orders} Pesanan</span>
                            <div class='customer-actions'>
                                <button class='customer-action-btn print-all-btn' data-customer-id='{$customer_id}'>
                                    🖨️ Cetak Semua
                                </button>
                            </div>
                        </div>
                    </div>";
                    
                    echo "<div class='customer-orders-container'>";
                    
                    // HEADER WITH NOTE COLUMN
                    echo "<div class='orders-grid-header'>
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
                    </div>";
                    
                    foreach ($orders_data as $index => $order) {
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
                        
                        $paymentColor = '';
                        switch($payment_status) {
                            case 'Lunas': $paymentColor = 'color: #059669; font-weight: 600;'; break;
                            case 'Pending': $paymentColor = 'color: #f59e0b; font-weight: 600;'; break;
                            default: $paymentColor = 'color: #dc2626; font-weight: 600;';
                        }
                        
                        echo "<div class='order-item-row' 
                            data-drop-id='{$drop_id}' 
                            data-item-id='{$id_item}'
                            data-customer-id='{$customer_id}'>
                            <div class='order-item-grid'>
                                <div class='order-item-cell center'>
                                    <input type='checkbox' class='item-checkbox' 
                                        data-drop-id='{$drop_id}' 
                                        data-customer-id='{$customer_id}'>
                                </div>
                                <div class='order-item-cell'>
                                    <span class='item-number-badge'>#{$item_number}</span><br>
                                    <small style='color: #6366f1; font-weight: 600;'>{$order_code}</small>
                                </div>
                                <div class='order-item-cell'>{$brand}</div>
                                <div class='order-item-cell'>
                                    <span style='font-size: 10px; color: #64748b;'>" . ucfirst($order['category']) . "</span><br>
                                    <span style='font-weight: 600;'>{$order['service_name']}</span>
                                </div>
                                <div class='order-item-cell center' style='font-weight: 700; color: #059669;'>
                                    Rp{$price}
                                </div>
                                <div class='order-item-cell center' style='font-size: 11px;'>{$trans_date}</div>
                                <div class='order-item-cell center' style='font-size: 11px;'>{$est_date}</div>
                                <div class='order-item-cell center'>
                                    <select class='status-dropdown' data-drop-id='{$drop_id}'>
                                        <option value='' disabled>Pilih Status</option>";
                        
                        $st2 = $conn->query("SELECT * FROM statuses ORDER BY id_status ASC");
                        while ($s2 = $st2->fetch_assoc()) {
                            $selected = ($s2['id_status'] == $order['status_id']) ? 'selected' : '';
                            echo "<option value='{$s2['id_status']}' {$selected}>{$s2['status_name']}</option>";
                        }
                        
                        echo "</select>
                                </div>
                                <div class='order-item-cell center' style='{$paymentColor} font-size: 11px;'>
                                    {$payment_status}
                                </div>
                                <div class='order-item-cell center' style='font-size: 10px;'>
                                    {$employee_name}
                                </div>
                                <div class='order-item-cell'>
                                    <div class='{$note_class}' 
                                        title='{$item_note}' 
                                        data-note='{$item_note}'
                                        data-item-id='{$id_item}'
                                        data-drop-id='{$drop_id}'>
                                        {$note_display}
                                    </div>
                                </div>
                            </div>
                        </div>";
                    }
                    
                    echo "<div class='customer-total-row'>
                        <span class='total-label'>Total Harga:</span>
                        <span class='total-amount'>Rp" . number_format($total_amount, 0, ',', '.') . "</span>
                    </div>";
                    
                    echo "</div>";
                    $stmt_orders->close();
                }
            }
            ?>
        </div>

        <!-- ITEM EDIT MODAL -->
        <div id="itemEditModal" class="item-edit-modal">
            <div class="item-edit-content">
                <div class="item-edit-header">
                    <span>✏️ Edit Item</span>
                    <span class="close-edit" onclick="closeItemEditModal()">&times;</span>
                </div>
                <form id="itemEditForm" class="item-edit-form">
                    <input type="hidden" id="editItemId" name="item_id">
                    <input type="hidden" id="editDropId" name="drop_id">
                    <input type="hidden" name="action" value="edit_item">
                    
                    <div>
                        <label>🏷️ Brand / Merk</label>
                        <input type="text" id="editBrand" name="brand" required>
                    </div>
                    
                    <div>
                        <label>🛠️ Layanan</label>
                        <select id="editService" name="service_id" required>
                            <option value="">-- Pilih Layanan --</option>
                            <?php
                            $order = "FIELD(category, 'cleaning', 'reglue', 'repaint', 'bag', 'cap'), service_name";
                            $query = "SELECT id_service, category, service_name FROM services ORDER BY $order";
                            $result = mysqli_query($conn, $query);
                            while ($r = mysqli_fetch_assoc($result)) {
                                $displayName = ucfirst($r['category']) . " - " . ucfirst($r['service_name']);
                                echo "<option value='{$r['id_service']}'>{$displayName}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div>
                        <label>💰 Harga (Rp)</label>
                        <input type="number" id="editPrice" name="price" required min="0" step="1000">
                    </div>
                    
                    <div style="grid-column: 1 / -1;">
                        <label>📝 Catatan Item</label>
                        <textarea id="editNotes" name="notes" rows="3" placeholder="Catatan untuk item ini..."></textarea>
                    </div>
                    
                    <div class="item-edit-actions" style="grid-column: 1 / -1;">
                        <button type="submit" class="btn-save-edit">💾 Simpan</button>
                        <button type="button" class="btn-cancel-edit" onclick="closeItemEditModal()">✕ Batal</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- MODAL ADD (DARI FILE ASLI ANDA - TIDAK DIUBAH) -->
        <div class="modal" id="addModal" style="display:none;">
            <div class="modal-content large">
                <span class="close" data-target="addModal">&times;</span>
                <h2>➕ Tambah Pesanan Baru</h2>

                <form method="POST" action="drop_add.php" class="grid-form" id="addForm">
                    <!-- Customer Info Section -->
                    <div class="customer-info-section" style="grid-column: 1 / -1;">
                        <h3 style="margin: 0 0 16px 0; color: #b45309; font-size: 16px; font-weight: 700;">
                            👤 Informasi Pelanggan
                        </h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label>Nama Pelanggan</label>
                                <input type="text" id="customer_name" name="customer_name" required placeholder="Masukkan nama pelanggan">
                            </div>
                            <div>
                                <label>No. Handphone</label>
                                <input type="text" id="customer_phone" name="phone_number" required placeholder="08xxxxxxxxxx">
                            </div>
                        </div>
                    </div>

                    <!-- Items Container -->
                    <div id="itemsContainer" style="grid-column: 1 / -1;">
                        <!-- Item 1 (Default) -->
                        <div class="item-group" data-item-index="1">
                            <div style="position: absolute; top: 10px; right: 10px; display: flex; gap: 10px;">
                                <span class="item-badge">Item #1</span>
                                <button type="button" class="remove-item-btn" data-item="1" style="display: none;">✕ Hapus</button>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 40px;">
                                <div>
                                    <label>🏷️ Brand / Merk</label>
                                    <input type="text" name="items[1][brand]" class="item-brand" required placeholder="Contoh: Nike, Adidas">
                                </div>

                                <div>
                                    <label>🛠️ Layanan</label>
                                    <select name="items[1][service_id]" class="item-service" required>
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
                                    <label>💰 Harga</label>
                                    <input type="text" class="item-price-display" readonly value="Rp 0" style="background: #f1f5f9; font-weight: 600; color: #0369a1;">
                                    <input type="hidden" name="items[1][price]" class="item-price" value="0">
                                </div>

                                <div>
                                    <label>⏱️ Estimasi Selesai</label>
                                    <input type="text" class="item-estimate" readonly value="-" style="background: #f1f5f9; font-weight: 600;">
                                    <input type="hidden" name="items[1][duration]" class="item-duration" value="0">
                                </div>

                                <div>
                                    <label>📅 Tgl. Transaksi</label>
                                    <input type="date" name="items[1][trans_date]" class="item-trans-date" value="<?= date('Y-m-d') ?>">
                                </div>

                                <div>
                                    <label>📆 Tanggal Estimasi Selesai</label>
                                    <input type="date" name="items[1][est_finish_date]" class="item-est-date" readonly style="background: #f1f5f9;">
                                </div>

                                <div style="grid-column: 1 / -1;">
                                    <label>📊 Status</label>
                                    <select name="items[1][status_id]" class="item-status" required>
                                        <option value="">Pilih Status</option>
                                        <?php
                                        $st = $conn->query("SELECT * FROM statuses ORDER BY id_status ASC");
                                        while ($s = $st->fetch_assoc()) {
                                            echo "<option value='{$s['id_status']}'>{$s['status_name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div style="grid-column: 1 / -1;">
                                    <label>📝 Catatan Item</label>
                                    <textarea name="items[1][notes]" class="item-notes" rows="2" placeholder="Catatan khusus untuk item ini..." style="width:100%; padding:10px; border-radius:6px; border:1px solid #d6dee9; resize: vertical;"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Item Button -->
                    <div style="grid-column: 1 / -1; text-align: center; margin: 20px 0;">
                        <button type="button" id="addItemBtn" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                            ➕ Tambah Barang
                        </button>
                    </div>

                    <!-- Order Summary Section -->
                    <div class="order-summary" style="grid-column: 1 / -1;">
                        <h3 style="margin: 0 0 16px 0; color: #0369a1; font-size: 16px; font-weight: 700;">📋 Ringkasan Pesanan</h3>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                            <div>
                                <label>Total Item</label>
                                <input type="text" id="totalItems" readonly value="1" style="background:#fff; font-weight: 600; color: #0369a1;">
                            </div>
                            <div>
                                <label>Total Harga</label>
                                <input type="text" id="totalPriceDisplay" readonly value="Rp 0" style="background:#fff; font-weight: 600; color: #0369a1; font-size: 16px;">
                                <input type="hidden" name="total_amount" id="totalPrice" value="0">
                            </div>
                            <div>
                                <label>Estimasi Terlama</label>
                                <input type="text" id="maxEstimate" readonly value="-" style="background:#fff; font-weight: 600;">
                                <input type="hidden" name="max_duration" id="maxDuration" value="0">
                            </div>
                            <div>
                                <label>Tanggal Estimasi Selesai</label>
                                <input type="date" id="finalEstDate" name="est_finish_date" readonly style="background:#fff; font-weight: 600;">
                            </div>
                        </div>
                    </div>

                    <!-- Payment & Employee Section -->
                    <div class="payment-section" style="grid-column: 1 / -1;">
                        <h3 style="margin: 0 0 16px 0; color: #7c3aed; font-size: 16px; font-weight: 700;">💳 Informasi Pembayaran</h3>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
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
                                <input type="date" id="payment_date_display" readonly disabled style="background:#f1f5f9; cursor: not-allowed;">
                                <input type="hidden" id="payment_date_hidden" name="payment_date">
                            </div>

                            <div>
                                <label>Metode Pembayaran</label>
                                <select name="payment_method" required>
                                    <option value="Tunai">💵 Tunai</option>
                                    <option value="Transfer">🏦 Transfer</option>
                                    <option value="QRIS">📱 QRIS</option>
                                    <option value="Debit">💳 Debit</option>
                                    <option value="Credit">💳 Credit</option>
                                </select>
                            </div>

                            <div>
                                <label>Nominal Pembayaran</label>
                                <input type="text" id="amount_paid_display" placeholder="Rp 0">
                                <input type="hidden" name="amount_paid" id="amount_paid">
                            </div>

                            <div style="grid-column: 1 / -1;">
                                <label>👷 Karyawan</label>
                                <?php
                                $activeEmployees = $conn->query("SELECT id_employee, name FROM employees WHERE status = 'Aktif'");
                                $employeeCount = $activeEmployees->num_rows;

                                if ($employeeCount === 0) {
                                    echo "<input type='text' value='Tidak ada karyawan aktif' readonly style='background:#f9f9f9; color:#888;'>";
                                } elseif ($employeeCount === 1) {
                                    $emp = $activeEmployees->fetch_assoc();
                                    echo "<input type='hidden' name='employee_id' value='{$emp['id_employee']}'>
                                          <input type='text' value='{$emp['name']}' readonly style='background:#f9f9f9;'>";
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
                        </div>
                    </div>

                    <div class="full-width">
                        <label>📝 Catatan Umum Pesanan</label>
                        <textarea name="note" rows="2" placeholder="Catatan umum untuk seluruh pesanan..." style="width:100%; padding:12px; border-radius:8px; border:2px solid #e2e8f0; resize: vertical;"></textarea>
                    </div>

                    <div class="full-width">
                        <div class="button-container">
                            <button type="submit" class="save-btn" id="saveOnlyBtn">💾 Simpan</button>
                            <button type="button" class="print-btn save-and-print" id="saveAndPrintBtn" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">🖨️ Simpan & Cetak</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- SUCCESS MODAL -->
        <div id="successModal" class="modal">
            <div class="modal-content" style="max-width: 400px; text-align: center; padding: 40px;">
                <div style="font-size: 48px; margin-bottom: 16px;">✅</div>
                <p id="successMessage" style="font-size: 18px; font-weight: 600; color: #059669; margin-bottom: 24px;">Data berhasil disimpan</p>
                <button id="closeSuccess" class="save-btn" style="width:auto; padding: 12px 40px;">OK</button>
            </div>
        </div>

        <!-- CONFIRM DELETE MODAL -->
        <div id="confirmDeleteModal" class="confirm-modal">
            <div class="confirm-content">
                <div style="font-size: 48px; margin-bottom: 16px;">⚠️</div>
                <h3>Yakin ingin menghapus pesanan ini?</h3>
                <p class="warning-text">⚠️ Data yang dihapus tidak dapat dikembalikan!</p>
                <div class="confirm-actions">
                    <button id="confirmOk" class="btn-ok">✓ Hapus</button>
                    <button id="confirmCancel" class="btn-cancel">✕ Batal</button>
                </div>
            </div>
        </div>

    </div>
</main>

<?php include_once "../partials/footer.php"; ?>

<script>
// ===== FUNGSI CLOSE MODAL EDIT =====
function closeItemEditModal() {
    document.getElementById('itemEditModal').style.display = 'none';
    document.body.style.overflow = '';
}

document.addEventListener('DOMContentLoaded', function() {
    console.log("✅ Multi-Item Drop System Initialized");
    
    let itemCounter = 1;
    // ===== HELPER FUNCTIONS (DARI FILE ASLI - TIDAK DIUBAH) =====
    const formatRupiah = (angka) => {
        if (typeof angka !== 'number') return 'Rp 0';
        return 'Rp ' + angka.toLocaleString('id-ID');
    };

    const parseRupiah = (rupiah) => {
        return parseInt(rupiah.replace(/[^0-9]/g, '') || 0);
    };

    function showModal(id) {
        const m = document.getElementById(id);
        if (m) {
            m.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }
    
    function hideModal(id) {
        const m = document.getElementById(id);
        if (m) {
            m.style.display = 'none';
            document.body.style.overflow = '';
        }
    }
    
    // ===== CALCULATE ORDER SUMMARY (DARI FILE ASLI - TIDAK DIUBAH) =====
    function calculateOrderSummary() {
        const items = document.querySelectorAll('.item-group');
        let totalPrice = 0;
        let maxDuration = 0;
        let maxEstDate = '';

        items.forEach(item => {
            const price = parseFloat(item.querySelector('.item-price').value || 0);
            const duration = parseInt(item.querySelector('.item-duration').value || 0);
            const estDate = item.querySelector('.item-est-date').value;

            totalPrice += price;
            
            if (duration > maxDuration) {
                maxDuration = duration;
            }

            if (estDate && (!maxEstDate || estDate > maxEstDate)) {
                maxEstDate = estDate;
            }
        });

        document.getElementById('totalItems').value = items.length + ' Item';
        document.getElementById('totalPriceDisplay').value = formatRupiah(totalPrice);
        document.getElementById('totalPrice').value = totalPrice;
        document.getElementById('maxEstimate').value = maxDuration > 0 ? `${maxDuration} Hari` : '-';
        document.getElementById('maxDuration').value = maxDuration;
        document.getElementById('finalEstDate').value = maxEstDate;
    }

    // ===== PAYMENT DATE FUNCTIONS (DARI FILE ASLI - TIDAK DIUBAH) =====
    function setPaymentDate(displayId, hiddenId, date) {
        const display = document.getElementById(displayId);
        const hidden = document.getElementById(hiddenId);
        
        if (display && hidden) {
            display.value = date || '';
            hidden.value = date || '';
            hidden.dataset.originalDate = date || '';
        }
    }

    function handlePaymentStatusChange(statusValue, displayId, hiddenId, existingDate = null) {
        const hiddenField = document.getElementById(hiddenId);
        
        if (statusValue === 'Lunas') {
            if (existingDate) {
                setPaymentDate(displayId, hiddenId, existingDate);
            } else {
                const today = new Date().toISOString().split('T')[0];
                setPaymentDate(displayId, hiddenId, today);
            }
        } else {
            setPaymentDate(displayId, hiddenId, '');
            if (hiddenField) {
                hiddenField.dataset.originalDate = '';
            }
        }
    }

    // ===== DYNAMIC ESTIMATE FETCH (DARI FILE ASLI - TIDAK DIUBAH) =====
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

    // ===== UPDATE ITEM SERVICE DATA (DARI FILE ASLI - TIDAK DIUBAH) =====
    const updateItemServiceData = async (itemGroup) => {
        const serviceSelect = itemGroup.querySelector('.item-service');
        const priceDisplay = itemGroup.querySelector('.item-price-display');
        const priceInput = itemGroup.querySelector('.item-price');
        const estimateDisplay = itemGroup.querySelector('.item-estimate');
        const durationInput = itemGroup.querySelector('.item-duration');
        const transDateInput = itemGroup.querySelector('.item-trans-date');
        const estDateInput = itemGroup.querySelector('.item-est-date');

        const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
        
        if (selectedOption && selectedOption.value) {
            const serviceId = selectedOption.value;
            const transDate = transDateInput.value;
            
            estimateDisplay.value = '⏳ Menghitung...';
            durationInput.value = '0';
            estDateInput.value = '';
            
            const result = await fetchDynamicEstimate(serviceId, transDate, 0);
            
            if (result && result.success) {
                priceDisplay.value = formatRupiah(result.price_min);
                priceInput.value = result.price_min;
                durationInput.value = result.duration;
                estimateDisplay.value = result.estimate_desc;
                estDateInput.value = result.estimate_date;
                
                if (result.queue_info) {
                    console.log(`📋 ${result.queue_info}`);
                }
                if (result.has_overdue) {
                    console.warn(`⚠️ Ada ${result.overdue_count} pesanan overdue - queue direset`);
                }
            } else {
                const priceMin = parseFloat(selectedOption.getAttribute('data-price') || 0);
                const duration = parseInt(selectedOption.getAttribute('data-duration') || 0);
                priceDisplay.value = formatRupiah(priceMin);
                priceInput.value = priceMin;
                durationInput.value = duration;
                estimateDisplay.value = (duration > 0) ? `${duration} Hari` : '-';
                const date = new Date(transDate);
                date.setDate(date.getDate() + duration);
                estDateInput.value = date.toISOString().split('T')[0];
            }
        } else {
            priceDisplay.value = 'Rp 0';
            priceInput.value = '0';
            durationInput.value = '0';
            estimateDisplay.value = '-';
            estDateInput.value = '';
        }

        calculateOrderSummary();
    };

    // ===== UPDATE REMOVE BUTTONS VISIBILITY (DARI FILE ASLI - TIDAK DIUBAH) =====
    function updateRemoveButtons() {
        const items = document.querySelectorAll('.item-group');
        items.forEach((item, index) => {
            const removeBtn = item.querySelector('.remove-item-btn');
            if (removeBtn) {
                removeBtn.style.display = items.length > 1 ? 'inline-block' : 'none';
            }
        });
    }

    // ===== CREATE NEW ITEM HTML (DARI FILE ASLI - TIDAK DIUBAH) =====
    function createNewItemHTML(itemIndex) {
        const servicesOptions = document.querySelector('.item-service').innerHTML;
        const statusOptions = document.querySelector('.item-status').innerHTML;
        
        return `
            <div style="position: absolute; top: 10px; right: 10px; display: flex; gap: 10px;">
                <span class="item-badge">Item #${itemIndex}</span>
                <button type="button" class="remove-item-btn" data-item="${itemIndex}">✕ Hapus</button>
            </div>

            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 40px;">
                <div>
                    <label>🏷️ Brand / Merk</label>
                    <input type="text" name="items[${itemIndex}][brand]" class="item-brand" required placeholder="Contoh: Nike, Adidas">
                </div>

                <div>
                    <label>🛠️ Layanan</label>
                    <select name="items[${itemIndex}][service_id]" class="item-service" required>
                        ${servicesOptions}
                    </select>
                </div>

                <div>
                    <label>💰 Harga</label>
                    <input type="text" class="item-price-display" readonly value="Rp 0" style="background: #f1f5f9; font-weight: 600; color: #0369a1;">
                    <input type="hidden" name="items[${itemIndex}][price]" class="item-price" value="0">
                </div>

                <div>
                    <label>⏱️ Estimasi Selesai</label>
                    <input type="text" class="item-estimate" readonly value="-" style="background: #f1f5f9; font-weight: 600;">
                    <input type="hidden" name="items[${itemIndex}][duration]" class="item-duration" value="0">
                </div>

                <div>
                    <label>📅 Tgl. Transaksi</label>
                    <input type="date" name="items[${itemIndex}][trans_date]" class="item-trans-date" value="${new Date().toISOString().split('T')[0]}">
                </div>

                <div>
                    <label>📆 Tanggal Estimasi Selesai</label>
                    <input type="date" name="items[${itemIndex}][est_finish_date]" class="item-est-date" readonly style="background: #f1f5f9;">
                </div>

                <div style="grid-column: 1 / -1;">
                    <label>📊 Status</label>
                    <select name="items[${itemIndex}][status_id]" class="item-status" required>
                        ${statusOptions}
                    </select>
                </div>

                <div style="grid-column: 1 / -1;">
                    <label>📝 Catatan Item</label>
                    <textarea name="items[${itemIndex}][notes]" class="item-notes" rows="2" placeholder="Catatan khusus untuk item ini..." style="width:100%; padding:10px; border-radius:6px; border:1px solid #d6dee9; resize: vertical;"></textarea>
                </div>
            </div>
        `;
    }// ===== ADD ITEM BUTTON (DARI FILE ASLI - TIDAK DIUBAH) =====
    document.getElementById('addItemBtn').addEventListener('click', function() {
        itemCounter++;
        console.log(`➕ Adding item #${itemCounter}`);

        const itemsContainer = document.getElementById('itemsContainer');
        const newItem = document.createElement('div');
        newItem.className = 'item-group';
        newItem.setAttribute('data-item-index', itemCounter);
        newItem.innerHTML = createNewItemHTML(itemCounter);

        itemsContainer.appendChild(newItem);

        const serviceSelect = newItem.querySelector('.item-service');
        const transDateInput = newItem.querySelector('.item-trans-date');

        serviceSelect.addEventListener('change', () => updateItemServiceData(newItem));
        transDateInput.addEventListener('change', () => {
            if (serviceSelect.value) updateItemServiceData(newItem);
        });

        newItem.querySelector('.remove-item-btn').addEventListener('click', function() {
            if (document.querySelectorAll('.item-group').length > 1) {
                newItem.remove();
                calculateOrderSummary();
                updateRemoveButtons();
                console.log(`🗑️ Item #${itemCounter} removed`);
            } else {
                alert('⚠️ Minimal harus ada 1 item dalam pesanan!');
            }
        });

        updateRemoveButtons();
        calculateOrderSummary();
    });

    // ===== ATTACH EVENT LISTENERS TO FIRST ITEM (DARI FILE ASLI - TIDAK DIUBAH) =====
    const firstItem = document.querySelector('.item-group');
    if (firstItem) {
        const serviceSelect = firstItem.querySelector('.item-service');
        const transDateInput = firstItem.querySelector('.item-trans-date');

        serviceSelect.addEventListener('change', () => updateItemServiceData(firstItem));
        transDateInput.addEventListener('change', () => {
            if (serviceSelect.value) updateItemServiceData(firstItem);
        });
        
        const removeBtn = firstItem.querySelector('.remove-item-btn');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                if (document.querySelectorAll('.item-group').length > 1) {
                    firstItem.remove();
                    calculateOrderSummary();
                    updateRemoveButtons();
                } else {
                    alert('⚠️ Minimal harus ada 1 item dalam pesanan!');
                }
            });
        }
    }

    // ===== OPEN ADD MODAL (DARI FILE ASLI - TIDAK DIUBAH) =====
    document.getElementById('openAddModal').addEventListener('click', function() {
        console.log("Opening ADD modal...");
        
        document.getElementById('addForm').reset();
        
        const itemsContainer = document.getElementById('itemsContainer');
        const allItems = itemsContainer.querySelectorAll('.item-group');
        allItems.forEach((item, index) => {
            if (index > 0) item.remove();
        });
        itemCounter = 1;

        const firstItemReset = itemsContainer.querySelector('.item-group');
        if (firstItemReset) {
            firstItemReset.querySelector('.item-trans-date').value = new Date().toISOString().split('T')[0];
            firstItemReset.querySelector('.item-price-display').value = 'Rp 0';
            firstItemReset.querySelector('.item-price').value = '0';
            firstItemReset.querySelector('.item-estimate').value = '-';
            firstItemReset.querySelector('.item-duration').value = '0';
            firstItemReset.querySelector('.item-est-date').value = '';
        }

        updateRemoveButtons();
        calculateOrderSummary();
        setPaymentDate('payment_date_display', 'payment_date_hidden', '');
        
        showModal('addModal');
        
        const payStatus = document.getElementById('payment_status');
        if (payStatus) {
            handlePaymentStatusChange(payStatus.value, 'payment_date_display', 'payment_date_hidden');
        }
    });

    // ===== CLOSE BUTTONS (DARI FILE ASLI - TIDAK DIUBAH) =====
    document.querySelectorAll('.close').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const target = btn.getAttribute('data-target');
            if (target) hideModal(target);
        });
    });

    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
            document.body.style.overflow = '';
        }
    });

    // ===== RUPIAH FORMATTING (DARI FILE ASLI - TIDAK DIUBAH) =====
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

    // ===== PAYMENT STATUS CHANGE (DARI FILE ASLI - TIDAK DIUBAH) =====
    const payStatusAdd = document.getElementById('payment_status');
    if (payStatusAdd) {
        payStatusAdd.addEventListener('change', function() {
            handlePaymentStatusChange(this.value, 'payment_date_display', 'payment_date_hidden');
        });
    }

    // ===== FORM SUBMIT VALIDATION (DARI FILE ASLI - TIDAK DIUBAH) =====
    document.getElementById('addForm').addEventListener('submit', function(e) {
        const paymentStatus = document.getElementById('payment_status').value;
        const paymentDateHidden = document.getElementById('payment_date_hidden');
        
        if (paymentStatus === 'Lunas' && !paymentDateHidden.value) {
            const today = new Date().toISOString().split('T')[0];
            setPaymentDate('payment_date_display', 'payment_date_hidden', today);
        }
    });

    // ===== SAVE & PRINT (DARI FILE ASLI - TIDAK DIUBAH) =====
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
        
        this.disabled = true;
        this.innerHTML = '⏳ Menyimpan...';
        
        const formData = new FormData(form);
        
        fetch('drop_add.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.drop_id) {
                console.log("✅ Pesanan berhasil disimpan!");
                
                hideModal('addModal');
                
                setTimeout(() => {
                    const cetak_url = `cetak_struk.php?id=${data.drop_id}`;
                    window.open(cetak_url, 'CetakStruk', 'width=600,height=800');
                    
                    setTimeout(() => {
                        sessionStorage.setItem('showSuccess', 'true');
                        sessionStorage.setItem('successMessage', `✅ Pesanan dengan ${data.total_items} item berhasil ditambahkan!`);
                        window.location.reload();
                    }, 1000);
                }, 500);
            } else {
                alert("❌ Gagal menyimpan pesanan: " + (data.message || "Unknown error"));
                this.disabled = false;
                this.innerHTML = '🖨️ Simpan & Cetak';
            }
        })
        .catch(error => {
            console.error("❌ Network error:", error);
            alert("❌ Terjadi kesalahan: " + error.message);
            this.disabled = false;
            this.innerHTML = '🖨️ Simpan & Cetak';
        });
    });

    // ===== SAVE ONLY (DARI FILE ASLI - TIDAK DIUBAH) =====
    document.getElementById('saveOnlyBtn').addEventListener('click', function(e) {
        e.preventDefault();
        
        const form = document.getElementById('addForm');
        
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        this.disabled = true;
        this.innerHTML = '⏳ Menyimpan...';
        
        const formData = new FormData(form);
        
        fetch('drop_add.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                hideModal('addModal');
                sessionStorage.setItem('showSuccess', 'true');
                sessionStorage.setItem('successMessage', `✅ Pesanan dengan ${data.total_items} item berhasil ditambahkan!`);
                setTimeout(() => window.location.reload(), 500);
            } else {
                alert("❌ Gagal menyimpan pesanan: " + (data.message || "Unknown error"));
                this.disabled = false;
                this.innerHTML = '💾 Simpan';
            }
        })
        .catch(error => {
            alert("❌ Terjadi kesalahan: " + error.message);
            this.disabled = false;
            this.innerHTML = '💾 Simpan';
        });
    });

    // ===== SUCCESS MODAL (DARI FILE ASLI - TIDAK DIUBAH) =====
    if (sessionStorage.getItem('showSuccess') === 'true') {
        document.getElementById('successMessage').textContent = sessionStorage.getItem('successMessage');
        showModal('successModal');
        sessionStorage.removeItem('showSuccess');
        sessionStorage.removeItem('successMessage');
    }
    
    document.getElementById('closeSuccess').addEventListener('click', function() {
        hideModal('successModal');
    });

    // ===== DOUBLE-CLICK TO EDIT (TAMBAHAN BARU - TIDAK GANGGU YANG LAIN) =====
    document.querySelectorAll('.order-item-row').forEach(row => {
        row.addEventListener('dblclick', function(e) {
            // Jangan trigger jika klik di checkbox atau select
            if (e.target.type === 'checkbox' || e.target.tagName === 'SELECT') {
                return;
            }
            
            const itemId = this.getAttribute('data-item-id');
            const dropId = this.getAttribute('data-drop-id');
            
            console.log(`✏️ Double-click detected - Item ${itemId}, Drop ${dropId}`);
            
            const formData = new FormData();
            formData.append('action', 'get_item');
            formData.append('item_id', itemId);
            formData.append('drop_id', dropId);
            
            fetch('drop.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editItemId').value = itemId;
                        document.getElementById('editDropId').value = dropId;
                        document.getElementById('editBrand').value = data.brand;
                        document.getElementById('editService').value = data.service_id;
                        document.getElementById('editPrice').value = data.price;
                        document.getElementById('editNotes').value = data.notes || '';
                        document.getElementById('itemEditModal').style.display = 'block';
                        document.body.style.overflow = 'hidden';
                        console.log("✅ Edit modal opened successfully");
                    } else {
                        alert('❌ ' + data.message);
                    }
                })
                .catch(e => alert('❌ ' + e.message));
        });
    });

    // ===== SUBMIT EDIT FORM (TAMBAHAN BARU) =====
    document.getElementById('itemEditForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        console.log("📤 Submitting edit form...");
        const formData = new FormData(this);
        
        fetch('drop.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    console.log("✅ Edit successful");
                    closeItemEditModal();
                    sessionStorage.setItem('showSuccess', 'true');
                    sessionStorage.setItem('successMessage', data.message);
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(e => alert('❌ ' + e.message));
    });

    // ===== CLICK NOTE CELL TO EDIT (TAMBAHAN BARU) =====
    document.querySelectorAll('.note-cell').forEach(noteCell => {
        noteCell.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent row double-click
            
            const itemId = this.getAttribute('data-item-id');
            const dropId = this.getAttribute('data-drop-id');
            
            console.log(`📝 Note cell clicked - Item ${itemId}`);
            
            const formData = new FormData();
            formData.append('action', 'get_item');
            formData.append('item_id', itemId);
            formData.append('drop_id', dropId);
            
            fetch('drop.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editItemId').value = itemId;
                        document.getElementById('editDropId').value = dropId;
                        document.getElementById('editBrand').value = data.brand;
                        document.getElementById('editService').value = data.service_id;
                        document.getElementById('editPrice').value = data.price;
                        document.getElementById('editNotes').value = data.notes || '';
                        document.getElementById('itemEditModal').style.display = 'block';
                        document.body.style.overflow = 'hidden';
                        
                        // Focus on notes field
                        setTimeout(() => {
                            document.getElementById('editNotes').focus();
                        }, 100);
                    } else {
                        alert('❌ ' + data.message);
                    }
                })
                .catch(e => alert('❌ ' + e.message));
        });
    });
    // ===== CUSTOMER CHECKBOX (DARI FILE ASLI - TIDAK DIUBAH) =====
    document.querySelectorAll('.customer-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const customerId = this.getAttribute('data-customer-id');
            const isChecked = this.checked;
            
            document.querySelectorAll(`.item-checkbox[data-customer-id="${customerId}"]`).forEach(itemCb => {
                itemCb.checked = isChecked;
            });
            
            console.log(`${isChecked ? '☑️' : '☐'} Customer ${customerId} - ${isChecked ? 'selected' : 'unselected'}`);
        });
    });

    // ===== ITEM CHECKBOX (DARI FILE ASLI - TIDAK DIUBAH) =====
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const customerId = this.getAttribute('data-customer-id');
            const customerCheckbox = document.querySelector(`.customer-checkbox[data-customer-id="${customerId}"]`);
            
            const allItemsForCustomer = document.querySelectorAll(`.item-checkbox[data-customer-id="${customerId}"]`);
            const checkedItems = Array.from(allItemsForCustomer).filter(cb => cb.checked);
            
            if (customerCheckbox) {
                customerCheckbox.checked = checkedItems.length === allItemsForCustomer.length;
            }
        });
    });

    // ===== PRINT SELECTED BUTTON (DARI FILE ASLI - TIDAK DIUBAH) =====
    const printBtn = document.getElementById('printSelectedBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            console.log("🖨️ Print button clicked!");
            const checked = document.querySelectorAll('.item-checkbox:checked');
            
            if (checked.length === 0) {
                alert('⚠️ Pilih setidaknya satu item untuk dicetak struknya.');
                return;
            }
            
            const dropIds = [...new Set(Array.from(checked).map(cb => cb.getAttribute('data-drop-id')))];
            
            console.log("Selected Drop IDs:", dropIds);
            
            const url = 'cetak_struk.php?id=' + dropIds.join(',');
            console.log("Opening URL:", url);
            window.open(url, '_blank', 'width=800,height=600');
        });
    }

    // ===== PRINT ALL BUTTON (DARI FILE ASLI - TIDAK DIUBAH) =====
    document.querySelectorAll('.print-all-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const customerId = this.getAttribute('data-customer-id');
            
            console.log(`🖨️ Printing all orders for customer ${customerId}`);
            
            const itemsForCustomer = document.querySelectorAll(`.item-checkbox[data-customer-id="${customerId}"]`);
            const dropIds = [...new Set(Array.from(itemsForCustomer).map(cb => cb.getAttribute('data-drop-id')))];
            
            if (dropIds.length === 0) {
                alert('⚠️ Tidak ada pesanan untuk dicetak.');
                return;
            }
            
            const url = 'cetak_struk.php?id=' + dropIds.join(',');
            window.open(url, '_blank', 'width=800,height=600');
        });
    });

    // ===== DELETE BUTTON (DARI FILE ASLI - TIDAK DIUBAH) =====
    const deleteBtn = document.getElementById('deleteBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            console.log("🗑️ Delete button clicked!");
            const checked = document.querySelectorAll('.item-checkbox:checked');
            
            if (checked.length === 0) {
                alert('⚠️ Pilih setidaknya satu item untuk dihapus.');
                return;
            }
            
            const dropIds = [...new Set(Array.from(checked).map(cb => cb.getAttribute('data-drop-id')))];
            
            const confirmModal = document.getElementById('confirmDeleteModal');
            confirmModal.style.display = 'flex';
            
            confirmModal.dataset.dropIds = dropIds.join(',');
        });
    }
    
    document.getElementById('confirmCancel').addEventListener('click', function() {
        const confirmModal = document.getElementById('confirmDeleteModal');
        confirmModal.style.display = 'none';
        delete confirmModal.dataset.dropIds;
    });

    document.getElementById('confirmOk').addEventListener('click', function() {
        const confirmModal = document.getElementById('confirmDeleteModal');
        const dropIds = confirmModal.dataset.dropIds;
        
        if (!dropIds) {
            alert('⚠️ Tidak ada data yang dipilih.');
            confirmModal.style.display = 'none';
            return;
        }
        
        confirmModal.style.display = 'none';

        console.log("Deleting Drop IDs:", dropIds);

        this.disabled = true;
        this.textContent = '⏳ Menghapus...';

        fetch('drop_delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ids=' + dropIds
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                sessionStorage.setItem('showSuccess', 'true');
                sessionStorage.setItem('successMessage', data.message || '✅ Data berhasil dihapus.');
                window.location.reload();
            } else {
                alert('❌ Gagal menghapus data: ' + (data.message || 'Unknown error'));
                this.disabled = false;
                this.textContent = '✓ Hapus';
            }
        })
        .catch(error => {
            alert('❌ Terjadi kesalahan saat menghapus data: ' + error.message);
            this.disabled = false;
            this.textContent = '✓ Hapus';
        });
    });

    // ===== STATUS DROPDOWN (DARI FILE ASLI - TIDAK DIUBAH) =====
    document.querySelectorAll('.status-dropdown').forEach(select => {
        select.addEventListener('change', function() {
            const dropId = this.getAttribute('data-drop-id');
            const newStatusId = this.value;
            
            if (!confirm('⚠️ Apakah Anda yakin ingin mengubah status pesanan ini?')) {
                this.value = this.dataset.oldValue || this.value;
                return;
            }

            const oldValue = this.dataset.oldValue || this.value;
            this.dataset.oldValue = oldValue;
            this.disabled = true;

            fetch('drop_update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id_drop=${dropId}&status_id=${newStatusId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    sessionStorage.setItem('showSuccess', 'true');
                    sessionStorage.setItem('successMessage', data.message || '✅ Status berhasil diubah.');
                    window.location.reload();
                } else {
                    alert('❌ Gagal mengubah status: ' + (data.message || 'Unknown error'));
                    this.value = oldValue;
                    this.disabled = false;
                }
            })
            .catch(error => {
                alert('❌ Terjadi kesalahan saat mengubah status: ' + error.message);
                this.value = oldValue;
                this.disabled = false;
            });
        });
        
        select.dataset.oldValue = select.value;
    });

    // ===== KEYBOARD SHORTCUTS (DARI FILE ASLI - TIDAK DIUBAH) =====
    document.addEventListener('keydown', function(e) {
        // ESC to close modals
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal, .note-modal, .item-edit-modal').forEach(modal => {
                if (modal.style.display === 'block') {
                    modal.style.display = 'none';
                    document.body.style.overflow = '';
                }
            });
            const confirmModal = document.getElementById('confirmDeleteModal');
            if (confirmModal && confirmModal.style.display === 'flex') {
                confirmModal.style.display = 'none';
            }
        }
        
        // Ctrl+N for new order
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            document.getElementById('openAddModal').click();
        }
    });
    
    console.log("✅ All event listeners initialized");
    console.log("📋 Keyboard shortcuts: ESC = Close modal, Ctrl+N = New order");
    console.log("🖱️ Double-click row to edit item");
    console.log("📝 Click note cell to edit note");
});
</script>