<?php
require_once '../db.php';
require_once 'check_auth.php';

$userData = getUserData();
$customerId = $userData['id'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Customer | SengkuClean</title>
    <link rel="icon" type="image/png" href="../public/img/logo.png">
    <link rel="stylesheet" href="../css/cs_dashboard.css">
</head>

<body>
    <header class="header">
        <div class="header-logo">
            <img src="../a/img/logo.png" alt="SengkuClean">
            <h2>Customer Portal</h2>
        </div>
        <nav class="header-nav">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="../public/index.php" class="nav-link">Home</a>
            <a href="../admin/logout.php" class="nav-link btn-logout">Logout</a>
        </nav>
    </header>

    <main class="container">
        <div class="welcome-section">
            <h1>Selamat Datang, <?php echo htmlspecialchars($userData['name']); ?>! 👋</h1>
            <p><strong>Email:</strong> <?= htmlspecialchars($userData['email'] ?? 'Belum terdaftar'); ?></p>
            <p><strong>Telepon:</strong> <?php echo htmlspecialchars($userData['phone']); ?></p>
            <span class="user-type">👤 Customer</span>
        </div>

        <div class="stats-grid">
            <?php
            // Query statistik customer
            $totalOrders = $conn->query("SELECT COUNT(*) as total FROM drops WHERE customer_id = $customerId")->fetch_assoc()['total'];
            $activeOrders = $conn->query("SELECT COUNT(*) as total FROM drops WHERE customer_id = $customerId AND status_id IN (1,2,3)")->fetch_assoc()['total'];
            $completedOrders = $conn->query("SELECT COUNT(*) as total FROM drops WHERE customer_id = $customerId AND status_id = 4")->fetch_assoc()['total'];

            // Total pembayaran
            $totalPayment = $conn->query("
                SELECT SUM(p.amount_paid) as total 
                FROM payments p
                INNER JOIN drops d ON p.drop_id = d.id_drop
                WHERE d.customer_id = $customerId
            ")->fetch_assoc()['total'] ?? 0;
            ?>

            <div class="stat-card">
                <h3>Total Pesanan</h3>
                <div class="stat-number"><?php echo number_format($totalOrders); ?></div>
            </div>

            <div class="stat-card">
                <h3>Pesanan Aktif</h3>
                <div class="stat-number"><?php echo number_format($activeOrders); ?></div>
            </div>

            <div class="stat-card">
                <h3>Pesanan Selesai</h3>
                <div class="stat-number"><?php echo number_format($completedOrders); ?></div>
            </div>

            <div class="stat-card">
                <h3>Total Pembayaran</h3>
                <div class="stat-number">Rp <?php echo number_format($totalPayment, 0, ',', '.'); ?></div>
            </div>
        </div>

        <div class="recent-section">
            <h2>📦 Pesanan Saya</h2>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Order Code</th>
                            <th>Service</th>
                            <th>Brand</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Deadline</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "
                            SELECT d.order_code, s.service_name, d.brand, 
                                   d.trans_date, st.status_name, dd.deadline_date
                            FROM drops d
                            LEFT JOIN services s ON d.service_id = s.id_service
                            LEFT JOIN statuses st ON d.status_id = st.id_status
                            LEFT JOIN deadlines dd ON d.id_drop = dd.drop_id
                            WHERE d.customer_id = $customerId
                            ORDER BY d.trans_date DESC
                            LIMIT 10
                        ";
                        $result = $conn->query($query);

                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td><strong>" . htmlspecialchars($row['order_code']) . "</strong></td>";
                                echo "<td>" . htmlspecialchars($row['service_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['brand']) . "</td>";
                                echo "<td>" . date('d/m/Y', strtotime($row['trans_date'])) . "</td>";
                                echo "<td><span class='status-badge'>" . htmlspecialchars($row['status_name']) . "</span></td>";
                                echo "<td>" . ($row['deadline_date'] ? date('d/m/Y', strtotime($row['deadline_date'])) : '-') . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding: 30px; color: #999;'>Belum ada pesanan</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="info-box">
            <h3>ℹ️ Informasi Login</h3>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($userData['email']); ?></p>
            <p><strong>Password:</strong> Gunakan Order Code Anda untuk login</p>
            <p class="note">💡 Simpan Order Code Anda dengan baik untuk login di masa mendatang!</p>
        </div>
    </main>
</body>

</html>