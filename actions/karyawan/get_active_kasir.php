<?php
/**
 * =============================================
 * FILE: actions/karyawan/get_active_kasir.php
 * DESKRIPSI: Get first active employee who checked in today
 * FIXED: Show employee who checked in today with status Aktif (regardless of role_type in employee_roles)
 * LOGIC: Employee becomes kasir by checking in first, not by having permanent kasir role
 * ============================================= */

// Clean output buffer
ob_start();

// Disable error display
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set response header
header('Content-Type: application/json; charset=utf-8');

$response = [
    'success' => false,
    'has_active_kasir' => false,
    'kasir_data' => null,
    'message' => ''
];

try {
    require_once '../../db.php';

    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    $today = date('Y-m-d');

    // FIXED: Get the FIRST employee who checked in today AND is still Aktif
    // We don't check employee_roles table because kasir role is added dynamically on first check-in
    // The role_today in attendance table shows what role they're working as today
    $stmt = $conn->prepare("
        SELECT 
            e.id_employee,
            e.employee_code,
            e.name,
            e.status,
            a.role_today,
            DATE_FORMAT(a.check_in, '%H:%i:%s') as check_in_time
        FROM employees e
        INNER JOIN attendance a ON e.id_employee = a.id_employee
        WHERE DATE(a.check_in) = ?
        AND a.check_out IS NULL
        AND e.status = 'Aktif'
        ORDER BY a.check_in ASC
        LIMIT 1
    ");

    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $stmt->bind_param("s", $today);

    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Found active employee who checked in first today
        $kasir = $result->fetch_assoc();

        // Check if this employee has kasir role (either permanent or temporary)
        $roleCheckStmt = $conn->prepare("
            SELECT COUNT(*) as has_kasir_role 
            FROM employee_roles 
            WHERE employee_id = ? 
            AND role_type = 'kasir'
        ");

        if ($roleCheckStmt) {
            $roleCheckStmt->bind_param("i", $kasir['id_employee']);
            $roleCheckStmt->execute();
            $roleResult = $roleCheckStmt->get_result();
            $roleData = $roleResult->fetch_assoc();
            $roleCheckStmt->close();

            $has_kasir_role = ($roleData['has_kasir_role'] > 0);
        } else {
            $has_kasir_role = false;
        }

        $response['success'] = true;
        $response['has_active_kasir'] = true;
        $response['kasir_data'] = [
            'id' => $kasir['id_employee'],
            'code' => $kasir['employee_code'],
            'name' => $kasir['name'],
            'role' => ucfirst($kasir['role_today']),
            'check_in_time' => $kasir['check_in_time'],
            'status' => $kasir['status'],
            'has_kasir_role' => $has_kasir_role
        ];
        $response['message'] = 'Active kasir found (first check-in today)';

        // Log for debugging
        error_log("ACTIVE KASIR: {$kasir['name']} (ID: {$kasir['id_employee']}) - Has kasir role: " . ($has_kasir_role ? 'YES' : 'NO'));
    } else {
        // No active employee found
        $response['success'] = true;
        $response['has_active_kasir'] = false;
        $response['kasir_data'] = null;
        $response['message'] = 'No active kasir today';

        error_log("NO ACTIVE KASIR: No employee checked in today or all have checked out");
    }

    $stmt->close();

} catch (Exception $e) {
    error_log("GET_ACTIVE_KASIR ERROR: " . $e->getMessage());
    $response = [
        'success' => false,
        'has_active_kasir' => false,
        'kasir_data' => null,
        'message' => $e->getMessage()
    ];
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// Clean output buffer
ob_end_clean();

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;