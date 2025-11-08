<?php
include('../partials/headerAdmin.php');
include('../db.php'); // koneksi database
?>
<link rel="stylesheet" href="../css/drop.css">

<main class="drop-page">
    <div class="drop-container">
        <h1 class="title">Drop</h1>

        <!-- TOP BAR -->
        <div class="top-bar">

            <!-- KIRI: Tambah + Search -->
            <!-- TOP BAR -->
            <div class="left-bar">
                <button class="add-btn" id="openAddModal">Tambah Barang</button>

                <form method="GET" action="" class="search-box">
                    <input type="text" name="search" placeholder="Cari data..."
                        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    <button type="submit" class="search-btn">
                        <img src="../a/assets/pencarian.png" alt="Cari" />
                    </button>
                </form>
            </div>

            <!-- KANAN: Sorting + Hapus Terpilih -->
            <div class="right-tools">

                <form method="GET" id="filterForm">
                    <select id="sort" name="sort" class="filter-select"
                        onchange="document.getElementById('filterForm').submit()">
                        <option value="">-- Pilih --</option>
                        <option value="nama_asc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'nama_asc') ? 'selected' : '' ?>>Nama (A-Z)</option>
                        <option value="nama_desc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'nama_desc') ? 'selected' : '' ?>>Nama (Z-A)</option>
                        <option value="tanggal_desc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'tanggal_desc') ? 'selected' : '' ?>>Tanggal Terlama</option>
                        <option value="tanggal_asc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'tanggal_asc') ? 'selected' : '' ?>>Tanggal Terbaru</option>
                    </select>
                </form>

                <button id="deleteSelected" class="delete-selected">
                    Hapus Terpilih
                </button>
            </div>
        </div>

        <!-- POPUP FORM TAMBAH BARANG -->
        <div class="modal" id="addModal">
            <div class="modal-content large">
                <span class="close">&times;</span>
                <h2>Tambah Barang</h2>

                <form method="POST" action="drop_add.php" class="grid-form">
                    <!-- Baris 1 -->
                    <div>
                        <label>Nama Pelanggan</label>
                        <input type="text" id="customer_name" name="customer_name" required>
                    </div>
                    <div>
                        <label>No. Handphone</label>
                        <input type="text" id="customer_phone" name="phone_number" required>
                    </div>

                    <!-- Baris 2 -->
                    <div>
                        <label>Brand / Merk</label>
                        <input type="text" name="brand" required>
                    </div>

                    <div>
                        <label for="service_id">Layanan</label>
                        <select name="service_id" id="service_id" required>
                            <option value="">-- Pilih Layanan --</option>
                            <?php
                            $order = "FIELD(category, 'cleaning', 'reglue', 'repaint', 'bag', 'cap'), service_name";
                            $query = "SELECT id_service, category, service_name FROM services ORDER BY $order";
                            $result = mysqli_query($conn, $query);
                            while ($row = mysqli_fetch_assoc($result)) {
                                $displayName = ucfirst($row['category']) . " - " . ucfirst($row['service_name']);
                                echo "<option value='{$row['id_service']}'>{$displayName}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Baris 3 -->
                    <div>
                        <label>Tgl. Transaksi</label>
                        <input type="date" id="tanggal_masuk" name="tanggal_masuk">
                    </div>
                    <div>
                        <label>Harga</label>
                        <input type="text" id="price_display" name="price_display" style="background:#f9f9f9;">
                        <input type="hidden" name="price_min" id="price_min">
                        <input type="hidden" name="price_max" id="price_max">
                    </div>

                    <!-- Baris 4 -->
                    <div>
                        <label>Estimasi Selesai</label>
                        <input type="text" id="estimate_desc" name="estimate_desc" placeholder="Contoh: 2 Hari">
                    </div>
                    <div>
                        <label for="tanggal_selesai">Tanggal Estimasi Selesai</label>
                        <input type="date" id="tanggal_selesai" name="tanggal_selesai" readonly
                            style="background:#f9f9f9;">
                    </div>

                    <!-- Baris 5 -->
                    <div>
                        <label>Status</label>
                        <select name="status_id" id="statusSelect" required>
                            <option value="">Pilih Status</option>
                            <?php
                            $st = $conn->query("SELECT * FROM statuses ORDER BY id_status ASC");
                            while ($s = $st->fetch_assoc()) {
                                echo "<option value='{$s['id_status']}'>{$s['status_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label>Status Pembayaran</label>
                        <select name="payment_status">
                            <option value="Belum Lunas">Belum Lunas</option>
                            <option value="Lunas">Lunas</option>
                        </select>
                    </div>

                    <!-- Baris 6 -->
                    <div>
                        <label>Tanggal Pembayaran</label>
                        <input type="date" name="payment_date">
                    </div>

                    <select name="payment_method">
                        <option value="">-- Pilih Metode --</option>
                        <option value="Tunai">Tunai</option>
                        <option value="Transfer">Transfer</option>
                    </select>

                    <div class="full-width">
                        <button type="submit" class="save-btn">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TABEL DATA -->
        <div class="table-container">
            <table id="dropTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>ID</th>
                        <th>Customer Name</th>
                        <th>Brand/Merk</th>
                        <th>Layanan</th>
                        <th>Tgl. Transaksi</th>
                        <th>Estimasi Selesai</th>
                        <th>Proses</th>
                        <th>Pembayaran</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
                    $sort = isset($_GET['sort']) ? $_GET['sort'] : '';
                    $sql = "
SELECT 
    d.*, 
    c.name, 
    c.phone,
    s.service_name, 
    s.category,
    st.status_name,
    p.payment_method,
    p.payment_date,
    p.amount_paid,
    p.status AS pay_status
FROM drops d
JOIN customers c ON d.customer_id = c.id_customer
JOIN services s ON d.service_id = s.id_service
LEFT JOIN statuses st ON d.status_id = st.id_status
LEFT JOIN payments p ON d.id_drop = p.drop_id
WHERE 
    c.name LIKE '%$search%' OR
    d.brand LIKE '%$search%' OR
    s.service_name LIKE '%$search%' OR
    s.category LIKE '%$search%' OR
    st.status_name LIKE '%$search%'
";

                    switch ($sort) {
                        case 'nama_asc':
                            $sql .= " ORDER BY c.name ASC";
                            break;
                        case 'nama_desc':
                            $sql .= " ORDER BY c.name DESC";
                            break;
                        case 'tanggal_asc':
                            $sql .= " ORDER BY d.trans_date ASC";
                            break;
                        case 'tanggal_desc':
                            $sql .= " ORDER BY d.trans_date DESC";
                            break;
                        default:
                            $sql .= " ORDER BY d.trans_date DESC";
                    }


                    $result = $conn->query($sql);
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr class='data-row' 
            data-id='{$row['id_drop']}'
            data-customer_id='{$row['customer_id']}'
            data-name=\"" . htmlspecialchars($row['name'], ENT_QUOTES) . "\" 
            data-phone=\"" . htmlspecialchars($row['phone'], ENT_QUOTES) . "\" 
            data-brand=\"" . htmlspecialchars($row['brand'], ENT_QUOTES) . "\" 
            data-service_id='{$row['service_id']}'
            data-trans_date='{$row['trans_date']}'
            data-est_finish='{$row['est_finish_date']}'
            data-status_id='{$row['status_id']}'
            data-payment_status='" . htmlspecialchars($row['pay_status'] ?? '-', ENT_QUOTES) . "'
            data-payment_date='" . htmlspecialchars($row['payment_date'] ?? '', ENT_QUOTES) . "'
            data-payment_method='" . htmlspecialchars($row['payment_method'] ?? '', ENT_QUOTES) . "'>";

                        echo "<td><input type='checkbox' class='row-checkbox' value='{$row['id_drop']}'></td>";
                        echo "<td>" . htmlspecialchars($row['order_code']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['brand']) . "</td>";
                        echo "<td><span class='service-text'><span class='light'>" . ucfirst(htmlspecialchars($row['category'])) . "</span> <span class='bold'>" . htmlspecialchars($row['service_name']) . "</span></span></td>";
                        echo "<td>" . date('d F Y', strtotime($row['trans_date'])) . "</td>";
                        echo "<td>" . date('d F Y', strtotime($row['est_finish_date'])) . "</td>";
                        echo "<td>" . htmlspecialchars($row['status_name'] ?? '-') . "</td>";
                        echo "<td>" . htmlspecialchars($row['pay_status'] ?? '-') . "</td>";
                        echo "</tr>";
                    }

                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL NOTIFIKASI BERHASIL -->
    <div id="successModal" class="modal">
        <div class="modal-content" style="max-width: 400px; text-align: center;">
            <p id="successMessage" style="font-size: 16px; font-weight: 600; color: #004d9d;">Data berhasil disimpan
            </p>
            <button id="closeSuccess" class="save-btn" style="width:auto; margin-top:15px;">OK</button>
        </div>
    </div>

    <div id="dropChoiceModal" class="modal">
        <div class="modal-content small">
            <h3>Drop Barang Baru / Pernah Drop Barang?</h3>
            <div class="modal-actions">
                <button id="btnDropBaru" class="confirm">Drop Baru</button>
                <button id="btnDropLama" class="secondary">Drop Lama</button>
            </div>
        </div>
    </div>

    <!-- Modal Cari Customer -->
    <div id="searchCustomerModal" class="modal">
        <div class="modal-content">
            <span class="close-search">&times;</span>
            <h2>Cari Data Customer</h2>

            <input type="text" id="searchCustomerInput" placeholder="Cari nama / no HP customer..." />

            <div id="customerResults" class="customer-results">
                <!-- Hasil pencarian customer akan tampil di sini -->
            </div>

            <button id="saveCustomer" class="save-btn" disabled>Simpan</button>
        </div>
    </div>
</main>

<?php include_once "../partials/footer.php"; ?>
<script src="../js/drop.js"></script>