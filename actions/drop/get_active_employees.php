<?php
/**
 * =============================================
 * FILE: actions/drop/get_active_employees.php
 * DESKRIPSI: Get employees who are CURRENTLY ACTIVE
 * LOGIC: Checked in today + NOT checked out + Status = 'Aktif'
 * VERSION: 3.0 - Match dengan sistem attendance
 * ============================================= */

// Clean output buffer
ob_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error.log');

// MUST set Content-Type BEFORE any output
header('Content-Type: application/json; charset=utf-8');

$response = [
    'success' => false,
    'employees' => [],
    'count' => 0,
    'message' => ''
];

try {
    // Include DB connection
    $db_path = __DIR__ . '/../../db.php';
    if (!file_exists($db_path)) {
        throw new Exception('File db.php tidak ditemukan di: ' . $db_path);
    }

    require_once $db_path;

    // Check connection
    if (!isset($conn) || !$conn) {
        throw new Exception('Koneksi database gagal');
    }

    $today = date('Y-m-d');

    // =========================================================================
    // CRITICAL LOGIC: Get employees who are ACTUALLY ACTIVE right now
    // Same logic as attendance system:
    // 1. Checked in TODAY (DATE(check_in) = today)
    // 2. NOT checked out yet (check_out IS NULL)
    // 3. Status = 'Aktif' in employees table
    // =========================================================================

    $sql = "
        SELECT DISTINCT
            e.id_employee,
            e.name,
            e.employee_code,
            e.status,
            DATE_FORMAT(a.check_in, '%H:%i:%s') as check_in_time,
            a.check_in as check_in_full
        FROM employees e
        INNER JOIN attendance a ON e.id_employee = a.id_employee
        WHERE DATE(a.check_in) = ?
        AND a.check_out IS NULL
        AND e.status = 'Aktif'
        ORDER BY a.check_in ASC
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }

    $stmt->bind_param("s", $today);

    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $employees = [];

    while ($row = $result->fetch_assoc()) {
        $employees[] = [
            'id_employee' => intval($row['id_employee']),
            'name' => $row['name'],
            'employee_code' => $row['employee_code'],
            'check_in_time' => $row['check_in_time'],
            'status' => $row['status']
        ];
    }

    $stmt->close();

    // Log hasil untuk debugging
    error_log("=== GET_ACTIVE_EMPLOYEES ===");
    error_log("Date: " . $today);
    error_log("Found " . count($employees) . " ACTUALLY ACTIVE employee(s)");

    if (count($employees) > 0) {
        error_log("Active employees:");
        foreach ($employees as $emp) {
            error_log("  - {$emp['name']} (ID: {$emp['id_employee']}, Code: {$emp['employee_code']}) - Check-in: {$emp['check_in_time']}");
        }
    } else {
        error_log("No active employees today (no check-in or all checked out)");
    }

    // Clear any accidental output
    ob_end_clean();

    // Build response
    $response = [
        'success' => true,
        'employees' => $employees,
        'count' => count($employees),
        'date' => $today,
        'message' => count($employees) > 0
            ? count($employees) . ' karyawan sedang aktif'
            : 'Tidak ada karyawan yang checkin hari ini'
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("GET_ACTIVE_EMPLOYEES ERROR: " . $e->getMessage());

    // Clear any accidental output
    ob_end_clean();

    $response = [
        'success' => false,
        'employees' => [],
        'count' => 0,
        'message' => $e->getMessage(),
        'date' => date('Y-m-d')
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} finally {
    if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
        $conn->close();
    }
}

exit;
?>