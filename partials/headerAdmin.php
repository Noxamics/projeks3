<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SengkuClean | Admin</title>
    <link rel="icon" type="image/png" href="../a/img/Logo.png">
    <link rel="stylesheet" href="../css/headerAdmin.css">
</head>

<body>
    <header class="admin-header-wrapper">
        <div class="header-left">
            <img src="../a/img/Logo Teks.png" alt="SengkuClean Logo" class="logo">
        </div>

        <nav class="admin-nav">
            <ul>
                <!-- Dashboard -->
                <li class="nav-item-container <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                    <a href="../admin/dashboard.php" title="Dashboard">
                        <div class="icon-wrapper">
                            <img src="../a/headerAdmin/dashboard.png" alt="Dashboard" class="icon-default">
                            <img src="../a/headerAdmin/dashboard-active.png" alt="Dashboard Active" class="icon-active">
                        </div>
                    </a>
                </li>

                <!-- Drop -->
                <li class="nav-item-container <?= $current_page == 'drop.php' ? 'active' : '' ?>">
                    <a href="../admin/drop.php" title="Drop">
                        <div class="icon-wrapper">
                            <img src="../a/headerAdmin/drop.png" alt="Drop" class="icon-default">
                            <img src="../a/headerAdmin/drop-active.png" alt="Drop Active" class="icon-active">
                        </div>
                    </a>
                </li>

                <!-- Karyawan -->
                <li class="nav-item-container <?= $current_page == 'karyawan.php' ? 'active' : '' ?>">
                    <a href="../admin/karyawan.php" title="Member">
                        <div class="icon-wrapper">
                            <img src="../a/headerAdmin/member.png" alt="Member" class="icon-default">
                            <img src="../a/headerAdmin/member-active.png" alt="Member Active" class="icon-active">
                        </div>
                    </a>
                </li>

                <!-- Analisis -->
                <li class="nav-item-container <?= $current_page == 'analisis.php' ? 'active' : '' ?>">
                    <a href="../admin/analisis.php" title="Analisis">
                        <div class="icon-wrapper">
                            <img src="../a/headerAdmin/analisis.png" alt="Analisis" class="icon-default">
                            <img src="../a/headerAdmin/analisis-active.png" alt="Analisis Active" class="icon-active">
                        </div>
                    </a>
                </li>

                <!-- Laporan -->
                <li class="nav-item-container <?= $current_page == 'laporan.php' ? 'active' : '' ?>">
                    <a href="../admin/laporan.php" title="Laporan">
                        <div class="icon-wrapper">
                            <img src="../a/headerAdmin/laporan.png" alt="Laporan" class="icon-default">
                            <img src="../a/headerAdmin/laporan-active.png" alt="Laporan Active" class="icon-active">
                        </div>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="header-right">
            <!-- Dropdown Setting -->
            <div class="dropdown-setting">
                <img src="../a/headerAdmin/setting.png" alt="Setting" class="icon-setting" id="settingToggle">

                <div class="dropdown-menu" id="settingMenu">
                    <a href="#">Profil</a>
                    <a href="#">Preferensi</a>
                    <hr>
                    <a href="../admin/logout.php" class="logout-btn">Keluar</a>
                </div>
            </div>

            <!-- Dark Mode Toggle -->
            <span class="dark-label">Dark Mode</span>
            <label class="switch">
                <input type="checkbox" id="darkModeToggle">
                <span class="slider"></span>
            </label>
        </div>
    </header>

    <script src="../js/headerAdmin.js"></script>
</body>

</html>