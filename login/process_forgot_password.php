<?php
session_start();
require_once '../db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fungsi untuk generate kode OTP 6 digit
function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Fungsi untuk kirim email OTP via PHPMailer
function sendEmailOTP($email, $otp, $customer_name) {
    $mail = new PHPMailer(true);

    try {
        // ============================================
        // KONFIGURASI SMTP GMAIL
        // ============================================
        // GANTI dengan email & app password Gmail Anda
        $gmail_email = 'noxamics@gmail.com';        // GANTI SINI
        $gmail_password = 'gtytdzqchodvqevl';       // GANTI SINI (App Password)
        
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $gmail_email;
        $mail->Password = $gmail_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // ============================================
        // SENDER & RECIPIENT
        // ============================================
        $mail->setFrom($gmail_email, 'SengkuClean');
        $mail->addAddress($email);

        // ============================================
        // KONTEN EMAIL HTML
        // ============================================
        $mail->isHTML(true);
        $mail->Subject = 'Kode Verifikasi Reset Password - SengkuClean';
        
        $mail->Body = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6; 
            color: #333; 
            margin: 0; 
            padding: 0; 
            background: #f5f5f5; 
        }
        .container { 
            max-width: 500px; 
            margin: 20px auto; 
            padding: 0; 
        }
        .email-wrapper { 
            background: white; 
            border-radius: 8px; 
            overflow: hidden; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        .header { 
            background: linear-gradient(135deg, #004d9d, #0062cc); 
            color: white; 
            padding: 30px 20px; 
            text-align: center; 
        }
        .header h2 { 
            margin: 0; 
            font-size: 24px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .content { 
            padding: 30px 20px; 
        }
        .greeting { 
            margin-bottom: 20px; 
        }
        .greeting p {
            margin: 10px 0;
            line-height: 1.6;
        }
        .otp-box { 
            background: linear-gradient(135deg, #f0f7ff, #e8f4ff);
            border: 2px solid #004d9d; 
            padding: 30px; 
            text-align: center; 
            border-radius: 8px; 
            margin: 25px 0;
        }
        .otp-label {
            font-size: 12px;
            color: #004d9d;
            font-weight: 600;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .otp-code { 
            font-size: 44px; 
            font-weight: 700; 
            letter-spacing: 12px; 
            color: #004d9d; 
            font-family: 'Courier New', monospace;
            margin: 0;
            line-height: 1;
        }
        .expiry { 
            color: #666; 
            font-size: 13px; 
            margin-top: 20px; 
            padding-top: 15px; 
            border-top: 1px solid #ddd;
        }
        .warning-box { 
            background: #fff3e0; 
            border-left: 4px solid #ff9800; 
            padding: 15px; 
            margin: 20px 0; 
            border-radius: 4px;
        }
        .warning-box strong { 
            color: #e65100; 
            display: block;
            margin-bottom: 10px;
        }
        .warning-box ul { 
            margin: 0; 
            padding-left: 20px; 
        }
        .warning-box li { 
            margin: 6px 0; 
            color: #e65100; 
            font-size: 13px;
        }
        .footer { 
            background: #fafafa; 
            padding: 20px; 
            text-align: center; 
            color: #999; 
            font-size: 11px; 
            border-top: 1px solid #eee;
        }
        .footer p{
            margin: 5px 0;
        }
        .divider { 
            height: 1px; 
            background: #eee; 
            margin: 20px 0; 
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='email-wrapper'>
            <div class='header'>
                <h2>🔐 Verifikasi Reset Password</h2>
                <p>SengkuClean</p>
            </div>
            
            <div class='content'>
                <div class='greeting'>
                    <p>Halo <strong>{$customer_name}</strong>,</p>
                    <p>Kami menerima permintaan untuk reset password akun Anda di <strong>SengkuClean</strong>.</p>
                </div>

                <div class='divider'></div>

                <div class='otp-box'>
                    <div class='otp-label'>Kode Verifikasi Anda</div>
                    <p class='otp-code'>{$otp}</p>
                    <div class='expiry'>
                        ⏱️ Berlaku selama <strong>15 menit</strong>
                    </div>
                </div>

                <div class='warning-box'>
                    <strong>⚠️ Keamanan Penting:</strong>
                    <ul>
                        <li>Jangan bagikan kode ini kepada siapapun</li>
                        <li>SengkuClean tidak akan pernah meminta kode ini</li>
                        <li>Jika Anda tidak melakukan permintaan ini, abaikan email ini</li>
                        <li>Gunakan perangkat pribadi saat reset password</li>
                    </ul>
                </div>

                <div class='divider'></div>

                <p style='font-size: 13px; color: #666; margin: 0; text-align: center;'>
                    Pertanyaan atau butuh bantuan? Hubungi tim support kami.
                </p>
            </div>

            <div class='footer'>
                <p>© 2025 SengkuClean - Layanan Pembersihan Sepatu Premium</p>
                <p>Email ini dikirim secara otomatis, jangan balas email ini</p>
            </div>
        </div>
    </div>
</body>
</html>";

        // Kirim email
        $mail->send();
        
        error_log("=== EMAIL SENT VIA PHPMAILER ===");
        error_log("To: {$email}");
        error_log("Customer: {$customer_name}");
        error_log("OTP: {$otp}");
        error_log("Time: " . date('Y-m-d H:i:s'));
        error_log("=================================");
        
        return true;

    } catch (Exception $e) {
        error_log("=== EMAIL FAILED ===");
        error_log("To: {$email}");
        error_log("Error: " . $mail->ErrorInfo);
        error_log("Time: " . date('Y-m-d H:i:s'));
        error_log("====================");
        return false;
    }
}

// ============================================
// STEP 1: Input Nomor Telepon & Kirim OTP
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] === '1') {
    $phone = trim($_POST['phone']);

    if (empty($phone)) {
        header("Location: forgot_password.php?error=empty");
        exit();
    }

    // Cek apakah nomor telepon terdaftar
    $stmt = $conn->prepare("SELECT id_customer, name, phone, email FROM customers WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: forgot_password.php?error=not_found");
        exit();
    }

    $customer = $result->fetch_assoc();

    // Cek apakah email ada
    if (empty($customer['email'])) {
        header("Location: forgot_password.php?error=no_email");
        exit();
    }

    // Validasi format email
    if (!filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
        header("Location: forgot_password.php?error=invalid_email");
        exit();
    }

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
        'code' => $otp,
        'customer_id' => $customer['id_customer'],
        'name' => $customer['name'],
        'email' => $customer['email']
    ];

    // Kirim email OTP via PHPMailer
    $email_sent = sendEmailOTP($customer['email'], $otp, $customer['name']);

    // Redirect ke step 2
    if ($email_sent) {
        header("Location: forgot_password.php?step=2&phone=" . urlencode($phone) . "&success=code_sent");
    } else {
        // Email gagal, tapi kode tetap ditampilkan sebagai fallback
        header("Location: forgot_password.php?step=2&phone=" . urlencode($phone) . "&warning=email_failed");
    }
    exit();
}

// ============================================
// STEP 2: Verifikasi OTP & Reset Password
// ============================================
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
        error_log("Invalid OTP - Expected: {$customer['otp_code']}, Got: {$verification_code}");
        header("Location: forgot_password.php?step=2&phone=" . urlencode($phone) . "&error=invalid_code");
        exit();
    }

    // OTP VALID! Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $customer_id = $customer['id_customer'];

    // Update password langsung (tanpa prepared statement untuk test)
    $update_query = "UPDATE customers SET password = '{$hashed_password}', otp_code = NULL, otp_expires_at = NULL, updated_at = NOW() WHERE id_customer = {$customer_id}";
    
    error_log("=== PASSWORD UPDATE ===");
    error_log("Customer ID: {$customer_id}");
    error_log("Phone: {$phone}");
    error_log("Query: {$update_query}");
    error_log("=======================");

    if ($conn->query($update_query)) {
        error_log("✅ PASSWORD RESET SUCCESS");
        error_log("Phone: {$phone}");
        error_log("Time: " . date('Y-m-d H:i:s'));
        
        // Hapus session
        unset($_SESSION['forgot_password']);

        // Redirect ke login dengan pesan sukses
        header("Location: login.php?success=password_reset");
        exit();
    } else {
        error_log("❌ PASSWORD UPDATE FAILED");
        error_log("Error: " . $conn->error);
        error_log("Query: {$update_query}");
        
        header("Location: forgot_password.php?step=2&phone=" . urlencode($phone) . "&error=database");
        exit();
    }
}

// Jika akses langsung tanpa POST
header("Location: forgot_password.php");
exit();
?>