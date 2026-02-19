<?php
require '../vendor/autoload.php';
include_once('../db.php');

use Dompdf\Dompdf;
use Dompdf\Options;

$filter = strtolower($_GET['filter'] ?? '');
$search = strtolower($_GET['search'] ?? '');
$jenisTotal = strtolower($_GET['total'] ?? 'lunas');

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
ORDER BY d.trans_date ASC
";

$result = mysqli_query($conn, $query);

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dateNow = date('d F Y');
$html = '
<style>
body{font-family:DejaVu Sans;font-size:9px;}
h2{text-align:center;color:#0b3d91;margin-bottom:10px;}
table{width:100%;border-collapse:collapse;}
th,td{border:1px solid #ddd;padding:3px;font-size:8px;}
th{background-color:#0b3d91;color:#fff;}
tfoot td{background-color:#eef2ff;font-weight:bold;color:#0b3d91;border-top:2px solid #0b3d91;}
.text-green{color:#16a34a;font-weight:bold;}
.text-red{color:#dc2626;font-weight:bold;}
</style>

<h2>Laporan Transaksi</h2>
<small>Tanggal Cetak: '.$dateNow.'</small>
<table>
<thead><tr>
<th>No</th>
<th>Nomor Order</th>
<th>Tgl. Masuk</th>
<th>Customer</th>
<th>Brand</th>
<th>Treatment</th>
<th>Status</th>
<th>Tgl. Dikerjakan</th>
<th>Karyawan</th>
<th>Tgl. Estimasi</th>
<th>Tgl. Diambil</th>
<th>Metode Pembayaran</th>
<th>Harga</th>
</tr></thead><tbody>';

$no = 1;
$total = 0;
while ($r = mysqli_fetch_assoc($result)) {
    $isLunas = strtolower($r['status_pembayaran'] ?? '') === 'lunas';
    
    if ($jenisTotal === 'semua' || ($jenisTotal === 'lunas' && $isLunas)) {
        $total += $r['total_harga'];
        
        $html .= "<tr>
        <td>{$no}</td>
        <td>{$r['kode_order']}</td>
        <td>".($r['tgl_transaksi'] ? date('d-m-Y', strtotime($r['tgl_transaksi'])) : '-')."</td>
        <td>{$r['customer_name']}</td>
        <td>{$r['brand']}</td>
        <td>{$r['treatment']}</td>
        <td>{$r['status_proses']}</td>
        <td>".($r['tgl_dikerjakan'] ? date('d-m-Y H:i', strtotime($r['tgl_dikerjakan'])) : '-')."</td>
        <td>{$r['karyawan']}</td>
        <td>".($r['estimasi_selesai'] ? date('d-m-Y', strtotime($r['estimasi_selesai'])) : '-')."</td>
        <td>".($r['tgl_diambil'] ? date('d-m-Y', strtotime($r['tgl_diambil'])) : '-')."</td>
        <td class='".($isLunas ? 'text-green' : 'text-red')."'>{$r['metode_pembayaran']}</td>
        <td>Rp ".number_format($r['total_harga'],0,',','.')."</td></tr>";
        $no++;
    }
}

$labelTotal = $jenisTotal === 'semua' ? 'Total Semua Transaksi' : 'Total Pendapatan (Lunas Saja)';
$html .= "</tbody><tfoot><tr>
<td colspan='12' align='right'>{$labelTotal}:</td>
<td>Rp ".number_format($total,0,',','.')."</td>
</tr></tfoot></table>";

$dompdf->loadHtml($html);
$dompdf->setPaper('A4','landscape');
$dompdf->render();
$dompdf->stream("laporan_filtered.pdf",["Attachment"=>true]);
exit;
?>