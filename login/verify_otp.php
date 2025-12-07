<?php
session_start();
require_once '../db.php';

// Cek apakah ada session OTP email
if (!isset($_SESSION['otp_email'])) {
    header("Location: login.php?error=unauthorized");
    exit;
}

// Proses verifikasi OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_SESSION['otp_email'];
    $otp = trim($_POST['otp']);

    // Validasi input
    if (empty($otp)) {
        header("Location: verify_otp.php?error=empty");
        exit;
    }

    // Validasi format OTP (6 digit)
    if (!preg_match('/^[0-9]{6}$/', $otp)) {
        header("Location: verify_otp.php?error=invalid_format");
        exit;
    }

    // Query admin dengan OTP - ✅ PERBAIKAN: Gunakan full_name
    $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ? AND otp_code = ?");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin) {
        // Cek apakah OTP masih valid (belum expired)
        if (strtotime($admin['otp_expires_at']) > time()) {
            // OTP VALID - Login berhasil
            $_SESSION['user_id'] = $admin['id_admin'];
            $_SESSION['user_type'] = 'admin';
            $_SESSION['user_name'] = $admin['full_name'] ?? 'Admin'; // ✅ Gunakan full_name
            $_SESSION['user_email'] = $admin['email'];

            // Hapus OTP setelah digunakan (untuk keamanan)
            $clear_otp = $conn->prepare("UPDATE admin SET otp_code = NULL, otp_expires_at = NULL WHERE id_admin = ?");
            $clear_otp->bind_param("i", $admin['id_admin']);
            $clear_otp->execute();

            // Hapus session OTP
            unset($_SESSION['otp_email']);
            unset($_SESSION['otp_admin_id']);
            unset($_SESSION['display_otp']);

            // Log login success
            error_log("Admin login success: " . $admin['email'] . " (ID: " . $admin['id_admin'] . ")");

            // Redirect ke dashboard
            header("Location: ../admin/dashboard.php?login=success");
            exit;
        } else {
            // OTP expired
            error_log("OTP expired for: " . $email);
            header("Location: verify_otp.php?error=expired");
            exit;
        }
    } else {
        // OTP tidak valid
        error_log("Invalid OTP for: " . $email . " | Entered OTP: " . $otp);
        header("Location: verify_otp.php?error=invalid_otp");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP | SengkuClean</title>
    <link rel="icon" type="image/png" href="../a/img/Logo.png">
    <link rel="stylesheet" href="../css/login.css">
    <style>
        .verify-container {
            max-width: 450px;
            margin: 0 auto;
        }

        .otp-input-wrapper {
            margin: 25px 0;
        }

        #otp {
            text-align: center;
            font-size: 28px;
            letter-spacing: 8px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            padding: 20px;
            border: 3px solid #ddd;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        #otp:focus {
            border-color: #154283;
            outline: none;
            box-shadow: 0 0 0 3px rgba(21, 66, 131, 0.1);
        }

        .timer-box {
            text-align: center;
            background: #FFF3E0;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #FF9800;
        }

        .timer-box p {
            margin: 0;
            color: #E65100;
            font-weight: 600;
            font-size: 16px;
        }

        #countdown {
            font-size: 20px;
            font-weight: bold;
            color: #F57C00;
        }

        .countdown-expired {
            color: #D32F2F !important;
        }

        .resend-section {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .resend-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #154283;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            transition: background 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .resend-btn:hover {
            background: #0d2f5e;
        }

        .resend-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .back-link {
            text-align: center;
            margin-top: 15px;
        }

        .back-link a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .dev-mode-box {
            background: #E8F5E9;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border: 2px solid #4CAF50;
            text-align: center;
        }

        .dev-mode-box h3 {
            margin: 0 0 10px 0;
            color: #2E7D32;
            font-size: 16px;
        }

        .dev-otp-code {
            font-size: 42px;
            letter-spacing: 10px;
            color: #1B5E20;
            font-weight: bold;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            cursor: pointer;
        }

        .dev-mode-warning {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
        }

        .alert-box {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .alert-success {
            background: #E8F5E9;
            color: #2E7D32;
            border-left: 4px solid #4CAF50;
        }

        .alert-error {
            background: #FFEBEE;
            color: #C62828;
            border-left: 4px solid #F44336;
        }

        .email-display {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 6px;
            margin: 10px 0;
            font-family: monospace;
            color: #154283;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <div class="logo">
                <a href="../public/index.php">
                    <img src="../a/img/Logo Sengku.png" alt="SengkuClean Logo" />
                </a>
            </div>
            <div class="nav-right">
                <a href="../public/index.php">HOME</a>
            </div>
        </div>
    </header>

    <div class="login-page">
        <div class="login-box verify-container">
            <img src="../a/img/Logo Sengku.png" alt="SengkuClean Logo" class="logo-login" />

            <h2 style="text-align:center; margin-bottom:10px;">Verifikasi OTP</h2>

            <p style="text-align:center; color:#666; font-size:14px; margin-bottom:20px;">
                Kode OTP telah dikirim ke:
            </p>
            <div class="email-display">
                <?php echo htmlspecialchars($_SESSION['otp_email']); ?>
            </div>

            <!-- Success Message -->
            <?php if (isset($_GET['success']) && $_GET['success'] === 'otp_sent'): ?>
                <div class="alert-box alert-success">
                    <strong>✅ Kode OTP berhasil dikirim!</strong><br>
                    <small>Cek inbox atau folder spam email Anda</small>
                </div>
            <?php endif; ?>

            <!-- Error Messages -->
            <?php if (isset($_GET['error'])): ?>
                <div class="alert-box alert-error">
                    <?php
                    switch ($_GET['error']) {
                        case 'invalid_otp':
                            echo "<strong>❌ Kode OTP salah!</strong><br><small>Silakan coba lagi</small>";
                            break;
                        case 'expired':
                            echo "<strong>⏰ Kode OTP sudah kedaluwarsa!</strong><br><small>Silakan minta kode baru</small>";
                            break;
                        case 'empty':
                            echo "<strong>⚠️ Mohon masukkan kode OTP!</strong>";
                            break;
                        case 'invalid_format':
                            echo "<strong>❌ Format OTP tidak valid!</strong><br><small>Harus 6 digit angka</small>";
                            break;
                        default:
                            echo "<strong>❌ Terjadi kesalahan!</strong><br><small>Silakan coba lagi</small>";
                    }
                    ?>
                </div>
            <?php endif; ?>

            <!-- Development Mode: Display OTP -->
            <?php if (isset($_GET['debug_otp'])): ?>
                <div class="dev-mode-box">
                    <h3>🔧 MODE DEVELOPMENT</h3>
                    <p style="font-size:12px; color:#666; margin:5px 0;">
                        Email server belum aktif. Gunakan kode ini:
                    </p>
                    <div class="dev-otp-code" onclick="copyOTP('<?php echo $_GET['debug_otp']; ?>')">
                        <?php echo $_GET['debug_otp']; ?>
                    </div>
                    <p class="dev-mode-warning">
                        <strong>Peringatan:</strong> Hapus fitur ini saat production!<br>
                        <small>Klik kode untuk copy</small>
                    </p>
                </div>
            <?php endif; ?>

            <!-- OTP Form -->
            <form method="POST" action="" id="otp-form">
                <div class="input-group otp-input-wrapper">
                    <label for="otp">Masukkan Kode OTP (6 Digit)</label>
                    <input type="text" id="otp" name="otp" maxlength="6" placeholder="000000" pattern="[0-9]{6}"
                        required autocomplete="off" autofocus>
                </div>

                <div class="timer-box">
                    <p>
                        Kode berlaku selama: <span id="countdown">5:00</span>
                    </p>
                </div>

                <button type="submit" class="btn-login" id="verify-btn">
                    Verifikasi Sekarang
                </button>
            </form>

            <!-- Resend Section -->
            <div class="resend-section">
                <p style="font-size:14px; color:#666; margin-bottom:10px;">
                    Tidak menerima kode?
                </p>
                <form action="send_otp.php" method="POST" style="display:inline;">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['otp_email']); ?>">
                    <button type="submit" class="resend-btn" id="resend-btn" disabled>
                        Kirim Ulang Kode (<span id="resend-timer">60</span>s)
                    </button>
                </form>
            </div>

            <div class="back-link">
                <a href="login.php">← Kembali ke Login</a>
            </div>
        </div>
    </div>

    <script>
        // Copy OTP function
        function copyOTP(otp) {
            navigator.clipboard.writeText(otp).then(function () {
                alert('✅ Kode OTP berhasil dicopy!');
                document.getElementById('otp').value = otp;
                document.getElementById('otp').focus();
            });
        }

        // OTP Input: Auto format and validate
        const otpInput = document.getElementById('otp');

        otpInput.addEventListener('input', function (e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Countdown Timer (5 menit)
        let timeLeft = 300;
        const countdownElement = document.getElementById('countdown');

        const countdownTimer = setInterval(function () {
            timeLeft--;

            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;

            countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

            if (timeLeft < 60) {
                countdownElement.classList.add('countdown-expired');
            }

            if (timeLeft <= 0) {
                clearInterval(countdownTimer);
                countdownElement.textContent = 'EXPIRED';

                document.getElementById('verify-btn').disabled = true;
                document.getElementById('verify-btn').textContent = 'Kode Kedaluwarsa';
                document.getElementById('verify-btn').style.background = '#ccc';

                alert('⏰ Kode OTP telah kedaluwarsa! Silakan minta kode baru.');
            }
        }, 1000);

        // Resend Button Timer (60 detik)
        let resendTimeLeft = 60;
        const resendBtn = document.getElementById('resend-btn');
        const resendTimerElement = document.getElementById('resend-timer');

        const resendTimer = setInterval(function () {
            resendTimeLeft--;
            resendTimerElement.textContent = resendTimeLeft;

            if (resendTimeLeft <= 0) {
                clearInterval(resendTimer);
                resendBtn.disabled = false;
                resendBtn.innerHTML = 'Kirim Ulang Kode';
            }
        }, 1000);

        // Auto focus
        window.addEventListener('load', function () {
            otpInput.focus();
        });

        // Prevent form submission if OTP not complete
        document.getElementById('otp-form').addEventListener('submit', function (e) {
            const otp = otpInput.value;

            if (otp.length !== 6 || !/^[0-9]{6}$/.test(otp)) {
                e.preventDefault();
                alert('❌ Kode OTP harus 6 digit angka!');
                otpInput.focus();
                return false;
            }
        });
    </script>
</body>

</html>