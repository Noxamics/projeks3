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

?>

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


// ===== AUTENTIKASI ADMIN (HARUS DI PALING ATAS) =====
require_once '../db.php';
require_once 'check_auth.php';

// Ambil data admin yang sedang login
$adminData = getAdminData();
$adminName = $adminData['name'];
$adminEmail = $adminData['email'];
$adminId = $adminData['id_admin'];

// ===== HEADER =====
include('../partials/headerAdmin.php');


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
            <h1 class="title">Manajemen Drop</h1>

            <!-- TOP BAR -->
            <div class="top-bar">
                <div class="left-bar">
                    <button class="add-btn" id="openAddModal">Tambah Pesanan</button>
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
                            <option value="" disabled selected hidden>Filter Data</option>
                            <option value="nama_asc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'nama_asc') ? 'selected' : '' ?>>Nama (A-Z)</option>
                            <option value="nama_desc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'nama_desc') ? 'selected' : '' ?>>Nama (Z-A)</option>
                            <option value="tanggal_desc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'tanggal_desc') ? 'selected' : '' ?>>Tanggal Terbaru</option>
                            <option value="tanggal_asc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'tanggal_asc') ? 'selected' : '' ?>>Tanggal Terlama</option>
                        </select>
                    </form>

                    <button class="print-btn" id="printSelectedBtn" title="Cetak Struk Terpilih">
                        Cetak Struk
                    </button>

                    <button class="delete-btn" id="deleteBtn" title="Hapus Data Terpilih">

                        Hapus
                    </button>
                </div>
            </div>

            <!-- TOGGLE & STATISTICS BAR -->
            <div class="stats-bar">
                <div class="stats-badges">
                    <div class="stat-badge stat-active">
                        <span class="stat-number"><?= $active_count ?></span>
                        <span class="stat-label">Aktif</span>
                    </div>
                    <div class="stat-badge stat-completed">
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
        </div>
    </main>

    <?php
    include('../modal/drop/modal_add.php');
    include('../modal/drop/modal_edit_item.php');
    include('../modal/drop/modal_confirm_delete.php');
    ?>
    <?php include_once "../partials/footer.php"; ?>

    <!-- SCRIPTS -->
    <script src="../js/drop/modal_system.js"></script>
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