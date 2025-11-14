<?php
include('../partials/headerAdmin.php');
?>
<link rel="stylesheet" href="../css/analisis.css">

<?php
// KONEKSI DATABASE (sudah disediakan di db.php)
include('../db.php'); // Sesuaikan path jika perlu

// === HARI INI ===
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// === 1. TODAY'S SALES ===
$total_sales = $conn->query("
    SELECT COALESCE(SUM(di.price), 0) as total 
    FROM drop_items di
    JOIN drops d ON di.drop_id = d.id_drop
    JOIN payments p ON d.id_drop = p.drop_id
    WHERE DATE(d.trans_date) = '$today' 
      AND p.status = 'Lunas'
")->fetch_assoc()['total'] ?? 0;

$total_order = $conn->query("
    SELECT COUNT(*) as count 
    FROM drops d 
    JOIN payments p ON d.id_drop = p.drop_id 
    WHERE DATE(p.payment_date) = '$today' AND p.status = 'Lunas'
")->fetch_assoc()['count'] ?? 0;

$service_completed = $conn->query("
    SELECT COALESCE(SUM(di.quantity), 0) as total 
    FROM drop_items di 
    JOIN drops d ON di.drop_id = d.id_drop 
    WHERE DATE(d.trans_date) = '$today' 
      AND d.status_id = 4
")->fetch_assoc()['total'] ?? 0;

$new_customers = $conn->query("
    SELECT COUNT(*) as count 
    FROM customers 
    WHERE DATE(created_at) = '$today'
")->fetch_assoc()['count'];

// === PERUBAHAN DARI KEMARIN ===
$yesterday_sales = $conn->query("
    SELECT COALESCE(SUM(di.price), 0) as total 
    FROM drop_items di
    JOIN drops d ON di.drop_id = d.id_drop
    JOIN payments p ON d.id_drop = p.drop_id
    WHERE DATE(d.trans_date) = '$yesterday' 
      AND p.status = 'Lunas'
")->fetch_assoc()['total'] ?? 0;

$sales_change = $yesterday_sales > 0
    ? round((($total_sales - $yesterday_sales) / $yesterday_sales) * 100, 1)
    : ($total_sales > 0 ? 100 : 0);

$yesterday_orders = $conn->query("
    SELECT COUNT(*) FROM drops d 
    JOIN payments p ON d.id_drop = p.drop_id 
    WHERE DATE(p.payment_date) = '$yesterday' AND p.status = 'Lunas'
")->fetch_assoc()['count'] ?? 0;

$order_change = $yesterday_orders > 0
    ? round((($total_order - $yesterday_orders) / $yesterday_orders) * 100, 1)
    : ($total_order > 0 ? 100 : 0);

$yesterday_products = $conn->query("
    SELECT COALESCE(SUM(di.quantity), 0) as total 
    FROM drop_items di 
    JOIN drops d ON di.drop_id = d.id_drop 
    JOIN payments p ON d.id_drop = p.drop_id 
    WHERE DATE(p.payment_date) = '$yesterday' AND p.status = 'Lunas'
")->fetch_assoc()['total'] ?? 0;

$product_change = $yesterday_products > 0
    ? round((($service_completed - $yesterday_products) / $yesterday_products) * 100, 1)
    : ($service_completed > 0 ? 100 : 0);

$yesterday_customers = $conn->query("
    SELECT COUNT(DISTINCT c.id_customer) as count 
    FROM customers c 
    JOIN drops d ON c.id_customer = d.customer_id 
    JOIN payments p ON d.id_drop = p.drop_id 
    WHERE DATE(p.payment_date) = '$yesterday' 
      AND p.status = 'Lunas'
      AND DATE(c.created_at) = '$yesterday'
")->fetch_assoc()['count'] ?? 0;

$customer_change = $yesterday_customers > 0
    ? round((($new_customers - $yesterday_customers) / $yesterday_customers) * 100, 1)
    : ($new_customers > 0 ? 100 : 0);

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
        'amount' => (int)$amount
    ];
}

// === 3. STATUS ORDER REAL-TIME (DARI TABEL STATUSES) ===
$status_data = $conn->query("
    SELECT 
        s.id_status,
        s.status_name,
        s.status_code,
        COALESCE(COUNT(d.id_drop), 0) as total_orders,
        COALESCE(SUM(di.quantity), 0) as total_items
    FROM statuses s
    LEFT JOIN drops d ON s.id_status = d.status_id 
        AND DATE(d.trans_date) = '$today'
    LEFT JOIN drop_items di ON d.id_drop = di.drop_id
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
    <h1 class="page-title">Statistik</h1>

    <!-- Today's Sales Section -->
    <div class="sales-section">
        <div class="section-header">
            <h2>Today' Sales</h2>
            <p class="subtitle">Sales Summery</p>
        </div>

        <div class="stats-grid">
            <!-- Total Sales Card -->
            <div class="stat-card pink">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M20 7H4C2.9 7 2 7.9 2 9V19C2 20.1 2.9 21 4 21H20C21.1 21 22 20.1 22 19V9C22 7.9 21.1 7 20 7Z" fill="currentColor" />
                        <path d="M16 3H8C6.9 3 6 3.9 6 5V7H18V5C18 3.9 17.1 3 16 3Z" fill="currentColor" />
                    </svg>
                </div>
                <div class="stat-value">Rp. <?php echo number_format($total_sales ?? 0, 0, ',', '.'); ?></div>
                <div class="stat-label">Total Sales</div>
                <div class="stat-change <?php echo ($sales_change ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo ($sales_change ?? 0) >= 0 ? '+' : ''; ?><?php echo $sales_change ?? 0; ?>% from yesterday
                </div>
            </div>

            <!-- Total Order Card -->
            <div class="stat-card yellow">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3ZM9 17H7V10H9V17ZM13 17H11V7H13V17ZM17 17H15V13H17V17Z" fill="currentColor" />
                    </svg>
                </div>
                <div class="stat-value"><?php echo $total_order ?? 0; ?></div>
                <div class="stat-label">Total Order</div>
                <div class="stat-change <?php echo ($order_change ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo ($order_change ?? 0) >= 0 ? '+' : ''; ?><?php echo $order_change ?? 0; ?>% from yesterday
                </div>
            </div>

            <!-- Service Completed -->
            <div class="stat-card green">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M19.14 12.94C19.18 12.64 19.2 12.33 19.2 12C19.2 11.68 19.18 11.36 19.13 11.06L21.16 9.48C21.34 9.34 21.39 9.07 21.28 8.87L19.36 5.55C19.24 5.33 18.99 5.26 18.77 5.33L16.38 6.29C15.88 5.91 15.35 5.59 14.76 5.35L14.4 2.81C14.36 2.57 14.16 2.4 13.92 2.4H10.08C9.84 2.4 9.65 2.57 9.61 2.81L9.25 5.35C8.66 5.59 8.12 5.92 7.63 6.29L5.24 5.33C5.02 5.25 4.77 5.33 4.65 5.55L2.74 8.87C2.62 9.08 2.66 9.34 2.86 9.48L4.89 11.06C4.84 11.36 4.8 11.69 4.8 12C4.8 12.31 4.82 12.64 4.87 12.94L2.84 14.52C2.66 14.66 2.61 14.93 2.72 15.13L4.64 18.45C4.76 18.67 5.01 18.74 5.23 18.67L7.62 17.71C8.12 18.09 8.65 18.41 9.24 18.65L9.6 21.19C9.65 21.43 9.84 21.6 10.08 21.6H13.92C14.16 21.6 14.36 21.43 14.39 21.19L14.75 18.65C15.34 18.41 15.88 18.09 16.37 17.71L18.76 18.67C18.98 18.75 19.23 18.67 19.35 18.45L21.27 15.13C21.39 14.91 21.34 14.66 21.15 14.52L19.14 12.94ZM12 15.6C10.02 15.6 8.4 13.98 8.4 12C8.4 10.02 10.02 8.4 12 8.4C13.98 8.4 15.6 10.02 15.6 12C15.6 13.98 13.98 15.6 12 15.6Z" fill="currentColor" />
                    </svg>
                </div>
                <div class="stat-value"><?php echo $service_completed ?? 0; ?></div>
                <div class="stat-label">Service Completed</div>
                <div class="stat-change <?php echo ($product_change ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo ($product_change ?? 0) >= 0 ? '+' : ''; ?><?php echo $product_change ?? 0; ?>% from yesterday
                </div>
            </div>

            <!-- New Customers Card -->
            <div class="stat-card purple">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z" fill="currentColor" />
                    </svg>
                </div>
                <div class="stat-value"><?php echo $new_customers ?? 0; ?></div>
                <div class="stat-label">New Customers</div>
                <div class="stat-change <?php echo ($customer_change ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo ($customer_change ?? 0) >= 0 ? '+' : ''; ?><?php echo $customer_change ?? 0; ?>% from yesterday
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Total Revenue Chart -->
        <div class="chart-card">
            <h2>Total Revenue</h2>
            <div class="chart-container">
                <canvas id="revenueChart"></canvas>
            </div>
            <div class="chart-legend">
                <span class="legend-label">Offline Sales</span>
            </div>
        </div>

        <!-- STATUS ORDER REAL-TIME (RAPI & TANPA TEKS DUPLIKAT) -->
        <div class="chart-card">
            <h2>Status Order Hari Ini</h2>
            <div class="chart-container" style="height: 320px; position: relative;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Services Table -->
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
    </div>
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
                        label: function(context) {
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