<?php
/**
 * get_dynamic_estimate.php - HYBRID LOGIC
 * 
 * 1. QUEUE SYSTEM:
 *    - Durasi dasar x1 untuk pesanan 1-3 (3 hari)
 *    - Durasi dasar x2 untuk pesanan 4-6 (6 hari)
 *    - Durasi dasar x3 untuk pesanan 7-9 (9 hari)
 *    - dst...
 *
 * 2. AUTO RESET OVERDUE:
 *    - Jika ada pesanan dengan status != 6 (belum selesai)
 *    - DAN est_finish_date < TODAY (LEWAT DEADLINE)
 *    - MAKA: Reset multiplier ke 1 (durasi kembali ke dasar)
 */

include('../db.php');

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = intval($_POST['service_id'] ?? 0);
    $trans_date = $_POST['trans_date'] ?? date('Y-m-d');
    $exclude_drop_id = intval($_POST['exclude_drop_id'] ?? 0);
    
    if ($service_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Service ID tidak valid']);
        exit;
    }
    
    try {
        // ===================================================
        // STEP 1: Ambil durasi service
        // ===================================================
        $stmt_service = $conn->prepare("SELECT category, service_name, duration, price_min FROM services WHERE id_service = ?");
        if (!$stmt_service) throw new Exception("Prepare failed: " . $conn->error);
        
        $stmt_service->bind_param("i", $service_id);
        $stmt_service->execute();
        $result_service = $stmt_service->get_result();
        
        if ($result_service->num_rows === 0) {
            throw new Exception("Service tidak ditemukan");
        }
        
        $service = $result_service->fetch_assoc();
        $category = $service['category'];
        $service_name = $service['service_name'];
        $base_duration = intval($service['duration']);
        $price_min = floatval($service['price_min']);
        $stmt_service->close();

        error_log("=== GET DYNAMIC ESTIMATE ===");
        error_log("Service: {$category} - {$service_name}, Base Duration: {$base_duration} hari");
        
        // ===================================================
        // STEP 2: Deteksi OVERDUE
        // ===================================================
        $today = date('Y-m-d');
        
        $sql_overdue = "
            SELECT COUNT(*) as overdue_count
            FROM drops d
            WHERE d.service_id = ?
            AND d.status_id != 6
            AND d.est_finish_date < ?
            AND d.id_drop != ?
        ";
        
        $stmt_overdue = $conn->prepare($sql_overdue);
        if (!$stmt_overdue) throw new Exception("Prepare failed: " . $conn->error);
        
        $stmt_overdue->bind_param("isi", $service_id, $today, $exclude_drop_id);
        $stmt_overdue->execute();
        $result_overdue = $stmt_overdue->get_result();
        $overdue_data = $result_overdue->fetch_assoc();
        $overdue_count = intval($overdue_data['overdue_count']);
        $stmt_overdue->close();

        error_log("Overdue count: {$overdue_count}");
        
        $has_overdue = $overdue_count > 0;

        // ===================================================
        // STEP 3: Hitung queue - JIKA ADA OVERDUE, RESET MULTIPLIER
        // ===================================================
        
        if ($has_overdue) {
            // ❌ ADA PESANAN OVERDUE
            // RESET MULTIPLIER KE 1 (durasi kembali ke dasar)
            error_log("🔴 OVERDUE DETECTED! Resetting multiplier to 1");
            
            $multiplier = 1;
            $queue_info = "⚠️ Ada pesanan yang lewat deadline - Queue direset!";
            
        } else {
            // ✅ TIDAK ADA OVERDUE
            // Hitung queue normal: setiap 3 pesanan = +1 multiplier
            
            $sql_count = "
                SELECT COUNT(*) as active_count
                FROM drops d
                WHERE d.service_id = ?
                AND d.status_id != 6
                AND d.id_drop != ?
            ";
            
            $stmt_count = $conn->prepare($sql_count);
            if (!$stmt_count) throw new Exception("Prepare failed: " . $conn->error);
            
            $stmt_count->bind_param("ii", $service_id, $exclude_drop_id);
            $stmt_count->execute();
            $result_count = $stmt_count->get_result();
            $count_data = $result_count->fetch_assoc();
            $active_count = intval($count_data['active_count']);
            $stmt_count->close();

            error_log("Active pesanan (sama service): {$active_count}");
            
            // Kalkulasi multiplier: setiap 3 pesanan = +1x durasi
            // 0 pesanan → multiplier = 1 (1x durasi dasar)
            // 1-2 pesanan → multiplier = 1 (1x durasi dasar)
            // 3-5 pesanan → multiplier = 2 (2x durasi dasar)
            // 6-8 pesanan → multiplier = 3 (3x durasi dasar)
            $multiplier = floor($active_count / 3) + 1;
            
            error_log("Multiplier: {$multiplier}x");
            
            if ($active_count > 0) {
                $queue_position = $active_count + 1;
                $queue_info = "Posisi antrian: #{$queue_position} | Pesanan aktif: {$active_count} | Durasi: {$base_duration} x{$multiplier}";
            } else {
                $queue_info = "Tidak ada antrian";
            }
        }

        // ===================================================
        // STEP 4: Hitung total durasi dan tanggal estimasi
        // ===================================================
        
        $final_duration = $base_duration * $multiplier;
        
        error_log("Final duration: {$final_duration} hari (base={$base_duration} x multiplier={$multiplier})");
        
        // Tanggal estimasi selesai
        $estimate_date = date('Y-m-d', strtotime($trans_date . " +{$final_duration} days"));
        
        error_log("Estimate date: {$estimate_date}");
        
        // Format deskripsi
        $estimate_desc = $final_duration > 0 ? "{$final_duration} Hari" : "-";
        $service_display = ucfirst($category) . " - " . ucfirst($service_name);

        // ===================================================
        // STEP 5: Return response
        // ===================================================
        
        echo json_encode([
            'success' => true,
            'duration' => $final_duration,
            'estimate_desc' => $estimate_desc,
            'estimate_date' => $estimate_date,
            'price_min' => $price_min,
            'category' => $category,
            'service_name' => $service_name,
            'service_display' => $service_display,
            'base_duration' => $base_duration,
            'multiplier' => $multiplier,
            'queue_info' => $queue_info,
            'has_overdue' => $has_overdue,
            'overdue_count' => $overdue_count
        ]);
        
    } catch (Exception $e) {
        error_log("❌ ERROR: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>