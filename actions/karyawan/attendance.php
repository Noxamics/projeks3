<?php
// projeks3/actions/karyawan/attendance.php
include('../../db.php');

$employee_id = intval($_POST['employee_id'] ?? 0);
$password = $_POST['password'] ?? '';
$action = $_POST['action'] ?? '';

if (!$employee_id || !$password || !$action) {
    echo "error:missing";
    exit;
}

// Verify password
// CATATAN: Anda perlu menambahkan kolom 'password' di tabel employees
$stmt = $conn->prepare("SELECT password FROM employees WHERE id_employee = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$stmt->bind_result($hash);
$stmt->fetch();
$stmt->close();

if (!$hash || !password_verify($password, $hash)) {
    echo "error:password";
    exit;
}

$today = date('Y-m-d');
$now = date('H:i:s');

if ($action === 'checkin') {
    // Check if already checked in today
    $chk = $conn->prepare("SELECT id_attendance FROM attendances WHERE employee_id = ? AND attendance_date = ? AND check_in IS NOT NULL");
    $chk->bind_param("is", $employee_id, $today);
    $chk->execute();
    $chk->store_result();

    if ($chk->num_rows > 0) {
        $chk->close();
        echo "error:already";
        exit;
    }
    $chk->close();

    // Insert check in
    $ins = $conn->prepare("INSERT INTO attendances (employee_id, attendance_date, check_in) VALUES (?, ?, ?)");
    $ins->bind_param("iss", $employee_id, $today, $now);
    $ok = $ins->execute();
    $ins->close();

    echo $ok ? "success" : "error:insert";

} elseif ($action === 'checkout') {
    // Check if checked in today
    $chk = $conn->prepare("SELECT id_attendance, check_out FROM attendances WHERE employee_id = ? AND attendance_date = ?");
    $chk->bind_param("is", $employee_id, $today);
    $chk->execute();
    $chk->bind_result($att_id, $existing_checkout);
    $found = $chk->fetch();
    $chk->close();

    if (!$found) {
        echo "error:no_checkin";
        exit;
    }

    if ($existing_checkout) {
        echo "error:already";
        exit;
    }

    // Calculate work hours
    $get_checkin = $conn->prepare("SELECT check_in FROM attendances WHERE id_attendance = ?");
    $get_checkin->bind_param("i", $att_id);
    $get_checkin->execute();
    $get_checkin->bind_result($check_in_time);
    $get_checkin->fetch();
    $get_checkin->close();

    $work_hours = 0;
    if ($check_in_time) {
        $start = new DateTime($today . ' ' . $check_in_time);
        $end = new DateTime($today . ' ' . $now);
        $diff = $start->diff($end);
        $work_hours = $diff->h + ($diff->i / 60);
    }

    // Update check out and work hours
    $upd = $conn->prepare("UPDATE attendances SET check_out = ?, work_hours = ? WHERE id_attendance = ?");
    $upd->bind_param("sdi", $now, $work_hours, $att_id);
    $ok = $upd->execute();
    $upd->close();

    echo $ok ? "success" : "error:update";

} else {
    echo "error:invalid_action";
}
?>