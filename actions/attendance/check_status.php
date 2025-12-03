<?php
/**
 * =============================================
 * FILE: actions/attendance/check_status.php
 * DESKRIPSI: Check employee attendance status for today
 * FIXED: Properly detect active status from employees table
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
    'has_checked_in' => false,
    'has_checked_out' => false,
    'check_in_time' => null,
    'check_out_time' => null,
    'role_today' => null,
    'work_hours' => '0',
    'today_shoes' => '0',
    'today_score' => '0',
    'current_status' => 'Non-Aktif',
    'message' => ''
];

try {
    require_once '../../db.php';

    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    // Get employee_id from POST
    $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;

    if ($employee_id <= 0) {
        throw new Exception('Invalid employee ID');
    }

    // FIRST: Get employee's current status from employees table
    $statusStmt = $conn->prepare("SELECT status FROM employees WHERE id_employee = ?");
    if (!$statusStmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $statusStmt->bind_param("i", $employee_id);
    $statusStmt->execute();
    $statusResult = $statusStmt->get_result();

    if ($statusResult->num_rows > 0) {
        $employeeData = $statusResult->fetch_assoc();
        $response['current_status'] = $employeeData['status']; // Get actual status from DB
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
            notes_check_in,
            notes_check_out,
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

        // Employee has checked in today
        $response['success'] = true;
        $response['has_checked_in'] = true;
        $response['check_in_time'] = $attendance['check_in_time'];
        $response['role_today'] = ucfirst($attendance['role_today']);

        // Check if already checked out
        if ($attendance['check_out'] !== null) {
            // ALREADY CHECKED OUT
            $response['has_checked_out'] = true;
            $response['check_out_time'] = $attendance['check_out_time'];
            $response['work_hours'] = $attendance['work_hours'] ?: '0';
            // Status should be Non-Aktif after checkout
            $response['current_status'] = 'Non-Aktif';
            $response['message'] = 'Employee has checked out today';
        } else {
            // STILL CHECKED IN (ACTIVE)
            // This is the key: if check_out is NULL, employee is ACTIVE
            $response['has_checked_out'] = false;

            // IMPORTANT: Use the status from employees table
            // If status is 'Aktif', show checkout form
            if ($response['current_status'] === 'Aktif') {
                // Calculate current work hours
                $checkin_datetime = new DateTime($attendance['check_in']);
                $now = new DateTime();
                $interval = $checkin_datetime->diff($now);
                $hours = $interval->h + ($interval->days * 24);
                $minutes = $interval->i;
                $response['work_hours'] = $hours . '.' . round(($minutes / 60) * 10);

                $response['message'] = 'Employee is currently checked in';
            }
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
                $response['today_shoes'] = $shoes_data['total_shoes'];
            }
            $stmt_shoes->close();
        }

        // Calculate today's score
        $target_shoes = 50;
        if ($response['today_shoes'] > 0) {
            $response['today_score'] = round(($response['today_shoes'] / $target_shoes) * 100, 1);
            if ($response['today_score'] > 100) {
                $response['today_score'] = 100;
            }
        }

    } else {
        // Employee has not checked in today
        $response['success'] = true;
        $response['has_checked_in'] = false;
        $response['has_checked_out'] = false;
        // Status should be from employees table
        $response['message'] = 'Employee has not checked in today';
    }

    $stmt->close();

} catch (Exception $e) {
    $response = [
        'success' => false,
        'has_checked_in' => false,
        'has_checked_out' => false,
        'current_status' => 'Non-Aktif',
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