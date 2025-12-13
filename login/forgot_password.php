<?php
require_once '../db.php';

// Jika sudah login, redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../customer/dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Lupa Password | SengkuClean</title>
    <link rel="icon" type="image/png" href="../a/img/Logo.png">
    <link rel="stylesheet" href="/projeks3/css/login.css" />
    <style>
        .email-notice {
            background: linear-gradient(135deg, #2196F3, #1976D2);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 15px 0;
            box-shadow: 0 4px 10px rgba(33, 150, 243, 0.3);
        }
        .email-notice h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: 600;
        }
        .email-notice .email {
            font-size: 16px;
            font-weight: 600;
            margin: 10px 0;
        }
        .email-notice small {
            display: block;
            margin-top: 10px;
            opacity: 0.9;
            font-size: 11px;
        }
        .code-display {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 15px 0;
            box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3);
        }
        .code-display h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: 600;
        }
        .code-display .code {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
        }
        .code-display small {
            display: block;
            margin-top: 10px;
            opacity: 0.9;
            font-size: 11px;
        }
        .warning-banner {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            color: #e65100;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <div class="logo">
                <a href="index.php">
                    <img src="../a/img/Logo Sengku.png" alt="SengkuClean Logo" />
                </a>
            </div>
            <div class="nav-right">
                <a href="login.php">LOGIN</a>
            </div>
        </div>
    </header>

    <div class="login-page">
        <div class="login-box">
            <img src="../a/img/Logo Sengku.png" alt="SengkuClean Logo" class="logo-login" />
            <h2 style="text-align:center; margin-bottom:20px;">LUPA PASSWORD</h2>

            <!-- Pesan Error/Success -->
            <?php if (isset($_GET['error'])): ?>
                <p style="color:red; text-align:center; margin-bottom:15px;">
                    <?php
                    if ($_GET['error'] === 'not_found') {
                        echo "❌ Nomor telepon tidak terdaftar!";
                    } elseif ($_GET['error'] === 'no_email') {
                        echo "❌ Akun Anda belum memiliki email terdaftar!<br><small>Silakan hubungi admin untuk menambahkan email.</small>";
                    } elseif ($_GET['error'] === 'invalid_email') {
                        echo "❌ Format email tidak valid!";
                    } elseif ($_GET['error'] === 'empty') {
                        echo "❌ Mohon isi semua field!";
                    } elseif ($_GET['error'] === 'invalid_code') {
                        echo "❌ Kode verifikasi salah!";
                    } elseif ($_GET['error'] === 'expired') {
                        echo "❌ Kode verifikasi sudah kadaluarsa!<br><small>Silakan minta kode baru.</small>";
                    } elseif ($_GET['error'] === 'password_mismatch') {
                        echo "❌ Password dan konfirmasi password tidak cocok!";
                    } elseif ($_GET['error'] === 'weak_password') {
                        echo "❌ Password minimal 6 karakter!";
                    }
                    ?>
                </p>
            <?php endif; ?>

            <?php if (isset($_GET['success']) && $_GET['success'] === 'code_sent'): ?>
                <p style="color:green; text-align:center; margin-bottom:15px;">
                    ✅ Kode verifikasi telah dikirim ke email Anda!
                </p>
            <?php endif; ?>

            <?php if (isset($_GET['success']) && $_GET['success'] === 'otp_generated'): ?>
                <p style="color:green; text-align:center; margin-bottom:15px;">
                    ✅ Kode OTP telah digenerate! Lihat tombol di bawah.
                </p>
            <?php endif; ?>

            <?php if (!isset($_GET['step']) || $_GET['step'] === '1'): ?>
                <!-- Step 1: Input Nomor Telepon -->
                <form action="process_forgot_password.php" method="POST">
                    <input type="hidden" name="step" value="1">

                    <div class="input-group">
                        <label for="phone">Nomor Handphone</label>
                        <input type="tel" id="phone" name="phone" 
                               placeholder="Masukkan nomor handphone terdaftar"
                               pattern="[0-9]{10,13}" required />
                        <small style="color:#666; font-size:11px; display:block; margin-top:5px;">
                            📧 Kode verifikasi akan dikirim ke email yang terdaftar
                        </small>
                    </div>

                    <button type="submit" class="btn-login">KIRIM KODE VERIFIKASI</button>

                    <p style="text-align:center; margin-top:15px; font-size:14px;">
                        Sudah ingat password? <a href="login.php" style="color:#4CAF50; text-decoration:none;">Login di sini</a>
                    </p>
                </form>

            <?php elseif ($_GET['step'] === '2'): ?>
                
                <!-- Notifikasi OTP Generated -->
                <?php if (!empty($_SESSION['forgot_password']['wa_link'])): ?>
                <div class="email-notice" style="background: linear-gradient(135deg, #25D366, #1da851);">
                    <h3>💬 KIRIM KODE KE WHATSAPP</h3>
                    <p style="margin: 15px 0; font-size: 13px;">
                        Klik tombol di bawah untuk mengirim kode OTP ke WhatsApp Anda
                    </p>
                    <a href="<?php echo htmlspecialchars($_SESSION['forgot_password']['wa_link']); ?>" 
                       target="_blank" 
                       style="display: inline-block; background: white; color: #25D366; padding: 12px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-top: 10px;">
                        📱 Kirim via WhatsApp
                    </a>
                </div>
                <?php endif; ?>

                <!-- Tampilkan Kode OTP Backup -->
                <?php if (!empty($_SESSION['forgot_password']['code'])): ?>
                <div class="code-display">
                    <h3>🔐 KODE VERIFIKASI BACKUP</h3>
                    <div class="code"><?php echo htmlspecialchars($_SESSION['forgot_password']['code']); ?></div>
                    <small>Gunakan kode ini jika perlu</small>
                    <small style="margin-top:5px;">⏱️ Berlaku 15 menit</small>
                </div>
                <?php endif; ?>

                <!-- Step 2: Input Kode Verifikasi & Password Baru -->
                <form action="process_forgot_password.php" method="POST">
                    <input type="hidden" name="step" value="2">
                    <input type="hidden" name="phone" value="<?php echo htmlspecialchars($_GET['phone'] ?? ''); ?>">

                    <div class="input-group">
                        <label for="verification_code">Kode Verifikasi</label>
                        <input type="text" id="verification_code" name="verification_code" 
                               placeholder="Masukkan kode 6 digit"
                               pattern="[0-9]{6}" maxlength="6" required 
                               style="font-size:18px; letter-spacing:3px; text-align:center;" />
                        <small style="color:#666; font-size:11px; display:block; margin-top:5px;">
                            Masukkan kode yang dikirim ke email Anda
                        </small>
                    </div>

                    <div class="input-group">
                        <label for="new_password">Password Baru</label>
                        <div class="password-wrapper">
                            <input type="password" id="new_password" name="new_password"
                                   placeholder="Masukkan password baru (min. 6 karakter)" 
                                   minlength="6" required />
                            <span class="toggle-password" onclick="togglePassword('new_password', 'eye_new')">
                                <img src="../a/svg/eye-off.svg" alt="Show Password" id="eye_new" />
                            </span>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="confirm_password">Konfirmasi Password Baru</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password"
                                   placeholder="Ketik ulang password baru" 
                                   minlength="6" required />
                            <span class="toggle-password" onclick="togglePassword('confirm_password', 'eye_confirm')">
                                <img src="../a/svg/eye-off.svg" alt="Show Password" id="eye_confirm" />
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">RESET PASSWORD</button>

                    <p style="text-align:center; margin-top:15px; font-size:14px;">
                        <a href="forgot_password.php" style="color:#4CAF50; text-decoration:none;">← Kirim ulang kode</a>
                    </p>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === 'password') {
                input.type = 'text';
                icon.src = '../a/svg/eye-on.svg';
            } else {
                input.type = 'password';
                icon.src = '../a/svg/eye-off.svg';
            }
        }
    </script>
</body>

</html>