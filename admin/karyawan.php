<?php
require_once('../db.php');
require_once('../partials/headerAdmin.php');

// Get statistics
$stats = [
    'totalEmployees' => 0,
    'todayAttendance' => 0,
    'todayKasir' => null
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
    <link rel="stylesheet" href="../css/karyawan/base.css">
    <link rel="stylesheet" href="../css/karyawan/modal.css">
    <link rel="stylesheet" href="../css/karyawan/header.css">
    <link rel="stylesheet" href="../css/karyawan/cards.css">
    <link rel="stylesheet" href="../css/karyawan/table.css">
    <link rel="stylesheet" href="../css/karyawan/responsive.css">
    <link rel="stylesheet" href="../css/karyawan/card-interactions.css">
</head>

<body>
    <main class="employee-page">
        <div class="container">

            <!-- HEADER -->
            <div class="header-controls">
                <div class="left-section">
                    <h1 class="page-title">Data Karyawan<br>Sengkuclean</h1>

                    <div class="quick-stats">
                        <div class="stat-card stat-primary">
                            <div class="stat-icon">👥</div>
                            <div class="stat-content">
                                <h3><?php echo $stats['totalEmployees']; ?></h3>
                                <p>Karyawan Aktif</p>
                            </div>
                        </div>

                        <div class="stat-card stat-success">
                            <div class="stat-icon">✅</div>
                            <div class="stat-content">
                                <h3><?php echo $stats['todayAttendance']; ?></h3>
                                <p>Hadir Hari Ini</p>
                            </div>
                        </div>

                        <div class="stat-card stat-warning">
                            <div class="stat-icon">💰</div>
                            <div class="stat-content">
                                <h3><?php echo $stats['todayKasir'] ? $stats['todayKasir']['employee_code'] : '-'; ?>
                                </h3>
                                <p>Kasir:
                                    <?php echo $stats['todayKasir'] ? $stats['todayKasir']['name'] : 'Belum ada'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="control-buttons">
                    <button class="btn btn-primary" onclick="openAddModal(); return false;">
                        <span class="btn-icon">➕</span>
                        <span>Tambah Karyawan</span>
                    </button>

                    <button class="btn btn-secondary" onclick="window.location.href='karyawan_performance.php'">
                        <span class="btn-icon">📊</span>
                        <span>Performance</span>
                    </button>

                    <div class="view-toggle">
                        <button class="toggle-btn active" data-view="card" onclick="switchView('card')">
                            <span>📱</span> Card
                        </button>
                        <button class="toggle-btn" data-view="table" onclick="switchView('table')">
                            <span>📊</span> Table
                        </button>
                    </div>
                </div>
            </div>

            <!-- FILTER SECTION -->
            <div class="filter-section">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="🔍 Cari nama, kode, atau nomor HP..."
                        class="search-input">
                </div>

                <div class="filter-group">
                    <label class="filter-label">Status:</label>
                    <div class="filter-buttons">
                        <button class="filter-btn active" data-status="all"
                            onclick="filterByStatus('all')">Semua</button>
                        <button class="filter-btn" data-status="Aktif" onclick="filterByStatus('Aktif')">Aktif</button>
                        <button class="filter-btn" data-status="Cuti" onclick="filterByStatus('Cuti')">Cuti</button>
                        <button class="filter-btn" data-status="Non-Aktif"
                            onclick="filterByStatus('Non-Aktif')">Non-Aktif</button>
                    </div>
                </div>

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
                                        <span
                                            class="badge-number">#<?php echo str_pad($emp['id_employee'], 3, '0', STR_PAD_LEFT); ?></span>
                                        <span class="badge-code"><?php echo htmlspecialchars($emp['employee_code']); ?></span>
                                    </div>

                                    <!-- Action buttons (appear on hover) -->
                                    <div class="card-action-buttons">
                                        <button class="btn-card-mini btn-edit"
                                            onclick="event.stopPropagation(); openEditModal(<?php echo $json; ?>)"
                                            title="Edit Karyawan">
                                            ✏️
                                        </button>
                                        <button class="btn-card-mini btn-delete"
                                            onclick="event.stopPropagation(); openDeleteModal(<?php echo $emp['id_employee']; ?>, '<?php echo htmlspecialchars($emp['name'], ENT_QUOTES); ?>')"
                                            title="Hapus Karyawan">
                                            🗑️
                                        </button>
                                    </div>

                                    <div class="card-gradient">
                                        <h3 class="card-name"><?php echo htmlspecialchars($emp['name']); ?></h3>
                                        <p class="card-phone">📞 <?php echo htmlspecialchars($emp['phone']); ?></p>

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
                                    <small>👆 Double-click untuk detail</small>
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

            <!-- TABLE VIEW -->
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
                                        <td>
                                            <div class="table-actions">
                                                <button class="btn-table btn-edit"
                                                    onclick="event.stopPropagation(); openEditModal(<?php echo $json; ?>)"
                                                    title="Edit Karyawan">
                                                    ✏️
                                                </button>
                                                <button class="btn-table btn-delete"
                                                    onclick="event.stopPropagation(); openDeleteModal(<?php echo $emp['id_employee']; ?>, '<?php echo htmlspecialchars($emp['name'], ENT_QUOTES); ?>')"
                                                    title="Hapus Karyawan">
                                                    🗑️
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

        </div>
    </main>

    <!-- MODALS -->
    <?php
    $modalFiles = [
        '../modal/karyawan/add.php',
        '../modal/karyawan/edit.php',
        '../modal/karyawan/delete.php',
        '../modal/karyawan/detail.php',
    ];

    foreach ($modalFiles as $modalFile) {
        if (file_exists($modalFile)) {
            include($modalFile);
        }
    }
    ?>

    <!-- JAVASCRIPT FILES -->
    <script src="../js/karyawan/main.js"></script>
    <script src="../js/karyawan/filter.js"></script>
    <script src="../js/karyawan/modal.js"></script>
    <script src="../js/karyawan/attendance.js"></script>
    <script src="../js/karyawan/card-interactions.js"></script>

</body>

</html>