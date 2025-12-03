<?php

if (!isset($_POST['employee_code'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid access']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once('../../db.php');

try {
    // Validate required fields
    $required = ['name', 'phone', 'join_date', 'password'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field {$field} wajib diisi.");
        }
    }

    // Validate roles
    if (empty($_POST['roles']) || !is_array($_POST['roles'])) {
        throw new Exception('Pilih minimal satu role untuk karyawan.');
    }

    // Sanitize inputs
    $employee_code = strtoupper(trim($_POST['employee_code']));
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $join_date = $_POST['join_date'];
    $status = $_POST['status'];
    $password = $_POST['password'];
    $roles = $_POST['roles'];

    // Optional fields
    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
    $address = !empty($_POST['address']) ? trim($_POST['address']) : null;
    $birth_date = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
    $emergency_contact = !empty($_POST['emergency_contact']) ? trim($_POST['emergency_contact']) : null;
    $base_salary = !empty($_POST['base_salary']) ? floatval($_POST['base_salary']) : 0.00;

    // Validate formats
    if (!preg_match('/^[A-Z0-9]+$/', $employee_code)) {
        throw new Exception('Kode karyawan harus berupa huruf kapital dan angka.');
    }

    if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        throw new Exception('Nomor HP harus terdiri dari 10-15 digit angka.');
    }

    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Format email tidak valid.');
    }

    if ($emergency_contact && !preg_match('/^[0-9]{10,15}$/', $emergency_contact)) {
        throw new Exception('Kontak darurat harus 10-15 digit angka.');
    }

    if ($base_salary < 0) {
        throw new Exception('Gaji pokok tidak boleh negatif.');
    }

    $valid_statuses = ['Aktif', 'Cuti', 'Non-Aktif'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Status tidak valid.');
    }

    $valid_roles = ['cleaning', 'reglue', 'repaint', 'kasir'];
    foreach ($roles as $role) {
        if (!in_array($role, $valid_roles)) {
            throw new Exception("Role '{$role}' tidak valid.");
        }
    }

    if (strlen($password) < 6) {
        throw new Exception('Password minimal terdiri dari 6 karakter.');
    }

    // Check duplicate employee code
    $stmt = $conn->prepare("SELECT id_employee FROM employees WHERE employee_code = ?");
    $stmt->bind_param("s", $employee_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stmt->close();
        throw new Exception("Kode karyawan '{$employee_code}' sudah digunakan.");
    }
    $stmt->close();

    // Handle photo upload
    $photo_name = null;
    $upload_path = __DIR__ . '/../../uploads/employee/';

    if (!is_dir($upload_path)) {
        if (!mkdir($upload_path, 0755, true)) {
            throw new Exception('Gagal membuat direktori upload.');
        }
    }

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception('Format foto tidak valid. Gunakan JPG, JPEG, atau PNG.');
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            throw new Exception('Ukuran foto terlalu besar. Maksimal 2MB.');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
            $extension = 'jpg';
        }

        $photo_name = 'emp_' . $employee_code . '_' . time() . '.' . $extension;
        $target_path = $upload_path . $photo_name;

        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            throw new Exception('Gagal mengupload foto.');
        }

        chmod($target_path, 0644);
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert to database
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(
            "INSERT INTO employees (
                employee_code, name, phone, email, address,
                birth_date, emergency_contact, base_salary, password,
                join_date, status, photo, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );

        $stmt->bind_param(
            "ssssssdsssss",
            $employee_code,
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
            $photo_name
        );

        if (!$stmt->execute()) {
            throw new Exception("Gagal menyimpan data karyawan: " . $stmt->error);
        }

        $employee_id = $conn->insert_id;
        $stmt->close();

        // Insert roles
        $stmt = $conn->prepare("INSERT INTO employee_roles (employee_id, role_type) VALUES (?, ?)");
        foreach ($roles as $role) {
            $stmt->bind_param("is", $employee_id, $role);
            if (!$stmt->execute()) {
                throw new Exception("Gagal menyimpan role '{$role}': " . $stmt->error);
            }
        }
        $stmt->close();

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Karyawan berhasil ditambahkan.',
            'data' => [
                'id' => $employee_id,
                'employee_code' => $employee_code,
                'name' => $name
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollback();

        if ($photo_name && file_exists($upload_path . $photo_name)) {
            unlink($upload_path . $photo_name);
        }

        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>