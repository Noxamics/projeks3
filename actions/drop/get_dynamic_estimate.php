<?php
// File: /actions/drop/get_dynamic_estimate.php
// Calculate dynamic estimate based on service queue and workload

session_start();
require_once '../../db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

try {
    // Validate required fields
    if (empty($_POST['service_id']) || empty($_POST['trans_date'])) {
        throw new Exception('Data tidak lengkap');
    }

    $service_id = intval($_POST['service_id']);
    $trans_date = $_POST['trans_date'];
    $exclude_drop_id = isset($_POST['exclude_drop_id']) ? intval($_POST['exclude_drop_id']) : 0;

    // === 1. GET SERVICE INFO ===
    $stmt = $conn->prepare("
        SELECT price_min, duration, service_name, category 
        FROM services 
        WHERE id_service = ?
    ");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Service tidak ditemukan');
    }

    $service = $result->fetch_assoc();
    $base_duration = intval($service['duration']);
    $price_min = floatval($service['price_min']);

    // === 2. CHECK FOR OVERDUE ORDERS ===
    $today = date('Y-m-d');

    $stmt = $conn->prepare("
        SELECT COUNT(*) as overdue_count
        FROM drop_items di
        WHERE di.service_id = ?
        AND di.est_finish_date < ?
        AND di.status_id NOT IN (6)
    ");
    $stmt->bind_param("is", $service_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $overdue_data = $result->fetch_assoc();
    $has_overdue = ($overdue_data['overdue_count'] > 0);

    // === 3. CALCULATE QUEUE ===
    // Count pending items with same service (not completed)
    $query = "
        SELECT COUNT(*) as queue_count
        FROM drop_items di
        WHERE di.service_id = ?
        AND di.status_id NOT IN (6)
    ";

    $params = [$service_id];
    $types = "i";

    if ($exclude_drop_id > 0) {
        $query .= " AND di.drop_id != ?";
        $params[] = $exclude_drop_id;
        $types .= "i";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $queue_data = $result->fetch_assoc();
    $queue_count = intval($queue_data['queue_count']);

    // === 4. CALCULATE ADDITIONAL DAYS BASED ON QUEUE ===
    // For every 5 items in queue, add 1 day
    $additional_days = floor($queue_count / 5);

    // If there are overdue items, reset queue calculation
    if ($has_overdue) {
        $additional_days = max($additional_days, 2); // Minimum 2 days delay
    }

    // === 5. CALCULATE FINAL DURATION ===
    $final_duration = $base_duration + $additional_days;

    // === 6. CALCULATE ESTIMATE DATE ===
    $date = new DateTime($trans_date);
    $date->modify("+{$final_duration} days");
    $estimate_date = $date->format('Y-m-d');

    // === 7. BUILD DESCRIPTION ===
    $estimate_desc = $final_duration . " Hari";

    if ($queue_count > 0) {
        $estimate_desc .= " (Antrian: {$queue_count})";
    }

    // === 8. BUILD RESPONSE ===
    $response = [
        'success' => true,
        'service_id' => $service_id,
        'service_name' => ucfirst($service['category']) . ' - ' . ucfirst($service['service_name']),
        'price_min' => $price_min,
        'base_duration' => $base_duration,
        'additional_days' => $additional_days,
        'duration' => $final_duration,
        'estimate_desc' => $estimate_desc,
        'estimate_date' => $estimate_date,
        'queue_count' => $queue_count,
        'has_overdue' => $has_overdue,
        'overdue_count' => $overdue_data['overdue_count']
    ];

    // Add queue info message
    if ($queue_count > 0) {
        $response['queue_info'] = "Terdapat {$queue_count} pesanan dalam antrian untuk layanan ini";
    }

    if ($has_overdue) {
        $response['overdue_info'] = "Terdapat {$overdue_data['overdue_count']} pesanan overdue untuk layanan ini";
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>