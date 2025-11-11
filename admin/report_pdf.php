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
ORDER BY MIN(d.trans_date) ASC
";
$result = mysqli_query($conn, $query);

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dateNow = date('d F Y');
$html = '
<style>
body{font-family:DejaVu Sans;font-size:12px;}
h2{text-align:center;color:#0b3d91;margin-bottom:10px;}
table{width:100%;border-collapse:collapse;}
th,td{border:1px solid #ddd;padding:6px;}
th{background-color:#0b3d91;color:#fff;}
tfoot td{background-color:#eef2ff;font-weight:bold;color:#0b3d91;border-top:2px solid #0b3d91;}
.text-green{color:#16a34a;font-weight:bold;}
.text-red{color:#dc2626;font-weight:bold;}
</style>

<h2>Laporan Transaksi</h2>
<small>Tanggal Cetak: '.$dateNow.'</small>
<table>
<thead><tr>
<th>No</th><th>Kode Order</th><th>Customer</th><th>Brand</th><th>Kategori</th>
<th>Layanan</th><th>Tgl Transaksi</th><th>Estimasi</th>
<th>Status Proses</th><th>Pembayaran</th><th>Karyawan</th><th>Harga</th>
</tr></thead><tbody>';

$no = 1;
$total = 0;
while ($r = mysqli_fetch_assoc($result)) {
    $isLunas = strtolower($r['status_pembayaran']) === 'lunas';
    if ($jenisTotal === 'semua' || ($jenisTotal === 'lunas' && $isLunas)) {
        $total += $r['total_harga'];
        $html .= "<tr>
        <td>{$no}</td>
        <td>{$r['kode_order']}</td>
        <td>{$r['customer_name']}</td>
        <td>{$r['brand']}</td>
        <td>{$r['kategori']}</td>
        <td>{$r['layanan']}</td>
        <td>".date('d-m-Y', strtotime($r['tgl_transaksi']))."</td>
        <td>".date('d-m-Y', strtotime($r['estimasi_selesai']))."</td>
        <td>{$r['status_proses']}</td>
        <td class='".($isLunas ? 'text-green' : 'text-red')."'>{$r['status_pembayaran']}</td>
        <td>{$r['karyawan']}</td>
        <td>Rp ".number_format($r['total_harga'],0,',','.')."</td></tr>";
        $no++;
    }
}

$labelTotal = $jenisTotal === 'semua' ? 'Total Semua Transaksi' : 'Total Pendapatan (Lunas Saja)';
$html .= "</tbody><tfoot><tr>
<td colspan='11' align='right'>{$labelTotal}:</td>
<td>Rp ".number_format($total,0,',','.')."</td>
</tr></tfoot></table>";

$dompdf->loadHtml($html);
$dompdf->setPaper('A4','landscape');
$dompdf->render();
$dompdf->stream("laporan_filtered.pdf",["Attachment"=>true]);
exit;
?>
