<?php
include('../db.php');
include('../partials/headerAdmin.php');

// Ambil semua data karyawan
$query = "SELECT * FROM employees ORDER BY created_at DESC";
$result = $conn->query($query);

// Ambil data kehadiran hari ini
$today = date('Y-m-d');
$attendance = [];
$attQuery = $conn->query("
    SELECT employee_id, check_in, check_out 
    FROM attendances 
    WHERE attendance_date = '$today'
");

$attendance = [];
while ($row = $attQuery->fetch_assoc()) {
    $attendance[$row['employee_id']] = [
        'check_in' => $row['check_in'],
        'check_out' => $row['check_out']
    ];
}
?>

<link rel="stylesheet" href="../css/karyawan.css">

<main class="employee-page">
    <div class="employee-header">
        <h1>Data Karyawan</h1>
        <button class="btn-add" id="btnAddEmployee">+ Tambah Karyawan</button>
    </div>

    <!-- Statistik -->
    <div class="employee-stats">
        <div class="stat-box">
            <h3><?= $conn->query("SELECT COUNT(*) AS total FROM employees WHERE status='Aktif'")->fetch_assoc()['total']; ?>
            </h3>
            <p>Karyawan Aktif</p>
        </div>
        <div class="stat-box">
            <h3><?= $conn->query("SELECT COUNT(*) AS hadir FROM attendances WHERE attendance_date = CURDATE()")->fetch_assoc()['hadir']; ?>
            </h3>
            <p>Hadir Hari Ini</p>
        </div>
    </div>

    <!-- Tabel Karyawan -->
    <table class="employee-table">
        <thead>
            <tr>
                <th>Foto</th>
                <th>Nama</th>
                <th>Nomor HP</th>
                <th>Status</th>
                <th>Tanggal Masuk</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><img src="../uploads/employee/<?= $row['photo'] ?: 'default.png' ?>" class="emp-photo"></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td>
                        <select class="status-dropdown" data-id="<?= $row['id_employee'] ?>">
                            <option value="Aktif" <?= $row['status'] == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="Cuti" <?= $row['status'] == 'Cuti' ? 'selected' : '' ?>>Cuti</option>
                            <option value="Non-Aktif" <?= $row['status'] == 'Non-Aktif' ? 'selected' : '' ?>>Non-Aktif</option>
                        </select>
                    </td>

                    <td><?= date('d M Y', strtotime($row['join_date'])) ?></td>
                    <td>
                        <a href="../actions/employee_detail.php?id=<?= $row['id_employee'] ?>" class="btn-detail">Detail</a>
                        <button class="btn-edit" data-id="<?= $row['id_employee'] ?>">Edit</button>
                        <form action="../actions/employee_delete.php" method="POST" style="display:inline;">
                            <input type="hidden" name="id_employee" value="<?= $row['id_employee'] ?>">
                            <button type="submit" class="btn-delete"
                                onclick="return confirm('Yakin hapus karyawan ini?')">Hapus</button>
                        </form>

                        <?php if (isset($attendance[$row['id_employee']])): ?>
                            <?php if (empty($attendance[$row['id_employee']]['check_out'])): ?>
                                <!-- Sudah Check In tapi belum Check Out -->
                                <button class="btn-absen checkin" data-id="<?= $row['id_employee'] ?>">Check Out</button>
                            <?php else: ?>
                                <!-- Sudah Check Out -->
                                <button class="btn-absen done" disabled>✅ Sudah Absen</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- Belum Absen -->
                            <button class="btn-absen" data-id="<?= $row['id_employee'] ?>">Absen Hari Ini</button>
                        <?php endif; ?>

                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Modal Tambah/Edit -->
    <div id="employeeModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeModal">&times;</span>
            <h2 id="modalTitle">Tambah Karyawan</h2>

            <form id="employeeForm" enctype="multipart/form-data" method="POST" action="../actions/employee_save.php">
                <input type="hidden" name="id_employee" id="id_employee">

                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" name="name" id="name" required>
                </div>

                <div class="form-group">
                    <label for="phone">Nomor HP</label>
                    <input type="text" name="phone" id="phone" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="Masukkan password">
                    <small id="passwordHelp" style="color: gray;">Kosongkan jika tidak ingin mengubah password.</small>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" required>
                        <option value="Aktif">Aktif</option>
                        <option value="Cuti">Cuti</option>
                        <option value="Non-Aktif">Non-Aktif</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="join_date">Tanggal Masuk</label>
                    <input type="date" name="join_date" id="join_date" required>
                </div>

                <div class="form-group">
                    <label for="photo">Foto</label>
                    <input type="file" name="photo" id="photo" accept="image/*">
                </div>

                <button type="submit" class="btn-save">Simpan</button>
            </form>

        </div>
    </div>
</main>

<script src="../js/karyawan.js"></script>