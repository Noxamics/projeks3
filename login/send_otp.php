<?php
session_start();
require_once '../db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        header("Location: login.php?error=empty");
        exit;
    }

    // Cek apakah email admin terdaftar
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
    $update->execute();

    // Simpan ke session
    $_SESSION['otp_email'] = $email;

    // Kirim email dengan PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Konfigurasi server SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'noxamics@gmail.com'; // ganti
        $mail->Password = 'pkfmdmhjfuexfeio';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Penerima
        $mail->setFrom('email_pengirim@gmail.com', 'SengkuClean Admin Login');
        $mail->addAddress($email);

        // Konten
        $mail->isHTML(true);
        $mail->Subject = 'Kode OTP Login Admin';
        $mail->Body = "
            <p>Halo,</p>
            <p>Kode OTP Anda adalah: <b>$otp</b></p>
            <p>Berlaku selama 5 menit.</p>
            <p>Jangan bagikan kode ini ke siapa pun.</p>
        ";

        $mail->send();

        header("Location: verify_otp.php");
        exit;
    } catch (Exception $e) {
        header("Location: login.php?error=email_failed&msg=" . urlencode($mail->ErrorInfo));
        exit;
    }
} else {
    header("Location: login.php");
    exit;
}
