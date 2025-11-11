<?php
include_once('../db.php');
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=laporan_transaksi_filtered.xls");

$filter = strtolower($_GET['filter'] ?? '');
$search = strtolower($_GET['search'] ?? '');
$jenisTotal = strtolower($_GET['total'] ?? 'lunas'); // default lunas
$where = "1=1";

if ($filter == 'minggu') $where .= " AND d.trans_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
elseif ($filter == 'bulan') $where .= " AND MONTH(d.trans_date)=MONTH(CURDATE()) AND YEAR(d.trans_date)=YEAR(CURDATE())";
elseif ($filter == 'tahun') $where .= " AND YEAR(d.trans_date)=YEAR(CURDATE())";
elseif ($filter == 'lunas') $where .= " AND p.status='Lunas'";
elseif ($filter == 'belum') $where .= " AND (p.status IS NULL OR p.status!='Lunas')";
elseif ($filter != '') $where .= " AND LOWER(s.category) LIKE '%" . mysqli_real_escape_string($conn, $filter) . "%'";

if (!empty($search)) {
    $esc = mysqli_real_escape_string($conn, $search);
    $where .= " AND (c.name LIKE '%$esc%' OR d.brand LIKE '%$esc%' OR s.service_name LIKE '%$esc%' OR s.category LIKE '%$esc%')";
}

$query = "
SELECT 
    d.id_drop, d.order_code AS kode_order,
    MAX(c.name) AS customer_name, MAX(d.brand) AS brand,
    MAX(s.category) AS kategori, MAX(s.service_name) AS layanan,
    MAX(d.trans_date) AS tgl_transaksi, MAX(d.est_finish_date) AS estimasi_selesai,
    MAX(st.status_name) AS status_proses, MAX(p.status) AS status_pembayaran,
    MAX(a.full_name) AS karyawan, SUM(di.price * di.quantity) AS total_harga
FROM drops d
JOIN customers c ON d.customer_id = c.id_customer
JOIN services s ON d.service_id = s.id_service
LEFT JOIN drop_items di ON d.id_drop = di.drop_id
LEFT JOIN payments p ON d.id_drop = p.drop_id
LEFT JOIN statuses st ON d.status_id = st.id_status
LEFT JOIN admin a ON 1=1
WHERE $where
GROUP BY d.id_drop
ORDER BY MAX(d.trans_date) DESC
";
$result = mysqli_query($conn, $query);

echo "<table border='1'>";
echo "<tr style='background:#0b3d91;color:white;'>
<th>No</th><th>Kode Order</th><th>Customer</th><th>Brand</th><th>Kategori</th>
<th>Layanan</th><th>Tgl Transaksi</th><th>Estimasi</th>
<th>Status Proses</th><th>Status Pembayaran</th><th>Karyawan</th><th>Harga</th></tr>";

$no = 1;
$totalPendapatan = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $isLunas = strtolower($row['status_pembayaran']) === 'lunas';
    if ($jenisTotal === 'semua' || ($jenisTotal === 'lunas' && $isLunas)) {
        $totalPendapatan += $row['total_harga'];
    }

    echo "<tr>
        <td>{$no}</td><td>{$row['kode_order']}</td><td>{$row['customer_name']}</td>
        <td>{$row['brand']}</td><td>{$row['kategori']}</td><td>{$row['layanan']}</td>
        <td>{$row['tgl_transaksi']}</td><td>{$row['estimasi_selesai']}</td>
        <td>{$row['status_proses']}</td><td>{$row['status_pembayaran']}</td>
        <td>{$row['karyawan']}</td><td>Rp ".number_format($row['total_harga'],0,',','.')."</td>
    </tr>";
    $no++;
}

$labelTotal = $jenisTotal === 'semua' ? 'Total Semua Transaksi' : 'Total Pendapatan (Lunas Saja)';
echo "<tr style='background:#eef2ff;font-weight:bold;color:#0b3d91;'>
<td colspan='11' align='right'>$labelTotal:</td>
<td>Rp ".number_format($totalPendapatan,0,',','.')."</td></tr></table>";
?>
