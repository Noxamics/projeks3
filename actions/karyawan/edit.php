<?php
/**
 * Edit Employee Action - COMPLETE VERSION
 * Location: actions/karyawan/edit.php
 * 
 * Process: Validate → Update Photo (if uploaded) → Update DB → Update Roles → Return JSON
 */

// Prevent direct access
if (!isset($_POST['id_employee'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid access']);
    exit;
}

// Security headers
header('Content-Type: application/json; charset=utf-8');

// Include database connection
require_once('../../db.php');

try {
    // ===========================
    // VALIDATE REQUIRED FIELDS
    // ===========================
    $required = ['id_employee', 'name', 'phone', 'join_date', 'status'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field {$field} wajib diisi!");
        }
    }

    // Validate roles
    if (empty($_POST['roles']) || !is_array($_POST['roles'])) {
        throw new Exception('Pilih minimal 1 role untuk karyawan!');
    }

    // ===========================
    // SANITIZE INPUTS
    // ===========================

    $id_employee = intval($_POST['id_employee']);
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $join_date = $_POST['join_date'];
    $status = $_POST['status'];
    $roles = $_POST['roles'];

    // Optional fields
    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
    $address = !empty($_POST['address']) ? trim($_POST['address']) : null;
    $birth_date = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
    $emergency_contact = !empty($_POST['emergency_contact']) ? trim($_POST['emergency_contact']) : null;
    $base_salary = !empty($_POST['base_salary']) ? floatval($_POST['base_salary']) : 0.00;

    // Password (optional - only update if provided)
    $password = !empty($_POST['password']) ? trim($_POST['password']) : null;

    // ===========================
    // VALIDATE INPUT FORMATS
    // ===========================

    // Validate phone (10-15 digits)
    if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        throw new Exception('Nomor HP harus 10-15 digit angka!');
    }

    // Validate email if provided
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Format email tidak valid!');
    }

    // Validate emergency contact if provided
    if ($emergency_contact && !preg_match('/^[0-9]{10,15}$/', $emergency_contact)) {
        throw new Exception('Kontak darurat harus 10-15 digit angka!');
    }

    // Validate base_salary
    if ($base_salary < 0) {
        throw new Exception('Gaji pokok tidak boleh negatif!');
    }

    // Validate status
    $valid_statuses = ['Aktif', 'Cuti', 'Non-Aktif'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Status tidak valid!');
    }

    // Validate roles
    $valid_roles = ['cleaning', 'reglue', 'repaint', 'kasir'];
    foreach ($roles as $role) {
        if (!in_array($role, $valid_roles)) {
            throw new Exception("Role '{$role}' tidak valid!");
        }
    }

    // Validate password length if provided
    if ($password && strlen($password) < 6) {
        throw new Exception('Password minimal 6 karakter!');
    }

    // ===========================
    // CHECK IF EMPLOYEE EXISTS
    // ===========================

    $stmt = $conn->prepare("SELECT employee_code, photo FROM employees WHERE id_employee = ?");
    $stmt->bind_param("i", $id_employee);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        throw new Exception('Karyawan tidak ditemukan!');
    }

    $current_employee = $result->fetch_assoc();
    $employee_code = $current_employee['employee_code'];
    $current_photo = $current_employee['photo'];
    $stmt->close();

    // ===========================
    // HANDLE PHOTO UPLOAD (OPTIONAL)
    // ===========================
    $photo_name = $current_photo; // Keep current photo by default
    $upload_path = __DIR__ . '/../../uploads/employee/';

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception('Format foto tidak valid! Gunakan JPG, JPEG, atau PNG');
        }

        // Validate file size (2MB max)
        if ($file['size'] > 2 * 1024 * 1024) {
            throw new Exception('Ukuran foto terlalu besar! Maximum 2MB');
        }

        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
            $extension = 'jpg';
        }

        $photo_name = 'emp_' . $employee_code . '_' . time() . '.' . $extension;
        $target_path = $upload_path . $photo_name;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            throw new Exception('Gagal mengupload foto!');
        }

        // Set proper permissions
        chmod($target_path, 0644);

        // Delete old photo if exists and different
        if ($current_photo && $current_photo !== $photo_name) {
            $old_photo_path = $upload_path . $current_photo;
            if (file_exists($old_photo_path)) {
                unlink($old_photo_path);
                error_log("Old photo deleted: $current_photo");
            }
        }

        error_log("Photo uploaded successfully: $photo_name");
    }

    // ===========================
    // HASH PASSWORD IF PROVIDED
    // ===========================
    $hashed_password = null;
    if ($password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    }

    // ===========================
    // UPDATE DATABASE
    // ===========================
    $conn->begin_transaction();

    try {
        // Build UPDATE query dynamically based on password
        if ($hashed_password) {
            // Update with password
            $stmt = $conn->prepare(
                "UPDATE employees SET 
                    name = ?,
                    phone = ?,
                    email = ?,
                    address = ?,
                    birth_date = ?,
                    emergency_contact = ?,
                    base_salary = ?,
                    password = ?,
                    join_date = ?,
                    status = ?,
                    photo = ?,
                    updated_at = NOW()
                WHERE id_employee = ?"
            );

            $stmt->bind_param(
                "ssssssdssssi",
                $name,
                $phone,
                $email,
                $address,
                $birth_date,
                $emergency_contact,
                $base_salary,
                $hashed_password,
                $join_date,
                $status,
                $photo_name,
                $id_employee
            );
        } else {
            // Update without password
            $stmt = $conn->prepare(
                "UPDATE employees SET 
                    name = ?,
                    phone = ?,
                    email = ?,
                    address = ?,
                    birth_date = ?,
                    emergency_contact = ?,
                    base_salary = ?,
                    join_date = ?,
                    status = ?,
                    photo = ?,
                    updated_at = NOW()
                WHERE id_employee = ?"
            );

            $stmt->bind_param(
                "ssssssdsssi",
                $name,
                $phone,
                $email,
                $address,
                $birth_date,
                $emergency_contact,
                $base_salary,
                $join_date,
                $status,
                $photo_name,
                $id_employee
            );
        }

        if (!$stmt->execute()) {
            throw new Exception("Gagal mengupdate data karyawan: " . $stmt->error);
        }

        $stmt->close();

        // 2. Delete existing roles
        $stmt = $conn->prepare("DELETE FROM employee_roles WHERE employee_id = ?");
        $stmt->bind_param("i", $id_employee);
        if (!$stmt->execute()) {
            throw new Exception("Gagal menghapus role lama: " . $stmt->error);
        }
        $stmt->close();

        // 3. Insert new roles
        $stmt = $conn->prepare("INSERT INTO employee_roles (employee_id, role_type) VALUES (?, ?)");

        foreach ($roles as $role) {
            $stmt->bind_param("is", $id_employee, $role);
            if (!$stmt->execute()) {
                throw new Exception("Gagal menyimpan role '{$role}': " . $stmt->error);
            }
        }

        $stmt->close();

        // 4. Commit transaction
        $conn->commit();

        error_log("=== EMPLOYEE UPDATED SUCCESSFULLY ===");
        error_log("ID: $id_employee");
        error_log("Code: $employee_code");
        error_log("Name: $name");
        error_log("Roles: " . implode(', ', $roles));
        error_log("Password Changed: " . ($password ? 'Yes' : 'No'));
        error_log("Photo Changed: " . ($photo_name !== $current_photo ? 'Yes' : 'No'));
        error_log("===================================");

        // Return JSON success
        echo json_encode([
            'success' => true,
            'message' => 'Data karyawan berhasil diupdate!',
            'data' => [
                'id' => $id_employee,
                'employee_code' => $employee_code,
                'name' => $name
            ]
        ]);

    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();

        // Delete uploaded photo if exists and different from current
        if ($photo_name !== $current_photo && file_exists($upload_path . $photo_name)) {
            unlink($upload_path . $photo_name);
            error_log("Rolled back: New photo deleted");
        }

        throw $e;
    }

} catch (Exception $e) {
    // Return JSON error
    error_log("Error updating employee: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>