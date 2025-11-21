<?php
// projeks3/actions/karyawan/get_attendance.php
include('../../db.php');

$employee_id = intval($_GET['id'] ?? 0);

if (!$employee_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT date, check_in, check_out, status FROM attendances WHERE employee_id = ? ORDER BY date DESC LIMIT 30");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'date' => date('d M Y', strtotime($row['date'])),
        'check_in' => $row['check_in'] ? date('H:i', strtotime($row['check_in'])) : null,
        'check_out' => $row['check_out'] ? date('H:i', strtotime($row['check_out'])) : null,
        'status' => $row['status']
    ];
}

$stmt->close();

header('Content-Type: application/json');
echo json_encode($data);
?>