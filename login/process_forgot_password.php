<?php
session_start();
require_once '../db.php';

// Fungsi untuk generate kode OTP 6 digit
function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Step 1: Verifikasi nomor telepon dan kirim OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] === '1') {
    $phone = trim($_POST['phone']);

    if (empty($phone)) {
        header("Location: forgot_password.php?error=empty");
        exit();
    }

    // Cek apakah nomor telepon terdaftar
    $stmt = $conn->prepare("SELECT id_customer, name, phone FROM customers WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: forgot_password.php?error=not_found");
        exit();
    }

    $customer = $result->fetch_assoc();

    // Generate OTP (6 digit)
    $otp = generateOTP();
    $otp_expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));

    // Simpan OTP ke database
    $update = $conn->prepare("UPDATE customers SET otp_code = ?, otp_expires_at = ? WHERE phone = ?");
    $update->bind_param("sss", $otp, $otp_expiry, $phone);
    $update->execute();

    // Simpan ke session
    $_SESSION['forgot_password'] = [
        'phone' => $phone,
        'code' => $otp, // Untuk ditampilkan di layar (development mode)
        'customer_id' => $customer['id_customer'],
        'name' => $customer['name']
    ];

    // ===================================
    // KIRIM WHATSAPP VIA FONNTE API
    // ===================================
    
    $fonnte_token = 'YOUR_FONNTE_TOKEN'; // GANTI dengan token Fonnte Anda
    
    // Format nomor (pastikan dimulai dengan 62 untuk Indonesia)
    $wa_number = $phone;
    if (substr($wa_number, 0, 1) === '0') {
        $wa_number = '62' . substr($wa_number, 1);
    }
    
    // Pesan WhatsApp
    $message = "🔐 *KODE OTP RESET PASSWORD*\n\n";
    $message .= "Halo *{$customer['name']}*,\n\n";
    $message .= "Kode OTP untuk reset password:\n";
    $message .= "*{$otp}*\n\n";
    $message .= "⏱️ Berlaku selama 15 menit\n";
    $message .= "⚠️ Jangan berikan kode ini kepada siapapun!\n\n";
    $message .= "_SengkuClean - Layanan Pembersihan Sepatu_";

    // Kirim via Fonnte API
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => [
            'target' => $wa_number,
            'message' => $message,
            'countryCode' => '62'
        ],
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $fonnte_token
        ],
    ]);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    // Log untuk debugging
    error_log("=== FORGOT PASSWORD OTP ===");
    error_log("Phone: {$phone}");
    error_log("OTP: {$otp}");
    error_log("WhatsApp Response: " . $response);
    error_log("===========================");

    // Redirect ke step 2 (tampilkan form verifikasi)
    // Kode OTP akan ditampilkan di layar untuk development
    header("Location: forgot_password.php?step=2&phone=" . urlencode($phone) . "&success=code_sent");
    exit();
}

// Step 2: Verifikasi OTP dan reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] === '2') {
    $phone = trim($_POST['phone']);
    $verification_code = trim($_POST['verification_code']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validasi input
    if (empty($verification_code) || empty($new_password) || empty($confirm_password)) {
        header("Location: forgot_password.php?step=2&phone=" . urlencode($phone) . "&error=empty");
        exit();
    }

    // Cek panjang password
    if (strlen($new_password) < 6) {
        header("Location: forgot_password.php?step=2&phone=" . urlencode($phone) . "&error=weak_password");
        exit();
    }

    // Cek kecocokan password
    if ($new_password !== $confirm_password) {
        header("Location: forgot_password.php?step=2&phone=" . urlencode($phone) . "&error=password_mismatch");
        exit();
    }

    // Ambil OTP dari database
    $stmt = $conn->prepare("SELECT otp_code, otp_expires_at, id_customer FROM customers WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: forgot_password.php?error=not_found");
        exit();
    }

    $customer = $result->fetch_assoc();

    // Cek apakah OTP sudah kadaluarsa
    if (strtotime($customer['otp_expires_at']) < time()) {
        header("Location: forgot_password.php?step=2&phone=" . urlencode($phone) . "&error=expired");
        exit();
    }

    // Verifikasi OTP
    if ($customer['otp_code'] !== $verification_code) {
        header("Location: forgot_password.php?step=2&phone=" . urlencode($phone) . "&error=invalid_code");
        exit();
    }

    // OTP VALID! Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password dan hapus OTP
    $stmt = $conn->prepare("UPDATE customers SET password = ?, otp_code = NULL, otp_expires_at = NULL, updated_at = CURRENT_TIMESTAMP WHERE id_customer = ?");
    $stmt->bind_param("si", $hashed_password, $customer['id_customer']);

    if ($stmt->execute()) {
        // Hapus session
        unset($_SESSION['forgot_password']);

        // Log success
        error_log("Password berhasil direset untuk phone: {$phone}");

        // Redirect ke login dengan pesan sukses
        header("Location: login.php?success=password_reset");
        exit();
    } else {
        header("Location: forgot_password.php?step=2&phone=" . urlencode($phone) . "&error=database");
        exit();
    }
}

// Jika akses langsung tanpa POST
header("Location: forgot_password.php");
exit();
?>