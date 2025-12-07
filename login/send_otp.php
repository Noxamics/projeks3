<?php
session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        header("Location: login.php?error=empty");
        exit;
    }

    // ✅ PERBAIKAN: Cek apakah email admin terdaftar (gunakan full_name)
    $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: login.php?error=email_not_found");
        exit;
    }

    $admin = $result->fetch_assoc();

    // Generate OTP (6 digit)
    $otp = rand(100000, 999999);
    $otp_expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

    // Simpan OTP ke database
    $update = $conn->prepare("UPDATE admin SET otp_code = ?, otp_expires_at = ? WHERE email = ?");
    $update->bind_param("sss", $otp, $otp_expiry, $email);

    if (!$update->execute()) {
        error_log("Failed to save OTP: " . $conn->error);
        header("Location: login.php?error=system_error");
        exit;
    }

    // Simpan ke session
    $_SESSION['otp_email'] = $email;
    $_SESSION['otp_admin_id'] = $admin['id_admin'];

    // ✅ PERBAIKAN: Ambil nama admin dari full_name
    $admin_name = $admin['full_name'] ?? 'Admin';

    // ===== KIRIM EMAIL MENGGUNAKAN PHP mail() =====
    $to = $email;
    $subject = "Kode OTP Login Admin SengkuClean";

    // HTML Email Content
    $message = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background-color: #f4f4f4; 
            margin: 0;
            padding: 20px;
        }
        .email-container { 
            background: white; 
            padding: 30px; 
            border-radius: 10px; 
            max-width: 500px; 
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header { 
            text-align: center; 
            color: #154283; 
            margin-bottom: 20px; 
        }
        .header h2 {
            margin: 0;
            font-size: 24px;
        }
        .otp-box { 
            background: linear-gradient(135deg, #154283 0%, #1e5aa8 100%); 
            padding: 30px; 
            text-align: center; 
            border-radius: 12px; 
            margin: 25px 0;
            box-shadow: 0 4px 15px rgba(21,66,131,0.3);
        }
        .otp-code { 
            color: white; 
            font-size: 48px; 
            margin: 0; 
            letter-spacing: 10px; 
            font-weight: bold;
            font-family: "Courier New", monospace;
        }
        .info-box {
            background: #FFF3E0;
            padding: 15px;
            border-left: 4px solid #FF9800;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info-box p {
            margin: 0;
            color: #E65100;
            font-weight: 600;
        }
        .warning-box {
            background: #FFEBEE;
            padding: 15px;
            border-left: 4px solid #F44336;
            border-radius: 5px;
            margin: 20px 0;
        }
        .warning-box p {
            margin: 0;
            color: #C62828;
            font-weight: bold;
        }
        .footer { 
            font-size: 12px; 
            color: #999; 
            text-align: center; 
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .greeting {
            font-size: 16px;
            color: #333;
            margin-bottom: 15px;
        }
        .instructions {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h2>🔐 Kode OTP Login Admin</h2>
        </div>
        
        <div class="greeting">
            <p>Halo <strong>' . htmlspecialchars($admin_name) . '</strong>,</p>
        </div>
        
        <p class="instructions">
            Anda telah meminta kode OTP untuk login ke sistem SengkuClean. 
            Gunakan kode berikut untuk melanjutkan proses login:
        </p>
        
        <div class="otp-box">
            <div class="otp-code">' . $otp . '</div>
        </div>
        
        <div class="info-box">
            <p>⏰ Kode ini berlaku selama <strong>5 menit</strong></p>
        </div>
        
        <div class="warning-box">
            <p>⚠️ JANGAN BAGIKAN KODE INI KEPADA SIAPA PUN!</p>
        </div>
        
        <p class="instructions">
            Masukkan kode di atas pada halaman verifikasi untuk melanjutkan login ke dashboard admin.
        </p>
        
        <div class="footer">
            <p>
                Jika Anda tidak meminta kode ini, abaikan email ini atau segera hubungi administrator.
            </p>
            <p style="margin-top: 10px;">
                Email ini dikirim secara otomatis, mohon tidak membalas email ini.
            </p>
            <p style="margin-top: 15px; font-weight: 600; color: #154283;">
                © 2025 SengkuClean - Sistem Manajemen Laundry Sepatu
            </p>
        </div>
    </div>
</body>
</html>';

    // Headers untuk HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: SengkuClean Admin <noreply@sengkuclean.mif.myhost.id>" . "\r\n";
    $headers .= "Reply-To: noreply@sengkuclean.mif.myhost.id" . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "X-Priority: 1" . "\r\n";

    // Kirim email
    $mail_sent = @mail($to, $subject, $message, $headers);

    if ($mail_sent) {
        error_log("OTP sent successfully to: " . $email . " | OTP: " . $otp);
        header("Location: verify_otp.php?success=otp_sent");
        exit;
    } else {
        // Email gagal, redirect dengan debug OTP (untuk development)
        error_log("Failed to send OTP email to: " . $email . " | OTP: " . $otp);

        // ✅ DEVELOPMENT MODE: Tampilkan OTP di URL
        // ⚠️ HAPUS PARAMETER debug_otp SAAT PRODUCTION!
        header("Location: verify_otp.php?success=otp_sent&debug_otp=" . $otp);
        exit;
    }
} else {
    header("Location: login.php");
    exit;
}
?>