<?php
// 1. Require db.php dulu
require_once '../db.php';

// 2. Include header
include('../partials/headerAdmin.php');

// 3. Query untuk mengambil data performance dari drops
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
        
        -- Status dari drop_items (ambil status terakhir)
        (SELECT s.status_name 
         FROM drop_items di 
         JOIN statuses s ON di.status_id = s.id_status 
         WHERE di.drop_id = d.id_drop 
         ORDER BY di.item_order DESC LIMIT 1) as current_status,
        
        -- Karyawan yang menerima (kasir)
        e_received.name as received_by_name,
        e_received.employee_code as received_by_code,
        d.received_at,
        
        -- Karyawan yang mengerjakan
        e_processed.name as processed_by_name,
        e_processed.employee_code as processed_by_code,
        d.processed_at,
        
        -- Karyawan yang packing
        e_packed.name as packed_by_name,
        e_packed.employee_code as packed_by_code,
        d.packed_at,
        
        -- Karyawan yang mengeluarkan
        e_released.name as released_by_name,
        e_released.employee_code as released_by_code,
        d.released_at,
        
        d.total_amount,
        d.total_items
        
    FROM drops d
    LEFT JOIN customers c ON d.customer_id = c.id_customer
    LEFT JOIN employees e_received ON d.received_by = e_received.id_employee
    LEFT JOIN employees e_processed ON d.processed_by = e_processed.id_employee
    LEFT JOIN employees e_packed ON d.packed_by = e_packed.id_employee
    LEFT JOIN employees e_released ON d.released_by = e_released.id_employee
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

// Helper function untuk format tanggal
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

// Hitung statistik
$stats = [
    'total' => count($performanceData),
    'new' => 0,
    'process' => 0,
    'ready' => 0,
    'picked' => 0
];

foreach ($performanceData as $row) {
    $status = $row['current_status'] ?? '';
    if ($status === 'Barang Baru Masuk')
        $stats['new']++;
    elseif ($status === 'Sedang Dikerjakan')
        $stats['process']++;
    elseif ($status === 'Barang Siap Diambil')
        $stats['ready']++;
    elseif ($status === 'Barang Telah Diambil')
        $stats['picked']++;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Tracking - Sengkuclean</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .title-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .title-icon svg {
            width: 28px;
            height: 28px;
            stroke: white;
        }

        .btn-back {
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            color: #4a5568;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-back:hover {
            background: white;
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .page-description {
            color: #718096;
            font-size: 16px;
            margin-top: 8px;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }

        .stat-card.stat-new {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .stat-card.stat-process {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .stat-card.stat-ready {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .stat-card.stat-picked {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .content-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-box {
            position: relative;
            flex: 1;
            min-width: 250px;
            max-width: 400px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 45px 12px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
        }

        .filter-group {
            display: flex;
            gap: 10px;
        }

        .filter-select {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
        }

        .table-wrapper {
            overflow-x: auto;
            border-radius: 15px;
            border: 2px solid #e2e8f0;
        }

        .performance-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1400px;
        }

        .performance-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .performance-table th {
            padding: 18px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .performance-table td {
            padding: 16px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
            vertical-align: top;
        }

        .performance-table tbody tr {
            transition: all 0.2s ease;
        }

        .performance-table tbody tr:hover {
            background: #f7fafc;
        }

        .employee-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 120px;
        }

        .employee-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 13px;
        }

        .employee-code {
            font-size: 11px;
            color: #718096;
        }

        .employee-time {
            font-size: 10px;
            color: #a0aec0;
            margin-top: 2px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-new {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-queue {
            background: #fef3c7;
            color: #92400e;
        }

        .status-process {
            background: #fed7aa;
            color: #c2410c;
        }

        .status-ready {
            background: #d1fae5;
            color: #065f46;
        }

        .status-picked {
            background: #e5e7eb;
            color: #374151;
        }

        .order-code {
            font-weight: 700;
            color: #667eea;
            font-size: 14px;
        }

        .customer-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 13px;
        }

        .customer-phone {
            font-size: 11px;
            color: #718096;
            margin-top: 2px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #a0aec0;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 20px;
            }

            .header-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .page-title {
                font-size: 24px;
            }

            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .table-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                max-width: 100%;
            }

            .content-card {
                padding: 15px;
            }

            .table-wrapper {
                border-radius: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="page-header">
            <div class="header-top">
                <div>
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
        </div>

        <div class="content-card">
            <div class="table-controls">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Cari order, customer, atau karyawan...">
                    <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" />
                        <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
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

                                    <td><span class="order-code"><?php echo htmlspecialchars($row['order_code']); ?></span></td>

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
                                            <span style="color: #cbd5e0;">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- TANGGAL DIKERJAKAN - DARI processed_at -->
                                    <td>
                                        <?php
                                        if (!empty($row['processed_at']) && $row['processed_at'] !== '0000-00-00 00:00:00') {
                                            echo formatDateOnly($row['processed_at']);
                                        } else {
                                            echo '<span style="color: #cbd5e0;">-</span>';
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
                                            <span style="color: #cbd5e0;">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- TANGGAL SIAP DIAMBIL - DARI packed_at -->
                                    <td>
                                        <?php
                                        if (!empty($row['packed_at']) && $row['packed_at'] !== '0000-00-00 00:00:00') {
                                            echo formatDateOnly($row['packed_at']);
                                        } else {
                                            echo '<span style="color: #cbd5e0;">-</span>';
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
                                            <span style="color: #cbd5e0;">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- TANGGAL DIAMBIL - DARI released_at -->
                                    <td>
                                        <?php
                                        if (!empty($row['released_at']) && $row['released_at'] !== '0000-00-00 00:00:00') {
                                            echo formatDateOnly($row['released_at']);
                                        } else {
                                            echo '<span style="color: #cbd5e0;">-</span>';
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
                                            <span style="color: #cbd5e0;">-</span>
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
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', filterTable);
        document.getElementById('statusFilter').addEventListener('change', filterTable);
        document.getElementById('monthFilter').addEventListener('change', filterTable);

        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const monthFilter = document.getElementById('monthFilter').value;
            const rows = document.querySelectorAll('.table-row');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const status = row.dataset.status;
                const month = row.dataset.month;

                const matchSearch = text.includes(searchTerm);
                const matchStatus = statusFilter === 'all' || status === statusFilter;
                const matchMonth = monthFilter === 'all' || month === monthFilter;

                row.style.display = (matchSearch && matchStatus && matchMonth) ? '' : 'none';
            });
        }

        // Contoh fungsi untuk update status dengan tracking (OPSIONAL)
        function updateStatusWithTracking(dropId, itemId, statusId, employeeId) {
            fetch('../actions/drop/update_status_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `drop_id=${dropId}&item_id=${itemId}&status_id=${statusId}&employee_id=${employeeId}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Status & tracking updated:', data);
                        location.reload(); // Reload untuk melihat perubahan
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                });
        }
    </script>
</body>

</html>

<?php include_once "../partials/footer.php"; ?>