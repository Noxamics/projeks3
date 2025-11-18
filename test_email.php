<?php
require __DIR__ . '/vendor/autoload.php'; // sesuaikan jika folder vendor kamu di tempat lain
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Konfigurasi server SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'noxamics@gmail.com'; // email kamu
    $mail->Password = 'vitnggtchvqizrsu'; // App Password dari Gmail (tanpa spasi di kode)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // gunakan STARTTLS
    $mail->Port = 587;

    // Info tambahan
    $mail->CharSet = 'UTF-8';
    $mail->SMTPDebug = 2; // ubah ke 0 kalau tidak mau tampil log

    // Penerima & pengirim
    $mail->setFrom('noxamics@gmail.com', 'Tes SMTP SengkuClean');
    $mail->addAddress('ericksquad25@gmail.com', 'Admin'); // ganti dengan email penerima

    // Konten email
    $mail->isHTML(true);
    $mail->Subject = 'Tes Kirim Email via PHPMailer';
    $mail->Body = '<h3>Halo! 🎉</h3><p>Email ini dikirim menggunakan <b>PHPMailer + Gmail SMTP</b>.</p><p>Jika kamu menerima ini, berarti konfigurasi SMTP sudah <b>berhasil</b>.</p>';
    $mail->AltBody = 'Tes kirim email via PHPMailer. Jika kamu membaca ini, berarti koneksi SMTP berhasil.';

    $mail->send();
    echo '<h2 style="color:green;">✅ Email berhasil dikirim!</h2>';
} catch (Exception $e) {
    echo '<h2 style="color:red;">❌ Gagal mengirim email.</h2>';
    echo '<p><b>Error:</b> ' . htmlspecialchars($mail->ErrorInfo) . '</p>';
}
