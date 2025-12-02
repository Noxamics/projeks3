<?php
// actions/karyawan/attendance_process.php
include('../../db.php');
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$employee_id = $_POST['employee_id'] ?? 0;
$password = $_POST['password'] ?? '';

// Verify employee and password
$stmt = $conn->prepare("SELECT id_employee, password FROM employees WHERE id_employee = ? AND status = 'Aktif'");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Karyawan tidak ditemukan atau tidak aktif']);
    exit;
}

$employee = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $employee['password'])) {
    echo json_encode(['success' => false, 'message' => 'Password salah!']);
    exit;
}

$today = date('Y-m-d');
$now = date('H:i:s');

// CHECK IN
if ($action === 'checkin') {
    $role_today = $_POST['role_today'] ?? '';
    $notes = $_POST['notes'] ?? '';

    if (empty($role_today)) {
        echo json_encode(['success' => false, 'message' => 'Pilih role terlebih dahulu']);
        exit;
    }

    // Check if already checked in today
    $check = $conn->query("SELECT id FROM attendance WHERE employee_id = $employee_id AND date = '$today'");
    if ($check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Anda sudah absen hari ini']);
        exit;
    }

    // Verify role exists for employee
    $roleCheck = $conn->query("SELECT id FROM employee_roles WHERE employee_id = $employee_id AND role_type = '$role_today'");
    if ($roleCheck->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Role tidak valid untuk karyawan ini']);
        exit;
    }

    // Check if first attendance today (auto-assign as kasir)
    $kasirCheck = $conn->query("SELECT id FROM daily_kasir WHERE date = '$today'");
    $is_kasir = false;

    if ($kasirCheck->num_rows === 0) {
        // This is the first attendance, assign as kasir
        $conn->query("INSERT INTO daily_kasir (date, employee_id) VALUES ('$today', $employee_id)");
        $is_kasir = true;
    }

    // Determine status based on time
    $status = 'Hadir';
    $checkInTime = strtotime($now);
    $lateThreshold = strtotime('08:30:00'); // Adjust as needed

    if ($checkInTime > $lateThreshold) {
        $status = 'Terlambat';
    }

    // Insert attendance
    $stmt = $conn->prepare("INSERT INTO attendance (employee_id, date, check_in, role_today, is_kasir_today, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssiis", $employee_id, $today, $now, $role_today, $is_kasir, $status, $notes);

    if ($stmt->execute()) {
        $attendance_id = $conn->insert_id;

        // Create daily performance record
        $conn->query("INSERT INTO daily_performance (employee_id, date) VALUES ($employee_id, '$today') 
                     ON DUPLICATE KEY UPDATE employee_id=employee_id");

        echo json_encode([
            'success' => true,
            'message' => 'Check in berhasil!',
            'is_kasir' => $is_kasir,
            'status' => $status,
            'time' => $now,
            'attendance_id' => $attendance_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan absensi']);
    }
}

// CHECK OUT
elseif ($action === 'checkout') {
    $notes = $_POST['notes'] ?? '';

    // Check if checked in today
    $stmt = $conn->prepare("SELECT id, check_in, check_out, role_today FROM attendance WHERE employee_id = ? AND date = ?");
    $stmt->bind_param("is", $employee_id, $today);
    $stmt->execute();
    $attendance = $stmt->get_result()->fetch_assoc();

    if (!$attendance) {
        echo json_encode(['success' => false, 'message' => 'Anda belum check in hari ini']);
        exit;
    }

    if ($attendance['check_out']) {
        echo json_encode(['success' => false, 'message' => 'Anda sudah check out hari ini']);
        exit;
    }

    // Update attendance
    $stmt = $conn->prepare("UPDATE attendance SET check_out = ?, notes = CONCAT(COALESCE(notes, ''), '\n', ?) WHERE id = ?");
    $stmt->bind_param("ssi", $now, $notes, $attendance['id']);

    if ($stmt->execute()) {
        // Calculate work duration
        $checkInTime = strtotime($attendance['check_in']);
        $checkOutTime = strtotime($now);
        $durationMinutes = round(($checkOutTime - $checkInTime) / 60);

        // Get today's work summary
        $summary = $conn->query("
            SELECT 
                COUNT(*) as total_shoes,
                SUM(duration_minutes) as total_minutes,
                AVG(CASE WHEN status = 'completed' THEN 100 ELSE 0 END) as completion_rate
            FROM employee_work_log 
            WHERE employee_id = $employee_id 
            AND DATE(start_time) = '$today'
            AND status = 'completed'
        ")->fetch_assoc();

        $totalShoes = $summary['total_shoes'] ?? 0;
        $totalMinutes = $summary['total_minutes'] ?? 0;

        // Calculate performance score (simple formula)
        $performanceScore = 0;
        if ($totalShoes > 0) {
            $avgTimePerShoe = $totalMinutes / $totalShoes;
            $targetTime = 30; // Target: 30 minutes per shoe
            $performanceScore = min(100, ($targetTime / $avgTimePerShoe) * 100);
        }

        // Calculate bonus (example: Rp 5000 per shoe)
        $bonusPerShoe = 5000;
        $totalBonus = $totalShoes * $bonusPerShoe;

        // Update daily performance
        $stmt = $conn->prepare("
            UPDATE daily_performance 
            SET total_shoes_completed = ?,
                total_cleaning = (SELECT COUNT(*) FROM employee_work_log WHERE employee_id = ? AND DATE(start_time) = ? AND service_type = 'cleaning' AND status = 'completed'),
                total_reglue = (SELECT COUNT(*) FROM employee_work_log WHERE employee_id = ? AND DATE(start_time) = ? AND service_type = 'reglue' AND status = 'completed'),
                total_repaint = (SELECT COUNT(*) FROM employee_work_log WHERE employee_id = ? AND DATE(start_time) = ? AND service_type = 'repaint' AND status = 'completed'),
                total_duration_minutes = ?,
                performance_score = ?,
                bonus_earned = ?
            WHERE employee_id = ? AND date = ?
        ");
        $stmt->bind_param(
            "iisisisisidis",
            $totalShoes,
            $employee_id,
            $today,
            $employee_id,
            $today,
            $employee_id,
            $today,
            $totalMinutes,
            $performanceScore,
            $totalBonus,
            $employee_id,
            $today
        );
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Check out berhasil!',
            'time' => $now,
            'summary' => [
                'duration_minutes' => $durationMinutes,
                'total_shoes' => $totalShoes,
                'performance_score' => number_format($performanceScore, 1),
                'bonus_earned' => $totalBonus
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal check out']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
}
?>