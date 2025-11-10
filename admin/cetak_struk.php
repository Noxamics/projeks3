<?php
include('../db.php');

$id = $_GET['id'] ?? 0;

$query = $conn->prepare("
    SELECT d.order_code, c.name, c.phone, s.service_name, di.brand, di.price, 
           p.amount_paid, p.payment_method, p.status, d.trans_date
    FROM drops d
    JOIN customers c ON d.customer_id = c.id_customer
    JOIN drop_items di ON di.drop_id = d.id_drop
    JOIN services s ON di.service_id = s.id_service
    JOIN payments p ON p.drop_id = d.id_drop
    WHERE d.id_drop = ?
");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result()->fetch_assoc();
$query->close();

// Hitung kembalian
$price = $result['price'];
$amount_paid = $result['amount_paid'];
$kembalian = $amount_paid - $price;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak Struk</title>
    <style>
        body {
            font-family: "Courier New", monospace;
            padding: 20px;
            background: #fff;
        }

        .struk {
            width: 300px;
            border: 1px solid #333;
            padding: 10px 15px;
            margin: auto;
        }

        h2 {
            text-align: center;
            margin: 0;
        }

        hr {
            border: none;
            border-top: 1px solid #333;
            margin: 10px 0;
        }

        table {
            width: 100%;
            font-size: 14px;
        }

        td {
            padding: 3px 0;
            vertical-align: top;
        }

        .footer {
            text-align: center;
            margin-top: 12px;
            font-size: 12px;
        }

        @media print {
            body {
                margin: 0;
            }

            .struk {
                border: none;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="struk">
        <h2>SengkuClean</h2>
        <hr>
        <table>
            <tr>
                <td>Kode Order</td>
                <td>: <?= htmlspecialchars($result['order_code']) ?></td>
            </tr>
            <tr>
                <td>Nama</td>
                <td>: <?= htmlspecialchars($result['name']) ?></td>
            </tr>
            <tr>
                <td>Telepon</td>
                <td>: <?= htmlspecialchars($result['phone']) ?></td>
            </tr>
            <tr>
                <td>Barang</td>
                <td>: <?= htmlspecialchars($result['brand']) ?></td>
            </tr>
            <tr>
                <td>Layanan</td>
                <td>: <?= htmlspecialchars($result['service_name']) ?></td>
            </tr>
            <tr>
                <td>Tanggal Masuk</td>
                <td>: <?= htmlspecialchars($result['trans_date']) ?></td>
            </tr>
            <tr>
                <td>Pembayaran</td>
                <td>: <?= htmlspecialchars($result['payment_method']) ?></td>
            </tr>
            <tr>
                <td>Status</td>
                <td>: <?= htmlspecialchars($result['status']) ?></td>
            </tr>
            <tr>
                <td>Harga</td>
                <td>: Rp<?= number_format($price, 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td>Dibayar</td>
                <td>: Rp<?= number_format($amount_paid, 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td><b>Kembalian</b></td>
                <td>: <b>Rp<?= number_format(max($kembalian, 0), 0, ',', '.') ?></b></td>
            </tr>
        </table>
        <hr>
        <div class="footer">
            Terima kasih telah menggunakan SengkuClean!
        </div>
    </div>

</body>

</html>