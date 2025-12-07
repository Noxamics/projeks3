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

// ✅ QUERY UPDATED
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
    COALESCE(SUM(di.price * di.quantity),0) AS total_harga

FROM drops d
JOIN customers c ON d.customer_id = c.id_customer
LEFT JOIN services s ON d.service_id = s.id_service
LEFT JOIN drop_items di ON d.id_drop = di.drop_id
LEFT JOIN payments p ON d.id_drop = p.drop_id

-- ✅ status dari deadlines
LEFT JOIN deadlines dl ON d.id_drop = dl.drop_id
LEFT JOIN statuses st ON dl.status_id = st.id_status

-- ✅ karyawan
LEFT JOIN employees e ON d.employee_id = e.id_employee

WHERE $where
GROUP BY d.id_drop
ORDER BY d.trans_date DESC
";

$result = mysqli_query($conn, $query);

echo "<table border='1'>";
echo "<tr style='background:#0b3d91;color:white;'>
<th>No</th><th>Kode Order</th><th>Customer</th><th>Brand</th><th>Kategori</th>
<th>Layanan</th><th>Tgl Transaksi</th><th>Estimasi</th><th>Selesai</th>
<th>Status Proses</th><th>Status Pembayaran</th><th>Karyawan</th><th>Harga</th></tr>";

$no = 1;
$totalPendapatan = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $isLunas = strtolower($row['status_pembayaran'] ?? '') === 'lunas';

    if ($jenisTotal === 'semua' || ($jenisTotal === 'lunas' && $isLunas)) {
        $totalPendapatan += $row['total_harga'];
    }

    echo "<tr>
        <td>{$no}</td>
        <td>{$row['kode_order']}</td>
        <td>{$row['customer_name']}</td>
        <td>{$row['brand']}</td>
        <td>{$row['kategori']}</td>
        <td>{$row['layanan']}</td>
        <td>{$row['tgl_transaksi']}</td>
        <td>{$row['estimasi_selesai']}</td>
        <td>".($row['tanggal_selesai'] ?? '-')."</td>
        <td>{$row['status_proses']}</td>
        <td>{$row['status_pembayaran']}</td>
        <td>{$row['karyawan']}</td>
        <td>Rp ".number_format($row['total_harga'],0,',','.')."</td>
    </tr>";
    $no++;
}

$labelTotal = $jenisTotal === 'semua' ? 'Total Semua Transaksi' : 'Total Pendapatan (Lunas Saja)';
echo "<tr style='background:#eef2ff;font-weight:bold;color:#0b3d91;'>
<td colspan='12' align='right'>$labelTotal:</td>
<td>Rp ".number_format($totalPendapatan,0,',','.')."</td>
</tr></table>";
?>