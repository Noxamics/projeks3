<?php 
include('../partials/headerAdmin.php'); 
?>
<link rel="stylesheet" href="../css/analisis.css">

<?php
// TODO: Koneksi database dan query data
// include('../config/database.php');

// TODO: Query untuk Today's Sales
// $total_sales = 0;
// $total_order = 0;
// $product_sold = 0;
// $new_customers = 0;
// $sales_change = 0;
// $order_change = 0;
// $product_change = 0;
// $customer_change = 0;

// TODO: Query untuk Revenue per hari
// $revenue_data = [];

// TODO: Query untuk Target vs Reality per bulan
// $target_data = [];
// $reality_data = [];

// TODO: Query untuk Top Services
// $top_services = [];
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
                        <path d="M20 7H4C2.9 7 2 7.9 2 9V19C2 20.1 2.9 21 4 21H20C21.1 21 22 20.1 22 19V9C22 7.9 21.1 7 20 7Z" fill="currentColor"/>
                        <path d="M16 3H8C6.9 3 6 3.9 6 5V7H18V5C18 3.9 17.1 3 16 3Z" fill="currentColor"/>
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
                        <path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3ZM9 17H7V10H9V17ZM13 17H11V7H13V17ZM17 17H15V13H17V17Z" fill="currentColor"/>
                    </svg>
                </div>
                <div class="stat-value"><?php echo $total_order ?? 0; ?></div>
                <div class="stat-label">Total Order</div>
                <div class="stat-change <?php echo ($order_change ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo ($order_change ?? 0) >= 0 ? '+' : ''; ?><?php echo $order_change ?? 0; ?>% from yesterday
                </div>
            </div>
            
            <!-- Product Sold Card -->
            <div class="stat-card green">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M19.14 12.94C19.18 12.64 19.2 12.33 19.2 12C19.2 11.68 19.18 11.36 19.13 11.06L21.16 9.48C21.34 9.34 21.39 9.07 21.28 8.87L19.36 5.55C19.24 5.33 18.99 5.26 18.77 5.33L16.38 6.29C15.88 5.91 15.35 5.59 14.76 5.35L14.4 2.81C14.36 2.57 14.16 2.4 13.92 2.4H10.08C9.84 2.4 9.65 2.57 9.61 2.81L9.25 5.35C8.66 5.59 8.12 5.92 7.63 6.29L5.24 5.33C5.02 5.25 4.77 5.33 4.65 5.55L2.74 8.87C2.62 9.08 2.66 9.34 2.86 9.48L4.89 11.06C4.84 11.36 4.8 11.69 4.8 12C4.8 12.31 4.82 12.64 4.87 12.94L2.84 14.52C2.66 14.66 2.61 14.93 2.72 15.13L4.64 18.45C4.76 18.67 5.01 18.74 5.23 18.67L7.62 17.71C8.12 18.09 8.65 18.41 9.24 18.65L9.6 21.19C9.65 21.43 9.84 21.6 10.08 21.6H13.92C14.16 21.6 14.36 21.43 14.39 21.19L14.75 18.65C15.34 18.41 15.88 18.09 16.37 17.71L18.76 18.67C18.98 18.75 19.23 18.67 19.35 18.45L21.27 15.13C21.39 14.91 21.34 14.66 21.15 14.52L19.14 12.94ZM12 15.6C10.02 15.6 8.4 13.98 8.4 12C8.4 10.02 10.02 8.4 12 8.4C13.98 8.4 15.6 10.02 15.6 12C15.6 13.98 13.98 15.6 12 15.6Z" fill="currentColor"/>
                    </svg>
                </div>
                <div class="stat-value"><?php echo $product_sold ?? 0; ?></div>
                <div class="stat-label">Product Sold</div>
                <div class="stat-change <?php echo ($product_change ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo ($product_change ?? 0) >= 0 ? '+' : ''; ?><?php echo $product_change ?? 0; ?>% from yesterday
                </div>
            </div>
            
            <!-- New Customers Card -->
            <div class="stat-card purple">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z" fill="currentColor"/>
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
        
        <!-- Target vs Reality Chart -->
        <div class="chart-card">
            <h2>Target vs Reality</h2>
            <div class="chart-container">
                <canvas id="targetChart"></canvas>
            </div>
            <div class="chart-legends">
                <div class="legend-item">
                    <span class="legend-color green"></span>
                    <span class="legend-text">Reality Sales</span>
                    <span class="legend-value"><?php echo number_format($total_reality_sales ?? 0, 0, ',', '.'); ?></span>
                </div>
                <div class="legend-item">
                    <span class="legend-color yellow"></span>
                    <span class="legend-text">Target Sales</span>
                    <span class="legend-value"><?php echo number_format($total_target_sales ?? 0, 0, ',', '.'); ?></span>
                </div>
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
// Data dari PHP untuk Revenue Chart
const revenueData = <?php echo json_encode($revenue_data ?? []); ?>;
const revenueLabels = revenueData.map(item => item.day) || ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
const revenueValues = revenueData.map(item => item.amount) || [0, 0, 0, 0, 0, 0, 0];

// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'bar',
    data: {
        labels: revenueLabels,
        datasets: [{
            label: 'Offline Sales',
            data: revenueValues,
            backgroundColor: '#0066CC',
            borderRadius: 4,
            barThickness: 30
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + (value/1000) + 'k';
                    }
                },
                grid: {
                    borderDash: [5, 5]
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Data dari PHP untuk Target vs Reality Chart
const targetData = <?php echo json_encode($target_data ?? []); ?>;
const targetLabels = targetData.map(item => item.month) || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'July'];
const realityValues = targetData.map(item => item.reality) || [0, 0, 0, 0, 0, 0, 0];
const targetValues = targetData.map(item => item.target) || [0, 0, 0, 0, 0, 0, 0];

// Target vs Reality Chart
const targetCtx = document.getElementById('targetChart').getContext('2d');
new Chart(targetCtx, {
    type: 'bar',
    data: {
        labels: targetLabels,
        datasets: [
            {
                label: 'Reality Sales',
                data: realityValues,
                backgroundColor: '#4CAF50',
                borderRadius: 4,
                barThickness: 20
            },
            {
                label: 'Target Sales',
                data: targetValues,
                backgroundColor: '#FFC107',
                borderRadius: 4,
                barThickness: 20
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    borderDash: [5, 5]
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});
</script>

<?php
include_once('../partials/footer.php');
?>