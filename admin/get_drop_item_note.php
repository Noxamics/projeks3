<?php
include('../db.php');
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $drop_id = intval($_POST['drop_id'] ?? 0);

    if ($drop_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Drop ID tidak valid'
        ]);
        exit;
    }

    try {
        // ===== AMBIL NOTE DARI DROP_ITEMS (ambil yang pertama/item_order=1) =====
        $stmt = $conn->prepare("
            SELECT notes 
            FROM drop_items 
            WHERE drop_id = ? AND item_order = 1
            LIMIT 1
        ");
        
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        
        $stmt->bind_param("i", $drop_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $item_note = '';
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $item_note = $row['notes'] ?? '';
        }
        $stmt->close();

        echo json_encode([
            'success' => true,
            'drop_id' => $drop_id,
            'item_note' => $item_note
        ]);

    } catch (Exception $e) {
        error_log("ERROR: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

    $conn->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>