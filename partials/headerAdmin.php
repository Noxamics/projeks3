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
                            <svg class="icon-default" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                            <svg class="icon-active" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="currentColor">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                        </div>
                    </a>
                </li>

                <!-- Drop -->
                <li class="nav-item-container <?= $current_page == 'drop.php' ? 'active' : '' ?>">
                    <a href="../admin/drop.php" title="Drop">
                        <div class="icon-wrapper">
                            <svg class="icon-default" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
                            </svg>
                            <svg class="icon-active" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
                            </svg>
                        </div>
                    </a>
                </li>

                <!-- Karyawan -->
                <li class="nav-item-container <?= $current_page == 'karyawan.php' ? 'active' : '' ?>">
                    <a href="../admin/karyawan.php" title="Karyawan">
                        <div class="icon-wrapper">
                            <svg class="icon-default" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            <svg class="icon-active" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" fill="currentColor"></path>
                                <circle cx="9" cy="7" r="4" fill="currentColor"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87" fill="currentColor"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" fill="currentColor"></path>
                            </svg>
                        </div>
                    </a>
                </li>

                <!-- Timeline Pesanan -->
                <li class="nav-item-container <?= $current_page == 'timeline_pesanan.php' ? 'active' : '' ?>">
                    <a href="../admin/timeline_pesanan.php" title="Timeline Pesanan">
                        <div class="icon-wrapper">
                            <svg class="icon-default" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="20" x2="12" y2="10"></line>
                                <line x1="18" y1="20" x2="18" y2="4"></line>
                                <line x1="6" y1="20" x2="6" y2="16"></line>
                            </svg>
                            <svg class="icon-active" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="20" x2="12" y2="10" stroke-width="4"></line>
                                <line x1="18" y1="20" x2="18" y2="4" stroke-width="4"></line>
                                <line x1="6" y1="20" x2="6" y2="16" stroke-width="4"></line>
                            </svg>
                        </div>
                    </a>
                </li>

                <!-- Laporan -->
                <li class="nav-item-container <?= $current_page == 'laporan.php' ? 'active' : '' ?>">
                    <a href="../admin/laporan.php" title="Laporan">
                        <div class="icon-wrapper">
                            <svg class="icon-default" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            <svg class="icon-active" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="currentColor">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"></path>
                                <polyline points="14 2 14 8 20 8" fill="none" stroke="#fff" stroke-width="2"></polyline>
                            </svg>
                        </div>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="header-right">
            <span class="dark-label">Dark Mode</span>
            <label class="switch">
                <input type="checkbox" id="darkModeToggle">
                <span class="slider"></span>
            </label>

            <!-- Logout Button -->
            <a href="../admin/logout.php" class="logout-button" title="Keluar">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Keluar</span>
            </a>
        </div>
    </header>

    <script src="../js/headerAdmin.js"></script>
</body>

</html>