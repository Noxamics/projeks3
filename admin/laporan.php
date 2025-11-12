<?php
include_once('../partials/headerAdmin.php');
include_once('../db.php');

// 🔹 Query data laporan (ubah ORDER BY ASC agar urut dari terlama)
$query = "
SELECT 
    d.id_drop,
    d.order_code AS kode_order,
    MAX(c.name) AS customer_name,
    MAX(d.brand) AS brand,
    MAX(s.category) AS kategori,
    MAX(s.service_name) AS layanan,
    MAX(d.trans_date) AS tgl_transaksi,
    MAX(d.est_finish_date) AS estimasi_selesai,
    MAX(st.status_name) AS status_proses,
    MAX(p.status) AS status_pembayaran,
    MAX(a.full_name) AS karyawan,
    SUM(di.price * di.quantity) AS total_harga
FROM drops d
JOIN customers c ON d.customer_id = c.id_customer
JOIN services s ON d.service_id = s.id_service
LEFT JOIN drop_items di ON d.id_drop = di.drop_id
LEFT JOIN payments p ON d.id_drop = p.drop_id
LEFT JOIN statuses st ON d.status_id = st.id_status
LEFT JOIN admin a ON 1=1
GROUP BY d.id_drop
ORDER BY MIN(d.trans_date) ASC
";
$result = mysqli_query($conn, $query);

// 🔹 Ambil daftar kategori layanan unik
$kategoriQuery = "SELECT DISTINCT category FROM services ORDER BY category ASC";
$kategoriResult = mysqli_query($conn, $kategoriQuery);
?>

<link rel="stylesheet" href="../css/laporan.css">

<main>
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
                <?php while ($kat = mysqli_fetch_assoc($kategoriResult)) : ?>
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
                    <th>Status Proses</th>
                    <th>Pembayaran</th>
                    <th>Karyawan</th>
                    <th>Harga</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                    <tr>
                        <td><?= htmlspecialchars($row['kode_order']); ?></td>
                        <td><?= htmlspecialchars($row['customer_name']); ?></td>
                        <td><?= htmlspecialchars($row['brand']); ?></td>
                        <td><?= htmlspecialchars($row['kategori']); ?></td>
                        <td><?= htmlspecialchars($row['layanan']); ?></td>
                        <td><?= date('d F Y', strtotime($row['tgl_transaksi'])); ?></td>
                        <td><?= date('d F Y', strtotime($row['estimasi_selesai'])); ?></td>
                        <td><?= htmlspecialchars($row['status_proses']); ?></td>
                        <td class="<?= strtolower($row['status_pembayaran']) == 'lunas' ? 'text-green' : 'text-red'; ?>">
                            <?= htmlspecialchars($row['status_pembayaran']); ?>
                        </td>
                        <td><?= htmlspecialchars($row['karyawan']); ?></td>
                        <td data-harga="<?= $row['total_harga'] ?>">Rp <?= number_format($row['total_harga'], 0, ',', '.'); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="10" style="text-align:right; font-weight:bold; color:#0b3d91;">
                        Total Pendapatan:
                    </td>
                    <td id="totalPendapatan" style="font-weight:bold; color:#0b3d91;">Rp 0</td>
                </tr>
            </tfoot>
        </table>
    </div>
</main>

<script src="../js/drop.js"></script>
<script>
// 🔢 Hitung total dinamis
function hitungTotalPendapatan() {
    const jenisTotal = document.getElementById("jenisTotal").value;
    let total = 0;
    document.querySelectorAll("#laporanTable tbody tr").forEach(row => {
        const visible = row.style.display !== "none";
        const status = row.cells[8].textContent.trim().toLowerCase();
        const harga = parseInt(row.cells[10].dataset.harga || 0);

        if (visible) {
            if (jenisTotal === "semua" || (jenisTotal === "lunas" && status === "lunas")) {
                total += harga;
            }
        }
    });
    document.getElementById("totalPendapatan").textContent = "Rp " + total.toLocaleString("id-ID");
}

// 🔍 Search Filter
document.getElementById("searchInput").addEventListener("keyup", function() {
    const value = this.value.toLowerCase();
    document.querySelectorAll("#laporanTable tbody tr").forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(value) ? "" : "none";
    });
    hitungTotalPendapatan();
});

// 🔽 Sort Filter
document.getElementById("sortBy").addEventListener("change", function() {
    const value = this.value.toLowerCase();
    const rows = document.querySelectorAll("#laporanTable tbody tr");
    const now = new Date();

    rows.forEach(row => {
        const dateText = row.cells[5].textContent.trim();
        const status = row.cells[8].textContent.toLowerCase().trim();
        const kategori = row.cells[3].textContent.toLowerCase().trim();
        const transDate = new Date(dateText);
        let show = true;

        switch (value) {
            case "minggu":
                const weekAgo = new Date(); weekAgo.setDate(now.getDate() - 7);
                show = transDate >= weekAgo;
                break;
            case "bulan":
                show = transDate.getMonth() === now.getMonth() && transDate.getFullYear() === now.getFullYear();
                break;
            case "tahun":
                show = transDate.getFullYear() === now.getFullYear();
                break;
            case "lunas":
                show = status.includes("lunas");
                break;
            case "belum":
                show = status.includes("belum");
                break;
            case "":
                show = true;
                break;
            default:
                show = kategori.includes(value);
                break;
        }
        row.style.display = show ? "" : "none";
    });
    hitungTotalPendapatan();
});

// 🔁 Ubah jenis total
document.getElementById("jenisTotal").addEventListener("change", hitungTotalPendapatan);

// ⬇️ Export sesuai filter aktif
document.getElementById("exportExcel").addEventListener("click", () => {
    const filter = document.getElementById("sortBy").value;
    const search = document.getElementById("searchInput").value;
    const jenisTotal = document.getElementById("jenisTotal").value;
    window.location.href = `report_excel.php?filter=${encodeURIComponent(filter)}&search=${encodeURIComponent(search)}&total=${encodeURIComponent(jenisTotal)}`;
});

document.getElementById("exportPDF").addEventListener("click", () => {
    const filter = document.getElementById("sortBy").value;
    const search = document.getElementById("searchInput").value;
    const jenisTotal = document.getElementById("jenisTotal").value;
    window.location.href = `report_pdf.php?filter=${encodeURIComponent(filter)}&search=${encodeURIComponent(search)}&total=${encodeURIComponent(jenisTotal)}`;
});

window.onload = hitungTotalPendapatan;
</script>

<?php include_once('../partials/footer.php'); ?>
