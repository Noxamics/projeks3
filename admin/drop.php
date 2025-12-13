<?php
// File: admin/drop.php - GROUPED BY DATE VERSION

// ===== HANDLE AJAX REQUESTS (tetap sama) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    include('../db.php');
    error_reporting(E_ALL);
    ini_set('display_errors', 0);

    $action = $_POST['action'];

    // DELETE SINGLE ITEM
    if ($action === 'delete_single_item') {
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        $drop_id = isset($_POST['drop_id']) ? intval($_POST['drop_id']) : 0;

        if ($item_id <= 0 || $drop_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
            exit;
        }

        try {
            $conn->begin_transaction();

            $stmt_count = $conn->prepare("SELECT COUNT(*) as item_count FROM drop_items WHERE drop_id = ?");
            $stmt_count->bind_param("i", $drop_id);
            $stmt_count->execute();
            $item_count = intval($stmt_count->get_result()->fetch_assoc()['item_count'] ?? 0);
            $stmt_count->close();

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

            $stmt_del = $conn->prepare("DELETE FROM drop_items WHERE id_item = ? AND drop_id = ?");
            $stmt_del->bind_param("ii", $item_id, $drop_id);
            $stmt_del->execute();
            $affected = $stmt_del->affected_rows;
            $stmt_del->close();

            if ($affected === 0) {
                throw new Exception("Item tidak ditemukan atau sudah dihapus");
            }

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

            $stmt_total = $conn->prepare("SELECT SUM(price) as total FROM drop_items WHERE drop_id = ?");
            $stmt_total->bind_param("i", $drop_id);
            $stmt_total->execute();
            $new_total = floatval($stmt_total->get_result()->fetch_assoc()['total'] ?? 0);
            $stmt_total->close();

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

            $stmt_up = $conn->prepare("UPDATE drops SET total_amount = ?, est_finish_date = ? WHERE id_drop = ?");
            $stmt_up->bind_param("dsi", $new_total, $new_est_date, $drop_id);
            $stmt_up->execute();
            $stmt_up->close();

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

    // DELETE ALL CUSTOMER ORDERS
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

// ===== AUTENTIKASI =====
require_once '../db.php';

// ===== HEADER =====
include('../partials/headerAdmin.php');

// ===== QUERY DATA - GROUPED BY DATE =====
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Query untuk mendapatkan semua tanggal transaksi yang unik
$sql_dates = "
    SELECT DISTINCT DATE(d.trans_date) as trans_date
    FROM drops d
    INNER JOIN drop_items di ON d.id_drop = di.drop_id
";

if (!empty($search)) {
    $esc = $conn->real_escape_string($search);
    $sql_dates .= " INNER JOIN customers c ON d.customer_id = c.id_customer
                    WHERE (c.name LIKE '%$esc%' OR c.phone LIKE '%$esc%')";
}

$sql_dates .= " ORDER BY d.trans_date DESC";

$result_dates = $conn->query($sql_dates);

// ===== HITUNG STATISTIK =====
$stats_query = "
    SELECT 
        COUNT(CASE WHEN di.status_id != 6 THEN 1 END) as active_count,
        COUNT(CASE WHEN di.status_id = 6 THEN 1 END) as completed_count
    FROM drop_items di
";

$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

$active_count = isset($stats['active_count']) ? intval($stats['active_count']) : 0;
$completed_count = isset($stats['completed_count']) ? intval($stats['completed_count']) : 0;

// ===== FUNGSI HELPER UNTUK FORMAT TANGGAL =====
function formatIndonesianDate($date)
{
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $months = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    $timestamp = strtotime($date);
    $day_name = $days[date('w', $timestamp)];
    $day = date('d', $timestamp);
    $month = $months[(int) date('m', $timestamp)];
    $year = date('Y', $timestamp);

    return "$day_name, $day $month $year";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Drop - Grouped by Date</title>
    <link rel="stylesheet" href="../css/drop/drop.css">
    <link rel="stylesheet" href="../css/drop/mobile.css">
    <link rel="stylesheet" href="../css/drop/badge.css">
    <link rel="stylesheet" href="../css/drop/m_optimaze.css">
    <style>
        /* Additional styles for date grouping */
        .date-group-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 24px;
            margin: 24px 0 16px 0;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .date-group-header svg {
            flex-shrink: 0;
        }

        .date-group-container {
            margin-bottom: 32px;
        }

        .date-group-stats {
            font-size: 14px;
            opacity: 0.95;
            margin-left: auto;
            display: flex;
            gap: 16px;
        }

        .date-group-stats span {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .date-group-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 12px 16px;
                font-size: 16px;
            }

            .date-group-stats {
                margin-left: 0;
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>

<body>

    <main class="drop-page">
        <div class="drop-container">
            <h1 class="title">Manajemen Drop</h1>

            <!-- TOP BAR -->
            <div class="top-bar">
                <div class="left-bar">
                    <form method="GET" action="" class="search-box">
                        <input type="text" name="search" placeholder="Cari data..."
                            value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                        <button type="submit" class="search-btn">
                            <img src="../a/assets/Pencarian.png" alt="Cari" />
                        </button>
                    </form>

                    <button class="add-btn" id="openAddModal">Tambah Pesanan</button>
                </div>

                <div class="right-tools">
                    <button onclick="testQZ()">TEST QZ</button>
                    <button class="print-btn" id="printSelectedBtn" title="Cetak Struk Terpilih">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                            viewBox="0 0 16 16">
                            <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1" />
                            <path
                                d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1" />
                        </svg>
                    </button>

                    <button class="print-btn" id="printSelectedThermalBtn"
                        style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);"
                        title="Cetak Thermal Terpilih">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                            viewBox="0 0 16 16">
                            <path
                                d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1" />
                            <path
                                d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1" />
                        </svg>
                    </button>


                    <button class="delete-btn" id="deleteBtn" title="Hapus Data Terpilih">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                            viewBox="0 0 16 16">
                            <path
                                d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z" />
                            <path
                                d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- STATISTICS BAR -->
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

                <div class="toggle-completed-container">
                    <label class="toggle-switch">
                        <input type="checkbox" id="toggleCompleted" checked>
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="toggle-label">Tampilkan barang yang sudah diambil</span>
                </div>
            </div>

            <!-- TABLE CONTAINER - GROUPED BY DATE -->
            <div class="table-container">
                <?php
                if ($result_dates->num_rows === 0) {
                    echo '<div class="empty-state">                        
                        <div class="empty-state-text">Belum ada pesanan</div>
                    </div>';
                } else {
                    // Loop setiap tanggal
                    while ($date_row = $result_dates->fetch_assoc()) {
                        // CRITICAL: Simpan tanggal asli dari database dalam format YYYY-MM-DD
                        $trans_date_original = $date_row['trans_date'];

                        // Buat versi formatted HANYA untuk display
                        $trans_date_display = formatIndonesianDate($trans_date_original);

                        // Query untuk mendapatkan ALL customers yang punya pesanan di tanggal ini
                        // Menggunakan INNER JOIN untuk memastikan dapat semua customer
                        $sql_customers_by_date = "
                            SELECT DISTINCT
                                c.id_customer, 
                                c.name, 
                                c.phone
                            FROM drops d
                            INNER JOIN customers c ON d.customer_id = c.id_customer
                            WHERE DATE(d.trans_date) = ?
                        ";

                        if (!empty($search)) {
                            $esc = $conn->real_escape_string($search);
                            $sql_customers_by_date .= " AND (c.name LIKE '%$esc%' OR c.phone LIKE '%$esc%')";
                        }

                        $sql_customers_by_date .= " ORDER BY c.name ASC";

                        $stmt_customers = $conn->prepare($sql_customers_by_date);
                        if (!$stmt_customers) {
                            error_log("ERROR preparing customer query: " . $conn->error);
                            continue;
                        }

                        // GUNAKAN $trans_date_original yang masih format YYYY-MM-DD
                        $stmt_customers->bind_param("s", $trans_date_original);
                        $stmt_customers->execute();
                        $result_customers = $stmt_customers->get_result();

                        $customer_count = $result_customers->num_rows;

                        if ($customer_count > 0) {
                            // Hitung stats untuk tanggal ini - GUNAKAN QUERY YANG SAMA PERSIS
                            $stmt_stats = $conn->prepare("
                                SELECT 
                                    COUNT(DISTINCT d.customer_id) as customer_count,
                                    COUNT(DISTINCT d.id_drop) as order_count,
                                    COUNT(di.id_item) as item_count
                                FROM drops d
                                INNER JOIN drop_items di ON d.id_drop = di.drop_id
                                WHERE DATE(d.trans_date) = ?
                            ");
                            $stmt_stats->bind_param("s", $trans_date_original);
                            $stmt_stats->execute();
                            $date_stats = $stmt_stats->get_result()->fetch_assoc();
                            $stmt_stats->close();

                            // Gunakan customer_count dari result query, bukan dari stats
                            $actual_customer_count = $customer_count;

                            echo '<div class="date-group-container">';
                            echo '<div class="date-group-header">';
                            echo '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
                                  </svg>';
                            echo '<span>' . $trans_date_display . '</span>';
                            echo '<div class="date-group-stats">';
                            echo '<span>' . $date_stats['customer_count'] . ' Customer</span>';
                            echo '<span>' . $date_stats['item_count'] . ' Item</span>';
                            echo '</div>';
                            echo '</div>';

                            // Tampilkan SEMUA customers di tanggal ini
                            while ($customer = $result_customers->fetch_assoc()) {
                                // CRITICAL: Kirim tanggal ORIGINAL yang masih format YYYY-MM-DD
                                $customer['filter_date'] = $trans_date_original;

                                // DEBUG: Log untuk memastikan format tanggal benar
                                error_log("Sending to customer_group.php - Customer: {$customer['name']} (ID: {$customer['id_customer']}), filter_date: {$trans_date_original}");

                                // Include customer group - akan menampilkan semua orders customer ini di tanggal ini
                                include('../partials/drop/customer_group.php');
                            }

                            echo '</div>'; // close date-group-container
                        }

                        $stmt_customers->close();
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
    include('../modal/drop/modal_system.php');
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
    <script src="../js/drop/thermal_print_handler.js"></script>
    <script src="../js/drop/badge.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.5/qz-tray.js"></script>
    <script>
        async function testQZ() {
            try {
                if (!qz.websocket.isActive()) {
                    await qz.websocket.connect();
                }
                alert("QZ Tray TERHUBUNG");
            } catch (err) {
                alert("GAGAL: " + err);
            }
        }
    </script>
</body>

</html>