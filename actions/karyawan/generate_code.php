<?php
/**
 * =============================================
 * FILE: actions/karyawan/generate_code.php
 * DESKRIPSI: Generate kode karyawan otomatis
 * =============================================
 */

header('Content-Type: application/json');

// Include database connection
require_once '../../db.php'; // Sesuaikan path database Anda

try {
    // Query untuk mendapatkan kode terakhir
    $query = "SELECT employee_code FROM employees ORDER BY employee_code DESC LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $lastCode = $row['employee_code'];

        // Extract angka dari kode (misal: EMP001 -> 001)
        preg_match('/(\d+)$/', $lastCode, $matches);

        if (isset($matches[1])) {
            $number = intval($matches[1]) + 1;
            $newCode = 'EMP' . str_pad($number, 3, '0', STR_PAD_LEFT);
        } else {
            $newCode = 'EMP001';
        }
    } else {
        // Jika belum ada data, mulai dari EMP001
        $newCode = 'EMP001';
    }

    echo json_encode([
        'success' => true,
        'code' => $newCode,
        'message' => 'Kode berhasil di-generate'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'code' => 'EMP001',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>