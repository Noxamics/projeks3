<?php
include('../db.php');
header('Content-Type: application/json');

$today = date('Y-m-d');

// Ambil semua karyawan yang sudah absen hari ini
$query = "
    SELECT e.id_employee, e.name
    FROM employees e
    INNER JOIN attendances a ON e.id_employee = a.employee_id
    WHERE a.attendance_date = ?
      AND a.check_in IS NOT NULL
      AND (a.check_out IS NULL OR a.check_out = '')
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}

echo json_encode($employees);
?>