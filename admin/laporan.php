<?php // <-- TIDAK ADA SPASI SEBELUM INI

// 1. Require db.php dulu (ini akan start session)
require_once '../db.php';

// 2. Baru require check_auth
// require_once 'check_auth.php';

// // 3. Ambil data admin
// $adminData = getAdminData();
// $adminName = $adminData['name'];
// $adminEmail = $adminData['email'];
// $adminId = $adminData['id_admin'];

// 4. Baru include header (yang mungkin ada output HTML)
include('../partials/headerAdmin.php');
?>

<?php
// 📹 Query data laporan - UPDATED SESUAI STRUKTUR DB
$query = "
SELECT 
    d.id_drop,
    d.order_code AS kode_order,
    ANY_VALUE(c.name) AS customer_name,
    ANY_VALUE(d.brand) AS brand,
    ANY_VALUE(s.category) AS kategori,
    ANY_VALUE(s.service_name) AS layanan,
    ANY_VALUE(d.trans_date) AS tgl_transaksi,
    ANY_VALUE(d.est_finish_date) AS estimasi_selesai,
    ANY_VALUE(d.actual_finish_date) AS tanggal_selesai,
    ANY_VALUE(st.status_name) AS status_proses,
    ANY_VALUE(p.status) AS status_pembayaran,
    ANY_VALUE(e.name) AS karyawan,
    COALESCE(SUM(di.price * di.quantity), 0) AS total_harga
FROM drops d
JOIN customers c ON d.customer_id = c.id_customer
LEFT JOIN services s ON d.service_id = s.id_service
LEFT JOIN drop_items di ON d.id_drop = di.drop_id
LEFT JOIN payments p ON d.id_drop = p.drop_id

-- ✅ Ambil status dari tabel deadlines
LEFT JOIN deadlines dl ON d.id_drop = dl.drop_id
LEFT JOIN statuses st ON dl.status_id = st.id_status

-- ✅ Ambil karyawan dari tabel employees
LEFT JOIN employees e ON d.employee_id = e.id_employee

GROUP BY d.id_drop
ORDER BY d.trans_date ASC
";
$result = mysqli_query($conn, $query);

// 📹 Ambil daftar kategori layanan unik
$kategoriQuery = "SELECT DISTINCT category FROM services ORDER BY category ASC";
$kategoriResult = mysqli_query($conn, $kategoriQuery);
?>

<link rel="stylesheet" href="../css/laporan.css?v=<?php echo time(); ?>">

<main class="report-page">
    <div class="report-container">
        <h1>Laporan Transaksi</h1>

        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Cari data..." />

            <select id="sortBy">
                <option value="">-- Sort By --</option>
                <optgroup label="Waktu">
                    <option value="minggu">Minggu Ini</option>
                    <option value="bulan">Bulan Ini</option>
                    <option value="tahun">Tahun Ini</option>
                </optgroup>
                <optgroup label="Status Pembayaran">
                    <option value="lunas">Lunas</option>
                    <option value="belum">Belum Lunas</option>
                </optgroup>
                <optgroup label="Kategori Layanan">
                    <?php while ($kat = mysqli_fetch_assoc($kategoriResult)): ?>
                        <option value="<?= htmlspecialchars(strtolower($kat['category'])) ?>">
                            <?= htmlspecialchars(ucwords($kat['category'])) ?>
                        </option>
                    <?php endwhile; ?>
                </optgroup>
            </select>

            <select id="jenisTotal">
                <option value="lunas">Total Lunas Saja</option>
                <option value="semua">Total Semua Transaksi</option>
            </select>

            <button id="exportExcel" class="btn-green">Export Excel</button>
            <button id="exportPDF" class="btn-red">Export PDF</button>
        </div>

        <div id="alertBox" class="alert-box">
            <span id="alertText"></span>
            <div id="progressBar" class="progress-bar"></div>
        </div>

        <div class="table-wrapper">
            <table id="laporanTable">
                <thead>
                    <tr>
                        <th>Kode Order</th>
                        <th>Customer</th>
                        <th>Brand</th>
                        <th>Kategori</th>
                        <th>Layanan</th>
                        <th>Tgl Transaksi</th>
                        <th>Estimasi Selesai</th>
                        <th>Selesai</th>
                        <th>Status Proses</th>
                        <th>Pembayaran</th>
                        <th>Karyawan</th>
                        <th>Harga</th>
                    </tr>
                </thead>

                <!-- Ganti bagian table body di laporan.php -->
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($row['kode_order']); ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['customer_name']); ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['brand']); ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['kategori'] ?? '-'); ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['layanan'] ?? '-'); ?>
                            </td>
                            <td>
                                <?= $row['tgl_transaksi'] ? date('d F Y', strtotime($row['tgl_transaksi'])) : '-'; ?>
                            </td>
                            <td>
                                <?= $row['estimasi_selesai'] ? date('d F Y', strtotime($row['estimasi_selesai'])) : '-'; ?>
                            </td>
                            <td>
                                <?= $row['tanggal_selesai'] ? date('d F Y', strtotime($row['tanggal_selesai'])) : '-'; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['status_proses'] ?? '-'); ?>
                            </td>
                            <td
                                class="<?= strtolower($row['status_pembayaran'] ?? 'belum') == 'lunas' ? 'text-green' : 'text-red'; ?>">
                                <?= htmlspecialchars($row['status_pembayaran'] ?? 'Belum Lunas'); ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['karyawan'] ?? '-'); ?>
                            </td>
                            <td data-harga="<?= $row['total_harga'] ?>">Rp
                                <?= number_format($row['total_harga'], 0, ',', '.'); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="11" style="text-align:right; font-weight:bold; color:#0b3d91;">
                            Total Pendapatan:
                        </td>
                        <td id="totalPendapatan" style="font-weight:bold; color:#0b3d91;">Rp 0</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</main>

<script>
    function hitungTotalPendapatan() {
        const jenisTotal = document.getElementById("jenisTotal").value;
        let total = 0;
        document.querySelectorAll("#laporanTable tbody tr").forEach(row => {
            const visible = row.style.display !== "none";
            const status = row.cells[9].textContent.trim().toLowerCase();
            const harga = parseInt(row.cells[11].dataset.harga || 0);
            if (visible) {
                if (jenisTotal === "semua" || (jenisTotal === "lunas" && status === "lunas")) {
                    total += harga;
                }
            }
        });
        document.getElementById("totalPendapatan").textContent = "Rp " + total.toLocaleString("id-ID");
    }

    function showAlert(message, duration = 6000) {
        const alertBox = document.getElementById("alertBox");
        const alertText = document.getElementById("alertText");
        const progressBar = document.getElementById("progressBar");

        alertText.textContent = message;
        alertBox.classList.add("show");
        progressBar.style.width = "0";

        setTimeout(() => {
            alertBox.classList.add("fade-out");
            setTimeout(() => alertBox.classList.remove("show", "fade-out"), 1000);
        }, duration);
    }

    function showExportAlert(message, duration = 4000) {
        const alertBox = document.getElementById("alertBox");
        const alertText = document.getElementById("alertText");
        const progressBar = document.getElementById("progressBar");

        alertText.textContent = message;
        alertBox.classList.add("show");
        progressBar.style.transition = "none";
        progressBar.style.width = "0";

        setTimeout(() => {
            progressBar.style.transition = `width ${duration / 1000}s linear`;
            progressBar.style.width = "100%";
        }, 100);

        setTimeout(() => {
            alertBox.classList.add("fade-out");
            setTimeout(() => alertBox.classList.remove("show", "fade-out"), 1000);
        }, duration);
    }

    document.getElementById("searchInput").addEventListener("keyup", function () {
        const value = this.value.toLowerCase();
        document.querySelectorAll("#laporanTable tbody tr").forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(value) ? "" : "none";
        });
        hitungTotalPendapatan();
    });

    document.getElementById("sortBy").addEventListener("change", function () {
        const value = this.value.toLowerCase();
        const rows = document.querySelectorAll("#laporanTable tbody tr");
        const now = new Date();
        let alertMsg = "";

        rows.forEach(row => {
            const dateText = row.cells[5].textContent.trim();
            const status = row.cells[9].textContent.toLowerCase().trim();
            const kategori = row.cells[3].textContent.toLowerCase().trim();
            const transDate = new Date(dateText);
            let show = true;

            switch (value) {
                case "minggu":
                    const weekAgo = new Date(); weekAgo.setDate(now.getDate() - 7);
                    show = transDate >= weekAgo;
                    alertMsg = "Menampilkan transaksi minggu ini";
                    break;
                case "bulan":
                    show = transDate.getMonth() === now.getMonth() && transDate.getFullYear() === now.getFullYear();
                    alertMsg = "Menampilkan transaksi bulan ini";
                    break;
                case "tahun":
                    show = transDate.getFullYear() === now.getFullYear();
                    alertMsg = "Menampilkan transaksi tahun ini";
                    break;
                case "lunas":
                    show = status.includes("lunas");
                    alertMsg = "Menampilkan transaksi yang sudah lunas";
                    break;
                case "belum":
                    show = status.includes("belum");
                    alertMsg = "Menampilkan transaksi yang belum lunas";
                    break;
                case "":
                    show = true;
                    alertMsg = "Menampilkan semua transaksi";
                    break;
                default:
                    show = kategori.includes(value);
                    alertMsg = `Menampilkan kategori layanan: ${value}`;
                    break;
            }
            row.style.display = show ? "" : "none";
        });

        showAlert(alertMsg);
        hitungTotalPendapatan();
    });

    document.getElementById("jenisTotal").addEventListener("change", function () {
        hitungTotalPendapatan();
        showAlert(this.value === "lunas" ?
            "Menampilkan total pendapatan dari transaksi lunas saja" :
            "Menampilkan total pendapatan dari semua transaksi"
        );
    });

    document.getElementById("exportExcel").addEventListener("click", () => {
        showExportAlert("Sedang menyiapkan file Excel...", 4000);
        const filter = document.getElementById("sortBy").value;
        const search = document.getElementById("searchInput").value;
        const jenisTotal = document.getElementById("jenisTotal").value;
        setTimeout(() => {
            window.location.href = `report_excel.php?filter=${encodeURIComponent(filter)}&search=${encodeURIComponent(search)}&total=${encodeURIComponent(jenisTotal)}`;
            showAlert("✅ File Excel berhasil diexport!", 12000);
        }, 4000);
    });

    document.getElementById("exportPDF").addEventListener("click", () => {
        showExportAlert("Sedang membuat laporan PDF...", 4000);
        const filter = document.getElementById("sortBy").value;
        const search = document.getElementById("searchInput").value;
        const jenisTotal = document.getElementById("jenisTotal").value;
        setTimeout(() => {
            window.location.href = `report_pdf.php?filter=${encodeURIComponent(filter)}&search=${encodeURIComponent(search)}&total=${encodeURIComponent(jenisTotal)}`;
            showAlert("✅ File PDF berhasil diexport!", 12000);
        }, 4000);
    });

    window.onload = hitungTotalPendapatan;
</script>

<?php include_once "../partials/footer.php"; ?>