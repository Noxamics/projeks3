<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Catalog | SengkuClean</title>
    <link rel="stylesheet" href="../css/catalog.css" />
</head>

<body>
    <!-- =============================
       HEADER
  ============================== -->
    <header>
        <div class="container header-container">
            <div class="logo">
                <a href="index.php">
                    <img src="../a/img/Logo Sengku.png" alt="SengkuClean Logo" />
                </a>
            </div>
            <div class="nav-right">
                <a href="index.php">HOME</a>
            </div>
        </div>
    </header>

    <!-- =============================
       CATALOG SECTION
  ============================== -->
    <section class="menu-section">
        <div class="container">
            <h2>CATALOG</h2>
            <p class="desc">
                Kami memberikan berbagai macam layanan untuk perawatan barang kesayangan anda yang akan dikerjakan oleh
                tim kami
                yang sudah berpengalaman dan professional.
            </p>

            <!-- Dropdown filter -->
            <div class="dropdown-group">
                <div class="main-dropdown" id="dropdown-all-container">
                    <button id="dropdown-all-toggle" class="dropdown-toggle" data-target="menu-all">
                        Semua ▾
                    </button>

                    <div class="dropdown-menu" id="menu-all">
                        <a href="#" data-submenu="semua">Semua</a>
                        <a href="#" data-submenu="sepatu">Sepatu</a>
                        <a href="#" data-submenu="tas">Tas</a>
                        <a href="#" data-submenu="topi">Topi</a>
                    </div>

                    <div class="submenu" id="submenu-sepatu">
                        <a href="#" data-submenu="sepatu-semua">Semua</a>
                        <a href="#" data-submenu="sepatu-cleaning">Cleaning</a>
                        <a href="#" data-submenu="sepatu-reglue">Reglue</a>
                        <a href="#" data-submenu="sepatu-repaint">Repaint</a>
                    </div>
                </div>
            </div>

            <!-- Catalog Grid -->
            <div id="catalog-cleaning" class="catalog">
                <div class="catalog-box">
                    <img src="../a/catalog/Sepatu 1.png" alt="Shoes" />
                    <p class="title">Regular</p>
                    <p class="detail">Cleanse midsole & outsole part entirely.</p>
                </div>

                <div class="catalog-box">
                    <img src="../a/catalog/Sepatu 2.png" alt="Medium Wash" />
                    <p class="title">Deep</p>
                    <p class="detail">Cleanse upper, midsole & outsole part entirely.</p>
                </div>

                <div class="catalog-box">
                    <img src="../a/catalog/Sepatu 3.png" alt="Hard Wash" />
                    <p class="title">Leather Care</p>
                    <p class="detail">Cleanse upper, midsole, outsole & insole part entirely.</p>
                </div>
            </div>
        </div>
    </section>

    <script src="../js/catalog.js"></script>
</body>

</html>