<?php include('../partials/headerAdmin.php'); ?>
<link rel="stylesheet" href="../css/drop.css">

<!-- Tambahkan wrapper -->
<main class="drop-page">
    <div class="drop-container">
        <h1 class="title">Drop</h1>

        <div class="top-bar">
            <button class="add-btn">Tambah Barang</button>

            <div class="search-box">
                <input type="text" placeholder="Cari data...">
                <button class="search-btn">
                    🔍
                </button>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer Name</th>
                        <th>Brand/Merk</th>
                        <th>Layanan</th>
                        <th>Estimasi Selesai</th>
                        <th>Tgl. Transaksi</th>
                        <th>Proses</th>
                        <th>Pembayaran</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $data = [
                        ["ORD2507-0001", "Erick", "Adidas", "Cap Cleaning", "15 June 2025", "10 June 2025", "Barang Masuk", "Belum Lunas"],
                        ["ORD2507-0002", "Mita", "Apada", "Cleaning Regular", "20 June 2025", "15 June 2025", "Barang Masuk", "Belum Lunas"],
                        ["ORD2507-0003", "Dwi", "Iya", "Regular Minor", "20 June 2025", "15 June 2025", "Barang Masuk", "Belum Lunas"],
                        ["ORD2507-0004", "Biawak", "Gucci", "Repaint Midsole", "22 June 2025", "15 June 2025", "Barang Masuk", "Belum Lunas"],
                        ["ORD2507-0005", "Budi", "Adidas", "Cleaning Deep Clean", "18 June 2025", "12 June 2025", "Barang Masuk", "Belum Lunas"]
                    ];

                    foreach ($data as $row) {
                        echo "<tr>";
                        foreach ($row as $col) {
                            echo "<td>$col</td>";
                        }
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include_once "../partials/footer.php"; ?>