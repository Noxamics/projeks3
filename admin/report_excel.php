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

// ✅ QUERY UPDATED SESUAI SCREENSHOT
$query = "
SELECT 
    d.id_drop, 
    d.order_code AS kode_order,
    ANY_VALUE(d.trans_date) AS tgl_transaksi,
    ANY_VALUE(c.name) AS customer_name, 
    ANY_VALUE(d.brand) AS brand,
    ANY_VALUE(CONCAT(s.category, ' - ', s.service_name)) AS treatment,
    ANY_VALUE(st.status_name) AS status_proses,
    
    -- Tanggal barang dikerjakan
    ANY_VALUE((
        SELECT sh.changed_at 
        FROM status_history sh 
        WHERE sh.drop_id = d.id_drop AND sh.status_id = 3
        LIMIT 1
    )) AS tgl_dikerjakan,
    
    -- Nama karyawan
    ANY_VALUE(e.name) AS karyawan, 
    
    ANY_VALUE(d.est_finish_date) AS estimasi_selesai,
    ANY_VALUE(d.actual_finish_date) AS tgl_diambil,
    
    COALESCE(SUM(di.price * di.quantity),0) AS total_harga,
    
    -- Metode pembayaran atau status
    ANY_VALUE(
        CASE 
            WHEN p.status = 'Lunas' THEN p.payment_method
            ELSE 'Belum Lunas'
        END
    ) AS metode_pembayaran,
    
    ANY_VALUE(p.status) AS status_pembayaran

FROM drops d
JOIN customers c ON d.customer_id = c.id_customer
LEFT JOIN services s ON d.service_id = s.id_service
LEFT JOIN drop_items di ON d.id_drop = di.drop_id
LEFT JOIN payments p ON d.id_drop = p.drop_id
LEFT JOIN deadlines dl ON d.id_drop = dl.drop_id
LEFT JOIN statuses st ON dl.status_id = st.id_status
LEFT JOIN employees e ON d.employee_id = e.id_employee

WHERE $where
GROUP BY d.id_drop
ORDER BY d.trans_date DESC
";

$result = mysqli_query($conn, $query);

echo "<table border='1'>";
echo "<tr style='background:#0b3d91;color:white;'>
<th>No</th>
<th>Nomor Order</th>
<th>Tgl. Barang Masuk</th>
<th>Nama Customer</th>
<th>Brand</th>
<th>Treatment</th>
<th>Status</th>
<th>Tgl. Barang Dikerjakan</th>
<th>Nama Karyawan (Yang Mengerjakan)</th>
<th>Tgl. Estimasi</th>
<th>Tgl. Diambil</th>
<th>Metode Pembayaran (Tunai/Tf/Qris)</th>
<th>Harga</th>
</tr>";

$no = 1;
$totalPendapatan = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $isLunas = strtolower($row['status_pembayaran'] ?? '') === 'lunas';
    
    // Tentukan warna untuk metode pembayaran
    $paymentColor = $isLunas ? '#16a34a' : '#dc2626';

    if ($jenisTotal === 'semua' || ($jenisTotal === 'lunas' && $isLunas)) {
        $totalPendapatan += $row['total_harga'];
    }

    echo "<tr>
        <td>{$no}</td>
        <td>{$row['kode_order']}</td>
        <td>{$row['tgl_transaksi']}</td>
        <td>{$row['customer_name']}</td>
        <td>{$row['brand']}</td>
        <td>{$row['treatment']}</td>
        <td>{$row['status_proses']}</td>
        <td>".($row['tgl_dikerjakan'] ? date('Y-m-d H:i:s', strtotime($row['tgl_dikerjakan'])) : '-')."</td>
        <td>{$row['karyawan']}</td>
        <td>{$row['estimasi_selesai']}</td>
        <td>".($row['tgl_diambil'] ?? '-')."</td>
        <td style='color:{$paymentColor};font-weight:bold;'>{$row['metode_pembayaran']}</td>
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