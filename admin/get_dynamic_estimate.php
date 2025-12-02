<?php
include('../db.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = intval($_POST['service_id'] ?? 0);
    $trans_date = $_POST['trans_date'] ?? date('Y-m-d');
    $exclude_drop_id = intval($_POST['exclude_drop_id'] ?? 0); // Untuk edit mode
    
    if ($service_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Service ID tidak valid']);
        exit;
    }
    
    try {
        // 1. Ambil data service (kategori, nama layanan, durasi dasar, harga)
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
        
        // 2. Hitung jumlah pesanan AKTIF dengan SERVICE_ID yang SAMA (kategori + layanan spesifik)
        // Status "aktif" = semua status kecuali "Selesai" dan "Diambil"
        $sql_count = "
            SELECT COUNT(*) as active_count
            FROM drops d
            JOIN statuses st ON d.status_id = st.id_status
            WHERE d.service_id = ?
            AND st.status_name NOT IN ('Selesai', 'Diambil')
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
        
        // 3. LOGIKA PERHITUNGAN ESTIMASI DINAMIS
        // Setiap 3 pesanan aktif dengan layanan yang SAMA, durasi bertambah 1x durasi dasar
        // Contoh: durasi dasar = 3 hari untuk "Cleaning - Deep"
        // - Pesanan 1-3 (Cleaning - Deep): 3 hari
        // - Pesanan 4-6 (Cleaning - Deep): 6 hari (3 + 3)
        // - Pesanan 7-9 (Cleaning - Deep): 9 hari (3 + 3 + 3)
        
        // Sementara "Cleaning - Unyellowing" punya antrian terpisah:
        // - Pesanan 1-3 (Cleaning - Unyellowing): durasi dasarnya sendiri
        // - dst.
        
        $multiplier = floor($active_count / 3) + 1;
        $final_duration = $base_duration * $multiplier;
        
        // 4. Hitung tanggal estimasi selesai
        $estimate_date = date('Y-m-d', strtotime($trans_date . " +{$final_duration} days"));
        
        // 5. Format deskripsi estimasi
        $estimate_desc = $final_duration > 0 ? "{$final_duration} Hari" : "-";
        
        // 6. Info tambahan untuk user
        $queue_info = "";
        $service_display = ucfirst($category) . " - " . ucfirst($service_name);
        
        if ($active_count > 0) {
            $queue_position = $active_count + 1;
            $queue_info = "Posisi antrian: #{$queue_position} | Pesanan aktif '{$service_display}': {$active_count}";
        } else {
            $queue_info = "Tidak ada antrian untuk '{$service_display}'";
        }
        
        echo json_encode([
            'success' => true,
            'duration' => $final_duration,
            'estimate_desc' => $estimate_desc,
            'estimate_date' => $estimate_date,
            'price_min' => $price_min,
            'active_count' => $active_count,
            'queue_info' => $queue_info,
            'category' => $category,
            'service_name' => $service_name,
            'service_display' => $service_display,
            'base_duration' => $base_duration,
            'multiplier' => $multiplier
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>