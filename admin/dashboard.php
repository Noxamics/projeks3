<?php // <-- TIDAK ADA SPASI SEBELUM INI

// 1. Require db.php dulu (ini akan start session)
require_once '../db.php';

// 2. Baru require check_auth
//require_once 'check_auth.php';

// 3. Ambil data admin
//$adminData = getAdminData();
//$adminName = $adminData['name'];
//$adminEmail = $adminData['email'];
//$adminId = $adminData['id_admin'];

// 4. Baru include header (yang mungkin ada output HTML)
include('../partials/headerAdmin.php');
?>

<link rel="stylesheet" href="../css/analisis.css">

<?php
// KONEKSI DATABASE (sudah disediakan di db.php)
include('../db.php'); // Sesuaikan path jika perlu
date_default_timezone_set('Asia/Jakarta');
// === METRIK BULANAN ===
// Filter SQL: YEAR(d.trans_date) = YEAR(CURDATE()) AND MONTH(d.trans_date) = MONTH(CURDATE())
// Logika ini sudah benar, mengambil data dari tanggal 1 bulan ini hingga saat ini.

## 💰 Total Penjualan Bulan Ini
$total_sales_month = $conn->query("
    SELECT COALESCE(SUM(di.subtotal), 0) as total 
    FROM drop_items di
    JOIN drops d ON di.drop_id = d.id_drop
    JOIN payments p ON d.id_drop = p.drop_id
    WHERE YEAR(d.trans_date) = YEAR(CURDATE()) 
      AND MONTH(d.trans_date) = MONTH(CURDATE()) 
      AND p.status = 'Lunas'
")->fetch_assoc()['total'] ?? 0;

/* CATATAN PENTING: Saya mengubah SUM(di.price) menjadi SUM(di.subtotal) 
   berdasarkan skema database Anda, di mana 'subtotal' adalah kolom 
   GENERATED ('quantity' * 'price'). Ini memastikan perhitungan 
   total penjualan yang akurat jika kuantitas item lebih dari satu.
   SUM(di.price) hanya akan mengambil harga satuan.
*/

## 🛒 Total Pesanan Bulan Ini
$total_order_month = $conn->query("
    SELECT COUNT(DISTINCT d.id_drop) as count 
    FROM drops d 
    JOIN payments p ON d.id_drop = p.drop_id 
    WHERE YEAR(p.payment_date) = YEAR(CURDATE()) 
      AND MONTH(p.payment_date) = MONTH(CURDATE()) 
      AND p.status = 'Lunas'
")->fetch_assoc()['count'] ?? 0;

/* CATATAN PENTING: Saya menambahkan COUNT(DISTINCT d.id_drop) untuk 
   memastikan bahwa satu pesanan (drop) tidak dihitung lebih dari sekali 
   jika ada multiple payment/drop_items, meskipun 'drops' harusnya unik.
*/

// === SERVICE COMPLETED (Bulanan) ===
## ✅ Layanan Selesai Bulan Ini (Jumlah barang/kuantitas)
$service_completed_month = $conn->query("
    SELECT COALESCE(SUM(di.quantity), 0) as total
    FROM status_history sh
    JOIN drops d ON sh.drop_id = d.id_drop
    JOIN drop_items di ON d.id_drop = di.drop_id
    WHERE YEAR(sh.changed_at) = YEAR(CURDATE())
      AND MONTH(sh.changed_at) = MONTH(CURDATE())
      AND sh.status_id >= 4 
")->fetch_assoc()['total'] ?? 0;

## 🆕 Pelanggan Baru Bulan Ini
$new_customers_month = $conn->query("
    SELECT COUNT(*) as count 
    FROM customers 
    WHERE YEAR(created_at) = YEAR(CURDATE())
      AND MONTH(created_at) = MONTH(CURDATE())
")->fetch_assoc()['count'] ?? 0;


// ---
// === PERUBAHAN DARI BULAN LALU ===
// Mengambil data untuk BULAN LALU (LFM: Last Full Month)

## 📉 Penjualan Bulan Lalu
$last_month_sales = $conn->query("
    SELECT COALESCE(SUM(di.subtotal), 0) as total 
    FROM drop_items di
    JOIN drops d ON di.drop_id = d.id_drop
    JOIN payments p ON d.id_drop = p.drop_id
    WHERE YEAR(d.trans_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
      AND MONTH(d.trans_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
      AND p.status = 'Lunas'
")->fetch_assoc()['total'] ?? 0;

$sales_change_month = $last_month_sales > 0
    ? round((($total_sales_month - $last_month_sales) / $last_month_sales) * 100, 1)
    : ($total_sales_month > 0 ? 100 : 0);

## 📦 Pesanan Bulan Lalu
$last_month_orders = $conn->query("
    SELECT COUNT(DISTINCT d.id_drop) as count 
    FROM drops d 
    JOIN payments p ON d.id_drop = p.drop_id 
    WHERE YEAR(p.payment_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
      AND MONTH(p.payment_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
      AND p.status = 'Lunas'
")->fetch_assoc()['count'] ?? 0;

$order_change_month = $last_month_orders > 0
    ? round((($total_order_month - $last_month_orders) / $last_month_orders) * 100, 1)
    : ($total_order_month > 0 ? 100 : 0);

## 🔄 Layanan Selesai Bulan Lalu
$last_month_completed = $conn->query("
    SELECT COUNT(sh.id) as total
    FROM status_history sh
    WHERE 
      -- Status Selesai atau lebih tinggi (4: COMPLETED, 5: READY, 6: PICKED)
      sh.status_id >= 4
      
      -- Filter untuk bulan lalu (lebih aman daripada membandingkan YEAR dan MONTH)
      AND sh.changed_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
      AND sh.changed_at < DATE_FORMAT(CURDATE(), '%Y-%m-01')
")->fetch_assoc()['total'] ?? 0;

// Perubahan persentase Layanan Selesai (Logika ini tetap sama)
$completed_change_month = $last_month_completed > 0
    ? round((($service_completed_month - $last_month_completed) / $last_month_completed) * 100, 1)
    : ($service_completed_month > 0 ? 100 : 0);

## 🧑‍🤝‍🧑 Pelanggan Baru Bulan Lalu
$last_month_customers = $conn->query("
    SELECT COUNT(*) as count 
    FROM customers 
    WHERE YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
      AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
")->fetch_assoc()['count'] ?? 0;

$customer_change_month = $last_month_customers > 0
    ? round((($new_customers_month - $last_month_customers) / $last_month_customers) * 100, 1)
    : ($new_customers_month > 0 ? 100 : 0);

// === REVENUE 7 HARI TERAKHIR (FIXED) ===
$revenue_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime($date));

    $amount = $conn->query("
        SELECT COALESCE(SUM(di.price), 0) as amount 
        FROM drop_items di
        JOIN drops d ON di.drop_id = d.id_drop
        JOIN payments p ON d.id_drop = p.drop_id
        WHERE DATE(d.trans_date) = '$date' 
          AND p.status = 'Lunas'
    ")->fetch_assoc()['amount'] ?? 0;

    $revenue_data[] = [
        'day' => $day_name,
        'amount' => (int) $amount
    ];
}

// === STATUS ORDER BULAN INI ===
$this_month = date('Y-m');
$first_day = $this_month . '-01';
$last_day = date('Y-m-t', strtotime($first_day)); // tanggal terakhir bulan ini

$status_data = $conn->query("
    SELECT 
        s.id_status,
        s.status_name,
        s.status_code,
        s.status_order,
        -- Menghitung jumlah *unique* order (drops) yang memiliki item di status ini
        COALESCE(COUNT(DISTINCT d.id_drop), 0) AS total_orders, 
        -- Menjumlahkan kuantitas item yang *saat ini* berada di status ini
        COALESCE(SUM(di.quantity), 0) AS total_items
    FROM statuses s
    LEFT JOIN drop_items di ON s.id_status = di.status_id
    -- Join ke drops untuk filter tanggal transaksi
    LEFT JOIN drops d ON di.drop_id = d.id_drop
        AND d.trans_date >= '$first_day'
        AND d.trans_date <= '$last_day 23:59:59'
    GROUP BY s.id_status, s.status_name, s.status_code, s.status_order
    ORDER BY s.status_order ASC
")->fetch_all(MYSQLI_ASSOC);

// === 4. TOP SERVICE ===
$top_services = $conn->query("
    SELECT 
        s.service_name as name,
        s.category as type,
        COUNT(di.id_item) as total_orders,
        ROUND(COUNT(di.id_item) * 100.0 / (SELECT COUNT(*) FROM drop_items), 1) as sales_percentage,
        ROUND(COUNT(di.id_item) * 100.0 / MAX(total.max_orders), 1) as popularity
    FROM drop_items di
    JOIN services s ON di.service_id = s.id_service
    CROSS JOIN (SELECT COUNT(*) as max_orders FROM drop_items) total
    GROUP BY di.service_id
    ORDER BY total_orders DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

?>
<main class="analisis-container">
    <h1 class="page-title">Dashboard</h1>
    <div class="sales-section">
        <div class="section-header">
            <h2>This Month's Sales</h2>
            <p class="subtitle">Sales Summary</p>
        </div>
        <div class="stats-grid">
            <div class="stat-card pink">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M20 7H4C2.9 7 2 7.9 2 9V19C2 20.1 2.9 21 4 21H20C21.1 21 22 20.1 22 19V9C22 7.9 21.1 7 20 7Z"
                            fill="currentColor" />
                        <path d="M16 3H8C6.9 3 6 3.9 6 5V7H18V5C18 3.9 17.1 3 16 3Z" fill="currentColor" />
                    </svg>
                </div>
                <div class="stat-value">Rp. <?php echo number_format($total_sales_month ?? 0, 0, ',', '.'); ?></div>
                <div class="stat-label">Total Sales</div>
                <div class="stat-change <?php echo ($sales_change_month ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo ($sales_change_month ?? 0) >= 0 ? '+' : ''; ?><?php echo $sales_change_month ?? 0; ?>%
                    from last month
                </div>
            </div>
            <div class="stat-card yellow">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3ZM9 17H7V10H9V17ZM13 17H11V7H13V17ZM17 17H15V13H17V17Z"
                            fill="currentColor" />
                    </svg>
                </div>
                <div class="stat-value"><?php echo $total_order_month ?? 0; ?></div>
                <div class="stat-label">Total Order</div>
                <div class="stat-change <?php echo ($order_change_month ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo ($order_change_month ?? 0) >= 0 ? '+' : ''; ?><?php echo $order_change_month ?? 0; ?>%
                    from last month
                </div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M19.14 12.94C19.18 12.64 19.2 12.33 19.2 12C19.2 11.68 19.18 11.36 19.13 11.06L21.16 9.48C21.34 9.34 21.39 9.07 21.28 8.87L19.36 5.55C19.24 5.33 18.99 5.26 18.77 5.33L16.38 6.29C15.88 5.91 15.35 5.59 14.76 5.35L14.4 2.81C14.36 2.57 14.16 2.4 13.92 2.4H10.08C9.84 2.4 9.65 2.57 9.61 2.81L9.25 5.35C8.66 5.59 8.12 5.92 7.63 6.29L5.24 5.33C5.02 5.25 4.77 5.33 4.65 5.55L2.74 8.87C2.62 9.08 2.66 9.34 2.86 9.48L4.89 11.06C4.84 11.36 4.8 11.69 4.8 12C4.8 12.31 4.82 12.64 4.87 12.94L2.84 14.52C2.66 14.66 2.61 14.93 2.72 15.13L4.64 18.45C4.76 18.67 5.01 18.74 5.23 18.67L7.62 17.71C8.12 18.09 8.65 18.41 9.24 18.65L9.6 21.19C9.65 21.43 9.84 21.6 10.08 21.6H13.92C14.16 21.6 14.36 21.43 14.39 21.19L14.75 18.65C15.34 18.41 15.88 18.09 16.37 17.71L18.76 18.67C18.98 18.75 19.23 18.67 19.35 18.45L21.27 15.13C21.39 14.91 21.34 14.66 21.15 14.52L19.14 12.94ZM12 15.6C10.02 15.6 8.4 13.98 8.4 12C8.4 10.02 10.02 8.4 12 8.4C13.98 8.4 15.6 10.02 15.6 12C15.6 13.98 13.98 15.6 12 15.6Z"
                            fill="currentColor" />
                    </svg>
                </div>
                <div class="stat-value"><?php echo $service_completed_month ?? 0; ?></div>
                <div class="stat-label">Service Completed</div>
                <div class="stat-change <?php echo ($completed_change_month ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo ($completed_change_month ?? 0) >= 0 ? '+' : ''; ?><?php echo $completed_change_month ?? 0; ?>%
                    from last month
                </div>
            </div>
            <div class="stat-card purple">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z"
                            fill="currentColor" />
                    </svg>
                </div>
                <div class="stat-value"><?php echo $new_customers_month ?? 0; ?></div>
                <div class="stat-label">New Customers</div>
                <div class="stat-change <?php echo ($customer_change_month ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo ($customer_change_month ?? 0) >= 0 ? '+' : ''; ?><?php echo $customer_change_month ?? 0; ?>%
                    from last month
                </div>
            </div>
        </div>
    </div>

    <div class="powerbi-section" style="margin: 30px 0;">
        <div class="section-header">
            <h2>Power BI Analytics</h2>
            <p class="subtitle">Advanced Business Intelligence</p>
        </div>
        <div class="powerbi-container"
            style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <iframe title="Sengkuclean" width="100%" height="600"
                src="https://app.powerbi.com/view?r=eyJrIjoiY2QxYzdlN2EtOTgwNC00ZWUzLWIxOGItNWIyMWU0NmE2MGRlIiwidCI6ImE2OWUxOWU4LWYwYTQtNGU3Ny1iZmY2LTk1NjRjODgxOWIxNCJ9"
                frameborder="0" allowFullScreen="true" style="border-radius: 8px;">
            </iframe>
        </div>
    </div>

    <!-- <div class="dashboard-grid">
        <div class="chart-card">
            <h2>Total Revenue</h2>
            <div class="chart-container">
                <canvas id="revenueChart"></canvas>
            </div>
            <div class="chart-legend">
                <span class="legend-label">Offline Sales</span>
            </div>
        </div> -->

    <!--         
        <div class="chart-card">
            <h2>Status Order Bulan Ini</h2>
            <div class="chart-container" style="height: 320px; position: relative;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div> -->

    <!--   
    <div class="table-card">
        <h2>Top Services</h2>
        <table class="services-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Popularity</th>
                    <th>Sales</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (isset($top_services) && count($top_services) > 0) {
                    $colors = ['red', 'orange', 'green', 'purple'];
                    $index = 1;
                    foreach ($top_services as $service) {
                        $color = $colors[($index - 1) % 4];
                        ?>
                        <tr>
                            <td><?php echo str_pad($index, 2, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($service['name']); ?><span class="service-type"><?php echo htmlspecialchars($service['type']); ?></span></td>
                            <td>
                                <div class="popularity-bar">
                                    <div class="progress-fill <?php echo $color; ?>" style="width: <?php echo $service['popularity']; ?>%"></div>
                                </div>
                            </td>
                            <td><span class="badge <?php echo $color; ?>"><?php echo $service['sales_percentage']; ?>%</span></td>
                        </tr>
                    <?php
                    $index++;
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 30px; color: #999;">Belum ada data layanan</td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div> -->
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
    // Revenue Chart
    const revenueData = <?php echo json_encode($revenue_data); ?>;
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: revenueData.map(d => d.day),
            datasets: [{
                label: 'Revenue',
                data: revenueData.map(d => d.amount),
                backgroundColor: '#0066CC',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Status Order Chart
    const statusData = <?php echo json_encode($status_data); ?>;
    new Chart(document.getElementById('statusChart'), {
        type: 'bar',
        data: {
            labels: statusData.map(s => s.status_name),
            datasets: [{
                label: 'Jumlah Order',
                data: statusData.map(s => s.total_orders),
                backgroundColor: ['#9C27B0', '#FF9800', '#F44336', '#4CAF50', '#2196F3', '#FFC107'],
                borderRadius: 8
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const idx = context.dataIndex;
                            const orders = statusData[idx].total_orders;
                            const items = statusData[idx].total_items;
                            return `${orders} order • ${items} item`;
                        }
                    },
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleFont: {
                        size: 13
                    },
                    bodyFont: {
                        size: 12
                    },
                    padding: 10,
                    cornerRadius: 6
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        display: false
                    }
                },
                y: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        padding: 10
                    }
                }
            },
            animation: {
                duration: 800
            }
        }
    });
</script>

<?php
include_once('../partials/footer.php');
?>