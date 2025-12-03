<?php
// projeks3/actions/karyawan/delete.php
header('Content-Type: application/json');

include('../../db.php');

// Response function
function sendResponse($success, $message)
{
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

// Validate employee ID
$id = intval($_POST['id_employee'] ?? 0);
if (!$id) {
    sendResponse(false, 'ID karyawan tidak valid');
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if employee exists
    $check = $conn->prepare("SELECT id_employee, photo, name FROM employees WHERE id_employee = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        $check->close();
        $conn->rollback();
        sendResponse(false, 'Karyawan tidak ditemukan');
    }

    $employee = $result->fetch_assoc();
    $photo = $employee['photo'];
    $name = $employee['name'];
    $check->close();

    // Delete related data from employee_roles table
    $deleteRoles = $conn->prepare("DELETE FROM employee_roles WHERE employee_id = ?");
    $deleteRoles->bind_param("i", $id);
    $deleteRoles->execute();
    $deleteRoles->close();

    // Delete employee record
    $stmt = $conn->prepare("DELETE FROM employees WHERE id_employee = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    $stmt->close();

    if (!$success) {
        $conn->rollback();
        sendResponse(false, 'Gagal menghapus data karyawan');
    }

    // Delete photo file if exists
    if ($photo) {
        $path = __DIR__ . '/../../uploads/employee/' . $photo;
        if (file_exists($path)) {
            @unlink($path);
        }
    }

    // Commit transaction
    $conn->commit();

    sendResponse(true, "Karyawan '$name' berhasil dihapus");

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    error_log("Delete employee error: " . $e->getMessage());
    sendResponse(false, 'Terjadi kesalahan: ' . $e->getMessage());
}

$conn->close();
?>