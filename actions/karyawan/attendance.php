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
    $chk = $conn->prepare("SELECT id FROM attendances WHERE employee_id = ? AND date = ? AND check_in IS NOT NULL");
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
    $ins = $conn->prepare("INSERT INTO attendances (employee_id, date, check_in, status) VALUES (?, ?, ?, 'Hadir')");
    $ins->bind_param("iss", $employee_id, $today, $now);
    $ok = $ins->execute();
    $ins->close();

    echo $ok ? "success" : "error:insert";

} elseif ($action === 'checkout') {
    // Check if checked in today
    $chk = $conn->prepare("SELECT id, check_out FROM attendances WHERE employee_id = ? AND date = ?");
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

    // Update check out
    $upd = $conn->prepare("UPDATE attendances SET check_out = ? WHERE id = ?");
    $upd->bind_param("si", $now, $att_id);
    $ok = $upd->execute();
    $upd->close();

    echo $ok ? "success" : "error:update";

} else {
    echo "error:invalid_action";
}
?>