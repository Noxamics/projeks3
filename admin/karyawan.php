<?php
include('../db.php');
include('../partials/headerAdmin.php');
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Manajemen Karyawan</title>
    <link rel="stylesheet" href="../css/karyawan.css">
</head>

<body>
    <div class="container">
        <!-- HEADER CONTROLS -->
        <div class="header-controls">
            <div class="left-section">
                <h1 class="page-title">Data<br>Karyawan<br>Sengkuclean</h1>
            </div>

            <div class="control-buttons">
                <button class="btn-add" onclick="openAddModal()">+ Tambah Karyawan</button>
                <div class="view-toggle">
                    <button class="toggle-btn active" data-view="card" onclick="switchView('card')">
                        <span>📱</span> Card View
                    </button>
                    <button class="toggle-btn" data-view="table" onclick="switchView('table')">
                        <span>📊</span> Table View
                    </button>
                </div>
            </div>
        </div>

        <!-- CARD VIEW MODE -->
        <div id="cardView" class="view-content active">
            <div class="cards-grid">
                <?php
                $employees = $conn->query("SELECT * FROM employees ORDER BY id_employee DESC");
                $index = 0;
                while ($emp = $employees->fetch_assoc()):
                    $photoFile = !empty($emp['photo']) ? $emp['photo'] : '';
                    $photoPath = !empty($photoFile) ? "../uploads/employee/" . $photoFile : '';

                    // Check if file exists
                    if ($photoPath && !file_exists(__DIR__ . "/../uploads/employee/" . $photoFile)) {
                        $photoPath = '';
                    }

                    // Fallback to avatar
                    if (empty($photoPath)) {
                        $initial = strtoupper(substr($emp['name'], 0, 2));
                        $colors = ['6c5ce7', '667eea', '764ba2', '2ecc71', '3498db', 'e74c3c', 'f39c12', '9b59b6'];
                        $randomColor = $colors[$index % count($colors)];
                        $photoPath = "https://ui-avatars.com/api/?name=" . urlencode($initial) . "&size=400&background={$randomColor}&color=fff&bold=true";
                    }

                    $json = htmlspecialchars(json_encode($emp), ENT_QUOTES);
                    $labels = ['Employee', 'Writing', 'Business', 'Marketing', 'Design'];
                    $label = $labels[$index % count($labels)];
                    $index++;
                    ?>
                    <div class="employee-card" ondblclick='openDetailModal(<?php echo $json; ?>)'>
                        <div class="card-image-wrapper">
                            <img src="<?php echo $photoPath; ?>" alt="<?php echo htmlspecialchars($emp['name']); ?>"
                                class="card-image">
                            <div class="card-badges">
                                <span class="badge-label"><?php echo $label; ?></span>
                                <span
                                    class="badge-number"><?php echo str_pad($emp['id_employee'], 3, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <div class="card-gradient">
                                <h3 class="card-name"><?php echo htmlspecialchars($emp['name']); ?></h3>
                                <p class="card-info"><?php echo htmlspecialchars($emp['phone']); ?></p>
                                <span class="status-badge status-<?php echo strtolower($emp['status']); ?>">
                                    <?php echo htmlspecialchars($emp['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- TABLE VIEW MODE -->
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
                            <th>Bergabung</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $employeesTable = $conn->query("SELECT * FROM employees ORDER BY id_employee DESC");
                        $i = 1;
                        while ($r = $employeesTable->fetch_assoc()):
                            $file = !empty($r['photo']) ? $r['photo'] : '';
                            $path = $file ? "../uploads/employee/" . $file : '';

                            if ($path && !file_exists(__DIR__ . "/../uploads/employee/" . $file)) {
                                $path = '';
                            }

                            if (empty($path)) {
                                $initial = strtoupper(substr($r['name'], 0, 2));
                                $path = "https://ui-avatars.com/api/?name=" . urlencode($initial) . "&size=100&background=6c5ce7&color=fff&bold=true";
                            }

                            $json = htmlspecialchars(json_encode($r), ENT_QUOTES);
                            ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><img src="<?php echo $path; ?>" class="thumb"
                                        ondblclick='openDetailModal(<?php echo $json; ?>)'></td>
                                <td><?php echo htmlspecialchars($r['employee_code']); ?></td>
                                <td><?php echo htmlspecialchars($r['name']); ?></td>
                                <td><?php echo htmlspecialchars($r['phone']); ?></td>
                                <td><?php echo date('d M Y', strtotime($r['join_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($r['status']); ?>">
                                        <?php echo htmlspecialchars($r['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-edit" onclick='openEditModal(<?php echo $json; ?>)'>Edit</button>
                                    <button class="btn-delete"
                                        onclick="openDeleteModal(<?php echo $r['id_employee']; ?>, '<?php echo htmlspecialchars($r['name'], ENT_QUOTES); ?>')">Hapus</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODALS -->
    <?php include('../modal/karyawan/add.php'); ?>
    <?php include('../modal/karyawan/edit.php'); ?>
    <?php include('../modal/karyawan/delete.php'); ?>
    <?php include('../modal/karyawan/detail.php'); ?>

    <script src="../js/karyawan.js"></script>
</body>

</html>