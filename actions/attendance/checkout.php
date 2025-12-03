<?php
/**
 * =============================================
 * FILE: actions/attendance/checkout.php
 * DESKRIPSI: Process Check Out with Kasir Role Removal
 * ============================================= */

// Clean output buffer
ob_start();

// Disable error display
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start session
session_start();

// Set response header
header('Content-Type: application/json; charset=utf-8');

// Response template
$response = [
    'success' => false,
    'message' => 'Unknown error',
    'data' => null
];

try {
    // Include connection
    if (!file_exists('../../db.php')) {
        throw new Exception('Connection file not found');
    }

    require_once '../../db.php';

    // Check connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get and validate input
    $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

    if ($employee_id <= 0) {
        throw new Exception('ID Karyawan tidak valid');
    }

    if (empty($password)) {
        throw new Exception('Password tidak boleh kosong');
    }

    // Get employee data
    $stmt = $conn->prepare("SELECT id_employee, employee_code, name, password, status FROM employees WHERE id_employee = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $stmt->bind_param("i", $employee_id);

    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Karyawan tidak ditemukan');
    }

    $employee = $result->fetch_assoc();
    $stmt->close();

    // Verify password
    if (!password_verify($password, $employee['password'])) {
        throw new Exception('Password salah');
    }

    // Get today's attendance record
    $today = date('Y-m-d');
    $attendanceStmt = $conn->prepare("
        SELECT * FROM attendance 
        WHERE id_employee = ? 
        AND DATE(check_in) = ? 
        AND check_out IS NULL
        ORDER BY check_in DESC
        LIMIT 1
    ");

    if (!$attendanceStmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $attendanceStmt->bind_param("is", $employee_id, $today);
    $attendanceStmt->execute();
    $attendanceResult = $attendanceStmt->get_result();

    if ($attendanceResult->num_rows === 0) {
        throw new Exception('Anda belum check in hari ini atau sudah check out');
    }

    $attendance = $attendanceResult->fetch_assoc();
    $attendanceStmt->close();

    // Calculate work hours
    $check_in = new DateTime($attendance['check_in']);
    $check_out = new DateTime();
    $interval = $check_in->diff($check_out);
    $work_hours = $interval->h + ($interval->i / 60);
    $work_hours = round($work_hours, 2);

    // Start transaction
    if (!$conn->begin_transaction()) {
        throw new Exception('Failed to start transaction');
    }

    try {
        // Update attendance record
        $check_out_time = date('Y-m-d H:i:s');
        $updateStmt = $conn->prepare("
            UPDATE attendance 
            SET check_out = ?, 
                work_hours = ?, 
                notes_check_out = ? 
            WHERE id_attendance = ?
        ");

        if (!$updateStmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }

        $updateStmt->bind_param("sdsi", $check_out_time, $work_hours, $notes, $attendance['id_attendance']);

        if (!$updateStmt->execute()) {
            throw new Exception('Update failed: ' . $updateStmt->error);
        }

        $updateStmt->close();

        // Check if there are other active check-ins for this employee
        $activeCheckStmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM attendance 
            WHERE id_employee = ? 
            AND check_out IS NULL
        ");

        if (!$activeCheckStmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $activeCheckStmt->bind_param("i", $employee_id);
        $activeCheckStmt->execute();
        $activeCheckResult = $activeCheckStmt->get_result();
        $activeCheckData = $activeCheckResult->fetch_assoc();
        $activeCheckStmt->close();

        $kasir_removed = false;
        $new_status = 'Aktif';

        // If no more active check-ins
        if ($activeCheckData['total'] == 0) {
            // Update status to Non-Aktif
            $updateStatusStmt = $conn->prepare("UPDATE employees SET status = 'Non-Aktif' WHERE id_employee = ?");

            if (!$updateStatusStmt) {
                throw new Exception('Prepare update status failed: ' . $conn->error);
            }

            $updateStatusStmt->bind_param("i", $employee_id);

            if ($updateStatusStmt->execute()) {
                $new_status = 'Non-Aktif';
            }

            $updateStatusStmt->close();

            // Remove passive kasir role (is_primary = 0)
            $removeKasirStmt = $conn->prepare("
                DELETE FROM employee_roles 
                WHERE employee_id = ? 
                AND role_type = 'kasir' 
                AND is_primary = 0
            ");

            if ($removeKasirStmt) {
                $removeKasirStmt->bind_param("i", $employee_id);
                if ($removeKasirStmt->execute() && $removeKasirStmt->affected_rows > 0) {
                    $kasir_removed = true;
                }
                $removeKasirStmt->close();
            }
        }

        // Commit transaction
        if (!$conn->commit()) {
            throw new Exception('Failed to commit transaction');
        }

        // Success response
        $response = [
            'success' => true,
            'message' => 'Check out berhasil!' . ($kasir_removed ? ' Role kasir pasif telah dihapus.' : ''),
            'data' => [
                'employee_id' => $employee_id,
                'employee_code' => $employee['employee_code'],
                'employee_name' => $employee['name'],
                'check_out_time' => date('H:i:s', strtotime($check_out_time)),
                'work_hours' => $work_hours,
                'new_status' => $new_status,
                'kasir_removed' => $kasir_removed
            ]
        ];

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null
    ];
}

// Close connection
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// Clean output buffer
ob_end_clean();

// Send JSON response
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;