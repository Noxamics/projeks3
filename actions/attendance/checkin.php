<?php
/**
 * =============================================
 * FILE: actions/attendance/checkin.php
 * DESKRIPSI: Process Check In with employee_roles relation
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
    $role_today = isset($_POST['role_today']) ? trim($_POST['role_today']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

    if ($employee_id <= 0) {
        throw new Exception('ID Karyawan tidak valid');
    }

    if (empty($password)) {
        throw new Exception('Password tidak boleh kosong');
    }

    if (empty($role_today)) {
        throw new Exception('Role hari ini harus dipilih');
    }

    // Get employee data with roles
    $stmt = $conn->prepare("
        SELECT 
            e.id_employee, 
            e.employee_code,
            e.name, 
            e.password, 
            e.status,
            GROUP_CONCAT(er.role_type) as roles
        FROM employees e
        LEFT JOIN employee_roles er ON e.id_employee = er.employee_id
        WHERE e.id_employee = ?
        GROUP BY e.id_employee
    ");

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

    // Check if employee has roles
    if (empty($employee['roles'])) {
        throw new Exception('Karyawan tidak memiliki role yang terdaftar');
    }

    // Validate role
    $employeeRoles = array_map('trim', explode(',', $employee['roles']));
    if (!in_array($role_today, $employeeRoles)) {
        throw new Exception('Role "' . $role_today . '" tidak valid. Role tersedia: ' . implode(', ', $employeeRoles));
    }

    // Check if already checked in today
    $today = date('Y-m-d');
    $checkStmt = $conn->prepare("
        SELECT id_attendance 
        FROM attendance 
        WHERE id_employee = ? 
        AND DATE(check_in) = ? 
        AND check_out IS NULL
    ");

    if (!$checkStmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $checkStmt->bind_param("is", $employee_id, $today);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        throw new Exception('Anda sudah check in hari ini dan belum check out');
    }
    $checkStmt->close();

    // Start transaction
    if (!$conn->begin_transaction()) {
        throw new Exception('Failed to start transaction');
    }

    try {
        // Insert attendance
        $check_in_time = date('Y-m-d H:i:s');
        $insertStmt = $conn->prepare("
            INSERT INTO attendance (id_employee, check_in, role_today, notes_check_in) 
            VALUES (?, ?, ?, ?)
        ");

        if (!$insertStmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }

        $insertStmt->bind_param("isss", $employee_id, $check_in_time, $role_today, $notes);

        if (!$insertStmt->execute()) {
            throw new Exception('Insert attendance failed: ' . $insertStmt->error);
        }

        $insertStmt->close();

        // Update employee status to Aktif
        $updateStmt = $conn->prepare("UPDATE employees SET status = 'Aktif' WHERE id_employee = ?");

        if (!$updateStmt) {
            throw new Exception('Prepare update failed: ' . $conn->error);
        }

        $updateStmt->bind_param("i", $employee_id);

        if (!$updateStmt->execute()) {
            throw new Exception('Update status failed: ' . $updateStmt->error);
        }

        $updateStmt->close();

        // Check for first check-in (auto add kasir role)
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE id_employee = ?");
        $countStmt->bind_param("i", $employee_id);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countData = $countResult->fetch_assoc();
        $countStmt->close();

        $kasir_auto_added = false;

        // If this is first check-in and doesn't have kasir role yet
        if ($countData['total'] == 1 && !in_array('kasir', $employeeRoles)) {
            // Insert kasir role into employee_roles table
            $insertRoleStmt = $conn->prepare("
                INSERT INTO employee_roles (employee_id, role_type, is_primary) 
                VALUES (?, 'kasir', 0)
            ");

            if ($insertRoleStmt) {
                $insertRoleStmt->bind_param("i", $employee_id);
                if ($insertRoleStmt->execute()) {
                    $kasir_auto_added = true;
                }
                $insertRoleStmt->close();
            }
        }

        // Commit transaction
        if (!$conn->commit()) {
            throw new Exception('Failed to commit transaction');
        }

        // Success response
        $response = [
            'success' => true,
            'message' => 'Check in berhasil!',
            'data' => [
                'employee_id' => $employee_id,
                'employee_code' => $employee['employee_code'],
                'employee_name' => $employee['name'],
                'check_in_time' => date('H:i:s', strtotime($check_in_time)),
                'role_today' => ucfirst($role_today),
                'new_status' => 'Aktif',
                'kasir_auto_added' => $kasir_auto_added
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