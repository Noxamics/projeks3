<?php
// File: performance.php
// Database connection
require_once '../db.php';
include('../partials/headerAdmin.php');

// ============================================================
// QUERY BARU - TIDAK PERLU JOIN KE EMPLOYEES
// Data langsung dari tabel drops, aman dari penghapusan karyawan
// ============================================================

$performanceData = [];
try {
    $query = "SELECT 
        d.id_drop,
        d.order_code,
        d.trans_date,
        d.est_finish_date,
        d.actual_finish_date,
        c.name as customer_name,
        c.phone as customer_phone,
        
        -- Status terakhir dari drop_items
        (SELECT s.status_name 
         FROM drop_items di 
         JOIN statuses s ON di.status_id = s.id_status 
         WHERE di.drop_id = d.id_drop 
         ORDER BY di.item_order DESC LIMIT 1) as current_status,
        
        -- ✅ DATA KARYAWAN LANGSUNG DARI DROPS (tidak perlu JOIN)
        d.received_by_name,
        d.received_by_code,
        d.received_at,
        
        d.processed_by_name,
        d.processed_by_code,
        d.processed_at,
        
        d.packed_by_name,
        d.packed_by_code,
        d.packed_at,
        
        d.released_by_name,
        d.released_by_code,
        d.released_at,
        
        d.total_amount,
        d.total_items
        
    FROM drops d
    LEFT JOIN customers c ON d.customer_id = c.id_customer
    ORDER BY d.trans_date DESC, d.id_drop DESC";

    $result = $conn->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $performanceData[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching performance data: " . $e->getMessage());
}

// Helper functions (tidak ada perubahan)
function formatDate($date)
{
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '-';
    }
    return date('d/m/Y H:i', strtotime($date));
}

function formatDateOnly($date)
{
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '-';
    }
    return date('d/m/Y', strtotime($date));
}

// Calculate statistics (tidak ada perubahan)
$stats = [
    'total' => count($performanceData),
    'new' => 0,
    'process' => 0,
    'ready' => 0,
    'picked' => 0
];

foreach ($performanceData as $row) {
    $status = $row['current_status'] ?? '';
    if ($status === 'Barang Baru Masuk') {
        $stats['new']++;
    } elseif ($status === 'Sedang Dikerjakan') {
        $stats['process']++;
    } elseif ($status === 'Barang Siap Diambil') {
        $stats['ready']++;
    } elseif ($status === 'Barang Telah Diambil') {
        $stats['picked']++;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Tracking - Sengkuclean</title>
    <link rel="stylesheet" href="../css/performance/performance.css">
</head>

<body>
    <div class="container">
        <!-- Page Header -->
        <header class="page-header">
            <div class="header-top">
                <div class="header-content">
                    <h1 class="page-title">
                        <div class="title-icon">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18 20V10M12 20V4M6 20V14" stroke="currentColor" stroke-width="2.5"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        Performance Tracking
                    </h1>
                    <p class="page-description">Riwayat lengkap pesanan dan aktivitas karyawan</p>
                </div>
                <a href="karyawan.php" class="btn-back">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Kembali
                </a>
            </div>

            <!-- Statistics Row -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Pesanan</div>
                </div>
                <div class="stat-card stat-new">
                    <div class="stat-number"><?php echo $stats['new']; ?></div>
                    <div class="stat-label">Baru Masuk</div>
                </div>
                <div class="stat-card stat-process">
                    <div class="stat-number"><?php echo $stats['process']; ?></div>
                    <div class="stat-label">Dikerjakan</div>
                </div>
                <div class="stat-card stat-ready">
                    <div class="stat-number"><?php echo $stats['ready']; ?></div>
                    <div class="stat-label">Siap Diambil</div>
                </div>
                <div class="stat-card stat-picked">
                    <div class="stat-number"><?php echo $stats['picked']; ?></div>
                    <div class="stat-label">Selesai</div>
                </div>
            </div>
        </header>

        <!-- Content Card -->
        <main class="content-card">
            <!-- Table Controls -->
            <div class="table-controls">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Cari order, customer, atau karyawan...">
                    <button type="button" class="search-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" />
                            <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" />
                        </svg>
                    </button>
                </div>

                <div class="filter-group">
                    <select id="statusFilter" class="filter-select">
                        <option value="all">Semua Status</option>
                        <option value="Barang Baru Masuk">Baru Masuk</option>
                        <option value="Menunggu Antrian">Antrian</option>
                        <option value="Sedang Dikerjakan">Dikerjakan</option>
                        <option value="Barang Siap Diambil">Siap Diambil</option>
                        <option value="Barang Telah Diambil">Selesai</option>
                    </select>

                    <select id="monthFilter" class="filter-select">
                        <option value="all">Semua Bulan</option>
                        <?php
                        for ($i = 1; $i <= 12; $i++) {
                            $selected = ($i == date('n')) ? 'selected' : '';
                            echo "<option value='$i' $selected>" . date('F', mktime(0, 0, 0, $i, 1)) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Performance Table -->
            <div class="table-wrapper">
                <table class="performance-table">
                    <thead>
                        <tr>
                            <th>Kode Order</th>
                            <th>Tgl Masuk</th>
                            <th>Customer</th>
                            <th>Kasir Menerima</th>
                            <th>Tgl Dikerjakan</th>
                            <th>Karyawan Mengerjakan</th>
                            <th>Tgl Siap</th>
                            <th>Karyawan Packing</th>
                            <th>Tgl Diambil</th>
                            <th>Karyawan Mengeluarkan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php if (empty($performanceData)): ?>
                            <tr>
                                <td colspan="11">
                                    <div class="empty-state">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                            <path
                                                d="M21 12V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H16"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                        <h3>Belum ada data performance</h3>
                                        <p>Data tracking akan muncul setelah ada pesanan</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($performanceData as $row):
                                $statusClass = 'status-new';
                                $statusText = $row['current_status'] ?? 'Barang Baru Masuk';

                                if (strpos($statusText, 'Menunggu') !== false) {
                                    $statusClass = 'status-queue';
                                } elseif (strpos($statusText, 'Dikerjakan') !== false) {
                                    $statusClass = 'status-process';
                                } elseif (strpos($statusText, 'Siap') !== false) {
                                    $statusClass = 'status-ready';
                                } elseif (strpos($statusText, 'Diambil') !== false) {
                                    $statusClass = 'status-picked';
                                }
                                ?>
                                <tr class="table-row" data-status="<?php echo htmlspecialchars($statusText); ?>"
                                    data-month="<?php echo date('n', strtotime($row['trans_date'])); ?>">

                                    <td>
                                        <span class="order-code"><?php echo htmlspecialchars($row['order_code']); ?></span>
                                    </td>

                                    <td><?php echo formatDateOnly($row['trans_date']); ?></td>

                                    <td>
                                        <div class="customer-name"><?php echo htmlspecialchars($row['customer_name']); ?></div>
                                        <div class="customer-phone"><?php echo htmlspecialchars($row['customer_phone']); ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if ($row['received_by_name']): ?>
                                            <div class="employee-info">
                                                <span
                                                    class="employee-name"><?php echo htmlspecialchars($row['received_by_name']); ?></span>
                                                <span
                                                    class="employee-code"><?php echo htmlspecialchars($row['received_by_code']); ?></span>
                                                <span class="employee-time"><?php echo formatDate($row['received_at']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="empty-value">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php
                                        if (!empty($row['processed_at']) && $row['processed_at'] !== '0000-00-00 00:00:00') {
                                            echo formatDateOnly($row['processed_at']);
                                        } else {
                                            echo '<span class="empty-value">-</span>';
                                        }
                                        ?>
                                    </td>

                                    <td>
                                        <?php if ($row['processed_by_name']): ?>
                                            <div class="employee-info">
                                                <span
                                                    class="employee-name"><?php echo htmlspecialchars($row['processed_by_name']); ?></span>
                                                <span
                                                    class="employee-code"><?php echo htmlspecialchars($row['processed_by_code']); ?></span>
                                                <span class="employee-time"><?php echo formatDate($row['processed_at']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="empty-value">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php
                                        if (!empty($row['packed_at']) && $row['packed_at'] !== '0000-00-00 00:00:00') {
                                            echo formatDateOnly($row['packed_at']);
                                        } else {
                                            echo '<span class="empty-value">-</span>';
                                        }
                                        ?>
                                    </td>

                                    <td>
                                        <?php if ($row['packed_by_name']): ?>
                                            <div class="employee-info">
                                                <span
                                                    class="employee-name"><?php echo htmlspecialchars($row['packed_by_name']); ?></span>
                                                <span
                                                    class="employee-code"><?php echo htmlspecialchars($row['packed_by_code']); ?></span>
                                                <span class="employee-time"><?php echo formatDate($row['packed_at']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="empty-value">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php
                                        if (!empty($row['released_at']) && $row['released_at'] !== '0000-00-00 00:00:00') {
                                            echo formatDateOnly($row['released_at']);
                                        } else {
                                            echo '<span class="empty-value">-</span>';
                                        }
                                        ?>
                                    </td>

                                    <td>
                                        <?php if ($row['released_by_name']): ?>
                                            <div class="employee-info">
                                                <span
                                                    class="employee-name"><?php echo htmlspecialchars($row['released_by_name']); ?></span>
                                                <span
                                                    class="employee-code"><?php echo htmlspecialchars($row['released_by_code']); ?></span>
                                                <span class="employee-time"><?php echo formatDate($row['released_at']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="empty-value">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($statusText); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script src="../js/performance/performance.js"></script>
</body>

</html>

<?php include_once "../partials/footer.php"; ?>