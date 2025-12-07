<?php
/**
 * =============================================
 * FILE: actions/attendance/check_status.php
 * DESKRIPSI: Check employee attendance status for today
 * FIXED VERSION: Proper status detection and summary
 * ============================================= */

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json; charset=utf-8');

$response = [
    'success' => false,
    'hasCheckedIn' => false,
    'hasCheckedOut' => false,
    'currentStatus' => 'Non-Aktif',
    'summary' => [
        'shoes_done' => 0,
        'work_hours' => '0',
        'role_today' => '-',
        'score' => 0
    ],
    'message' => ''
];

try {
    require_once '../../db.php';

    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;

    if ($employee_id <= 0) {
        throw new Exception('Invalid employee ID');
    }

    // Get employee's ACTUAL status from database
    $statusStmt = $conn->prepare("SELECT status FROM employees WHERE id_employee = ?");
    if (!$statusStmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $statusStmt->bind_param("i", $employee_id);
    $statusStmt->execute();
    $statusResult = $statusStmt->get_result();

    if ($statusResult->num_rows > 0) {
        $employeeData = $statusResult->fetch_assoc();
        $response['currentStatus'] = $employeeData['status'];
    }
    $statusStmt->close();

    // Check today's attendance
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT 
            id_attendance,
            check_in,
            check_out,
            role_today,
            work_hours,
            DATE_FORMAT(check_in, '%H:%i:%s') as check_in_time,
            DATE_FORMAT(check_out, '%H:%i:%s') as check_out_time
        FROM attendance 
        WHERE id_employee = ? 
        AND DATE(check_in) = ?
        ORDER BY check_in DESC
        LIMIT 1
    ");

    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $stmt->bind_param("is", $employee_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $attendance = $result->fetch_assoc();

        $response['success'] = true;
        $response['hasCheckedIn'] = true;
        $response['summary']['role_today'] = ucfirst($attendance['role_today']);

        // LOGIC: If check_out is NULL, employee is still ACTIVE (show checkout form)
        if ($attendance['check_out'] === null) {
            // STILL CHECKED IN - ACTIVE STATUS
            $response['hasCheckedOut'] = false;

            // Calculate current work hours
            $checkin_datetime = new DateTime($attendance['check_in']);
            $now = new DateTime();
            $interval = $checkin_datetime->diff($now);
            $hours = $interval->h + ($interval->days * 24);
            $minutes = $interval->i;
            $response['summary']['work_hours'] = $hours . '.' . round(($minutes / 60) * 10);

            $response['message'] = 'Employee is currently checked in (Active)';

        } else {
            // ALREADY CHECKED OUT - NON-AKTIF STATUS
            $response['hasCheckedOut'] = true;
            $response['summary']['work_hours'] = $attendance['work_hours'] ?: '0';
            $response['currentStatus'] = 'Non-Aktif'; // Force non-aktif after checkout
            $response['message'] = 'Employee has checked out today';
        }

        // Get today's shoes completed
        $stmt_shoes = $conn->prepare("
            SELECT COALESCE(SUM(total_shoes_completed), 0) as total_shoes
            FROM daily_performance
            WHERE employee_id = ? 
            AND DATE(date) = ?
        ");

        if ($stmt_shoes) {
            $stmt_shoes->bind_param("is", $employee_id, $today);
            $stmt_shoes->execute();
            $shoes_result = $stmt_shoes->get_result();
            if ($shoes_result->num_rows > 0) {
                $shoes_data = $shoes_result->fetch_assoc();
                $response['summary']['shoes_done'] = $shoes_data['total_shoes'];
            }
            $stmt_shoes->close();
        }

        // Calculate score
        $target_shoes = 50;
        if ($response['summary']['shoes_done'] > 0) {
            $response['summary']['score'] = min(100, round(($response['summary']['shoes_done'] / $target_shoes) * 100, 1));
        }

    } else {
        // NO CHECK-IN TODAY
        $response['success'] = true;
        $response['hasCheckedIn'] = false;
        $response['hasCheckedOut'] = false;
        $response['message'] = 'Employee has not checked in today';
    }

    $stmt->close();

} catch (Exception $e) {
    $response = [
        'success' => false,
        'hasCheckedIn' => false,
        'hasCheckedOut' => false,
        'currentStatus' => 'Non-Aktif',
        'message' => $e->getMessage()
    ];
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;