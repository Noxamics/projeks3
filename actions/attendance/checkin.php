<?php
/**
 * =============================================
 * FILE: actions/attendance/checkin.php
 * DESKRIPSI: Process Check In - ONLY FIRST CHECK-IN TODAY gets KASIR role
 * LOGIC: Cek jumlah attendance hari ini SEBELUM insert
 * ============================================= */

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
session_start();
header('Content-Type: application/json; charset=utf-8');

$response = [
    'success' => false,
    'message' => 'Unknown error',
    'data' => null
];

try {
    if (!file_exists('../../db.php')) {
        throw new Exception('Connection file not found');
    }

    require_once '../../db.php';

    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }

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

    // === CRITICAL CHECK: Is this THE FIRST check-in TODAY? ===
    // Count BEFORE inserting this check-in
    $countStmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM attendance 
        WHERE DATE(check_in) = ?
    ");

    if (!$countStmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $countStmt->bind_param("s", $today);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $countData = $countResult->fetch_assoc();
    $countStmt->close();

    // If count = 0, this will be the FIRST check-in today
    $isFirstCheckinToday = ($countData['total'] == 0);

    // Log for debugging
    error_log("CHECK-IN ATTEMPT: Employee {$employee['name']} (ID: {$employee_id})");
    error_log("Total check-ins today BEFORE this: {$countData['total']}");
    error_log("Is first check-in today: " . ($isFirstCheckinToday ? 'YES' : 'NO'));

    // Start transaction
    if (!$conn->begin_transaction()) {
        throw new Exception('Failed to start transaction');
    }

    try {
        // 1. INSERT ATTENDANCE
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

        // 2. UPDATE EMPLOYEE STATUS TO AKTIF
        $updateStmt = $conn->prepare("UPDATE employees SET status = 'Aktif' WHERE id_employee = ?");

        if (!$updateStmt) {
            throw new Exception('Prepare update failed: ' . $conn->error);
        }

        $updateStmt->bind_param("i", $employee_id);

        if (!$updateStmt->execute()) {
            throw new Exception('Update status failed: ' . $updateStmt->error);
        }

        $updateStmt->close();

        // 3. ADD KASIR ROLE ONLY IF FIRST CHECK-IN TODAY
        $kasir_auto_added = false;

        if ($isFirstCheckinToday) {
            // Check if employee already has kasir role
            if (!in_array('kasir', $employeeRoles)) {
                // Insert kasir role as PASSIVE (is_primary = 0)
                $insertRoleStmt = $conn->prepare("
                    INSERT INTO employee_roles (employee_id, role_type, is_primary) 
                    VALUES (?, 'kasir', 0)
                ");

                if ($insertRoleStmt) {
                    $insertRoleStmt->bind_param("i", $employee_id);
                    if ($insertRoleStmt->execute()) {
                        $kasir_auto_added = true;
                        error_log("✅ KASIR ROLE ADDED to Employee ID: {$employee_id}");
                    }
                    $insertRoleStmt->close();
                }
            } else {
                error_log("ℹ️ Employee ID {$employee_id} already has KASIR role (permanent)");
            }
        } else {
            error_log("ℹ️ NOT first check-in today. KASIR role NOT added to Employee ID: {$employee_id}");
        }

        // Commit transaction
        if (!$conn->commit()) {
            throw new Exception('Failed to commit transaction');
        }

        // Build success message
        $successMessage = 'Check in berhasil!';
        if ($kasir_auto_added) {
            $successMessage .= ' Anda adalah check-in pertama hari ini, role kasir ditambahkan.';
        }

        // Success response
        $response = [
            'success' => true,
            'message' => $successMessage,
            'data' => [
                'employee_id' => $employee_id,
                'employee_code' => $employee['employee_code'],
                'employee_name' => $employee['name'],
                'check_in_time' => date('H:i:s', strtotime($check_in_time)),
                'role_today' => ucfirst($role_today),
                'new_status' => 'Aktif',
                'kasir_auto_added' => $kasir_auto_added,
                'is_first_today' => $isFirstCheckinToday
            ]
        ];

    } catch (Exception $e) {
        $conn->rollback();
        error_log("TRANSACTION ROLLBACK: " . $e->getMessage());
        throw $e;
    }

} catch (Exception $e) {
    error_log("CHECK-IN ERROR: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null
    ];
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;