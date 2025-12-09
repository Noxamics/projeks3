<?php // <-- TIDAK ADA SPASI SEBELUM INI

// 1. Require db.php dulu (ini akan start session)
require_once '../db.php';

// 2. Baru require check_auth
// require_once 'check_auth.php';

// // 3. Ambil data admin
// $adminData = getAdminData();
// $adminName = $adminData['name'];
// $adminEmail = $adminData['email'];
// $adminId = $adminData['id_admin'];

// 4. Baru include header (yang mungkin ada output HTML)
include('../partials/headerAdmin.php');
?>

<?php
// Get statistics
$stats = [
    'totalEmployees' => 0,
    'todayAttendance' => 0,
    'todayKasir' => []
];

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM employees WHERE status='Aktif'");
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['totalEmployees'] = $result->fetch_assoc()['total'];
    $stmt->close();
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendances WHERE attendance_date=CURDATE()");
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['todayAttendance'] = $result->fetch_assoc()['total'];
    $stmt->close();
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
}

try {
    $stmt = $conn->prepare("SELECT e.name, e.employee_code FROM daily_kasir dk 
                           JOIN employees e ON dk.employee_id=e.id_employee 
                           WHERE dk.date=CURDATE() LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $stats['todayKasir'] = $result->fetch_assoc();
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
}

// Get employees - Store as array
$employeeArray = [];
try {
    $query = "SELECT e.id_employee, e.employee_code, e.name, e.phone, 
              e.address, e.join_date, e.status, e.photo,
              GROUP_CONCAT(DISTINCT er.role_type ORDER BY er.role_type) as roles
              FROM employees e
              LEFT JOIN employee_roles er ON e.id_employee = er.employee_id
              GROUP BY e.id_employee
              ORDER BY e.id_employee DESC";

    $result = $conn->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $employeeArray[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
}

function getEmployeePhotoPath($photoFile, $name, $index)
{
    if (!empty($photoFile)) {
        $fullPath = __DIR__ . "/../uploads/employee/" . $photoFile;
        if (file_exists($fullPath)) {
            return "../uploads/employee/" . $photoFile;
        }
    }

    $initial = strtoupper(substr($name, 0, 2));
    $colors = ['6c5ce7', '667eea', '764ba2', '2ecc71', '3498db', 'e74c3c', 'f39c12', '9b59b6'];
    $color = $colors[$index % count($colors)];
    return "https://ui-avatars.com/api/?name=" . urlencode($initial) .
        "&size=400&background={$color}&color=fff&bold=true";
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Karyawan - Sengkuclean</title>

    <!-- CSS Files -->
    <link rel="stylesheet" href="../css/karyawan/karyawan.css">

</head>

<body>
    <main class="employee-page">
        <div class="container">

            <!-- HEADER CONTROLS -->
            <div class="header-controls">
                <div class="left-section">
                    <div class="header-section">
                        <h1 class="page-title">
                            <span class="title-main">Data Karyawan</span>
                            <span class="title-sub">Sengkuclean</span>
                        </h1>
                        <p class="page-description">Kelola dan pantau data karyawan secara real-time</p>
                        <!-- TAMBAHKAN BUTTON INI DI SINI (SEBELUM </div> left-section) -->
                        <button class="btn-add-mobile" onclick="openAddModal(); return false;">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M16 21V19C16 17.9391 15.5786 16.9217 14.8284 16.1716C14.0783 15.4214 13.0609 15 12 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path
                                    d="M8.5 11C10.7091 11 12.5 9.20914 12.5 7C12.5 4.79086 10.7091 3 8.5 3C6.29086 3 4.5 4.79086 4.5 7C4.5 9.20914 6.29086 11 8.5 11Z"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path d="M20 8V14M17 11H23" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>

                    <div class="quick-stats">
                        <!-- TOTAL KARYAWAN -->
                        <div class="stat-card stat-primary">
                            <div class="stat-header">
                                <div class="stat-icon">
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" fill="currentColor" />
                                        <path
                                            d="M9 11C11.2091 11 13 9.20914 13 7C13 4.79086 11.2091 3 9 3C6.79086 3 5 4.79086 5 7C5 9.20914 6.79086 11 9 11Z"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" fill="currentColor" />
                                        <path
                                            d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" fill="currentColor" />
                                        <path
                                            d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89317 18.7122 8.75608 18.1676 9.45768C17.623 10.1593 16.8604 10.6597 16 10.88"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" fill="currentColor" />
                                    </svg>
                                </div>
                                <span class="stat-badge">Total</span>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number"><?php echo $stats['totalEmployees']; ?></h3>
                                <p class="stat-label">Karyawan Aktif</p>
                            </div>
                            <div class="stat-footer">
                                <span class="stat-trend">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 19V5M12 5L5 12M12 5L19 12" stroke="#10b981" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    Active
                                </span>
                            </div>
                        </div>

                        <!--  
                        <div class="stat-card stat-success">
                            <div class="stat-header">
                                <div class="stat-icon">
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M22 11.08V12C21.9988 14.1564 21.3005 16.2547 20.0093 17.9818C18.7182 19.709 16.9033 20.9725 14.8354 21.5839C12.7674 22.1953 10.5573 22.1219 8.53447 21.3746C6.51168 20.6273 4.78465 19.2461 3.61096 17.4371C2.43727 15.628 1.87979 13.4881 2.02168 11.3363C2.16356 9.18455 2.99721 7.13631 4.39828 5.49706C5.79935 3.85781 7.69279 2.71537 9.79619 2.24013C11.8996 1.7649 14.1003 1.98232 16.07 2.85999"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" fill="currentColor" />
                                        <path d="M22 4L12 14.01L9 11.01" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" fill="currentColor" />
                                    </svg>
                                </div>
                                <span class="stat-badge">Hari Ini</span>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number"><?php echo $stats['todayAttendance']; ?></h3>
                                <p class="stat-label">Hadir Hari Ini</p>
                            </div>
                            <div class="stat-footer">
                                <span class="stat-trend">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"
                                            stroke="#10b981" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <path d="M12 6V12L16 14" stroke="#10b981" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <?php echo date('d M Y'); ?>
                                </span>
                            </div>
                        </div>-->

                        <!-- KASIR AKTIF (UPDATED) -->
                        <div class="stat-card stat-warning">
                            <div class="stat-header">
                                <div class="stat-icon">
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M21 4H3C1.89543 4 1 4.89543 1 6V10C1 11.1046 1.89543 12 3 12H21C22.1046 12 23 11.1046 23 10V6C23 4.89543 22.1046 4 21 4Z"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" fill="currentColor" />
                                        <path d="M1 12V18C1 19.1046 1.89543 20 3 20H21C22.1046 20 23 19.1046 23 18V12"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" fill="currentColor" />
                                        <path d="M12 8V16" stroke="white" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <path d="M8 16H16" stroke="white" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <span class="stat-badge">
                                    <?php echo (!empty($stats['todayKasir'])) ? 'On Duty' : 'Standby'; ?>
                                </span>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number">
                                    <?php
                                    echo (!empty($stats['todayKasir']['employee_code']))
                                        ? htmlspecialchars($stats['todayKasir']['employee_code'])
                                        : '-';
                                    ?>
                                </h3>

                                <p class="stat-label">
                                    <?php echo (!empty($stats['todayKasir'])) ? 'Karyawan Check-in' : 'Belum Ada Check-in'; ?>
                                </p>
                            </div>

                            <div class="stat-footer">
                                <span class="stat-info">
                                    <!-- ICON SVG - FIXED -->
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21"
                                            stroke="#f59e0b" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <path
                                            d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z"
                                            stroke="#f59e0b" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>

                                    <?php
                                    echo (!empty($stats['todayKasir']['name']))
                                        ? htmlspecialchars($stats['todayKasir']['name'])
                                        : 'Belum ada karyawan check-in';

                                    // Role
                                    if (!empty($stats['todayKasir']['role_today'])) {
                                        echo ' <span style="font-size: 11px; opacity: 0.8;">(' .
                                            htmlspecialchars(ucfirst($stats['todayKasir']['role_today'])) .
                                            ')</span>';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="control-buttons">
                    <button class="btn btn-primary" onclick="openAddModal(); return false;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M16 21V19C16 17.9391 15.5786 16.9217 14.8284 16.1716C14.0783 15.4214 13.0609 15 12 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path
                                d="M8.5 11C10.7091 11 12.5 9.20914 12.5 7C12.5 4.79086 10.7091 3 8.5 3C6.29086 3 4.5 4.79086 4.5 7C4.5 9.20914 6.29086 11 8.5 11Z"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M20 8V14M17 11H23" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        <span>Tambah Karyawan</span>
                    </button>

                    <!-- <button class="btn btn-secondary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 20V10M12 20V4M6 20V14" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span>Performance</span>
                    </button> -->

                    <div class="view-toggle">
                        <button class="toggle-btn active" data-view="card">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="3" width="7" height="7" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                                <rect x="14" y="3" width="7" height="7" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                                <rect x="14" y="14" width="7" height="7" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                                <rect x="3" y="14" width="7" height="7" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Card
                        </button>
                        <button class="toggle-btn" data-view="table">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 6H21M3 12H21M3 18H21" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Table
                        </button>
                    </div>
                </div>
            </div>


            <!-- FILTER SECTION - UPDATED -->
            <div class="filter-section">
                <!-- Search Box -->
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Cari Nama, Kode, atau Nomor HP..."
                        class="search-input">
                </div>

                <!-- Filters Row - 2 Columns (Status | Role) -->
                <div class="filters-row">
                    <!-- Status Filter -->
                    <div class="filter-group">
                        <label class="filter-label">Status:</label>
                        <!-- Desktop: Buttons -->
                        <div class="filter-buttons">
                            <button class="filter-btn active" data-status="all"
                                onclick="filterByStatus('all')">Semua</button>
                            <button class="filter-btn" data-status="Aktif"
                                onclick="filterByStatus('Aktif')">Aktif</button>
                            <button class="filter-btn" data-status="Cuti" onclick="filterByStatus('Cuti')">Cuti</button>
                            <button class="filter-btn" data-status="Non-Aktif"
                                onclick="filterByStatus('Non-Aktif')">Non-Aktif</button>
                        </div>
                        <!-- Mobile: Dropdown -->
                        <select id="statusFilter" class="status-select" onchange="filterByStatus(this.value)">
                            <option value="all">Semua Status</option>
                            <option value="Aktif">Aktif</option>
                            <option value="Cuti">Cuti</option>
                            <option value="Non-Aktif">Non-Aktif</option>
                        </select>
                    </div>

                    <!-- Role Filter -->
                    <div class="filter-group">
                        <label class="filter-label">Role:</label>
                        <select id="roleFilter" class="role-select">
                            <option value="all">Semua Role</option>
                            <option value="cleaning">Cleaning</option>
                            <option value="reglue">Reglue</option>
                            <option value="repaint">Repaint</option>
                            <option value="kasir">Kasir</option>
                        </select>
                    </div>
                </div>

                <!-- View Toggle - Moved Here for Mobile -->
                <div class="view-toggle">
                    <button class="toggle-btn active" data-view="card">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="3" y="3" width="7" height="7" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" />
                            <rect x="14" y="3" width="7" height="7" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" />
                            <rect x="14" y="14" width="7" height="7" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" />
                            <rect x="3" y="14" width="7" height="7" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Card
                    </button>
                    <button class="toggle-btn" data-view="table">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 6H21M3 12H21M3 18H21" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Table
                    </button>
                </div>
            </div>

            <!-- CARD VIEW -->
            <div id="cardView" class="view-content active">
                <div class="cards-grid">
                    <?php
                    if (!empty($employeeArray)):
                        $index = 0;
                        foreach ($employeeArray as $emp):
                            $photoPath = getEmployeePhotoPath($emp['photo'], $emp['name'], $index);
                            $json = htmlspecialchars(json_encode($emp), ENT_QUOTES, 'UTF-8');
                            $roles = !empty($emp['roles']) ? explode(',', $emp['roles']) : ['cleaning'];
                            $index++;
                            ?>
                            <div class="employee-card" data-status="<?php echo htmlspecialchars($emp['status']); ?>"
                                data-roles="<?php echo htmlspecialchars($emp['roles'] ?? 'cleaning'); ?>"
                                data-name="<?php echo htmlspecialchars($emp['name']); ?>"
                                data-code="<?php echo htmlspecialchars($emp['employee_code']); ?>"
                                data-phone="<?php echo htmlspecialchars($emp['phone']); ?>"
                                data-employee='<?php echo $json; ?>'>

                                <div class="card-image-wrapper">
                                    <img src="<?php echo htmlspecialchars($photoPath); ?>"
                                        alt="<?php echo htmlspecialchars($emp['name']); ?>" class="card-image" loading="lazy">

                                    <div class="card-badges">
                                        <span class="badge-code"><?php echo htmlspecialchars($emp['employee_code']); ?></span>
                                    </div>

                                    <!-- Action buttons (appear on hover) -->
                                    <div class="card-action-buttons">
                                        <!-- Attendance Button -->
                                        <button class="btn-card-mini btn-attendance"
                                            onclick="event.stopPropagation(); openAttendanceModal(<?php echo $json; ?>)"
                                            title="Absensi Karyawan">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12 6 12 12 16 14"></polyline>
                                            </svg>
                                        </button>

                                        <!-- Edit Button -->
                                        <button class="btn-card-mini btn-edit"
                                            onclick="event.stopPropagation(); openEditModal(<?php echo $json; ?>)"
                                            title="Edit Karyawan">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>

                                        <!-- Delete Button -->
                                        <button class="btn-card-mini btn-delete"
                                            onclick="event.stopPropagation(); openDeleteModal(<?php echo $emp['id_employee']; ?>, '<?php echo htmlspecialchars($emp['name'], ENT_QUOTES); ?>')"
                                            title="Hapus Karyawan">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                                </path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="card-gradient">
                                        <h3 class="card-name"><?php echo htmlspecialchars($emp['name']); ?></h3>
                                        <p class="card-phone">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px;">
                                                <path
                                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                                                </path>
                                            </svg>
                                            <?php echo htmlspecialchars($emp['phone']); ?>
                                        </p>

                                        <div class="card-roles">
                                            <?php foreach ($roles as $role): ?>
                                                <span class="role-badge role-<?php echo strtolower(trim($role)); ?>">
                                                    <?php echo ucfirst(trim($role)); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>

                                        <span class="status-badge status-<?php echo strtolower($emp['status']); ?>">
                                            <?php echo htmlspecialchars($emp['status']); ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Double-click hint -->
                                <div class="card-hint">
                                    <small>Double-click untuk detail</small>
                                </div>
                            </div>
                            <?php
                        endforeach;
                    else:
                        ?>
                        <div class="empty-state">
                            <div class="empty-icon">📭</div>
                            <h3>Belum ada data karyawan</h3>
                            <p>Klik tombol "Tambah Karyawan" untuk menambahkan karyawan baru</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TABLE VIEW - FIXED VERSION -->
            <div id="tableView" class="view-content">
                <div class="table-wrapper">
                    <table class="employee-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Foto</th>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>No HP</th>
                                <th>Status</th>
                                <th>Roles</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($employeeArray)):
                                $no = 1;
                                foreach ($employeeArray as $emp):
                                    $photoPath = getEmployeePhotoPath($emp['photo'], $emp['name'], $no);
                                    $json = htmlspecialchars(json_encode($emp), ENT_QUOTES, 'UTF-8');
                                    $roles = !empty($emp['roles']) ? explode(',', $emp['roles']) : ['cleaning'];
                                    ?>
                                    <tr class="table-row-interactive"
                                        data-status="<?php echo htmlspecialchars($emp['status']); ?>"
                                        data-roles="<?php echo htmlspecialchars($emp['roles'] ?? 'cleaning'); ?>"
                                        data-employee='<?php echo $json; ?>'>

                                        <td><?php echo $no++; ?></td>
                                        <td>
                                            <img src="<?php echo htmlspecialchars($photoPath); ?>"
                                                alt="<?php echo htmlspecialchars($emp['name']); ?>" class="table-photo">
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($emp['employee_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['phone']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($emp['status']); ?>">
                                                <?php echo htmlspecialchars($emp['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="role-badges-wrapper">
                                                <?php foreach ($roles as $role): ?>
                                                    <span class="role-badge role-<?php echo strtolower(trim($role)); ?>">
                                                        <?php echo ucfirst(trim($role)); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td onclick="event.stopPropagation();">
                                            <div class="table-actions">
                                                <!-- FIXED: Added onclick inline handlers -->
                                                <button class="btn-table btn-edit"
                                                    onclick="event.stopPropagation(); openEditModal(<?php echo $json; ?>)"
                                                    title="Edit Karyawan">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7">
                                                        </path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z">
                                                        </path>
                                                    </svg>
                                                </button>

                                                <button class="btn-table btn-delete"
                                                    onclick="event.stopPropagation(); openDeleteModal(<?php echo $emp['id_employee']; ?>, '<?php echo htmlspecialchars($emp['name'], ENT_QUOTES); ?>')"
                                                    title="Hapus Karyawan">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path
                                                            d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                                        </path>
                                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                endforeach;
                            else:
                                ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px;">
                                        <div class="empty-icon">📭</div>
                                        <h3>Belum ada data karyawan</h3>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($employeeArray)): ?>
                    <div class="table-hint">
                        <small>💡 Tips: Double-click pada row untuk melihat detail karyawan</small>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Notification Component -->
            <div id="customNotification" class="notification-overlay">
                <div class="notification-box">
                    <div id="notificationIcon" class="notification-icon success">✓</div>
                    <h3 id="notificationTitle" class="notification-title">Berhasil!</h3>
                    <p id="notificationMessage" class="notification-message">Operasi berhasil!</p>
                    <button id="notificationBtn" class="notification-btn success"
                        onclick="closeNotification()">OK</button>
                </div>
            </div>
        </div>
    </main>

    <!-- MODALS -->
    <?php
    $modalFiles = [
        '../modal/karyawan/add.php',
        '../modal/karyawan/edit.php',
        '../modal/karyawan/delete.php',
        '../modal/karyawan/detail.php',
        '../modal/karyawan/attendance.php',
    ];

    foreach ($modalFiles as $modalFile) {
        if (file_exists($modalFile)) {
            include($modalFile);
        }
    }
    ?>

    <!-- Core utilities dulu -->
    <script src="../js/karyawan/notification.js"></script>

    <!-- Modal management (butuh notification) -->
    <script src="../js/karyawan/modal.js"></script>

    <!-- Attendance (butuh modal untuk clock) -->
    <script src="../js/karyawan/attendance.js"></script>
    <script src="../js/karyawan/kasir-stat.js"></script>

    <!-- Form handlers (butuh modal & notification) -->
    <script src="../js/karyawan/modal-form.js"></script>

    <!-- UI interactions -->
    <script src="../js/karyawan/filter.js"></script>
    <script src="../js/karyawan/view-toogle.js"></script>

    <!-- ⚠️ PENTING: card-interactions.js harus ada dan di-load -->
    <script src="../js/karyawan/card-interactions.js"></script>

    <!-- Main initialization terakhir -->
    <script src="../js/karyawan/main.js"></script>

</body>

</html>

<?php include_once "../partials/footer.php"; ?>