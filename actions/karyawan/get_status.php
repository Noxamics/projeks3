<?php
/**
 * Get Statistics (for auto-refresh)
 * Location: actions/karyawan/get_stats.php
 * 
 * Returns current statistics for dashboard
 */

// Security headers
header('Content-Type: application/json; charset=utf-8');

// Include database connection
require_once('../../db.php');

// Check if GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$stats = [
    'success' => false,
    'totalEmployees' => 0,
    'todayAttendance' => 0,
    'todayKasir' => null
];

try {
    // Total Active Employees
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM employees WHERE status='Aktif'");
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['totalEmployees'] = $result->fetch_assoc()['total'];
    $stmt->close();

    // Today's Attendance Count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendances WHERE attendance_date=CURDATE()");
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['todayAttendance'] = $result->fetch_assoc()['total'];
    $stmt->close();

    // Today's Cashier
    $stmt = $conn->prepare(
        "SELECT e.name, e.employee_code 
         FROM daily_kasir dk 
         JOIN employees e ON dk.employee_id=e.id_employee 
         WHERE dk.date=CURDATE() 
         LIMIT 1"
    );
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $kasir = $result->fetch_assoc();
        $stats['todayKasir'] = [
            'name' => $kasir['name'],
            'code' => $kasir['employee_code']
        ];
    }

    $stmt->close();
    $stats['success'] = true;

} catch (Exception $e) {
    error_log("Get stats error: " . $e->getMessage());
    $stats['error'] = 'Database error';
}

$conn->close();

echo json_encode($stats);
?>