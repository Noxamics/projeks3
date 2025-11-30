<?php
include('../db.php');
header('Content-Type: application/json');

// Tambahkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Matikan display_errors untuk JSON response

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari request body atau POST parameter
    $ids = isset($_POST['ids']) ? $_POST['ids'] : '';
    
    // Jika kosong, coba ambil dari raw input (untuk JSON request)
    if (empty($ids)) {
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = isset($input['ids']) ? $input['ids'] : '';
    }
    
    if (empty($ids)) {
        echo json_encode([
            'success' => false, 
            'message' => 'ID tidak ditemukan atau tidak valid'
        ]);
        exit;
    }
    
    // Parse IDs (bisa berupa string "1,2,3" atau array)
    if (is_string($ids)) {
        $idsArray = array_map('intval', explode(',', $ids));
    } else if (is_array($ids)) {
        $idsArray = array_map('intval', $ids);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Format ID tidak valid'
        ]);
        exit;
    }
    
    // Filter untuk memastikan semua ID valid (> 0)
    $idsArray = array_filter($idsArray, function($id) {
        return $id > 0;
    });
    
    if (empty($idsArray)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Tidak ada ID valid untuk dihapus'
        ]);
        exit;
    }
    
    // Mulai transaksi
    $conn->begin_transaction();
    
    try {
        $deletedCount = 0;
        
        foreach ($idsArray as $id) {
            // 1. Hapus data terkait dari tabel 'payments'
            $stmt_payment = $conn->prepare("DELETE FROM payments WHERE drop_id = ?");
            if (!$stmt_payment) {
                throw new Exception("Prepare payments failed: " . $conn->error);
            }
            $stmt_payment->bind_param("i", $id);
            if (!$stmt_payment->execute()) {
                throw new Exception("Delete payments failed for ID {$id}: " . $stmt_payment->error);
            }
            $stmt_payment->close();
            
            // 2. Hapus data terkait dari tabel 'deadlines'
            $stmt_deadline = $conn->prepare("DELETE FROM deadlines WHERE drop_id = ?");
            if (!$stmt_deadline) {
                throw new Exception("Prepare deadlines failed: " . $conn->error);
            }
            $stmt_deadline->bind_param("i", $id);
            if (!$stmt_deadline->execute()) {
                throw new Exception("Delete deadlines failed for ID {$id}: " . $stmt_deadline->error);
            }
            $stmt_deadline->close();
            
            // 3. Hapus data terkait dari tabel 'drop_items'
            $stmt_items = $conn->prepare("DELETE FROM drop_items WHERE drop_id = ?");
            if (!$stmt_items) {
                throw new Exception("Prepare drop_items failed: " . $conn->error);
            }
            $stmt_items->bind_param("i", $id);
            if (!$stmt_items->execute()) {
                throw new Exception("Delete drop_items failed for ID {$id}: " . $stmt_items->error);
            }
            $stmt_items->close();
            
            // 4. Hapus data utama dari tabel 'drops'
            $stmt_drop = $conn->prepare("DELETE FROM drops WHERE id_drop = ?");
            if (!$stmt_drop) {
                throw new Exception("Prepare drops failed: " . $conn->error);
            }
            $stmt_drop->bind_param("i", $id);
            if (!$stmt_drop->execute()) {
                throw new Exception("Delete drops failed for ID {$id}: " . $stmt_drop->error);
            }
            
            if ($stmt_drop->affected_rows > 0) {
                $deletedCount++;
            }
            $stmt_drop->close();
        }
        
        // Commit transaksi jika semua berhasil
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Berhasil menghapus {$deletedCount} data"
        ]);
        
    } catch (Exception $e) {
        // Rollback jika terjadi error
        $conn->rollback();
        
        // Log error untuk debugging
        error_log("DELETE ERROR: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menghapus data: ' . $e->getMessage()
        ]);
    }
    
    $conn->close();
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Method request tidak valid'
    ]);
}
?>