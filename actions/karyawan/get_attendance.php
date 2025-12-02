<?php
// projeks3/actions/karyawan/get_attendance.php
include('../../db.php');

$employee_id = intval($_GET['id'] ?? 0);

if (!$employee_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT attendance_date, check_in, check_out, work_hours, notes FROM attendances WHERE employee_id = ? ORDER BY attendance_date DESC LIMIT 30");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    // Determine status based on data
    $status = 'Hadir';
    if (!$row['check_in']) {
        $status = 'Alpha';
    } elseif (!$row['check_out']) {
        $status = 'Belum Checkout';
    } elseif ($row['work_hours'] && $row['work_hours'] < 8) {
        $status = 'Pulang Awal';
    }

    $data[] = [
        'date' => date('d M Y', strtotime($row['attendance_date'])),
        'check_in' => $row['check_in'] ? date('H:i', strtotime($row['check_in'])) : '-',
        'check_out' => $row['check_out'] ? date('H:i', strtotime($row['check_out'])) : '-',
        'work_hours' => $row['work_hours'] ? number_format($row['work_hours'], 1) . ' jam' : '-',
        'status' => $status,
        'notes' => $row['notes'] ?? ''
    ];
}

$stmt->close();

header('Content-Type: application/json');
echo json_encode($data);
?>