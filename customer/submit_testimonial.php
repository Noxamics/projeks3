<?php
require_once '../db.php';
require_once 'check_auth.php';

// Cek login customer
$userData = getUserData();
$customerId = $userData['id'];

// Pastikan metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $rating = intval($_POST['rating']);
    $testimonial = trim($_POST['testimonial']);

    if ($rating < 1 || $rating > 5) {
        die("Rating tidak valid.");
    }

    if (empty($testimonial)) {
        die("Testimoni tidak boleh kosong.");
    }

    // Simpan ke database
    $stmt = $conn->prepare("INSERT INTO customer_testimonials (customer_id, rating, testimonial) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $customerId, $rating, $testimonial);

    if ($stmt->execute()) {
        header("Location: dashboard.php?testimonial_success=1");
        exit;
    } else {
        echo "Gagal menyimpan testimoni: " . $conn->error;
    }
}
