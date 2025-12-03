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
    <script src="../js/customer.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        <!-- PROGRES PENGERJAAN SEPATU — VERSI BERSIH (TANPA DESCRIPTION) -->
        <div class="progress-section">
            <h2>Progres Pengerjaan Sepatu Anda</h2>

            <?php
            $progress_query = "
                SELECT 
                    di.id_item,
                    d.order_code,
                    di.brand,
                    di.quantity,
                    st.status_name,
                    st.status_order,
                    DATE(d.trans_date) AS tanggal_masuk,
                    dd.deadline_date
                FROM drop_items di
                JOIN drops d ON di.drop_id = d.id_drop
                JOIN statuses st ON d.status_id = st.id_status
                LEFT JOIN deadlines dd ON d.id_drop = dd.drop_id
                WHERE d.customer_id = ?
                ORDER BY d.trans_date DESC, st.status_order ASC
            ";
            $stmt = $conn->prepare($progress_query);
            $stmt->bind_param("i", $customerId);
            $stmt->execute();
            $progress_result = $stmt->get_result();
            ?>

            <?php if ($progress_result->num_rows > 0): ?>
                <div class="progress-grid">
                    <?php while ($shoe = $progress_result->fetch_assoc()):
                        $progressPercent = round(($shoe['status_order'] / 6) * 100);
                    ?>
                        <div class="progress-card">
                            <div class="progress-header">
                                <div>
                                    <strong><?php echo htmlspecialchars($shoe['brand'] ?: 'Sepatu'); ?></strong>
                                    <?php if ($shoe['quantity'] > 1): ?>
                                        <small> • <?php echo $shoe['quantity']; ?> pasang</small>
                                    <?php endif; ?>
                                </div>
                                <div class="order-code">#<?php echo htmlspecialchars($shoe['order_code']); ?></div>
                            </div>

                            <!-- STATUS SAAT INI — HANYA NAMA STATUS, LEBIH BERSIH -->
                            <div style="
                                margin: 18px 0 10px;
                                padding: 12px 16px;
                                background: #e8f5e8;
                                border-left: 5px solid #45a049;
                                border-radius: 10px;
                                font-weight: 600;
                                color: #45a049;
                                font-size: 16px;
                            ">
                                <?php echo htmlspecialchars($shoe['status_name']); ?>
                            </div>

                            <!-- PROGRESS BAR -->
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" style="width: <?php echo $progressPercent; ?>%; background: #45a049;"></div>
                            </div>

                            <div class="progress-percentage" style="color: #1a438a; font-weight: 700;">
                                <?php echo $progressPercent; ?>% Selesai
                            </div>

                            <!-- LANGKAH BULAT -->
                            <div class="progress-steps">
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <div class="step <?php echo $shoe['status_order'] >= $i ? 'active' : ''; ?>"
                                        style="background: <?php echo $shoe['status_order'] >= $i ? '#45a049' : '#e0e0e0'; ?>;">
                                        <?php echo $i; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>

                            <div class="progress-status">
                                <small style="color:#666; font-size:13px; display:block; margin-top:10px;">
                                    Masuk: <?php echo date('d/m/Y', strtotime($shoe['tanggal_masuk'])); ?>
                                    <?php if ($shoe['deadline_date']): ?>
                                        • Estimasi selesai: <?php echo date('d/m/Y', strtotime($shoe['deadline_date'])); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>Belum ada sepatu dalam proses pengerjaan</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- <== Testimoni Page Customer ==> -->

        <?php if (isset($_GET['testimonial_success'])): ?>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        title: "Terima Kasih!",
                        text: "Telah menggunakan layanan kami dan mengirimkan testimoni 😊",
                        icon: "success",
                        confirmButtonColor: "#154283",
                        confirmButtonText: "OK"
                    });
                });
            </script>
        <?php endif; ?>


        <div class="testimonial-section">

            <h2 class="testimonial-title">Berikan Testimoni Anda</h2>

            <form action="submit_testimonial.php" method="POST" class="testimonial-form">
                <input type="hidden" name="customer_id" value="<?= $customerId ?>">

                <label>Rating:</label>

                <div class="star-rating">
                    <input type="hidden" name="rating" id="rating-value" required>
                    <span class="star" data-value="1">★</span>
                    <span class="star" data-value="2">★</span>
                    <span class="star" data-value="3">★</span>
                    <span class="star" data-value="4">★</span>
                    <span class="star" data-value="5">★</span>
                </div>

                <label for="testimonial">Testimoni:</label>
                <textarea name="testimonial" id="testimonial" rows="4" placeholder="Write Your Experience..." required></textarea>

                <button type="submit" class="testi-btn">Kirim Testimoni</button>
            </form>

        </div>

        <form id="wa-form">
            <input type="text" id="name" placeholder="Full Name" required />
            <input type="email" id="email" placeholder="Email" required />
            <textarea id="message" placeholder="Comment or message" required></textarea>

            <button type="submit" class="wa-button">
                <img src="../a/assets/WA Putih.png" width="30">
            </button>
        </form>

        <div id="loading" style="display:none; margin-top:10px; font-weight:bold;">
            Loading...
        </div>

        <script src="../js/customer/submit-whatsapp.js"></script>


        <div class="info-box">
            <h3>ℹ️ Informasi Login</h3>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($userData['email']); ?></p>
            <p><strong>Password:</strong> Gunakan Order Code Anda untuk login</p>
            <p class="note">💡 Simpan Order Code Anda dengan baik untuk login di masa mendatang!</p>
        </div>
    </main>
</body>

</html>