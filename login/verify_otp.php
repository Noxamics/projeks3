<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['otp_email'])) {
    header("Location: login.php?error=unauthorized");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_SESSION['otp_email'];
    $otp = trim($_POST['otp']);

    $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ? AND otp_code = ?");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && strtotime($admin['otp_expires_at']) > time()) {
        // OTP valid
        $_SESSION['user_id'] = $admin['id_admin'];
        $_SESSION['user_type'] = 'admin';
        header("Location: ../admin/dashboard.php");
        exit;
    } else {
        header("Location: verify_otp.php?error=invalid_otp");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Verifikasi OTP | SengkuClean</title>
    <link rel="stylesheet" href="/projeks3/css/login.css">
</head>

<body>
    <div class="login-page">
        <div class="login-box">
            <h2 style="text-align:center; margin-bottom:20px;">Verifikasi OTP</h2>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_otp'): ?>
                <p style="color:red; text-align:center;">Kode OTP salah atau sudah kedaluwarsa.</p>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="input-group">
                    <label for="otp">Masukkan Kode OTP</label>
                    <input type="text" id="otp" name="otp" maxlength="6" required>
                </div>

                <button type="submit" class="btn-login">Verifikasi</button>
            </form>
        </div>
    </div>
</body>

</html>