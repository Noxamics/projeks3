<?php
/**
 * =============================================
 * FILE: actions/attendance/check_status.php
 * DESKRIPSI: Check if employee has checked in today
 * ============================================= */

header('Content-Type: application/json; charset=utf-8');

$response = [
    'success' => false,
    'has_checked_in' => false,
    'message' => '',
    'summary' => null
];

try {
    require_once '../../db.php';

    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    $employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;

    if ($employee_id <= 0) {
        throw new Exception('Invalid employee ID');
    }

    // Check today's attendance
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT 
            id_attendance,
            check_in,
            role_today,
            TIMESTAMPDIFF(HOUR, check_in, NOW()) as work_hours
        FROM attendance 
        WHERE id_employee = ? 
        AND DATE(check_in) = ? 
        AND check_out IS NULL
    ");

    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $stmt->bind_param("is", $employee_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $attendance = $result->fetch_assoc();

        // Get work summary (you can expand this with actual data)
        $response = [
            'success' => true,
            'has_checked_in' => true,
            'message' => 'Employee has checked in today',
            'summary' => [
                'shoes_completed' => 0, // This should be fetched from actual work records
                'work_hours' => round($attendance['work_hours'], 1),
                'role_today' => ucfirst($attendance['role_today']),
                'score' => 0 // This should be calculated based on performance
            ]
        ];
    } else {
        $response = [
            'success' => true,
            'has_checked_in' => false,
            'message' => 'Employee has not checked in today',
            'summary' => null
        ];
    }

    $stmt->close();

} catch (Exception $e) {
    $response = [
        'success' => false,
        'has_checked_in' => false,
        'message' => $e->getMessage(),
        'summary' => null
    ];
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;