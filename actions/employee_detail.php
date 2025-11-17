<?php
include('../db.php');
include('../partials/headerAdmin.php');

// Pastikan ID ada di URL
if (!isset($_GET['id'])) {
    echo "<script>alert('ID karyawan tidak ditemukan'); window.location.href='karyawan.php';</script>";
    exit;
}

$id = intval($_GET['id']);

// Ambil data karyawan
$stmt = $conn->prepare("SELECT * FROM employees WHERE id_employee = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

if (!$employee) {
    echo "<script>alert('Data karyawan tidak ditemukan'); window.location.href='karyawan.php';</script>";
    exit;
}

// Ambil riwayat absensi karyawan
$attQuery = $conn->prepare("
    SELECT attendance_date, check_in, check_out, status, work_hours, notes
    FROM attendances
    WHERE employee_id = ?
    ORDER BY attendance_date DESC
");
$attQuery->bind_param("i", $id);
$attQuery->execute();
$attResult = $attQuery->get_result();
?>

<link rel="stylesheet" href="../css/karyawan_detail.css">

<main class="employee-detail-page">
    <div class="header-detail">
        <h1>Detail Karyawan</h1>
        <a href="../admin/karyawan.php" class="btn-back">← Kembali</a>
    </div>

    <!-- BAGIAN PROFIL -->
    <section class="profile-section">
        <div class="photo-box">
            <img src="../uploads/employee/<?= $employee['photo'] ?: 'default.png' ?>" class="photo">
        </div>
        <div class="info-box">
            <h2><?= htmlspecialchars($employee['name']) ?></h2>
            <p><strong>Nomor HP:</strong> <?= htmlspecialchars($employee['phone']) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($employee['status']) ?></p>
            <p><strong>Tanggal Masuk:</strong> <?= date('d M Y', strtotime($employee['join_date'])) ?></p>
            <p><strong>Dibuat Pada:</strong> <?= date('d M Y H:i', strtotime($employee['created_at'])) ?></p>
        </div>
    </section>

    <!-- BAGIAN ABSENSI -->
    <section class="attendance-section">
        <h2>Riwayat Kehadiran</h2>

        <table class="attendance-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Status</th>
                    <th>Jam Kerja (Jam)</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($attResult->num_rows > 0): ?>
                    <?php while ($att = $attResult->fetch_assoc()): ?>
                        <?php
                        // Hitung jam kerja dari check_in dan check_out
                        $workHours = '-';
                        if (!empty($att['check_in']) && !empty($att['check_out'])) {
                            $in = strtotime($att['check_in']);
                            $out = strtotime($att['check_out']);
                            $diffSeconds = $out - $in;

                            // Format jadi jam dan menit
                            $hours = floor($diffSeconds / 3600);
                            $minutes = floor(($diffSeconds % 3600) / 60);
                            $workHours = $hours . ' jam ' . $minutes . ' menit';
                        }
                        ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($att['attendance_date'])) ?></td>
                            <td><?= $att['check_in'] ?: '-' ?></td>
                            <td><?= $att['check_out'] ?: '-' ?></td>
                            <td class="<?= strtolower($att['status']) ?>"><?= $att['status'] ?></td>
                            <td><?= $workHours ?></td>
                            <td><?= htmlspecialchars($att['notes'] ?: '-') ?></td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="6" class="no-data">Belum ada riwayat kehadiran</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>