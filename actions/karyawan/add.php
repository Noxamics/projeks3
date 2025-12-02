<?php
/**
 * Add Employee Action - COMPLETE VERSION
 * Location: actions/karyawan/add.php
 * 
 * Process: Form → Validate → Upload Photo → Insert DB → Insert Roles → Return Success
 */

// Prevent direct access
if (!isset($_POST['employee_code'])) {
    die('error:invalid_access');
}

// Security headers
header('Content-Type: text/plain; charset=utf-8');

// Include database connection
require_once('../../db.php');

// Session check (optional - uncomment if using sessions)
// session_start();
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     die('error:unauthorized');
// }

// ===========================
// VALIDATE REQUIRED FIELDS
// ===========================
$required = ['employee_code', 'name', 'phone', 'join_date', 'status', 'password'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        error_log("Missing field: $field");
        die('error:missing_field_' . $field);
    }
}

// Validate roles
if (empty($_POST['roles']) || !is_array($_POST['roles'])) {
    error_log("Missing or invalid roles");
    die('error:missing_roles');
}

// ===========================
// SANITIZE INPUTS
// ===========================
$employee_code = strtoupper(trim($_POST['employee_code']));
$name = trim($_POST['name']);
$phone = trim($_POST['phone']);
$join_date = $_POST['join_date'];
$status = $_POST['status'];
$password = $_POST['password'];
$roles = $_POST['roles'];

// ===========================
// VALIDATE INPUT FORMATS
// ===========================

// Validate employee code (alphanumeric only)
if (!preg_match('/^[A-Z0-9]+$/', $employee_code)) {
    error_log("Invalid employee code format: $employee_code");
    die('error:invalid_employee_code_format');
}

// Validate phone (10-15 digits)
if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
    error_log("Invalid phone format: $phone");
    die('error:invalid_phone_format');
}

// Validate status
$valid_statuses = ['Aktif', 'Cuti', 'Non-Aktif'];
if (!in_array($status, $valid_statuses)) {
    error_log("Invalid status: $status");
    die('error:invalid_status');
}

// Validate roles
$valid_roles = ['cleaning', 'reglue', 'repaint', 'kasir'];
foreach ($roles as $role) {
    if (!in_array($role, $valid_roles)) {
        error_log("Invalid role: $role");
        die('error:invalid_role');
    }
}

// Validate password length
if (strlen($password) < 6) {
    error_log("Password too short");
    die('error:password_too_short');
}

// ===========================
// CHECK DUPLICATES
// ===========================
try {
    $stmt = $conn->prepare("SELECT id_employee FROM employees WHERE employee_code = ? OR phone = ?");
    $stmt->bind_param("ss", $employee_code, $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $existing = $result->fetch_assoc();
        $stmt->close();
        error_log("Duplicate found - Code: $employee_code or Phone: $phone");
        die('error:duplicate_code_or_phone');
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Database error checking duplicates: " . $e->getMessage());
    die('error:database_check_failed');
}

// ===========================
// HANDLE PHOTO UPLOAD
// ===========================
$photo_name = null;
$upload_path = __DIR__ . '/../../uploads/employee/';

// Create upload directory if not exists
if (!is_dir($upload_path)) {
    if (!mkdir($upload_path, 0755, true)) {
        error_log("Failed to create upload directory: $upload_path");
        die('error:upload_dir_creation_failed');
    }
}

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['photo'];

    error_log("Processing photo upload: " . $file['name']);

    // Validate file type using finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!in_array($mime_type, $allowed_types)) {
        error_log("Invalid file type: $mime_type");
        die('error:invalid_file_type');
    }

    // Validate file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        error_log("File too large: " . $file['size'] . " bytes");
        die('error:file_too_large');
    }

    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
        $extension = 'jpg'; // Default extension
    }

    $photo_name = 'emp_' . $employee_code . '_' . time() . '.' . $extension;
    $target_path = $upload_path . $photo_name;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        error_log("Failed to move uploaded file to: $target_path");
        $photo_name = null; // Reset if upload fails
    } else {
        error_log("Photo uploaded successfully: $photo_name");

        // Set proper permissions
        chmod($target_path, 0644);
    }
} else if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    // There was an upload error
    $error_code = $_FILES['photo']['error'];
    error_log("File upload error code: $error_code");
}

// ===========================
// HASH PASSWORD
// ===========================
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
error_log("Password hashed successfully");

// ===========================
// INSERT TO DATABASE
// ===========================
$conn->begin_transaction();

try {
    // 1. Insert employee
    $stmt = $conn->prepare(
        "INSERT INTO employees (employee_code, name, phone, join_date, status, password, photo, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
    );

    $stmt->bind_param("sssssss", $employee_code, $name, $phone, $join_date, $status, $hashed_password, $photo_name);

    if (!$stmt->execute()) {
        throw new Exception("Failed to insert employee: " . $stmt->error);
    }

    $employee_id = $conn->insert_id;
    error_log("Employee inserted successfully. ID: $employee_id");

    $stmt->close();

    // 2. Insert roles
    $stmt = $conn->prepare("INSERT INTO employee_roles (employee_id, role_type) VALUES (?, ?)");

    foreach ($roles as $role) {
        $stmt->bind_param("is", $employee_id, $role);
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert role '$role': " . $stmt->error);
        }
        error_log("Role inserted: $role for employee ID: $employee_id");
    }

    $stmt->close();

    // 3. Commit transaction
    $conn->commit();

    error_log("Transaction committed successfully");
    error_log("Employee added: ID=$employee_id, Code=$employee_code, Name=$name");

    // Return success
    echo "success";

} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();

    // Delete uploaded photo if exists
    if ($photo_name && file_exists($upload_path . $photo_name)) {
        unlink($upload_path . $photo_name);
        error_log("Rolled back: Photo deleted");
    }

    error_log("Error adding employee: " . $e->getMessage());
    die('error:' . $e->getMessage());
}

$conn->close();

// ===========================
// LOG SUCCESS
// ===========================
error_log("=== EMPLOYEE ADDED SUCCESSFULLY ===");
error_log("ID: $employee_id");
error_log("Code: $employee_code");
error_log("Name: $name");
error_log("Phone: $phone");
error_log("Roles: " . implode(', ', $roles));
error_log("Photo: " . ($photo_name ? $photo_name : 'None'));
error_log("===================================");
?>