<?php
/**
 * =============================================
 * FILE: actions/karyawan/get_active_kasir.php
 * DESKRIPSI: Get active employee with kasir role (for drop transactions)
 * ============================================= */

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

    // Get active employee with kasir role
    $query = "
        SELECT DISTINCT
            e.id_employee,
            e.employee_code,
            e.name,
            e.status,
            GROUP_CONCAT(DISTINCT er.role_type) as roles
        FROM employees e
        INNER JOIN employee_roles er ON e.id_employee = er.employee_id
        WHERE e.status = 'Aktif'
        AND er.role_type = 'kasir'
        GROUP BY e.id_employee
        ORDER BY e.name ASC
        LIMIT 1
    ";

    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $kasir = $result->fetch_assoc();

        $response['success'] = true;
        $response['has_active_kasir'] = true;
        $response['kasir_data'] = [
            'id' => $kasir['id_employee'],
            'code' => $kasir['employee_code'],
            'name' => $kasir['name'],
            'status' => $kasir['status'],
            'role' => 'Kasir'
        ];
        $response['message'] = 'Active kasir found';

    } else {
        $response['success'] = true;
        $response['has_active_kasir'] = false;
        $response['message'] = 'No active kasir found';
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;